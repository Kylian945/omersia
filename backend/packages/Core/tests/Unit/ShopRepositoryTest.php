<?php

declare(strict_types=1);

namespace Omersia\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\Shop;
use Omersia\Core\Models\ShopDomain;
use Omersia\Core\Repositories\ShopRepository;
use Tests\TestCase;

class ShopRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ShopRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ShopRepository(new Shop);
    }

    public function it_can_find_shop_by_code(): void
    {
        $shop = Shop::factory()->create(['code' => 'test-shop']);

        $result = $this->repository->findByCode('test-shop');

        $this->assertNotNull($result);
        $this->assertEquals('test-shop', $result->code);
    }

    public function it_returns_null_when_shop_code_not_found(): void
    {
        $result = $this->repository->findByCode('non-existent');

        $this->assertNull($result);
    }

    public function it_can_find_shop_by_domain(): void
    {
        $shop = Shop::factory()->create();
        ShopDomain::factory()->create([
            'shop_id' => $shop->id,
            'domain' => 'example.com',
        ]);

        $result = $this->repository->findByDomain('example.com');

        $this->assertNotNull($result);
        $this->assertEquals($shop->id, $result->id);
    }

    public function it_returns_null_when_domain_not_found(): void
    {
        $result = $this->repository->findByDomain('non-existent.com');

        $this->assertNull($result);
    }

    public function it_finds_shop_with_multiple_domains(): void
    {
        $shop = Shop::factory()->create();
        ShopDomain::factory()->create(['shop_id' => $shop->id, 'domain' => 'domain1.com']);
        ShopDomain::factory()->create(['shop_id' => $shop->id, 'domain' => 'domain2.com']);

        $result1 = $this->repository->findByDomain('domain1.com');
        $result2 = $this->repository->findByDomain('domain2.com');

        $this->assertEquals($shop->id, $result1->id);
        $this->assertEquals($shop->id, $result2->id);
    }

    public function it_can_get_active_shops(): void
    {
        Shop::factory()->create(['is_active' => true]);
        Shop::factory()->create(['is_active' => true]);
        Shop::factory()->create(['is_active' => false]);

        $result = $this->repository->getActiveShops();

        $this->assertCount(2, $result);
    }

    public function it_returns_empty_collection_when_no_active_shops(): void
    {
        Shop::factory()->create(['is_active' => false]);

        $result = $this->repository->getActiveShops();

        $this->assertCount(0, $result);
    }

    public function it_can_get_default_shop(): void
    {
        Shop::factory()->create(['is_default' => false]);
        $defaultShop = Shop::factory()->create(['is_default' => true]);

        $result = $this->repository->getDefaultShop();

        $this->assertNotNull($result);
        $this->assertEquals($defaultShop->id, $result->id);
    }

    public function it_returns_null_when_no_default_shop(): void
    {
        Shop::factory()->create(['is_default' => false]);

        $result = $this->repository->getDefaultShop();

        $this->assertNull($result);
    }

    public function it_can_update_settings(): void
    {
        $shop = Shop::factory()->create([
            'name' => 'Old Name',
            'display_name' => 'Old Display',
        ]);

        $result = $this->repository->updateSettings($shop->id, [
            'name' => 'New Name',
            'display_name' => 'New Display',
        ]);

        $this->assertTrue($result);
        $this->assertEquals('New Name', $shop->fresh()->name);
        $this->assertEquals('New Display', $shop->fresh()->display_name);
    }

    public function it_only_updates_fillable_attributes(): void
    {
        $shop = Shop::factory()->create(['name' => 'Original']);

        $this->repository->updateSettings($shop->id, [
            'name' => 'Updated',
            'non_fillable_field' => 'Should not update',
        ]);

        $this->assertEquals('Updated', $shop->fresh()->name);
        $this->assertObjectNotHasProperty('non_fillable_field', $shop->fresh());
    }

    public function it_can_update_multiple_settings(): void
    {
        $shop = Shop::factory()->create();

        $this->repository->updateSettings($shop->id, [
            'name' => 'New Name',
            'code' => 'new-code',
            'default_locale' => 'en',
            'display_name' => 'New Display Name',
        ]);

        $updated = $shop->fresh();
        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('new-code', $updated->code);
        $this->assertEquals('en', $updated->default_locale);
        $this->assertEquals('New Display Name', $updated->display_name);
    }
}
