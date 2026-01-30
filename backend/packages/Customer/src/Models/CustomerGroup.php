<?php

declare(strict_types=1);

namespace Omersia\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Omersia\Core\Models\Shop;

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
