<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateContentManagerRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the 'content_manager' role for superadmin
        $role = Role::firstOrCreate(
            ['name' => 'content_manager', 'guard_name' => 'web'],
            ['description' => 'Content Manager - Manages Legal Library, Document Templates, and Fiscal Resources']
        );

        // Define permissions for content manager
        $permissions = [
            // Legal Library Permissions
            'view legal-library',
            'manage legal-library',
            'create legal-library',
            'edit legal-library',
            'delete legal-library',

            // Document Template Permissions  
            'view document-template',
            'manage document-template',
            'create document-template',
            'edit document-template',
            'delete document-template',

            // Fiscal Resources Permissions
            'view fiscal-resources',
            'manage fiscal-resources',
            'create fiscal-resources',
            'edit fiscal-resources',
            'delete fiscal-resources',

            // Mobile App Access
            'view mobile-app',
            'manage mobile-app',
        ];

        // Ensure all permissions exist and assign to role
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
            
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        echo "Content Manager role created with all permissions!\n";
    }
}
