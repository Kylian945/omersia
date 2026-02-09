<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\AddressPolicy;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Omersia\Catalog\Models\Order;
use Omersia\Customer\Models\Address;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * SEC-001: Enregistrement des Policies pour prévenir les IDOR
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        Address::class => AddressPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin bypass (seulement pour les User admin, pas les Customer)
        Gate::before(function ($user, $ability) {
            // Ne pas appliquer aux Customer (API storefront)
            if ($user instanceof \Omersia\Customer\Models\Customer) {
                return null; // Continuer vers la Policy normale
            }

            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        // Accès admin de base (seulement pour les User admin)
        Gate::define('access-admin', function ($user) {
            if (! method_exists($user, 'roles')) {
                return false;
            }
            return $user->roles()->exists();
        });

        // Permissions dynamiques (seulement si la table existe)
        try {
            if (DB::getSchemaBuilder()->hasTable('permissions')) {
                $permissions = DB::table('permissions')->pluck('name');
                foreach ($permissions as $permission) {
                    Gate::define($permission, function ($user) use ($permission) {
                        if (! method_exists($user, 'hasPermission')) {
                            return false;
                        }
                        return $user->hasPermission($permission);
                    });
                }
            }
        } catch (\Exception) {
            // Ignorer les erreurs lors des migrations ou si la DB n'est pas encore configurée
        }
    }
}
