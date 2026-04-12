<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Admin\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition()
    {
        $city = $this->faker->city();
        $country = $this->faker->randomElement(['US', 'GB', 'CA', 'AU', 'IN', 'NG', 'DE', 'FR', 'JP']);

        return [
            'name' => $this->faker->company() . ' ' . $this->faker->randomElement(['HQ', 'Branch', 'Office']),
            'code' => strtoupper($this->faker->unique()->bothify('LOC-###??')),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $city,
            'state_province' => $this->faker->optional(0.7)->stateAbbr(),
            'postal_code' => $this->faker->optional(0.8)->postcode(),
            'country' => $country,
            'phone' => $this->faker->optional(0.7)->phoneNumber(),
            'email' => $this->faker->optional(0.6)->companyEmail(),
            'website' => $this->faker->optional(0.5)->url(),
            'timezone' => $this->faker->optional(0.8)->timezone(),
            'is_active' => true,
            'is_remote' => false,
            'is_headquarters' => false,
            'capacity' => $this->faker->optional(0.5)->numberBetween(10, 500),
            'opening_hours' => $this->faker->optional(0.7)->sentence(),
            'opening_date' => $this->faker->optional(0.3)->dateTimeBetween('-10 years', 'now'),
            'closing_date' => null,
            'latitude' => $this->faker->optional(0.6)->latitude(),
            'longitude' => $this->faker->optional(0.6)->longitude(),
            'geofence_radius' => $this->faker->optional(0.5)->randomFloat(2, 50, 500),
            'external_id' => null,
            'last_synced_at' => null,
            'employee_count' => 0,
            'department_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the location is active.
     */
    public function active()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the location is headquarters.
     */
    public function headquarters()
    {
        return $this->state(fn (array $attributes) => [
            'is_headquarters' => true,
        ]);
    }

    /**
     * Indicate that the location is remote/virtual.
     */
    public function remote()
    {
        return $this->state(fn (array $attributes) => [
            'is_remote' => true,
            'address_line_1' => 'Remote',
            'city' => 'Virtual',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    /**
     * Set a specific country.
     */
    public function inCountry(string $countryCode)
    {
        return $this->state(fn (array $attributes) => [
            'country' => $countryCode,
        ]);
    }
}