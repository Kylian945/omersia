<?php

declare(strict_types=1);

namespace Omersia\Catalog\DTO;

/**
 * DTO pour la création d'un produit
 * Encapsule toutes les données nécessaires à la création d'un produit
 */
class ProductCreateDTO
{
    public function __construct(
        public readonly int $shopId,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly ?string $sku = null,
        public readonly ?float $price = null,
        public readonly ?float $compareAtPrice = null,
        public readonly ?int $stockQty = null,
        public readonly bool $manageStock = false,
        // Translation data
        public readonly string $name = '',
        public readonly string $slug = '',
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly string $locale = 'fr',
        // Relations
        public readonly array $categoryIds = [],
        public readonly array $relatedProductIds = [],
    ) {}

    /**
     * Créer depuis un tableau (ex: request validated)
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] ?? 'simple';

        return new self(
            shopId: (int) ($data['shop_id'] ?? 1),
            type: $type,
            isActive: filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            sku: $type === 'simple' ? ($data['sku'] ?? null) : ($data['sku'] ?? null),
            price: $type === 'simple' ? (isset($data['price']) ? (float) $data['price'] : null) : (isset($data['price']) ? (float) $data['price'] : 0),
            compareAtPrice: $type === 'simple' ? (isset($data['compare_at_price']) ? (float) $data['compare_at_price'] : null) : null,
            stockQty: $type === 'simple' ? (int) ($data['stock_qty'] ?? 0) : 0,
            manageStock: $type === 'simple',
            name: $data['name'] ?? '',
            slug: $data['slug'] ?? '',
            shortDescription: $data['short_description'] ?? null,
            description: $data['description'] ?? null,
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            locale: $data['locale'] ?? 'fr',
            categoryIds: $data['categories'] ?? [],
            relatedProductIds: $data['related_products'] ?? [],
        );
    }

    /**
     * Retourne les données pour la création du produit
     */
    public function toProductArray(): array
    {
        return [
            'shop_id' => $this->shopId,
            'sku' => $this->sku,
            'type' => $this->type,
            'is_active' => $this->isActive,
            'manage_stock' => $this->manageStock,
            'stock_qty' => $this->stockQty ?? 0,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
        ];
    }

    /**
     * Retourne les données pour la traduction
     */
    public function toTranslationArray(): array
    {
        return [
            'locale' => $this->locale,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }
}
