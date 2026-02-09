<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\EcommercePageTranslation;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Services\ThemeWidgetService;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class ThemeWidgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ThemeWidgetService $service;

    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ThemeWidgetService;
        $this->shop = Shop::factory()->create();
    }

    public function it_detects_removed_widgets_between_themes(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'hero-banner', 'name' => 'Hero Banner'],
                ['type' => 'product-grid', 'name' => 'Product Grid'],
                ['type' => 'testimonials', 'name' => 'Testimonials'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'hero-banner', 'name' => 'Hero Banner'],
                ['type' => 'product-grid', 'name' => 'Product Grid'],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        $this->assertTrue($result['has_incompatibilities']);
        $this->assertContains('testimonials', array_column($result['removed_widgets'], 'type'));
        $this->assertCount(1, $result['removed_widgets']);
    }

    public function it_detects_added_widgets_between_themes(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'hero-banner', 'name' => 'Hero Banner'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'hero-banner', 'name' => 'Hero Banner'],
                ['type' => 'product-carousel', 'name' => 'Product Carousel'],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        $this->assertCount(1, $result['added_widgets']);
        $this->assertContains('product-carousel', array_column($result['added_widgets'], 'type'));
    }

    public function it_returns_no_incompatibilities_when_all_widgets_are_compatible(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'hero-banner', 'name' => 'Hero Banner'],
                ['type' => 'product-grid', 'name' => 'Product Grid'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'hero-banner', 'name' => 'Hero Banner V2'],
                ['type' => 'product-grid', 'name' => 'Product Grid V2'],
                ['type' => 'newsletter', 'name' => 'Newsletter'],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        $this->assertFalse($result['has_incompatibilities']);
        $this->assertEmpty($result['removed_widgets']);
        $this->assertCount(1, $result['added_widgets']);
    }

    public function it_finds_pages_using_removed_widgets(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'old-widget', 'name' => 'Old Widget'],
                ['type' => 'common-widget', 'name' => 'Common Widget'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'common-widget', 'name' => 'Common Widget'],
            ],
        ]);

        $page = EcommercePage::factory()->create([
            'shop_id' => $this->shop->id,
            'type' => 'custom',
            'slug' => 'test-page',
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Test Page',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'old-widget', 'settings' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        $this->assertTrue($result['has_incompatibilities']);
        $this->assertCount(1, $result['affected_pages']);
        $this->assertEquals($page->id, $result['affected_pages'][0]['page_id']);
        $this->assertContains('old-widget', $result['affected_pages'][0]['incompatible_widgets']);
    }

    public function it_extracts_widget_types_from_nested_columns(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'nested-widget', 'name' => 'Nested Widget'],
                ['type' => 'inner-widget', 'name' => 'Inner Widget'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [],
        ]);

        $page = EcommercePage::factory()->create([
            'shop_id' => $this->shop->id,
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Nested Page',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'nested-widget'],
                                ],
                                'columns' => [
                                    [
                                        'widgets' => [
                                            ['type' => 'inner-widget'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        $this->assertCount(1, $result['affected_pages']);
        $this->assertContains('nested-widget', $result['affected_pages'][0]['incompatible_widgets']);
        $this->assertContains('inner-widget', $result['affected_pages'][0]['incompatible_widgets']);
    }

    public function it_counts_total_widgets_to_remove(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'widget-a', 'name' => 'Widget A'],
                ['type' => 'widget-b', 'name' => 'Widget B'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [],
        ]);

        // Page 1 with 2 widgets
        $page1 = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page1->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'widget-a'],
                                    ['type' => 'widget-b'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Page 2 with 1 widget
        $page2 = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page2->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'widget-a'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        // Total incompatible widget types across all pages: page1 has 2 types, page2 has 1 type = 3
        $this->assertEquals(3, $result['total_widgets_to_remove']);
    }

    public function it_handles_multiple_translations_per_page(): void
    {
        $currentTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [
                ['type' => 'old-widget', 'name' => 'Old Widget'],
            ],
        ]);

        $newTheme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'widgets' => [],
        ]);

        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'title' => 'English Page',
            'content_json' => [
                'sections' => [
                    ['columns' => [['widgets' => [['type' => 'old-widget']]]]],
                ],
            ],
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page Française',
            'content_json' => [
                'sections' => [
                    ['columns' => [['widgets' => [['type' => 'old-widget']]]]],
                ],
            ],
        ]);

        $result = $this->service->compareThemeWidgets($currentTheme, $newTheme);

        // Should report both translations
        $this->assertCount(2, $result['affected_pages']);
        $this->assertEquals('en', $result['affected_pages'][0]['locale']);
        $this->assertEquals('fr', $result['affected_pages'][1]['locale']);
    }

    public function it_cleans_incompatible_widgets_from_pages(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        $translation = EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'keep-widget', 'settings' => []],
                                    ['type' => 'remove-widget', 'settings' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $removedCount = $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['remove-widget']
        );

        $this->assertEquals(1, $removedCount);

        $translation->refresh();
        $widgets = $translation->content_json['sections'][0]['columns'][0]['widgets'];

        $this->assertCount(1, $widgets);
        $this->assertEquals('keep-widget', $widgets[0]['type']);
    }

    public function it_cleans_widgets_from_nested_columns(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        $translation = EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'parent-widget'],
                                ],
                                'columns' => [
                                    [
                                        'widgets' => [
                                            ['type' => 'child-widget'],
                                            ['type' => 'remove-widget'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['remove-widget']
        );

        $translation->refresh();
        $nestedWidgets = $translation->content_json['sections'][0]['columns'][0]['columns'][0]['widgets'];

        $this->assertCount(1, $nestedWidgets);
        $this->assertEquals('child-widget', $nestedWidgets[0]['type']);
    }

    public function it_returns_zero_when_no_widgets_to_remove(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'keep-widget'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $removedCount = $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['non-existent-widget']
        );

        $this->assertEquals(0, $removedCount);
    }

    public function it_handles_empty_widget_array(): void
    {
        $removedCount = $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            []
        );

        $this->assertEquals(0, $removedCount);
    }

    public function it_handles_pages_with_no_sections(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'content_json' => [],
        ]);

        $removedCount = $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['any-widget']
        );

        $this->assertEquals(0, $removedCount);
    }

    public function it_handles_columns_without_widgets_array(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                // No widgets array
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $removedCount = $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['any-widget']
        );

        $this->assertEquals(0, $removedCount);
    }

    public function it_preserves_widget_array_indices_after_removal(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);

        $translation = EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    [
                        'columns' => [
                            [
                                'widgets' => [
                                    ['type' => 'widget-1'],
                                    ['type' => 'remove-widget'],
                                    ['type' => 'widget-2'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['remove-widget']
        );

        $translation->refresh();
        $widgets = $translation->content_json['sections'][0]['columns'][0]['widgets'];

        // Should be re-indexed (array_values is used)
        $this->assertArrayHasKey(0, $widgets);
        $this->assertArrayHasKey(1, $widgets);
        $this->assertArrayNotHasKey(2, $widgets);
    }

    public function it_only_affects_pages_from_specified_shop(): void
    {
        $otherShop = Shop::factory()->create();

        $page1 = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        $page2 = EcommercePage::factory()->create(['shop_id' => $otherShop->id]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page1->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    ['columns' => [['widgets' => [['type' => 'remove-widget']]]]],
                ],
            ],
        ]);

        $trans2 = EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page2->id,
            'locale' => 'en',
            'content_json' => [
                'sections' => [
                    ['columns' => [['widgets' => [['type' => 'remove-widget']]]]],
                ],
            ],
        ]);

        $removedCount = $this->service->cleanIncompatibleWidgets(
            $this->shop->id,
            ['remove-widget']
        );

        $this->assertEquals(1, $removedCount);

        // Other shop's page should remain unchanged
        $trans2->refresh();
        $this->assertCount(1, $trans2->content_json['sections'][0]['columns'][0]['widgets']);
    }
}
