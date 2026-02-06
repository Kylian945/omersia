<?php

declare(strict_types=1);

namespace Omersia\Api\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Omersia\Api\DTO\CartItemDTO;
use Omersia\Api\DTO\DiscountApplicationDTO;
use Omersia\Api\Services\DiscountEvaluationService;
use Omersia\Catalog\Models\Cart;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;

final class CartService
{
    public function __construct(
        private readonly DiscountEvaluationService $discountEvaluationService
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sync(array $data, ?Customer $customer): array
    {
        $cartToken = $data['token'] ?? null;

        return DB::transaction(function () use ($data, $customer, $cartToken) {
            $cart = $cartToken
                ? Cart::where('token', $cartToken)->first()
                : null;

            if (empty($data['items'])) {
                if ($cart) {
                    $cart->items()->delete();
                    $cart->delete();
                }

                return [
                    'deleted' => true,
                ];
            }

            if (! $cart) {
                $cart = new Cart;
                $cart->token = Str::uuid()->toString();
                $cart->status = 'open';
            }

            if ($customer) {
                $cart->customer_id = $customer->id;
                if (empty($data['email'])) {
                    $cart->email = $customer->email;
                }
            }

            if (! empty($data['email'])) {
                $cart->email = $data['email'];
            }

            $cart->currency = $data['currency'] ?? $cart->currency ?? 'EUR';

            $cart->save();

            $cart->items()->delete();

            $subtotal = 0;
            $totalQty = 0;

            foreach ($data['items'] as $itemData) {
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

            return [
                'id' => $cart->id,
                'token' => $cart->token,
                'subtotal' => $cart->subtotal,
                'total_qty' => $cart->total_qty,
                'currency' => $cart->currency,
            ];
        });
    }

    /**
     * @param array<string, mixed> $data
     * @return array{status:int, payload:array<string, mixed>}
     */
    public function applyDiscount(array $data, ?Customer $customer, int $shopId): array
    {
        $code = strtoupper(trim($data['code']));

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
            return [
                'status' => 422,
                'payload' => [
                    'ok' => false,
                    'message' => 'Code promo invalide ou expiré.',
                ],
            ];
        }

        $existingTypes = (array) ($data['existing_types'] ?? []);

        $hasProductDiscount = in_array('product', $existingTypes, true)
            || in_array('buy_x_get_y', $existingTypes, true);
        $hasOrderDiscount = in_array('order', $existingTypes, true);
        $hasShippingDiscount = in_array('shipping', $existingTypes, true);

        if (
            ($hasProductDiscount && ! $discount->combines_with_product_discounts) ||
            ($hasOrderDiscount && ! $discount->combines_with_order_discounts) ||
            ($hasShippingDiscount && ! $discount->combines_with_shipping_discounts)
        ) {
            return [
                'status' => 422,
                'payload' => [
                    'ok' => false,
                    'message' => 'Ce code n\'est pas cumulable avec les réductions déjà appliquées.',
                ],
            ];
        }

        $itemDTOs = [];
        $subtotal = 0;
        $productIds = [];

        foreach ($data['items'] as $itemData) {
            $itemDTO = CartItemDTO::fromArray($itemData);
            $itemDTOs[] = $itemDTO;
            $subtotal += $itemDTO->getLineSubtotal();
            $productIds[] = $itemDTO->id;
        }
        $productIds = array_unique($productIds);

        $applicationDTO = new DiscountApplicationDTO(
            discount: $discount,
            customer: $customer,
            items: $itemDTOs,
            subtotal: $subtotal,
            productIds: $productIds
        );

        $result = $this->discountEvaluationService->evaluate($applicationDTO);

        if (! $result->ok) {
            return [
                'status' => 422,
                'payload' => [
                    'ok' => false,
                    'message' => $result->message ?? 'Ce code ne génère aucune remise pour ce panier.',
                ],
            ];
        }

        return [
            'status' => 200,
            'payload' => [
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
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function applyAutomaticDiscounts(array $data, ?Customer $customer, int $shopId): array
    {
        $itemDTOs = [];
        $subtotal = 0;
        $productIds = [];

        foreach ($data['items'] as $itemData) {
            $itemDTO = CartItemDTO::fromArray($itemData);
            $itemDTOs[] = $itemDTO;
            $subtotal += $itemDTO->getLineSubtotal();
            $productIds[] = $itemDTO->id;
        }
        $productIds = array_unique($productIds);

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

        $lineAdjustmentsByCode = [];

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

        return [
            'ok' => true,
            'promotions' => $applied,
            'line_adjustments_by_code' => $lineAdjustmentsByCode,
            'order_discount_total' => $orderDiscountTotal,
            'product_discount_total' => $productDiscountTotal,
            'shipping_discount_total' => $shippingDiscountTotal,
            'free_shipping' => $freeShipping,
        ];
    }
}
