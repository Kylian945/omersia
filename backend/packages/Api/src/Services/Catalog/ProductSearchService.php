<?php

declare(strict_types=1);

namespace Omersia\Api\Services\Catalog;

use Illuminate\Support\Collection;
use Omersia\Catalog\Models\Product;

final class ProductSearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{query: string, products: Collection<int, Product>, facets: array<string, mixed>}
     */
    public function search(string $query, string $locale, int $limit, array $filters = []): array
    {
        $query = trim($query);

        if ($query === '') {
            return [
                'query' => $query,
                'products' => collect(),
                'facets' => [
                    'categories' => [],
                    'price_range' => ['min' => 0, 'max' => 0],
                ],
            ];
        }

        $categoryIds = $this->parseCategoryIds($filters['categories'] ?? null);
        $minPrice = isset($filters['min_price']) ? (float) $filters['min_price'] : null;
        $maxPrice = isset($filters['max_price']) ? (float) $filters['max_price'] : null;
        $inStockOnly = filter_var($filters['in_stock_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $results = Product::search($query)
            ->where('is_active', true)
            ->take($limit * 3)
            ->get();

        $results->load([
            'images',
            'translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            },
            'categories.translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            },
            'variants' => function ($q) {
                $q->where('is_active', true);
            },
        ]);

        if (! empty($categoryIds)) {
            $results = $results->filter(function ($product) use ($categoryIds) {
                if ($product->categories->isEmpty()) {
                    return false;
                }

                return $product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty();
            });
        }

        if ($inStockOnly) {
            $results = $results->filter(function ($product) {
                $stockQty = $product->stock_qty;

                if ($product->type === 'variant' && $product->relationLoaded('variants')) {
                    $stockQty = $product->variants
                        ->where('is_active', true)
                        ->sum('stock_qty');
                }

                return ($stockQty ?? 0) > 0;
            });
        }

        if ($minPrice !== null && $minPrice > 0) {
            $results = $results->filter(function ($product) use ($minPrice) {
                return ($product->price ?? 0) >= $minPrice;
            });
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $results = $results->filter(function ($product) use ($maxPrice) {
                return ($product->price ?? 0) <= $maxPrice;
            });
        }

        $results = $results->take($limit)->values();

        $facets = $this->buildFacets($results);

        return [
            'query' => $query,
            'products' => $results,
            'facets' => $facets,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function parseCategoryIds(?string $categoryIdsParam): array
    {
        if (! is_string($categoryIdsParam) || $categoryIdsParam === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $categoryIdsParam))));
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function buildFacets(Collection $products): array
    {
        $categories = [];
        $prices = [];

        foreach ($products as $product) {
            if ($product->categories->isNotEmpty()) {
                foreach ($product->categories as $category) {
                    $catId = $category->id;
                    if (! isset($categories[$catId])) {
                        $translation = $category->translations->first();
                        $categories[$catId] = [
                            'id' => $catId,
                            'name' => $translation?->name ?? 'Catégorie',
                            'slug' => $translation?->slug ?? '',
                            'count' => 0,
                        ];
                    }
                    $categories[$catId]['count']++;
                }
            }

            if ($product->price > 0) {
                $prices[] = $product->price;
            }
        }

        $priceRange = [
            'min' => ! empty($prices) ? floor(min($prices)) : 0,
            'max' => ! empty($prices) ? ceil(max($prices)) : 0,
        ];

        return [
            'categories' => array_values($categories),
            'price_range' => $priceRange,
        ];
    }
}
