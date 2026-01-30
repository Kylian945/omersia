<?php

declare(strict_types=1);

namespace Omersia\Customer\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Customer\Mail\WelcomeMail;
use Omersia\Customer\Models\Customer;
use Tests\TestCase;

class WelcomeMailTest extends TestCase
{
    use RefreshDatabase;

    public function it_builds_welcome_mail_with_customer(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $mailable = new WelcomeMail($customer);

        $this->assertEquals($customer->id, $mailable->customer->id);
    }

    public function it_has_correct_subject(): void
    {
        $customer = Customer::factory()->create();
        $mailable = new WelcomeMail($customer);

        $built = $mailable->build();

        $this->assertStringContainsString('Bienvenue', $built->subject);
    }

    public function it_uses_welcome_view(): void
    {
        $customer = Customer::factory()->create();
        $mailable = new WelcomeMail($customer);

        $built = $mailable->build();

        $this->assertEquals('emails.welcome', $built->view);
    }

    public function it_passes_customer_to_view(): void
    {
        $customer = Customer::factory()->create([
            'firstname' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        $mailable = new WelcomeMail($customer);
        $built = $mailable->build();

        $this->assertArrayHasKey('customer', $built->buildViewData());
        $this->assertEquals('Jane', $built->buildViewData()['customer']->firstname);
    }
}
