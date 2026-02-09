<?php

declare(strict_types=1);

namespace Omersia\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property mixed $name
 * @property mixed $code
 * @property bool $enabled
 * @property array<string, mixed>|null $config
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 */
class PaymentProvider extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Payment\Database\Factories\PaymentProviderFactory::new();
    }

    protected $fillable = ['name', 'code', 'enabled', 'config'];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'encrypted:array',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public static function ensureCoreProviders(): void
    {
        self::query()->firstOrCreate(
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

        self::query()->firstOrCreate(
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
}
