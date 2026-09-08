<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure permissions cache is cleared
        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $moduleName = 'onboarding';
        $baseActions = ['view', 'add', 'edit', 'delete', 'restore', 'force_delete'];

        $createdPermissions = [];

        foreach ($baseActions as $action) {
            $name = strtolower($action . '_' . $moduleName);
            $perm = Permission::firstOrCreate(['name' => $name], ['guard_name' => 'web', 'is_fixed' => true]);
            $createdPermissions[] = $perm;
        }

        // Assign to roles admin and demo_admin
        $roles = Role::whereIn('name', ['admin', 'demo_admin'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($createdPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions from roles and delete permissions
        $moduleName = 'onboarding';
        $baseActions = ['view', 'add', 'edit', 'delete', 'restore', 'force_delete'];

        $permissionNames = array_map(function ($action) use ($moduleName) {
            return strtolower($action . '_' . $moduleName);
        }, $baseActions);

        $permissions = Permission::whereIn('name', $permissionNames)->get();

        $roles = Role::whereIn('name', ['admin', 'demo_admin'])->get();
        foreach ($roles as $role) {
            foreach ($permissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        Permission::whereIn('name', $permissionNames)->delete();

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
};


