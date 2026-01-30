<?php

declare(strict_types=1);

namespace Omersia\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;

class CategoryTranslationFactory extends Factory
{
    protected $model = CategoryTranslation::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'category_id' => Category::factory(),
            'locale' => 'fr',
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->paragraph(),
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
