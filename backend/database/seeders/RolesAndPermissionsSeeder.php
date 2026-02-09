<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions par groupe
        $permissions = [
            'products' => ['view', 'create', 'update', 'delete'],
            'categories' => ['view', 'create', 'update', 'delete'],
            'orders' => ['view', 'update', 'delete', 'export'],
            'customers' => ['view', 'create', 'update', 'delete', 'export'],
            'discounts' => ['view', 'create', 'update', 'delete'],
            'settings' => ['view', 'update'],
            'payments' => ['view', 'configure'],
            'shipping' => ['view', 'configure'],
            'themes' => ['view', 'update', 'upload'],
            'pages' => ['view', 'create', 'update', 'delete'],
            'media' => ['view', 'upload', 'delete'],
            'modules' => ['view', 'install', 'configure', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
        ];

        // Permissions spéciales (super-admin uniquement)
        $specialPermissions = [
            [
                'name' => 'manage-roles',
                'display_name' => 'Gérer les rôles et permissions',
                'group' => 'Administration',
            ],
        ];

        foreach ($permissions as $group => $actions) {
            foreach ($actions as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => "{$group}.{$action}"],
                    [
                        'display_name' => ucfirst($action).' '.ucfirst($group),
                        'group' => $group,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Ajouter les permissions spéciales
        foreach ($specialPermissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'group' => $permission['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Créer ou récupérer les rôles
        $superAdminRole = DB::table('roles')->where('name', 'super-admin')->first();
        if (! $superAdminRole) {
            $superAdminId = DB::table('roles')->insertGetId([
                'name' => 'super-admin',
                'display_name' => 'Super Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $superAdminId = $superAdminRole->id;
        }

        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        if (! $managerRole) {
            $managerId = DB::table('roles')->insertGetId([
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Gestion des produits, commandes et clients',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $managerId = $managerRole->id;
        }

        $editorRole = DB::table('roles')->where('name', 'editor')->first();
        if (! $editorRole) {
            $editorId = DB::table('roles')->insertGetId([
                'name' => 'editor',
                'display_name' => 'Éditeur',
                'description' => 'Gestion du contenu et des pages',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $editorId = $editorRole->id;
        }

        // Supprimer les anciennes associations pour ces rôles
        DB::table('role_permissions')->whereIn('role_id', [$superAdminId, $managerId, $editorId])->delete();

        // Super Admin = toutes les permissions
        $allPermissions = DB::table('permissions')->pluck('id');
        foreach ($allPermissions as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $superAdminId,
                'permission_id' => $permId,
            ]);
        }

        // Manager = produits, orders, customers, discounts
        $managerPerms = DB::table('permissions')
            ->whereIn('group', ['products', 'categories', 'orders', 'customers', 'discounts', 'media'])
            ->pluck('id');
        foreach ($managerPerms as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $managerId,
                'permission_id' => $permId,
            ]);
        }

        // Editor = pages, themes, media
        $editorPerms = DB::table('permissions')
            ->whereIn('group', ['pages', 'themes', 'media'])
            ->pluck('id');
        foreach ($editorPerms as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $editorId,
                'permission_id' => $permId,
            ]);
        }
    }
}
