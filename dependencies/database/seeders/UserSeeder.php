<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // Since it extednds App\Modules\Admin\Models\User
use App\Modules\Admin\Models\Role;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $clockingAccess = User::create([ // For clocking device authentication 
            'id' => 1,
            'name' => 'admin',
            'email' => 'admin@softui.com',
            'password' => Hash::make('secret'),
        ]);

        $superAdmin = User::create([ 
            'id' => 2,
            'name' => 'super admin',
            'email' => 'superadmin@quickerfaster.com',
            'password' => Hash::make('QuickHR@12345'),
        ]);


        $companyAdmin = User::create([
            'id' => 3,
            'name' => 'company admin',
            'email' => 'gmadmin@agriwatts.ng',
            'password' => Hash::make('Test@12345'),
        ]);


        // Check if the 'super_admin' role exists
        $superAdminRole = Role::findByName('super_admin', 'web'); // 'web' is the default guard
        $companyAdminRole = Role::findByName('company_admin', 'web'); // 'web' is the default guard
        $employeeRole = Role::findByName('employee', 'web'); // 'web' is the default guard
        
        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole); // For clocking purposes
        } else {
            // Optional: throw an exception or log a warning
            throw new \Exception('Role "super_admin" not found. Did you run RoleSeeder?');
        }

        if ($companyAdminRole) {
             $companyAdmin->assignRole($companyAdminRole);
        } else {
            // Optional: throw an exception or log a warning
            throw new \Exception('Role "company admin" not found. Did you run RoleSeeder?');
        }
        

        if ($employeeRole) {
             $clockingAccess->assignRole($employeeRole);
        } else {
            // Optional: throw an exception or log a warning
            throw new \Exception('Role "employee" not found. Did you run RoleSeeder?');
        }

    }
}