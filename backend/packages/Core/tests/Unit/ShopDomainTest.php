<?php

declare(strict_types=1);

namespace Omersia\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\Shop;
use Omersia\Core\Models\ShopDomain;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShopDomainTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_shop_domain(): void
    {
        $shop = Shop::factory()->create();

        $domain = ShopDomain::create([
            'shop_id' => $shop->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('shop_domains', [
            'shop_id' => $shop->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);
    }

    #[Test]
    public function it_belongs_to_shop(): void
    {
        $shop = Shop::factory()->create();
        $domain = ShopDomain::factory()->create(['shop_id' => $shop->id]);

        $this->assertInstanceOf(Shop::class, $domain->shop);
        $this->assertEquals($shop->id, $domain->shop->id);
    }

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $domain = new ShopDomain;
        $fillable = $domain->getFillable();

        $this->assertContains('shop_id', $fillable);
        $this->assertContains('domain', $fillable);
        $this->assertContains('is_primary', $fillable);
    }

    #[Test]
    public function it_can_have_primary_domain(): void
    {
        $shop = Shop::factory()->create();
        $primary = ShopDomain::factory()->create([
            'shop_id' => $shop->id,
            'is_primary' => true,
        ]);
        $secondary = ShopDomain::factory()->create([
            'shop_id' => $shop->id,
            'is_primary' => false,
        ]);

        $this->assertTrue($primary->is_primary);
        $this->assertFalse($secondary->is_primary);
    }

    #[Test]
    public function it_stores_domain_string(): void
    {
        $shop = Shop::factory()->create();
        $domain = ShopDomain::create([
            'shop_id' => $shop->id,
            'domain' => 'my-shop.example.com',
            'is_primary' => true,
        ]);

        $this->assertEquals('my-shop.example.com', $domain->domain);
    }
}
