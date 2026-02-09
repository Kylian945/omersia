<?php

declare(strict_types=1);

namespace Omersia\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Payment\Models\Payment;
use Omersia\Payment\Models\PaymentProvider;
use Tests\TestCase;

class PaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_payment_provider(): void
    {
        $provider = PaymentProvider::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'enabled' => true,
            'config' => ['api_key' => 'sk_test_123'],
        ]);

        $this->assertDatabaseHas('payment_providers', [
            'name' => 'Stripe',
            'code' => 'stripe',
            'enabled' => true,
        ]);
    }

    public function it_has_many_payments(): void
    {
        $provider = PaymentProvider::factory()->create();
        Payment::factory()->count(3)->create(['payment_provider_id' => $provider->id]);

        $this->assertCount(3, $provider->payments);
    }

    public function it_casts_enabled_to_boolean(): void
    {
        $provider = PaymentProvider::factory()->create(['enabled' => 1]);

        $this->assertIsBool($provider->enabled);
        $this->assertTrue($provider->enabled);
    }

    public function it_casts_config_to_array(): void
    {
        $provider = PaymentProvider::factory()->create([
            'config' => ['api_key' => 'key123', 'webhook_secret' => 'secret456'],
        ]);

        $this->assertIsArray($provider->config);
        $this->assertEquals('key123', $provider->config['api_key']);
    }

    public function it_has_fillable_attributes(): void
    {
        $provider = new PaymentProvider;
        $fillable = $provider->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('enabled', $fillable);
        $this->assertContains('config', $fillable);
    }

    public function it_stores_provider_code(): void
    {
        $provider = PaymentProvider::factory()->create(['code' => 'paypal']);

        $this->assertEquals('paypal', $provider->code);
    }

    public function it_can_be_disabled(): void
    {
        $provider = PaymentProvider::factory()->create(['enabled' => false]);

        $this->assertFalse($provider->enabled);
    }

    public function it_can_store_complex_config(): void
    {
        $config = [
            'api_key' => 'test_key',
            'webhook_secret' => 'whsec_test',
            'mode' => 'sandbox',
            'supported_currencies' => ['EUR', 'USD', 'GBP'],
        ];

        $provider = PaymentProvider::factory()->create(['config' => $config]);

        $this->assertEquals($config, $provider->fresh()->config);
        $this->assertEquals('sandbox', $provider->config['mode']);
        $this->assertIsArray($provider->config['supported_currencies']);
    }
}
