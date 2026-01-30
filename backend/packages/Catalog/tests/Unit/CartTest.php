<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Cart;
use Omersia\Catalog\Models\CartItem;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_cart(): void
    {
        $cart = Cart::create([
            'token' => 'test-token',
            'currency' => 'EUR',
            'subtotal' => 100.00,
            'total_qty' => 5,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('carts', [
            'token' => 'test-token',
            'currency' => 'EUR',
            'subtotal' => 100.00,
        ]);
    }

    public function it_has_items(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

        $this->assertCount(3, $cart->items);
    }

    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $cart = Cart::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $cart->customer);
        $this->assertEquals($customer->id, $cart->customer->id);
    }

    public function it_casts_metadata_to_array(): void
    {
        $cart = Cart::factory()->create([
            'metadata' => ['source' => 'mobile', 'campaign' => 'winter'],
        ]);

        $this->assertIsArray($cart->metadata);
        $this->assertEquals('mobile', $cart->metadata['source']);
    }

    public function it_casts_last_activity_at_to_datetime(): void
    {
        $cart = Cart::factory()->create([
            'last_activity_at' => '2025-01-15 10:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cart->last_activity_at);
    }

    public function it_has_fillable_attributes(): void
    {
        $cart = new Cart;
        $fillable = $cart->getFillable();

        $this->assertContains('token', $fillable);
        $this->assertContains('customer_id', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('currency', $fillable);
        $this->assertContains('subtotal', $fillable);
        $this->assertContains('total_qty', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('metadata', $fillable);
        $this->assertContains('last_activity_at', $fillable);
    }

    public function it_can_store_email_for_guest_cart(): void
    {
        $cart = Cart::create([
            'token' => 'guest-token',
            'email' => 'guest@example.com',
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $this->assertEquals('guest@example.com', $cart->email);
        $this->assertNull($cart->customer_id);
    }

    public function it_tracks_total_quantity(): void
    {
        $cart = Cart::factory()->create(['total_qty' => 10]);

        $this->assertEquals(10, $cart->total_qty);
    }

    public function it_stores_subtotal(): void
    {
        $cart = Cart::factory()->create(['subtotal' => 250.50]);

        $this->assertEquals(250.50, $cart->subtotal);
    }
}
