<?php

declare(strict_types=1);

namespace Omersia\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Models\PageVersion;

class PageVersionFactory extends Factory
{
    protected $model = PageVersion::class;

    public function definition(): array
    {
        return [
            'page_translation_id' => PageTranslation::factory(),
            'content_json' => [
                'sections' => [
                    [
                        'id' => 'section-'.$this->faker->randomNumber(3),
                        'columns' => [],
                    ],
                ],
            ],
            'created_by' => null,
            'label' => 'Version '.$this->faker->dateTimeBetween('-7 days', 'now')->format('d/m H:i'),
            'created_at' => now(),
        ];
    }
}

