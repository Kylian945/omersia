<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property mixed $shipping_method_id
 * @property mixed $name
 * @property array<string, mixed>|null $countries
 * @property mixed $postal_codes
 * @property bool $is_active
 * @property-read ShippingMethod|null $shippingMethod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShippingRate> $rates
 */
class ShippingZone extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'name',
        'countries',
        'postal_codes',
        'is_active',
    ];

    protected $casts = [
        'countries' => 'array',
        'is_active' => 'boolean',
    ];

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Vérifie si cette zone s'applique à un code postal donné
     */
    public function matchesPostalCode(string $postalCode): bool
    {
        if (empty($this->postal_codes)) {
            return true; // Pas de restriction
        }

        $patterns = explode(',', $this->postal_codes);

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);

            // Support pour les patterns type "75*", "75000-75999"
            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                if (str_starts_with($postalCode, $prefix)) {
                    return true;
                }
            } elseif (str_contains($pattern, '-')) {
                [$min, $max] = explode('-', $pattern);
                if ($postalCode >= $min && $postalCode <= $max) {
                    return true;
                }
            } elseif ($pattern === $postalCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si cette zone s'applique à un pays donné
     */
    public function matchesCountry(string $countryCode): bool
    {
        if (empty($this->countries)) {
            return true; // Pas de restriction
        }

        return in_array(strtoupper($countryCode), $this->countries);
    }
}
