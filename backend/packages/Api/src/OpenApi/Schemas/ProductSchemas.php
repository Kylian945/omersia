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
 *     @OA\Property(property="slug", type="string", nullable=true, example="vetements", description="Slug de la catégorie (ajouté dynamiquement depuis la traduction)"),
 *     @OA\Property(property="name", type="string", nullable=true, example="Vêtements", description="Nom de la catégorie (ajouté dynamiquement depuis la traduction)"),
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
 *     ),
 *
 *     @OA\Property(property="compare_at_price", type="number", format="float", nullable=true, example=39.99, description="Prix avant réduction"),
 *     @OA\Property(property="on_sale", type="boolean", example=false, description="Indique si le produit est en promotion"),
 *     @OA\Property(property="has_variants", type="boolean", example=false, description="Indique si le produit possède des variantes"),
 *     @OA\Property(property="from_price", type="number", format="float", nullable=true, example=19.99, description="Prix minimum des variantes (si has_variants=true)")
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
 *     schema="OptionValue",
 *     type="object",
 *
 *     @OA\Property(property="option", type="string", example="Taille", description="Nom de l'option"),
 *     @OA\Property(property="value", type="string", example="M", description="Valeur de l'option")
 * )
 *
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=456),
 *     @OA\Property(property="sku", type="string", nullable=true, example="TSHIRT-BLACK-M"),
 *     @OA\Property(property="price", type="number", format="float", example=29.99),
 *     @OA\Property(property="compare_at_price", type="number", format="float", nullable=true, example=39.99),
 *     @OA\Property(property="on_sale", type="boolean", example=false),
 *     @OA\Property(property="stock", type="integer", example=15),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(
 *         property="option_values",
 *         type="array",
 *         description="Valeurs d'options associées à cette variante",
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
 *     @OA\Property(
 *         property="values",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="value", type="string", example="S")
 *         )
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
 *             @OA\Property(
 *                 property="variants",
 *                 type="array",
 *                 description="Variantes du produit",
 *
 *                 @OA\Items(ref="#/components/schemas/ProductVariant")
 *             ),
 *             @OA\Property(
 *                 property="options",
 *                 type="array",
 *                 description="Options configurables (Taille, Couleur, etc.)",
 *
 *                 @OA\Items(ref="#/components/schemas/ProductOption")
 *             ),
 *             @OA\Property(
 *                 property="relatedProducts",
 *                 type="array",
 *                 description="Produits associés",
 *
 *                 @OA\Items(ref="#/components/schemas/Product")
 *             )
 *         )
 *     }
 * )
 */
class ProductSchemas {}
