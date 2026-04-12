<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Models\Shift;
use App\Modules\Admin\Models\Department;
use App\Modules\Admin\Models\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeePositionFactory extends Factory
{
    protected $model = EmployeePosition::class;

    public function definition()
    {
        return [
            'employee_id' => Employee::factory(),
            'job_title_id' => JobTitle::factory(),
            'department_id' => Department::factory(),
            // 'shift_id' => Shift::factory(),
            // 'work_pattern_id' => null, // optional
            'attendance_policy_id' => null, // optional
            'manager_id' => null, // optional
            'reports_to' => null, // optional
            'pay_type' => $this->faker->randomElement(['hourly', 'salaried_daily', 'salaried_full']),
            'hourly_rate' => $this->faker->randomFloat(2, 15, 50),
            'base_salary' => $this->faker->randomFloat(2, 30000, 120000),
            'salary_currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'pay_frequency' => $this->faker->randomElement(['Monthly', 'Semi-monthly', 'Bi-weekly', 'Weekly']),
            'employment_status' => $this->faker->randomElement(['Active', 'On Leave', 'Terminated', 'Suspended']),
            'location_id' => null,
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'end_date' => null,
            'cost_center' => $this->faker->optional()->bothify('CC-####'),
            'work_email' => $this->faker->optional()->companyEmail(),
            'work_phone_extension' => $this->faker->optional()->numerify('###'),
            'job_description' => $this->faker->optional()->paragraph(),
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Associate with a specific employee.
     */
    public function forEmployee($employee)
    {
        return $this->state(function (array $attributes) use ($employee) {
            return [
                'employee_id' => $employee instanceof Employee ? $employee->id : $employee,
            ];
        });
    }

    /**
     * Set as hourly with specific rate.
     */
    public function hourly($rate = null)
    {
        return $this->state(function (array $attributes) use ($rate) {
            return [
                'pay_type' => 'hourly',
                'hourly_rate' => $rate ?? $this->faker->randomFloat(2, 15, 50),
                'base_salary' => 0,
            ];
        });
    }

    /**
     * Set as salaried.
     */
    public function salaried($salary = null)
    {
        return $this->state(function (array $attributes) use ($salary) {
            return [
                'pay_type' => 'salaried_full',
                'base_salary' => $salary ?? $this->faker->randomFloat(2, 40000, 100000),
                'hourly_rate' => 0,
            ];
        });
    }
}
