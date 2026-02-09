<?php

declare(strict_types=1);

namespace Omersia\Sales\Services;

use Omersia\Sales\DTO\DiscountCreateDTO;
use Omersia\Sales\DTO\DiscountUpdateDTO;
use Omersia\Sales\Models\Discount;

/**
 * Service pour la création et mise à jour de réductions
 */
class DiscountCreationService
{
    public function __construct(
        private readonly DiscountRelationService $discountRelationService
    ) {}

    /**
     * Créer une nouvelle réduction avec ses relations
     */
    public function createDiscount(DiscountCreateDTO $dto): Discount
    {
        $discount = Discount::create($dto->toDiscountArray());

        $this->discountRelationService->syncRelations(
            discount: $discount,
            productScope: $dto->productScope,
            customerSelection: $dto->customerSelection,
            productIds: $dto->productIds,
            collectionIds: $dto->collectionIds,
            customerGroupIds: $dto->customerGroupIds,
            customerIds: $dto->customerIds
        );

        return $discount;
    }

    /**
     * Mettre à jour une réduction existante avec ses relations
     */
    public function updateDiscount(Discount $discount, DiscountUpdateDTO $dto): Discount
    {
        $discount->fill($dto->toDiscountArray());
        $discount->save();

        $this->discountRelationService->syncRelations(
            discount: $discount,
            productScope: $dto->productScope,
            customerSelection: $dto->customerSelection,
            productIds: $dto->productIds,
            collectionIds: $dto->collectionIds,
            customerGroupIds: $dto->customerGroupIds,
            customerIds: $dto->customerIds
        );

        return $discount;
    }
}
