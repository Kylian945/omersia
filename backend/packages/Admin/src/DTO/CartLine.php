<?php

declare(strict_types=1);

namespace Omersia\Admin\DTO;

class CartLine
{
    public function __construct(
        public int $product_id,
        public ?int $variant_id,
        public int $quantity,
        public float $unit_price,
        public ?int $collection_id = null,
        public ?string $name = null,
    ) {}
}
