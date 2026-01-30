<?php

declare(strict_types=1);

namespace Omersia\Admin\DTO;

class CartLineResult
{
    /**
     * @param  CartDiscountAllocation[]  $discounts
     */
    public function __construct(
        public int $product_id,
        public ?int $variant_id,
        public string $name,
        public int $quantity,
        public float $unit_price,
        public float $line_subtotal,
        public float $line_discount,
        public float $line_total,
        public array $discounts = [],
    ) {}
}
