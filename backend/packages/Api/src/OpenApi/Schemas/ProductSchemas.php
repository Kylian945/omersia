<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductTranslation",
 *     type="object",
 *
 *     @OA\Property(property="locale", type="string", example="fr"),
 *     @OA\Property(property="name", type="string", example="T-shirt Homme"),
 *     @OA\Property(property="slug", type="string", example="t-shirt-homme"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="short_description", type="string", nullable=true),
 *     @OA\Property(property="meta_title", type="string", nullable=true),
 *     @OA\Property(property="meta_description", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CategoryTranslation",
 *     type="object",
 *
 *     @OA\Property(property="locale", type="string", example="fr"),
 *     @OA\Property(property="name", type="string", example="Vêtements"),
 *     @OA\Property(property="slug", type="string", example="vetements"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="meta_title", type="string", nullable=true),
 *     @OA\Property(property="meta_description", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", nullable=true, example="Vêtements"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="vetements"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="image", type="string", nullable=true, example="https://cdn.example.com/category.jpg"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://cdn.example.com/category.jpg"),
 *     @OA\Property(property="count", type="integer", nullable=true, example=12),
 *     @OA\Property(property="parent_id", type="integer", nullable=true),
 *     @OA\Property(property="position", type="integer", nullable=true, example=1),
 *     @OA\Property(
 *         property="translations",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/CategoryTranslation")
 *     ),
 *
 *     @OA\Property(
 *         property="parent",
 *         ref="#/components/schemas/Category",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="children",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Category")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductImage",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=34),
 *     @OA\Property(property="url", type="string", example="https://cdn.example.com/products/image1.jpg"),
 *     @OA\Property(property="path", type="string", nullable=true, example="products/image1.jpg"),
 *     @OA\Property(property="alt", type="string", nullable=true, example="Vue principale"),
 *     @OA\Property(property="position", type="integer", example=1),
 *     @OA\Property(property="is_main", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProductVariantValue",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="value", type="string", example="M"),
 *     @OA\Property(
 *         property="option",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Taille")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="OptionValue",
 *     type="object",
 *
 *     @OA\Property(property="option", type="string", example="Taille"),
 *     @OA\Property(property="value", type="string", example="M")
 * )
 *
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=456),
 *     @OA\Property(property="sku", type="string", nullable=true, example="TSHIRT-BLACK-M"),
 *     @OA\Property(property="name", type="string", nullable=true, example="Noir / M"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="manage_stock", type="boolean", example=true),
 *     @OA\Property(property="stock_qty", type="integer", nullable=true, example=15),
 *     @OA\Property(property="price", type="number", format="float", example=29.99),
 *     @OA\Property(property="compare_at_price", type="number", format="float", nullable=true, example=39.99),
 *     @OA\Property(property="on_sale", type="boolean", example=false),
 *     @OA\Property(
 *         property="values",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/ProductVariantValue")
 *     ),
 *
 *     @OA\Property(
 *         property="option_values",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/OptionValue")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductOption",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Taille"),
 *     @OA\Property(property="position", type="integer", example=1),
 *     @OA\Property(
 *         property="values",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="value", type="string", example="S"),
 *             @OA\Property(property="position", type="integer", example=1)
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=123),
 *     @OA\Property(property="sku", type="string", nullable=true, example="TSHIRT-BLACK-L"),
 *     @OA\Property(property="type", type="string", example="simple"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="manage_stock", type="boolean", example=true),
 *     @OA\Property(property="stock_qty", type="integer", nullable=true, example=42),
 *     @OA\Property(property="price", type="number", format="float", example=29.99),
 *     @OA\Property(property="compare_at_price", type="number", format="float", nullable=true, example=39.99),
 *     @OA\Property(property="on_sale", type="boolean", example=false),
 *     @OA\Property(property="has_variants", type="boolean", example=false),
 *     @OA\Property(property="from_price", type="number", format="float", nullable=true, example=19.99),
 *     @OA\Property(property="name", type="string", nullable=true, example="T-shirt Homme"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="t-shirt-homme"),
 *     @OA\Property(property="short_description", type="string", nullable=true),
 *     @OA\Property(property="main_image_url", type="string", nullable=true, example="https://cdn.example.com/products/image1.jpg"),
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
 *     ),
 *
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/ProductVariant")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductListResponse",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="first_page_url", type="string", nullable=true),
 *     @OA\Property(property="from", type="integer", nullable=true),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="last_page_url", type="string", nullable=true),
 *     @OA\Property(
 *         property="links",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="url", type="string", nullable=true),
 *             @OA\Property(property="label", type="string"),
 *             @OA\Property(property="active", type="boolean")
 *         )
 *     ),
 *     @OA\Property(property="next_page_url", type="string", nullable=true),
 *     @OA\Property(property="path", type="string"),
 *     @OA\Property(property="per_page", type="integer", example=20),
 *     @OA\Property(property="prev_page_url", type="string", nullable=true),
 *     @OA\Property(property="to", type="integer", nullable=true),
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
 *     description="Détail complet d'un produit avec variantes, options et produits associés",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Product"),
 *         @OA\Schema(
 *
 *             @OA\Property(property="description", type="string", nullable=true),
 *             @OA\Property(property="meta_title", type="string", nullable=true),
 *             @OA\Property(property="meta_description", type="string", nullable=true),
 *             @OA\Property(
 *                 property="options",
 *                 type="array",
 *
 *                 @OA\Items(ref="#/components/schemas/ProductOption")
 *             ),
 *
 *             @OA\Property(
 *                 property="variants",
 *                 type="array",
 *
 *                 @OA\Items(ref="#/components/schemas/ProductVariant")
 *             ),
 *
 *             @OA\Property(
 *                 property="relatedProducts",
 *                 type="array",
 *
 *                 @OA\Items(ref="#/components/schemas/Product")
 *             ),
 *
 *             @OA\Property(
 *                 property="related_products",
 *                 type="array",
 *
 *                 @OA\Items(ref="#/components/schemas/Product")
 *             )
 *         )
 *     }
 * )
 */
class ProductSchemas {}
