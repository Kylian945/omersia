<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return Cache::remember(
            "user.{$this->id}.permission.{$permission}",
            3600,
            fn () => $this->roles()
                ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
                ->exists()
        );
    }

    public function getAllPermissions(): array
    {
        return Cache::remember(
            "user.{$this->id}.permissions",
            3600,
            fn () => $this->roles()
                ->with('permissions')
                ->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->unique()
                ->values()
                ->toArray()
        );
    }

    public function isAdmin(): bool
    {
        return $this->roles()->exists();
    }
}
