<?php

declare(strict_types=1);

namespace Omersia\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Customer;

/**
 * @property int $id
 * @property mixed $discount_id
 * @property mixed $order_id
 * @property mixed $customer_id
 * @property mixed $usage_count
 * @property-read Discount|null $discount
 * @property-read Order|null $order
 * @property-read Customer|null $customer
 */
class DiscountUsage extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Sales\Database\Factories\DiscountUsageFactory::new();
    }

    protected $fillable = [
        'discount_id',
        'order_id',
        'customer_id',
        'usage_count',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
