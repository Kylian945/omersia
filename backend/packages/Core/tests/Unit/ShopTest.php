<?php

declare(strict_types=1);

namespace Omersia\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\Shop;
use Omersia\Core\Models\ShopDomain;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_shop(): void
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'code' => 'test-shop',
            'default_locale' => 'fr',
        ]);

        $this->assertDatabaseHas('shops', [
            'name' => 'Test Shop',
            'code' => 'test-shop',
            'default_locale' => 'fr',
        ]);
    }

    public function it_has_domains_relationship(): void
    {
        $shop = Shop::factory()->create();
        ShopDomain::factory()->create(['shop_id' => $shop->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $shop->domains);
        $this->assertCount(1, $shop->domains);
    }

    public function it_can_have_multiple_domains(): void
    {
        $shop = Shop::factory()->create();
        ShopDomain::factory()->count(3)->create(['shop_id' => $shop->id]);

        $this->assertCount(3, $shop->fresh()->domains);
    }

    public function it_has_fillable_attributes(): void
    {
        $shop = new Shop;
        $fillable = $shop->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('default_locale', $fillable);
        $this->assertContains('default_currency_id', $fillable);
        $this->assertContains('logo_path', $fillable);
        $this->assertContains('display_name', $fillable);
    }

    public function it_can_set_display_name(): void
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'code' => 'test',
            'default_locale' => 'fr',
            'display_name' => 'My Beautiful Shop',
        ]);

        $this->assertEquals('My Beautiful Shop', $shop->display_name);
    }

    public function it_can_set_logo_path(): void
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'code' => 'test',
            'default_locale' => 'fr',
            'logo_path' => '/logos/test-logo.png',
        ]);

        $this->assertEquals('/logos/test-logo.png', $shop->logo_path);
    }
}
