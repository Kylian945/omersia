<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Models\ThemeSetting;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeModelTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_casts_is_active_to_boolean(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'is_active' => 1]);
        $this->assertIsBool($theme->is_active);
        $this->assertTrue($theme->is_active);
    }

    #[Test]
    public function it_casts_is_default_to_boolean(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'is_default' => 1]);
        $this->assertTrue($theme->is_default);
    }

    #[Test]
    public function it_casts_widgets_config_to_array(): void
    {
        $widgets = [['type' => 'hero_banner'], ['type' => 'heading']];
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'widgets_config' => $widgets]);
        $this->assertIsArray($theme->widgets_config);
        $this->assertCount(2, $theme->widgets_config);
    }

    #[Test]
    public function it_casts_settings_schema_to_array(): void
    {
        $schema = ['colors' => ['primary' => ['type' => 'color', 'default' => '#000']]];
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'settings_schema' => $schema]);
        $this->assertIsArray($theme->settings_schema);
    }

    #[Test]
    public function get_settings_array_returns_grouped_settings(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id]);
        ThemeSetting::create(['theme_id' => $theme->id, 'key' => 'primary', 'value' => '#111827', 'type' => 'color', 'group' => 'colors']);
        ThemeSetting::create(['theme_id' => $theme->id, 'key' => 'body_font', 'value' => 'Inter', 'type' => 'text', 'group' => 'typography']);
        $theme->load('settings');

        $settings = $theme->getSettingsArray();

        $this->assertArrayHasKey('colors', $settings);
        $this->assertArrayHasKey('typography', $settings);
        $this->assertEquals('#111827', $settings['colors']['primary']);
        $this->assertEquals('Inter', $settings['typography']['body_font']);
    }

    #[Test]
    public function get_settings_array_returns_empty_when_no_settings(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id]);
        $theme->load('settings');
        $this->assertEquals([], $theme->getSettingsArray());
    }

    #[Test]
    public function get_setting_returns_value(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id]);
        ThemeSetting::create(['theme_id' => $theme->id, 'key' => 'primary', 'value' => '#ff0000', 'type' => 'color', 'group' => 'colors']);

        $this->assertEquals('#ff0000', $theme->getSetting('primary'));
    }

    #[Test]
    public function get_setting_returns_default_when_not_found(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id]);
        $this->assertEquals('fallback', $theme->getSetting('nonexistent', 'fallback'));
    }

    #[Test]
    public function get_widgets_returns_widgets_config(): void
    {
        $widgets = [['type' => 'hero_banner'], ['type' => 'heading']];
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'widgets_config' => $widgets]);
        $this->assertEquals($widgets, $theme->getWidgets());
    }

    #[Test]
    public function get_widgets_returns_empty_array_when_null(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'widgets_config' => null]);
        $this->assertEquals([], $theme->getWidgets());
    }

    #[Test]
    public function get_widget_types_returns_type_array(): void
    {
        $widgets = [['type' => 'hero_banner'], ['type' => 'heading'], ['type' => 'text']];
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'widgets_config' => $widgets]);
        $this->assertEquals(['hero_banner', 'heading', 'text'], $theme->getWidgetTypes());
    }

    #[Test]
    public function has_widget_returns_true_for_supported(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'widgets_config' => [['type' => 'hero_banner']]]);
        $this->assertTrue($theme->hasWidget('hero_banner'));
    }

    #[Test]
    public function has_widget_returns_false_for_unsupported(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'widgets_config' => [['type' => 'hero_banner']]]);
        $this->assertFalse($theme->hasWidget('video'));
    }

    #[Test]
    public function has_settings_schema_returns_true_when_set(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'settings_schema' => ['colors' => []]]);
        $this->assertTrue($theme->hasSettingsSchema());
    }

    #[Test]
    public function has_settings_schema_returns_false_when_null(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'settings_schema' => null]);
        $this->assertFalse($theme->hasSettingsSchema());
    }

    #[Test]
    public function has_settings_schema_returns_false_when_empty(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'settings_schema' => []]);
        $this->assertFalse($theme->hasSettingsSchema());
    }

    #[Test]
    public function get_default_settings_from_schema_extracts_defaults(): void
    {
        $schema = [
            'colors' => [
                'primary' => ['type' => 'color', 'default' => '#111827'],
                'secondary' => ['type' => 'color', 'default' => '#6366f1'],
            ],
        ];
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'settings_schema' => $schema]);

        $defaults = $theme->getDefaultSettingsFromSchema();

        $this->assertEquals('#111827', $defaults['colors']['primary']);
        $this->assertEquals('#6366f1', $defaults['colors']['secondary']);
    }

    #[Test]
    public function get_default_settings_from_schema_returns_empty_when_no_schema(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id, 'settings_schema' => null]);
        $this->assertEquals([], $theme->getDefaultSettingsFromSchema());
    }

    #[Test]
    public function it_belongs_to_shop(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id]);
        $this->assertEquals($this->shop->id, $theme->shop->id);
    }

    #[Test]
    public function it_has_many_settings(): void
    {
        $theme = Theme::factory()->create(['shop_id' => $this->shop->id]);
        ThemeSetting::create(['theme_id' => $theme->id, 'key' => 'a', 'value' => '1', 'type' => 'text', 'group' => 'g']);
        ThemeSetting::create(['theme_id' => $theme->id, 'key' => 'b', 'value' => '2', 'type' => 'text', 'group' => 'g']);

        $this->assertCount(2, $theme->settings()->get());
    }
}
