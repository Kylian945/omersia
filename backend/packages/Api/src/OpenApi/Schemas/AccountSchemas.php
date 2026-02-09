<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Account profile-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="ProfileResponse",
 *     type="object",
 *     description="Customer profile information",
 *
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="firstname", type="string", example="John"),
 *     @OA\Property(property="lastname", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateProfileRequest",
 *     type="object",
 *     description="Update customer profile request",
 *
 *     @OA\Property(property="firstname", type="string", nullable=true, example="John"),
 *     @OA\Property(property="lastname", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78")
 * )
 */
class AccountSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
