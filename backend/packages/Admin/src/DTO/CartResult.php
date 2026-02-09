<?php

declare(strict_types=1);

namespace Omersia\Admin\DTO;

class CartResult
{
    /**
     * @param  CartLineResult[]  $lines
     * @param  CartDiscountAllocation[]  $applied_discounts
     */
    public function __construct(
        public array $lines,
        public float $subtotal,
        public float $product_discount_total,
        public float $order_discount_total,
        public float $shipping_amount,
        public float $shipping_discount_total,
        public float $total_discounts,
        public float $total,
        public array $applied_discounts,
        public ?string $discount_code = null,
        public ?string $discount_code_error = null,
    ) {}

    public static function empty(): self
    {
        return new self([], 0, 0, 0, 0, 0, 0, 0, [], null, null);
    }
}
