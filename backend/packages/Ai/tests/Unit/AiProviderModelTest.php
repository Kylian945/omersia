<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Ai\Models\AiProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiProviderModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Fillable & Casts
    // -------------------------------------------------------------------------

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $provider = new AiProvider;
        $fillable = $provider->getFillable();

        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('is_enabled', $fillable);
        $this->assertContains('is_default', $fillable);
        $this->assertContains('config', $fillable);
    }

    #[Test]
    public function it_casts_is_enabled_to_boolean(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => 1,
            'is_default' => 0,
        ]);

        $this->assertIsBool($provider->is_enabled);
        $this->assertTrue($provider->is_enabled);
    }

    #[Test]
    public function it_casts_is_default_to_boolean(): void
    {
        $provider = AiProvider::create([
            'code' => 'anthropic',
            'name' => 'Anthropic',
            'is_enabled' => true,
            'is_default' => 1,
        ]);

        $this->assertIsBool($provider->is_default);
        $this->assertTrue($provider->is_default);
    }

    #[Test]
    public function it_casts_config_as_encrypted_array(): void
    {
        $config = [
            'driver' => 'openai',
            'api_key' => 'sk-test-1234',
            'model' => 'gpt-4.1-mini',
        ];

        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => $config,
        ]);

        $fresh = $provider->fresh();
        $this->assertIsArray($fresh->config);
        $this->assertEquals('openai', $fresh->config['driver']);
        $this->assertEquals('sk-test-1234', $fresh->config['api_key']);
        $this->assertEquals('gpt-4.1-mini', $fresh->config['model']);
    }

    #[Test]
    public function it_can_create_provider_with_null_config(): void
    {
        $provider = AiProvider::create([
            'code' => 'groq',
            'name' => 'Groq',
            'is_enabled' => false,
            'is_default' => false,
            'config' => null,
        ]);

        $this->assertDatabaseHas('ai_providers', ['code' => 'groq']);
        $this->assertNull($provider->fresh()->config);
    }

    // -------------------------------------------------------------------------
    // getSupportedDrivers()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_all_supported_drivers(): void
    {
        $drivers = AiProvider::getSupportedDrivers();

        $this->assertIsArray($drivers);
        $this->assertContains('openai', $drivers);
        $this->assertContains('anthropic', $drivers);
        $this->assertContains('gemini', $drivers);
        $this->assertContains('groq', $drivers);
        $this->assertContains('xai', $drivers);
        $this->assertContains('deepseek', $drivers);
        $this->assertContains('mistral', $drivers);
        $this->assertContains('ollama', $drivers);
        $this->assertContains('openrouter', $drivers);
        $this->assertCount(9, $drivers);
    }

    // -------------------------------------------------------------------------
    // getSupportedProviders()
    // -------------------------------------------------------------------------

    #[Test]
    public function get_supported_providers_returns_array(): void
    {
        $providers = AiProvider::getSupportedProviders();

        $this->assertIsArray($providers);
    }

    // -------------------------------------------------------------------------
    // getSupportedCodes()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_codes_of_existing_providers(): void
    {
        AiProvider::create(['code' => 'openai', 'name' => 'OpenAI', 'is_enabled' => true, 'is_default' => false]);
        AiProvider::create(['code' => 'anthropic', 'name' => 'Anthropic', 'is_enabled' => true, 'is_default' => false]);

        $codes = AiProvider::getSupportedCodes();

        $this->assertIsArray($codes);
        $this->assertContains('openai', $codes);
        $this->assertContains('anthropic', $codes);
    }

    #[Test]
    public function get_supported_codes_returns_empty_array_when_no_providers(): void
    {
        $codes = AiProvider::getSupportedCodes();

        $this->assertIsArray($codes);
        $this->assertEmpty($codes);
    }

    // -------------------------------------------------------------------------
    // getModelSuggestions()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_model_suggestions_keyed_by_driver(): void
    {
        $suggestions = AiProvider::getModelSuggestions();

        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('openai', $suggestions);
        $this->assertArrayHasKey('anthropic', $suggestions);
        $this->assertArrayHasKey('gemini', $suggestions);
        $this->assertArrayHasKey('groq', $suggestions);
        $this->assertArrayHasKey('ollama', $suggestions);
    }

    #[Test]
    public function it_returns_expected_openai_model_suggestions(): void
    {
        $suggestions = AiProvider::getModelSuggestions();

        $this->assertContains('gpt-4.1-mini', $suggestions['openai']);
        $this->assertContains('gpt-4.1', $suggestions['openai']);
        $this->assertContains('gpt-4o-mini', $suggestions['openai']);
    }

    #[Test]
    public function it_returns_expected_anthropic_model_suggestions(): void
    {
        $suggestions = AiProvider::getModelSuggestions();

        $this->assertContains('claude-3-5-sonnet-latest', $suggestions['anthropic']);
        $this->assertContains('claude-3-7-sonnet-latest', $suggestions['anthropic']);
    }

    // -------------------------------------------------------------------------
    // getDefaultModelForDriver()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_first_model_suggestion_as_default_for_openai(): void
    {
        $default = AiProvider::getDefaultModelForDriver('openai');

        $this->assertEquals('gpt-4.1-mini', $default);
    }

    #[Test]
    public function it_returns_first_model_suggestion_as_default_for_anthropic(): void
    {
        $default = AiProvider::getDefaultModelForDriver('anthropic');

        $this->assertEquals('claude-3-5-sonnet-latest', $default);
    }

    #[Test]
    public function it_returns_first_model_suggestion_as_default_for_gemini(): void
    {
        $default = AiProvider::getDefaultModelForDriver('gemini');

        $this->assertEquals('gemini-2.0-flash', $default);
    }

    #[Test]
    public function it_returns_null_for_unknown_driver(): void
    {
        $default = AiProvider::getDefaultModelForDriver('nonexistent_driver');

        $this->assertNull($default);
    }

    #[Test]
    public function it_normalizes_driver_to_lowercase_when_getting_default_model(): void
    {
        $default = AiProvider::getDefaultModelForDriver('OpenAI');

        $this->assertEquals('gpt-4.1-mini', $default);
    }

    #[Test]
    public function it_trims_whitespace_from_driver_when_getting_default_model(): void
    {
        $default = AiProvider::getDefaultModelForDriver('  groq  ');

        $this->assertEquals('llama-3.3-70b-versatile', $default);
    }

    // -------------------------------------------------------------------------
    // ensureCoreProviders()
    // -------------------------------------------------------------------------

    #[Test]
    public function ensure_core_providers_normalizes_config_driver(): void
    {
        $provider = AiProvider::create([
            'code' => 'myopenai',
            'name' => 'My OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'OpenAI', 'api_key' => 'sk-abc'],
        ]);

        AiProvider::ensureCoreProviders();

        $fresh = $provider->fresh();
        $this->assertEquals('openai', $fresh->config['driver']);
    }

    #[Test]
    public function ensure_core_providers_falls_back_to_openai_for_invalid_driver(): void
    {
        $provider = AiProvider::create([
            'code' => 'custom',
            'name' => 'Custom',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'totally_invalid_driver', 'api_key' => 'key'],
        ]);

        AiProvider::ensureCoreProviders();

        $fresh = $provider->fresh();
        $this->assertEquals('openai', $fresh->config['driver']);
    }

    #[Test]
    public function ensure_core_providers_preserves_null_model_as_null(): void
    {
        $provider = AiProvider::create([
            'code' => 'testprovider',
            'name' => 'Test',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'anthropic', 'api_key' => 'key', 'model' => ''],
        ]);

        AiProvider::ensureCoreProviders();

        $fresh = $provider->fresh();
        $this->assertNull($fresh->config['model']);
    }

    #[Test]
    public function ensure_core_providers_preserves_non_empty_model(): void
    {
        $provider = AiProvider::create([
            'code' => 'testprovider',
            'name' => 'Test',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'key', 'model' => 'gpt-4.1'],
        ]);

        AiProvider::ensureCoreProviders();

        $fresh = $provider->fresh();
        $this->assertEquals('gpt-4.1', $fresh->config['model']);
    }

    #[Test]
    public function ensure_core_providers_removes_default_flag_from_disabled_providers(): void
    {
        AiProvider::create([
            'code' => 'disabled_default',
            'name' => 'Disabled Default',
            'is_enabled' => false,
            'is_default' => true,
            'config' => ['driver' => 'openai', 'api_key' => 'key'],
        ]);

        AiProvider::ensureCoreProviders();

        $this->assertDatabaseHas('ai_providers', [
            'code' => 'disabled_default',
            'is_enabled' => false,
            'is_default' => false,
        ]);
    }

    #[Test]
    public function ensure_core_providers_does_nothing_when_no_records_exist(): void
    {
        AiProvider::ensureCoreProviders();

        $this->assertDatabaseCount('ai_providers', 0);
    }

    // -------------------------------------------------------------------------
    // getConfigValue()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_config_value_by_key(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-secret', 'model' => 'gpt-4o-mini'],
        ]);

        $this->assertEquals('sk-secret', $provider->getConfigValue('api_key'));
        $this->assertEquals('openai', $provider->getConfigValue('driver'));
        $this->assertEquals('gpt-4o-mini', $provider->getConfigValue('model'));
    }

    #[Test]
    public function it_returns_default_when_config_key_is_missing(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai'],
        ]);

        $this->assertNull($provider->getConfigValue('api_key'));
        $this->assertEquals('fallback', $provider->getConfigValue('nonexistent', 'fallback'));
    }

    #[Test]
    public function it_returns_default_when_config_is_null(): void
    {
        $provider = AiProvider::create([
            'code' => 'nullconfig',
            'name' => 'Null Config',
            'is_enabled' => false,
            'is_default' => false,
            'config' => null,
        ]);

        $this->assertEquals('default_val', $provider->getConfigValue('anything', 'default_val'));
    }

    // -------------------------------------------------------------------------
    // hasApiKey()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_true_when_api_key_is_set(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-real-key'],
        ]);

        $this->assertTrue($provider->hasApiKey());
    }

    #[Test]
    public function it_returns_false_when_api_key_is_absent(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai'],
        ]);

        $this->assertFalse($provider->hasApiKey());
    }

    #[Test]
    public function it_returns_false_when_api_key_is_empty_string(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => '   '],
        ]);

        $this->assertFalse($provider->hasApiKey());
    }

    #[Test]
    public function it_returns_false_when_api_key_is_null(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => null],
        ]);

        $this->assertFalse($provider->hasApiKey());
    }

    #[Test]
    public function it_returns_false_when_config_is_null(): void
    {
        $provider = AiProvider::create([
            'code' => 'noconfig',
            'name' => 'No Config',
            'is_enabled' => false,
            'is_default' => false,
            'config' => null,
        ]);

        $this->assertFalse($provider->hasApiKey());
    }

    // -------------------------------------------------------------------------
    // getDriver()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_driver_from_config(): void
    {
        $provider = AiProvider::create([
            'code' => 'myanthropic',
            'name' => 'Anthropic',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'anthropic', 'api_key' => 'key'],
        ]);

        $this->assertEquals('anthropic', $provider->getDriver());
    }

    #[Test]
    public function it_normalizes_driver_to_lowercase(): void
    {
        $provider = AiProvider::create([
            'code' => 'myprovider',
            'name' => 'Provider',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'GEMINI', 'api_key' => 'key'],
        ]);

        $this->assertEquals('gemini', $provider->getDriver());
    }

    #[Test]
    public function it_falls_back_to_openai_when_driver_is_missing(): void
    {
        $provider = AiProvider::create([
            'code' => 'nodriverconfig',
            'name' => 'No Driver',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['api_key' => 'key'],
        ]);

        $this->assertEquals('openai', $provider->getDriver());
    }

    #[Test]
    public function it_falls_back_to_openai_when_driver_is_unsupported(): void
    {
        $provider = AiProvider::create([
            'code' => 'baddriver',
            'name' => 'Bad Driver',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'unknownprovider', 'api_key' => 'key'],
        ]);

        $this->assertEquals('openai', $provider->getDriver());
    }

    #[Test]
    public function it_falls_back_to_openai_when_driver_is_empty_string(): void
    {
        $provider = AiProvider::create([
            'code' => 'emptydriver',
            'name' => 'Empty Driver',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => '   ', 'api_key' => 'key'],
        ]);

        $this->assertEquals('openai', $provider->getDriver());
    }

    #[Test]
    public function it_falls_back_to_openai_when_config_is_null(): void
    {
        $provider = AiProvider::create([
            'code' => 'nullcfg',
            'name' => 'Null',
            'is_enabled' => false,
            'is_default' => false,
            'config' => null,
        ]);

        $this->assertEquals('openai', $provider->getDriver());
    }

    // -------------------------------------------------------------------------
    // getModel()
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_model_from_config(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'model' => 'gpt-4.1', 'api_key' => 'key'],
        ]);

        $this->assertEquals('gpt-4.1', $provider->getModel());
    }

    #[Test]
    public function it_returns_null_when_model_is_absent(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'key'],
        ]);

        $this->assertNull($provider->getModel());
    }

    #[Test]
    public function it_returns_null_when_model_is_empty_string(): void
    {
        $provider = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'model' => '   ', 'api_key' => 'key'],
        ]);

        $this->assertNull($provider->getModel());
    }

    #[Test]
    public function it_returns_null_when_config_is_null(): void
    {
        $provider = AiProvider::create([
            'code' => 'nomodel',
            'name' => 'No Model',
            'is_enabled' => false,
            'is_default' => false,
            'config' => null,
        ]);

        $this->assertNull($provider->getModel());
    }
}
