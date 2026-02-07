<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Omersia\Customer\Models\Customer;

/**
 * @property int $id
 * @property mixed $token
 * @property mixed $customer_id
 * @property mixed $email
 * @property mixed $currency
 * @property mixed $subtotal
 * @property mixed $total_qty
 * @property mixed $status
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CartItem> $items
 * @property-read Customer|null $customer
 */
class Cart extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\CartFactory::new();
    }

    protected $fillable = [
        'token',
        'customer_id',
        'email',
        'currency',
        'subtotal',
        'total_qty',
        'status',
        'metadata',
        'last_activity_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
