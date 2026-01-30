<?php

declare(strict_types=1);

namespace Omersia\Apparence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\EcommercePageTranslation;

class EcommercePageTranslationFactory extends Factory
{
    protected $model = EcommercePageTranslation::class;

    public function definition(): array
    {
        return [
            'ecommerce_page_id' => EcommercePage::factory(),
            'locale' => 'en',
            'title' => $this->faker->sentence(3),
            'content_json' => null,
            'meta_title' => null,
            'meta_description' => null,
            'noindex' => false,
        ];
    }
}
