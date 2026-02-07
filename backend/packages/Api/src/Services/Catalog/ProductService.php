<?php

declare(strict_types=1);

namespace Omersia\Api\Services\Catalog;

use Illuminate\Pagination\LengthAwarePaginator;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;

final class ProductService
{
    public function paginate(?string $categorySlug, string $locale, int $limit): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('is_active', true);

        if ($categorySlug) {
            $query->whereHas('categories.translations', function ($q) use ($categorySlug, $locale) {
                $q->where('locale', $locale)
                    ->where('slug', $categorySlug);
            });
        }

        $query->with('images')
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'categories' => function ($q) use ($locale) {
                    $q->where('is_active', true)
                        ->with(['translations' => function ($tq) use ($locale) {
                            $tq->where('locale', $locale);
                        }]);
                },
            ])
            ->with(['variants' => function ($q) {
                $q->where('is_active', true);
            }]);

        return $query->paginate($limit);
    }

    public function findBySlug(string $slug, string $locale): Product
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($slug, $locale) {
                $q->where('locale', $locale)
                    ->where('slug', $slug);
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'images',
                'categories' => function ($q) use ($locale) {
                    $q->where('is_active', true)
                        ->with(['translations' => function ($tq) use ($locale) {
                            $tq->where('locale', $locale);
                        }]);
                },
                'relatedProducts.translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'relatedProducts.images',
                'relatedProducts.variants',
                'options.values',
                'variants.values.option',
            ])
            ->firstOrFail();
    }

    public function paginateByCategory(Category $category, string $locale, int $perPage = 20): LengthAwarePaginator
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->with('images')
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'variants' => function ($q) {
                    $q->where('is_active', true);
                },
            ])
            ->paginate($perPage);
    }
}
