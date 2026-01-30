<?php

declare(strict_types=1);

namespace Omersia\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Mail\PaymentConfirmationMail;
use Omersia\Payment\Models\Payment;
use Tests\TestCase;

class PaymentConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    public function it_builds_payment_confirmation_mail(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-123']);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $mailable = new PaymentConfirmationMail($order, $payment);

        $this->assertEquals($order->id, $mailable->order->id);
        $this->assertEquals($payment->id, $mailable->payment->id);
    }

    public function it_has_correct_subject_with_order_number(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-456']);
        $payment = Payment::factory()->create();

        $mailable = new PaymentConfirmationMail($order, $payment);
        $built = $mailable->build();

        $this->assertStringContainsString('Paiement reçu', $built->subject);
        $this->assertStringContainsString('ORD-456', $built->subject);
    }

    public function it_uses_payment_confirmation_view(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create();

        $mailable = new PaymentConfirmationMail($order, $payment);
        $built = $mailable->build();

        $this->assertEquals('emails.payment-confirmation', $built->view);
    }

    public function it_passes_order_and_payment_to_view(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-789']);
        $payment = Payment::factory()->create(['amount' => 150.00]);

        $mailable = new PaymentConfirmationMail($order, $payment);
        $built = $mailable->build();

        $viewData = $built->buildViewData();

        $this->assertArrayHasKey('order', $viewData);
        $this->assertArrayHasKey('payment', $viewData);
        $this->assertArrayHasKey('customer', $viewData);
        $this->assertEquals('ORD-789', $viewData['order']->number);
        $this->assertEquals(150.00, $viewData['payment']->amount);
    }
}
