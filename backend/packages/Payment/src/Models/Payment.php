<?php

declare(strict_types=1);

namespace Omersia\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Omersia\Catalog\Models\Order;

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

    public function provider()
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
