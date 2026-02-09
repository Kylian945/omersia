<?php

declare(strict_types=1);

namespace Omersia\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Omersia\Catalog\Models\Order;

/**
 * @property int $id
 * @property int $order_id
 * @property int $payment_provider_id
 * @property string $provider_code
 * @property string $provider_payment_id
 * @property string $status
 * @property int $amount
 * @property string $currency
 * @property array<string, mixed>|null $meta
 * @property-read Order|null $order
 * @property-read PaymentProvider|null $provider
 */
class Payment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Payment\Database\Factories\PaymentFactory::new();
    }

    protected $fillable = [
        'order_id',
        'payment_provider_id',
        'provider_code',
        'provider_payment_id',
        'status',
        'amount',
        'currency',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
