<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\AttendancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendancePolicyFactory extends Factory
{
    protected $model = AttendancePolicy::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => 'ATT-' . $this->faker->unique()->numberBetween(1000, 9999),
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
            'applies_to_shift_categories' => json_encode(['regular']), // <-- FIX: JSON string, not array
            'effective_date' => now()->startOfYear(),
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
