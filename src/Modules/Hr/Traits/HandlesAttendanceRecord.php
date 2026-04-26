<?php 


namespace App\Modules\Hr\Traits;

use App\Modules\Hr\Models\Attendance;
use App\Modules\Hr\Models\Employee;
use Carbon\Carbon;

trait HandlesAttendanceRecord
{
    protected function getOrCreateAttendanceRecord(Employee $employee, Carbon $date, $schedule = null, $policy = null): Attendance
    {
        $attendance = Attendance::firstOrCreate(
            [
                'employee_number' => $employee->employee_number,
                'date'            => $date->toDateString(),
            ],
            [
                'employee_id'            => $employee->id,
                'shift_id'               => $schedule['shift']->id ?? null,
                'attendance_policy_id'   => $policy?->id,
                'company'                => $employee->employeePosition?->department?->company?->name ?? 'N/A',
                'department'             => $employee->employeePosition?->department?->name ?? 'N/A',
                'status'                 => 'pending',
                'is_approved'            => false,
                'net_hours'              => 0.00,
            ]
        );

        // ✅ Update existing records with latest company/department (fix for old data)
        if (!$attendance->wasRecentlyCreated) {
            $attendance->update([
                'company'    => $employee->employeePosition?->department?->company?->name ?? 'N/A',
                'department' => $employee->employeePosition?->department?->name ?? 'N/A',
            ]);
        }

        return $attendance;
    }
}