<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Services\PageService;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageService $service;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PageService::class);
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_creates_page_with_translation(): void
    {
        // Arrange
        $data = [
            'title' => 'Ma Nouvelle Page',
            'slug' => 'ma-nouvelle-page',
            'type' => 'page',
            'is_active' => true,
            'is_home' => false,
            'meta_title' => 'Meta titre',
            'meta_description' => 'Meta description',
        ];

        // Act
        $page = $this->service->create($data, $this->shop->id, 'fr');

        // Assert: Page créée en DB
        $this->assertDatabaseHas('cms_pages', [
            'shop_id' => $this->shop->id,
            'type' => 'page',
            'is_active' => true,
        ]);

        // Assert: Translation créée en DB
        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Ma Nouvelle Page',
            'slug' => 'ma-nouvelle-page',
            'meta_title' => 'Meta titre',
        ]);

        // Assert: Relations chargées
        $this->assertTrue($page->relationLoaded('translations'));
        $this->assertCount(1, $page->translations);
    }

    #[Test]
    public function it_updates_existing_page_translation(): void
    {
        // Arrange: page existante avec translation FR
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Ancien Titre',
            'slug' => 'ancien-slug',
        ]);

        $updateData = [
            'title' => 'Nouveau Titre',
            'slug' => 'nouveau-slug',
            'type' => 'page',
            'is_active' => true,
            'is_home' => false,
        ];

        // Act
        $updated = $this->service->update($page, $updateData, 'fr');

        // Assert: pas de doublon — toujours 1 translation FR
        $this->assertCount(1, $updated->translations);
        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Nouveau Titre',
            'slug' => 'nouveau-slug',
        ]);
        $this->assertDatabaseMissing('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Ancien Titre',
        ]);
    }

    #[Test]
    public function it_creates_translation_if_missing_on_update(): void
    {
        // Arrange: page sans translation EN
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page FR',
            'slug' => 'page-fr',
        ]);

        $updateData = [
            'title' => 'Page EN',
            'slug' => 'page-en',
            'type' => 'page',
            'is_active' => true,
            'is_home' => false,
        ];

        // Act: update en locale EN (inexistante)
        $updated = $this->service->update($page, $updateData, 'en');

        // Assert: une nouvelle translation EN créée, la FR est préservée
        $this->assertCount(2, $updated->translations);
        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Page EN',
            'slug' => 'page-en',
        ]);
        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page FR',
        ]);
    }

    #[Test]
    public function it_deletes_page(): void
    {
        // Arrange
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'slug' => 'page-a-supprimer',
        ]);

        $pageId = $page->id;

        // Act
        $result = $this->service->delete($page);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('cms_pages', ['id' => $pageId]);
    }

    #[Test]
    public function it_saves_builder_layout(): void
    {
        // Arrange
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page Existante',
            'slug' => 'page-existante',
            'content_json' => null,
        ]);

        $layout = [
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 100,
                            'widgets' => [
                                ['id' => 'w1', 'type' => 'heading', 'props' => ['text' => 'Hello']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Act
        $translation = $this->service->saveBuilderLayout($page, $layout, 'fr');

        // Assert
        $this->assertNotNull($translation->content_json);
        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'fr',
        ]);
        $fresh = PageTranslation::where('page_id', $page->id)->where('locale', 'fr')->first();
        $this->assertNotNull($fresh);
        $this->assertEquals($layout, $fresh->content_json);
    }

    #[Test]
    public function it_gets_public_page_returns_null_for_inactive(): void
    {
        // Arrange: page inactive
        $page = Page::factory()->create([
            'shop_id' => $this->shop->id,
            'is_active' => false,
        ]);
        PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'slug' => 'page-privee',
            'title' => 'Page Privée',
        ]);

        // Act
        $result = $this->service->getPublicPage('page-privee', 'fr');

        // Assert: null car la page est inactive
        $this->assertNull($result);
    }
}
