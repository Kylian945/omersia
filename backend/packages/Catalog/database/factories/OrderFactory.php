<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Order;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'cart_id' => null,
            'shipping_method_id' => null,
            'number' => $this->faker->unique()->numerify('ORD-######'),
            'status' => 'draft',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 0,
            'currency' => 'EUR',
            'customer_email' => $this->faker->safeEmail(),
            'customer_firstname' => $this->faker->firstName(),
            'customer_lastname' => $this->faker->lastName(),
            'shipping_address' => null,
            'billing_address' => null,
            'placed_at' => null,
            'meta' => [],
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'placed_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'placed_at' => null,
        ]);
    }
}
