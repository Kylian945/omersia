<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Authentication-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     description="Customer registration request",
 *     required={"firstname", "lastname", "email", "password", "password_confirmation"},
 *
 *     @OA\Property(property="firstname", type="string", example="John", description="Customer first name"),
 *     @OA\Property(property="lastname", type="string", example="Doe", description="Customer last name"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer email address"),
 *     @OA\Property(property="password", type="string", format="password", example="SecureP@ssw0rd", description="Password (min 8 characters)"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="SecureP@ssw0rd", description="Password confirmation"),
 *     @OA\Property(property="newsletter", type="boolean", example=true, description="Subscribe to newsletter (optional)")
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     description="Customer login request",
 *     required={"email", "password"},
 *
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer email"),
 *     @OA\Property(property="password", type="string", format="password", example="SecureP@ssw0rd", description="Customer password")
 * )
 *
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     description="Successful login response",
 *
 *     @OA\Property(property="message", type="string", example="Connexion réussie."),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=42),
 *         @OA\Property(property="firstname", type="string", example="John"),
 *         @OA\Property(property="lastname", type="string", example="Doe"),
 *         @OA\Property(property="email", type="string", example="john.doe@example.com")
 *     ),
 *     @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz123456789", description="Sanctum API token")
 * )
 *
 * @OA\Schema(
 *     schema="MeResponse",
 *     type="object",
 *     description="Current authenticated user information",
 *
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="firstname", type="string", nullable=true, example="John"),
 *     @OA\Property(property="lastname", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="email", type="string", example="john.doe@example.com")
 * )
 *
 * @OA\Schema(
 *     schema="ForgotPasswordRequest",
 *     type="object",
 *     description="Password reset request",
 *     required={"email"},
 *
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Email address to send reset link")
 * )
 *
 * @OA\Schema(
 *     schema="ResetPasswordRequest",
 *     type="object",
 *     description="Password reset with token",
 *     required={"email", "token", "password", "password_confirmation"},
 *
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="token", type="string", example="abc123xyz789", description="Reset token from email"),
 *     @OA\Property(property="password", type="string", format="password", example="NewP@ssw0rd", description="New password"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="NewP@ssw0rd", description="New password confirmation")
 * )
 *
 * @OA\Schema(
 *     schema="MessageResponse",
 *     type="object",
 *     description="Simple message response",
 *
 *     @OA\Property(property="message", type="string", example="Opération réussie.")
 * )
 */
class AuthSchemas
{
    // This class serves only as a container for OpenAPI annotations
}
