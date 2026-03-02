<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\EcommercePageTranslation;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\WithApiKey;

class EcommercePageApiTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
        // EcommercePageApiController utilise Shop::firstOrFail()
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_shows_ecommerce_page_by_slug(): void
    {
        // Arrange
        $page = EcommercePage::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'a-propos',
            'type' => 'custom',
            'is_active' => true,
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'À propos de nous',
            'content_json' => ['sections' => []],
        ]);

        // Act
        $response = $this->getJson('/api/v1/ecommerce-pages/a-propos?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $page->id,
            'slug' => 'a-propos',
            'type' => 'custom',
            'title' => 'À propos de nous',
        ]);
    }

    #[Test]
    public function it_shows_ecommerce_page_by_type(): void
    {
        // Arrange: page avec type 'collection' et slug 'summer'
        // La route /api/v1/ecommerce-pages/{type}/{slug?} requiert 2 segments
        $page = EcommercePage::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'summer',
            'type' => 'collection',
            'is_active' => true,
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Collection Été',
            'content_json' => ['sections' => []],
        ]);

        // Act: GET /api/v1/ecommerce-pages/{type}/{slug} (2 segments)
        $response = $this->getJson('/api/v1/ecommerce-pages/collection/summer?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $page->id,
            'type' => 'collection',
            'slug' => 'summer',
        ]);
    }

    #[Test]
    public function it_returns_404_for_unknown_ecommerce_page(): void
    {
        // Act
        $response = $this->getJson('/api/v1/ecommerce-pages/slug-inexistant?locale=fr', $this->apiHeaders());

        // Assert
        $response->assertNotFound();
        $response->assertJson(['error' => 'Page not found']);
    }

    #[Test]
    public function it_returns_correct_ecommerce_page_structure(): void
    {
        // Arrange
        $page = EcommercePage::factory()->create([
            'shop_id' => $this->shop->id,
            'slug' => 'page-structuree',
            'type' => 'custom',
            'is_active' => true,
        ]);

        EcommercePageTranslation::factory()->create([
            'ecommerce_page_id' => $page->id,
            'locale' => 'fr',
            'title' => 'Page Structurée',
            'content_json' => [
                'sections' => [
                    [
                        'id' => 'section-1',
                        'columns' => [
                            [
                                'id' => 'col-1',
                                'widgets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Act
        $response = $this->getJson('/api/v1/ecommerce-pages/page-structuree?locale=fr', $this->apiHeaders());

        // Assert: structure complète
        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'type',
            'slug',
            'title',
            'content' => [
                'sections',
            ],
        ]);

        // Vérifier que content_json est bien retourné
        $content = $response->json('content');
        $this->assertArrayHasKey('sections', $content);
        $this->assertCount(1, $content['sections']);
    }
}
