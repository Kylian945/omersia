<?php

declare(strict_types=1);

namespace Omersia\Customer\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Address;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_customer(): void
    {
        $shop = Shop::factory()->create();

        $customer = Customer::create([
            'shop_id' => $shop->id,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function it_belongs_to_shop(): void
    {
        $shop = Shop::factory()->create();
        $customer = Customer::factory()->create(['shop_id' => $shop->id]);

        $this->assertInstanceOf(Shop::class, $customer->shop);
        $this->assertEquals($shop->id, $customer->shop->id);
    }

    public function it_belongs_to_many_groups(): void
    {
        $customer = Customer::factory()->create();
        $group = CustomerGroup::factory()->create();
        $customer->groups()->attach($group);

        $this->assertCount(1, $customer->groups);
        $this->assertEquals($group->id, $customer->groups->first()->id);
    }

    public function it_has_fullname_attribute(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => 'Jane',
            'lastname' => 'Smith',
        ]);

        $this->assertEquals('Jane Smith', $customer->fullname);
    }

    public function it_returns_email_when_no_name(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => '',
            'lastname' => '',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('test@example.com', $customer->fullname);
    }

    public function it_has_orders(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'status' => 'confirmed',
        ]);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        $this->assertCount(2, $customer->orders);
    }

    public function it_calculates_orders_total(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'total' => 100.00,
        ]);
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'processing',
            'total' => 50.00,
        ]);

        $this->assertEquals(150.00, $customer->ordersTotal());
    }

    public function it_has_addresses(): void
    {
        $customer = Customer::factory()->create();
        Address::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->addresses);
    }

    public function it_has_default_billing_address(): void
    {
        $customer = Customer::factory()->create();
        $billingAddress = Address::factory()->create([
            'customer_id' => $customer->id,
            'is_default_billing' => true,
        ]);

        $this->assertNotNull($customer->defaultBillingAddress);
        $this->assertEquals($billingAddress->id, $customer->defaultBillingAddress->id);
    }

    public function it_has_default_shipping_address(): void
    {
        $customer = Customer::factory()->create();
        $shippingAddress = Address::factory()->create([
            'customer_id' => $customer->id,
            'is_default_shipping' => true,
        ]);

        $this->assertNotNull($customer->defaultShippingAddress);
        $this->assertEquals($shippingAddress->id, $customer->defaultShippingAddress->id);
    }

    public function it_hides_password_attribute(): void
    {
        $customer = Customer::factory()->create(['password' => bcrypt('secret')]);

        $array = $customer->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function it_casts_is_active_to_boolean(): void
    {
        $customer = Customer::factory()->create(['is_active' => 1]);

        $this->assertIsBool($customer->is_active);
        $this->assertTrue($customer->is_active);
    }

    public function it_has_fillable_attributes(): void
    {
        $customer = new Customer;
        $fillable = $customer->getFillable();

        $this->assertContains('shop_id', $fillable);
        $this->assertContains('firstname', $fillable);
        $this->assertContains('lastname', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('is_active', $fillable);
    }
}
