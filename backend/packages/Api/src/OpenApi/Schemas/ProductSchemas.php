<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductTranslation",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="locale", type="string", example="fr"),
 *     @OA\Property(property="slug", type="string", example="t-shirt-homme"),
 *     @OA\Property(property="title", type="string", example="T-shirt Homme"),
 *     @OA\Property(property="description", type="string", example="T-shirt 100% coton bio.")
 * )
 *
 * @OA\Schema(
 *     schema="CategoryTranslation",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="locale", type="string", example="fr"),
 *     @OA\Property(property="title", type="string", example="Vêtements"),
 *     @OA\Property(property="slug", type="string", example="vetements")
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(
 *         property="translations",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/CategoryTranslation")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductImage",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=34),
 *     @OA\Property(property="url", type="string", example="https://cdn.example.com/products/image1.jpg"),
 *     @OA\Property(property="position", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=123),
 *     @OA\Property(property="shop_id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="TSHIRT-BLACK-L"),
 *     @OA\Property(property="type", type="string", example="simple"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="price", type="number", format="float", example=29.99),
 *     @OA\Property(property="stock", type="integer", example=42),
 *     @OA\Property(
 *         property="translations",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/ProductTranslation")
 *     ),
 *
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Category")
 *     ),
 *
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/ProductImage")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductListResponse",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=20),
 *     @OA\Property(property="total", type="integer", example=85),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Product")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductDetailResponse",
 *     type="object",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Product")
 *     }
 * )
 */
class ProductSchemas {}
