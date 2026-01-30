<?php

declare(strict_types=1);

namespace Omersia\Catalog\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Omersia\Catalog\Models\Product;
use Omersia\Shared\Contracts\RepositoryInterface;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findBySku(string $sku, ?int $shopId = null): ?Product;

    public function getByShopId(int $shopId): Collection;

    public function getActiveProducts(?int $shopId = null): Collection;

    public function getByCategory(int $categoryId, ?int $shopId = null): Collection;

    public function searchProducts(string $query, ?int $shopId = null, int $perPage = 15): LengthAwarePaginator;

    public function getFeaturedProducts(?int $shopId = null, int $limit = 10): Collection;

    public function getNewArrivals(?int $shopId = null, int $limit = 10): Collection;

    public function updateStock(int $productId, int $quantity): bool;

    public function decrementStock(int $productId, int $quantity): bool;

    public function incrementStock(int $productId, int $quantity): bool;

    public function attachCategories(int $productId, array $categoryIds): void;

    public function syncCategories(int $productId, array $categoryIds): void;

    public function attachImages(int $productId, array $images): void;

    public function getRelatedProducts(int $productId, int $limit = 4): Collection;
}
