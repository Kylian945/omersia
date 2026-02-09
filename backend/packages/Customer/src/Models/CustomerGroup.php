<?php

declare(strict_types=1);

namespace Omersia\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Omersia\Core\Models\Shop;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $name
 * @property mixed $code
 * @property mixed $description
 * @property bool $is_default
 * @property-read Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Customer> $customers
 */
class CustomerGroup extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Customer\Database\Factories\CustomerGroupFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'name',
        'code',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_group_customer',
            'customer_group_id',
            'customer_id'
        );
    }
}
