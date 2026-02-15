<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Omersia\Admin\Http\Requests\RoleStoreRequest;
use Omersia\Admin\Http\Requests\RoleUpdateRequest;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('users', 'permissions')->get();
        $permissions = Permission::all()->groupBy('group');

        return view('admin::settings.roles.index', compact('roles', 'permissions'));
    }

    public function create(): View
    {
        $permissions = Permission::all()->groupBy('group');

        return view()->make('admin::settings.roles.create', compact('permissions'));
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('admin.settings.roles.index')
            ->with('success', 'Rôle créé avec succès.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::all()->groupBy('group');

        return view('admin::settings.roles.edit', compact('role', 'permissions'));
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.settings.roles.index')
            ->with('success', 'Rôle mis à jour avec succès.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.settings.roles.index')
                ->with('error', 'Impossible de supprimer ce rôle car il est assigné à des utilisateurs.');
        }

        $role->delete();

        return redirect()->route('admin.settings.roles.index')
            ->with('success', 'Rôle supprimé avec succès.');
    }
}
