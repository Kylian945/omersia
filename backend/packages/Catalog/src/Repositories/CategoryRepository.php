<?php

declare(strict_types=1);

namespace Omersia\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Omersia\Catalog\Contracts\CategoryRepositoryInterface;
use Omersia\Catalog\Models\Category;
use Omersia\Shared\Repositories\BaseRepository;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getByShopId(int $shopId): Collection
    {
        return $this->model->where('shop_id', $shopId)->get();
    }

    public function getActiveCategories(?int $shopId = null): Collection
    {
        $query = $this->model->where('is_active', true);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->get();
    }

    public function getRootCategories(?int $shopId = null): Collection
    {
        $query = $this->model->whereNull('parent_id');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->orderBy('position')->get();
    }

    public function getChildCategories(int $parentId): Collection
    {
        return $this->model
            ->where('parent_id', $parentId)
            ->orderBy('position')
            ->get();
    }

    public function getCategoryTree(?int $shopId = null): Collection
    {
        $query = $this->model->with('children');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->whereNull('parent_id')
            ->orderBy('position')
            ->get();
    }

    public function findBySlug(string $slug, ?int $shopId = null): ?Category
    {
        $query = $this->model->whereHas('translations', function ($q) use ($slug) {
            $q->where('slug', $slug);
        });

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->first();
    }

    public function updatePosition(int $categoryId, int $position): bool
    {
        $category = $this->findOrFail($categoryId);

        return $category->update(['position' => $position]);
    }

    public function moveToParent(int $categoryId, ?int $parentId): bool
    {
        $category = $this->findOrFail($categoryId);

        return $category->update(['parent_id' => $parentId]);
    }

    public function attachProducts(int $categoryId, array $productIds): void
    {
        $category = $this->findOrFail($categoryId);
        $category->products()->attach($productIds);
    }

    public function syncProducts(int $categoryId, array $productIds): void
    {
        $category = $this->findOrFail($categoryId);
        $category->products()->sync($productIds);
    }
}
