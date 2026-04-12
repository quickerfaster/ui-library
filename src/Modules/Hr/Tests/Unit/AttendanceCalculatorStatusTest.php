<?php

namespace App\Modules\Hr\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use App\Modules\Hr\Models\{
    Employee, EmployeePosition, EmployeeWorkPattern, Shift, WorkPattern, AttendancePolicy,
    PolicyAssignment, ClockEvent, Attendance, AttendanceSession, ShiftSchedule
};
use App\Modules\Hr\Services\AttendanceCalculator;

class AttendanceCalculatorStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;
    protected Shift $shift;
    protected WorkPattern $workPattern;
    protected AttendancePolicy $defaultPolicy;
    protected AttendanceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base shift (not default)
        $this->shift = Shift::factory()->create([
            'name' => 'Standard 8-5',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'duration_hours' => 8.0,
            'is_overnight' => false,
            'is_default' => false,
        ]);

        // Create default work pattern (system-wide fallback)
        $this->workPattern = WorkPattern::factory()->create([
            'name' => 'Mon-Fri',
            'shift_id' => $this->shift->id,
            'applicable_days' => '1,2,3,4,5',
            'pattern_type' => 'recurring',
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create employee
        $this->employee = Employee::factory()->create([
            'employee_number' => 'EMP-TEST-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Create default attendance policy (system-wide fallback)
        $this->defaultPolicy = AttendancePolicy::factory()->create([
            'name' => 'Default Policy',
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
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create employee position (without direct work_pattern_id or attendance_policy_id)
        EmployeePosition::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
            'attendance_policy_id' => null, // employee-specific override
            'pay_type' => 'hourly',
            'hourly_rate' => 20.00,
            'start_date' => '2026-01-01',
        ]);

        // Assign the default work pattern to the employee via EmployeeWorkPattern
        EmployeeWorkPattern::create([
            'employee_id' => $this->employee->id,
            'work_pattern_id' => $this->workPattern->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $this->calculator = new AttendanceCalculator();
    }

    // ------------------------------------------------------------------------
    // Helper method to create clock events for the current employee
    // ------------------------------------------------------------------------
    protected function createClockEvent(string $type, Carbon $timestamp, ?string $employeeNumber = null): ClockEvent
    {
        $employeeNumber = $employeeNumber ?? $this->employee->employee_number;
        return ClockEvent::factory()->create([
            'employee_id' => $employeeNumber,
            'event_type' => $type,
            'timestamp' => $timestamp,
            'method' => 'test',
        ]);
    }

    // ------------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------------

    /** @test */
    public function it_handles_multiple_sessions_correctly()
    {
        $date = Carbon::parse('2026-02-16'); // Monday

        // Morning session: 08:00 - 12:00 (4 hours)
        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(12, 0));

        // Afternoon session: 13:00 - 17:00 (4 hours)
        $this->createClockEvent('clock_in', $date->copy()->setTime(13, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(17, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals(8.0, $attendance->net_hours);
        $this->assertEquals(0, $attendance->minutes_late);
        $this->assertEquals(0, $attendance->minutes_early_departure);

        $sessions = AttendanceSession::where('attendance_id', $attendance->id)->get();
        $this->assertCount(2, $sessions);
    }

    /** @test */
    public function it_marks_late_based_on_first_clock_in()
    {
        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 10));
        $this->createClockEvent('clock_out', $date->copy()->setTime(12, 0));
        $this->createClockEvent('clock_in', $date->copy()->setTime(13, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(17, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(5, $attendance->minutes_late); // 08:10 - (08:00+5) = 5
        $this->assertNotEquals(8.0, $attendance->net_hours);
    }

    /** @test */
    public function it_marks_early_departure_based_on_last_clock_out()
    {
        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(12, 0));
        $this->createClockEvent('clock_in', $date->copy()->setTime(13, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(16, 50));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('early_departure', $attendance->status);
        $this->assertEquals(5, $attendance->minutes_early_departure);
        $this->assertEquals(7.83, round($attendance->net_hours, 2));
    }

    /** @test */
    public function it_marks_half_day_when_hours_less_than_50_percent_of_expected()
    {
        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(11, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('half_day', $attendance->status);
        $this->assertEquals(3.0, $attendance->net_hours);
        $this->assertTrue((bool) $attendance->needs_review);
    }

    /** @test */
    public function it_marks_incomplete_when_hours_between_50_and_90_percent()
    {
        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(14, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('incomplete', $attendance->status);
        $this->assertEquals(6.0, $attendance->net_hours);
        $this->assertTrue((bool) $attendance->needs_review);
    }

    /** @test */
    public function it_marks_early_departure_when_hours_between_90_and_100_percent()
    {
        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(16, 30));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('early_departure', $attendance->status);
        $this->assertEquals(8.5, $attendance->net_hours);
        $this->assertTrue((bool) $attendance->needs_review);
    }


// ... rest of the test class

/** @test */
public function it_uses_shift_policy_when_no_higher_policy_exists()
{
    // Deactivate the default policy so it's not used as fallback
    $this->defaultPolicy->update(['is_active' => false, 'is_default' => false]);

    $shiftPolicy = AttendancePolicy::factory()->create([
        'name' => 'Shift Policy',
        'is_active' => true,
        'is_default' => false,
    ]);

    $shift = Shift::factory()->create([
        'start_time' => '09:00:00',
        'end_time' => '18:00:00',
        'duration_hours' => 8.0,
    ]);

    // Assign policy to shift via PolicyAssignment
    PolicyAssignment::create([
        'shift_id' => $shift->id,
        'attendance_policy_id' => $shiftPolicy->id,
    ]);

    $employee = Employee::factory()->create([
        'employee_number' => 'EMP-TEST-002',
    ]);

    EmployeePosition::factory()->create([
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'attendance_policy_id' => null,
        'pay_type' => 'hourly',
        'hourly_rate' => 20.00,
        'start_date' => '2026-01-01',
    ]);

    $date = Carbon::parse('2026-02-16');
    ShiftSchedule::factory()->create([
        'employee_id' => $employee->id,
        'schedule_date' => $date->toDateString(),
        'shift_id' => $shift->id,
        'is_published' => true,
    ]);

    $this->createClockEvent('clock_in', $date->copy()->setTime(9, 0), $employee->employee_number);
    $this->createClockEvent('clock_out', $date->copy()->setTime(18, 0), $employee->employee_number);

    $this->calculator->calculateForDay($employee->employee_number, $date);

    $attendance = Attendance::where('employee_number', $employee->employee_number)
        ->whereDate('date', $date)
        ->first();

    $this->assertEquals($shiftPolicy->id, $attendance->attendance_policy_id);
}

/** @test */
public function shift_policy_is_overridden_by_higher_level_policies()
{
    $employeePolicy = AttendancePolicy::factory()->create(['name' => 'Employee Policy']);
    $shiftPolicy = AttendancePolicy::factory()->create(['name' => 'Shift Policy']);

    $shift = Shift::factory()->create();

    // Assign shift policy via PolicyAssignment
    PolicyAssignment::create([
        'shift_id' => $shift->id,
        'attendance_policy_id' => $shiftPolicy->id,
    ]);

    // Assign employee policy directly to position
    $this->employee->employeePosition->attendance_policy_id = $employeePolicy->id;
    $this->employee->employeePosition->save();

    $date = Carbon::parse('2026-02-16');
    $policy = $this->calculator->getApplicablePolicy(
        $this->employee->employeePosition,
        $date,
        $shift
    );

    $this->assertEquals($employeePolicy->id, $policy->id);
}

/** @test */
public function shift_policy_is_used_when_higher_policies_are_inactive_or_expired()
{
    $shiftPolicy = AttendancePolicy::factory()->create([
        'name' => 'Shift Policy',
        'effective_date' => '2026-01-01',
        'is_active' => true,
    ]);

    $shift = Shift::factory()->create();

    // Assign shift policy via PolicyAssignment
    PolicyAssignment::create([
        'shift_id' => $shift->id,
        'attendance_policy_id' => $shiftPolicy->id,
    ]);

    // Higher-level policy exists but is inactive
    $inactivePolicy = AttendancePolicy::factory()->create([
        'name' => 'Inactive Policy',
        'is_active' => false,
    ]);
    $this->employee->employeePosition->attendance_policy_id = $inactivePolicy->id;
    $this->employee->employeePosition->save();

    $date = Carbon::parse('2026-02-16');
    $policy = $this->calculator->getApplicablePolicy(
        $this->employee->employeePosition,
        $date,
        $shift
    );

    $this->assertEquals($shiftPolicy->id, $policy->id);
}

/** @test */
public function shift_policy_is_skipped_if_shift_has_no_assigned_policy()
{
    $shift = Shift::factory()->create();

    $employee = Employee::factory()->create(['employee_number' => 'EMP-TEST-002']);
    EmployeePosition::factory()->create([
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'attendance_policy_id' => null,
        'pay_type' => 'hourly',
        'hourly_rate' => 20.00,
        'start_date' => '2026-01-01',
    ]);

    // No PolicyAssignment for this shift

    $date = Carbon::parse('2026-02-16');
    $policy = $this->calculator->getApplicablePolicy(
        $employee->employeePosition,
        $date,
        $shift
    );

    // Should fall back to system default policy
    $this->assertEquals($this->defaultPolicy->id, $policy->id);
}

    /** @test */
    public function it_detects_when_break_is_taken_correctly()
    {
        $date = Carbon::parse('2026-02-16');

        // Morning: 08:00 - 12:00
        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(12, 0));

        // Gap of 60 minutes (break)
        // Afternoon: 13:00 - 17:00
        $this->createClockEvent('clock_in', $date->copy()->setTime(13, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(17, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals(0, $attendance->missed_break_minutes);
        $this->assertFalse((bool) $attendance->needs_review);
    }

    /** @test */
    public function it_detects_missed_break_when_no_adequate_gap()
    {
        $date = Carbon::parse('2026-02-16');

        // Work continuously from 08:00 to 14:00 (6 hours) with no gap
        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(14, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals(30, $attendance->missed_break_minutes);
        $this->assertTrue((bool) $attendance->needs_review);

        $metadata = json_decode($attendance->calculation_metadata, true);
        $this->assertStringContainsString('missed_break', json_encode($metadata));
    }

    /** @test */
    public function it_applies_unpaid_break_deduction_from_policy()
    {
        $policy = $this->defaultPolicy;
        $policy->unpaid_break_minutes = 30;
        $policy->save();

        $date = Carbon::parse('2026-02-16');

        // Work 8 hours with a one-hour lunch break
        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(12, 0));
        $this->createClockEvent('clock_in', $date->copy()->setTime(13, 0));
        $this->createClockEvent('clock_out', $date->copy()->setTime(17, 0));

        $this->calculator->calculateForDay($this->employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $this->employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        // Total worked = 8, minus 0.5 unpaid = 7.5
        $this->assertEquals(7.5, $attendance->net_hours);
    }

    /** @test */
    public function it_calculates_expected_hours_from_shift_duration()
    {
        $shift = Shift::factory()->create([
            'name' => 'Standard 8-5',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'duration_hours' => 9.0, // explicitly set
            'is_overnight' => false,
        ]);

        $workPattern = WorkPattern::factory()->create([
            'name' => 'Mon-Fri 2',
            'shift_id' => $shift->id,
            'applicable_days' => '1,2,3,4,5',
            'pattern_type' => 'recurring',
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'is_default' => false,
        ]);

        $employee = Employee::factory()->create(['employee_number' => 'EMP-TEST-002']);
        EmployeePosition::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'attendance_policy_id' => null,
            'pay_type' => 'hourly',
            'hourly_rate' => 20.00,
            'start_date' => '2026-01-01',
        ]);

        EmployeeWorkPattern::create([
            'employee_id' => $employee->id,
            'work_pattern_id' => $workPattern->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0), $employee->employee_number);
        $this->createClockEvent('clock_out', $date->copy()->setTime(17, 0), $employee->employee_number);

        $this->calculator->calculateForDay($employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(9.0, $attendance->net_hours);
    }

    /** @test */
    public function it_handles_no_schedule_gracefully()
    {
        // Deactivate the default work pattern so no schedule is found
        $this->workPattern->update(['is_active' => false, 'is_default' => false]);

        $employee = Employee::factory()->create(['employee_number' => 'EMP-TEST-002']);
        EmployeePosition::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => null,
            'attendance_policy_id' => null,
            'pay_type' => 'hourly',
            'hourly_rate' => 20.00,
            'start_date' => '2026-01-01',
        ]);

        $date = Carbon::parse('2026-02-16');

        $this->createClockEvent('clock_in', $date->copy()->setTime(8, 0), $employee->employee_number);
        $this->createClockEvent('clock_out', $date->copy()->setTime(17, 0), $employee->employee_number);

        $this->calculator->calculateForDay($employee->employee_number, $date);

        $attendance = Attendance::where('employee_number', $employee->employee_number)
            ->whereDate('date', $date)
            ->first();

        $this->assertEquals('unscheduled', $attendance->status);
        $this->assertEquals(0.0, $attendance->net_hours);
        $this->assertTrue((bool) $attendance->needs_review);
    }
}
