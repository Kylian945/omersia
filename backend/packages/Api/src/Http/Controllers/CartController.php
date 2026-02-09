<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Omersia\Api\Http\Requests\CartApplyAutomaticDiscountsRequest;
use Omersia\Api\Http\Requests\CartApplyDiscountRequest;
use Omersia\Api\Http\Requests\CartSyncRequest;
use Omersia\Api\Services\CartCustomerResolver;
use Omersia\Api\Services\CartService;
use OpenApi\Annotations as OA;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CartCustomerResolver $customerResolver
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/cart/sync",
     *     summary="Synchroniser le panier (créer ou mettre à jour)",
     *     tags={"Panier"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/CartSyncRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Panier synchronisé ou supprimé. Si items vide/null, retourne {deleted: true}. Sinon retourne le panier mis à jour.",
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *                     type="object",
     *                     description="Panier mis à jour",
     *
     *                     @OA\Property(property="id", type="integer", example=42),
     *                     @OA\Property(property="token", type="string", example="cart_abc123"),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=59.98),
     *                     @OA\Property(property="total_qty", type="integer", example=3),
     *                     @OA\Property(property="currency", type="string", example="EUR")
     *                 ),
     *
     *                 @OA\Schema(
     *                     type="object",
     *                     description="Panier supprimé (items vides)",
     *
     *                     @OA\Property(property="deleted", type="boolean", example=true)
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="API key invalide ou manquante",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ApiKeyError")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function sync(CartSyncRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customer = $this->customerResolver->resolve($request);

        $result = $this->cartService->sync($validated, $customer);

        return response()->json($result);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cart/apply-discount",
     *     summary="Appliquer un code promo au panier",
     *     tags={"Panier"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/DiscountApplicationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Code promo appliqué",
     *
     *         @OA\JsonContent(ref="#/components/schemas/DiscountApplicationResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="API key invalide ou manquante",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ApiKeyError")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Code promo invalide ou non applicable",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code promo invalide ou expiré.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function applyDiscount(CartApplyDiscountRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customerId = isset($validated['email']) ? (int) $validated['email'] : null;
        $customer = $this->customerResolver->resolve($request, $customerId);

        $result = $this->cartService->applyDiscount($validated, $customer, (int) session('shop_id', 1));

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cart/apply-automatic-discounts",
     *     summary="Appliquer automatiquement les réductions actives au panier",
     *     tags={"Panier"},
     *     security={{"api.key": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"items"},
     *
     *             @OA\Property(property="email", type="integer", nullable=true, description="Customer ID"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id", "qty", "price"},
     *
     *                     @OA\Property(property="id", type="integer", description="Product ID"),
     *                     @OA\Property(property="qty", type="integer", minimum=1),
     *                     @OA\Property(property="price", type="number", format="float", minimum=0),
     *                     @OA\Property(property="variant_id", type="integer", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Réductions automatiques évaluées",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(
     *                 property="promotions",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="code", type="string", example="AUTO-12"),
     *                     @OA\Property(property="label", type="string", example="Soldes été"),
     *                     @OA\Property(property="type", type="string", example="order"),
     *                     @OA\Property(property="value_type", type="string", example="percentage")
     *                 )
     *             ),
     *             @OA\Property(property="line_adjustments_by_code", type="object", description="Ajustements ligne par code promo"),
     *             @OA\Property(property="order_discount_total", type="number", format="float", example=10.00),
     *             @OA\Property(property="product_discount_total", type="number", format="float", example=5.00),
     *             @OA\Property(property="shipping_discount_total", type="number", format="float", example=0.00),
     *             @OA\Property(property="free_shipping", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="API key invalide ou manquante",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ApiKeyError")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ServerError")
     *     )
     * )
     */
    public function applyAutomaticDiscounts(CartApplyAutomaticDiscountsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customerId = isset($validated['email']) ? (int) $validated['email'] : null;
        $customer = $this->customerResolver->resolve($request, $customerId);

        $result = $this->cartService->applyAutomaticDiscounts($validated, $customer, (int) session('shop_id', 1));

        return response()->json($result);
    }
}
