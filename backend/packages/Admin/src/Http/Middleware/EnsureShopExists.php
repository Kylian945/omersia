<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Omersia\Core\Models\Shop;

class EnsureShopExists
{
    public function handle(Request $request, Closure $next)
    {
        // Si au moins une boutique existe, on laisse passer
        if (Shop::query()->exists()) {
            return $next($request);
        }

        // Si pas de boutique :
        // - on ne boucle pas sur la page de création elle-même
        // - on limite aux admins
        if ($request->user() && method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()) {
            if (! $request->routeIs('admin.shops.create') && ! $request->routeIs('admin.shops.store')) {
                return redirect()->route('admin.shops.create');
            }

            return $next($request);
        }

        // Pas admin ou pas connecté => on bloque
        abort(403, 'Aucune boutique configurée. Contactez un administrateur.');
    }
}
