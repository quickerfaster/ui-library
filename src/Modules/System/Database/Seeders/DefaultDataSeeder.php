<?php

namespace App\Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

use App\Modules\Admin\Models\Company;
use App\Modules\Hr\Models\Shift;
use App\Modules\Hr\Models\WorkPattern;
use App\Modules\Hr\Models\AttendancePolicy;
use App\Modules\Admin\Models\Location;
use App\Modules\Admin\Models\Department;

class DefaultDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a placeholder company (will be updated by the controller)
        $company = Company::create([
            'name' => 'Placeholder Company',
            'subdomain' => 'placeholder',
            'status' => 'pending',
            'billing_email' => 'placeholder@example.com',
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'is_placeholder' => true,   // Add this column to companies table
        ]);

        // 2. Create default shift
        $defaultShift = Shift::create([
            // 'company_id' => $company->id,
            'name' => 'Standard Day Shift',
            'code' => 'STD',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'duration_hours' => 8.0,
            'is_overnight' => false,
            'is_active' => true,
            'is_default' => true,
            'shift_category' => 'regular',
        ]);

        // 3. Create default work pattern
        $defaultWorkPattern = WorkPattern::create([
            // 'company_id' => $company->id,
            'name' => 'Monday-Friday',
            'code' => 'M-F',
            'shift_id' => $defaultShift->id,
            'applicable_days' => '1,2,3,4,5', // Monday to Friday
            'pattern_type' => 'recurring',
            'effective_date' => Carbon::now(),
            'is_active' => true,
            'is_default' => true,
        ]);

        // 4. Create default attendance policy
        $defaultPolicy = AttendancePolicy::create([
            // 'company_id' => $company->id,
            'name' => 'Default Attendance Policy',
            'code' => 'DEFAULT',
            'effective_date' => Carbon::now(),
            'is_active' => true,
            'is_default' => true,
            'grace_period_minutes' => 5,
            'early_departure_grace_minutes' => 5,
            'overtime_daily_threshold_hours' => 8.0,
            'overtime_weekly_threshold_hours' => 40.0,
            'max_daily_overtime_hours' => 4.0,
            'overtime_multiplier' => 1.5,
            'double_time_threshold_hours' => 12.0,
            'double_time_multiplier' => 2.0,
            'requires_break_after_hours' => 5.0,
            'break_duration_minutes' => 30,
            'unpaid_break_minutes' => 0,
            'applies_to_shift_categories' => json_encode(['regular']),
        ]);

        // 5. Create default location
        $defaultLocation = Location::create([
            // 'company_id' => $company->id,
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
            'is_headquarters' => true,

            'address_line_1' => 'Default Adress',
            'city' => 'Default City',
            'country' => 'US',
            'timezone' => 'America/New_York',
        ]);

        // 6. Create default department
        $defaultDepartment = Department::create([
            'company_id' => $company->id,
            'name' => 'General',
            'code' => 'GEN',
            'is_active' => true,
        ]);

        // Optional: Assign the default policy to the company via PolicyAssignment
        // (if you want company‑specific default)
        // \App\Modules\Hr\Models\PolicyAssignment::create([
        //     // 'company_id' => $company->id,
        //     'attendance_policy_id' => $defaultPolicy->id,
        // ]);
    }
}