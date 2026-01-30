<?php

declare(strict_types=1);

namespace Omersia\Admin\DTO;

class Cart
{
    /**
     * @param  CartLine[]  $lines
     * @param  int[]  $customer_group_ids
     */
    public function __construct(
        public array $lines,
        public float $shipping_amount,
        public ?int $customer_id = null,
        public array $customer_group_ids = [],
        public ?string $discount_code = null,
    ) {}
}
