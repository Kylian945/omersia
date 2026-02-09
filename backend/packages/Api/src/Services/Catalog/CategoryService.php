<?php

declare(strict_types=1);

namespace Omersia\Api\Services\Catalog;

use Illuminate\Support\Collection;
use Omersia\Catalog\Models\Category;

final class CategoryService
{
    public function findAccueil(string $locale): ?Category
    {
        return Category::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale)
                    ->where('slug', 'accueil');
            })
            ->first();
    }

    /**
     * @return Collection<int, Category>
     */
    public function listCategories(Category $accueil, string $locale, bool $parentOnly): Collection
    {
        $query = Category::query()
            ->where('is_active', true)
            ->where('id', '!=', $accueil->id);

        if ($parentOnly) {
            $query->where('parent_id', $accueil->id);
        }

        return $query
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
            ])
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->where('is_active', true);
                },
            ])
            ->orderBy('position')
            ->get();
    }

    public function findBySlug(string $slug, string $locale): Category
    {
        return Category::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($slug, $locale) {
                $q->where('locale', $locale)
                    ->where('slug', $slug);
            })
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'children' => function ($q) use ($locale) {
                    $q->where('is_active', true)
                        ->orderBy('position')
                        ->with([
                            'translations' => function ($t) use ($locale) {
                                $t->where('locale', $locale);
                            },
                            'children' => function ($qq) use ($locale) {
                                $qq->where('is_active', true)
                                    ->orderBy('position')
                                    ->with([
                                        'translations' => function ($tt) use ($locale) {
                                            $tt->where('locale', $locale);
                                        },
                                    ]);
                            },
                        ]);
                },
                'parent' => function ($q) use ($locale) {
                    $q->where('is_active', true)
                        ->orderBy('position')
                        ->with([
                            'translations' => function ($t) use ($locale) {
                                $t->where('locale', $locale);
                            },
                        ]);
                },
            ])
            ->firstOrFail();
    }
}
