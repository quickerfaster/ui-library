<?php

namespace App\Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Models\EmployeePayrollProfile;
use App\Modules\Hr\Models\PaySchedule;
use App\Modules\Admin\Models\Department;
use App\Modules\Admin\Models\JobTitle;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Location;
use App\Modules\Admin\Models\Shift;
use App\Modules\Hr\Models\AttendancePolicy;

class EmployeeWithDependenciesSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        // Step 1: Create static dependencies (reused for all employees)
        $this->createDependencies();

        // Step 2: Create 5,000 employees with EMP0001..EMP5000 numbers
        $employees = $this->createEmployees(5000);

        // Step 3: Create one active position for each employee
        $this->createPositionsForEmployees($employees);

        // Step 4: Create one payroll profile for each employee
        $this->createPayrollProfilesForEmployees($employees);
    }

    /**
     * Create all lookup tables that EmployeePosition and EmployeePayrollProfile reference.
     */
    private function createDependencies(): void
    {
        // Companies (each creates its own location via factory)
        Company::factory()->count(3)->active()->create();

        // Additional locations (not tied to a company, but can be used)
        Location::factory()->count(5)->active()->create();

        // Departments
        Department::factory()->count(10)->create();

        // Job titles
        JobTitle::factory()->count(20)->create();

        // Shifts
        Shift::factory()->count(5)->create();

        // Attendance policies
        AttendancePolicy::factory()->count(5)->create();

        // Pay Schedules (since you don't have a factory yet, create manually)
        $this->createPaySchedules();
    }

    /**
     * Create pay schedules directly because no factory is available.
     */
    private function createPaySchedules(): void
    {
        $schedules = [
            [
                'name' => 'Monthly',
                'code' => 'MON',
                'frequency' => 'Monthly',
                'first_period_start_date' => now()->startOfYear(),
                'next_pay_date' => now()->addMonth()->startOfMonth(),
                'payment_delay_days' => 5,
                'country_code' => 'US',
                'currency_code' => 'USD',
                'timezone' => 'America/New_York',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Bi-Weekly',
                'code' => 'BIW',
                'frequency' => 'Bi-weekly',
                'first_period_start_date' => now()->startOfYear(),
                'next_pay_date' => now()->addWeeks(2),
                'payment_delay_days' => 3,
                'country_code' => 'US',
                'currency_code' => 'USD',
                'timezone' => 'America/New_York',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Weekly',
                'code' => 'WEE',
                'frequency' => 'Weekly',
                'first_period_start_date' => now()->startOfYear(),
                'next_pay_date' => now()->addWeek(),
                'payment_delay_days' => 2,
                'country_code' => 'US',
                'currency_code' => 'USD',
                'timezone' => 'America/New_York',
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($schedules as $schedule) {
            PaySchedule::create($schedule);
        }
    }

    /**
     * Create employees with sequential employee numbers.
     */
    private function createEmployees(int $count): \Illuminate\Database\Eloquent\Collection
    {
        $employeeNumbers = array_map(function ($i) {
            return 'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT);
        }, range(1, $count));

        $employees = Employee::factory()
            ->count($count)
            ->sequence(fn ($sequence) => [
                'employee_number' => $employeeNumbers[$sequence->index],
                //'status' => 'Active', // assuming 'status' column exists – if not, remove this line
            ])
            ->create();

        return $employees;
    }

    /**
     * Create one EmployeePosition record per employee.
     */
    private function createPositionsForEmployees(\Illuminate\Database\Eloquent\Collection $employees): void
    {
        // Pre-fetch all dependency collections for random selection
        $jobTitles = JobTitle::all();
        $departments = Department::all();
        $attendancePolicies = AttendancePolicy::all();
        $locations = Location::all();
        $shifts = Shift::all();
        $paySchedules = PaySchedule::all();

        foreach ($employees as $employee) {
            // Random selections
            $jobTitle = $jobTitles->random();
            $department = $departments->random();
            $attendancePolicy = $attendancePolicies->random();
            $location = $locations->random();
            $shift = $shifts->random();
            $paySchedule = $paySchedules->random();

            // Decide pay type (70% salaried, 30% hourly)
            $payType = $this->faker->boolean(70) ? 'salaried_full' : 'hourly';
            $baseSalary = $payType === 'salaried_full' ? $this->faker->randomFloat(2, 40000, 120000) : 0;
            $hourlyRate = $payType === 'hourly' ? $this->faker->randomFloat(2, 15, 50) : 0;

            EmployeePosition::factory()
                ->forEmployee($employee)
                ->state([
                    'job_title_id' => $jobTitle->id,
                    'department_id' => $department->id,
                    'attendance_policy_id' => $attendancePolicy->id,
                    'location_id' => $location->id,
                    'shift_id' => $shift->id,
                    'pay_schedule_id' => $paySchedule->id,
                    'manager_id' => null,
                    'reports_to' => null,
                    'pay_type' => $payType,
                    'hourly_rate' => $hourlyRate,
                    'base_salary' => $baseSalary,
                    'salary_currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
                    'pay_frequency' => $paySchedule->frequency, // match the schedule's frequency
                    'employment_status' => 'Active',
                    'cost_center' => $this->faker->optional(0.5)->bothify('CC-####'),
                    'work_email' => $this->faker->optional(0.8)->companyEmail(),
                    'work_phone_extension' => $this->faker->optional(0.3)->numerify('###'),
                ])
                ->create();
        }
    }

    /**
     * Create one EmployeePayrollProfile per employee.
     */
    private function createPayrollProfilesForEmployees(\Illuminate\Database\Eloquent\Collection $employees): void
    {
        $paySchedules = PaySchedule::all();

        foreach ($employees as $employee) {
            // Random pay schedule (could be same as position or different – up to you)
            $paySchedule = $paySchedules->random();

            EmployeePayrollProfile::create([
                'employee_id' => $employee->id,
                'pay_schedule_id' => $paySchedule->id,
                'bank_account_holder_name' => $employee->first_name . ' ' . $employee->last_name,
                'bank_name' => $this->faker->company() . ' Bank',
                'bank_account_number' => $this->faker->bankAccountNumber(),
                'bank_routing_number' => $this->faker->regexify('[0-9]{9}'),
                'bank_iban' => $this->faker->optional(0.5)->iban('US'),
                'bank_swift' => $this->faker->optional(0.3)->swiftBicNumber(),
                'account_type' => $this->faker->randomElement(['checking', 'savings']),
                'payment_method' => 'bank_transfer',
                'tax_filing_status' => $this->faker->randomElement(['single', 'married', 'head_of_household']),
                'allowances' => $this->faker->numberBetween(0, 5),
                'extra_withholding' => $this->faker->randomFloat(2, 0, 200),
                'is_exempt_from_federal_tax' => $this->faker->boolean(10),
                'override_country_code' => 'US',
                'override_state_code' => $this->faker->stateAbbr(),
                'currency_code' => 'USD',
                'effective_date' => $employee->hire_date,
                'expiry_date' => null,
                'is_active' => true,
            ]);
        }
    }

    // Faker helper for cleaner code inside loops
    protected $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }
}