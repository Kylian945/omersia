<?php

declare(strict_types=1);

namespace Omersia\Customer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Omersia\Customer\Models\Customer;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;

    /**
     * Create a new message instance.
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Bienvenue chez '.config('app.name', 'Omersia').' !')
            ->view('emails.welcome')
            ->with([
                'customer' => $this->customer,
            ]);
    }
}
