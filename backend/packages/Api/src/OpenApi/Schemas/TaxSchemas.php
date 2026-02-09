<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Tax calculation OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="TaxCalculationRequest",
 *     type="object",
 *     description="Tax calculation request",
 *     required={"subtotal", "address"},
 *
 *     @OA\Property(property="subtotal", type="number", format="float", example=59.98, description="Cart subtotal"),
 *     @OA\Property(property="shipping_cost", type="number", format="float", nullable=true, example=5.00, description="Shipping cost"),
 *     @OA\Property(
 *         property="address",
 *         type="object",
 *         required={"country"},
 *         @OA\Property(property="country", type="string", example="FR", description="Country code"),
 *         @OA\Property(property="state", type="string", nullable=true, example="Île-de-France", description="State/province"),
 *         @OA\Property(property="postal_code", type="string", nullable=true, example="75001", description="Postal code")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="TaxCalculationResponse",
 *     type="object",
 *     description="Tax calculation result",
 *
 *     @OA\Property(property="tax_total", type="number", format="float", example=11.99, description="Total tax amount"),
 *     @OA\Property(property="tax_rate", type="number", format="float", example=20.00, description="Applied tax rate percentage"),
 *     @OA\Property(
 *         property="tax_zone",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="France"),
 *         @OA\Property(property="code", type="string", example="FR")
 *     ),
 *     @OA\Property(
 *         property="breakdown",
 *         type="array",
 *         description="Tax breakdown by component",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="name", type="string", example="TVA"),
 *             @OA\Property(property="rate", type="number", format="float", example=20.00),
 *             @OA\Property(property="amount", type="number", format="float", example=11.99)
 *         )
 *     )
 * )
 */
class TaxSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
