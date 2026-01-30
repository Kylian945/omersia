<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * Category-related OpenAPI schemas.
 *
 * @OA\Schema(
 *     schema="CategoryWithProducts",
 *     type="object",
 *     description="Category with its products",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Category"),
 *         @OA\Schema(
 *
 *             @OA\Property(
 *                 property="products",
 *                 type="array",
 *
 *                 @OA\Items(ref="#/components/schemas/Product")
 *             )
 *         )
 *     }
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
