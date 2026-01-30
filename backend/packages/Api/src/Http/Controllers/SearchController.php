<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Catalog\Models\Product;
use OpenApi\Annotations as OA;

class SearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/search",
     *     summary="Recherche de produits",
     *     tags={"Recherche"},
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Terme de recherche",
     *
     *         @OA\Schema(type="string", example="t-shirt")
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="fr")
     *     ),
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Nombre de résultats par page",
     *
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Résultats de recherche",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="query", type="string"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/ProductListResponse")
     *             )
     *         )
     *     )
     * )
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $locale = $request->get('locale', 'fr');
        $limit = (int) $request->get('limit', 20);

        // Filtres
        $categoryIdsParam = $request->get('categories', '');
        $categoryIds = ! empty($categoryIdsParam) && is_string($categoryIdsParam)
            ? array_map('intval', explode(',', $categoryIdsParam))
            : [];

        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $inStockOnly = filter_var($request->get('in_stock_only', false), FILTER_VALIDATE_BOOLEAN);

        // Si pas de query, retourner un résultat vide
        if (empty(trim($query))) {
            return response()->json([
                'query' => $query,
                'total' => 0,
                'products' => [],
                'facets' => [
                    'categories' => [],
                    'price_range' => ['min' => 0, 'max' => 0],
                ],
            ]);
        }

        // Recherche avec Scout/Meilisearch sans filtres pour obtenir tous les résultats
        // Les filtres seront appliqués après car Meilisearch ne supporte pas bien whereIn
        $results = Product::search($query)
            ->where('is_active', true)
            ->take($limit * 3) // Prendre plus pour pouvoir filtrer ensuite
            ->get();

        // Charger les relations nécessaires pour chaque résultat
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

        // Appliquer les filtres manuellement
        if (! empty($categoryIds)) {
            $results = $results->filter(function ($product) use ($categoryIds) {
                if (! $product->categories) {
                    return false;
                }

                return $product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty();
            });
        }

        if ($inStockOnly) {
            $results = $results->filter(function ($product) {
                $stockQty = $product->stock_qty;

                // Calculer le stock agrégé pour les produits à variants
                if ($product->type === 'variant' && $product->relationLoaded('variants')) {
                    $stockQty = $product->variants
                        ->where('is_active', true)
                        ->sum('stock_qty');
                }

                return ($stockQty ?? 0) > 0;
            });
        }

        // Appliquer les filtres de prix
        if ($minPrice !== null && $minPrice > 0) {
            $results = $results->filter(function ($product) use ($minPrice) {
                return ($product->price ?? 0) >= (float) $minPrice;
            });
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $results = $results->filter(function ($product) use ($maxPrice) {
                return ($product->price ?? 0) <= (float) $maxPrice;
            });
        }

        // Limiter les résultats après filtrage
        $results = $results->take($limit)->values();

        // Normaliser les résultats et sérialiser correctement les images
        $products = $results->map(function ($product) {
            // Récupérer la traduction
            $translation = $product->translation();

            // Calculer les prix et variantes
            $hasVariants = ($product->type === 'variant')
                || ($product->variants && $product->variants->count() > 0);

            $fromPrice = null;
            $price = (float) ($product->price ?? 0);
            $compareAtPrice = $product->compare_at_price !== null
                ? (float) $product->compare_at_price
                : null;
            $onSale = false;

            // Calculer le stock (agrégé pour les variants)
            $stockQty = $product->stock_qty;
            if ($hasVariants && $product->relationLoaded('variants')) {
                $stockQty = $product->variants
                    ->where('is_active', true)
                    ->sum('stock_qty');
            }

            if ($hasVariants) {
                $activeVariants = $product->variants
                    ->filter(fn ($v) => $v->price !== null);

                $fromPrice = $activeVariants->min('price') ?? 0;
                $price = (float) $fromPrice;
                $compareAtPrice = null;
            } else {
                $onSale = $compareAtPrice !== null && $compareAtPrice > $price;
            }

            // Construire l'objet de réponse avec images sérialisées
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'slug' => $translation?->slug ?? '',
                'name' => $translation?->name ?? '',
                'short_description' => $translation?->short_description ?? '',
                'price' => $price,
                'compare_at_price' => $compareAtPrice,
                'on_sale' => $onSale,
                'stock_qty' => $stockQty,
                'has_variants' => $hasVariants,
                'from_price' => $fromPrice,

                // Images sérialisées avec URLs complètes
                'main_image_url' => $this->buildImageUrl($product),
                'images' => $product->images->map(function ($img) use ($translation) {
                    return [
                        'url' => $img->url, // Utilise l'accesseur getUrlAttribute()
                        'alt' => $img->alt_text ?? $translation?->name ?? '',
                        'position' => $img->position,
                        'is_main' => $img->is_main,
                    ];
                })->toArray(),

                // Traductions disponibles
                'translations' => $product->translations->map(function ($t) {
                    return [
                        'locale' => $t->locale,
                        'name' => $t->name,
                        'slug' => $t->slug,
                    ];
                })->toArray(),
            ];
        });

        // Calculer les facettes à partir des résultats
        $categories = [];
        $prices = [];

        foreach ($results as $product) {
            // Collecter les catégories
            if ($product->categories) {
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

            // Collecter les prix
            if ($product->price > 0) {
                $prices[] = $product->price;
            }
        }

        $priceRange = [
            'min' => ! empty($prices) ? floor(min($prices)) : 0,
            'max' => ! empty($prices) ? ceil(max($prices)) : 0,
        ];

        return response()->json([
            'query' => $query,
            'total' => $products->count(),
            'products' => $products,
            'facets' => [
                'categories' => array_values($categories),
                'price_range' => $priceRange,
            ],
        ]);
    }

    /**
     * Build full image URL from Product's main image
     * Uses the ProductImage->url accessor which handles storage paths
     */
    private function buildImageUrl(Product $product): ?string
    {
        // Utiliser la relation mainImage si chargée
        if ($product->relationLoaded('images')) {
            $mainImage = $product->images->firstWhere('is_main', true)
                ?? $product->images->first();

            if ($mainImage) {
                return $mainImage->url; // Utilise getUrlAttribute()
            }
        }

        return null;
    }
}
