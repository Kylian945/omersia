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

class PageRepositoryTest extends TestCase
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
    public function it_paginates_pages_by_shop_id(): void
    {
        // Arrange: créer 5 pages pour le shop courant et 2 pour un autre
        Page::factory()->count(5)->create(['shop_id' => $this->shop->id]);
        $otherShop = Shop::factory()->create();
        Page::factory()->count(2)->create(['shop_id' => $otherShop->id]);

        // Act
        $result = $this->repository->getByShopId($this->shop->id, perPage: 3);

        // Assert
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
        $this->assertEquals(1, $result->currentPage());
    }

    #[Test]
    public function it_gets_active_pages_by_locale(): void
    {
        // Arrange: créer pages actives avec translations FR et une inactive
        $activePage = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => true,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $activePage->id,
            'locale' => 'fr',
            'title' => 'Page Active',
            'slug' => 'page-active',
        ]);

        $inactivePage = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => false,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $inactivePage->id,
            'locale' => 'fr',
            'title' => 'Page Inactive',
            'slug' => 'page-inactive',
        ]);

        // Act
        $result = $this->repository->getActiveByLocale('fr');

        // Assert: seulement la page active est retournée
        $this->assertCount(1, $result);
        $this->assertEquals($activePage->id, $result->first()->id);
        $this->assertTrue($result->first()->relationLoaded('translations'));
    }

    #[Test]
    public function it_finds_page_by_slug_and_locale(): void
    {
        // Arrange
        $page = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => true,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'slug' => 'ma-page-test',
            'title' => 'Ma Page Test',
        ]);

        // Act
        $found = $this->repository->findBySlug('ma-page-test', 'fr');

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($page->id, $found->id);
        $this->assertTrue($found->relationLoaded('translations'));
        $this->assertEquals('fr', $found->translations->first()->locale);
    }

    #[Test]
    public function it_returns_null_for_inactive_page_when_active_only(): void
    {
        // Arrange: page inactive avec translation FR
        $page = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => false,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'slug' => 'page-inactive-slug',
            'title' => 'Page Inactive',
        ]);

        // Act: activeOnly=true (par défaut)
        $found = $this->repository->findBySlug('page-inactive-slug', 'fr', activeOnly: true);

        // Assert: null car la page est inactive
        $this->assertNull($found);
    }
}
