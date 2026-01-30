<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductTranslation;

class ProductTranslationFactory extends Factory
{
    protected $model = ProductTranslation::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'product_id' => Product::factory(),
            'locale' => 'fr',
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'short_description' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(3, true),
            'meta_title' => $name,
            'meta_description' => $this->faker->sentence(),
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => $locale,
        ]);
    }
}
