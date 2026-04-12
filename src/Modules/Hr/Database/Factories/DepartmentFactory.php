<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Admin\Models\Department;
use App\Modules\Admin\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([
                'Human Resources', 'Finance', 'Engineering', 'Sales', 
                'Marketing', 'Operations', 'Legal', 'IT', 'Customer Support'
            ]),
            'code' => strtoupper($this->faker->unique()->bothify('DEPT-###')),
            'description' => $this->faker->optional(0.7)->sentence(),
            'company_id' => Company::factory(),
            'parent_department_id' => null,
            'cost_center' => $this->faker->optional(0.5)->bothify('CC-####'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the department is active.
     */
    public function active()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the department is inactive.
     */
    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Attach a specific company.
     */
    public function forCompany($company)
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company instanceof Company ? $company->id : $company,
        ]);
    }

    /**
     * Set as child department.
     */
    public function childOf($parentDepartment)
    {
        return $this->state(fn (array $attributes) => [
            'parent_department_id' => $parentDepartment instanceof Department ? $parentDepartment->id : $parentDepartment,
        ]);
    }
}