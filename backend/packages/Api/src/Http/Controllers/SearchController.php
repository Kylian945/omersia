<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Api\Http\Requests\SearchRequest;
use Omersia\Api\Http\Resources\ProductSummaryResource;
use Omersia\Api\Services\Catalog\ProductSearchService;
use OpenApi\Annotations as OA;

class SearchController extends Controller
{
    public function __construct(
        private readonly ProductSearchService $productSearchService
    ) {}

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
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             )
     *         )
     *     )
     * )
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $locale = $request->input('locale', 'fr');
        $limit = (int) $request->input('limit', 24);
        $query = (string) $request->input('q', '');

        $result = $this->productSearchService->search($query, $locale, $limit, $request->validated());

        $products = ProductSummaryResource::collection($result['products'])->toArray($request);

        return response()->json([
            'query' => $result['query'],
            'total' => count($products),
            'products' => $products,
            'facets' => $result['facets'],
        ]);
    }
}
