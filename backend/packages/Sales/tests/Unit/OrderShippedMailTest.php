<?php

declare(strict_types=1);

namespace Omersia\Sales\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Sales\Mail\OrderShippedMail;
use Tests\TestCase;

class OrderShippedMailTest extends TestCase
{
    use RefreshDatabase;

    public function it_builds_order_shipped_mail(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-123']);

        $mailable = new OrderShippedMail(
            $order,
            'TRK123456',
            'https://tracking.example.com/TRK123456',
            'DHL'
        );

        $this->assertEquals($order->id, $mailable->order->id);
        $this->assertEquals('TRK123456', $mailable->trackingNumber);
        $this->assertEquals('https://tracking.example.com/TRK123456', $mailable->trackingUrl);
        $this->assertEquals('DHL', $mailable->carrier);
    }

    public function it_has_correct_subject_with_order_number(): void
    {
        $order = Order::factory()->create(['number' => 'ORD-456']);

        $mailable = new OrderShippedMail($order);
        $built = $mailable->build();

        $this->assertStringContainsString('Votre commande a été expédiée', $built->subject);
        $this->assertStringContainsString('ORD-456', $built->subject);
    }

    public function it_uses_order_shipped_view(): void
    {
        $order = Order::factory()->create();

        $mailable = new OrderShippedMail($order);
        $built = $mailable->build();

        $this->assertEquals('emails.order-shipped', $built->view);
    }

    public function it_passes_order_and_tracking_data_to_view(): void
    {
        $order = Order::factory()->create([
            'number' => 'ORD-789',
            'shipping_address' => ['street' => '123 Main St'],
        ]);

        $mailable = new OrderShippedMail(
            $order,
            'TRACK999',
            'https://example.com/track',
            'UPS'
        );
        $built = $mailable->build();

        $viewData = $built->buildViewData();

        $this->assertArrayHasKey('order', $viewData);
        $this->assertArrayHasKey('customer', $viewData);
        $this->assertArrayHasKey('items', $viewData);
        $this->assertArrayHasKey('trackingNumber', $viewData);
        $this->assertArrayHasKey('trackingUrl', $viewData);
        $this->assertArrayHasKey('carrier', $viewData);
        $this->assertArrayHasKey('shippingAddress', $viewData);
        $this->assertEquals('TRACK999', $viewData['trackingNumber']);
        $this->assertEquals('UPS', $viewData['carrier']);
    }

    public function it_handles_null_tracking_information(): void
    {
        $order = Order::factory()->create();

        $mailable = new OrderShippedMail($order);

        $this->assertNull($mailable->trackingNumber);
        $this->assertNull($mailable->trackingUrl);
        $this->assertNull($mailable->carrier);
    }
}
