<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Shipping-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="ShippingMethod",
 *     type="object",
 *     description="Available shipping method",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Colissimo"),
 *     @OA\Property(property="carrier", type="string", example="La Poste"),
 *     @OA\Property(property="price", type="number", format="float", example=5.99),
 *     @OA\Property(property="estimated_days_min", type="integer", nullable=true, example=2),
 *     @OA\Property(property="estimated_days_max", type="integer", nullable=true, example=3),
 *     @OA\Property(property="enabled", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="ShippingMethodsResponse",
 *     type="object",
 *     description="List of available shipping methods",
 *
 *     @OA\Property(
 *         property="methods",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/ShippingMethod")
 *     )
 * )
 */
class ShippingSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
