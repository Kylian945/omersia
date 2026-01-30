<?php

declare(strict_types=1);

namespace Omersia\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Customer\Database\Factories\AddressFactory::new();
    }

    protected $table = 'addresses';

    protected $fillable = [
        'customer_id',
        'label',
        'line1',
        'line2',
        'postcode',
        'city',
        'state',
        'country',
        'phone',
        'is_default_billing',
        'is_default_shipping',
    ];

    protected $casts = [
        'is_default_billing' => 'boolean',
        'is_default_shipping' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
