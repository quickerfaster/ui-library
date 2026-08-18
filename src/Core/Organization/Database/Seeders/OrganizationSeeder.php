<?php

namespace QuickerFaster\UILibrary\Core\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Core\Organization\Models\Company;
use QuickerFaster\UILibrary\Core\Organization\Models\Branch;
use QuickerFaster\UILibrary\Core\Organization\Models\Department;
use QuickerFaster\UILibrary\Core\Organization\Models\Location;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if data already exists
        if (Company::count() > 0) {
            return;
        }

        $company = Company::create([
            'name' => 'Demo Company',
            'code' => 'DEMO',
            'email' => 'info@demo.com',
            'currency_code' => 'USD',
            'timezone' => 'UTC',
        ]);

        Branch::create([
            'company_id' => $company->id,
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_headquarters' => true,
        ]);

        Department::create([
            'company_id' => $company->id,
            'name' => 'General',
            'code' => 'GEN',
        ]);

        Location::create([
            'company_id' => $company->id,
            'name' => 'Main Office',
            'code' => 'MAIN',
            'is_headquarters' => true,
        ]);
    }
}