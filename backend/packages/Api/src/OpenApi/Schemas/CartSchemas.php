<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Cart-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="CartItem",
 *     type="object",
 *     description="Item in shopping cart",
 *     required={"id", "price", "qty"},
 *
 *     @OA\Property(property="id", type="integer", example=123, description="Product ID"),
 *     @OA\Property(property="variant_id", type="integer", nullable=true, example=456, description="Variant ID if applicable"),
 *     @OA\Property(property="name", type="string", nullable=true, example="T-shirt Homme"),
 *     @OA\Property(property="price", type="number", format="float", example=29.99, description="Unit price"),
 *     @OA\Property(property="qty", type="integer", example=2, minimum=1, description="Quantity")
 * )
 *
 * @OA\Schema(
 *     schema="CartSyncRequest",
 *     type="object",
 *     description="Cart synchronization request",
 *
 *     @OA\Property(property="token", type="string", nullable=true, example="cart_abc123xyz", description="Cart token for guest users"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="customer@example.com"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         nullable=true,
 *
 *         @OA\Items(ref="#/components/schemas/CartItem")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DiscountApplicationRequest",
 *     type="object",
 *     description="Apply discount code request",
 *     required={"code", "items"},
 *
 *     @OA\Property(property="code", type="string", example="SUMMER2024", description="Discount code"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="customer@example.com"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=59.98),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/CartItem")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DiscountApplicationResponse",
 *     type="object",
 *     description="Discount application result",
 *
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", nullable=true, example="Code promo invalide"),
 *     @OA\Property(property="order_discount_amount", type="number", format="float", nullable=true, example=10.00),
 *     @OA\Property(property="product_discount_amount", type="number", format="float", nullable=true, example=5.00),
 *     @OA\Property(property="total_discount", type="number", format="float", nullable=true, example=15.00),
 *     @OA\Property(
 *         property="line_adjustments",
 *         type="array",
 *         nullable=true,
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="product_id", type="integer"),
 *             @OA\Property(property="discount_amount", type="number", format="float")
 *         )
 *     )
 * )
 */
class CartSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
