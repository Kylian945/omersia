<?php

declare(strict_types=1);

namespace Omersia\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Catalog\Contracts\ProductVariantRepositoryInterface;
use Omersia\Catalog\Models\ProductVariant;
use Omersia\Shared\Repositories\BaseRepository;

/** @extends BaseRepository<ProductVariant> */
class ProductVariantRepository extends BaseRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariant $model)
    {
        parent::__construct($model);
    }

    public function getByProductId(int $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->get();
    }

    public function findBySku(string $sku, ?int $productId = null): ?ProductVariant
    {
        $query = $this->model->where('sku', $sku);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->first();
    }

    public function findByOptions(int $productId, array $optionValues): ?ProductVariant
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('options', json_encode($optionValues))
            ->first();
    }

    public function getInStockVariants(int $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('stock_qty', '>', 0)
            ->get();
    }

    public function updateStock(int $variantId, int $quantity): bool
    {
        $variant = $this->findOrFail($variantId);

        return $variant->update(['stock_qty' => $quantity]);
    }

    public function decrementStock(int $variantId, int $quantity): bool
    {
        $variant = $this->findOrFail($variantId);

        if ($variant->stock_qty < $quantity) {
            return false;
        }

        $variant->decrement('stock_qty', $quantity);

        return true;
    }

    public function incrementStock(int $variantId, int $quantity): bool
    {
        $variant = $this->findOrFail($variantId);

        $variant->increment('stock_qty', $quantity);

        return true;
    }
}
