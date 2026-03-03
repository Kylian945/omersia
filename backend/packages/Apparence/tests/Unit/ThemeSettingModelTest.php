<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\Theme;
use Omersia\Apparence\Models\ThemeSetting;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeSettingModelTest extends TestCase
{
    use RefreshDatabase;

    private Theme $theme;

    protected function setUp(): void
    {
        parent::setUp();
        $shop = Shop::factory()->create();
        $this->theme = Theme::factory()->create(['shop_id' => $shop->id]);
    }

    #[Test]
    public function get_decoded_value_returns_string_for_text_type(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'font', 'value' => 'Inter', 'type' => 'text', 'group' => 'typography']);
        $this->assertEquals('Inter', $setting->getDecodedValue());
    }

    #[Test]
    public function get_decoded_value_returns_color_string_for_color_type(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'primary', 'value' => '#ff0000', 'type' => 'color', 'group' => 'colors']);
        $this->assertEquals('#ff0000', $setting->getDecodedValue());
    }

    #[Test]
    public function get_decoded_value_returns_boolean_for_boolean_type(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'sticky', 'value' => '1', 'type' => 'boolean', 'group' => 'header']);
        $this->assertTrue($setting->getDecodedValue());
    }

    #[Test]
    public function get_decoded_value_returns_false_for_boolean_zero(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'sticky', 'value' => '0', 'type' => 'boolean', 'group' => 'header']);
        $this->assertFalse($setting->getDecodedValue());
    }

    #[Test]
    public function get_decoded_value_returns_float_for_number_type(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'size', 'value' => '16', 'type' => 'number', 'group' => 'typography']);
        $this->assertEquals(16.0, $setting->getDecodedValue());
    }

    #[Test]
    public function get_decoded_value_returns_zero_for_non_numeric_number_type(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'size', 'value' => 'abc', 'type' => 'number', 'group' => 'typography']);
        $this->assertEquals(0, $setting->getDecodedValue());
    }

    #[Test]
    public function get_decoded_value_returns_array_for_json_type(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'data', 'value' => '{"a":1}', 'type' => 'json', 'group' => 'general']);
        $this->assertEquals(['a' => 1], $setting->getDecodedValue());
    }

    #[Test]
    public function set_encoded_value_for_boolean(): void
    {
        $setting = new ThemeSetting(['theme_id' => $this->theme->id, 'key' => 'test', 'type' => 'boolean', 'group' => 'g']);
        $setting->setEncodedValue(true);
        $this->assertEquals('1', $setting->value);

        $setting->setEncodedValue(false);
        $this->assertEquals('0', $setting->value);
    }

    #[Test]
    public function set_encoded_value_for_json(): void
    {
        $setting = new ThemeSetting(['theme_id' => $this->theme->id, 'key' => 'data', 'type' => 'json', 'group' => 'g']);
        $setting->setEncodedValue(['a' => 1]);
        $this->assertEquals('{"a":1}', $setting->value);
    }

    #[Test]
    public function it_belongs_to_theme(): void
    {
        $setting = ThemeSetting::create(['theme_id' => $this->theme->id, 'key' => 'k', 'value' => 'v', 'type' => 'text', 'group' => 'g']);
        $this->assertEquals($this->theme->id, $setting->theme->id);
    }
}
