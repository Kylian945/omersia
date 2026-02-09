<?php

declare(strict_types=1);

namespace Omersia\Catalog\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Catalog\Models\Category;
use Omersia\Shared\Contracts\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function getByShopId(int $shopId): Collection;

    public function getActiveCategories(?int $shopId = null): Collection;

    public function getRootCategories(?int $shopId = null): Collection;

    public function getChildCategories(int $parentId): Collection;

    public function getCategoryTree(?int $shopId = null): Collection;

    public function findBySlug(string $slug, ?int $shopId = null): ?Category;

    public function updatePosition(int $categoryId, int $position): bool;

    public function moveToParent(int $categoryId, ?int $parentId): bool;

    public function attachProducts(int $categoryId, array $productIds): void;

    public function syncProducts(int $categoryId, array $productIds): void;
}
