<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Omersia\Core\Models\Shop;

class TaxZone extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\TaxZoneFactory::new();
    }

    protected $fillable = [
        'shop_id',
        'name',
        'code',
        'description',
        'countries',
        'states',
        'postal_codes',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'countries' => 'array',
        'states' => 'array',
        'postal_codes' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the shop that owns the tax zone
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the tax rates for this zone
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class)->orderBy('priority');
    }

    /**
     * Get active tax rates for this zone
     */
    public function activeTaxRates(): HasMany
    {
        return $this->taxRates()->where('is_active', true);
    }

    /**
     * Check if this zone matches the given address
     *
     * @param  array  $address  ['country' => 'FR', 'state' => null, 'postal_code' => '75001']
     */
    public function matchesAddress(array $address): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $country = $address['country'] ?? null;
        $state = $address['state'] ?? null;
        $postalCode = $address['postal_code'] ?? null;

        // Check country match
        if ($this->countries && ! in_array($country, $this->countries)) {
            return false;
        }

        // Check state match if specified
        if ($this->states && $country) {
            $stateList = $this->states[$country] ?? null;
            if ($stateList && ! in_array($state, $stateList)) {
                return false;
            }
        }

        // Check postal code match if specified
        if ($this->postal_codes && $postalCode) {
            $matches = false;
            foreach ($this->postal_codes as $pattern) {
                // Support wildcard patterns like "75*"
                $pattern = str_replace('*', '.*', $pattern);
                if (preg_match('/^'.$pattern.'$/', $postalCode)) {
                    $matches = true;
                    break;
                }
            }
            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate total tax rate for this zone
     */
    public function getTotalTaxRate(bool $includeShipping = false): float
    {
        $rates = $this->activeTaxRates;

        if ($includeShipping) {
            $rates = $rates->where('shipping_taxable', true);
        }

        return $rates->sum('rate');
    }
}
