<?php

namespace QuickerFaster\UILibrary\Core\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create default permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage permissions',
            'manage settings',
            'manage modules',
            'view_workflows_overview',
            'view_workflow_definition',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(['view dashboard', 'manage users', 'manage settings', 'view_workflows_overview', 'view_workflow_definition']);

        $companyAdmin = Role::firstOrCreate(['name' => 'company_admin', 'guard_name' => 'web']);
        $companyAdmin->givePermissionTo(['view dashboard', 'manage users', 'manage settings', 'view_workflows_overview', 'view_workflow_definition']);

        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->givePermissionTo(['view dashboard']);

        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $member->givePermissionTo(['view dashboard']);
    }
}
