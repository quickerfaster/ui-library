<?php

namespace App\Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Admin\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [

            // SaaS Platform
            [
                'name' => 'super_admin',
                'description' => 'Full SaaS platform access',
            ],

            // Company Administration
            [
                'name' => 'company_admin',
                'description' => 'Manages company settings, users, and modules',
            ],

            // Human Resources
            [
                'name' => 'hr_manager',
                'description' => 'Manages employees, attendance, leave, and HR operations',
            ],

            [
                'name' => 'hr_officer',
                'description' => 'Supports daily HR operations',
            ],

            // Payroll & Finance
            [
                'name' => 'payroll_officer',
                'description' => 'Processes payroll and salary operations',
            ],

            [
                'name' => 'accountant',
                'description' => 'Handles finance and payroll reports',
            ],

            // Department Management
            [
                'name' => 'manager',
                'description' => 'Manages department employees and approvals',
            ],

            [
                'name' => 'supervisor',
                'description' => 'Supervises team attendance and activities',
            ],

            // Recruitment
            [
                'name' => 'recruiter',
                'description' => 'Handles recruitment and applicants',
            ],

            // Employee
            [
                'name' => 'employee',
                'description' => 'Standard employee access',
            ],
        ];

        foreach ($roles as $data) {

            Role::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'guard_name' => 'web',
                    'editable' => 'No',
                ]
            );
        }
    }
}

