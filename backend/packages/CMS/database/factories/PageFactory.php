<?php

declare(strict_types=1);

namespace Omersia\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\CMS\Models\Page;
use Omersia\Core\Models\Shop;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'type' => 'page',
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => null,
            'is_active' => true,
            'is_home' => false,
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

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Page::STATUS_DRAFT,
            'published_at' => null,
            'published_by' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Page::STATUS_ARCHIVED,
        ]);
    }

    public function homePage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_home' => true,
            'type' => 'page',
        ]);
    }

    public function legal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'legal',
            'is_active' => true,
        ]);
    }
}
