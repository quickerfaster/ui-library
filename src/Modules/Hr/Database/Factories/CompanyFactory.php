<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'parent_company_id' => null,
            'location_id' => Location::factory(), // Creates a location automatically
            'subdomain' => $this->faker->unique()->bothify(strtolower($this->faker->company())),
            'database_name' => null,
            'status' => $this->faker->randomElement(['pending', 'active', 'suspended', 'canceled']),
            'billing_email' => $this->faker->companyEmail(),
            'billing_address_line_1' => $this->faker->streetAddress(),
            'billing_address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state_province' => $this->faker->optional(0.7)->stateAbbr(),
            'billing_postal_code' => $this->faker->optional(0.8)->postcode(),
            'billing_country_code' => $this->faker->randomElement(['US', 'GB', 'CA', 'AU', 'IN', 'NG', 'DE', 'FR']),
            'timezone' => $this->faker->timezone(),
            'currency_code' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR', 'NGN']),
            'level' => $this->faker->randomElement(['parent', 'division', 'branch']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the company is active.
     */
    public function active()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the company is a parent company.
     */
    public function parent()
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'parent',
            'parent_company_id' => null,
        ]);
    }

    /**
     * Indicate that the company is a child of another company.
     */
    public function childOf($parentCompany)
    {
        return $this->state(fn (array $attributes) => [
            'parent_company_id' => $parentCompany instanceof Company ? $parentCompany->id : $parentCompany,
            'level' => 'division',
        ]);
    }

    /**
     * Attach a specific location.
     */
    public function atLocation($location)
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $location instanceof Location ? $location->id : $location,
        ]);
    }
}