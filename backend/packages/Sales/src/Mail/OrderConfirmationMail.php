<?php

declare(strict_types=1);

namespace Omersia\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Order;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Confirmation de commande #'.$this->order->number)
            ->view('emails.order-confirmation')
            ->with([
                'order' => $this->order,
                'customer' => $this->order->customer,
                'items' => $this->order->items,
                'shippingAddress' => $this->order->shipping_address,
                'billingAddress' => $this->order->billing_address,
            ]);
    }
}
