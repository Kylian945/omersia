<?php

declare(strict_types=1);

namespace Omersia\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Models\Payment;
use Omersia\Payment\Models\PaymentProvider;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_payment(): void
    {
        $order = Order::factory()->create();
        $provider = PaymentProvider::factory()->create();

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_provider_id' => $provider->id,
            'provider_code' => 'stripe',
            'provider_payment_id' => 'pi_123456',
            'status' => 'pending',
            'amount' => 100.00,
            'currency' => 'EUR',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_code' => 'stripe',
            'amount' => 100.00,
        ]);
    }

    public function it_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $payment->order);
        $this->assertEquals($order->id, $payment->order->id);
    }

    public function it_belongs_to_provider(): void
    {
        $provider = PaymentProvider::factory()->create();
        $payment = Payment::factory()->create(['payment_provider_id' => $provider->id]);

        $this->assertInstanceOf(PaymentProvider::class, $payment->provider);
        $this->assertEquals($provider->id, $payment->provider->id);
    }

    public function it_casts_meta_to_array(): void
    {
        $payment = Payment::factory()->create([
            'meta' => ['transaction_id' => 'txn_123', 'method' => 'card'],
        ]);

        $this->assertIsArray($payment->meta);
        $this->assertEquals('txn_123', $payment->meta['transaction_id']);
    }

    public function it_has_fillable_attributes(): void
    {
        $payment = new Payment;
        $fillable = $payment->getFillable();

        $this->assertContains('order_id', $fillable);
        $this->assertContains('payment_provider_id', $fillable);
        $this->assertContains('provider_code', $fillable);
        $this->assertContains('provider_payment_id', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('amount', $fillable);
        $this->assertContains('currency', $fillable);
        $this->assertContains('meta', $fillable);
    }

    public function it_stores_provider_payment_id(): void
    {
        $payment = Payment::factory()->create([
            'provider_payment_id' => 'ch_abc123xyz',
        ]);

        $this->assertEquals('ch_abc123xyz', $payment->provider_payment_id);
    }

    public function it_stores_payment_status(): void
    {
        $payment = Payment::factory()->create(['status' => 'completed']);

        $this->assertEquals('completed', $payment->status);
    }

    public function it_stores_currency(): void
    {
        $payment = Payment::factory()->create(['currency' => 'USD']);

        $this->assertEquals('USD', $payment->currency);
    }

    public function it_can_store_complex_meta(): void
    {
        $meta = [
            'card_last4' => '4242',
            'card_brand' => 'visa',
            'receipt_url' => 'https://example.com/receipt',
        ];

        $payment = Payment::factory()->create(['meta' => $meta]);

        $this->assertEquals($meta, $payment->fresh()->meta);
    }
}
