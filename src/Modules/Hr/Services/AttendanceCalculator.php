<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\{
    ClockEvent,
    Attendance,
    AttendancePolicy,
    WorkPattern,
    Shift,
    ShiftSchedule,
    Employee,
    EmployeePosition,
    AttendanceSession,
    PolicyAssignment
};
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceCalculator
{
    /**
     * Calculate attendance for a specific employee and date
     * Creates/updates attendance record AND attendance sessions
     */
    public function calculateForDay(string $employeeNumber, Carbon $date): array
    {
        return DB::transaction(function () use ($employeeNumber, $date) {
            // 1. Get employee and position
            $employee = Employee::where('employee_number', $employeeNumber)->first();
            if (!$employee) {
                throw new \Exception("Employee not found: {$employeeNumber}");
            }

            $position = $employee->employeePosition;
            if (!$position) {
                throw new \Exception("Employee position not found for: {$employeeNumber}");
            }



            // 2. Get expected schedule and pattern using
            $pattern = $this->getApplicableWorkPattern($position, $date); // we already improved this
            $schedule = $this->getExpectedSchedule($employee, $position, $pattern, $date);
            $shift = $schedule['shift'] ?? null;

            // 3. Get policy with shift fallback
            $policy = $this->getApplicablePolicy($position, $date, $shift);





            // 4. Get clock events
            $events = ClockEvent::where('employee_id', $employeeNumber)
                ->whereDate('timestamp', $date)
                ->orderBy('timestamp')
                ->get();

            // 5. Process events into sessions
            $sessionData = $this->processClockEvents($events);
            $sessions = $sessionData['sessions'];
            $totalHours = $sessionData['total_hours'];
            $firstClockIn = $sessionData['first_clock_in'];
            $lastClockOut = $sessionData['last_clock_out'];

            // 6. Get or create attendance record
            $attendance = $this->getOrCreateAttendanceRecord($employee, $date, $schedule, $policy);

            // 7. DELETE existing sessions for this attendance (fresh calculation)
            AttendanceSession::where('attendance_id', $attendance->id)->delete();

            // 8. CREATE new sessions from processed events
            foreach ($sessions as $session) {
                AttendanceSession::create([
                    'attendance_id' => $attendance->id,
                    'clock_in_event_id' => $session['clock_in_event_id'] ?? null,
                    'clock_out_event_id' => $session['clock_out_event_id'] ?? null,
                    'start_time' => $session['start'],
                    'end_time' => $session['end'],
                    'duration_hours' => $session['duration'],
                    'session_type' => 'work',
                    'is_overnight' => $session['is_overnight'] ?? false,
                    'notes' => $session['notes'] ?? null,
                ]);
            }

            // 9. Calculate attendance metrics using policy
            $calculation = $this->calculateAttendanceMetrics(
                actualWorkedHours: $totalHours,
                firstClockIn: $firstClockIn,
                lastClockOut: $lastClockOut,
                schedule: $schedule,
                policy: $policy,
                employee: $employee,
                date: $date,
                sessions: $sessions
            );



            $dateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeekIso; // 1=Monday, 7=Sunday

            // When day of attendance is not part of the Work Pattern the status = 'unscheduled'
            // For example, 'Sun' in unschedule in the Work Pattern Mon-Fri
            if ($pattern && !in_array($dayOfWeek, explode(",", $pattern->applicable_days)))
                $calculation['status'] = 'unscheduled';


            // 10. Update attendance record with calculation results
            $attendance->update([
                'status' => $calculation['status'],
                'shift_id' => $shift?->id,
                'net_hours' => $calculation['total_hours'],
                'regular_hours' => $calculation['regular_hours'],
                'overtime_hours' => $calculation['overtime_hours'],
                'double_time_hours' => $calculation['double_time_hours'],
                'minutes_late' => $calculation['minutes_late'],
                'minutes_early_departure' => $calculation['minutes_early_departure'],
                'missed_break_minutes' => $calculation['missed_break_minutes'],
                'needs_review' => $calculation['needs_review'],
                'attendance_policy_id' => $policy?->id,
                'work_pattern_id' => $pattern?->id,
                'calculation_metadata' => json_encode($calculation['breakdown']),
                'calculation_version' => '1.0',
                'calculation_method' => 'auto',
                'sessions' => json_encode(array_map(function ($s) {
                    return [
                        'start' => $s['start'] ? $s['start']->format('H:i') : null,
                        'end' => $s['end'] ? $s['end']->format('H:i') : null,
                        'duration' => $s['duration']
                    ];
                }, $sessions)),
            ]);

            return [
                'success' => true,
                'attendance_id' => $attendance->id,
                'calculation' => $calculation,
                'sessions_created' => count($sessions)
            ];
        });
    }

    /**
     * Process raw clock events into work sessions
     */
    protected function processClockEvents($events): array
    {
        $sessions = [];
        $totalHours = 0.0;
        $firstClockIn = null;
        $lastClockOut = null;

        $inSession = false;
        $sessionStart = null;
        $sessionStartEvent = null;

        foreach ($events as $event) {
            if ($event->event_type === 'clock_in' && !$inSession) {
                $inSession = true;
                $sessionStart = $event->timestamp;
                $sessionStartEvent = $event;

                if (!$firstClockIn) {
                    $firstClockIn = $event->timestamp;
                }
            } elseif ($event->event_type === 'clock_out' && $inSession) {
                $sessionEnd = $event->timestamp;
                $duration = $sessionStart->diffInMinutes($sessionEnd) / 60.0;

                $sessions[] = [
                    'clock_in_event_id' => $sessionStartEvent->id,
                    'clock_out_event_id' => $event->id,
                    'start' => $sessionStart,
                    'end' => $sessionEnd,
                    'duration' => round($duration, 2),
                    'is_overnight' => $sessionStart->format('Y-m-d') !== $sessionEnd->format('Y-m-d'),
                    'notes' => null
                ];

                $totalHours += $duration;
                $lastClockOut = $event->timestamp;
                $inSession = false;
                $sessionStart = null;
                $sessionStartEvent = null;
            }
        }

        // Handle orphaned clock-in (no matching clock-out)
        if ($inSession && $sessionStart && $sessionStartEvent) {
            $sessions[] = [
                'clock_in_event_id' => $sessionStartEvent->id,
                'clock_out_event_id' => null,
                'start' => $sessionStart,
                'end' => null,
                'duration' => 0.0,
                'is_overnight' => false,
                'notes' => 'Missing clock-out'
            ];
        }

        return [
            'sessions' => $sessions,
            'total_hours' => round($totalHours, 2),
            'first_clock_in' => $firstClockIn,
            'last_clock_out' => $lastClockOut
        ];
    }

    /**
     * Calculate attendance metrics based on policy
     */
    protected function calculateAttendanceMetrics(
        float $actualWorkedHours,        // Renamed from $totalHours for clarity
        ?Carbon $firstClockIn,
        ?Carbon $lastClockOut,
        ?array $schedule,
        ?AttendancePolicy $policy,
        Employee $employee,
        Carbon $date,
        array $sessions
    ): array {


        $shiftId = null;
        if ($schedule && $schedule['shift'])
            $shiftId = $schedule['shift']->id;

        $result = [
            'status' => 'absent',
            'shift_id' => $shiftId,
            'total_hours' => 0.0,               // This will be payable hours (after deduction)
            'actual_hours' => $actualWorkedHours, // Store actual for breakdown
            'regular_hours' => 0.0,
            'overtime_hours' => 0.0,
            'double_time_hours' => 0.0,
            'minutes_late' => 0,
            'minutes_early_departure' => 0,
            'missed_break_minutes' => 0,
            'violations' => [],
            'breakdown' => [],
            'needs_review' => false
        ];



        // Ensure we always have a valid policy
        if (!$policy) {
            $policy = AttendancePolicy::where('is_default', true)->first();
            if (!$policy) {
                // This should never happen in a properly initialized system
                throw new \RuntimeException('No default attendance policy found.');
            }
        }



        // If no schedule, mark as unscheduled
        if (!$schedule) {
            $result['status'] = 'unscheduled';
            $result['needs_review'] = true;
            return $result;
        }

        // Compute expected hours for this schedule
        $expectedHours = $this->getExpectedHours($schedule);
        $result['breakdown']['expected_hours'] = $expectedHours;

        // If no hours and it's a work day → absent
        if ($actualWorkedHours == 0) {
            $result['status'] = 'absent';
            $result['needs_review'] = true;
            return $result;
        }

        // Check lateness (based on first clock-in)
        if ($firstClockIn) {
            $latenessCheck = $this->checkLateness(
                $firstClockIn,
                $schedule['start_time'],
                $policy->grace_period_minutes,
                $date
            );

            if ($latenessCheck['is_late']) {
                $result['minutes_late'] = $latenessCheck['minutes_late'];
                $result['violations'][] = [
                    'type' => 'late_arrival',
                    'minutes' => $latenessCheck['minutes_late']
                ];
            }
        }

        // Check early departure (based on last clock-out)
        if ($lastClockOut) {
            $earlyDepartureCheck = $this->checkEarlyDeparture(
                $lastClockOut,
                $schedule['end_time'],
                $policy->early_departure_grace_minutes,
                $date
            );

            if ($earlyDepartureCheck['is_early']) {
                $result['minutes_early_departure'] = $earlyDepartureCheck['minutes_early'];
                $result['violations'][] = [
                    'type' => 'early_departure',
                    'minutes' => $earlyDepartureCheck['minutes_early']
                ];
            }
        }

        // Calculate overtime breakdown based on actual worked hours
        $overtimeCalculation = $this->calculateOvertime(
            totalHours: $actualWorkedHours,
            policy: $policy,
            date: $date,
            employeeId: $employee->id
        );

        $result['regular_hours'] = $overtimeCalculation['regular_hours'];
        $result['overtime_hours'] = $overtimeCalculation['overtime_hours'];
        $result['double_time_hours'] = $overtimeCalculation['double_time_hours'];
        $result['breakdown']['overtime_calculation'] = $overtimeCalculation['breakdown'];

        // Check break compliance (requires break after X hours)
        if ($policy->requires_break_after_hours > 0 && $policy->break_duration_minutes > 0) {
            $breakCheck = $this->checkBreakCompliance(
                $sessions,
                $policy->requires_break_after_hours,
                $policy->break_duration_minutes
            );

            if ($breakCheck['missed_break']) {
                $result['missed_break_minutes'] = $breakCheck['missed_minutes'];
                $result['violations'][] = [
                    'type' => 'missed_break',
                    'minutes' => $breakCheck['missed_minutes']
                ];
            }
        }

        // --- Determine payable hours (after unpaid break deduction) ---
        $payableHours = $actualWorkedHours;
        if ($policy->unpaid_break_minutes > 0 && $actualWorkedHours > 0) {
            $deductionHours = $policy->unpaid_break_minutes / 60;
            $payableHours = max(0, $actualWorkedHours - $deductionHours);
            $result['breakdown']['unpaid_break_deducted'] = $policy->unpaid_break_minutes;
        }
        $result['total_hours'] = round($payableHours, 2);

        // --- Determine final status based on actual worked hours (not payable) ---
        $hasViolations = !empty($result['violations']);
        $result['status'] = $this->determineStatus(
            $actualWorkedHours,               // Use actual hours for status
            $result['minutes_late'],
            $result['minutes_early_departure'],
            $expectedHours,
            $hasViolations
        );

        // Mark as needing review if violations or special statuses
        $result['needs_review'] = $hasViolations ||
            $result['status'] === 'incomplete' ||
            $result['status'] === 'half_day' ||
            $result['status'] === 'unscheduled';

        // Store violations in breakdown for audit
        $result['breakdown']['violations'] = $result['violations'];

        return $result;
    }


    /**
     * Get or create attendance record
     */
    protected function getOrCreateAttendanceRecord(Employee $employee, Carbon $date, $schedule = null, $policy = null): Attendance
    {
        $attendance = Attendance::where('employee_number', $employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $shiftId = null;
        if ($schedule && $schedule['shift'])
            $shiftId = $schedule['shift']->id;

        if (!$attendance) {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'shift_id' => $shiftId,
                'attendance_policy_id' => $policy?->id,
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











public function getApplicablePolicy(EmployeePosition $position, Carbon $date, ?\App\Modules\Hr\Models\Shift $shift = null): ?AttendancePolicy
{
    // Priority 1: Employee-specific policy (direct override)
    if ($position->attendance_policy_id) {
        $policy = AttendancePolicy::find($position->attendance_policy_id);
        if ($policy && $this->isPolicyActive($policy, $date)) {
            return $policy;
        }
    }

    // Priority 2: Shift-specific policy (only via PolicyAssignment)
    if ($shift) {
        $policy = $this->getPolicyForEntity(\App\Modules\Hr\Models\Shift::class, $shift->id, $date);
        if ($policy) return $policy;
    }

    // Priority 3: Department policy
    if ($position->department) {
        $policy = $this->getPolicyForEntity(\App\Modules\Admin\Models\Department::class, $position->department->id, $date);
        if ($policy) return $policy;
    }

    // Priority 4: Location policy
    if ($position->location) {
        $policy = $this->getPolicyForEntity(\App\Modules\Admin\Models\Location::class, $position->location->id, $date);
        if ($policy) return $policy;
    }

    // Priority 5: Company policy
    if ($position->department && $position->department->company) {
        $policy = $this->getPolicyForEntity(\App\Modules\Admin\Models\Company::class, $position->department->company->id, $date);
        if ($policy) return $policy;
    }

    // Priority 6: System-wide default policy
    return AttendancePolicy::where('is_default', true)
        ->where('is_active', true)
        ->whereDate('effective_date', '<=', $date)
        ->where(function ($q) use ($date) {
            $q->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', $date);
        })
        ->first();
}

    

/**
 * Fetch a policy assigned to a specific entity (company, location, department, shift).
 *
 * @param string $modelClass Fully qualified model class (e.g., 'App\Modules\Admin\Models\Company')
 * @param int $id
 * @param Carbon $date
 * @return AttendancePolicy|null
 */
protected function getPolicyForEntity(string $modelClass, int $id, Carbon $date): ?AttendancePolicy
{
    $cacheKey = "policy_for_{$modelClass}_{$id}";
    return \Cache::remember($cacheKey, 3600, function () use ($modelClass, $id, $date) {
        $assignment = PolicyAssignment::where('assignable_type', $modelClass)
            ->where('assignable_id', $id)
            ->with('attendancePolicy')
            ->first();

        if ($assignment && $this->isPolicyActive($assignment->attendancePolicy, $date)) {
            return $assignment->attendancePolicy;
        }

        return null;
    });
}

    /**
     * Check if a policy is active on the given date.
     *
     * @param AttendancePolicy $policy
     * @param Carbon $date
     * @return bool
     */
    protected function isPolicyActive(AttendancePolicy $policy, Carbon $date): bool
    {
        if (!$policy->is_active)
            return false;
        if ($policy->effective_date && $policy->effective_date > $date)
            return false;
        if ($policy->expiration_date && $policy->expiration_date < $date)
            return false;
        return true;
    }

    /**
     * Get the applicable work pattern for an employee on a given date.
     * Priority: Employee work pattern (from employee_work_patterns) > System default.
     *
     * @param \App\Modules\Hr\Models\Employee $employee
     * @param Carbon $date
     * @return \App\Modules\Hr\Models\WorkPattern|null
     */
    public function getApplicableWorkPattern(EmployeePosition $employee, Carbon $date): ?\App\Modules\Hr\Models\WorkPattern
    {
        // 1. Employee-specific active work pattern
        $employeeWorkPattern = $employee->employeeWorkPatterns()
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->with('workPattern')
            ->first();

        if ($employeeWorkPattern && $this->isWorkPatternActive($employeeWorkPattern->workPattern, $date)) {
            return $employeeWorkPattern->workPattern;
        }

        // 2. System-wide default work pattern
        return \App\Modules\Hr\Models\WorkPattern::where('is_default', true)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            })
            ->first();
    }

    /**
     * Check if a work pattern is active on the given date.
     *
     * @param \App\Modules\Hr\Models\WorkPattern $pattern
     * @param Carbon $date
     * @return bool
     */
    protected function isWorkPatternActive(\App\Modules\Hr\Models\WorkPattern $pattern, Carbon $date): bool
    {
        if (!$pattern->is_active)
            return false;
        if ($pattern->effective_date && $pattern->effective_date > $date)
            return false;
        if ($pattern->end_date && $pattern->end_date < $date)
            return false;
        return true;
    }

    /**
     * Get the expected schedule for an employee on a given date.
     *
     * @param \App\Modules\Hr\Models\Employee $employee
     * @param EmployeePosition $position
     * @param \App\Modules\Hr\Models\WorkPattern|null $pattern
     * @param Carbon $date
     * @return array|null
     */
    public function getExpectedSchedule(
        \App\Modules\Hr\Models\Employee $employee,
        EmployeePosition $position,
        ?\App\Modules\Hr\Models\WorkPattern $pattern,
        Carbon $date
    ): ?array {
        $dateString = $date->toDateString();
        $dayOfWeek = $date->dayOfWeekIso; // 1=Monday, 7=Sunday

        // Priority 1: Specific ShiftSchedule for the date
        $shiftSchedule = \App\Modules\Hr\Models\ShiftSchedule::where('employee_id', $employee->id)
            ->whereDate('schedule_date', $dateString)
            ->where('is_published', true)
            ->first();

        if ($shiftSchedule) {
            return [
                'type' => 'specific_shift_schedule',
                'schedule' => $shiftSchedule,
                'start_time' => $shiftSchedule->start_time_override
                    ? Carbon::parse($shiftSchedule->start_time_override)
                    : Carbon::parse($shiftSchedule->shift->start_time),
                'end_time' => $shiftSchedule->end_time_override
                    ? Carbon::parse($shiftSchedule->end_time_override)
                    : Carbon::parse($shiftSchedule->shift->end_time),
                'shift' => $shiftSchedule->shift
            ];
        }

        // Priority 2: WorkPattern for the day of week
        if ($pattern && in_array($dayOfWeek, explode(",", $pattern->applicable_days))) {
            $shift = $pattern->shift;
            $startTimeString = $pattern->override_start_time ?: $shift->start_time;
            $endTimeString = $pattern->override_end_time ?: $shift->end_time;

            return [
                'type' => 'work_pattern',
                'pattern' => $pattern,
                'shift' => $shift,
                'start_time' => $date->copy()->setTimeFromTimeString($startTimeString),
                'end_time' => $date->copy()->setTimeFromTimeString($endTimeString),
                'is_overnight' => $shift->is_overnight
            ];
        }

        // Priority 3: Employee's default shift from position
        if ($position->shift_id && $position->shift->is_active) {
            $shift = $position->shift;
            return [
                'type' => 'user_default_shift',
                'shift' => $shift,
                'start_time' => $date->copy()->setTimeFromTimeString($shift->start_time),
                'end_time' => $date->copy()->setTimeFromTimeString($shift->end_time),
                'is_overnight' => $shift->is_overnight
            ];
        }

        // Priority 4: System-wide default shift
        $defaultShift = Shift::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($defaultShift) {
            return [
                'type' => 'system_default_shift',
                'shift' => $defaultShift,
                'start_time' => $date->copy()->setTimeFromTimeString($defaultShift->start_time),
                'end_time' => $date->copy()->setTimeFromTimeString($defaultShift->end_time),
                'is_overnight' => $defaultShift->is_overnight
            ];
        }

        return null;
    }










































    /**
     * Check for lateness
     */
    protected function checkLateness(?Carbon $actualStart, Carbon $scheduledStart, int $graceMinutes, Carbon $date): array
    {
        if (!$actualStart) {
            return ['is_late' => false, 'minutes_late' => 0];
        }

        $graceTime = $scheduledStart->copy()->addMinutes($graceMinutes);

        if ($actualStart->greaterThan($graceTime)) {
            $minutesLate = $actualStart->diffInMinutes($graceTime);
            return ['is_late' => true, 'minutes_late' => $minutesLate];
        }

        return ['is_late' => false, 'minutes_late' => 0];
    }

    /**
     * Check for early departure
     */
    protected function checkEarlyDeparture(?Carbon $actualEnd, Carbon $scheduledEnd, int $graceMinutes, Carbon $date): array
    {
        if (!$actualEnd) {
            return ['is_early' => false, 'minutes_early' => 0];
        }

        $graceTime = $scheduledEnd->copy()->subMinutes($graceMinutes);

        if ($actualEnd->lessThan($graceTime)) {
            $minutesEarly = $graceTime->diffInMinutes($actualEnd);
            return ['is_early' => true, 'minutes_early' => $minutesEarly];
        }

        return ['is_early' => false, 'minutes_early' => 0];
    }

    /**
     * Calculate overtime breakdown
     */
    protected function calculateOvertime(float $totalHours, AttendancePolicy $policy, Carbon $date, int $employeeId): array
    {
        $regularHours = 0.0;
        $overtimeHours = 0.0;
        $doubleTimeHours = 0.0;
        $breakdown = [];

        // Daily overtime
        if ($totalHours > $policy->overtime_daily_threshold_hours) {
            $overtimeHours = $totalHours - $policy->overtime_daily_threshold_hours - ($policy->unpaid_break_minutes / 60); // Convert min to hour
            $regularHours = $policy->overtime_daily_threshold_hours;

            // Apply max daily overtime limit
            if ($policy->max_daily_overtime_hours > 0 && $overtimeHours > $policy->max_daily_overtime_hours) {
                $overtimeHours = $policy->max_daily_overtime_hours;
                $breakdown['daily_overtime_capped'] = true;
            }

            // Check for double time
            if (
                $policy->double_time_threshold_hours > 0 &&
                $totalHours > $policy->double_time_threshold_hours
            ) {

                $doubleTimeHours = $totalHours - $policy->double_time_threshold_hours;
                $overtimeHours -= $doubleTimeHours;

                // Ensure overtime hours don't go negative
                if ($overtimeHours < 0) {
                    $doubleTimeHours += $overtimeHours;
                    $overtimeHours = 0;
                }
            }
        } else {
            $regularHours = $totalHours;
        }

        // Weekly overtime (simplified - in reality need to check past 7 days)
        // You'll need to implement this with a weekly aggregation
        $breakdown['daily_threshold'] = $policy->overtime_daily_threshold_hours;
        $breakdown['weekly_threshold'] = $policy->overtime_weekly_threshold_hours;
        $breakdown['max_daily_overtime'] = $policy->max_daily_overtime_hours;
        $breakdown['double_time_threshold'] = $policy->double_time_threshold_hours;

        return [
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'double_time_hours' => round($doubleTimeHours, 2),
            'breakdown' => $breakdown
        ];
    }





    /**
     * Calculate the expected duration of a shift (from start to end, including any unpaid breaks)
     * This is used for attendance status comparison (e.g., half-day, incomplete).
     *
     * @param array|null $schedule The schedule array (from getExpectedSchedule)
     * @return float Expected hours between start and end (including overnight)
     */
    protected function getExpectedHours(?array $schedule): float
    {
        if (!$schedule) {
            return 8.0; // Default fallback if no schedule
        }

        // If the shift already has a pre-calculated duration, use it
        if (isset($schedule['shift']->duration_hours) && $schedule['shift']->duration_hours > 0) {
            return (float) $schedule['shift']->duration_hours;
        }

        $start = $schedule['start_time'];
        $end = $schedule['end_time'];

        // Calculate duration (already handles overnight because end_time is Carbon with correct date)
        $duration = $start->diffInMinutes($end) / 60.0;

        return round($duration, 2);
    }




    /**
     * Check if required break was taken
     */
    protected function checkBreakCompliance(
        array $sessions,
        float $requiresBreakAfterHours,
        int $requiredBreakMinutes
    ): array {
        if (!$requiresBreakAfterHours || $requiredBreakMinutes <= 0 || empty($sessions)) {
            return ['missed_break' => false, 'missed_minutes' => 0];
        }

        $sessions = collect($sessions)->sortBy('start')->values()->toArray();
        $cumulativeHours = 0;
        $requiredBreakSeconds = $requiredBreakMinutes * 60;

        for ($i = 0; $i < count($sessions); $i++) {
            $session = $sessions[$i];
            if (!$session['end'])
                continue;

            $sessionHours = $session['duration'] ?? 0;
            $cumulativeHours += $sessionHours;

            // 1. Check if the CURRENT stretch exceeds the limit
            if ($cumulativeHours > $requiresBreakAfterHours) {
                return [
                    'missed_break' => true,
                    'missed_minutes' => $requiredBreakMinutes
                ];
            }

            // 2. Check if there is a valid break BEFORE the next session
            if ($i < count($sessions) - 1) {
                $nextSessionStart = Carbon::parse($sessions[$i + 1]['start']);
                $thisSessionEnd = Carbon::parse($session['end']);
                $gapSeconds = $thisSessionEnd->diffInSeconds($nextSessionStart);

                if ($gapSeconds >= $requiredBreakSeconds) {
                    // VALID BREAK TAKEN: Reset the work timer
                    $cumulativeHours = 0;
                }
            }
        }

        return ['missed_break' => false, 'missed_minutes' => 0];
    }


    /**
     * Determine final status with refined logic
     */
    protected function determineStatus(
        float $totalHours,
        int $minutesLate,
        int $minutesEarly,
        float $expectedHours,
        bool $hasViolations = false
    ): string {
        if ($totalHours == 0) {
            return 'absent';
        }

        // Priority: late or early departure
        if ($minutesLate > 0) {
            return 'late';
        }



        // Half-day if less than 50% of expected hours (configurable threshold)
        $halfDayThreshold = $expectedHours * 0.5;
        $earlyDepartureThreshold = $expectedHours * 0.9;
        if ($totalHours <= $halfDayThreshold) {
            return 'half_day';
        }


        // Incomplete if less than expected but more than half
        if ($totalHours > $halfDayThreshold && $totalHours < $earlyDepartureThreshold) {
            return 'incomplete';
        }


        if ($minutesEarly > 0) {
            return 'early_departure';
        }



        return 'present';
    }






    /**
     * Get default policy values when no policy is assigned
     */
    protected function getDefaultPolicyValues(): object
    {
        return (object) [
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
            'unpaid_break_minutes' => 0
        ];
    }

    /**
     * Update or create attendance record with calculation results
     */
    /*protected function updateAttendanceRecord(
        Employee $employee,
        Carbon $date,
        array $calculation,
        ?AttendancePolicy $policy,
        ?WorkPattern $pattern
    ): Attendance {
        $attendance = Attendance::where('employee_number', $employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->employee_id = $employee->id;
            $attendance->employee_number = $employee->employee_number;
            $attendance->date = $date;
        }

        // Update with calculation results
        $attendance->status = $calculation['status'];
        $attendance->net_hours = $calculation['total_hours'];
        $attendance->regular_hours = $calculation['regular_hours'];
        $attendance->overtime_hours = $calculation['overtime_hours'];
        $attendance->double_time_hours = $calculation['double_time_hours'];
        $attendance->minutes_late = $calculation['minutes_late'];
        $attendance->minutes_early_departure = $calculation['minutes_early_departure'];
        $attendance->missed_break_minutes = $calculation['missed_break_minutes'];
        $attendance->needs_review = $calculation['needs_review'];
        $attendance->attendance_policy_id = $policy?->id;
        $attendance->work_pattern_id = $pattern?->id;
        $attendance->calculation_metadata = json_encode($calculation['breakdown']);
        $attendance->calculation_version = '1.0';
        $attendance->calculation_method = 'auto';

        $attendance->save();

        return $attendance;
    }*/


    /**
     * Core calculation logic
     */
    /*protected function calculateAttendance(
        $events,
        ?array $schedule,
        ?AttendancePolicy $policy,
        ?WorkPattern $pattern,
        Employee $employee,
        Carbon $date
    ): array {
        $result = [
            'status' => 'absent',
            'shift_id' => null,
            'total_hours' => 0.0,
            'regular_hours' => 0.0,
            'overtime_hours' => 0.0,
            'double_time_hours' => 0.0,
            'minutes_late' => 0,
            'minutes_early_departure' => 0,
            'missed_break_minutes' => 0,
            'violations' => [],
            'breakdown' => [],
            'needs_review' => false
        ];

        // If no schedule, mark as unscheduled
        if (!$schedule) {
            $result['status'] = 'unscheduled';
            $result['needs_review'] = true;
            return $result;
        }

        // If no policy, use defaults
        if (!$policy) {
            $policy = $this->getDefaultPolicyValues();
        }

        // Calculate total hours from clock events
        $sessions = $this->processClockEvents($events);
        $totalHours = $sessions['total_hours'];
        $result['total_hours'] = $totalHours;
        $result['breakdown']['sessions'] = $sessions['sessions'];

        // Check if it's a work day (has schedule but no hours could mean absence)
        if ($totalHours == 0) {
            $result['status'] = 'absent';
            $result['needs_review'] = true;
            return $result;
        }

        // Check lateness
        $latenessCheck = $this->checkLateness(
            $sessions['first_clock_in'],
            $schedule['start_time'],
            $policy->grace_period_minutes,
            $date
        );

        if ($latenessCheck['is_late']) {
            $result['minutes_late'] = $latenessCheck['minutes_late'];
            $result['violations'][] = [
                'type' => 'late_arrival',
                'minutes' => $latenessCheck['minutes_late']
            ];
        }

        // Check early departure
        $earlyDepartureCheck = $this->checkEarlyDeparture(
            $sessions['last_clock_out'],
            $schedule['end_time'],
            $policy->early_departure_grace_minutes,
            $date
        );

        if ($earlyDepartureCheck['is_early']) {
            $result['minutes_early_departure'] = $earlyDepartureCheck['minutes_early'];
            $result['violations'][] = [
                'type' => 'early_departure',
                'minutes' => $earlyDepartureCheck['minutes_early']
            ];
        }

        // Calculate overtime breakdown
        $overtimeCalculation = $this->calculateOvertime(
            $totalHours,
            $policy,
            $date,
            $employee->id
        );

        $result['regular_hours'] = $overtimeCalculation['regular_hours'];
        $result['overtime_hours'] = $overtimeCalculation['overtime_hours'];
        $result['double_time_hours'] = $overtimeCalculation['double_time_hours'];
        $result['breakdown']['overtime_calculation'] = $overtimeCalculation['breakdown'];

        // Check break compliance
        $breakCheck = $this->checkBreakCompliance(
            $sessions['sessions'],
            $policy->requires_break_after_hours,
            $policy->break_duration_minutes
        );

        if ($breakCheck['missed_break']) {
            $result['missed_break_minutes'] = $breakCheck['missed_minutes'];
            $result['violations'][] = [
                'type' => 'missed_break',
                'minutes' => $breakCheck['missed_minutes']
            ];
        }

        // Determine final status
        $result['status'] = $this->determineStatus(
            $totalHours,
            $result['minutes_late'],
            $result['minutes_early_departure'],
            $this->getExpectedHours($schedule),
            count($result['violations'])
        );

        $result['needs_review'] = !empty($result['violations']) ||
            $result['status'] === 'incomplete' ||
            $result['status'] === 'half_day';


        $result['shift_id'] = $schedule['shift']? $schedule['shift']->id : null;

        // Add violation to the breakdown
        $result['breakdown']['violations'] = $result['violations'];


        return $result;
    }*/




}
