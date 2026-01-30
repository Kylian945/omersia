<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Cart;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'token' => $this->faker->uuid(),
            'customer_id' => null,
            'email' => null,
            'currency' => 'EUR',
            'subtotal' => 0,
            'total_qty' => 0,
            'status' => 'open',
            'metadata' => [],
            'last_activity_at' => now(),
        ];
    }

    public function withCustomer(int $customerId): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
            'email' => $this->faker->safeEmail(),
        ]);
    }
}
