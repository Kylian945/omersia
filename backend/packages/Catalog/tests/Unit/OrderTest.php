<?php

declare(strict_types=1);

namespace Omersia\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Omersia\Catalog\Models\Cart;
use Omersia\Catalog\Models\Order;
use Omersia\Catalog\Models\OrderItem;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Mail\OrderConfirmationMail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_order(): void
    {
        $order = Order::create([
            'number' => 'ORD-001',
            'currency' => 'EUR',
            'status' => 'draft',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal' => 100.00,
            'total' => 120.00,
            'customer_email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('orders', [
            'number' => 'ORD-001',
            'total' => 120.00,
        ]);
    }

    #[Test]
    public function it_has_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->items);
    }

    #[Test]
    public function it_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $order->customer);
        $this->assertEquals($customer->id, $order->customer->id);
    }

    #[Test]
    public function it_belongs_to_cart(): void
    {
        $cart = Cart::factory()->create();
        $order = Order::factory()->create(['cart_id' => $cart->id]);

        $this->assertInstanceOf(Cart::class, $order->cart);
        $this->assertEquals($cart->id, $order->cart->id);
    }

    #[Test]
    public function it_belongs_to_shipping_method(): void
    {
        $shippingMethod = ShippingMethod::factory()->create();
        $order = Order::factory()->create(['shipping_method_id' => $shippingMethod->id]);

        $this->assertInstanceOf(ShippingMethod::class, $order->shippingMethod);
    }

    #[Test]
    public function it_casts_shipping_address_to_array(): void
    {
        $order = Order::factory()->create([
            'shipping_address' => ['street' => '123 Main St', 'city' => 'Paris'],
        ]);

        $this->assertIsArray($order->shipping_address);
        $this->assertEquals('123 Main St', $order->shipping_address['street']);
    }

    #[Test]
    public function it_casts_billing_address_to_array(): void
    {
        $order = Order::factory()->create([
            'billing_address' => ['street' => '456 Oak Ave', 'city' => 'Lyon'],
        ]);

        $this->assertIsArray($order->billing_address);
        $this->assertEquals('456 Oak Ave', $order->billing_address['street']);
    }

    #[Test]
    public function it_casts_meta_to_array(): void
    {
        $order = Order::factory()->create([
            'meta' => ['source' => 'web', 'campaign' => 'summer'],
        ]);

        $this->assertIsArray($order->meta);
        $this->assertEquals('web', $order->meta['source']);
    }

    #[Test]
    public function it_has_status_label_attribute(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $this->assertEquals('Confirmée', $order->status_label);
    }

    #[Test]
    public function it_can_scope_confirmed_orders(): void
    {
        Order::factory()->create(['status' => 'confirmed']);
        Order::factory()->create(['status' => 'processing']);
        Order::factory()->create(['status' => 'draft']);

        $confirmed = Order::confirmed()->get();

        $this->assertCount(2, $confirmed);
    }

    #[Test]
    public function it_can_scope_draft_orders(): void
    {
        Order::factory()->create(['status' => 'draft']);
        Order::factory()->create(['status' => 'draft']);
        Order::factory()->create(['status' => 'confirmed']);

        $drafts = Order::draft()->get();

        $this->assertCount(2, $drafts);
    }

    #[Test]
    public function it_can_check_if_is_draft(): void
    {
        $draft = Order::factory()->create(['status' => 'draft']);
        $confirmed = Order::factory()->create(['status' => 'confirmed']);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($confirmed->isDraft());
    }

    #[Test]
    public function it_can_confirm_draft_order(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'status' => 'draft',
            'customer_id' => $customer->id,
            'placed_at' => null,
        ]);

        $order->confirm();

        $this->assertEquals('confirmed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->placed_at);
    }

    #[Test]
    public function it_sends_confirmation_email_when_confirming(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'status' => 'draft',
            'customer_id' => $customer->id,
        ]);

        $order->confirm();

        Mail::assertQueued(OrderConfirmationMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }

    #[Test]
    public function it_does_not_confirm_non_draft_order(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);
        $originalPlacedAt = $order->placed_at;

        $order->confirm();

        $this->assertEquals('confirmed', $order->fresh()->status);
        $this->assertEquals($originalPlacedAt, $order->fresh()->placed_at);
    }

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $order = new Order;
        $fillable = $order->getFillable();

        $this->assertContains('number', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('total', $fillable);
        $this->assertContains('customer_email', $fillable);
    }
}
