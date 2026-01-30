<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use OpenApi\Annotations as OA;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="Lister toutes les catégories actives",
     *     description="Retourne toutes les catégories actives (enfants de la catégorie 'accueil') avec leurs traductions et le nombre de produits",
     *     operationId="getCategories",
     *     tags={"Catégories"},
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
     *         name="parent_only",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true),
     *         description="Retourner uniquement les catégories parentes"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Liste des catégories avec comptage des produits",
     *
     *         @OA\JsonContent(ref="#/components/schemas/CategoryListResponse")
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

        // 1. Trouver la catégorie "accueil"
        $accueil = Category::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($locale) {
                $q->where('locale', $locale)
                    ->where('slug', 'accueil');
            })
            ->first();

        if (! $accueil) {
            return response()->json([
                'categories' => [],
                'error' => 'Catégorie accueil introuvable',
            ]);
        }

        // 2. Récupérer SES ENFANTS DIRECTS
        $categories = Category::query()
            ->where('is_active', true)
            ->where('id', '!=', $accueil->id)
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
            ])
            ->orderBy('position')
            ->get();

        // 3. Ajouter le nombre de produits
        $categoriesWithCount = $categories->map(function ($category) {
            $productCount = Product::query()
                ->where('is_active', true)
                ->whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category->id);
                })
                ->count();

            $translation = $category->translations->first();

            return [
                'id' => $category->id,
                'name' => $translation?->name ?? 'Sans nom',
                'slug' => $translation?->slug ?? '',
                'description' => $translation?->description,
                'image' => $category->image,
                'count' => $productCount,
                'parent_id' => $category->parent_id,
                'position' => $category->position,
            ];
        });

        return response()->json([
            'categories' => $categoriesWithCount,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/categories/{slug}",
     *     summary="Détail d'une catégorie avec ses produits et sous-catégories",
     *     description="Retourne une catégorie par slug avec ses sous-catégories (jusqu'à 3 niveaux), sa catégorie parente et les produits paginés (20/page)",
     *     operationId="getCategoryBySlug",
     *     tags={"Catégories"},
     *     security={{"api.key": {}}},
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la catégorie dans la langue spécifiée",
     *
     *         @OA\Schema(type="string", example="chemises-homme")
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
     *         description="Catégorie trouvée avec produits et sous-catégories",
     *
     *         @OA\JsonContent(ref="#/components/schemas/CategoryWithProducts")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Catégorie non trouvée pour ce slug et cette langue",
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

        // 1. Catégorie par slug (dans la locale)
        $category = Category::query()
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

        // 2. Produits directement liés à cette catégorie
        $paginator = Product::query()
            ->where('is_active', true)
            ->whereHas('categories', function ($q) use ($category) {
                // Utilise la relation categories() → pivot product_categories
                $q->where('categories.id', $category->id);
            })
            ->with('images')
            ->with([
                'translations' => function ($q) use ($locale) {
                    $q->where('locale', $locale);
                },
            ])
            // On veut aussi les variantes actives pour calculer le prix min si besoin
            ->with(['variants' => function ($q) {
                $q->where('is_active', true);
            }])
            ->paginate(20);

        $items = $paginator->items();

        foreach ($items as $product) {
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
        }

        return response()->json([
            'category' => $category,
            'products' => $items,
        ]);
    }
}
