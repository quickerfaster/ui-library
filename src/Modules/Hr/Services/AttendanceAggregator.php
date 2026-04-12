<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\{
    ClockEvent, Attendance, AttendanceSession, AttendancePolicy,
    WorkPattern, Shift, ShiftSchedule, Employee, EmployeePosition,
    LeaveRequest, Holiday
};
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceAggregator
{
    protected AttendanceCalculator $calculator;

    public function __construct(AttendanceCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Recalculate attendance for a specific employee and day
     * Now uses AttendanceCalculator for normal days, handles special cases separately
     */
    public function recalculateForDay(string $employeeNumber, string $date): void
    {
        DB::transaction(function () use ($employeeNumber, $date) {
            $dateObj = Carbon::parse($date);
            $dateOnly = $dateObj->toDateString();

            // Get employee
            $employee = Employee::where('employee_number', $employeeNumber)->first();
            if (!$employee) {
                Log::error("Employee not found: {$employeeNumber}");
                return;
            }

            // Check for holiday
            $isHoliday = $this->isCompanyHoliday($dateOnly);

            // Check for approved leave
            $hasApprovedLeave = LeaveRequest::where('employee_id', $employeeNumber)
                ->where('status', 'Approved')
                ->whereDate('start_date', '<=', $dateOnly)
                ->whereDate('end_date', '>=', $dateOnly)
                ->exists();

            // SPECIAL CASE 1: Holiday
            if ($isHoliday) {
                $this->handleHolidayAttendance($employee, $dateOnly);
                return;
            }

            // SPECIAL CASE 2: Approved Leave
            if ($hasApprovedLeave) {
                $this->handleLeaveAttendance($employee, $dateOnly);
                return;
            }

            // SPECIAL CASE 3: No clock events (unplanned absence)
            $hasClockEvents = ClockEvent::where('employee_id', $employeeNumber)
                ->whereDate('timestamp', $dateOnly)
                ->exists();

            if (!$hasClockEvents) {
                $this->handleUnplannedAbsence($employee, $dateOnly);
                return;
            }

            // NORMAL CASE: Use AttendanceCalculator for full processing
            try {
                $result = $this->calculator->calculateForDay($employeeNumber, $dateObj);

                Log::info("Attendance calculated successfully via calculator", [
                    'employee' => $employeeNumber,
                    'date' => $dateOnly,
                    'attendance_id' => $result['attendance_id'],
                    'sessions' => $result['sessions_created']
                ]);
            } catch (\Exception $e) {
                Log::error("Calculator failed, using fallback", [
                    'employee' => $employeeNumber,
                    'date' => $dateOnly,
                    'error' => $e->getMessage()
                ]);

                // Fallback to legacy processing
                // $this->fallbackCalculation($employee, $dateOnly);
            }
        });
    }

    /**
     * Handle company holiday attendance
     */
    private function handleHolidayAttendance(Employee $employee, string $date): void
    {
        $holiday = Holiday::whereDate('date', $date)->first();

        $attendance = $this->getOrCreateAttendanceRecord($employee, $date);

        // Delete any existing sessions (shouldn't exist, but clean up)
        AttendanceSession::where('attendance_id', $attendance->id)->delete();

        $attendance->update([
            'status' => 'holiday',
            'net_hours' => 0.00,
            'regular_hours' => 0.00,
            'overtime_hours' => 0.00,
            'is_approved' => true,
            'notes' => $holiday ? "Company Holiday: {$holiday->name}" : "Company Holiday",
            'needs_review' => false,
            'is_unplanned' => false,
            'absence_type' => null,
            'calculation_method' => 'auto',
            'sessions' => null,
        ]);

        Log::info("Marked attendance as holiday", [
            'employee_id' => $employee->employee_number,
            'date' => $date,
            'attendance_id' => $attendance->id
        ]);
    }

    /**
     * Handle approved leave attendance
     */
    private function handleLeaveAttendance(Employee $employee, string $date): void
    {
        $leaveRequest = LeaveRequest::where('employee_id', $employee->employee_number)
            ->where('status', 'Approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (!$leaveRequest) {
            return;
        }

        $attendance = $this->getOrCreateAttendanceRecord($employee, $date);

        // Delete any existing sessions
        AttendanceSession::where('attendance_id', $attendance->id)->delete();

        // Get standard hours from employee's shift if available
        $standardHours = 8.00;
        if ($employee->position && $employee->position->shift) {
            $standardHours = $employee->position->shift->duration_hours ?? 8.00;
        }

        $attendance->update([
            'status' => 'leave',
            'leave_request_id' => $leaveRequest->id,
            'net_hours' => $standardHours,
            'regular_hours' => $standardHours,
            'overtime_hours' => 0.00,
            'is_approved' => true,
            'notes' => "On Leave: " . ($leaveRequest->leaveType->name ?? 'Approved Leave'),
            'needs_review' => false,
            'is_unplanned' => false,
            'absence_type' => 'planned_leave',
            'hours_deducted' => ($leaveRequest->leaveType->deducts_from_balance ?? true) ? $standardHours : 0,
            'is_paid_absence' => $leaveRequest->leaveType->is_paid ?? true,
            'calculation_method' => 'auto',
            'sessions' => null,
        ]);

        Log::info("Marked attendance as leave", [
            'employee_id' => $employee->employee_number,
            'date' => $date,
            'leave_request_id' => $leaveRequest->id,
            'attendance_id' => $attendance->id
        ]);
    }

    /**
     * Handle unplanned absence (no clock events, no approved leave, not holiday)
     */
    private function handleUnplannedAbsence(Employee $employee, string $date): void
    {
        $attendance = $this->getOrCreateAttendanceRecord($employee, $date);

        // Delete any existing sessions
        AttendanceSession::where('attendance_id', $attendance->id)->delete();

        // Get standard hours for deduction
        $standardHours = 8.00;
        if ($employee->position && $employee->position->shift) {
            $standardHours = $employee->position->shift->duration_hours ?? 8.00;
        }

        $attendance->update([
            'status' => 'absent',
            'net_hours' => 0.00,
            'regular_hours' => 0.00,
            'overtime_hours' => 0.00,
            'is_approved' => false,
            'notes' => 'No show - unplanned absence',
            'needs_review' => true,
            'is_unplanned' => true,
            'absence_type' => 'unplanned_absent',
            'hours_deducted' => $standardHours,
            'is_paid_absence' => false,
            'calculation_method' => 'auto',
            'sessions' => null,
        ]);

        Log::warning("Detected unplanned absence", [
            'employee_id' => $employee->employee_number,
            'date' => $date,
            'attendance_id' => $attendance->id
        ]);
    }

    /**
     * Fallback to original processing logic (kept for backward compatibility)
     */
    private function fallbackCalculation(Employee $employee, string $date): void
    {
        $attendance = $this->getOrCreateAttendanceRecord($employee, Carbon::parse($date));

        $events = ClockEvent::where('employee_id', $employee->employee_number)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp')
            ->get();

        // Original session processing logic (extracted from your old code)
        // $this->legacyProcessClockEvents($attendance, $events, $date);
    }

    /**
     * Legacy session processing - kept from original code
     */
    /*private function legacyProcessClockEvents(Attendance $attendance, $events, string $date): void
    {
        $sessions = [];
        $totalHours = 0.0;
        $currentState = 'out';
        $currentSessionStart = null;
        $sessionStartEvent = null;
        $notes = [];

        foreach ($events as $event) {
            if ($event->event_type === 'clock_in') {
                if ($currentState === 'out') {
                    $currentState = 'in';
                    $currentSessionStart = $event->timestamp;
                    $sessionStartEvent = $event;
                } else {
                    $notes[] = "Duplicate clock-in at {$event->timestamp->format('H:i')}";
                    Log::warning("Duplicate clock-in detected", [
                        'employee_id' => $attendance->employee_id,
                        'event_id' => $event->id,
                        'timestamp' => $event->timestamp
                    ]);
                }
            } elseif ($event->event_type === 'clock_out') {
                if ($currentState === 'in' && $currentSessionStart && $sessionStartEvent) {
                    if ($event->timestamp->greaterThan($currentSessionStart)) {
                        $duration = $currentSessionStart->diffInMinutes($event->timestamp) / 60.0;
                        $duration = round($duration, 2);
                        $totalHours += $duration;

                        // 1. Store in JSON (backward compatibility)
                        $sessions[] = [
                            'start' => $currentSessionStart->format('H:i'),
                            'end' => $event->timestamp->format('H:i'),
                            'duration' => $duration
                        ];

                        // 2. Create AttendanceSession record
                        AttendanceSession::create([
                            'attendance_id' => $attendance->id,
                            'clock_in_event_id' => $sessionStartEvent->id,
                            'clock_out_event_id' => $event->id,
                            'start_time' => $currentSessionStart,
                            'end_time' => $event->timestamp,
                            'duration_hours' => $duration,
                            'session_type' => 'work',
                            'is_overnight' => $currentSessionStart->format('Y-m-d') !== $event->timestamp->format('Y-m-d'),
                        ]);

                        $currentState = 'out';
                        $currentSessionStart = null;
                        $sessionStartEvent = null;
                    } else {
                        $notes[] = "Invalid clock-out (before clock-in) at {$event->timestamp->format('H:i')}";
                    }
                } else {
                    $notes[] = "Orphaned clock-out at {$event->timestamp->format('H:i')}";
                }
            }
        }

        // Handle orphaned clock-in (session started but not ended)
        if ($currentState === 'in' && $currentSessionStart && $sessionStartEvent) {
            $notes[] = "Open session started at {$currentSessionStart->format('H:i')}";

            // Create partial session for tracking
            AttendanceSession::create([
                'attendance_id' => $attendance->id,
                'clock_in_event_id' => $sessionStartEvent->id,
                'clock_out_event_id' => null,
                'start_time' => $currentSessionStart,
                'end_time' => null,
                'duration_hours' => 0.00,
                'session_type' => 'work',
                'is_overnight' => false,
                'notes' => 'Session not closed',
            ]);
        }

        // Determine status based on sessions
        $status = match (true) {
            $currentState === 'in' => 'incomplete',
            $totalHours >= 8.0 => 'complete',
            $totalHours > 0 && $totalHours < 8.0 => 'half_day',
            default => 'incomplete'
        };

        // Check for tardiness against shift schedule
        $isLate = $this->checkForTardiness($attendance->employee_id, $date, $events);
        if ($isLate) {
            $status = 'late';
            $notes[] = 'Late arrival detected';
        }

        $finalNote = !empty($notes) ? implode('; ', $notes) : null;

        $attendance->update([
            'net_hours' => round($totalHours, 2),
            'sessions' => !empty($sessions) ? json_encode($sessions) : null,
            'status' => $status,
            'notes' => $finalNote,
            'needs_review' => !empty($notes) || $isLate,
            'is_unplanned' => false,
            'absence_type' => $status === 'half_day' ? 'half_day' : null,
        ]);
    }*/









    /**
     * Get or create attendance record helper
     */
    private function getOrCreateAttendanceRecord(Employee $employee, Carbon $date): Attendance
    {
        $attendance = Attendance::where('employee_number', $employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'company' => $employee->department->company->name ?? 'N/A',
                'department' => $employee->department->name ?? 'N/A',
                'date' => $date,
                'status' => 'pending',
                'is_approved' => false,
                'net_hours' => 0.00,
            ]);
        }

        return $attendance;
    }

    /**
     * Check if date is a company holiday
     */
    private function isCompanyHoliday(string $date): bool
    {
        // Your existing implementation
        $dateObj = Carbon::parse($date);

        $exactMatch = Holiday::whereDate('date', $date)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('business_impact', 'office_closed')
                    ->orWhere('business_impact', 'reduced_staff');
            })
            ->exists();

        if ($exactMatch) {
            return true;
        }

        $observedMatch = Holiday::whereDate('observed_date', $date)
            ->where('is_active', true)
            ->whereNotNull('observed_date')
            ->where(function ($query) {
                $query->where('business_impact', 'office_closed')
                    ->orWhere('business_impact', 'reduced_staff');
            })
            ->exists();

        return $observedMatch;
    }

    /**
     * Batch recalculate for date range
     */
    public function recalculateDateRange(string $employeeNumber, string $startDate, string $endDate): void
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $this->recalculateForDay($employeeNumber, $date->format('Y-m-d'));
        }

        Log::info("Recalculated attendance range", [
            'employee_id' => $employeeNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_processed' => $period->count()
        ]);
    }
}
