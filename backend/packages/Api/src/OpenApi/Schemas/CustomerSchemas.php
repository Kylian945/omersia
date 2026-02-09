<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Customer and Address schemas.
 *
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     description="Customer account",
 *
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="shop_id", type="integer", example=1),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="firstname", type="string", example="John"),
 *     @OA\Property(property="lastname", type="string", example="Doe"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78"),
 *     @OA\Property(property="newsletter", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Address",
 *     type="object",
 *     description="Customer address",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="customer_id", type="integer", example=42),
 *     @OA\Property(property="label", type="string", nullable=true, example="Maison"),
 *     @OA\Property(property="firstname", type="string", nullable=true, example="John"),
 *     @OA\Property(property="lastname", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="line1", type="string", example="123 rue de la Paix"),
 *     @OA\Property(property="line2", type="string", nullable=true, example="Apt 4B"),
 *     @OA\Property(property="postcode", type="string", example="75001"),
 *     @OA\Property(property="city", type="string", example="Paris"),
 *     @OA\Property(property="country", type="string", example="FR"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78"),
 *     @OA\Property(property="is_default_shipping", type="boolean", example=false),
 *     @OA\Property(property="is_default_billing", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="AddressListResponse",
 *     type="object",
 *     description="List of customer addresses",
 *
 *     @OA\Property(
 *         property="addresses",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Address")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AddressDetailResponse",
 *     type="object",
 *     description="Single address detail response",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="customer_id", type="integer", example=42),
 *     @OA\Property(property="label", type="string", nullable=true, example="Maison"),
 *     @OA\Property(property="firstname", type="string", nullable=true, example="John"),
 *     @OA\Property(property="lastname", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="company", type="string", nullable=true, example="ACME Inc."),
 *     @OA\Property(property="line1", type="string", example="123 rue de la Paix"),
 *     @OA\Property(property="line2", type="string", nullable=true, example="Apt 4B"),
 *     @OA\Property(property="postcode", type="string", example="75001"),
 *     @OA\Property(property="city", type="string", example="Paris"),
 *     @OA\Property(property="state", type="string", nullable=true, example="Île-de-France"),
 *     @OA\Property(property="country", type="string", example="FR"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78"),
 *     @OA\Property(property="is_default_shipping", type="boolean", example=false),
 *     @OA\Property(property="is_default_billing", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="AddressCreateRequest",
 *     type="object",
 *     required={"label", "line1", "postcode", "city"},
 *     description="Request body for creating a new address",
 *
 *     @OA\Property(property="label", type="string", example="Maison", description="Label for the address"),
 *     @OA\Property(property="firstname", type="string", nullable=true, example="John"),
 *     @OA\Property(property="lastname", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="company", type="string", nullable=true, example="ACME Inc."),
 *     @OA\Property(property="line1", type="string", example="123 rue de la Paix"),
 *     @OA\Property(property="line2", type="string", nullable=true, example="Apt 4B"),
 *     @OA\Property(property="postcode", type="string", example="75001"),
 *     @OA\Property(property="city", type="string", example="Paris"),
 *     @OA\Property(property="state", type="string", nullable=true, example="Île-de-France"),
 *     @OA\Property(property="country", type="string", nullable=true, example="FR"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78"),
 *     @OA\Property(property="is_default_shipping", type="boolean", example=false),
 *     @OA\Property(property="is_default_billing", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AddressUpdateRequest",
 *     type="object",
 *     description="Request body for updating an address",
 *
 *     @OA\Property(property="label", type="string", example="Maison", description="Label for the address"),
 *     @OA\Property(property="firstname", type="string", nullable=true, example="John"),
 *     @OA\Property(property="lastname", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="company", type="string", nullable=true, example="ACME Inc."),
 *     @OA\Property(property="line1", type="string", example="123 rue de la Paix"),
 *     @OA\Property(property="line2", type="string", nullable=true, example="Apt 4B"),
 *     @OA\Property(property="postcode", type="string", example="75001"),
 *     @OA\Property(property="city", type="string", example="Paris"),
 *     @OA\Property(property="state", type="string", nullable=true, example="Île-de-France"),
 *     @OA\Property(property="country", type="string", nullable=true, example="FR"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+33 6 12 34 56 78"),
 *     @OA\Property(property="is_default_shipping", type="boolean", example=false),
 *     @OA\Property(property="is_default_billing", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="CustomerRegisterRequest",
 *     type="object",
 *     required={"firstname", "lastname", "email", "password"},
 *
 *     @OA\Property(property="firstname", type="string", example="John"),
 *     @OA\Property(property="lastname", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="secret123"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
 *     @OA\Property(property="newsletter", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="CustomerLoginRequest",
 *     type="object",
 *     required={"email", "password"},
 *
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="secret123")
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     type="object",
 *     description="Authentication response with token",
 *
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/Customer"
 *     ),
 *     @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz123456")
 * )
 */
class CustomerSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
