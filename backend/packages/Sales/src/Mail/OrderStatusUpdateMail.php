<?php

declare(strict_types=1);

namespace Omersia\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Order;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public string $statusType; // 'delivery_delayed', 'in_transit', 'delivered'

    public ?string $statusMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $statusType, ?string $statusMessage = null)
    {
        $this->order = $order;
        $this->statusType = $statusType;
        $this->statusMessage = $statusMessage;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subjects = [
            'delivery_delayed' => 'Mise à jour de votre livraison - Commande #'.$this->order->number,
            'in_transit' => 'Votre colis est en cours de livraison - Commande #'.$this->order->number,
            'delivered' => 'Votre commande a été livrée - #'.$this->order->number,
        ];

        return $this->subject($subjects[$this->statusType] ?? 'Mise à jour de commande #'.$this->order->number)
            ->view('emails.order-status-update')
            ->with([
                'order' => $this->order,
                'customer' => $this->order->customer,
                'statusType' => $this->statusType,
                'statusMessage' => $this->statusMessage,
                'shippingAddress' => $this->order->shipping_address,
            ]);
    }
}
