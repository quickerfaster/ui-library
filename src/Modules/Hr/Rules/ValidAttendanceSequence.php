<?php

namespace App\Modules\Hr\Rules;


use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\MOdules\Hr\Models\DailyAttendance;
use Carbon\Carbon;

class ValidAttendanceSequence implements ValidationRule
{
    protected $employeeId;
    protected $attendanceTime;
    protected $attendanceType;
    protected $attendanceDate;

    public function __construct(String $employeeId, string $attendanceTime, string $attendanceType, string $attendanceDate)
    {
        $this->employeeId = $employeeId;
        $this->attendanceTime = Carbon::parse($attendanceTime);
        $this->attendanceType = $attendanceType;
        $this->attendanceDate = $attendanceDate;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Prevent exact duplicate entry (attendance_time is primary check)
        $exists = DailyAttendance::where('employee_id', $this->employeeId)
            ->where('attendance_time', $this->attendanceTime)
            ->where('attendance_type', $this->attendanceType)
            ->exists();

        if ($exists) {
            $fail('This exact attendance record already exists.');
            return;
        }

        // 2. Get the last record for that employee on that specific attendance_date
        $last = DailyAttendance::where('employee_id', $this->employeeId)
            ->where('attendance_date', $this->attendanceDate)
            ->orderBy('attendance_time', 'desc')
            ->first();

        if ($this->attendanceType === 'check-in') {
            // Invalid if the last record for the day was also a check-in
            if ($last && $last->attendance_type === 'check-in') {
                $fail('Cannot check-in. The last attendance record was already a check-in.');
                return;
            }
        } elseif ($this->attendanceType === 'check-out') {
            // Invalid if no check-in yet, or the last record was also a check-out
            if (!$last || $last->attendance_type === 'check-out') {
                $fail('Cannot check-out. No prior check-in found or the last record was a check-out.');
                return;
            }

            // Ensure check-out time is after the last check-in time
            if ($this->attendanceTime->lte(Carbon::parse($last->attendance_time))) {
                 $fail('Check-out time must be after the last check-in time.');
                 return;
            }
        }
    }
}
