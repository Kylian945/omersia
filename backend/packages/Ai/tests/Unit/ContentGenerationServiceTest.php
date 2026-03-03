<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Models\AiProvider;
use Omersia\Ai\Models\AiSetting;
use Omersia\Ai\Services\ContentGenerationService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class ContentGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContentGenerationService::class);
    }

    // -------------------------------------------------------------------------
    // Guard: no providers
    // -------------------------------------------------------------------------

    #[Test]
    public function generate_throws_when_no_enabled_provider_exists(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Aucun provider IA actif avec clé API');

        $this->service->generate([
            'context' => 'category',
            'target_field' => 'name',
        ]);
    }

    #[Test]
    public function generate_throws_when_provider_exists_but_has_no_api_key(): void
    {
        AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai'],
        ]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Aucun provider IA actif avec clé API');

        $this->service->generate([
            'context' => 'category',
            'target_field' => 'name',
        ]);
    }

    #[Test]
    public function generate_throws_when_provider_is_disabled(): void
    {
        AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => false,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-real-key'],
        ]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Aucun provider IA actif avec clé API');

        $this->service->generate([
            'context' => 'category',
            'target_field' => 'name',
        ]);
    }

    // -------------------------------------------------------------------------
    // Guard: invalid context / target field
    // These are checked before provider lookup, so no provider needed.
    // -------------------------------------------------------------------------

    #[Test]
    public function generate_throws_for_invalid_context(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Contexte IA invalide');

        $this->service->generate([
            'context' => 'unknown_context',
            'target_field' => 'name',
        ]);
    }

    #[Test]
    public function generate_throws_for_null_context(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Contexte IA invalide');

        $this->service->generate([
            'context' => null,
            'target_field' => 'name',
        ]);
    }

    #[Test]
    public function generate_throws_for_missing_context_key(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Contexte IA invalide');

        $this->service->generate([
            'target_field' => 'name',
        ]);
    }

    #[Test]
    public function generate_throws_for_invalid_target_field(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Champ cible IA invalide');

        $this->service->generate([
            'context' => 'category',
            'target_field' => 'nonexistent_field',
        ]);
    }

    #[Test]
    public function generate_throws_for_null_target_field(): void
    {
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Champ cible IA invalide');

        $this->service->generate([
            'context' => 'category',
            'target_field' => null,
        ]);
    }

    #[Test]
    public function generate_throws_when_target_field_not_allowed_in_context(): void
    {
        // 'description' is only valid for 'category' context, not 'cms_page'
        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Champ cible IA invalide');

        $this->service->generate([
            'context' => 'cms_page',
            'target_field' => 'description',
        ]);
    }

    // -------------------------------------------------------------------------
    // resolveContext — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function resolve_context_accepts_valid_contexts(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveContext');
        $method->setAccessible(true);

        $this->assertEquals('category', $method->invoke($this->service, 'category'));
        $this->assertEquals('cms_page', $method->invoke($this->service, 'cms_page'));
        $this->assertEquals('ecommerce_page', $method->invoke($this->service, 'ecommerce_page'));
    }

    #[Test]
    public function resolve_context_rejects_unknown_context(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveContext');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->service, 'product'));
        $this->assertNull($method->invoke($this->service, ''));
        $this->assertNull($method->invoke($this->service, null));
        $this->assertNull($method->invoke($this->service, 42));
    }

    // -------------------------------------------------------------------------
    // resolveTargetField — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function resolve_target_field_accepts_valid_fields_for_category(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveTargetField');
        $method->setAccessible(true);

        $this->assertEquals('name', $method->invoke($this->service, 'category', 'name'));
        $this->assertEquals('description', $method->invoke($this->service, 'category', 'description'));
        $this->assertEquals('meta_title', $method->invoke($this->service, 'category', 'meta_title'));
        $this->assertEquals('meta_description', $method->invoke($this->service, 'category', 'meta_description'));
    }

    #[Test]
    public function resolve_target_field_accepts_valid_fields_for_cms_page(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveTargetField');
        $method->setAccessible(true);

        $this->assertEquals('title', $method->invoke($this->service, 'cms_page', 'title'));
        $this->assertEquals('meta_title', $method->invoke($this->service, 'cms_page', 'meta_title'));
        $this->assertEquals('meta_description', $method->invoke($this->service, 'cms_page', 'meta_description'));
    }

    #[Test]
    public function resolve_target_field_accepts_valid_fields_for_ecommerce_page(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveTargetField');
        $method->setAccessible(true);

        $this->assertEquals('title', $method->invoke($this->service, 'ecommerce_page', 'title'));
        $this->assertEquals('meta_title', $method->invoke($this->service, 'ecommerce_page', 'meta_title'));
        $this->assertEquals('meta_description', $method->invoke($this->service, 'ecommerce_page', 'meta_description'));
    }

    #[Test]
    public function resolve_target_field_rejects_field_not_in_context(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveTargetField');
        $method->setAccessible(true);

        // 'description' is not in cms_page context
        $this->assertNull($method->invoke($this->service, 'cms_page', 'description'));
        // 'name' is not in cms_page context
        $this->assertNull($method->invoke($this->service, 'cms_page', 'name'));
    }

    // -------------------------------------------------------------------------
    // resolveUsage — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function resolve_usage_returns_category_content_for_name(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveUsage');
        $method->setAccessible(true);

        $this->assertEquals(AiSetting::USAGE_CATEGORY_CONTENT, $method->invoke($this->service, 'category', 'name'));
        $this->assertEquals(AiSetting::USAGE_CATEGORY_CONTENT, $method->invoke($this->service, 'category', 'description'));
    }

    #[Test]
    public function resolve_usage_returns_category_seo_for_meta_fields(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveUsage');
        $method->setAccessible(true);

        $this->assertEquals(AiSetting::USAGE_CATEGORY_SEO, $method->invoke($this->service, 'category', 'meta_title'));
        $this->assertEquals(AiSetting::USAGE_CATEGORY_SEO, $method->invoke($this->service, 'category', 'meta_description'));
    }

    #[Test]
    public function resolve_usage_returns_page_seo_for_meta_fields_in_cms_page(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveUsage');
        $method->setAccessible(true);

        $this->assertEquals(AiSetting::USAGE_PAGE_SEO, $method->invoke($this->service, 'cms_page', 'meta_title'));
        $this->assertEquals(AiSetting::USAGE_PAGE_SEO, $method->invoke($this->service, 'cms_page', 'meta_description'));
        $this->assertEquals(AiSetting::USAGE_PAGE_SEO, $method->invoke($this->service, 'ecommerce_page', 'meta_title'));
    }

    #[Test]
    public function resolve_usage_returns_page_content_for_title_in_cms_page(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('resolveUsage');
        $method->setAccessible(true);

        $this->assertEquals(AiSetting::USAGE_PAGE_CONTENT, $method->invoke($this->service, 'cms_page', 'title'));
        $this->assertEquals(AiSetting::USAGE_PAGE_CONTENT, $method->invoke($this->service, 'ecommerce_page', 'title'));
    }

    // -------------------------------------------------------------------------
    // normalizeTextLine — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_text_line_trims_whitespace(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '  Hello World  ', 255);

        $this->assertEquals('Hello World', $result);
    }

    #[Test]
    public function normalize_text_line_replaces_newlines_with_spaces(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, "Hello\nWorld\rTest", 255);

        $this->assertEquals('Hello World Test', $result);
    }

    #[Test]
    public function normalize_text_line_collapses_multiple_spaces(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Hello    World   Test', 255);

        $this->assertEquals('Hello World Test', $result);
    }

    #[Test]
    public function normalize_text_line_truncates_to_max_length(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Hello World', 5);

        $this->assertEquals('Hello', $result);
    }

    #[Test]
    public function normalize_text_line_returns_empty_string_for_non_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->service, null, 255));
        $this->assertEquals('', $method->invoke($this->service, 42, 255));
        $this->assertEquals('', $method->invoke($this->service, [], 255));
    }

    // -------------------------------------------------------------------------
    // normalizeTextBlock — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_text_block_trims_leading_and_trailing_whitespace(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '  Hello World  ', 5000);

        $this->assertEquals('Hello World', $result);
    }

    #[Test]
    public function normalize_text_block_normalizes_carriage_returns(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, "Line1\r\nLine2\rLine3", 5000);

        $this->assertEquals("Line1\nLine2\nLine3", $result);
    }

    #[Test]
    public function normalize_text_block_preserves_legitimate_newlines(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $input = "Paragraph one.\n\nParagraph two.";
        $result = $method->invoke($this->service, $input, 5000);

        $this->assertEquals("Paragraph one.\n\nParagraph two.", $result);
    }

    #[Test]
    public function normalize_text_block_truncates_to_max_length(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Hello World', 5);

        $this->assertEquals('Hello', $result);
    }

    #[Test]
    public function normalize_text_block_returns_empty_string_for_non_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->service, null, 5000));
        $this->assertEquals('', $method->invoke($this->service, false, 5000));
    }

    // -------------------------------------------------------------------------
    // normalizeNullableString — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_nullable_string_returns_null_for_non_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeNullableString');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->service, null));
        $this->assertNull($method->invoke($this->service, 42));
        $this->assertNull($method->invoke($this->service, []));
    }

    #[Test]
    public function normalize_nullable_string_returns_null_for_empty_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeNullableString');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->service, ''));
        $this->assertNull($method->invoke($this->service, '   '));
    }

    #[Test]
    public function normalize_nullable_string_returns_trimmed_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeNullableString');
        $method->setAccessible(true);

        $this->assertEquals('Hello', $method->invoke($this->service, '  Hello  '));
    }

    #[Test]
    public function normalize_nullable_string_truncates_to_max_length(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeNullableString');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Hello World', 5);

        $this->assertEquals('Hello', $result);
    }

    // -------------------------------------------------------------------------
    // decodeJsonResponse — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function decode_json_response_parses_valid_json(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('decodeJsonResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '{"name": "MacBook Pro"}');

        $this->assertIsArray($result);
        $this->assertEquals('MacBook Pro', $result['name']);
    }

    #[Test]
    public function decode_json_response_strips_markdown_code_blocks(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('decodeJsonResponse');
        $method->setAccessible(true);

        $input = "```json\n{\"name\": \"Test\"}\n```";
        $result = $method->invoke($this->service, $input);

        $this->assertIsArray($result);
        $this->assertEquals('Test', $result['name']);
    }

    #[Test]
    public function decode_json_response_extracts_json_from_noisy_response(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('decodeJsonResponse');
        $method->setAccessible(true);

        // JSON embedded in text
        $input = 'Here is the answer: {"meta_title": "Great product"} That is all.';
        $result = $method->invoke($this->service, $input);

        $this->assertIsArray($result);
        $this->assertEquals('Great product', $result['meta_title']);
    }

    #[Test]
    public function decode_json_response_throws_for_invalid_json(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('decodeJsonResponse');
        $method->setAccessible(true);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Impossible de parser la réponse IA en JSON valide');

        $method->invoke($this->service, 'This is not JSON at all, no curly braces');
    }

    // -------------------------------------------------------------------------
    // extractTextResponse — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function extract_text_response_accepts_plain_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTextResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '  {"name": "Test"}  ');

        $this->assertEquals('{"name": "Test"}', $result);
    }

    #[Test]
    public function extract_text_response_reads_text_property_from_object(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTextResponse');
        $method->setAccessible(true);

        $object = new \stdClass;
        $object->text = '{"name": "Test from object"}';

        $result = $method->invoke($this->service, $object);

        $this->assertEquals('{"name": "Test from object"}', $result);
    }

    #[Test]
    public function extract_text_response_throws_for_invalid_response_type(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTextResponse');
        $method->setAccessible(true);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Réponse IA invalide');

        $method->invoke($this->service, 42);
    }

    // -------------------------------------------------------------------------
    // contextLabel — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function context_label_returns_correct_labels(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('contextLabel');
        $method->setAccessible(true);

        $this->assertEquals('Catégorie', $method->invoke($this->service, 'category'));
        $this->assertEquals('Page CMS', $method->invoke($this->service, 'cms_page'));
        $this->assertEquals('Page e-commerce', $method->invoke($this->service, 'ecommerce_page'));
        $this->assertEquals('Contenu', $method->invoke($this->service, 'unknown'));
    }

    // -------------------------------------------------------------------------
    // buildEntityPayload — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function build_entity_payload_for_category_context(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEntityPayload');
        $method->setAccessible(true);

        $input = [
            'name' => 'Electronics',
            'description' => 'All electronics',
            'meta_title' => 'Buy Electronics',
            'meta_description' => 'Shop electronics online',
            'slug' => 'electronics',
        ];

        $result = $method->invoke($this->service, $input, 'category');

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('meta_title', $result);
        $this->assertArrayHasKey('meta_description', $result);
        $this->assertArrayHasKey('slug', $result);
        $this->assertEquals('Electronics', $result['name']);
    }

    #[Test]
    public function build_entity_payload_for_cms_page_context(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEntityPayload');
        $method->setAccessible(true);

        $input = [
            'title' => 'About Us',
            'meta_title' => 'About Our Company',
            'slug' => 'about-us',
        ];

        $result = $method->invoke($this->service, $input, 'cms_page');

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('meta_title', $result);
        $this->assertArrayHasKey('slug', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayNotHasKey('name', $result);
        $this->assertArrayNotHasKey('description', $result);
    }

    #[Test]
    public function build_entity_payload_returns_empty_for_unknown_context(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEntityPayload');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['name' => 'Test'], 'unknown');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // orderByDefaultProvider — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function order_by_default_provider_puts_default_first(): void
    {
        $provider1 = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-1'],
        ]);

        $provider2 = AiProvider::create([
            'code' => 'anthropic',
            'name' => 'Anthropic',
            'is_enabled' => true,
            'is_default' => true,
            'config' => ['driver' => 'anthropic', 'api_key' => 'sk-2'],
        ]);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('orderByDefaultProvider');
        $method->setAccessible(true);

        $providers = collect([$provider1, $provider2]);
        $ordered = $method->invoke($this->service, $providers);

        $this->assertEquals('anthropic', $ordered->first()->code);
    }

    #[Test]
    public function order_by_default_provider_returns_original_order_when_no_default(): void
    {
        $provider1 = AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-1'],
        ]);

        $provider2 = AiProvider::create([
            'code' => 'groq',
            'name' => 'Groq',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'groq', 'api_key' => 'gsk-2'],
        ]);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('orderByDefaultProvider');
        $method->setAccessible(true);

        $providers = collect([$provider1, $provider2]);
        $ordered = $method->invoke($this->service, $providers);

        $this->assertEquals('openai', $ordered->first()->code);
    }
}
