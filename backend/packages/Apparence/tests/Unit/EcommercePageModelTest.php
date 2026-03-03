<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Apparence\Models\EcommercePageTranslation;
use Omersia\Core\Models\Shop;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EcommercePageModelTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shop::factory()->create();
    }

    #[Test]
    public function it_casts_is_active_to_boolean(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id, 'is_active' => 1]);
        $this->assertIsBool($page->is_active);
        $this->assertTrue($page->is_active);
    }

    #[Test]
    public function it_belongs_to_shop(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        $this->assertEquals($this->shop->id, $page->shop->id);
    }

    #[Test]
    public function it_has_many_translations(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        EcommercePageTranslation::factory()->create(['ecommerce_page_id' => $page->id, 'locale' => 'fr']);
        EcommercePageTranslation::factory()->create(['ecommerce_page_id' => $page->id, 'locale' => 'en']);

        $this->assertCount(2, $page->translations()->get());
    }

    #[Test]
    public function translation_returns_correct_locale(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        EcommercePageTranslation::factory()->create(['ecommerce_page_id' => $page->id, 'locale' => 'fr', 'title' => 'Accueil']);
        EcommercePageTranslation::factory()->create(['ecommerce_page_id' => $page->id, 'locale' => 'en', 'title' => 'Home']);

        $this->assertEquals('Accueil', $page->translation('fr')->title);
        $this->assertEquals('Home', $page->translation('en')->title);
    }

    #[Test]
    public function translation_returns_null_for_unknown_locale(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id]);
        EcommercePageTranslation::factory()->create(['ecommerce_page_id' => $page->id, 'locale' => 'fr']);

        $this->assertNull($page->translation('de'));
    }

    #[Test]
    public function it_has_expected_fillable_attributes(): void
    {
        $fillable = (new EcommercePage)->getFillable();
        $this->assertContains('shop_id', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    #[Test]
    public function it_stores_type_correctly(): void
    {
        $page = EcommercePage::factory()->create(['shop_id' => $this->shop->id, 'type' => 'homepage']);
        $this->assertEquals('homepage', $page->fresh()->type);
    }
}
