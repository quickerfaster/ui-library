<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\Attendance;
use App\Modules\Hr\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $date = $this->faker->dateTimeBetween('-1 month', 'now');
        $employee = Employee::factory()->create();
        
        return [
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'company' => $this->faker->company(),
            'department' => $this->faker->word(),
            'date' => $date,
            'net_hours' => $this->faker->randomFloat(2, 0, 12),
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'half_day', 'holiday', 'leave']),
            'sessions' => null,
            'shift_id' => null,
            'absence_type' => null,
            'is_unplanned' => false,
            'absence_reason' => null,
            'is_paid_absence' => true,
            'hours_deducted' => 0,
            'is_approved' => $this->faker->boolean(30),
            'approved_by' => null,
            'approved_at' => null,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'needs_review' => $this->faker->boolean(20),
            'leave_request_id' => null,
            'last_calculated_at' => now(),
            'calculation_method' => 'auto',
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'double_time_hours' => 0,
            'attendance_policy_id' => null,
            'work_pattern_id' => null,
            'minutes_late' => 0,
            'minutes_early_departure' => 0,
            'missed_break_minutes' => 0,
            'calculation_metadata' => null,
            'calculation_version' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Mark as approved.
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_approved' => true,
                'approved_by' => 'System',
                'approved_at' => now(),
            ];
        });
    }

    /**
     * Set a specific employee.
     */
    public function forEmployee($employee)
    {
        return $this->state(function (array $attributes) use ($employee) {
            return [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
            ];
        });
    }

    /**
     * Set a specific date.
     */
    public function onDate($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'date' => $date,
            ];
        });
    }

    /**
     * Present with hours.
     */
    public function present($hours = 8)
    {
        return $this->state(function (array $attributes) use ($hours) {
            return [
                'status' => 'present',
                'net_hours' => $hours,
                'regular_hours' => min($hours, 8),
                'overtime_hours' => max($hours - 8, 0),
            ];
        });
    }
}
