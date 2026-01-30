<?php

declare(strict_types=1);

namespace Omersia\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Core\Models\Shop;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->slug(2),
            'default_locale' => 'fr',
            'default_currency_id' => null,
            'logo_path' => null,
            'display_name' => $this->faker->company(),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
