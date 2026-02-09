<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Omersia\Payment\Models\PaymentProvider;

return new class extends Migration
{
    public function up(): void
    {
        PaymentProvider::query()->firstOrCreate(
            ['code' => 'stripe'],
            [
                'name' => 'Stripe',
                'enabled' => false,
                'config' => [
                    'mode' => 'test',
                    'currency' => 'eur',
                ],
            ]
        );

        PaymentProvider::query()->firstOrCreate(
            ['code' => 'manual_test'],
            [
                'name' => 'Paiement de test',
                'enabled' => true,
                'config' => [
                    'description' => 'Moyen de paiement de test local (sans Stripe).',
                    'module_name' => 'Core',
                    'is_test_gateway' => true,
                ],
            ]
        );
    }

    public function down(): void
    {
        $manualTestProvider = PaymentProvider::query()
            ->where('code', 'manual_test')
            ->first();

        if (! $manualTestProvider) {
            return;
        }

        $manualTestProvider->enabled = false;
        $manualTestProvider->save();
    }
};
