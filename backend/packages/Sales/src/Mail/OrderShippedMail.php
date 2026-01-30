<?php

declare(strict_types=1);

namespace Omersia\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Order;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public ?string $trackingNumber;

    public ?string $trackingUrl;

    public ?string $carrier;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, ?string $trackingNumber = null, ?string $trackingUrl = null, ?string $carrier = null)
    {
        $this->order = $order;
        $this->trackingNumber = $trackingNumber;
        $this->trackingUrl = $trackingUrl;
        $this->carrier = $carrier;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Votre commande a été expédiée - #'.$this->order->number)
            ->view('emails.order-shipped')
            ->with([
                'order' => $this->order,
                'customer' => $this->order->customer,
                'items' => $this->order->items,
                'trackingNumber' => $this->trackingNumber,
                'trackingUrl' => $this->trackingUrl,
                'carrier' => $this->carrier,
                'shippingAddress' => $this->order->shipping_address,
            ]);
    }
}
