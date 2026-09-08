<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $modules = [
            ['module_name' => 'ads', 'is_custom_permission' => 0],
            ['module_name' => 'vastads', 'is_custom_permission' => 0],
            ['module_name' => 'customads', 'is_custom_permission' => 0],
        ];

        foreach ($modules as $module) {
            $permissions = ['view', 'add', 'edit', 'delete', 'restore', 'force_delete'];
            $module_name = strtolower(str_replace(' ', '_', $module['module_name']));

            $newPermissions = [];

            foreach ($permissions as $value) {
                $permission_name = $value . '_' . $module_name;

                $permission = Permission::firstOrCreate([
                    'name'      => $permission_name,
                    'is_fixed'  => true,
                ]);

                $newPermissions[] = $permission; // collect only created ones
            }

            // Assign only these new permissions
            $admin = Role::where('name', 'admin')->first();
            $demo_admin = Role::where('name', 'demo_admin')->first();

            if ($admin) {
                $admin->givePermissionTo($newPermissions);
            }
            if ($demo_admin) {
                $demo_admin->givePermissionTo($newPermissions);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        {
            $modules = ['ads', 'vastads', 'customads'];
            $permissionsList = [];

            foreach ($modules as $module) {
                $permissions = ['view', 'add', 'edit', 'delete', 'restore', 'force_delete'];
                foreach ($permissions as $value) {
                    $permissionName = $value . '_' . strtolower($module);

                    // collect the names for revoke
                    $permissionsList[] = $permissionName;

                    // delete from DB
                    Permission::where('name', $permissionName)->delete();
                }
            }

            $admin = Role::where('name', 'admin')->first();
            $demo_admin = Role::where('name', 'demo_admin')->first();

            if ($admin) {
                $admin->revokePermissionTo($permissionsList);
            }
            if ($demo_admin) {
                $demo_admin->revokePermissionTo($permissionsList);
            }
        }
    }
};
