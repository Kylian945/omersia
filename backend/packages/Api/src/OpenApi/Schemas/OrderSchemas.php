<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Order-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="OrderAddress",
 *     type="object",
 *     description="Address used in an order",
 *
 *     @OA\Property(property="label", type="string", nullable=true, example="Maison"),
 *     @OA\Property(property="firstname", type="string", example="John"),
 *     @OA\Property(property="lastname", type="string", example="Doe"),
 *     @OA\Property(property="line1", type="string", example="123 rue de la Paix"),
 *     @OA\Property(property="line2", type="string", nullable=true, example="Apt 4B"),
 *     @OA\Property(property="postcode", type="string", example="75001"),
 *     @OA\Property(property="city", type="string", example="Paris"),
 *     @OA\Property(property="country", type="string", example="FR"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78")
 * )
 *
 * @OA\Schema(
 *     schema="OrderItem",
 *     type="object",
 *     description="Item in an order",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=123),
 *     @OA\Property(property="variant_id", type="integer", nullable=true, example=456),
 *     @OA\Property(property="name", type="string", example="T-shirt Homme"),
 *     @OA\Property(property="sku", type="string", example="TSHIRT-BLACK-L"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="unit_price", type="number", format="float", example=29.99),
 *     @OA\Property(property="total_price", type="number", format="float", example=59.98),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=0.00)
 * )
 *
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     description="Order",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="number", type="string", example="ORD-2024-00123"),
 *     @OA\Property(property="customer_id", type="integer", example=42),
 *     @OA\Property(property="status", type="string", enum={"draft", "pending", "processing", "shipped", "completed", "cancelled"}, example="pending"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=59.98),
 *     @OA\Property(property="tax_amount", type="number", format="float", example=11.99),
 *     @OA\Property(property="shipping_amount", type="number", format="float", example=5.00),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=0.00),
 *     @OA\Property(property="total", type="number", format="float", example=76.97),
 *     @OA\Property(
 *         property="shipping_address",
 *         ref="#/components/schemas/OrderAddress"
 *     ),
 *     @OA\Property(
 *         property="billing_address",
 *         ref="#/components/schemas/OrderAddress"
 *     ),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     ),
 *
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="OrderListResponse",
 *     type="object",
 *     description="Paginated list of orders",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Pagination"),
 *         @OA\Schema(
 *
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *
 *                 @OA\Items(ref="#/components/schemas/Order")
 *             )
 *         )
 *     }
 * )
 */
class OrderSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
