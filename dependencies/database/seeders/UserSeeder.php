<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $memberUser = User::create([
            'id' => 1,
            'name' => 'admin',
            'email' => 'admin@softui.com',
            'password' => Hash::make('secret'),
        ]);

        $superAdmin = User::create([
            'id' => 2,
            'name' => 'super admin',
            'email' => 'superadmin@quickerfaster.com',
            'password' => Hash::make('ChangeMe@12345'),
        ]);

        $companyAdmin = User::create([
            'id' => 3,
            'name' => 'company admin',
            'email' => 'gmadmin@agriwatts.ng',
            'password' => Hash::make('Test@12345'),
        ]);

        $superAdminRole = Role::findByName('super_admin', 'web');
        $companyAdminRole = Role::findByName('company_admin', 'web');
        $memberRole = Role::findByName('member', 'web');

        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole);
        } else {
            throw new \Exception('Role "super_admin" not found. Did you run RoleSeeder?');
        }

        if ($companyAdminRole) {
            $companyAdmin->assignRole($companyAdminRole);
        } else {
            throw new \Exception('Role "company_admin" not found. Did you run RoleSeeder?');
        }

        if ($memberRole) {
            $memberUser->assignRole($memberRole);
        } else {
            throw new \Exception('Role "member" not found. Did you run RoleSeeder?');
        }
    }
}
