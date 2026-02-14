<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::withCount('roles')->get()->groupBy('group');

        return view('admin::settings.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view()->make('admin::settings.permissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        Permission::create($validated);

        return redirect()->route('admin.settings.permissions.index')
            ->with('success', 'Permission créée avec succès.');
    }

    public function edit(Permission $permission): View
    {
        return view('admin::settings.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id,
            'display_name' => 'required|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        $permission->update($validated);

        return redirect()->route('admin.settings.permissions.index')
            ->with('success', 'Permission mise à jour avec succès.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->roles()->count() > 0) {
            return redirect()->route('admin.settings.permissions.index')
                ->with('error', 'Impossible de supprimer cette permission car elle est assignée à des rôles.');
        }

        $permission->delete();

        return redirect()->route('admin.settings.permissions.index')
            ->with('success', 'Permission supprimée avec succès.');
    }
}
