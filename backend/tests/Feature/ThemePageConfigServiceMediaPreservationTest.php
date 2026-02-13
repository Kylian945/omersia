<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\EcommercePageTranslation;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Services\ThemePageConfigService;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class ThemePageConfigServiceMediaPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_preserves_uploaded_widget_images_when_theme_pages_are_force_updated(): void
    {
        $shop = Shop::factory()->create();

        $theme = Theme::factory()->create([
            'shop_id' => $shop->id,
            'slug' => 'test-media-preservation',
            'pages_config_path' => 'themes/test-media-preservation/pages.json',
        ]);

        $this->writePagesConfig($theme->pages_config_path, $this->themePagesConfig('Nouveau titre hero'));

        $page = EcommercePage::factory()->create([
            'shop_id' => $shop->id,
            'type' => 'home',
            'slug' => 'accueil',
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Accueil',
            'content_json' => $this->pageContentWithMedia(
                '/uploads/hero-original.jpg',
                ['/uploads/gallery-1.jpg', '/uploads/gallery-2.jpg']
            ),
        ]);

        $service = new ThemePageConfigService;
        $stats = $service->applyThemePagesConfig($theme, $shop, forceUpdate: true);

        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertEmpty($stats['errors']);

        $updatedTranslation = EcommercePage::query()
            ->where('shop_id', $shop->id)
            ->where('type', 'home')
            ->where('slug', 'accueil')
            ->firstOrFail()
            ->translations()
            ->where('locale', 'fr')
            ->firstOrFail();

        $widgetsById = $this->widgetsById($updatedTranslation->content_json ?? []);

        $this->assertSame('Nouveau titre hero', $widgetsById['widget-hero']['props']['title'] ?? null);
        $this->assertSame('/uploads/hero-original.jpg', $widgetsById['widget-hero']['props']['image'] ?? null);
        $this->assertSame(
            ['/uploads/gallery-1.jpg', '/uploads/gallery-2.jpg'],
            $widgetsById['widget-gallery']['props']['images'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function test_it_keeps_images_scoped_per_theme_when_switching_themes(): void
    {
        $shop = Shop::factory()->create();
        $service = new ThemePageConfigService;

        $themeA = Theme::factory()->create([
            'shop_id' => $shop->id,
            'slug' => 'theme-a',
            'pages_config_path' => 'themes/theme-a/pages.json',
            'metadata' => [],
        ]);
        $themeB = Theme::factory()->create([
            'shop_id' => $shop->id,
            'slug' => 'theme-b',
            'pages_config_path' => 'themes/theme-b/pages.json',
            'metadata' => [],
        ]);

        $this->writePagesConfig($themeA->pages_config_path, $this->themePagesConfig('Titre Theme A'));
        $this->writePagesConfig($themeB->pages_config_path, $this->themePagesConfig('Titre Theme B'));

        $page = EcommercePage::factory()->create([
            'shop_id' => $shop->id,
            'type' => 'home',
            'slug' => 'accueil',
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Accueil',
            'content_json' => $this->pageContentWithMedia('/uploads/theme-a-hero.jpg', ['/uploads/theme-a-gallery.jpg']),
        ]);

        // Snapshot du thème A avant de basculer.
        $service->saveThemePageMediaSnapshot($themeA, $shop);

        // Activation thème B sans snapshot propre => pas d'héritage des images du thème A.
        $statsB = $service->applyThemePagesConfig(
            $themeB,
            $shop,
            forceUpdate: true,
            preserveMediaByTheme: true
        );

        $this->assertSame(1, $statsB['updated']);
        $widgetsThemeB = $this->widgetsById($this->getHomeTranslationContent($shop));
        $this->assertSame('', $widgetsThemeB['widget-hero']['props']['image'] ?? null);
        $this->assertSame([], $widgetsThemeB['widget-gallery']['props']['images'] ?? null);

        // L'utilisateur upload une image sur thème B, puis on snapshot ce thème.
        $translationThemeB = EcommercePage::query()
            ->where('shop_id', $shop->id)
            ->where('type', 'home')
            ->where('slug', 'accueil')
            ->firstOrFail()
            ->translations()
            ->where('locale', 'fr')
            ->firstOrFail();

        $translationThemeB->update([
            'content_json' => $this->pageContentWithMedia('/uploads/theme-b-hero.jpg', ['/uploads/theme-b-gallery.jpg']),
        ]);
        $service->saveThemePageMediaSnapshot($themeB, $shop);

        // Retour vers thème A => restauration de ses propres images.
        $statsA = $service->applyThemePagesConfig(
            $themeA,
            $shop,
            forceUpdate: true,
            preserveMediaByTheme: true
        );

        $this->assertSame(1, $statsA['updated']);
        $widgetsThemeA = $this->widgetsById($this->getHomeTranslationContent($shop));
        $this->assertSame('/uploads/theme-a-hero.jpg', $widgetsThemeA['widget-hero']['props']['image'] ?? null);
        $this->assertSame(['/uploads/theme-a-gallery.jpg'], $widgetsThemeA['widget-gallery']['props']['images'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function themePagesConfig(string $heroTitle): array
    {
        return [
            'pages' => [
                [
                    'type' => 'home',
                    'slug' => 'accueil',
                    'translations' => [
                        'fr' => [
                            'title' => 'Accueil',
                            'meta_title' => 'Accueil',
                            'meta_description' => 'Accueil',
                            'noindex' => false,
                            'content' => [
                                'sections' => [
                                    [
                                        'id' => 'section-1',
                                        'columns' => [
                                            [
                                                'id' => 'col-1',
                                                'widgets' => [
                                                    [
                                                        'id' => 'widget-hero',
                                                        'type' => 'hero_banner',
                                                        'props' => [
                                                            'title' => $heroTitle,
                                                            'description' => 'Nouveau descriptif',
                                                            'image' => '',
                                                        ],
                                                    ],
                                                    [
                                                        'id' => 'widget-gallery',
                                                        'type' => 'custom_gallery',
                                                        'props' => [
                                                            'images' => [],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pageContentWithMedia(string $heroImage, array $galleryImages): array
    {
        return [
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'widgets' => [
                                [
                                    'id' => 'widget-hero',
                                    'type' => 'hero_banner',
                                    'props' => [
                                        'title' => 'Ancien titre hero',
                                        'image' => $heroImage,
                                    ],
                                ],
                                [
                                    'id' => 'widget-gallery',
                                    'type' => 'custom_gallery',
                                    'props' => [
                                        'images' => $galleryImages,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function writePagesConfig(string $relativePath, array $config): void
    {
        $fullPath = storage_path('app/'.$relativePath);
        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>
     */
    private function getHomeTranslationContent(Shop $shop): array
    {
        $translation = EcommercePage::query()
            ->where('shop_id', $shop->id)
            ->where('type', 'home')
            ->where('slug', 'accueil')
            ->firstOrFail()
            ->translations()
            ->where('locale', 'fr')
            ->firstOrFail();

        return is_array($translation->content_json) ? $translation->content_json : ['sections' => []];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, array<string, mixed>>
     */
    private function widgetsById(array $content): array
    {
        $widgets = [];
        $sections = $content['sections'] ?? [];
        if (! is_array($sections)) {
            return $widgets;
        }

        foreach ($sections as $section) {
            $columns = is_array($section) ? ($section['columns'] ?? null) : null;
            if (! is_array($columns)) {
                continue;
            }
            $this->collectWidgetsByIdFromColumns($columns, $widgets);
        }

        return $widgets;
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, array<string, mixed>>  $widgets
     */
    private function collectWidgetsByIdFromColumns(array $columns, array &$widgets): void
    {
        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $columnWidgets = $column['widgets'] ?? null;
            if (is_array($columnWidgets)) {
                foreach ($columnWidgets as $widget) {
                    if (! is_array($widget)) {
                        continue;
                    }

                    $widgetId = $widget['id'] ?? null;
                    if (is_string($widgetId) && $widgetId !== '') {
                        $widgets[$widgetId] = $widget;
                    }

                    $nestedColumns = $widget['props']['columns'] ?? null;
                    if (($widget['type'] ?? null) === 'container' && is_array($nestedColumns)) {
                        $this->collectWidgetsByIdFromColumns($nestedColumns, $widgets);
                    }
                }
            }

            $nestedColumns = $column['columns'] ?? null;
            if (is_array($nestedColumns)) {
                $this->collectWidgetsByIdFromColumns($nestedColumns, $widgets);
            }
        }
    }
}
