<?php

namespace Omersia\Api\DTO;

use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;

/**
 * DTO pour l'application d'une réduction à un panier
 */
class DiscountApplicationDTO
{
    /**
     * @param  Discount  $discount  La réduction à appliquer
     * @param  Customer|null  $customer  Le client (si connecté)
     * @param  CartItemDTO[]  $items  Les articles du panier
     * @param  float  $subtotal  Le sous-total du panier
     * @param  int[]  $productIds  Liste des IDs produits dans le panier
     */
    public function __construct(
        public readonly Discount $discount,
        public readonly ?Customer $customer,
        public readonly array $items,
        public readonly float $subtotal,
        public readonly array $productIds,
    ) {}

    /**
     * Obtient la liste des IDs de groupes clients du customer
     */
    public function getCustomerGroupIds(): array
    {
        if (! $this->customer) {
            return [];
        }

        return $this->customer->groups()
            ->pluck('customer_groups.id')
            ->all();
    }

    /**
     * Obtient la quantité totale d'articles dans le panier
     */
    public function getTotalQuantity(): int
    {
        return array_sum(array_map(fn ($item) => $item->qty, $this->items));
    }
}
