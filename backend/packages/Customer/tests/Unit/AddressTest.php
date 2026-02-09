<?php

declare(strict_types=1);

namespace Omersia\Customer\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Models\Address;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_address(): void
    {
        $customer = Customer::factory()->create();

        $address = Address::create([
            'customer_id' => $customer->id,
            'label' => 'Home',
            'line1' => '123 Main St',
            'city' => 'Paris',
            'postcode' => '75001',
            'country' => 'FR',
        ]);

        $this->assertDatabaseHas('addresses', [
            'customer_id' => $customer->id,
            'label' => 'Home',
            'line1' => '123 Main St',
            'city' => 'Paris',
        ]);
    }

    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $address = Address::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $address->customer);
        $this->assertEquals($customer->id, $address->customer->id);
    }

    public function it_casts_is_default_billing_to_boolean(): void
    {
        $address = Address::factory()->create(['is_default_billing' => 1]);

        $this->assertIsBool($address->is_default_billing);
        $this->assertTrue($address->is_default_billing);
    }

    public function it_casts_is_default_shipping_to_boolean(): void
    {
        $address = Address::factory()->create(['is_default_shipping' => 1]);

        $this->assertIsBool($address->is_default_shipping);
        $this->assertTrue($address->is_default_shipping);
    }

    public function it_has_fillable_attributes(): void
    {
        $address = new Address;
        $fillable = $address->getFillable();

        $this->assertContains('customer_id', $fillable);
        $this->assertContains('label', $fillable);
        $this->assertContains('line1', $fillable);
        $this->assertContains('line2', $fillable);
        $this->assertContains('postcode', $fillable);
        $this->assertContains('city', $fillable);
        $this->assertContains('state', $fillable);
        $this->assertContains('country', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('is_default_billing', $fillable);
        $this->assertContains('is_default_shipping', $fillable);
    }

    public function it_can_store_label(): void
    {
        $address = Address::factory()->create([
            'label' => 'Home',
        ]);

        $this->assertEquals('Home', $address->label);
    }

    public function it_can_store_state(): void
    {
        $address = Address::factory()->create([
            'state' => 'Île-de-France',
        ]);

        $this->assertEquals('Île-de-France', $address->state);
    }

    public function it_can_store_second_address_line(): void
    {
        $address = Address::factory()->create([
            'line1' => '123 Main St',
            'line2' => 'Apt 4B',
        ]);

        $this->assertEquals('123 Main St', $address->line1);
        $this->assertEquals('Apt 4B', $address->line2);
    }
}
