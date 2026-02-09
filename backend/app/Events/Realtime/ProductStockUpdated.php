<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Product;

class ProductStockUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{
     *     id:int,
     *     type:string,
     *     manage_stock:bool,
     *     stock_qty:int,
     *     variants_count:int
     * }  $product
     */
    public function __construct(
        public readonly array $product
    ) {}

    public static function fromModel(Product $product): self
    {
        $variantCount = $product->getAttribute('variants_count') !== null
            ? (int) $product->getAttribute('variants_count')
            : (int) $product->variants()->count();
        $isVariant = $product->type === 'variant' || $variantCount > 0;

        if ($isVariant) {
            $preloadedVariantsStock = $product->getAttribute('variants_stock_qty');
            $globalStockQty = $preloadedVariantsStock !== null
                ? (int) $preloadedVariantsStock
                : (int) $product->variants()
                    ->sum('stock_qty');
        } else {
            $globalStockQty = (int) $product->stock_qty;
        }

        return new self([
            'id' => (int) $product->id,
            'type' => (string) $product->type,
            'manage_stock' => (bool) $product->manage_stock,
            'stock_qty' => $globalStockQty,
            'variants_count' => $variantCount,
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.products')];
    }

    public function broadcastAs(): string
    {
        return 'products.stock.updated';
    }

    /**
     * @return array{
     *     product:array{
     *         id:int,
     *         type:string,
     *         manage_stock:bool,
     *         stock_qty:int,
     *         variants_count:int
     *     }
     * }
     */
    public function broadcastWith(): array
    {
        return [
            'product' => $this->product,
        ];
    }
}
