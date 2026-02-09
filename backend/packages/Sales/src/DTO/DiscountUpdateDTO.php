<?php

declare(strict_types=1);

namespace Omersia\Sales\DTO;

/**
 * DTO pour la mise à jour d'une réduction
 */
class DiscountUpdateDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $method,
        public readonly string $type,
        public readonly ?string $code,
        public readonly ?string $valueType,
        public readonly ?float $value,
        public readonly int $priority,
        public readonly string $productScope,
        public readonly string $customerSelection,
        public readonly ?float $minSubtotal,
        public readonly ?int $minQuantity,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly ?int $usageLimit,
        public readonly ?int $usageLimitPerCustomer,
        public readonly ?int $buyQuantity,
        public readonly ?int $getQuantity,
        public readonly ?bool $getIsFree,
        public readonly bool $isActive,
        public readonly bool $combinesWithProductDiscounts,
        public readonly bool $combinesWithOrderDiscounts,
        public readonly bool $combinesWithShippingDiscounts,
        // Relations
        public readonly array $productIds = [],
        public readonly array $collectionIds = [],
        public readonly array $customerGroupIds = [],
        public readonly array $customerIds = [],
    ) {}

    /**
     * Créer depuis un tableau (ex: request validated)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            method: $data['method'],
            type: $data['type'],
            code: $data['method'] === 'code' ? ($data['code'] ?? null) : null,
            valueType: $data['value_type'] ?? null,
            value: isset($data['value']) ? (float) $data['value'] : null,
            priority: (int) ($data['priority'] ?? 0),
            productScope: $data['product_scope'] ?? 'all',
            customerSelection: $data['customer_selection'] ?? 'all',
            minSubtotal: isset($data['min_subtotal']) ? (float) $data['min_subtotal'] : null,
            minQuantity: isset($data['min_quantity']) ? (int) $data['min_quantity'] : null,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
            usageLimit: isset($data['usage_limit']) ? (int) $data['usage_limit'] : null,
            usageLimitPerCustomer: isset($data['usage_limit_per_customer']) ? (int) $data['usage_limit_per_customer'] : null,
            buyQuantity: isset($data['buy_quantity']) ? (int) $data['buy_quantity'] : null,
            getQuantity: isset($data['get_quantity']) ? (int) $data['get_quantity'] : null,
            getIsFree: isset($data['get_is_free']) ? (bool) $data['get_is_free'] : null,
            isActive: (bool) ($data['is_active'] ?? false),
            combinesWithProductDiscounts: (bool) ($data['combines_with_product_discounts'] ?? false),
            combinesWithOrderDiscounts: (bool) ($data['combines_with_order_discounts'] ?? false),
            combinesWithShippingDiscounts: (bool) ($data['combines_with_shipping_discounts'] ?? false),
            productIds: $data['product_ids'] ?? [],
            collectionIds: $data['collection_ids'] ?? [],
            customerGroupIds: $data['customer_group_ids'] ?? [],
            customerIds: $data['customer_ids'] ?? [],
        );
    }

    /**
     * Retourne les données pour la mise à jour du discount
     */
    public function toDiscountArray(): array
    {
        return [
            'name' => $this->name,
            'method' => $this->method,
            'type' => $this->type,
            'code' => $this->code,
            'value_type' => $this->valueType,
            'value' => $this->value,
            'priority' => $this->priority,
            'product_scope' => $this->productScope,
            'customer_selection' => $this->customerSelection,
            'min_subtotal' => $this->minSubtotal,
            'min_quantity' => $this->minQuantity,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'usage_limit' => $this->usageLimit,
            'usage_limit_per_customer' => $this->usageLimitPerCustomer,
            'buy_quantity' => $this->buyQuantity,
            'get_quantity' => $this->getQuantity,
            'get_is_free' => $this->getIsFree,
            'is_active' => $this->isActive,
            'combines_with_product_discounts' => $this->combinesWithProductDiscounts,
            'combines_with_order_discounts' => $this->combinesWithOrderDiscounts,
            'combines_with_shipping_discounts' => $this->combinesWithShippingDiscounts,
        ];
    }
}
