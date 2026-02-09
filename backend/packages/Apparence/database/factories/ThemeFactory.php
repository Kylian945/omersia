<?php

declare(strict_types=1);

namespace Omersia\Apparence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Apparence\Models\Theme;
use Omersia\Core\Models\Shop;

class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'version' => '1.0.0',
            'author' => $this->faker->name(),
            'preview_image' => null,
            'zip_path' => null,
            'component_path' => null,
            'pages_config_path' => null,
            'widgets_config' => null,
            'is_active' => true,
            'is_default' => false,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
