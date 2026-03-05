<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $page = new Page;
        $this->assertEquals('cms_pages', $page->getTable());
    }

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $page = new Page;
        $fillable = $page->getFillable();
        $this->assertContains('shop_id', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('published_at', $fillable);
        $this->assertContains('published_by', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('is_home', $fillable);
    }

    #[Test]
    public function it_casts_is_active_to_boolean(): void
    {
        $page = Page::factory()->create(['is_active' => 1]);
        $this->assertIsBool($page->is_active);
        $this->assertTrue($page->is_active);
    }

    #[Test]
    public function it_casts_is_active_false_to_boolean(): void
    {
        $page = Page::factory()->create(['is_active' => 0]);
        $this->assertIsBool($page->is_active);
        $this->assertFalse($page->is_active);
    }

    #[Test]
    public function it_casts_is_home_to_boolean(): void
    {
        $page = Page::factory()->create(['is_home' => 1]);
        $this->assertIsBool($page->is_home);
        $this->assertTrue($page->is_home);
    }

    #[Test]
    public function it_has_many_translations_relation(): void
    {
        $page = Page::factory()->create();
        $this->assertInstanceOf(HasMany::class, $page->translations());
    }

    #[Test]
    public function it_loads_multiple_translations(): void
    {
        $page = Page::factory()->create();
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr']);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'en']);
        $page->load('translations');
        $this->assertInstanceOf(Collection::class, $page->translations);
        $this->assertCount(2, $page->translations);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_translations(): void
    {
        $page = Page::factory()->create();
        $page->load('translations');
        $this->assertCount(0, $page->translations);
    }

    #[Test]
    public function it_returns_translation_for_given_locale(): void
    {
        $page = Page::factory()->create();
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'title' => 'Titre FR']);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'en', 'title' => 'Title EN']);
        $page->load('translations');

        $this->assertEquals('Titre FR', $page->translation('fr')->title);
        $this->assertEquals('Title EN', $page->translation('en')->title);
    }

    #[Test]
    public function it_returns_null_translation_for_unknown_locale(): void
    {
        $page = Page::factory()->create();
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr']);
        $page->load('translations');
        $this->assertNull($page->translation('de'));
    }

    #[Test]
    public function it_falls_back_to_app_locale_when_no_locale_provided(): void
    {
        app()->setLocale('fr');
        $page = Page::factory()->create();
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'title' => 'Page FR']);
        $page->load('translations');

        $result = $page->translation();
        $this->assertNotNull($result);
        $this->assertEquals('fr', $result->locale);
    }

    #[Test]
    public function factory_creates_page_with_default_type(): void
    {
        $page = Page::factory()->create();
        $this->assertEquals('page', $page->type);
    }

    #[Test]
    public function factory_active_state(): void
    {
        $page = Page::factory()->active()->create();
        $this->assertTrue($page->is_active);
    }

    #[Test]
    public function factory_inactive_state(): void
    {
        $page = Page::factory()->inactive()->create();
        $this->assertFalse($page->is_active);
    }

    #[Test]
    public function factory_home_page_state(): void
    {
        $page = Page::factory()->homePage()->create();
        $this->assertTrue($page->is_home);
    }

    #[Test]
    public function factory_legal_state(): void
    {
        $page = Page::factory()->legal()->create();
        $this->assertEquals('legal', $page->type);
        $this->assertTrue($page->is_active);
    }

    #[Test]
    public function factory_draft_state_sets_status_and_clears_publication_meta(): void
    {
        $page = Page::factory()->draft()->create();

        $this->assertEquals(Page::STATUS_DRAFT, $page->status);
        $this->assertNull($page->published_at);
        $this->assertNull($page->published_by);
    }

    #[Test]
    public function factory_published_state_sets_status_and_published_at(): void
    {
        $page = Page::factory()->published()->create();

        $this->assertEquals(Page::STATUS_PUBLISHED, $page->status);
        $this->assertNotNull($page->published_at);
    }

    #[Test]
    public function it_stores_shop_id(): void
    {
        $shop = Shop::factory()->create();
        $page = Page::factory()->create(['shop_id' => $shop->id]);
        $this->assertEquals($shop->id, $page->shop_id);
    }
}
