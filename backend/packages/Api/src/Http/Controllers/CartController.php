<?php

declare(strict_types=1);

namespace Omersia\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Omersia\Api\DTO\CartItemDTO;
use Omersia\Api\DTO\DiscountApplicationDTO;
use Omersia\Api\Services\DiscountEvaluationService;
use Omersia\Catalog\Models\Cart;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use OpenApi\Annotations as OA;

class CartController extends Controller
{
    public function __construct(
        private readonly DiscountEvaluationService $discountEvaluationService
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
     *         description="Panier synchronisé",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="token", type="string", example="cart_abc123"),
     *             @OA\Property(property="subtotal", type="number", format="float", example=59.98),
     *             @OA\Property(property="total_qty", type="integer", example=3),
     *             @OA\Property(property="currency", type="string", example="EUR")
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
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'token' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'size:3'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.name' => ['required', 'string'],
            'items.*.price' => ['required', 'numeric'],
            'items.*.oldPrice' => ['nullable', 'numeric'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.imageUrl' => ['nullable', 'string'],
            'items.*.variantId' => ['nullable', 'integer'],
            'items.*.variantLabel' => ['nullable', 'string'],
        ]);

        // 🔥 1) Essayer de récupérer l’utilisateur via Sanctum
        $user = $request->user(); // tentatives "normales"

        if (! $user) {
            if ($token = $request->bearerToken()) {
                if ($accessToken = PersonalAccessToken::findToken($token)) {
                    $user = $accessToken->tokenable; // ton modèle User
                }
            }
        }

        $cartToken = $validated['token'] ?? null;

        return DB::transaction(function () use ($validated, $user, $cartToken) {
            $cart = $cartToken
                ? Cart::where('token', $cartToken)->first()
                : null;

            if (empty($validated['items'])) {
                if ($cart) {
                    $cart->items()->delete();
                    $cart->delete();
                }

                return response()->json([
                    'deleted' => true,
                ]);
            }

            if (! $cart) {
                $cart = new Cart;
                $cart->token = Str::uuid()->toString();
                $cart->status = 'open';
            }

            // 🔐 2) Lier le panier à l’utilisateur SI trouvé
            if ($user) {
                $cart->customer_id = $user->id;
                if (empty($validated['email'])) {
                    $cart->email = $user->email;
                }
            }

            // invité + éventuellement un email
            if (! empty($validated['email'])) {
                $cart->email = $validated['email'];
            }

            $cart->currency = $validated['currency'] ?? $cart->currency ?? 'EUR';

            $cart->save();

            $cart->items()->delete();

            $subtotal = 0;
            $totalQty = 0;

            foreach ($validated['items'] as $itemData) {
                $lineSubtotal = $itemData['price'] * $itemData['qty'];
                $subtotal += $lineSubtotal;
                $totalQty += $itemData['qty'];

                $cart->items()->create([
                    'product_id' => $itemData['id'],
                    'variant_id' => $itemData['variantId'] ?? null,
                    'name' => $itemData['name'],
                    'variant_label' => $itemData['variantLabel'] ?? null,
                    'unit_price' => $itemData['price'],
                    'old_price' => $itemData['oldPrice'] ?? null,
                    'qty' => $itemData['qty'],
                    'image_url' => $itemData['imageUrl'] ?? null,
                ]);
            }

            $cart->subtotal = $subtotal;
            $cart->total_qty = $totalQty;
            $cart->last_activity_at = now();
            $cart->save();

            return response()->json([
                'id' => $cart->id,
                'token' => $cart->token,
                'subtotal' => $cart->subtotal,
                'total_qty' => $cart->total_qty,
                'currency' => $cart->currency,
            ]);
        });
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
    public function applyDiscount(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'integer'], // id client chez toi
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],   // product_id
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.variant_id' => ['nullable', 'integer'],
        ]);

        // 🔐 Récup user via Sanctum (comme dans sync)
        $user = $request->user();

        if (! $user) {
            if ($token = $request->bearerToken()) {
                if ($accessToken = PersonalAccessToken::findToken($token)) {
                    $user = $accessToken->tokenable; // Customer
                }
            }
        }

        // On essaie aussi d’avoir le customer via "email" (en réalité id ici)
        /** @var Customer|null $customer */
        $customer = null;
        if ($user instanceof Customer) {
            $customer = $user;
        } elseif (! $user && ! empty($validated['email'])) {
            $customer = Customer::where('id', $validated['email'])->first();
        }

        $shopId = session('shop_id', 1);
        $code = strtoupper(trim($validated['code']));

        // 1) Trouver une réduction code promo active
        /** @var Discount|null $discount */
        $discount = Discount::forShop($shopId)
            ->where('method', 'code')
            ->whereRaw('UPPER(code) = ?', [$code])
            ->where('is_active', true)
            ->where(function ($q) {
                $now = now();
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->first();

        if (! $discount) {
            return response()->json([
                'ok' => false,
                'message' => 'Code promo invalide ou expiré.',
            ], 422);
        }

        $existingTypes = (array) $request->input('existing_types', []);

        $hasProductDiscount = in_array('product', $existingTypes, true)
            || in_array('buy_x_get_y', $existingTypes, true);
        $hasOrderDiscount = in_array('order', $existingTypes, true);
        $hasShippingDiscount = in_array('shipping', $existingTypes, true);

        if (
            ($hasProductDiscount && ! $discount->combines_with_product_discounts) ||
            ($hasOrderDiscount && ! $discount->combines_with_order_discounts) ||
            ($hasShippingDiscount && ! $discount->combines_with_shipping_discounts)
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Ce code n’est pas cumulable avec les réductions déjà appliquées.',
            ], 422);
        }

        // 2) Convertir les items en DTOs et calculer sous-total
        $itemDTOs = [];
        $subtotal = 0;
        $productIds = [];

        foreach ($validated['items'] as $itemData) {
            $itemDTO = CartItemDTO::fromArray($itemData);
            $itemDTOs[] = $itemDTO;
            $subtotal += $itemDTO->getLineSubtotal();
            $productIds[] = $itemDTO->id;
        }
        $productIds = array_unique($productIds);

        // 3) Créer le DTO d'application et évaluer la réduction
        $applicationDTO = new DiscountApplicationDTO(
            discount: $discount,
            customer: $customer,
            items: $itemDTOs,
            subtotal: $subtotal,
            productIds: $productIds
        );

        $result = $this->discountEvaluationService->evaluate($applicationDTO);

        if (! $result->ok) {
            return response()->json([
                'ok' => false,
                'message' => $result->message ?? 'Ce code ne génère aucune remise pour ce panier.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'code' => $discount->code,
            'label' => $discount->name,
            'type' => $discount->type,
            'value_type' => $discount->value_type,
            'value' => $discount->value,
            'discount_amount' => $result->totalDiscount,
            'order_discount_amount' => $result->orderDiscountAmount,
            'product_discount_amount' => $result->productDiscountAmount,
            'shipping_discount_amount' => $result->shippingDiscountAmount,
            'free_shipping' => $result->freeShipping,
            'line_adjustments' => array_values($result->lineAdjustments),
        ]);
    }

    public function applyAutomaticDiscounts(Request $request)
    {
        $validated = $request->validate([
            'email' => ['nullable', 'integer'], // chez toi: id customer ou email, adapte
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.variant_id' => ['nullable', 'integer'],
        ]);

        // 1) Récup user / customer comme dans applyDiscount()
        $user = $request->user();
        if (! $user) {
            if ($token = $request->bearerToken()) {
                if ($accessToken = PersonalAccessToken::findToken($token)) {
                    $user = $accessToken->tokenable;
                }
            }
        }

        /** @var Customer|null $customer */
        $customer = null;
        if ($user instanceof Customer) {
            $customer = $user;
        } elseif (! $user && ! empty($validated['email'])) {
            $customer = Customer::where('id', $validated['email'])->first();
        }

        $shopId = session('shop_id', 1);

        // 2) Convertir les items en DTOs et calculer sous-total
        $itemDTOs = [];
        $subtotal = 0;
        $productIds = [];

        foreach ($validated['items'] as $itemData) {
            $itemDTO = CartItemDTO::fromArray($itemData);
            $itemDTOs[] = $itemDTO;
            $subtotal += $itemDTO->getLineSubtotal();
            $productIds[] = $itemDTO->id;
        }
        $productIds = array_unique($productIds);

        // 3) Récupérer toutes les réductions automatiques actives
        $discounts = Discount::forShop($shopId)
            ->where('method', 'automatic')
            ->where('is_active', true)
            ->where(function ($q) {
                $now = now();
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('priority', 'desc')
            ->get();

        $applied = [];
        $orderDiscountTotal = 0.0;
        $productDiscountTotal = 0.0;
        $shippingDiscountTotal = 0.0;
        $freeShipping = false;

        // map [ "code" => [ "productId-variantKey" => adj ] ]
        $lineAdjustmentsByCode = [];

        // 4) Évaluer chaque réduction automatique avec le service
        foreach ($discounts as $discount) {
            $applicationDTO = new DiscountApplicationDTO(
                discount: $discount,
                customer: $customer,
                items: $itemDTOs,
                subtotal: $subtotal,
                productIds: $productIds
            );

            $result = $this->discountEvaluationService->evaluate($applicationDTO);

            if (! $result->ok) {
                continue;
            }

            $orderDiscountTotal += $result->orderDiscountAmount;
            $productDiscountTotal += $result->productDiscountAmount;
            $shippingDiscountTotal += $result->shippingDiscountAmount;

            if ($result->freeShipping) {
                $freeShipping = true;
            }

            $codeKey = $discount->code ?? 'AUTO-'.$discount->id * 12;

            $applied[] = [
                'code' => $codeKey,
                'label' => $discount->name,
                'type' => $discount->type,
                'value_type' => $discount->value_type,
            ];

            // Construire une map ligne par ligne
            $lineAdjustmentsByCode[$codeKey] = [];
            foreach ($result->lineAdjustments as $adj) {
                $variantId = $adj['variant_id'] ?? 'no-variant';
                $key = $adj['id'].'-'.$variantId;

                $lineAdjustmentsByCode[$codeKey][$key] = [
                    'id' => $adj['id'],
                    'variant_id' => $adj['variant_id'] ?? null,
                    'discount_amount' => $adj['discount_amount'],
                    'is_gift' => $adj['is_gift'] ?? false,
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'promotions' => $applied,
            'line_adjustments_by_code' => $lineAdjustmentsByCode,
            'order_discount_total' => $orderDiscountTotal,
            'product_discount_total' => $productDiscountTotal,
            'shipping_discount_total' => $shippingDiscountTotal,
            'free_shipping' => $freeShipping,
        ]);
    }
}
