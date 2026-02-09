<?php

declare(strict_types=1);

namespace Omersia\Api\DTO;

/**
 * DTO pour la création d'une commande
 * Encapsule toutes les données nécessaires à la création d'une commande
 */
class OrderCreateDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly ?int $cartId,
        public readonly int $shippingMethodId,
        public readonly string $currency,
        public readonly string $customerEmail,
        public readonly ?string $customerFirstname,
        public readonly ?string $customerLastname,
        public readonly array $shippingAddress,
        public readonly array $billingAddress,
        public readonly array $items,
        public readonly float $discountTotal = 0.0,
        public readonly float $shippingTotal = 0.0,
        public readonly float $taxTotal = 0.0,
        public readonly array $appliedDiscountCodes = [],
    ) {}

    /**
     * Créer depuis un tableau (ex: request validated)
     */
    public static function fromArray(array $data, int $customerId): self
    {
        return new self(
            customerId: $customerId,
            cartId: $data['cart_id'] ?? null,
            shippingMethodId: $data['shipping_method_id'],
            currency: $data['currency'],
            customerEmail: $data['customer_email'],
            customerFirstname: $data['customer_firstname'] ?? null,
            customerLastname: $data['customer_lastname'] ?? null,
            shippingAddress: $data['shipping_address'],
            billingAddress: $data['billing_address'] ?? $data['shipping_address'],
            items: $data['items'],
            discountTotal: (float) ($data['discount_total'] ?? 0.0),
            shippingTotal: (float) ($data['shipping_total'] ?? 0.0),
            taxTotal: (float) ($data['tax_total'] ?? 0.0),
            appliedDiscountCodes: $data['applied_discount_codes'] ?? [],
        );
    }

    /**
     * Calculer le sous-total à partir des items
     */
    public function calculateSubtotal(): float
    {
        return array_reduce(
            $this->items,
            fn ($sum, $item) => $sum + ($item['total_price'] ?? 0),
            0.0
        );
    }

    /**
     * Calculer le total de la commande
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->calculateSubtotal();

        return $subtotal - $this->discountTotal + $this->shippingTotal + $this->taxTotal;
    }

    /**
     * Créer un nouveau DTO avec des prix vérifiés
     */
    public function withVerifiedPrices(
        array $verifiedItems,
        float $verifiedSubtotal,
        float $verifiedDiscountTotal,
        array $discountIds
    ): self {
        return new self(
            customerId: $this->customerId,
            cartId: $this->cartId,
            shippingMethodId: $this->shippingMethodId,
            currency: $this->currency,
            customerEmail: $this->customerEmail,
            customerFirstname: $this->customerFirstname,
            customerLastname: $this->customerLastname,
            shippingAddress: $this->shippingAddress,
            billingAddress: $this->billingAddress,
            items: $verifiedItems,
            discountTotal: $verifiedDiscountTotal,
            shippingTotal: $this->shippingTotal,
            taxTotal: $this->taxTotal,
            appliedDiscountCodes: $this->appliedDiscountCodes,
        );
    }
}
