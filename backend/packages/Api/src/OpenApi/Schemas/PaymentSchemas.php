<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Payment-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="PaymentMethod",
 *     type="object",
 *     description="Available payment method",
 *
 *     @OA\Property(property="id", type="string", example="stripe"),
 *     @OA\Property(property="name", type="string", example="Carte bancaire"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Paiement sécurisé par carte"),
 *     @OA\Property(property="enabled", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="PaymentMethodsResponse",
 *     type="object",
 *     description="List of available payment methods",
 *
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/PaymentMethod")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PaymentIntentRequest",
 *     type="object",
 *     description="Payment intent creation request",
 *     required={"order_id"},
 *
 *     @OA\Property(property="order_id", type="integer", example=123, description="Order ID to create payment for")
 * )
 *
 * @OA\Schema(
 *     schema="PaymentIntentResponse",
 *     type="object",
 *     description="Stripe payment intent",
 *
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="client_secret", type="string", example="pi_abc123_secret_xyz789"),
 *         @OA\Property(property="payment_intent_id", type="string", example="pi_abc123")
 *     )
 * )
 */
class PaymentSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
