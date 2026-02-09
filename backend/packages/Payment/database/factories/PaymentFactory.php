<?php

declare(strict_types=1);

namespace Omersia\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Models\Payment;
use Omersia\Payment\Models\PaymentProvider;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_provider_id' => PaymentProvider::factory(),
            'provider_code' => $this->faker->slug(),
            'provider_payment_id' => $this->faker->uuid(),
            'amount' => $this->faker->numberBetween(1000, 50000), // in cents
            'currency' => 'EUR',
            'status' => 'pending',
            'meta' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
