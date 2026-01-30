<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Checkout-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="CreateCheckoutOrderRequest",
 *     type="object",
 *     description="Create or update checkout order",
 *     required={"shippingMethodId", "shippingAddressId", "billingAddressId", "items", "subtotal", "shippingCostBase", "promoDiscount", "automaticDiscountTotal", "shippingDiscountTotal", "total"},
 *
 *     @OA\Property(property="order_id", type="integer", nullable=true, example=123, description="Existing order ID to update (optional)"),
 *     @OA\Property(property="shippingMethodId", type="integer", example=1, description="Selected shipping method ID"),
 *     @OA\Property(property="shippingAddressId", type="integer", example=5, description="Shipping address ID"),
 *     @OA\Property(property="billingAddressId", type="integer", example=5, description="Billing address ID"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Order items",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="product_id", type="integer", example=42),
 *             @OA\Property(property="variant_id", type="integer", nullable=true, example=12),
 *             @OA\Property(property="name", type="string", example="T-shirt Homme"),
 *             @OA\Property(property="quantity", type="integer", example=2),
 *             @OA\Property(property="unit_price", type="number", format="float", example=29.99)
 *         )
 *     ),
 *     @OA\Property(property="subtotal", type="number", format="float", example=59.98, description="Cart subtotal"),
 *     @OA\Property(property="shippingCostBase", type="number", format="float", example=5.00, description="Base shipping cost"),
 *     @OA\Property(property="promoDiscount", type="number", format="float", example=10.00, description="Promo code discount amount"),
 *     @OA\Property(property="automaticDiscountTotal", type="number", format="float", example=0.00, description="Automatic discount total"),
 *     @OA\Property(property="shippingDiscountTotal", type="number", format="float", example=0.00, description="Shipping discount total"),
 *     @OA\Property(property="total", type="number", format="float", example=54.98, description="Order total"),
 *     @OA\Property(
 *         property="promoCodes",
 *         type="array",
 *         nullable=true,
 *         description="Applied promo codes",
 *
 *         @OA\Items(type="string", example="SUMMER2024")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="OrderCreatedResponse",
 *     type="object",
 *     description="Order creation response",
 *
 *     @OA\Property(property="id", type="integer", example=123, description="Order ID"),
 *     @OA\Property(property="number", type="string", example="ORD-2024-00123", description="Order number")
 * )
 */
class CheckoutSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
