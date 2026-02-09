<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\OrderItem;
use Omersia\Catalog\Models\Product;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('???-######')),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'meta' => [],
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $this->faker->numberBetween(1, 100),
        ]);
    }
}
