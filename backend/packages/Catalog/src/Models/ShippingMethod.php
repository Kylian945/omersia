<?php

declare(strict_types=1);

namespace Omersia\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property mixed $code
 * @property mixed $name
 * @property mixed $description
 * @property float|int|string|null $price
 * @property mixed $delivery_time
 * @property bool $is_active
 * @property bool $use_weight_based_pricing
 * @property bool $use_zone_based_pricing
 * @property float|int|string|null $free_shipping_threshold
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Order> $orders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShippingZone> $zones
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShippingRate> $rates
 */
class ShippingMethod extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Omersia\Catalog\Database\Factories\ShippingMethodFactory::new();
    }

    protected $table = 'shipping_methods';

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'delivery_time',
        'is_active',
        'use_weight_based_pricing',
        'use_zone_based_pricing',
        'free_shipping_threshold',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_weight_based_pricing' => 'boolean',
        'use_zone_based_pricing' => 'boolean',
        'free_shipping_threshold' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(ShippingZone::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Normalise un code pays (peut recevoir nom complet ou code ISO)
     */
    protected function normalizeCountryCode(?string $country): ?string
    {
        if (! $country) {
            return null;
        }

        // Mapping des noms de pays vers codes ISO
        $countryMap = [
            'france' => 'FR',
            'belgique' => 'BE',
            'belgium' => 'BE',
            'suisse' => 'CH',
            'switzerland' => 'CH',
            'allemagne' => 'DE',
            'germany' => 'DE',
            'italie' => 'IT',
            'italy' => 'IT',
            'espagne' => 'ES',
            'spain' => 'ES',
            'portugal' => 'PT',
            'pays-bas' => 'NL',
            'netherlands' => 'NL',
            'royaume-uni' => 'GB',
            'united kingdom' => 'GB',
        ];

        $normalized = strtolower(trim($country));

        // Si c'est déjà un code ISO (2 lettres), le retourner en majuscules
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        // Sinon chercher dans le mapping
        return $countryMap[$normalized] ?? strtoupper($country);
    }

    /**
     * Calcule le prix de livraison pour un panier donné
     *
     * @param  float  $cartTotal  Montant total du panier
     * @param  float|null  $weight  Poids total en kg
     * @param  string|null  $countryCode  Code pays (ex: FR) ou nom (ex: France)
     * @param  string|null  $postalCode  Code postal
     * @return float Prix de la livraison
     */
    public function calculatePrice(
        float $cartTotal,
        ?float $weight = null,
        ?string $countryCode = null,
        ?string $postalCode = null
    ): float {
        // Normaliser le code pays
        $countryCode = $this->normalizeCountryCode($countryCode);

        // Livraison gratuite si seuil atteint
        if ($this->free_shipping_threshold && $cartTotal >= $this->free_shipping_threshold) {
            return 0;
        }

        // Si pas de tarification avancée, retourner le prix de base
        if (! $this->use_weight_based_pricing && ! $this->use_zone_based_pricing) {
            return (float) $this->price;
        }

        // Trouver la zone applicable
        $zone = null;
        if ($this->use_zone_based_pricing && ($countryCode || $postalCode)) {
            $zone = $this->zones()
                ->where('is_active', true)
                ->get()
                ->first(function ($z) use ($countryCode, $postalCode) {
                    $matchesCountry = ! $countryCode || $z->matchesCountry($countryCode);
                    $matchesPostal = ! $postalCode || $z->matchesPostalCode($postalCode);

                    return $matchesCountry && $matchesPostal;
                });
        }

        // Trouver le tarif applicable
        $rate = $this->rates()
            ->forWeightAndZone($weight, $zone?->id)
            ->first();

        if ($rate) {
            return (float) $rate->price;
        }

        // Fallback sur le prix de base
        return (float) $this->price;
    }
}
