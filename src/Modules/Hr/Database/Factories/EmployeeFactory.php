<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\Employee;
use App\Modules\Admin\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        return [
            'employee_number' => 'EMP-' . $this->faker->unique()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'hire_date' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['Active', 'Inactive', 'Terminated']),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['Male', 'Female', 'Non-binary', 'Prefer not to say']),
            'nationality' => $this->faker->country(),
            'marital_status' => $this->faker->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'address_street' => $this->faker->streetAddress(),
            'address_city' => $this->faker->city(),
            'address_state' => $this->faker->stateAbbr(),
            'address_postal_code' => $this->faker->postcode(),
            'address_country' => $this->faker->countryCode(),
            'created_at' => now(),
            'updated_at' => now(),


            'department_id' => Department::factory(),
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'Active',
            ];
        });
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'Inactive',
            ];
        });
    }
}