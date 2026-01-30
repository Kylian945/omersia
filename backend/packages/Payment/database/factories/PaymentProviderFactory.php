<?php

declare(strict_types=1);

namespace Omersia\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Payment\Models\PaymentProvider;

class PaymentProviderFactory extends Factory
{
    protected $model = PaymentProvider::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->slug(2),
            'enabled' => true,
            'config' => [],
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
