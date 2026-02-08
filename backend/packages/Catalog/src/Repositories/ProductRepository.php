<?php

declare(strict_types=1);

namespace Omersia\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Omersia\Catalog\Contracts\ProductRepositoryInterface;
use Omersia\Catalog\Models\Product;
use Omersia\Shared\Repositories\BaseRepository;

/** @extends BaseRepository<Product> */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku, ?int $shopId = null): ?Product
    {
        $query = $this->model
            ->where('sku', $sku)
            ->with(['translations', 'images', 'categories', 'mainImage']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->first();
    }

    public function getByShopId(int $shopId): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->with(['translations', 'images', 'categories', 'mainImage'])
            ->get();
    }

    public function getActiveProducts(?int $shopId = null): Collection
    {
        $query = $this->model
            ->where('is_active', true)
            ->with(['translations', 'images', 'categories', 'mainImage']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->get();
    }

    public function getByCategory(int $categoryId, ?int $shopId = null): Collection
    {
        $query = $this->model
            ->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            })
            ->with(['translations', 'images', 'categories', 'mainImage']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->get();
    }

    public function searchProducts(string $query, ?int $shopId = null, int $perPage = 15): LengthAwarePaginator
    {
        $queryBuilder = $this->model
            ->where(function ($q) use ($query) {
                $q->where('sku', 'LIKE', "%{$query}%")
                    ->orWhereHas('translations', function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    });
            })
            ->with(['translations', 'images', 'categories', 'mainImage']);

        if ($shopId) {
            $queryBuilder->where('shop_id', $shopId);
        }

        return $queryBuilder->paginate($perPage);
    }

    public function getFeaturedProducts(?int $shopId = null, int $limit = 10): Collection
    {
        $query = $this->model
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['translations', 'images', 'categories', 'mainImage']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->limit($limit)->get();
    }

    public function getNewArrivals(?int $shopId = null, int $limit = 10): Collection
    {
        $query = $this->model
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->with(['translations', 'images', 'categories', 'mainImage']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->limit($limit)->get();
    }

    public function updateStock(int $productId, int $quantity): bool
    {
        $product = $this->findOrFail($productId);

        return $product->update(['stock_qty' => $quantity]);
    }

    public function decrementStock(int $productId, int $quantity): bool
    {
        $product = $this->findOrFail($productId);

        if ($product->stock_qty < $quantity) {
            return false;
        }

        $product->decrement('stock_qty', $quantity);

        return true;
    }

    public function incrementStock(int $productId, int $quantity): bool
    {
        $product = $this->findOrFail($productId);
        $product->increment('stock_qty', $quantity);

        return true;
    }

    public function attachCategories(int $productId, array $categoryIds): void
    {
        $product = $this->findOrFail($productId);
        $product->categories()->attach($categoryIds);
    }

    public function syncCategories(int $productId, array $categoryIds): void
    {
        $product = $this->findOrFail($productId);
        $product->categories()->sync($categoryIds);
    }

    public function attachImages(int $productId, array $images): void
    {
        $product = $this->findOrFail($productId);

        foreach ($images as $image) {
            $product->images()->create($image);
        }
    }

    public function getRelatedProducts(int $productId, int $limit = 4): Collection
    {
        $product = $this->findOrFail($productId);

        return $product->relatedProducts()
            ->with(['translations', 'images', 'categories', 'mainImage'])
            ->limit($limit)
            ->get();
    }
}
