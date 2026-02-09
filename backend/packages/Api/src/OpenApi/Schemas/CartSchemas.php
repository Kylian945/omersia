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
 *     @OA\Property(property="name", type="string", example="T-shirt Homme"),
 *     @OA\Property(property="price", type="number", format="float", example=29.99, description="Unit price"),
 *     @OA\Property(property="oldPrice", type="number", format="float", nullable=true, example=39.99, description="Previous price (before discount)"),
 *     @OA\Property(property="qty", type="integer", example=2, minimum=1, description="Quantity"),
 *     @OA\Property(property="imageUrl", type="string", nullable=true, example="https://cdn.example.com/img.jpg", description="Product image URL"),
 *     @OA\Property(property="variantLabel", type="string", nullable=true, example="Taille: M", description="Variant display label")
 * )
 *
 * @OA\Schema(
 *     schema="CartSyncRequest",
 *     type="object",
 *     description="Cart synchronization request",
 *
 *     @OA\Property(property="token", type="string", nullable=true, example="cart_abc123xyz", description="Cart token for guest users"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="customer@example.com"),
 *     @OA\Property(property="currency", type="string", nullable=true, example="EUR", description="Currency code (3 chars)"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         nullable=true,
 *         description="Cart items. If empty or null, the cart will be deleted.",
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
 *     @OA\Property(property="code", type="string", example="SUMMER2024", description="Code promo appliqué"),
 *     @OA\Property(property="label", type="string", example="Soldes été 2024", description="Nom de la réduction"),
 *     @OA\Property(property="type", type="string", example="order", enum={"order", "product", "shipping", "buy_x_get_y"}, description="Type de réduction"),
 *     @OA\Property(property="value_type", type="string", example="percentage", enum={"percentage", "fixed"}, description="Type de valeur"),
 *     @OA\Property(property="value", type="number", format="float", example=10.00, description="Valeur de la réduction"),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=15.00, description="Montant total de la réduction"),
 *     @OA\Property(property="order_discount_amount", type="number", format="float", example=10.00, description="Montant réduction sur la commande"),
 *     @OA\Property(property="product_discount_amount", type="number", format="float", example=5.00, description="Montant réduction sur les produits"),
 *     @OA\Property(property="shipping_discount_amount", type="number", format="float", example=0.00, description="Montant réduction sur la livraison"),
 *     @OA\Property(property="free_shipping", type="boolean", example=false, description="Livraison gratuite accordée"),
 *     @OA\Property(
 *         property="line_adjustments",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", description="Product ID"),
 *             @OA\Property(property="variant_id", type="integer", nullable=true, description="Variant ID"),
 *             @OA\Property(property="discount_amount", type="number", format="float"),
 *             @OA\Property(property="is_gift", type="boolean", example=false)
 *         )
 *     )
 * )
 */
class CartSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
