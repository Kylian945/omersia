<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Repositories\PageRepository;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageRepositoryAdditionalTest extends TestCase
{
    use RefreshDatabase;

    private PageRepository $repository;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(PageRepository::class);
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function find_by_slug_returns_inactive_page_when_active_only_is_false(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => false]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'slug' => 'page-inactive']);

        $found = $this->repository->findBySlug('page-inactive', 'fr', activeOnly: false);

        $this->assertNotNull($found);
        $this->assertEquals($page->id, $found->id);
        $this->assertFalse($found->is_active);
    }

    #[Test]
    public function find_by_slug_returns_null_for_nonexistent_slug(): void
    {
        $this->assertNull($this->repository->findBySlug('nonexistent', 'fr'));
    }

    #[Test]
    public function find_by_slug_returns_null_for_wrong_locale(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'slug' => 'page-fr']);

        $this->assertNull($this->repository->findBySlug('page-fr', 'en'));
    }

    #[Test]
    public function get_active_by_locale_returns_empty_when_no_active_pages(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => false]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr']);

        $this->assertCount(0, $this->repository->getActiveByLocale('fr'));
    }

    #[Test]
    public function get_active_by_locale_only_loads_matching_locale_translations(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'title' => 'Titre FR']);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'en', 'title' => 'Title EN']);

        $result = $this->repository->getActiveByLocale('fr');

        $this->assertCount(1, $result);
        $this->assertCount(1, $result->first()->translations);
        $this->assertEquals('fr', $result->first()->translations->first()->locale);
    }

    #[Test]
    public function get_active_by_locale_excludes_drafts_when_published_only_is_true(): void
    {
        $draft = Page::factory()->draft()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $draft->id, 'locale' => 'fr', 'slug' => 'draft']);

        $published = Page::factory()->published()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $published->id, 'locale' => 'fr', 'slug' => 'published']);

        $result = $this->repository->getActiveByLocale('fr');

        $this->assertCount(1, $result);
        $this->assertEquals($published->id, $result->first()->id);
    }

    #[Test]
    public function find_by_slug_can_return_draft_when_published_filter_is_disabled(): void
    {
        $draft = Page::factory()->draft()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $draft->id, 'locale' => 'fr', 'slug' => 'draft-visible-admin']);

        $this->assertNull($this->repository->findBySlug('draft-visible-admin', 'fr'));

        $found = $this->repository->findBySlug(
            'draft-visible-admin',
            'fr',
            activeOnly: false,
            publishedOnly: false
        );

        $this->assertNotNull($found);
        $this->assertEquals($draft->id, $found->id);
    }

    #[Test]
    public function get_by_shop_id_orders_by_id_descending(): void
    {
        $first = Page::factory()->create(['shop_id' => $this->shop->id]);
        $second = Page::factory()->create(['shop_id' => $this->shop->id]);
        $third = Page::factory()->create(['shop_id' => $this->shop->id]);

        $result = $this->repository->getByShopId($this->shop->id, perPage: 10);
        $ids = collect($result->items())->pluck('id')->toArray();

        $this->assertEquals($third->id, $ids[0]);
        $this->assertEquals($second->id, $ids[1]);
        $this->assertEquals($first->id, $ids[2]);
    }

    #[Test]
    public function get_by_shop_id_eager_loads_translations(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr']);

        $result = $this->repository->getByShopId($this->shop->id);

        $this->assertTrue($result->first()->relationLoaded('translations'));
    }
}
