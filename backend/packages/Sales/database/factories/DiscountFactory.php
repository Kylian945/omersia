<?php

declare(strict_types=1);

namespace Omersia\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Core\Models\Shop;
use Omersia\Sales\Models\Discount;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => $this->faker->words(3, true),
            'type' => 'product',
            'method' => 'automatic',
            'code' => null,
            'value_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ];
    }
}
