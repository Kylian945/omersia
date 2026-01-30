<?php

declare(strict_types=1);

namespace Omersia\Api\DTO;

/**
 * DTO pour la mise à jour d'une commande
 */
class OrderUpdateDTO
{
    public function __construct(
        public readonly string $currency,
        public readonly int $shippingMethodId,
        public readonly string $customerEmail,
        public readonly ?string $customerFirstname,
        public readonly ?string $customerLastname,
        public readonly array $shippingAddress,
        public readonly array $billingAddress,
        public readonly array $items,
        public readonly float $discountTotal,
        public readonly float $shippingTotal,
        public readonly float $taxTotal,
        public readonly float $total,
    ) {}

    /**
     * Créer depuis un tableau (ex: request validated)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            currency: $data['currency'],
            shippingMethodId: $data['shipping_method_id'],
            customerEmail: $data['customer_email'],
            customerFirstname: $data['customer_firstname'] ?? null,
            customerLastname: $data['customer_lastname'] ?? null,
            shippingAddress: $data['shipping_address'],
            billingAddress: $data['billing_address'],
            items: $data['items'],
            discountTotal: (float) $data['discount_total'],
            shippingTotal: (float) $data['shipping_total'],
            taxTotal: (float) $data['tax_total'],
            total: (float) $data['total'],
        );
    }
}
