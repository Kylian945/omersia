<?php

declare(strict_types=1);

namespace Omersia\CMS\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\CMS\Models\Page;
use Omersia\CMS\Models\PageTranslation;
use Omersia\CMS\Models\PageVersion;
use Omersia\CMS\Services\PageVersioningService;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageVersioningServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageVersioningService $service;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PageVersioningService::class);
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_creates_snapshot_for_translation(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        $translation = PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'content_json' => ['sections' => [['id' => 's1']]],
        ]);

        $version = $this->service->createSnapshot($translation, null, 'Avant publication');

        $this->assertInstanceOf(PageVersion::class, $version);
        $this->assertDatabaseHas('cms_page_versions', [
            'id' => $version->id,
            'page_translation_id' => $translation->id,
            'label' => 'Avant publication',
        ]);
    }

    #[Test]
    public function it_restores_a_version_and_saves_current_snapshot_before_restore(): void
    {
        $page = Page::factory()->create(['shop_id' => $this->shop->id]);
        $translation = PageTranslation::factory()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'content_json' => ['sections' => [['id' => 'new-layout']]],
        ]);

        $targetVersion = PageVersion::factory()->create([
            'page_translation_id' => $translation->id,
            'content_json' => ['sections' => [['id' => 'old-layout']]],
        ]);

        $restoredTranslation = $this->service->restoreVersion($translation, $targetVersion);

        $this->assertEquals(
            ['sections' => [['id' => 'old-layout']]],
            $restoredTranslation->content_json
        );

        $this->assertSame(2, PageVersion::query()->where('page_translation_id', $translation->id)->count());
    }

    #[Test]
    public function build_visual_diff_returns_added_removed_and_changed_items(): void
    {
        $from = [
            'sections' => [
                ['id' => 's1', 'title' => 'Avant'],
            ],
        ];
        $to = [
            'sections' => [
                ['id' => 's1', 'title' => 'Après'],
            ],
            'meta' => ['enabled' => true],
        ];

        $diff = $this->service->buildVisualDiff($from, $to);

        $this->assertTrue($diff['has_changes']);
        $this->assertNotEmpty($diff['changed']);
        $this->assertNotEmpty($diff['added']);
    }
}

