<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Catalog\Models\Product;
use OpenApi\Annotations as OA;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     summary="Liste paginée des produits actifs",
     *     tags={"Produits"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="fr", enum={"fr", "en"}),
     *         description="Langue des traductions"
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=1, minimum=1),
     *         description="Numéro de page"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des produits avec pagination Laravel",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProductListResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="API key invalide ou manquante",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ApiKeyError")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $locale = $request->get('locale', 'fr');
        $categorySlug = $request->get('category');
        $limit = min((int) $request->get('limit', 20), 100); // Max 100 produits

        $query = Product::query()
            ->where('is_active', true);

        // Filtrer par catégorie si spécifié
        if ($categorySlug) {
            $query->whereHas('categories.translations', function ($q) use ($categorySlug, $locale) {
                $q->where('locale', $locale)
                    ->where('slug', $categorySlug);
            });
        }

        $paginator = $query
            ->with('images')
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
            // On veut aussi les variantes actives pour calculer le prix min si besoin
            ->with(['variants' => function ($q) {
                $q->where('is_active', true);
            }])
            ->paginate($limit);

        $paginator->getCollection()->transform(function ($product) {
            $hasVariants = ($product->type === 'variant')
                || ($product->variants && $product->variants->count() > 0);

            if ($hasVariants) {
                $activeVariants = $product->variants
                    ->filter(fn ($v) => $v->price !== null);

                $fromPrice = $activeVariants->min('price') ?? 0;

                $product->has_variants = true;
                $product->from_price = (float) $fromPrice;

                // Pour rester compatible avec ton front actuel :
                $product->price = (float) $fromPrice;

                // (Tu peux raffiner si tu veux gérer un compare_at_price global)
                $product->compare_at_price = null;
                $product->on_sale = false;
            } else {
                $product->has_variants = false;

                $product->price = (float) ($product->price ?? 0);
                $product->compare_at_price = $product->compare_at_price !== null
                    ? (float) $product->compare_at_price
                    : null;

                $product->on_sale = $product->compare_at_price !== null
                    && $product->compare_at_price > $product->price;
            }

            // Ajouter le slug directement sur chaque catégorie pour faciliter le filtrage frontend
            if ($product->categories) {
                $product->categories->transform(function ($category) {
                    $translation = $category->translations->first();
                    $category->slug = $translation?->slug;
                    $category->name = $translation?->name;

                    return $category;
                });
            }

            return $product;
        });

        return response()->json($paginator);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{slug}",
     *     summary="Détail d'un produit par slug avec images, catégories, variantes et options",
     *     tags={"Produits"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug du produit dans la langue spécifiée",
     *
     *         @OA\Schema(type="string", example="t-shirt-homme")
     *     ),
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="string", default="fr", enum={"fr", "en"}),
     *         description="Langue des traductions"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Produit trouvé avec toutes ses relations",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProductDetailResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Produit non trouvé pour ce slug et cette langue",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="API key invalide ou manquante",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ApiKeyError")
     *     )
     * )
     */
    public function show(string $slug, Request $request)
    {
        $locale = $request->get('locale', 'fr');

        $product = Product::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($slug, $locale) {
                $q->where('locale', $locale)
                    ->where('slug', $slug);
            })
            ->with([
                // Traduction principale
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },

                // Images
                'images',

                // Catégories actives + traduction
                'categories' => function ($q) use ($locale) {
                    $q->where('is_active', true)
                        ->with(['translations' => function ($tq) use ($locale) {
                            $tq->where('locale', $locale);
                        }]);
                },

                // Produits associés
                'relatedProducts.translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
                'relatedProducts.images',
                'relatedProducts.variants',

                // Options et valeurs (Taille, Couleur, ...)
                'options.values',

                // Variantes + leurs valeurs + l’option associée
                'variants.values.option',
            ])
            ->firstOrFail();

        // Cast prix produit
        $product->price = (float) ($product->price ?? 0);
        $product->compare_at_price = $product->compare_at_price !== null
            ? (float) $product->compare_at_price
            : null;

        $product->on_sale = $product->compare_at_price !== null
            && $product->compare_at_price > $product->price;

        // Indique si on est sur un produit à variantes
        $product->has_variants = $product->type === 'variant'
            || ($product->variants && $product->variants->count() > 0);

        // Ajouter le slug directement sur chaque catégorie
        if ($product->categories) {
            $product->categories->transform(function ($category) {
                $translation = $category->translations->first();
                $category->slug = $translation?->slug;
                $category->name = $translation?->name;

                return $category;
            });
        }

        // Normalisation des variantes pour le frontend
        if ($product->variants && $product->variants->count() > 0) {
            $product->variants->transform(function ($variant) {
                $variant->price = (float) ($variant->price ?? 0);
                $variant->compare_at_price = $variant->compare_at_price !== null
                    ? (float) $variant->compare_at_price
                    : null;

                $variant->on_sale = $variant->compare_at_price !== null
                    && $variant->compare_at_price > $variant->price;

                // option_values: [ { option: "Taille", value: "S" }, ... ]
                $variant->option_values = $variant->values
                    ->map(function ($vv) {
                        return [
                            'option' => $vv->option->name ?? null,
                            'value' => $vv->value,
                        ];
                    })
                    ->filter(fn ($v) => $v['option'] && $v['value'])
                    ->values();

                return $variant;
            });
        }

        return response()->json($product);
    }
}
