<?php

declare(strict_types=1);

namespace Omersia\Customer\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;
use Tests\TestCase;

class CustomerGroupTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_customer_group(): void
    {
        $shop = Shop::factory()->create();

        $group = CustomerGroup::create([
            'shop_id' => $shop->id,
            'name' => 'VIP Customers',
            'code' => 'vip',
            'description' => 'High value customers',
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('customer_groups', [
            'name' => 'VIP Customers',
            'code' => 'vip',
        ]);
    }

    public function it_belongs_to_shop(): void
    {
        $shop = Shop::factory()->create();
        $group = CustomerGroup::factory()->create(['shop_id' => $shop->id]);

        $this->assertInstanceOf(Shop::class, $group->shop);
        $this->assertEquals($shop->id, $group->shop->id);
    }

    public function it_belongs_to_many_customers(): void
    {
        $group = CustomerGroup::factory()->create();
        $customer = Customer::factory()->create();
        $group->customers()->attach($customer);

        $this->assertCount(1, $group->customers);
        $this->assertEquals($customer->id, $group->customers->first()->id);
    }

    public function it_can_have_multiple_customers(): void
    {
        $group = CustomerGroup::factory()->create();
        $customers = Customer::factory()->count(5)->create();
        $group->customers()->attach($customers);

        $this->assertCount(5, $group->customers);
    }

    public function it_casts_is_default_to_boolean(): void
    {
        $group = CustomerGroup::factory()->create(['is_default' => 1]);

        $this->assertIsBool($group->is_default);
        $this->assertTrue($group->is_default);
    }

    public function it_has_fillable_attributes(): void
    {
        $group = new CustomerGroup;
        $fillable = $group->getFillable();

        $this->assertContains('shop_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('is_default', $fillable);
    }

    public function it_stores_description(): void
    {
        $group = CustomerGroup::factory()->create([
            'description' => 'Special discount group',
        ]);

        $this->assertEquals('Special discount group', $group->description);
    }

    public function it_stores_code(): void
    {
        $group = CustomerGroup::factory()->create([
            'code' => 'wholesale',
        ]);

        $this->assertEquals('wholesale', $group->code);
    }
}
