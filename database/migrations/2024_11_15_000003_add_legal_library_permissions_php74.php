<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddLegalLibraryPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create permissions for legal library
        $permissions = [
            'manage legal library',
            'view legal library',
        ];

        foreach ($permissions as $permission) {
            // Check if permission already exists before creating
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }

        // Assign permissions to roles if they exist
        try {
            // Admin should have all permissions
            $adminRole = Role::where('name', 'company')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($permissions);
            }

            // Users/Clients should only view
            $userRoles = ['advocate', 'client', 'co advocate', 'team leader'];
            foreach ($userRoles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo('view legal library');
                }
            }
        } catch (\Exception $e) {
            // Roles might not exist yet, skip
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove permissions
        $permissions = [
            'manage legal library',
            'view legal library',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }
}
