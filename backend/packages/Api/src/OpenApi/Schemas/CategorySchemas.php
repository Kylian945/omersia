<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Category-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="CategoryDetailResponse",
 *     type="object",
 *     description="Category payload with related products",
 *
 *     @OA\Property(property="category", ref="#/components/schemas/Category"),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Product")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CategoryListResponse",
 *     type="object",
 *     description="List of categories",
 *
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Category")
 *     )
 * )
 */
class CategorySchemas
{
    // This class serves only as a container for OpenAPI annotations
}
