<?php

declare(strict_types=1);

namespace Omersia\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Mail\PaymentFailedMail;
use Tests\TestCase;

class PaymentFailedMailTest extends TestCase
{
    use RefreshDatabase;

    public function it_builds_payment_failed_mail(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-123']);

        $mailable = new PaymentFailedMail($order, 'Insufficient funds', 'https://example.com/retry');

        $this->assertEquals($order->id, $mailable->order->id);
        $this->assertEquals('Insufficient funds', $mailable->reason);
        $this->assertEquals('https://example.com/retry', $mailable->retryUrl);
    }

    public function it_has_correct_subject_with_order_number(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-456']);

        $mailable = new PaymentFailedMail($order);
        $built = $mailable->build();

        $this->assertStringContainsString('Échec de paiement', $built->subject);
        $this->assertStringContainsString('ORD-456', $built->subject);
    }

    public function it_uses_payment_failed_view(): void
    {
        $order = Order::factory()->create();

        $mailable = new PaymentFailedMail($order);
        $built = $mailable->build();

        $this->assertEquals('emails.payment-failed', $built->view);
    }

    public function it_passes_order_and_reason_to_view(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-789']);
        $reason = 'Card declined';
        $retryUrl = 'https://example.com/retry-payment';

        $mailable = new PaymentFailedMail($order, $reason, $retryUrl);
        $built = $mailable->build();

        $viewData = $built->buildViewData();

        $this->assertArrayHasKey('order', $viewData);
        $this->assertArrayHasKey('customer', $viewData);
        $this->assertArrayHasKey('reason', $viewData);
        $this->assertArrayHasKey('retryUrl', $viewData);
        $this->assertEquals('ORD-789', $viewData['order']->number);
        $this->assertEquals('Card declined', $viewData['reason']);
        $this->assertEquals('https://example.com/retry-payment', $viewData['retryUrl']);
    }

    public function it_handles_empty_reason_and_retry_url(): void
    {
        $order = Order::factory()->create();

        $mailable = new PaymentFailedMail($order);

        $this->assertEquals('', $mailable->reason);
        $this->assertEquals('', $mailable->retryUrl);
    }
}
