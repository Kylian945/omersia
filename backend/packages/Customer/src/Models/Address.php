<?php

declare(strict_types=1);

namespace Omersia\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property mixed $customer_id
 * @property mixed $label
 * @property mixed $line1
 * @property mixed $line2
 * @property mixed $postcode
 * @property mixed $city
 * @property mixed $state
 * @property mixed $country
 * @property mixed $type
 * @property mixed $user_id
 * @property mixed $phone
 * @property bool $is_default_billing
 * @property bool $is_default_shipping
 * @property-read Customer|null $customer
 */
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
