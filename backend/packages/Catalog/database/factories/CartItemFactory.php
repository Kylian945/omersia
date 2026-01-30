<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Cart;
use Omersia\Catalog\Models\CartItem;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => $this->faker->numberBetween(1, 100),
            'variant_id' => null,
            'name' => $this->faker->words(3, true),
            'variant_label' => null,
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'old_price' => null,
            'qty' => $this->faker->numberBetween(1, 5),
            'image_url' => $this->faker->imageUrl(),
            'options' => [],
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $this->faker->numberBetween(1, 100),
            'variant_label' => $this->faker->words(2, true),
        ]);
    }

    public function withOldPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'old_price' => $attributes['unit_price'] * 1.2,
        ]);
    }
}
