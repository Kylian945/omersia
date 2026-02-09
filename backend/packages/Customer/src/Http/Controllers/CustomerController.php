<?php

declare(strict_types=1);

namespace Omersia\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Customer\Models\Customer;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('customers.view');

        $search = $request->get('search');

        $customers = Customer::when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin::customers.index', compact('customers', 'search'));
    }

    public function show(Customer $customer)
    {
        $this->authorize('customers.view');

        $addresses = $customer->addresses()
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderBy('label')
            ->get();

        $orders = $customer->orders()->get();

        return view('admin::customers.show', compact('customer', 'addresses', 'orders'));
    }
}
