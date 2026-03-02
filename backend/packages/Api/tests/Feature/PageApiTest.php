<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\WithApiKey;

class PageApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_lists_active_pages(): void
    {
        // Arrange: 2 pages actives + 1 inactive
        $page1 = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create([
            'page_id' => $page1->id,
            'locale' => 'fr',
            'title' => 'Page Une',
            'slug' => 'page-une',
        ]);

        $page2 = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create([
            'page_id' => $page2->id,
            'locale' => 'fr',
            'title' => 'Page Deux',
            'slug' => 'page-deux',
        ]);

        $inactive = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => false]);
        PageTranslation::factory()->create([
            'page_id' => $inactive->id,
            'locale' => 'fr',
            'title' => 'Page Inactive',
            'slug' => 'page-inactive',
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    #[Test]
    public function it_shows_page_by_slug(): void
    {
        // Arrange
        $page = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => true,
            'type' => 'page',
            'is_home' => false,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Ma Page',
            'slug' => 'ma-page',
            'meta_title' => 'Meta Ma Page',
            'meta_description' => 'Description meta',
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/ma-page?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $page->id,
            'slug' => 'ma-page',
            'title' => 'Ma Page',
        ]);
    }

    #[Test]
    public function it_returns_404_for_unknown_slug(): void
    {
        // Act
        $response = $this->getJson('/api/v1/pages/slug-inexistant?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_404_for_inactive_page(): void
    {
        // Arrange: page inactive
        $page = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => false,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page Inactive',
            'slug' => 'page-inactive-slug',
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/page-inactive-slug?locale=fr', $this->apiHeaders());

        // Assert: 404 car la page est inactive
        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_translations_in_response(): void
    {
        // Arrange
        $page = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => true,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Titre Traduit',
            'slug' => 'titre-traduit',
            'meta_title' => 'SEO Titre',
            'meta_description' => 'SEO Description',
        ]);

        // Act
        $response = $this->getJson('/api/v1/pages/titre-traduit?locale=fr', $this->apiHeaders());

        // Assert: la réponse inclut les champs de translation
        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'slug',
            'title',
            'meta_title',
            'meta_description',
            'layout',
        ]);
        $response->assertJsonFragment([
            'title' => 'Titre Traduit',
            'slug' => 'titre-traduit',
            'meta_title' => 'SEO Titre',
            'meta_description' => 'SEO Description',
        ]);
    }
}
