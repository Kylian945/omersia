<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Models\ThemeSetting;
use Omersia\Apparence\Services\ThemeCustomizationService;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeCustomizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThemeCustomizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ThemeCustomizationService::class);
    }

    #[Test]
    public function get_default_settings_returns_expected_groups(): void
    {
        $defaults = $this->service->getDefaultSettings();

        $this->assertArrayHasKey('colors', $defaults);
        $this->assertArrayHasKey('typography', $defaults);
        $this->assertArrayHasKey('layout', $defaults);
        $this->assertArrayHasKey('buttons', $defaults);
        $this->assertArrayHasKey('header', $defaults);
        $this->assertArrayHasKey('backgrounds', $defaults);
        $this->assertArrayHasKey('texts', $defaults);
        $this->assertArrayHasKey('borders', $defaults);
        $this->assertArrayHasKey('states', $defaults);
    }

    #[Test]
    public function get_default_settings_has_primary_color(): void
    {
        $defaults = $this->service->getDefaultSettings();
        $this->assertArrayHasKey('primary', $defaults['colors']);
        $this->assertEquals('#111827', $defaults['colors']['primary']['default']);
    }

    #[Test]
    public function generate_css_variables_contains_root(): void
    {
        $settings = ['colors' => ['primary' => '#ff0000']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('}', $css);
    }

    #[Test]
    public function generate_css_variables_outputs_color_vars(): void
    {
        $settings = ['colors' => ['primary' => '#111827', 'secondary' => '#6366f1']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-primary: #111827', $css);
        $this->assertStringContainsString('--theme-secondary: #6366f1', $css);
    }

    #[Test]
    public function generate_css_variables_converts_underscores_to_dashes(): void
    {
        $settings = ['backgrounds' => ['page_bg' => '#ffffff']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-page-bg: #ffffff', $css);
    }

    #[Test]
    public function generate_css_variables_appends_px_to_size_values(): void
    {
        $settings = ['typography' => ['h1_size' => '48']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-h1-size: 48px', $css);
    }

    #[Test]
    public function generate_css_variables_button_style_square(): void
    {
        $settings = ['buttons' => ['button_style' => 'square']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-button-radius: 0px', $css);
    }

    #[Test]
    public function generate_css_variables_button_style_rounded(): void
    {
        $settings = ['buttons' => ['button_style' => 'rounded']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-button-radius: 8px', $css);
    }

    #[Test]
    public function generate_css_variables_button_style_pill(): void
    {
        $settings = ['buttons' => ['button_style' => 'pill']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-button-radius: 9999px', $css);
    }

    #[Test]
    public function generate_css_variables_shadow_none(): void
    {
        $settings = ['surfaces' => ['shadow_style' => 'none']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-shadow-sm: none', $css);
        $this->assertStringContainsString('--theme-shadow-md: none', $css);
    }

    #[Test]
    public function generate_css_variables_shadow_hard(): void
    {
        $settings = ['surfaces' => ['shadow_style' => 'hard']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-shadow-sm: 0 2px 0', $css);
    }

    #[Test]
    public function generate_css_variables_shadow_soft_default(): void
    {
        $settings = ['surfaces' => ['shadow_style' => 'soft']];
        $css = $this->service->generateCssVariables($settings);

        $this->assertStringContainsString('--theme-shadow-sm: 0 1px 2px', $css);
    }

    #[Test]
    public function generate_css_variables_handles_empty_settings(): void
    {
        $css = $this->service->generateCssVariables([]);

        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('}', $css);
    }

    #[Test]
    public function clear_theme_cache_removes_api_and_settings_cache_entries(): void
    {
        $shop = Shop::factory()->create();
        $theme = Theme::factory()->create([
            'shop_id' => $shop->id,
            'slug' => 'vision',
            'is_active' => true,
        ]);

        $settingsKey = "theme_settings_{$shop->id}";
        $apiKey = "theme.settings.full.{$shop->id}";

        Cache::put($settingsKey, ['cached' => true], 3600);
        Cache::put($apiKey, ['cached' => true], 3600);
        Cache::tags(['shop', 'theme'])->put($apiKey, ['cached' => true], 3600);

        $this->service->clearThemeCache($theme);

        $this->assertNull(Cache::get($settingsKey));
        $this->assertNull(Cache::get($apiKey));
        $this->assertNull(Cache::tags(['shop', 'theme'])->get($apiKey));
    }

    #[Test]
    public function update_settings_realigns_existing_setting_group_from_schema(): void
    {
        $shop = Shop::factory()->create();
        $theme = Theme::factory()->create([
            'shop_id' => $shop->id,
            'slug' => 'vision',
            'is_active' => true,
        ]);

        ThemeSetting::create([
            'theme_id' => $theme->id,
            'key' => 'product_image_ratio',
            'value' => 'square',
            'type' => 'text',
            'group' => 'general',
        ]);

        $this->service->updateSettings($theme, [
            'product_image_ratio' => 'portrait',
        ]);

        $setting = ThemeSetting::where('theme_id', $theme->id)
            ->where('key', 'product_image_ratio')
            ->firstOrFail();

        $this->assertSame('portrait', $setting->getDecodedValue());
        $this->assertSame('products', $setting->group);
        $this->assertSame('select', $setting->type);
    }
}
