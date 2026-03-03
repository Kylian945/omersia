<?php

declare(strict_types=1);

namespace Omersia\Ai\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Ai\Exceptions\AiGenerationException;
use Omersia\Ai\Models\AiProvider;
use Omersia\Ai\Services\ProductSeoGenerationService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class ProductSeoGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductSeoGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductSeoGenerationService::class);
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
            'target_field' => 'name',
            'name' => 'Test Product',
        ]);
    }

    #[Test]
    public function generate_throws_when_provider_has_no_api_key(): void
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
            'target_field' => 'name',
            'name' => 'Test Product',
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
            'config' => ['driver' => 'openai', 'api_key' => 'sk-real'],
        ]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Aucun provider IA actif avec clé API');

        $this->service->generate([
            'target_field' => 'name',
        ]);
    }

    // -------------------------------------------------------------------------
    // Guard: invalid target field (checked after provider lookup)
    // -------------------------------------------------------------------------

    #[Test]
    public function generate_throws_for_invalid_target_field_when_provider_exists(): void
    {
        AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-key'],
        ]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Champ cible IA invalide');

        $this->service->generate([
            'target_field' => 'invalid_field',
        ]);
    }

    #[Test]
    public function generate_throws_for_null_target_field(): void
    {
        AiProvider::create([
            'code' => 'openai',
            'name' => 'OpenAI',
            'is_enabled' => true,
            'is_default' => false,
            'config' => ['driver' => 'openai', 'api_key' => 'sk-key'],
        ]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Champ cible IA invalide');

        $this->service->generate([
            'target_field' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // normalizeTargetField — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_target_field_accepts_valid_fields(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTargetField');
        $method->setAccessible(true);

        $this->assertEquals('name', $method->invoke($this->service, 'name'));
        $this->assertEquals('short_description', $method->invoke($this->service, 'short_description'));
        $this->assertEquals('description', $method->invoke($this->service, 'description'));
        $this->assertEquals('meta_title', $method->invoke($this->service, 'meta_title'));
        $this->assertEquals('meta_description', $method->invoke($this->service, 'meta_description'));
    }

    #[Test]
    public function normalize_target_field_rejects_invalid_fields(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTargetField');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->service, 'slug'));
        $this->assertNull($method->invoke($this->service, 'price'));
        $this->assertNull($method->invoke($this->service, ''));
        $this->assertNull($method->invoke($this->service, null));
        $this->assertNull($method->invoke($this->service, 42));
    }

    #[Test]
    public function normalize_target_field_trims_whitespace(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTargetField');
        $method->setAccessible(true);

        $this->assertEquals('name', $method->invoke($this->service, '  name  '));
    }

    // -------------------------------------------------------------------------
    // normalizeCategories — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_categories_filters_non_string_elements(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategories');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['Electronics', 42, null, true, 'Laptops']);

        $this->assertCount(2, $result);
        $this->assertContains('Electronics', $result);
        $this->assertContains('Laptops', $result);
    }

    #[Test]
    public function normalize_categories_filters_empty_strings(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategories');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['', '  ', 'Electronics']);

        $this->assertCount(1, $result);
        $this->assertContains('Electronics', $result);
    }

    #[Test]
    public function normalize_categories_deduplicates(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategories');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['Electronics', 'Electronics', 'Laptops']);

        $this->assertCount(2, $result);
    }

    #[Test]
    public function normalize_categories_returns_empty_array_for_non_array(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategories');
        $method->setAccessible(true);

        $this->assertEmpty($method->invoke($this->service, null));
        $this->assertEmpty($method->invoke($this->service, 'Electronics'));
        $this->assertEmpty($method->invoke($this->service, 42));
    }

    #[Test]
    public function normalize_categories_truncates_long_names_to_120_chars(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategories');
        $method->setAccessible(true);

        $longName = str_repeat('a', 150);
        $result = $method->invoke($this->service, [$longName]);

        $this->assertCount(1, $result);
        $this->assertEquals(120, mb_strlen($result[0]));
    }

    #[Test]
    public function normalize_categories_trims_whitespace(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategories');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['  Electronics  ']);

        $this->assertContains('Electronics', $result);
    }

    // -------------------------------------------------------------------------
    // normalizeTextLine — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_text_line_strips_newlines(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, "Product\nName\rHere", 255);

        $this->assertEquals('Product Name Here', $result);
    }

    #[Test]
    public function normalize_text_line_collapses_spaces(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Hello   World', 255);

        $this->assertEquals('Hello World', $result);
    }

    #[Test]
    public function normalize_text_line_respects_max_length(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'MacBook Pro 16 inch laptop', 10);

        $this->assertEquals('MacBook Pr', $result);
    }

    #[Test]
    public function normalize_text_line_returns_empty_string_for_null(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextLine');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->service, null, 255));
    }

    // -------------------------------------------------------------------------
    // normalizeTextBlock — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_text_block_normalizes_crlf_to_lf(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, "Line1\r\nLine2", 5000);

        $this->assertEquals("Line1\nLine2", $result);
    }

    #[Test]
    public function normalize_text_block_respects_max_length(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeTextBlock');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'This is a long description text here', 10);

        $this->assertEquals('This is a ', $result);
    }

    // -------------------------------------------------------------------------
    // normalizeNullableString — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function normalize_nullable_string_returns_null_for_non_string_input(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeNullableString');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->service, null));
        $this->assertNull($method->invoke($this->service, 42));
        $this->assertNull($method->invoke($this->service, true));
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
    public function normalize_nullable_string_returns_trimmed_value(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeNullableString');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '  MacBook Pro  ');

        $this->assertEquals('MacBook Pro', $result);
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

        $result = $method->invoke($this->service, '{"meta_title": "Buy MacBook Pro | Best Price"}');

        $this->assertIsArray($result);
        $this->assertEquals('Buy MacBook Pro | Best Price', $result['meta_title']);
    }

    #[Test]
    public function decode_json_response_strips_markdown_fences(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('decodeJsonResponse');
        $method->setAccessible(true);

        $input = "```json\n{\"description\": \"A great laptop\"}\n```";
        $result = $method->invoke($this->service, $input);

        $this->assertEquals('A great laptop', $result['description']);
    }

    #[Test]
    public function decode_json_response_throws_for_unparseable_content(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('decodeJsonResponse');
        $method->setAccessible(true);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Impossible de parser la réponse IA en JSON valide');

        $method->invoke($this->service, 'Just plain text with no JSON');
    }

    // -------------------------------------------------------------------------
    // extractTextResponse — via private method reflection
    // -------------------------------------------------------------------------

    #[Test]
    public function extract_text_response_handles_plain_string(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTextResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '  {"name":"Test"}  ');

        $this->assertEquals('{"name":"Test"}', $result);
    }

    #[Test]
    public function extract_text_response_reads_text_property_on_object(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTextResponse');
        $method->setAccessible(true);

        $response = new \stdClass;
        $response->text = '{"meta_description": "Great product"}';

        $result = $method->invoke($this->service, $response);

        $this->assertEquals('{"meta_description": "Great product"}', $result);
    }

    #[Test]
    public function extract_text_response_throws_for_unhandled_type(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('extractTextResponse');
        $method->setAccessible(true);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Réponse IA invalide');

        $method->invoke($this->service, 123);
    }
}
