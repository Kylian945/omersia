<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Error response schemas for the Omersia API.
 *
 * Defines standardized error response formats used across all API endpoints.
 *
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     description="Standard error response",
 *     required={"message"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Resource not found",
 *         description="Human-readable error message"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         nullable=true,
 *         example="RESOURCE_NOT_FOUND",
 *         description="Machine-readable error code"
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         nullable=true,
 *         example="The requested resource was not found",
 *         description="Detailed error description"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     description="Validation error response (HTTP 422)",
 *     required={"message", "errors"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="The given data was invalid.",
 *         description="Generic validation error message"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Field-specific validation errors",
 *         example={
 *             "email": {"L'adresse email est déjà utilisée."},
 *             "password": {"Le mot de passe doit contenir au moins 8 caractères."}
 *         },
 *
 *         @OA\AdditionalProperties(
 *             type="array",
 *
 *             @OA\Items(type="string")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedError",
 *     type="object",
 *     description="Authentication error response (HTTP 401)",
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Unauthenticated",
 *         description="Authentication failure message"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ForbiddenError",
 *     type="object",
 *     description="Authorization error response (HTTP 403)",
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="This action is unauthorized.",
 *         description="Authorization failure message"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundError",
 *     type="object",
 *     description="Resource not found error (HTTP 404)",
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Resource not found",
 *         description="Not found message"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ApiKeyError",
 *     type="object",
 *     description="API key validation error",
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Invalid API key",
 *         description="API key error message"
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         nullable=true,
 *         example="API key expired",
 *         description="Detailed error (e.g., expired, missing)"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ServerError",
 *     type="object",
 *     description="Internal server error (HTTP 500)",
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Server Error",
 *         description="Generic server error message"
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         nullable=true,
 *         description="Detailed error (only in debug mode)"
 *     )
 * )
 */
class ErrorSchemas
{
    // This class serves only as a container for OpenAPI annotations
    // No implementation is needed
}
