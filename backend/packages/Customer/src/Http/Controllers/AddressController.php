<?php

declare(strict_types=1);

namespace Omersia\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Customer\Models\Address;
use Omersia\Customer\Models\Customer;

class AddressController extends Controller
{
    public function index(Customer $customer)
    {
        $this->authorize('customers.view');

        $addresses = $customer->addresses()
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderBy('label')
            ->get();

        return view('admin::customers.addresses.index', compact('customer', 'addresses'));
    }

    public function create(Customer $customer)
    {
        $this->authorize('customers.update');

        $address = new Address;

        return view('admin::customers.addresses.create', compact('customer', 'address'));
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorize('customers.update');

        $data = $this->validateAddress($request);

        if (! empty($data['is_default_shipping'])) {
            $customer->addresses()->update(['is_default_shipping' => false]);
        }

        if (! empty($data['is_default_billing'])) {
            $customer->addresses()->update(['is_default_billing' => false]);
        }

        $customer->addresses()->create($data);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Adresse créée avec succès.');
    }

    public function edit(Customer $customer, Address $address)
    {
        $this->authorize('customers.update');
        $this->authorizeAddress($customer, $address);

        return view('admin::customers.addresses.edit', compact('customer', 'address'));
    }

    public function update(Request $request, Customer $customer, Address $address)
    {
        $this->authorize('customers.update');
        $this->authorizeAddress($customer, $address);

        $data = $this->validateAddress($request);

        if (! empty($data['is_default_shipping'])) {
            $customer->addresses()->update(['is_default_shipping' => false]);
        }

        if (! empty($data['is_default_billing'])) {
            $customer->addresses()->update(['is_default_billing' => false]);
        }

        $address->update($data);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Adresse mise à jour avec succès.');
    }

    public function destroy(Customer $customer, Address $address)
    {
        $this->authorize('customers.update');
        $this->authorizeAddress($customer, $address);

        $address->delete();

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Adresse supprimée.');
    }

    public function setDefaultShipping(Customer $customer, Address $address)
    {
        $this->authorize('customers.update');
        $this->authorizeAddress($customer, $address);

        $customer->addresses()->update(['is_default_shipping' => false]);
        $address->update(['is_default_shipping' => true]);

        return back()->with('success', 'Adresse définie comme adresse de livraison par défaut.');
    }

    public function setDefaultBilling(Customer $customer, Address $address)
    {
        $this->authorize('customers.update');
        $this->authorizeAddress($customer, $address);

        $customer->addresses()->update(['is_default_billing' => false]);
        $address->update(['is_default_billing' => true]);

        return back()->with('success', 'Adresse définie comme adresse de facturation par défaut.');
    }

    protected function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'required|string|size:2',
            'phone' => 'nullable|string|max:30',
            'is_default_billing' => 'sometimes|boolean',
            'is_default_shipping' => 'sometimes|boolean',
        ]);
    }

    protected function authorizeAddress(Customer $customer, Address $address): void
    {
        if ($address->customer_id !== $customer->id) {
            abort(403);
        }
    }
}
