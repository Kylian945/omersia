<?php

declare(strict_types=1);

namespace Omersia\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;

class PageTranslationFactory extends Factory
{
    protected $model = PageTranslation::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3);
        $slug = $this->faker->unique()->slug(3);

        return [
            'page_id' => Page::factory(),
            'locale' => 'fr',
            'title' => $title,
            'slug' => $slug,
            'content' => null,
            'content_json' => null,
            'meta_title' => null,
            'meta_description' => null,
            'noindex' => false,
        ];
    }

    public function french(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'fr',
        ]);
    }

    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'en',
        ]);
    }

    public function withContentJson(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_json' => [
                'sections' => [
                    [
                        'id' => 'section-1',
                        'columns' => [
                            [
                                'id' => 'col-1',
                                'desktopWidth' => 100,
                                'mobileWidth' => 100,
                                'widgets' => [
                                    [
                                        'id' => 'widget-1',
                                        'type' => 'heading',
                                        'props' => ['text' => 'Test Heading'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
