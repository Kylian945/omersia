<?php

declare(strict_types=1);

namespace Omersia\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Sales\Mail\OrderConfirmationMail;
use Tests\TestCase;

class OrderConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    public function it_builds_order_confirmation_mail(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-123']);

        $mailable = new OrderConfirmationMail($order);

        $this->assertEquals($order->id, $mailable->order->id);
    }

    public function it_has_correct_subject_with_order_number(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-456']);

        $mailable = new OrderConfirmationMail($order);
        $built = $mailable->build();

        $this->assertStringContainsString('Confirmation de commande', $built->subject);
        $this->assertStringContainsString('ORD-456', $built->subject);
    }

    public function it_uses_order_confirmation_view(): void
    {
        $order = Order::factory()->create();

        $mailable = new OrderConfirmationMail($order);
        $built = $mailable->build();

        $this->assertEquals('emails.order-confirmation', $built->view);
    }

    public function it_passes_order_data_to_view(): void
    {
        $order = Order::factory()->create([
            'number' => 'ORD-789',
            'shipping_address' => ['street' => '123 Main St'],
            'billing_address' => ['street' => '456 Oak Ave'],
        ]);

        $mailable = new OrderConfirmationMail($order);
        $built = $mailable->build();

        $viewData = $built->buildViewData();

        $this->assertArrayHasKey('order', $viewData);
        $this->assertArrayHasKey('customer', $viewData);
        $this->assertArrayHasKey('items', $viewData);
        $this->assertArrayHasKey('shippingAddress', $viewData);
        $this->assertArrayHasKey('billingAddress', $viewData);
        $this->assertEquals('ORD-789', $viewData['order']->number);
    }
}
