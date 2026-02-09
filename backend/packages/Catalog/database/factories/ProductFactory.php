<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Product;
use Omersia\Core\Models\Shop;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'sku' => $this->faker->unique()->bothify('SKU-####-????'),
            'type' => 'simple',
            'is_active' => true,
            'manage_stock' => true,
            'stock_qty' => $this->faker->numberBetween(0, 100),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'compare_at_price' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_qty' => 0,
        ]);
    }
}
