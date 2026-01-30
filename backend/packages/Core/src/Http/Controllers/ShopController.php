<?php

declare(strict_types=1);

namespace Omersia\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Omersia\Core\Models\Shop;
use Omersia\Core\Models\ShopDomain;

class ShopController extends Controller
{
    public function create()
    {
        // Si une boutique existe déjà, on redirige vers le dashboard
        if (Shop::query()->exists()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin::shops.create');
    }

    public function store(Request $request)
    {
        if (Shop::query()->exists()) {
            return redirect()->route('admin.dashboard');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $shop = Shop::create([
            'name' => $request->name,
            'code' => $request->code,
            'default_locale' => 'fr',
            'default_currency_id' => null, // à brancher plus tard sur ta table currencies
        ]);

        ShopDomain::create([
            'shop_id' => $shop->id,
            'domain' => $request->domain,
            'is_primary' => true,
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Boutique créée avec succès.');
    }
}
