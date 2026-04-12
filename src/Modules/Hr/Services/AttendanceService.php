<?php

namespace App\Modules\Hr\Services;


use App\Modules\Hr\Models\DailyAttendance;
use App\Modules\Hr\Models\EmployeeProfile;
use App\Modules\Hr\Models\Shift; // Assuming you have a Shift model
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
// Consider custom exceptions:
// use App\Exceptions\ShiftNotFoundException;
// use App\Exceptions\AttendanceSequenceException;

use App\Modules\Hr\Models\DailyEarning;
use App\Modules\Hr\Models\RoleSchedule; // Import RoleSchedule

use Illuminate\Support\Facades\Log; // For logging warnings/errors

class AttendanceService
{
    protected PayrollCalculatorService $payrollCalculator;

    public function __construct(PayrollCalculatorService $payrollCalculatorService)
    {
        $this->payrollCalculator = $payrollCalculatorService;
    }

    public function record(array $data): DailyAttendance
    {



        return DB::transaction(function () use ($data) {

            $employeeId = $data['employee_id'];
            $attendanceTime = Carbon::parse($data['attendance_time']);
            $type = $data['attendance_type'];
            $attendanceDate = $attendanceTime->toDateString();

            $employeeProfile = EmployeeProfile::where('employee_id', $employeeId)
                                            ->with(['shift', 'user.roles']) // Eager load shift and role
                                            ->first();

            if (!$employeeProfile) {
                throw new InvalidArgumentException("Employee with ID {$employeeId} not found.");
            }

            if (!isset($data['attendance_type']) || !in_array($data['attendance_type'], ['check-in', 'check-out'])) {
                throw new InvalidArgumentException("Invalid attendance type.");
            }


            // Create the attendance record
            $attendance = DailyAttendance::create([
                'employee_id' => $employeeId,
                'attendance_time' => $attendanceTime,
                'attendance_type' => $type,
                'device_id' => $data['device_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'sync_status' => 'pending',
                'sync_attempts' => 0,
                'attendance_date' => $attendanceDate,
            ]);


            // Find the active RoleSchedule for this employee's role, shift, and attendance date
            $roleSchedule = $this->getRoleScheduleForDate($employeeProfile, $attendanceDate);

            // Update attendance record with scheduled times based on roleSchedule (or shift default)
            $this->updateAttendanceWithScheduledTimes($attendance, $roleSchedule, $employeeProfile);

            // Handle check-in and check-out specific logic
            if ($type === 'check-in') {
                $this->validateCheckInTime($attendance, $roleSchedule, $employeeProfile);
            } elseif ($type === 'check-out') {
                $this->handleCheckOut($attendance);
                $this->validateCheckOutTime($attendance, $roleSchedule, $employeeProfile);
            }

            return $attendance;
        });
    }

    /**
     * Retrieves the RoleSchedule for a given employee and date.
     * This method is crucial for getting the correct rules.
     */
    protected function getRoleScheduleForDate(EmployeeProfile $employeeProfile, string $dateString): ?RoleSchedule
    {
        $date = Carbon::parse($dateString);
        $dayOfWeekIso = $date->dayOfWeekIso; // 1=Mon, ..., 7=Sun

        return RoleSchedule::with('shift') // Eager load the shift related to the role_schedule
                            ->where('role_id', $employeeProfile->role_id)
                            ->where('shift_id', $employeeProfile->shift_id) // Assuming shift_id is directly on EmployeeProfile
                            ->where('day_of_week_id', $dayOfWeekIso)
                            /*->where('is_active', true)
                            ->where('effective_date', '<=', $dateString)
                            ->where(function ($query) use ($dateString) {
                                $query->whereNull('end_date')
                                      ->orWhere('end_date', '>=', $dateString);
                            })*/
                            ->first();
    }


    /**
     * Gets the effective start and end times considering RoleSchedule overrides and Shift defaults.
     *
     * @return array [Carbon $effectiveStart, Carbon $effectiveEnd, bool $isOvernight]
     */
    protected function getEffectiveShiftTimes(Carbon $baseDate, ?RoleSchedule $roleSchedule): array
    {
        $shiftStartTime = null;
        $shiftEndTime = null;
        $isOvernight = false;

        if ($roleSchedule) {
            // Prioritize override times from RoleSchedule
            if ($roleSchedule->override_time_start && $roleSchedule->override_time_end) {
                $shiftStartTime = Carbon::parse($roleSchedule->override_time_start);
                $shiftEndTime = Carbon::parse($roleSchedule->override_time_end);
            } else if ($roleSchedule->shift) {
                // Fallback to linked Shift times
                $shiftStartTime = Carbon::parse($roleSchedule->shift->start_time);
                $shiftEndTime = Carbon::parse($roleSchedule->shift->end_time);
                $isOvernight = $roleSchedule->shift->is_overnight; // Use the flag from shift
            }
        }

        if (!$shiftStartTime || !$shiftEndTime) {
            // Fallback if no roleSchedule or shift times are found.
            // This case should ideally be prevented or handled with a default.
            // For now, return default Carbon times at start/end of day or throw error.
            Log::warning("No effective shift times found for date {$baseDate->toDateString()} and employee.");
            return [
                $baseDate->copy()->startOfDay(),
                $baseDate->copy()->endOfDay(),
                false // Assuming not overnight for a fallback
            ];
        }

        $effectiveStart = $baseDate->copy()->setTimeFromTimeString($shiftStartTime->format('H:i:s'));
        $effectiveEnd = $baseDate->copy()->setTimeFromTimeString($shiftEndTime->format('H:i:s'));

        // Adjust for overnight shift if applicable (either from override or shift flag)
        if ($isOvernight || ($roleSchedule && $roleSchedule->override_time_start && $roleSchedule->override_time_end && $shiftStartTime->greaterThan($shiftEndTime))) {
            $effectiveEnd->addDay();
        }

        return [$effectiveStart, $effectiveEnd, $isOvernight];
    }


    /**
     * Updates the attendance record with calculated scheduled start and end times based on the RoleSchedule.
     */
    protected function updateAttendanceWithScheduledTimes(DailyAttendance $attendance, ?RoleSchedule $roleSchedule, EmployeeProfile $employeeProfile): void
    {
        list($scheduledStart, $scheduledEnd, $isOvernight) = $this->getEffectiveShiftTimes(
            $attendance->attendance_time->copy()->startOfDay(), // Use attendance date as base
            $roleSchedule
        );

        if (!$roleSchedule) {
            $scheduledStart = $employeeProfile->shift->start_time;
            $scheduledEnd = $employeeProfile->shift->end_time;
        }

        $attendance->update([
            'scheduled_start' => Carbon::parse($scheduledStart)->format('H:i:s'),
            'scheduled_end' => Carbon::parse($scheduledEnd)->format('H:i:s'),
        ]);
    }


    protected function validateCheckInTime(DailyAttendance $checkIn, ?RoleSchedule $roleSchedule, EmployeeProfile $employeeProfile): void
    {
        list($scheduledStart, $scheduledEnd, $isOvernight) = $this->getEffectiveShiftTimes(
            $checkIn->attendance_time->copy()->startOfDay(),
            $roleSchedule
        );


        if (!$roleSchedule) {
            $scheduledStart = Carbon::parse($employeeProfile->shift->start_time);
            $scheduledEnd = Carbon::parse($employeeProfile->shift->end_time);
        }

        // Get grace period from roleSchedule or default
        $lateGraceMinutes = $roleSchedule ? $roleSchedule->late_grace_minutes : 0;

        $actualCheckIn = $checkIn->attendance_time;
        $difference = $scheduledStart->diffInMinutes($actualCheckIn, false);

        $status = match (true) {
            $difference < -$lateGraceMinutes => 'early',
            $difference >= -$lateGraceMinutes && $difference <= $lateGraceMinutes => 'on_time',
            $difference > $lateGraceMinutes => 'late',
            default => null,
        };

        $checkIn->update([
            'check_status' => $status,
            'minutes_difference' => $difference,
        ]);
    }


    protected function validateCheckOutTime(DailyAttendance $checkOut, ?RoleSchedule $roleSchedule, EmployeeProfile $employeeProfile): void
    {
        list($scheduledStart, $scheduledEnd, $isOvernight) = $this->getEffectiveShiftTimes(
            $checkOut->attendance_time->copy()->startOfDay(), // Use check-out date as base for scheduled end
            $roleSchedule
        );

        if (!$roleSchedule) {
            $scheduledStart = $employeeProfile->shift->start_time;
            $scheduledEnd = $employeeProfile->shift->end_time;
        }

        // Adjust scheduledEnd for overnight shifts if the checkOut time is for the next day's portion of the shift
        // This is a subtle point: if shift is 22:00-06:00, and checkout is 02:00, the scheduledEnd is 06:00 *of that same calendar day*.
        // The getEffectiveShiftTimes already considers adding a day if it's an overnight shift and the baseDate is the start date.
        // However, if the checkout happened on the "next day" calendar-wise (e.g., checkout at 02:00 on July 2 for a shift that started July 1 22:00),
        // the scheduledEnd passed in might be 06:00 July 1, which is incorrect.
        // We need to ensure scheduledEnd corresponds to the *actual day* the checkOut occurs if it crosses midnight.
        // The determineShiftWorkDate is good for the "work_date" for payroll, but for *validation*,
        // we need to compare to the correct scheduled end time on the calendar day of the checkout.
        if ($isOvernight && $checkOut->attendance_time->isSameDay($scheduledStart->copy()->addDay())) {
             // If checkout is on the "next day" of an overnight shift,
             // and scheduledEnd is still on the "start day", move scheduledEnd to the next day.
             $scheduledEnd = $scheduledEnd->copy()->addDay();
        }


        $actualCheckOut = $checkOut->attendance_time;
        $earlyLeaveGraceMinutes = $roleSchedule ? $roleSchedule->early_leave_grace_minutes : 5;

        $difference = Carbon::parse($scheduledEnd)->diffInMinutes($actualCheckOut, false);

        $status = match (true) {
            $difference < -$earlyLeaveGraceMinutes => 'early_checkout',
            $difference >= -$earlyLeaveGraceMinutes && $difference <= $earlyLeaveGraceMinutes => 'on_time_checkout',
            $difference > $earlyLeaveGraceMinutes => 'late_checkout',
            default => null,
        };

        // Check for overtime if applicable
        if (
            isset($roleSchedule?->overtime_after_hours) &&
            $roleSchedule->overtime_after_hours > 0
        ) {
            $overtimeThreshold = $scheduledEnd->copy()->startOfDay()->addMinutes($roleSchedule->overtime_after_hours * 60);

            if ($actualCheckOut->greaterThan($overtimeThreshold)) {
                $status = 'overtime';
            }
        }



        $checkOut->update([
            'check_status' => $status,
            'minutes_difference' => $difference,
        ]);
    }

    protected function handleCheckOut(DailyAttendance $checkout): void
    {
        $employeeId = $checkout->employee_id;

        $checkIn = DailyAttendance::where('employee_id', $employeeId)
            ->where('attendance_type', 'check-in')
            ->where('attendance_time', '<', $checkout->attendance_time)
            //->whereNull('checkin_id')
            ->orderBy('attendance_time', 'desc')
            ->first();

        if (!$checkIn) {
            Log::warning("No matching check-in found for employee ID {$employeeId} at {$checkout->attendance_time}");
            return;
        }

        $checkout->update(['checkin_id' => $checkIn->id]);

        $employeeProfile = EmployeeProfile::where('employee_id', $employeeId)
                                            ->with(['shift', 'user.roles'])
                                            ->first();

        if (!$employeeProfile) {
            Log::error("EmployeeProfile not found for employee ID: {$employeeId} during checkout calculation.");
            return;
        }

        // Pass the actual roleSchedule to the payroll calculator
        $roleSchedule = $this->getRoleScheduleForDate($employeeProfile, $checkIn->attendance_time);


        // Determine the work_date for aggregation (important for overnight shifts)
        $workDate = $this->determineShiftWorkDate(Carbon::parse($checkIn->attendance_time), $employeeProfile->shift); // Still relies on shift for work_date logic
        // Use the PayrollCalculatorService to get paid minutes
        $calculatedHours = $this->payrollCalculator->calculatePaidHours($checkIn, $checkout, $employeeProfile, $roleSchedule);


        $regularMinutes = $calculatedHours['regular_minutes'];
        $overtimeMinutes = $calculatedHours['overtime_minutes'];


        $hourlyRate = $employeeProfile->hourly_rate ?? 100;
        $overtimeRateMultiplier = 1; //this should be configurable
        if (!$roleSchedule?->overtime_after_hours) {
            $overtimeMinutes = 0;
        } else {
            $overtimeRateMultiplier = $roleSchedule->overtimeRateMultiplier? $roleSchedule->overtimeRateMultiplier: 1; //this should be configurable
        }


        $regularHours = round($regularMinutes / 60, 2);
        $overtimeHours = round($overtimeMinutes / 60, 2);
        $totalPaidHours = $regularHours + $overtimeHours;

        $regularAmount = $regularHours * $hourlyRate;
        $overtimeAmount = $overtimeHours * ($hourlyRate * $overtimeRateMultiplier);
        $totalAmountEarned = $regularAmount + $overtimeAmount;

        $earning = DailyEarning::firstOrNew([
            'employee_id' => $employeeId,
            'work_date' => $workDate->toDateString(),
        ]);


        $earning->regular_hours = ($earning->regular_hours ?? 0) + $regularHours;
        $earning->overtime_hours = ($earning->overtime_hours ?? 0) + $overtimeHours;
        $earning->total_hours = ($earning->total_hours ?? 0) + $totalPaidHours;


        if (!$roleSchedule?->overtime_after_hours) {
            // Handle overtime by default when roleSchedule is not configure by admin
            // $earning = $this->adjustOverHours($earning, $employeeId);
        }



        $earning->regular_amount = ($earning->regular_amount ?? 0) + $regularAmount;
        $earning->overtime_amount = ($earning->overtime_amount ?? 0) + $overtimeAmount;
        $earning->total_amount = ($earning->total_amount ?? 0) + $totalAmountEarned;

        $earning->save();
    }


    private function adjustOverHours(DailyEarning $earning, $employeeId): DailyEarning
    {
        $employeeProfile = EmployeeProfile::where('employee_id', $employeeId)->first();

        $start = Carbon::parse($employeeProfile->shift->start_time);
        $end = Carbon::parse($employeeProfile->shift->end_time);

        $shiftDurationMinutes = $start->diffInMinutes($end);
        $shiftDurationHours = $shiftDurationMinutes / 60;

        if ($earning->total_hours >= $shiftDurationHours) {
            $earning->overtime_hours = $earning->total_hours - $shiftDurationHours;
            $earning->regular_hours = $shiftDurationHours;
        }

        return $earning;
    }




    // `determineShiftWorkDate` stays as is, using Shift model's start/end times for now,
    // as it determines the *payroll day* based on the underlying shift pattern.
    // Overrides affect calculation, but not necessarily which calendar day is considered the "work day".
    protected function determineShiftWorkDate(Carbon $checkInTime, ?Shift $assignedShift): Carbon
    {
        if (!$assignedShift) {
            return $checkInTime->copy()->startOfDay();
        }

        $shiftStartTime = Carbon::parse($assignedShift->start_time);
        $shiftEndTime = Carbon::parse($assignedShift->end_time);

        if (!$assignedShift->is_overnight) { // Use the new flag
            return $checkInTime->copy()->startOfDay();
        }

        // Overnight shift logic: work date is the day shift started
        $checkInTimeOnly = $checkInTime->copy()->format('H:i:s');
        if ($checkInTimeOnly < $assignedShift->end_time) { // If check-in is "next day" portion
            return $checkInTime->copy()->subDay()->startOfDay();
        } else { // If check-in is "start day" portion
            return $checkInTime->copy()->startOfDay();
        }
    }
}
