<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Api\Mail\CustomerPasswordResetMail;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;

class CustomerPasswordResetMailTest extends TestCase
{
    use RefreshDatabase;

    public function it_builds_password_reset_mail(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => 'John',
            'email' => 'john@example.com',
        ]);
        $resetUrl = 'https://example.com/reset/token123';

        $mailable = new CustomerPasswordResetMail($customer, $resetUrl);

        $this->assertEquals($customer->id, $mailable->customer->id);
        $this->assertEquals($resetUrl, $mailable->resetUrl);
    }

    public function it_has_correct_subject(): void
    {
        $customer = Customer::factory()->create();
        $resetUrl = 'https://example.com/reset/abc';

        $mailable = new CustomerPasswordResetMail($customer, $resetUrl);
        $envelope = $mailable->envelope();

        $this->assertEquals('Réinitialisation de votre mot de passe', $envelope->subject);
    }

    public function it_uses_password_reset_view(): void
    {
        $customer = Customer::factory()->create();
        $resetUrl = 'https://example.com/reset/xyz';

        $mailable = new CustomerPasswordResetMail($customer, $resetUrl);
        $content = $mailable->content();

        $this->assertEquals('emails.password-reset', $content->view);
    }

    public function it_passes_customer_and_reset_url_to_view(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => 'Jane',
            'email' => 'jane@example.com',
        ]);
        $resetUrl = 'https://example.com/reset/token456';

        $mailable = new CustomerPasswordResetMail($customer, $resetUrl);
        $content = $mailable->content();

        $this->assertEquals('Jane', $content->with['customer']->firstname);
        $this->assertEquals('https://example.com/reset/token456', $content->with['resetUrl']);
    }

    public function it_has_no_attachments(): void
    {
        $customer = Customer::factory()->create();
        $resetUrl = 'https://example.com/reset/abc';

        $mailable = new CustomerPasswordResetMail($customer, $resetUrl);
        $attachments = $mailable->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
