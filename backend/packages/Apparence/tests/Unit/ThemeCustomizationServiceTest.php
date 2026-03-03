<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Services\ThemeCustomizationService;
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
}
