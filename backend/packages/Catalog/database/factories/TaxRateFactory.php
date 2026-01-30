<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\TaxRate;
use Omersia\Catalog\Models\TaxZone;

class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'tax_zone_id' => TaxZone::factory(),
            'name' => $this->faker->words(2, true).' Tax',
            'type' => 'percentage',
            'rate' => $this->faker->randomFloat(2, 5, 25),
            'compound' => false,
            'shipping_taxable' => true,
            'priority' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function percentage(float $rate = 20.00): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'rate' => $rate,
        ]);
    }

    public function fixed(float $rate = 5.00): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'rate' => $rate,
        ]);
    }

    public function compound(): static
    {
        return $this->state(fn (array $attributes) => [
            'compound' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
