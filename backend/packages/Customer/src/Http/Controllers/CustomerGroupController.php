<?php

declare(strict_types=1);

namespace Omersia\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Omersia\Customer\Http\Requests\CustomerGroupStoreRequest;
use Omersia\Customer\Http\Requests\CustomerGroupUpdateRequest;
use Omersia\Customer\Models\Customer;
use Omersia\Customer\Models\CustomerGroup;

class CustomerGroupController extends Controller
{
    public function index(): View
    {
        $this->authorize('customers.view');

        $groups = CustomerGroup::where('shop_id', 1)
            ->orderBy('name')
            ->paginate(20);

        return view('admin::customer-groups.index', compact('groups'));
    }

    public function create(): View
    {
        $this->authorize('customers.create');

        $customers = Customer::orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        return view('admin::customer-groups.create', compact('customers'));
    }

    public function store(CustomerGroupStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $group = CustomerGroup::create([
            'shop_id' => 1,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ]);

        $group->customers()->sync($data['customer_ids'] ?? []);

        if ($group->is_default) {
            CustomerGroup::where('shop_id', 1)
                ->where('id', '!=', $group->id)
                ->update(['is_default' => false]);
        }

        return redirect()
            ->route('customer-groups.index')
            ->with('success', 'Groupe client créé.');
    }

    public function edit(CustomerGroup $customerGroup): View
    {
        $this->authorize('customers.update');
        $this->authorizeGroup($customerGroup);

        $customers = Customer::orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        return view('admin::customer-groups.edit', [
            'group' => $customerGroup,
            'customers' => $customers,
        ]);
    }

    public function update(CustomerGroupUpdateRequest $request, CustomerGroup $customerGroup): RedirectResponse
    {
        $this->authorizeGroup($customerGroup);

        $data = $request->validated();

        $customerGroup->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ]);

        $customerGroup->customers()->sync($data['customer_ids'] ?? []);

        if ($customerGroup->is_default) {
            CustomerGroup::where('shop_id', 1)
                ->where('id', '!=', $customerGroup->id)
                ->update(['is_default' => false]);
        }

        return redirect()
            ->route('customer-groups.index')
            ->with('success', 'Groupe client mis à jour.');
    }

    public function destroy(CustomerGroup $customerGroup): RedirectResponse
    {
        $this->authorize('customers.delete');
        $this->authorizeGroup($customerGroup);

        $customerGroup->customers()->detach();

        $customerGroup->delete();

        return redirect()
            ->route('customer-groups.index')
            ->with('success', 'Groupe client supprimé.');
    }

    protected function authorizeGroup(CustomerGroup $group): void
    {
        if ($group->shop_id !== 1) {
            abort(404);
        }
    }
}
