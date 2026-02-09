<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Catalog\Http\Requests\TaxRateStoreRequest;
use Omersia\Catalog\Http\Requests\TaxRateUpdateRequest;
use Omersia\Catalog\Http\Requests\TaxZoneStoreRequest;
use Omersia\Catalog\Http\Requests\TaxZoneUpdateRequest;
use Omersia\Catalog\Models\TaxRate;
use Omersia\Catalog\Models\TaxZone;
use Omersia\Core\Models\Shop;

class TaxController extends Controller
{
    /**
     * Display tax zones and rates
     */
    public function index()
    {
        $this->authorize('settings.view');

        $shop = Shop::first();
        $taxZones = TaxZone::where('shop_id', $shop->id)
            ->with('taxRates')
            ->orderBy('priority')
            ->get();

        return view('admin::settings.taxes.index', compact('taxZones', 'shop'));
    }

    /**
     * Show form for creating a new tax zone
     */
    public function createZone()
    {
        $this->authorize('settings.update');

        return view('admin::settings.taxes.create-zone');
    }

    /**
     * Store a new tax zone
     */
    public function storeZone(TaxZoneStoreRequest $request)
    {
        $validated = $request->validated();

        $shop = Shop::first();

        // Process countries_input if countries array is not provided
        $countries = $validated['countries'] ?? null;
        if (! $countries && ! empty($validated['countries_input'])) {
            $countries = array_map(
                fn ($c) => strtoupper(trim($c)),
                array_filter(explode(',', $validated['countries_input']))
            );
        }

        // Process postal_codes_input if postal_codes array is not provided
        $postalCodes = $validated['postal_codes'] ?? null;
        if (! $postalCodes && ! empty($validated['postal_codes_input'])) {
            $postalCodes = array_map(
                fn ($c) => trim($c),
                array_filter(explode(',', $validated['postal_codes_input']))
            );
        }

        $taxZone = TaxZone::create([
            'shop_id' => $shop->id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'countries' => $countries,
            'states' => $validated['states'] ?? null,
            'postal_codes' => $postalCodes,
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.settings.taxes.index')
            ->with('success', 'Zone de taxe créée avec succès.');
    }

    /**
     * Show form for editing a tax zone
     */
    public function editZone(TaxZone $taxZone)
    {
        $this->authorize('settings.update');

        return view('admin::settings.taxes.edit-zone', compact('taxZone'));
    }

    /**
     * Update a tax zone
     */
    public function updateZone(TaxZoneUpdateRequest $request, TaxZone $taxZone)
    {
        $validated = $request->validated();

        // Process countries_input if countries array is not provided
        $countries = $validated['countries'] ?? null;
        if (! $countries && ! empty($validated['countries_input'])) {
            $countries = array_map(
                fn ($c) => strtoupper(trim($c)),
                array_filter(explode(',', $validated['countries_input']))
            );
        }

        // Process postal_codes_input if postal_codes array is not provided
        $postalCodes = $validated['postal_codes'] ?? null;
        if (! $postalCodes && ! empty($validated['postal_codes_input'])) {
            $postalCodes = array_map(
                fn ($c) => trim($c),
                array_filter(explode(',', $validated['postal_codes_input']))
            );
        }

        $taxZone->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'countries' => $countries,
            'states' => $validated['states'] ?? null,
            'postal_codes' => $postalCodes,
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.settings.taxes.index')
            ->with('success', 'Zone de taxe mise à jour avec succès.');
    }

    /**
     * Delete a tax zone
     */
    public function destroyZone(TaxZone $taxZone)
    {
        $this->authorize('settings.update');

        $taxZone->delete();

        return redirect()->route('admin.settings.taxes.index')
            ->with('success', 'Zone de taxe supprimée avec succès.');
    }

    /**
     * Show form for creating a new tax rate
     */
    public function createRate(TaxZone $taxZone)
    {
        $this->authorize('settings.update');

        return view('admin::settings.taxes.create-rate', compact('taxZone'));
    }

    /**
     * Store a new tax rate
     */
    public function storeRate(TaxRateStoreRequest $request, TaxZone $taxZone)
    {
        $validated = $request->validated();

        $taxZone->taxRates()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'rate' => $validated['rate'],
            'compound' => $validated['compound'] ?? false,
            'shipping_taxable' => $validated['shipping_taxable'] ?? true,
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.settings.taxes.index')
            ->with('success', 'Taux de taxe créé avec succès.');
    }

    /**
     * Show form for editing a tax rate
     */
    public function editRate(TaxZone $taxZone, TaxRate $taxRate)
    {
        $this->authorize('settings.update');

        return view('admin::settings.taxes.edit-rate', compact('taxZone', 'taxRate'));
    }

    /**
     * Update a tax rate
     */
    public function updateRate(TaxRateUpdateRequest $request, TaxZone $taxZone, TaxRate $taxRate)
    {
        $validated = $request->validated();

        $taxRate->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'rate' => $validated['rate'],
            'compound' => $validated['compound'] ?? false,
            'shipping_taxable' => $validated['shipping_taxable'] ?? true,
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.settings.taxes.index')
            ->with('success', 'Taux de taxe mis à jour avec succès.');
    }

    /**
     * Delete a tax rate
     */
    public function destroyRate(TaxZone $taxZone, TaxRate $taxRate)
    {
        $this->authorize('settings.update');

        $taxRate->delete();

        return redirect()->route('admin.settings.taxes.index')
            ->with('success', 'Taux de taxe supprimé avec succès.');
    }
}
