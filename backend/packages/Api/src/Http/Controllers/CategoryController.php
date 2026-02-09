<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Api\Http\Requests\CategoryIndexRequest;
use Omersia\Api\Http\Requests\CategoryShowRequest;
use Omersia\Api\Http\Resources\CategoryDetailResource;
use Omersia\Api\Http\Resources\CategoryListResource;
use Omersia\Api\Http\Resources\ProductSummaryResource;
use Omersia\Api\Services\Catalog\CategoryService;
use Omersia\Api\Services\Catalog\ProductService;
use OpenApi\Annotations as OA;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductService $productService
    ) {}

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
    public function index(CategoryIndexRequest $request): JsonResponse
    {
        $locale = $request->input('locale', 'fr');
        $parentOnly = $request->boolean('parent_only', false);

        $accueil = $this->categoryService->findAccueil($locale);

        if (! $accueil) {
            return response()->json([
                'categories' => [],
                'error' => 'Catégorie accueil introuvable',
            ]);
        }

        $categories = $this->categoryService->listCategories($accueil, $locale, $parentOnly);

        return response()->json([
            'categories' => CategoryListResource::collection($categories)->toArray($request),
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
     *         @OA\JsonContent(ref="#/components/schemas/CategoryDetailResponse")
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
    public function show(string $slug, CategoryShowRequest $request): JsonResponse
    {
        $locale = $request->input('locale', 'fr');

        $category = $this->categoryService->findBySlug($slug, $locale);

        $paginator = $this->productService->paginateByCategory($category, $locale, 20);
        $products = ProductSummaryResource::collection($paginator->items())->toArray($request);

        return response()->json([
            'category' => new CategoryDetailResource($category),
            'products' => $products,
        ]);
    }
}
