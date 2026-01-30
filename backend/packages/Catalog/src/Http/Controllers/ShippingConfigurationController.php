<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Catalog\Http\Requests\ShippingRateStoreRequest;
use Omersia\Catalog\Http\Requests\ShippingRateUpdateRequest;
use Omersia\Catalog\Http\Requests\ShippingZoneStoreRequest;
use Omersia\Catalog\Http\Requests\ShippingZoneUpdateRequest;
use Omersia\Catalog\Models\ShippingMethod;
use Omersia\Catalog\Models\ShippingRate;
use Omersia\Catalog\Models\ShippingZone;

class ShippingConfigurationController extends Controller
{
    /**
     * Affiche la configuration avancée d'une méthode de livraison
     */
    public function show(ShippingMethod $shippingMethod)
    {
        $this->authorize('shipping.configure');

        $method = $shippingMethod->load(['zones.rates', 'rates' => function ($query) {
            $query->whereNull('shipping_zone_id')->orderBy('priority', 'desc');
        }]);

        return view('admin::settings.shipping_methods.configure', compact('method'));
    }

    /**
     * Met à jour les options générales de tarification
     */
    public function updateOptions(Request $request, ShippingMethod $shippingMethod)
    {
        $this->authorize('shipping.configure');

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'use_weight_based_pricing' => ['boolean'],
            'use_zone_based_pricing' => ['boolean'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        $shippingMethod->update($data);

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Options de tarification mises à jour.');
    }

    /**
     * Crée une nouvelle zone
     */
    public function storeZone(ShippingZoneStoreRequest $request, ShippingMethod $shippingMethod)
    {
        $data = $request->validated();

        // Transformer countries_input (string) en tableau
        $countries = null;
        if (! empty($data['countries_input'])) {
            $countriesInput = trim($data['countries_input']);

            // Si c'est "*", on laisse null pour "tous les pays"
            if ($countriesInput !== '*') {
                // Séparer par virgule, trim et uppercase
                $countries = array_map(
                    fn ($code) => strtoupper(trim($code)),
                    explode(',', $countriesInput)
                );
                // Filtrer les codes vides
                $countries = array_filter($countries);
            }
        }

        $shippingMethod->zones()->create([
            'name' => $data['name'],
            'countries' => $countries,
            'postal_codes' => $data['postal_codes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Zone créée avec succès.');
    }

    /**
     * Met à jour une zone
     */
    public function updateZone(ShippingZoneUpdateRequest $request, ShippingMethod $shippingMethod, ShippingZone $zone)
    {
        $data = $request->validated();

        // Transformer countries_input (string) en tableau
        $countries = null;
        if (! empty($data['countries_input'])) {
            $countriesInput = trim($data['countries_input']);

            // Si c'est "*", on laisse null pour "tous les pays"
            if ($countriesInput !== '*') {
                // Séparer par virgule, trim et uppercase
                $countries = array_map(
                    fn ($code) => strtoupper(trim($code)),
                    explode(',', $countriesInput)
                );
                // Filtrer les codes vides
                $countries = array_filter($countries);
            }
        }

        $zone->update([
            'name' => $data['name'],
            'countries' => $countries,
            'postal_codes' => $data['postal_codes'] ?? null,
            'is_active' => $data['is_active'] ?? $zone->is_active,
        ]);

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Zone mise à jour.');
    }

    /**
     * Supprime une zone
     */
    public function destroyZone(ShippingMethod $shippingMethod, ShippingZone $zone)
    {
        $this->authorize('shipping.configure');

        $zone->delete();

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Zone supprimée.');
    }

    /**
     * Crée un nouveau tarif
     */
    public function storeRate(ShippingRateStoreRequest $request, ShippingMethod $shippingMethod)
    {
        $data = $request->validated();

        // Vérifier que la zone appartient bien à cette méthode
        if (! empty($data['shipping_zone_id'])) {
            $zone = ShippingZone::where('id', $data['shipping_zone_id'])
                ->where('shipping_method_id', $shippingMethod->id)
                ->firstOrFail();
        }

        $shippingMethod->rates()->create($data);

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Tarif créé avec succès.');
    }

    /**
     * Met à jour un tarif
     */
    public function updateRate(ShippingRateUpdateRequest $request, ShippingMethod $shippingMethod, ShippingRate $rate)
    {
        $data = $request->validated();

        $rate->update($data);

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Tarif mis à jour avec succès.');
    }

    /**
     * Supprime un tarif
     */
    public function destroyRate(ShippingMethod $shippingMethod, ShippingRate $rate)
    {
        $this->authorize('shipping.configure');

        $rate->delete();

        return redirect()
            ->route('admin.settings.shipping_methods.configure', $shippingMethod)
            ->with('success', 'Tarif supprimé.');
    }
}
