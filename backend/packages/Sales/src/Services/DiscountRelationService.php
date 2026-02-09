<?php

declare(strict_types=1);

namespace Omersia\Sales\Services;

use Omersia\Sales\Models\Discount;

/**
 * Service pour gérer les relations pivot des réductions
 */
class DiscountRelationService
{
    /**
     * Synchroniser toutes les relations d'une réduction
     */
    public function syncRelations(
        Discount $discount,
        string $productScope,
        string $customerSelection,
        array $productIds,
        array $collectionIds,
        array $customerGroupIds,
        array $customerIds
    ): void {
        $this->syncProductRelations($discount, $productScope, $productIds, $collectionIds);
        $this->syncCustomerRelations($discount, $customerSelection, $customerGroupIds, $customerIds);
    }

    /**
     * Synchroniser les relations produits/collections
     */
    private function syncProductRelations(
        Discount $discount,
        string $productScope,
        array $productIds,
        array $collectionIds
    ): void {
        if ($productScope === 'products') {
            $discount->products()->sync($productIds);
            $discount->collections()->sync([]);
        } elseif ($productScope === 'collections') {
            $discount->collections()->sync($collectionIds);
            $discount->products()->sync([]);
        } else {
            // all : s'applique à tout, on vide les pivots
            $discount->products()->sync([]);
            $discount->collections()->sync([]);
        }
    }

    /**
     * Synchroniser les relations clients/groupes
     */
    private function syncCustomerRelations(
        Discount $discount,
        string $customerSelection,
        array $customerGroupIds,
        array $customerIds
    ): void {
        if ($customerSelection === 'groups') {
            $discount->customerGroups()->sync($customerGroupIds);
            $discount->customers()->sync([]);
        } elseif ($customerSelection === 'customers') {
            $discount->customers()->sync($customerIds);
            $discount->customerGroups()->sync([]);
        } else {
            // all : ouvert à tout le monde
            $discount->customerGroups()->sync([]);
            $discount->customers()->sync([]);
        }
    }
}
