<?php

declare(strict_types=1);

namespace Omersia\Apparence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Core\Models\Shop;

class EcommercePageFactory extends Factory
{
    protected $model = EcommercePage::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'slug' => $this->faker->unique()->slug(),
            'type' => 'page',
            'is_active' => true,
        ];
    }

    public function homepage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'homepage',
        ]);
    }
}
