<?php

declare(strict_types=1);

namespace Omersia\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Omersia\Catalog\Http\Requests\ShippingMethodStoreRequest;
use Omersia\Catalog\Http\Requests\ShippingMethodUpdateRequest;
use Omersia\Catalog\Models\ShippingMethod;

class ShippingMethodController extends Controller
{
    public function index()
    {
        $this->authorize('shipping.view');

        $methods = ShippingMethod::orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin::settings.shipping_methods.index', compact('methods'));
    }

    public function create()
    {
        $this->authorize('shipping.configure');

        $method = new ShippingMethod;

        return view('admin::settings.shipping_methods.create', compact('method'));
    }

    public function store(ShippingMethodStoreRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? false;

        ShippingMethod::create($validated);

        return redirect()
            ->route('admin.settings.shipping_methods.index')
            ->with('success', 'Méthode de livraison créée avec succès.');
    }

    public function edit(ShippingMethod $shippingMethod)
    {
        $this->authorize('shipping.configure');

        $method = $shippingMethod;

        return view('admin::settings.shipping_methods.edit', compact('method'));
    }

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? false;

        $shippingMethod->update($validated);

        return redirect()
            ->route('admin.settings.shipping_methods.index')
            ->with('success', 'Méthode de livraison mise à jour avec succès.');
    }

    public function destroy(ShippingMethod $shippingMethod)
    {
        $this->authorize('shipping.configure');

        $shippingMethod->delete();

        return redirect()
            ->route('admin.settings.shipping_methods.index')
            ->with('success', 'Méthode de livraison supprimée avec succès.');
    }
}
