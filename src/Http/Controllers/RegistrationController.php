<?php

namespace QuickerFaster\UILibrary\Http\Controllers;

use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Location;
use App\Modules\Admin\Models\Department;
use App\Modules\Hr\Models\Shift;
use App\Modules\Hr\Models\AttendancePolicy;
use App\Modules\Hr\Models\WorkPattern;
use App\Modules\Hr\Models\PolicyAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


// THIS WILL BE IMPLEMENTED LATER WHEN SAAS MULTITENEANCY IS BEEN IMPLEMENTED
class RegistrationController extends Controller
{
    public function register(Request $request)
    {
        DB::transaction(function () use ($request) {
            // 1. Create the company
            $company = Company::create([
                'name' => $request->company_name,
                'subdomain' => $request->subdomain,
                'status' => 'active',
                // Add other required fields as per your model
            ]);

            // 2. Create a default shift (is_default = true)
            $defaultShift = Shift::create([
                'company_id' => $company->id,
                'name' => 'Day Shift',
                'code' => 'DAY',
                'is_default' => true,
                'is_active' => true,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'duration_hours' => 8,
                'is_overnight' => false,
            ]);

            // 3. Create a default attendance policy (is_default = true)
            $defaultPolicy = AttendancePolicy::create([
                'company_id' => $company->id,
                'name' => 'Default Attendance Policy',
                'code' => 'DEFAULT_POLICY',
                'is_default' => true,
                'is_active' => true,
                'effective_date' => now(),
                'grace_period_minutes' => 5,
                'early_departure_grace_minutes' => 5,
                'overtime_daily_threshold_hours' => 8,
                'overtime_weekly_threshold_hours' => 40,
                'overtime_multiplier' => 1.5,
                // Add other fields with sensible defaults
            ]);

            // 4. Create a default work pattern (is_default = true)
            $defaultWorkPattern = WorkPattern::create([
                'company_id' => $company->id,
                'name' => 'Standard 9-5',
                'code' => 'STANDARD_9_5',
                'is_default' => true,
                'is_active' => true,
                'effective_date' => now(),
                'shift_id' => $defaultShift->id,
                'applicable_days' => '1,2,3,4,5', // Monday to Friday
                // override times left null
            ]);

            // 5. Optional: Create a default location and department
            $defaultLocation = Location::create([
                'company_id' => $company->id,
                'name' => 'Headquarters',
                'code' => 'HQ',
                'is_active' => true,
                'is_headquarters' => true,
                'city' => 'Default City',
                'country' => 'US',
                'timezone' => 'America/New_York',
            ]);

            $defaultDepartment = Department::create([
                'company_id' => $company->id,
                'name' => 'General',
                'code' => 'GEN',
                'is_active' => true,
            ]);

            // 6. Optionally assign the default policy to the company
            // This ensures a company-specific policy fallback.
            // If you want to rely solely on the system default (is_default=true), you can skip this.
            PolicyAssignment::create([
                'company_id' => $company->id,
                'attendance_policy_id' => $defaultPolicy->id,
                'priority' => 0,
            ]);
        });

        // Return appropriate response (e.g., redirect to dashboard)
        return redirect()->route('dashboard')->with('success', 'Company registered successfully.');
    }
}