<?php

declare(strict_types=1);

namespace Omersia\Payment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Omersia\Catalog\Models\Order;
use Omersia\Payment\Models\Payment;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public Payment $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Paiement reçu - Commande #'.$this->order->number)
            ->view('emails.payment-confirmation')
            ->with([
                'order' => $this->order,
                'payment' => $this->payment,
                'customer' => $this->order->customer,
            ]);
    }
}
