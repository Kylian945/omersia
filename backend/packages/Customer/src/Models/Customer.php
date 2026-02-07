<?php

declare(strict_types=1);

namespace Omersia\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Omersia\Catalog\Models\Order;
use Omersia\Core\Models\Shop;
use Omersia\Gdpr\Models\CookieConsent;
use Omersia\Gdpr\Models\DataRequest;

/**
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property bool $is_active
 * @property string $password
 * @property string|null $fullname
 * @property-read Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CustomerGroup> $groups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Order> $orders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Address> $addresses
 * @property-read Address|null $defaultBillingAddress
 * @property-read Address|null $defaultShippingAddress
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Omersia\Catalog\Models\Cart> $carts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CookieConsent> $cookieConsents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DataRequest> $dataRequests
 */
class Customer extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;

    // On réutilise la table users comme source clients
    use Notifiable;

    protected static function newFactory()
    {
        return \Omersia\Customer\Database\Factories\CustomerFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'firstname',
        'lastname',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerGroup::class,
            'customer_group_customer',
            'customer_id',
            'customer_group_id'
        );
    }

    /**
     * Nom complet pratique.
     */
    public function getFullnameAttribute(): string
    {
        $full = trim(($this->firstname ?? '').' '.($this->lastname ?? ''));

        return $full !== '' ? $full : $this->email;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id')->where('status', '!=', 'draft');
    }

    public function ordersTotal(): float
    {
        return (float) $this->orders()->sum('total');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function defaultBillingAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'customer_id')->where('is_default_billing', true);
    }

    public function defaultShippingAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'customer_id')->where('is_default_shipping', true);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(\Omersia\Catalog\Models\Cart::class, 'customer_id');
    }

    public function cookieConsents(): HasMany
    {
        return $this->hasMany(CookieConsent::class, 'customer_id');
    }

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'customer_id');
    }
}
