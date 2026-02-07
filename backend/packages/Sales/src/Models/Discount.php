<?php

declare(strict_types=1);

namespace Omersia\Sales\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\Product;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;

/**
 * @property int $id
 * @property mixed $shop_id
 * @property mixed $name
 * @property mixed $type
 * @property mixed $method
 * @property mixed $code
 * @property mixed $value_type
 * @property mixed $value
 * @property mixed $min_subtotal
 * @property mixed $min_quantity
 * @property mixed $buy_quantity
 * @property mixed $get_quantity
 * @property mixed $buy_applies_to
 * @property mixed $get_applies_to
 * @property bool $get_is_free
 * @property mixed $get_discount_value
 * @property mixed $product_scope
 * @property mixed $customer_selection
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property mixed $usage_limit
 * @property mixed $usage_limit_per_customer
 * @property bool $applies_once_per_order
 * @property bool $combines_with_product_discounts
 * @property bool $combines_with_order_discounts
 * @property bool $combines_with_shipping_discounts
 * @property mixed $priority
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $collections
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CustomerGroup> $customerGroups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Customer> $customers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DiscountUsage> $usages
 */
class Discount extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Sales\Database\Factories\DiscountFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'name',
        'type',
        'method',
        'code',
        'value_type',
        'value',
        'min_subtotal',
        'min_quantity',
        'buy_quantity',
        'get_quantity',
        'buy_applies_to',
        'get_applies_to',
        'get_is_free',
        'get_discount_value',
        'product_scope',
        'customer_selection',
        'is_active',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_limit_per_customer',
        'applies_once_per_order',
        'combines_with_product_discounts',
        'combines_with_order_discounts',
        'combines_with_shipping_discounts',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'get_is_free' => 'boolean',
        'applies_once_per_order' => 'boolean',
        'combines_with_product_discounts' => 'boolean',
        'combines_with_order_discounts' => 'boolean',
        'combines_with_shipping_discounts' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Relations
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_products');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'discount_categories');
    }

    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'discount_customer_groups');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'discount_customers');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }
}
