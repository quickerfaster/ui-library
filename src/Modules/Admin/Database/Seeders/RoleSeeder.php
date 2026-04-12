<?php

namespace App\Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Modules\Admin\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            // System & Administrative
            ['name' => 'super_admin', 'description' => 'Full system access and control'],
            ['name' => 'admin', 'description' => 'Manage users, settings, and system operations'],
            ['name' => 'manager', 'description' => 'Oversees business or department-level operations'],
            ['name' => 'team_lead', 'description' => 'Leads a functional team or unit'],
            ['name' => 'employee', 'description' => 'General staff member with basic access'],

            // HR
            ['name' => 'hr_manager', 'description' => 'Manages HR policies, hiring, and employee welfare'],
            ['name' => 'hr_assistant', 'description' => 'Supports HR documentation and coordination'],
            ['name' => 'hr_admin', 'description' => 'Supports HR administration activities'],
            ['name' => 'hr_recruiter', 'description' => 'Manages candidate sourcing and interviews'],

            // Finance
            /*['name' => 'finance_manager', 'description' => 'Manages budgeting, forecasting, and compliance'],
            ['name' => 'accountant', 'description' => 'Handles accounts and bookkeeping'],
            ['name' => 'payroll_officer', 'description' => 'Processes staff salaries and deductions'],
            ['name' => 'procurement_officer', 'description' => 'Handles purchasing and vendor relationships'],

            // Operations & Logistics
            ['name' => 'operations_manager', 'description' => 'Supervises all operational activities'],
            ['name' => 'warehouse_manager', 'description' => 'Manages warehouse operations and logistics'],
            ['name' => 'inventory_officer', 'description' => 'Maintains stock and supply records'],
            ['name' => 'logistics_coordinator', 'description' => 'Plans and manages product deliveries'],

            // Production & Manufacturing
            ['name' => 'production_manager', 'description' => 'Oversees manufacturing processes and timelines'],
            ['name' => 'quality_control_officer', 'description' => 'Ensures product quality meets standards'],
            ['name' => 'machine_operator', 'description' => 'Operates production machinery'],

            // Sales & Marketing
            ['name' => 'sales_manager', 'description' => 'Leads sales team and targets'],
            ['name' => 'sales_rep', 'description' => 'Manages client relationships and product sales'],
            ['name' => 'marketing_manager', 'description' => 'Develops marketing strategies and campaigns'],
            ['name' => 'content_creator', 'description' => 'Creates content for marketing and branding'],
            ['name' => 'social_media_manager', 'description' => 'Handles brand visibility on social platforms'],

            // IT & Software
            ['name' => 'it_admin', 'description' => 'Maintains IT systems and user support'],
            ['name' => 'developer', 'description' => 'Builds and maintains software applications'],
            ['name' => 'ui_ux_designer', 'description' => 'Designs user-friendly interfaces'],
            ['name' => 'data_analyst', 'description' => 'Analyzes business data for insights'],

            // Healthcare
            ['name' => 'doctor', 'description' => 'Provides medical diagnosis and treatment'],
            ['name' => 'nurse', 'description' => 'Supports patient care and treatment'],
            ['name' => 'pharmacist', 'description' => 'Manages drug prescriptions and inventory'],

            // Education
            ['name' => 'school_admin', 'description' => 'Manages school operations and staff'],
            ['name' => 'teacher', 'description' => 'Delivers lessons and evaluates students'],
            ['name' => 'student_affairs_officer', 'description' => 'Coordinates student-related services'],

            // Customer & External Services
            ['name' => 'customer_support', 'description' => 'Handles customer inquiries and resolution'],
            ['name' => 'client_account_manager', 'description' => 'Manages client portfolios and satisfaction'],

            // Legal & Compliance
            ['name' => 'compliance_officer', 'description' => 'Ensures adherence to legal and internal policies'],
            ['name' => 'auditor', 'description' => 'Reviews financial and operational compliance'],

            // General & Misc
            ['name' => 'intern', 'description' => 'Temporary role for training and learning'],
            ['name' => 'consultant', 'description' => 'Provides expert advice and solutions'],
            ['name' => 'freelancer', 'description' => 'Handles short-term or task-based projects'],*/
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

