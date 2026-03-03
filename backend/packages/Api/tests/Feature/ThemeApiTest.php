<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Models\ThemeSetting;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\WithApiKey;

class ThemeApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
        $this->shop = Shop::factory()->create();
        Cache::flush();
    }

    #[Test]
    public function it_returns_theme_settings_with_expected_structure(): void
    {
        Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'vision',
            'component_path' => 'vision',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertOk();
        $response->assertJsonStructure([
            'settings',
            'settings_schema',
            'css_variables',
            'component_path',
            'theme_slug',
        ]);
    }

    #[Test]
    public function it_returns_component_path_from_active_theme(): void
    {
        Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'my-theme',
            'component_path' => 'my-theme-path',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertOk();
        $response->assertJsonPath('component_path', 'my-theme-path');
        $response->assertJsonPath('theme_slug', 'my-theme');
    }

    #[Test]
    public function it_returns_default_vision_when_no_active_theme(): void
    {
        Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'inactive',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertOk();
        $response->assertJsonPath('component_path', 'vision');
        $response->assertJsonPath('theme_slug', 'vision');
    }

    #[Test]
    public function it_returns_default_settings_when_no_theme(): void
    {
        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertOk();
        $settings = $response->json('settings');
        $this->assertIsArray($settings);
        $this->assertNotEmpty($settings);
    }

    #[Test]
    public function it_returns_css_variables_containing_root(): void
    {
        Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'vision',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $css = $response->json('css_variables');
        $this->assertIsString($css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--theme-', $css);
    }

    #[Test]
    public function it_returns_settings_schema_with_expected_groups(): void
    {
        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertOk();
        $schema = $response->json('settings_schema');
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('colors', $schema);
        $this->assertArrayHasKey('typography', $schema);
    }

    #[Test]
    public function it_returns_404_when_no_shop_exists(): void
    {
        $this->shop->delete();

        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertNotFound();
        $response->assertJson(['error' => 'Shop not configured']);
    }

    #[Test]
    public function it_requires_api_key(): void
    {
        $response = $this->getJson('/api/v1/theme/settings');
        $response->assertUnauthorized();
    }

    #[Test]
    public function it_reflects_custom_setting_value(): void
    {
        $theme = Theme::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'vision',
            'is_active' => true,
        ]);

        ThemeSetting::create([
            'theme_id' => $theme->id,
            'key' => 'primary',
            'value' => '#ff0000',
            'type' => 'color',
            'group' => 'colors',
        ]);

        $response = $this->getJson('/api/v1/theme/settings', $this->apiHeaders());

        $response->assertOk();
        $this->assertEquals('#ff0000', $response->json('settings.colors.primary'));
    }
}
