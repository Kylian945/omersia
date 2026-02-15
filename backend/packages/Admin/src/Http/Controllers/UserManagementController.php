<?php

declare(strict_types=1);

namespace Omersia\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Omersia\Admin\Http\Requests\AssignRoleRequest;
use Omersia\Admin\Http\Requests\UserRolesUpdateRequest;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->paginate(20);
        $roles = Role::all();

        return view('admin::settings.users.index', compact('users', 'roles'));
    }

    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::all();

        return view()->make('admin::settings.users.edit', compact('user', 'roles'));
    }

    public function updateRoles(UserRolesUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->roles()->sync($validated['roles'] ?? []);

        return redirect()->route('admin.settings.users.index')
            ->with('success', 'Rôles de l\'utilisateur mis à jour avec succès.');
    }

    public function assignRole(AssignRoleRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->roles()->syncWithoutDetaching([$validated['role_id']]);

        return redirect()->back()
            ->with('success', 'Rôle attribué avec succès.');
    }

    public function removeRole(User $user, Role $role): RedirectResponse
    {
        $user->roles()->detach($role->id);

        return redirect()->back()
            ->with('success', 'Rôle retiré avec succès.');
    }
}
