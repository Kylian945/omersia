<?php

namespace Omersia\Api\DTO;

/**
 * DTO pour un article du panier
 */
class CartItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly float $price,
        public readonly int $qty,
        public readonly ?string $name = null,
        public readonly ?int $variantId = null,
        public readonly ?string $imageUrl = null,
    ) {}

    /**
     * Créer depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            price: (float) $data['price'],
            qty: (int) $data['qty'],
            name: $data['name'] ?? null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            imageUrl: $data['image_url'] ?? null,
        );
    }

    /**
     * Convertir en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'qty' => $this->qty,
            'variant_id' => $this->variantId,
            'image_url' => $this->imageUrl,
        ];
    }

    /**
     * Calcule le sous-total de la ligne
     */
    public function getLineSubtotal(): float
    {
        return $this->price * $this->qty;
    }
}
