<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageTranslationModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $translation = new PageTranslation;
        $this->assertEquals('cms_page_translations', $translation->getTable());
    }

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $fillable = (new PageTranslation)->getFillable();
        $expected = ['page_id', 'locale', 'title', 'slug', 'content', 'content_json', 'meta_title', 'meta_description', 'noindex'];
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    #[Test]
    public function it_casts_noindex_to_boolean(): void
    {
        $page = Page::factory()->create();
        $translation = PageTranslation::factory()->create(['page_id' => $page->id, 'noindex' => 1]);
        $this->assertIsBool($translation->noindex);
        $this->assertTrue($translation->noindex);
    }

    #[Test]
    public function it_casts_noindex_false_to_boolean(): void
    {
        $page = Page::factory()->create();
        $translation = PageTranslation::factory()->create(['page_id' => $page->id, 'noindex' => 0]);
        $this->assertFalse($translation->noindex);
    }

    #[Test]
    public function it_casts_content_json_to_array(): void
    {
        $page = Page::factory()->create();
        $jsonData = ['sections' => [['id' => 'section-1', 'columns' => []]]];
        $translation = PageTranslation::factory()->create(['page_id' => $page->id, 'content_json' => $jsonData]);

        $fresh = $translation->fresh();
        $this->assertIsArray($fresh->content_json);
        $this->assertEquals($jsonData, $fresh->content_json);
    }

    #[Test]
    public function it_stores_null_content_json(): void
    {
        $page = Page::factory()->create();
        $translation = PageTranslation::factory()->create(['page_id' => $page->id, 'content_json' => null]);
        $this->assertNull($translation->fresh()->content_json);
    }

    #[Test]
    public function it_belongs_to_page(): void
    {
        $page = Page::factory()->create();
        $translation = PageTranslation::factory()->create(['page_id' => $page->id]);
        $this->assertInstanceOf(BelongsTo::class, $translation->page());
        $this->assertEquals($page->id, $translation->page->id);
    }

    #[Test]
    public function factory_french_state(): void
    {
        $translation = PageTranslation::factory()->french()->create();
        $this->assertEquals('fr', $translation->locale);
    }

    #[Test]
    public function factory_english_state(): void
    {
        $translation = PageTranslation::factory()->english()->create();
        $this->assertEquals('en', $translation->locale);
    }

    #[Test]
    public function factory_with_content_json_state(): void
    {
        $translation = PageTranslation::factory()->withContentJson()->create();
        $this->assertIsArray($translation->content_json);
        $this->assertArrayHasKey('sections', $translation->content_json);
    }

    #[Test]
    public function it_stores_meta_fields(): void
    {
        $page = Page::factory()->create();
        $translation = PageTranslation::factory()->create([
            'page_id' => $page->id,
            'meta_title' => 'SEO Title',
            'meta_description' => 'SEO Desc',
        ]);
        $this->assertEquals('SEO Title', $translation->meta_title);
        $this->assertEquals('SEO Desc', $translation->meta_description);
    }
}
