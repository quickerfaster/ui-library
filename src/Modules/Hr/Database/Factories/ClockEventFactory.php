<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\ClockEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClockEventFactory extends Factory
{
    protected $model = ClockEvent::class;

    public function definition()
    {
        $timestamp = $this->faker->dateTimeThisMonth();
        
        return [
            'employee_id' => $this->faker->bothify('EMP-####-###'), // string employee_number
            'event_type' => $this->faker->randomElement(['clock_in', 'clock_out']),
            'timestamp' => $timestamp,
            'method' => $this->faker->randomElement(['device', 'web', 'mobile', 'biometric', 'kiosk', 'manual']),
            'latitude' => $this->faker->optional(0.7)->latitude(),
            'longitude' => $this->faker->optional(0.7)->longitude(),
            'location_name' => $this->faker->optional(0.6)->city() . ', ' . $this->faker->country(),
            'timezone' => $this->faker->optional(0.8)->timezone(),
            'ip_address' => $this->faker->optional(0.5)->ipv4(),
            'device_id' => $this->faker->optional(0.7)->bothify('device-####'),
            'device_name' => $this->faker->optional(0.7)->bothify('Device Model ##??'),
            'sync_status' => 'synced',
            'sync_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set a specific employee number for the clock event.
     */
    public function forEmployee($employeeNumber)
    {
        return $this->state(function (array $attributes) use ($employeeNumber) {
            return [
                'employee_id' => $employeeNumber,
            ];
        });
    }

    /**
     * Indicate a clock-in event.
     */
    public function clockIn()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'clock_in',
            ];
        });
    }

    /**
     * Indicate a clock-out event.
     */
    public function clockOut()
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'clock_out',
            ];
        });
    }

    /**
     * Set a specific timestamp.
     */
    public function at($timestamp)
    {
        return $this->state(function (array $attributes) use ($timestamp) {
            return [
                'timestamp' => $timestamp,
            ];
        });
    }
}