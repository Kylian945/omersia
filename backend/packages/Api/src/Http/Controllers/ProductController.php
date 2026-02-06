<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Api\Http\Requests\ProductIndexRequest;
use Omersia\Api\Http\Requests\ProductShowRequest;
use Omersia\Api\Http\Resources\ProductDetailResource;
use Omersia\Api\Http\Resources\ProductSummaryResource;
use Omersia\Api\Services\Catalog\ProductService;
use OpenApi\Annotations as OA;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

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
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $locale = $request->input('locale', 'fr');
        $categorySlug = $request->input('category');
        $limit = (int) $request->input('limit', 20);

        $paginator = $this->productService->paginate($categorySlug, $locale, $limit);

        $payload = $paginator->toArray();
        $payload['data'] = ProductSummaryResource::collection($paginator->items())->toArray($request);

        return response()->json($payload);
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
    public function show(string $slug, ProductShowRequest $request): JsonResponse
    {
        $locale = $request->input('locale', 'fr');

        $product = $this->productService->findBySlug($slug, $locale);

        return response()->json(new ProductDetailResource($product));
    }
}
