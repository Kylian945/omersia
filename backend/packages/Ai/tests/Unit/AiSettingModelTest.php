<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Ai\Models\AiSetting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiSettingModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    #[Test]
    public function it_defines_global_scope_constant(): void
    {
        $this->assertEquals('global', AiSetting::GLOBAL_SCOPE);
    }

    #[Test]
    public function it_defines_usage_constants(): void
    {
        $this->assertEquals('all', AiSetting::USAGE_ALL);
        $this->assertEquals('category_content', AiSetting::USAGE_CATEGORY_CONTENT);
        $this->assertEquals('category_seo', AiSetting::USAGE_CATEGORY_SEO);
        $this->assertEquals('page_content', AiSetting::USAGE_PAGE_CONTENT);
        $this->assertEquals('page_seo', AiSetting::USAGE_PAGE_SEO);
        $this->assertEquals('product_content', AiSetting::USAGE_PRODUCT_CONTENT);
        $this->assertEquals('product_seo', AiSetting::USAGE_PRODUCT_SEO);
        $this->assertEquals('assistant', AiSetting::USAGE_ASSISTANT);
    }

    // -------------------------------------------------------------------------
    // Fillable & Casts
    // -------------------------------------------------------------------------

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $setting = new AiSetting;
        $fillable = $setting->getFillable();

        $this->assertContains('scope', $fillable);
        $this->assertContains('usage_scopes', $fillable);
        $this->assertContains('business_context', $fillable);
        $this->assertContains('seo_objectives', $fillable);
        $this->assertContains('forbidden_terms', $fillable);
        $this->assertContains('writing_tone', $fillable);
        $this->assertContains('content_locale', $fillable);
        $this->assertContains('title_max_length', $fillable);
        $this->assertContains('meta_description_max_length', $fillable);
        $this->assertContains('additional_instructions', $fillable);
    }

    #[Test]
    public function it_casts_usage_scopes_to_array(): void
    {
        $setting = AiSetting::create([
            'scope' => 'test',
            'usage_scopes' => ['all', 'product_content'],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        $this->assertIsArray($setting->fresh()->usage_scopes);
    }

    #[Test]
    public function it_casts_title_max_length_to_integer(): void
    {
        $setting = AiSetting::create([
            'scope' => 'cast_test',
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => '80',
            'meta_description_max_length' => 160,
        ]);

        $this->assertIsInt($setting->fresh()->title_max_length);
        $this->assertEquals(80, $setting->fresh()->title_max_length);
    }

    #[Test]
    public function it_casts_meta_description_max_length_to_integer(): void
    {
        $setting = AiSetting::create([
            'scope' => 'cast_test2',
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => '200',
        ]);

        $this->assertIsInt($setting->fresh()->meta_description_max_length);
        $this->assertEquals(200, $setting->fresh()->meta_description_max_length);
    }

    // -------------------------------------------------------------------------
    // defaultValues()
    // -------------------------------------------------------------------------

    #[Test]
    public function default_values_returns_expected_structure(): void
    {
        $defaults = AiSetting::defaultValues();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('usage_scopes', $defaults);
        $this->assertArrayHasKey('business_context', $defaults);
        $this->assertArrayHasKey('seo_objectives', $defaults);
        $this->assertArrayHasKey('forbidden_terms', $defaults);
        $this->assertArrayHasKey('writing_tone', $defaults);
        $this->assertArrayHasKey('content_locale', $defaults);
        $this->assertArrayHasKey('title_max_length', $defaults);
        $this->assertArrayHasKey('meta_description_max_length', $defaults);
        $this->assertArrayHasKey('additional_instructions', $defaults);
    }

    #[Test]
    public function default_values_has_correct_defaults(): void
    {
        $defaults = AiSetting::defaultValues();

        $this->assertEquals([AiSetting::USAGE_ALL], $defaults['usage_scopes']);
        $this->assertNull($defaults['business_context']);
        $this->assertNull($defaults['seo_objectives']);
        $this->assertNull($defaults['forbidden_terms']);
        $this->assertEquals('professionnel', $defaults['writing_tone']);
        $this->assertEquals('fr', $defaults['content_locale']);
        $this->assertEquals(70, $defaults['title_max_length']);
        $this->assertEquals(160, $defaults['meta_description_max_length']);
        $this->assertNull($defaults['additional_instructions']);
    }

    // -------------------------------------------------------------------------
    // getUsageOptions()
    // -------------------------------------------------------------------------

    #[Test]
    public function get_usage_options_returns_all_usage_types(): void
    {
        $options = AiSetting::getUsageOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey(AiSetting::USAGE_ALL, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_CATEGORY_CONTENT, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_CATEGORY_SEO, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_PAGE_CONTENT, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_PAGE_SEO, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_PRODUCT_CONTENT, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_PRODUCT_SEO, $options);
        $this->assertArrayHasKey(AiSetting::USAGE_ASSISTANT, $options);
    }

    #[Test]
    public function get_usage_options_values_are_strings(): void
    {
        $options = AiSetting::getUsageOptions();

        foreach ($options as $key => $label) {
            $this->assertIsString($key);
            $this->assertIsString($label);
        }
    }

    // -------------------------------------------------------------------------
    // normalizeUsageScopesInput()
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_usage_scopes_returns_empty_array_for_null(): void
    {
        $result = AiSetting::normalizeUsageScopesInput(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function normalize_usage_scopes_returns_empty_array_for_non_array(): void
    {
        $this->assertEmpty(AiSetting::normalizeUsageScopesInput('all'));
        $this->assertEmpty(AiSetting::normalizeUsageScopesInput('*'));
        $this->assertEmpty(AiSetting::normalizeUsageScopesInput(42));
        $this->assertEmpty(AiSetting::normalizeUsageScopesInput(true));
    }

    #[Test]
    public function normalize_usage_scopes_filters_invalid_values(): void
    {
        $result = AiSetting::normalizeUsageScopesInput(['invalid_scope', 'another_bad_one']);

        $this->assertEmpty($result);
    }

    #[Test]
    public function normalize_usage_scopes_filters_non_string_elements(): void
    {
        $result = AiSetting::normalizeUsageScopesInput([42, null, true, 'all']);

        $this->assertCount(1, $result);
        $this->assertContains('all', $result);
    }

    #[Test]
    public function normalize_usage_scopes_filters_empty_strings(): void
    {
        $result = AiSetting::normalizeUsageScopesInput(['', '  ', 'all']);

        $this->assertCount(1, $result);
        $this->assertContains('all', $result);
    }

    #[Test]
    public function normalize_usage_scopes_trims_whitespace(): void
    {
        $result = AiSetting::normalizeUsageScopesInput(['  all  ', ' product_content ']);

        $this->assertContains('all', $result);
        $this->assertContains('product_content', $result);
    }

    #[Test]
    public function normalize_usage_scopes_deduplicates_values(): void
    {
        $result = AiSetting::normalizeUsageScopesInput(['all', 'all', 'product_content', 'product_content']);

        $this->assertCount(2, $result);
        $this->assertContains('all', $result);
        $this->assertContains('product_content', $result);
    }

    #[Test]
    public function normalize_usage_scopes_accepts_all_valid_usage_types(): void
    {
        $validScopes = [
            AiSetting::USAGE_ALL,
            AiSetting::USAGE_CATEGORY_CONTENT,
            AiSetting::USAGE_CATEGORY_SEO,
            AiSetting::USAGE_PAGE_CONTENT,
            AiSetting::USAGE_PAGE_SEO,
            AiSetting::USAGE_PRODUCT_CONTENT,
            AiSetting::USAGE_PRODUCT_SEO,
            AiSetting::USAGE_ASSISTANT,
        ];

        $result = AiSetting::normalizeUsageScopesInput($validScopes);

        $this->assertCount(count($validScopes), $result);
        foreach ($validScopes as $scope) {
            $this->assertContains($scope, $result);
        }
    }

    #[Test]
    public function normalize_usage_scopes_returns_empty_array_for_empty_array(): void
    {
        $result = AiSetting::normalizeUsageScopesInput([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // appliesToUsage()
    // -------------------------------------------------------------------------

    #[Test]
    public function global_scope_applies_to_any_usage(): void
    {
        $setting = AiSetting::create([
            'scope' => AiSetting::GLOBAL_SCOPE,
            'usage_scopes' => [AiSetting::USAGE_PRODUCT_SEO],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_PRODUCT_CONTENT));
        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_ASSISTANT));
        $this->assertTrue($setting->appliesToUsage('any_usage'));
    }

    #[Test]
    public function it_applies_when_usage_all_is_in_scopes(): void
    {
        $setting = AiSetting::create([
            'scope' => 'context_all',
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_PRODUCT_CONTENT));
        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_CATEGORY_SEO));
        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_ASSISTANT));
    }

    #[Test]
    public function it_applies_when_specific_usage_matches(): void
    {
        $setting = AiSetting::create([
            'scope' => 'product_context',
            'usage_scopes' => [AiSetting::USAGE_PRODUCT_CONTENT, AiSetting::USAGE_PRODUCT_SEO],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_PRODUCT_CONTENT));
        $this->assertTrue($setting->appliesToUsage(AiSetting::USAGE_PRODUCT_SEO));
        $this->assertFalse($setting->appliesToUsage(AiSetting::USAGE_CATEGORY_CONTENT));
        $this->assertFalse($setting->appliesToUsage(AiSetting::USAGE_ASSISTANT));
    }

    #[Test]
    public function it_does_not_apply_when_usage_scopes_is_empty(): void
    {
        $setting = AiSetting::create([
            'scope' => 'empty_scopes',
            'usage_scopes' => null,
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        $this->assertFalse($setting->appliesToUsage(AiSetting::USAGE_PRODUCT_CONTENT));
        $this->assertFalse($setting->appliesToUsage(AiSetting::USAGE_ALL));
    }

    // -------------------------------------------------------------------------
    // toContextValues()
    // -------------------------------------------------------------------------

    #[Test]
    public function to_context_values_returns_all_expected_keys(): void
    {
        $setting = AiSetting::create([
            'scope' => 'ctx_test',
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'business_context' => 'E-commerce fashion',
            'seo_objectives' => 'Drive organic traffic',
            'forbidden_terms' => 'cheap, discount',
            'writing_tone' => 'elegant',
            'content_locale' => 'en',
            'title_max_length' => 65,
            'meta_description_max_length' => 155,
            'additional_instructions' => 'Use bullet points.',
        ]);

        $values = $setting->toContextValues();

        $this->assertArrayHasKey('usage_scopes', $values);
        $this->assertArrayHasKey('business_context', $values);
        $this->assertArrayHasKey('seo_objectives', $values);
        $this->assertArrayHasKey('forbidden_terms', $values);
        $this->assertArrayHasKey('writing_tone', $values);
        $this->assertArrayHasKey('content_locale', $values);
        $this->assertArrayHasKey('title_max_length', $values);
        $this->assertArrayHasKey('meta_description_max_length', $values);
        $this->assertArrayHasKey('additional_instructions', $values);
    }

    #[Test]
    public function to_context_values_returns_correct_values(): void
    {
        $setting = AiSetting::create([
            'scope' => 'ctx_values',
            'usage_scopes' => [AiSetting::USAGE_PRODUCT_CONTENT],
            'business_context' => 'Tech e-shop',
            'seo_objectives' => null,
            'forbidden_terms' => null,
            'writing_tone' => 'dynamique',
            'content_locale' => 'fr',
            'title_max_length' => 60,
            'meta_description_max_length' => 150,
            'additional_instructions' => null,
        ]);

        $values = $setting->toContextValues();

        $this->assertEquals([AiSetting::USAGE_PRODUCT_CONTENT], $values['usage_scopes']);
        $this->assertEquals('Tech e-shop', $values['business_context']);
        $this->assertNull($values['seo_objectives']);
        $this->assertEquals('dynamique', $values['writing_tone']);
        $this->assertEquals('fr', $values['content_locale']);
        $this->assertIsInt($values['title_max_length']);
        $this->assertEquals(60, $values['title_max_length']);
        $this->assertIsInt($values['meta_description_max_length']);
        $this->assertEquals(150, $values['meta_description_max_length']);
    }

    // -------------------------------------------------------------------------
    // resolveForUsage()
    // -------------------------------------------------------------------------

    #[Test]
    public function resolve_for_usage_creates_global_setting_if_missing(): void
    {
        $this->assertDatabaseCount('ai_settings', 0);

        AiSetting::resolveForUsage(AiSetting::USAGE_PRODUCT_CONTENT);

        $this->assertDatabaseHas('ai_settings', ['scope' => AiSetting::GLOBAL_SCOPE]);
    }

    #[Test]
    public function resolve_for_usage_returns_array_with_default_keys(): void
    {
        $resolved = AiSetting::resolveForUsage(AiSetting::USAGE_PRODUCT_CONTENT);

        $this->assertIsArray($resolved);
        $this->assertArrayHasKey('writing_tone', $resolved);
        $this->assertArrayHasKey('content_locale', $resolved);
        $this->assertArrayHasKey('title_max_length', $resolved);
        $this->assertArrayHasKey('meta_description_max_length', $resolved);
    }

    #[Test]
    public function resolve_for_usage_merges_global_setting_values(): void
    {
        AiSetting::create([
            'scope' => AiSetting::GLOBAL_SCOPE,
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'business_context' => 'Global fashion store',
            'writing_tone' => 'luxueux',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        $resolved = AiSetting::resolveForUsage(AiSetting::USAGE_PRODUCT_SEO);

        $this->assertEquals('Global fashion store', $resolved['business_context']);
        $this->assertEquals('luxueux', $resolved['writing_tone']);
    }

    #[Test]
    public function resolve_for_usage_applies_matching_context_over_global(): void
    {
        AiSetting::create([
            'scope' => AiSetting::GLOBAL_SCOPE,
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        AiSetting::create([
            'scope' => 'product_specific',
            'usage_scopes' => [AiSetting::USAGE_PRODUCT_CONTENT],
            'writing_tone' => 'dynamique',
            'content_locale' => 'en',
            'title_max_length' => 60,
            'meta_description_max_length' => 140,
        ]);

        $resolved = AiSetting::resolveForUsage(AiSetting::USAGE_PRODUCT_CONTENT);

        $this->assertEquals('dynamique', $resolved['writing_tone']);
        $this->assertEquals('en', $resolved['content_locale']);
    }

    #[Test]
    public function resolve_for_usage_does_not_apply_non_matching_context(): void
    {
        AiSetting::create([
            'scope' => AiSetting::GLOBAL_SCOPE,
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        AiSetting::create([
            'scope' => 'product_only',
            'usage_scopes' => [AiSetting::USAGE_PRODUCT_SEO],
            'writing_tone' => 'technique',
            'content_locale' => 'fr',
            'title_max_length' => 60,
            'meta_description_max_length' => 140,
        ]);

        $resolved = AiSetting::resolveForUsage(AiSetting::USAGE_CATEGORY_CONTENT);

        // The product_only context should NOT override the global tone for a category usage
        $this->assertEquals('professionnel', $resolved['writing_tone']);
    }

    #[Test]
    public function resolve_for_usage_skips_numeric_zero_for_length_fields(): void
    {
        AiSetting::create([
            'scope' => AiSetting::GLOBAL_SCOPE,
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 70,
            'meta_description_max_length' => 160,
        ]);

        AiSetting::create([
            'scope' => 'zero_length_ctx',
            'usage_scopes' => [AiSetting::USAGE_ALL],
            'writing_tone' => 'professionnel',
            'content_locale' => 'fr',
            'title_max_length' => 0,
            'meta_description_max_length' => 0,
        ]);

        $resolved = AiSetting::resolveForUsage(AiSetting::USAGE_PRODUCT_CONTENT);

        // zero length should NOT override the global's 70/160
        $this->assertEquals(70, $resolved['title_max_length']);
        $this->assertEquals(160, $resolved['meta_description_max_length']);
    }
}
