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

class PageServiceAdditionalTest extends TestCase
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
    public function it_parses_content_json_string_on_create(): void
    {
        $layout = ['sections' => [['id' => 'section-1', 'columns' => []]]];
        $data = [
            'title' => 'Page Layout',
            'slug' => 'page-layout',
            'content_json' => json_encode($layout),
        ];

        $page = $this->service->create($data, $this->shop->id, 'fr');
        $translation = PageTranslation::where('page_id', $page->id)->first();

        $this->assertIsArray($translation->content_json);
        $this->assertArrayHasKey('sections', $translation->content_json);
    }

    #[Test]
    public function it_stores_null_content_json_when_not_provided(): void
    {
        $page = $this->service->create(['title' => 'No Layout', 'slug' => 'no-layout'], $this->shop->id);

        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'content_json' => null,
        ]);
    }

    #[Test]
    public function it_defaults_noindex_to_false(): void
    {
        $page = $this->service->create(['title' => 'Indexed', 'slug' => 'indexed'], $this->shop->id);
        $translation = PageTranslation::where('page_id', $page->id)->first();

        $this->assertFalse($translation->noindex);
    }

    #[Test]
    public function it_sets_noindex_true_when_provided(): void
    {
        $page = $this->service->create(['title' => 'Noindex', 'slug' => 'noindex', 'noindex' => true], $this->shop->id);
        $translation = PageTranslation::where('page_id', $page->id)->first();

        $this->assertTrue($translation->noindex);
    }

    #[Test]
    public function it_defaults_type_to_page(): void
    {
        $page = $this->service->create(['title' => 'Default', 'slug' => 'default'], $this->shop->id);

        $this->assertDatabaseHas('cms_pages', ['id' => $page->id, 'type' => 'page']);
    }

    #[Test]
    public function save_builder_layout_creates_translation_when_none_exists(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        $this->assertCount(0, $page->translations()->get());

        $layout = ['sections' => [['id' => 's1', 'columns' => []]]];
        $translation = $this->service->saveBuilderLayout($page, $layout, 'fr');

        $this->assertDatabaseHas('cms_page_translations', [
            'page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page',
            'slug' => 'page-'.$page->id,
        ]);
        $this->assertEquals($layout, $translation->content_json);
    }

    #[Test]
    public function get_public_pages_returns_only_active(): void
    {
        $active = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $active->id, 'locale' => 'fr']);
        $inactive = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => false]);
        PageTranslation::factory()->create(['page_id' => $inactive->id, 'locale' => 'fr']);

        $result = $this->service->getPublicPages('fr');

        $this->assertCount(1, $result);
        $this->assertEquals($active->id, $result->first()->id);
    }

    #[Test]
    public function get_public_page_returns_active_page(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'slug' => 'publique']);

        $result = $this->service->getPublicPage('publique', 'fr');

        $this->assertNotNull($result);
        $this->assertEquals($page->id, $result->id);
    }

    #[Test]
    public function update_parses_content_json_string(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        PageTranslation::factory()->create(['page_id' => $page->id, 'locale' => 'fr', 'slug' => 'old']);

        $layout = ['sections' => [['id' => 's1', 'columns' => []]]];
        $updated = $this->service->update($page, [
            'title' => 'New',
            'slug' => 'new',
            'content_json' => json_encode($layout),
        ], 'fr');

        $translation = $updated->translations->where('locale', 'fr')->first();
        $this->assertIsArray($translation->content_json);
        $this->assertEquals($layout, $translation->content_json);
    }
}
