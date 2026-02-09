<?php

declare(strict_types=1);

namespace Omersia\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Omersia\Payment\Models\PaymentProvider;
use Tests\TestCase;

class PaymentProviderEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function it_encrypts_config_in_database(): void
    {
        $sensitiveConfig = [
            'secret_key' => 'sk_test_51234567890abcdef',
            'webhook_secret' => 'whsec_test_secret_key_here',
            'publishable_key' => 'pk_test_1234567890',
        ];

        $provider = PaymentProvider::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'enabled' => true,
            'config' => $sensitiveConfig,
        ]);

        // Vérifier que les données sont accessibles via le modèle (déchiffrement automatique)
        $this->assertEquals($sensitiveConfig, $provider->fresh()->config);

        // Vérifier que les données en base sont chiffrées (pas en clair)
        $rawConfig = DB::table('payment_providers')
            ->where('id', $provider->id)
            ->value('config');

        // Les données chiffrées ne doivent PAS contenir les secrets en clair
        $this->assertStringNotContainsString('sk_test_51234567890abcdef', $rawConfig);
        $this->assertStringNotContainsString('whsec_test_secret_key_here', $rawConfig);
        $this->assertStringNotContainsString('pk_test_1234567890', $rawConfig);

        // Le format chiffré Laravel commence par 'eyJpdiI6' (JSON base64)
        $this->assertStringStartsWith('eyJpdiI6', $rawConfig);
    }

    public function it_decrypts_config_transparently(): void
    {
        $config = [
            'secret_key' => 'sk_test_secret',
            'publishable_key' => 'pk_test_public',
            'mode' => 'test',
        ];

        $provider = PaymentProvider::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'enabled' => true,
            'config' => $config,
        ]);

        // Récupérer depuis la base - déchiffrement automatique
        $retrieved = PaymentProvider::find($provider->id);

        $this->assertIsArray($retrieved->config);
        $this->assertEquals('sk_test_secret', $retrieved->config['secret_key']);
        $this->assertEquals('pk_test_public', $retrieved->config['publishable_key']);
        $this->assertEquals('test', $retrieved->config['mode']);
    }

    public function it_handles_empty_config(): void
    {
        $provider = PaymentProvider::create([
            'name' => 'Test Provider',
            'code' => 'test',
            'enabled' => false,
            'config' => [],
        ]);

        $this->assertIsArray($provider->fresh()->config);
        $this->assertEmpty($provider->config);
    }

    public function it_handles_null_values_in_config(): void
    {
        $config = [
            'secret_key' => null,
            'webhook_secret' => null,
            'publishable_key' => 'pk_test_123',
        ];

        $provider = PaymentProvider::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'enabled' => true,
            'config' => $config,
        ]);

        $retrieved = $provider->fresh();
        $this->assertNull($retrieved->config['secret_key']);
        $this->assertNull($retrieved->config['webhook_secret']);
        $this->assertEquals('pk_test_123', $retrieved->config['publishable_key']);
    }

    public function it_updates_encrypted_config(): void
    {
        $provider = PaymentProvider::create([
            'name' => 'Stripe',
            'code' => 'stripe',
            'enabled' => true,
            'config' => ['secret_key' => 'old_key'],
        ]);

        // Mise à jour
        $provider->config = ['secret_key' => 'new_key', 'webhook_secret' => 'new_secret'];
        $provider->save();

        // Vérifier depuis la base
        $retrieved = PaymentProvider::find($provider->id);
        $this->assertEquals('new_key', $retrieved->config['secret_key']);
        $this->assertEquals('new_secret', $retrieved->config['webhook_secret']);

        // Vérifier que c'est bien chiffré en base
        $rawConfig = DB::table('payment_providers')
            ->where('id', $provider->id)
            ->value('config');

        $this->assertStringNotContainsString('new_key', $rawConfig);
        $this->assertStringNotContainsString('new_secret', $rawConfig);
    }
}
