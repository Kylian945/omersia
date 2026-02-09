<?php

declare(strict_types=1);

namespace Omersia\Payment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Order;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public string $reason;

    public string $retryUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $reason = '', string $retryUrl = '')
    {
        $this->order = $order;
        $this->reason = $reason;
        $this->retryUrl = $retryUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Échec de paiement - Commande #'.$this->order->number)
            ->view('emails.payment-failed')
            ->with([
                'order' => $this->order,
                'customer' => $this->order->customer,
                'reason' => $this->reason,
                'retryUrl' => $this->retryUrl,
            ]);
    }
}
