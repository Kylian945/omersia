<?php

declare(strict_types=1);

namespace Omersia\Catalog\Services;

use App\Events\Realtime\ProductStockUpdated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductVariant;
use RuntimeException;

class OrderInventoryService
{
    /**
     * Décrémente le stock des lignes de commande une seule fois.
     *
     * @throws RuntimeException
     */
    public function deductStockForOrder(Order $order): void
    {
        $productIdsToBroadcast = DB::transaction(function () use ($order): array {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            $meta = is_array($lockedOrder->meta) ? $lockedOrder->meta : [];
            if (! empty($meta['inventory_deducted_at'])) {
                return [];
            }

            $items = $lockedOrder->items;
            if ($items->isEmpty()) {
                $meta['inventory_deducted_at'] = now()->toIso8601String();
                $meta['inventory_deducted_reason'] = 'order_confirmation';
                $lockedOrder->meta = $meta;
                $lockedOrder->save();

                return [];
            }

            $variantIds = $items->pluck('variant_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $productIds = $items->pluck('product_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $variantsById = $this->loadLockedVariants($variantIds);
            $productsById = $this->loadLockedProducts($productIds);

            $affectedProductIds = [];

            foreach ($items as $item) {
                $quantity = max(0, (int) $item->quantity);
                if ($quantity === 0) {
                    continue;
                }

                $variantId = $item->variant_id ? (int) $item->variant_id : null;
                if ($variantId !== null) {
                    $variant = $variantsById->get($variantId);
                    if (! $variant) {
                        throw new RuntimeException("Variant #{$variantId} introuvable pour la commande #{$lockedOrder->id}.");
                    }

                    if ((bool) $variant->manage_stock) {
                        if ((int) $variant->stock_qty < $quantity) {
                            throw new RuntimeException("Stock insuffisant pour la variante #{$variantId}.");
                        }

                        $variant->decrement('stock_qty', $quantity);
                    }

                    $affectedProductIds[(int) $variant->product_id] = true;

                    continue;
                }

                $productId = $item->product_id ? (int) $item->product_id : null;
                if ($productId === null) {
                    continue;
                }

                $product = $productsById->get($productId);
                if (! $product) {
                    throw new RuntimeException("Produit #{$productId} introuvable pour la commande #{$lockedOrder->id}.");
                }

                if ((bool) $product->manage_stock) {
                    if ((int) $product->stock_qty < $quantity) {
                        throw new RuntimeException("Stock insuffisant pour le produit #{$productId}.");
                    }

                    $product->decrement('stock_qty', $quantity);
                }

                $affectedProductIds[$productId] = true;
            }

            $meta['inventory_deducted_at'] = now()->toIso8601String();
            $meta['inventory_deducted_reason'] = 'order_confirmation';
            $lockedOrder->meta = $meta;
            $lockedOrder->save();

            return array_map('intval', array_keys($affectedProductIds));
        });

        if ($productIdsToBroadcast === []) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $productIdsToBroadcast)
            ->withCount('variants')
            ->withSum(
                'variants as variants_stock_qty',
                'stock_qty'
            )
            ->get();

        foreach ($products as $product) {
            event(ProductStockUpdated::fromModel($product));
        }
    }

    /**
     * @param  Collection<int, int>  $variantIds
     * @return Collection<int, ProductVariant>
     */
    private function loadLockedVariants(Collection $variantIds): Collection
    {
        if ($variantIds->isEmpty()) {
            return collect();
        }

        return ProductVariant::query()
            ->whereIn('id', $variantIds->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @return Collection<int, Product>
     */
    private function loadLockedProducts(Collection $productIds): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $productIds->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
