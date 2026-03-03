<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Omersia\CMS\Http\Requests\PageStoreRequest;
use Omersia\CMS\Http\Requests\PageUpdateRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new PageUpdateRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function it_passes_with_minimal_valid_data(): void
    {
        $validator = $this->validate(['title' => 'Updated', 'slug' => 'updated']);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_with_all_optional_fields(): void
    {
        $validator = $this->validate([
            'title' => 'Updated',
            'slug' => 'updated',
            'type' => 'page',
            'is_active' => true,
            'is_home' => false,
            'content_json' => json_encode(['sections' => []]),
            'meta_title' => 'SEO',
            'meta_description' => 'Desc',
            'noindex' => true,
        ]);
        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_fails_when_title_is_missing(): void
    {
        $validator = $this->validate(['slug' => 'ok']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_when_slug_is_missing(): void
    {
        $validator = $this->validate(['title' => 'Ok']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_when_title_exceeds_255(): void
    {
        $validator = $this->validate(['title' => str_repeat('z', 256), 'slug' => 'ok']);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_fails_when_slug_exceeds_255(): void
    {
        $validator = $this->validate(['title' => 'Ok', 'slug' => str_repeat('s', 256)]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_fails_when_meta_title_exceeds_255(): void
    {
        $validator = $this->validate(['title' => 'Ok', 'slug' => 'ok', 'meta_title' => str_repeat('m', 256)]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function it_returns_french_error_for_missing_title(): void
    {
        $validator = $this->validate(['slug' => 'ok']);
        $this->assertContains('Le titre de la page est obligatoire.', $validator->errors()->get('title'));
    }

    #[Test]
    public function it_returns_french_error_for_meta_title_too_long(): void
    {
        $validator = $this->validate(['title' => 'Ok', 'slug' => 'ok', 'meta_title' => str_repeat('m', 256)]);
        $this->assertContains('Le méta titre ne peut pas dépasser 255 caractères.', $validator->errors()->get('meta_title'));
    }

    #[Test]
    public function it_has_same_rules_as_store_request(): void
    {
        $this->assertEquals((new PageStoreRequest)->rules(), (new PageUpdateRequest)->rules());
    }
}
