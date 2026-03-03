<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Omersia\CMS\Http\Requests\PageStoreRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageStoreRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new PageStoreRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function it_passes_with_minimal_valid_data(): void
    {
        $validator = $this->validate(['title' => 'Ma Page', 'slug' => 'ma-page']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_with_all_optional_fields(): void
    {
        $validator = $this->validate([
            'title' => 'Complete',
            'slug' => 'complete',
            'type' => 'legal',
            'is_active' => true,
            'is_home' => false,
            'content_json' => json_encode(['sections' => []]),
            'meta_title' => 'SEO',
            'meta_description' => 'Description',
            'noindex' => false,
        ]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_when_nullable_fields_are_null(): void
    {
        $validator = $this->validate([
            'title' => 'Page',
            'slug' => 'page',
            'type' => null,
            'is_active' => null,
            'content_json' => null,
            'meta_title' => null,
            'meta_description' => null,
            'noindex' => null,
        ]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_fails_when_title_is_missing(): void
    {
        $validator = $this->validate(['slug' => 'slug']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_when_slug_is_missing(): void
    {
        $validator = $this->validate(['title' => 'Title']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_when_both_required_fields_missing(): void
    {
        $validator = $this->validate([]);
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('slug', $errors);
    }

    #[Test]
    public function it_fails_when_title_exceeds_255(): void
    {
        $validator = $this->validate(['title' => str_repeat('a', 256), 'slug' => 'ok']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    #[Test]
    public function it_passes_when_title_is_exactly_255(): void
    {
        $validator = $this->validate(['title' => str_repeat('a', 255), 'slug' => 'ok']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_fails_when_slug_exceeds_255(): void
    {
        $validator = $this->validate(['title' => 'Ok', 'slug' => str_repeat('a', 256)]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_when_meta_title_exceeds_255(): void
    {
        $validator = $this->validate(['title' => 'Ok', 'slug' => 'ok', 'meta_title' => str_repeat('a', 256)]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('meta_title', $validator->errors()->toArray());
    }

    #[Test]
    public function it_returns_french_error_for_missing_title(): void
    {
        $validator = $this->validate(['slug' => 'ok']);
        $this->assertContains('Le titre de la page est obligatoire.', $validator->errors()->get('title'));
    }

    #[Test]
    public function it_returns_french_error_for_missing_slug(): void
    {
        $validator = $this->validate(['title' => 'Ok']);
        $this->assertContains('Le slug est obligatoire.', $validator->errors()->get('slug'));
    }

    #[Test]
    public function it_returns_french_error_for_title_too_long(): void
    {
        $validator = $this->validate(['title' => str_repeat('a', 256), 'slug' => 'ok']);
        $this->assertContains('Le titre ne peut pas dépasser 255 caractères.', $validator->errors()->get('title'));
    }
}
