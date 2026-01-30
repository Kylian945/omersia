<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Common reusable OpenAPI schemas for the Omersia API.
 *
 * This file defines shared schemas used across multiple endpoints:
 * - Pagination structures
 * - Timestamps
 * - Common response wrappers
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     description="Laravel pagination metadata",
 *
 *     @OA\Property(property="current_page", type="integer", example=1, description="Current page number"),
 *     @OA\Property(property="last_page", type="integer", example=10, description="Last page number"),
 *     @OA\Property(property="per_page", type="integer", example=20, description="Items per page"),
 *     @OA\Property(property="total", type="integer", example=200, description="Total number of items"),
 *     @OA\Property(property="from", type="integer", nullable=true, example=1, description="Index of first item on current page"),
 *     @OA\Property(property="to", type="integer", nullable=true, example=20, description="Index of last item on current page"),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         description="Pagination links",
 *         @OA\Property(property="first", type="string", nullable=true, example="http://api.example.com/products?page=1"),
 *         @OA\Property(property="last", type="string", nullable=true, example="http://api.example.com/products?page=10"),
 *         @OA\Property(property="prev", type="string", nullable=true, example="http://api.example.com/products?page=1"),
 *         @OA\Property(property="next", type="string", nullable=true, example="http://api.example.com/products?page=3")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Timestamps",
 *     type="object",
 *     description="Standard Laravel timestamps",
 *
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-15T10:30:00.000000Z",
 *         description="Creation timestamp"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-20T14:45:00.000000Z",
 *         description="Last update timestamp"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Translation",
 *     type="object",
 *     description="Translatable content",
 *
 *     @OA\Property(property="locale", type="string", example="fr", description="Language code"),
 *     @OA\Property(property="title", type="string", nullable=true, example="Titre du produit"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="titre-du-produit"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Description du produit"),
 *     @OA\Property(property="meta_title", type="string", nullable=true),
 *     @OA\Property(property="meta_description", type="string", nullable=true),
 *     @OA\Property(property="meta_keywords", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Image",
 *     type="object",
 *     description="Image resource",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="path", type="string", example="/storage/products/image.jpg"),
 *     @OA\Property(property="url", type="string", example="https://cdn.example.com/products/image.jpg"),
 *     @OA\Property(property="alt", type="string", nullable=true, example="Product image"),
 *     @OA\Property(property="position", type="integer", example=1, description="Display order"),
 *     @OA\Property(property="is_main", type="boolean", example=true, description="Is primary image")
 * )
 *
 * @OA\Schema(
 *     schema="Money",
 *     type="object",
 *     description="Money value with currency",
 *
 *     @OA\Property(property="amount", type="number", format="float", example=29.99, description="Monetary amount"),
 *     @OA\Property(property="currency", type="string", example="EUR", description="ISO 4217 currency code"),
 *     @OA\Property(property="formatted", type="string", example="29,99 €", description="Human-readable formatted price")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     description="Generic success response",
 *
 *     @OA\Property(property="ok", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", nullable=true, example="Operation completed successfully")
 * )
 */
class CommonSchemas
{
    // This class serves only as a container for OpenAPI annotations
    // No implementation is needed
}
