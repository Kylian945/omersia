<?php

declare(strict_types=1);

namespace Omersia\Admin\DTO;

class CartDiscountAllocation
{
    public function __construct(
        public int $discount_id,
        public string $name,
        public string $type,   // product|order|shipping|buy_x_get_y
        public string $method, // code|automatic
        public float $amount,
        public ?string $description = null,
    ) {}
}
