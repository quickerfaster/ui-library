# Clock In/Out — Fix Plan

> **Date**: 2026-09-14
> **Status**: ✅ All phases implemented
> **Geofencing**: See [`geofencing-implementation.md`](geofencing-implementation.md)
> **Related**: [ClockInOut component](../../src/Http/Livewire/QuickActions/ClockInOut.php), [ClockEventRecorderService](../../app/Modules/Attendance/Services/ClockEventRecorderService.php), [AttendanceAggregator](../../app/Modules/Attendance/Services/AttendanceAggregator.php)

---

## Overview

This plan addresses **5 critical bugs** in the `employee_number` vs `employee_id` identity chain, plus **7 weaknesses** from the earlier ClockInOut component analysis. Fixes are organized into 4 phases by priority.

---

## Phase 1: Critical Identity Bugs (employee_number vs employee_id)

These 5 bugs all stem from the same root cause: the `ClockEvent` table stores both `employee_id` (integer FK) and `employee_number` (string), but downstream code inconsistently uses one where the other is required.

### Fix 1.1 — Dashboard Config: Pass `employee_id` not `employee_number`

**File**: [`app/Modules/Hr/Data/dashboards/dashboard_my_portal.php`](app/Modules/Hr/Data/dashboards/dashboard_my_portal.php:163)

**Current**:
```php
'employee-id' => '{{ employee_number }}',
```

**Fix**:
```php
'employee-id' => '{{ employee_id }}',
```

**Why**: The [`ClockInOut`](src/Http/Livewire/QuickActions/ClockInOut.php:22) component's `$employeeId` property feeds directly into [`ClockEventRecorderService`](app/Modules/Attendance/Services/ClockEventRecorderService.php:26) which queries `ClockEvent::where('employee_id', ...)` — an integer FK column. Passing `employee_number` (string like `"EMP-2025-001"`) causes MySQL type coercion to 0, making the component always show "Not Clocked In" and creating orphaned clock events with `employee_id=0`.

**Risk**: None. The `my-portal.blade.php` view already resolves `'employee_id' => $employee->id` in `$dashboardParams` (line 17).

---

### Fix 1.2 — ClockEventRecorderService: Defensive Identifier Resolution

**File**: [`app/Modules/Attendance/Services/ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:16)

**Current**: The service blindly uses `$employeeId` as-is in `where('employee_id', $employeeId)` and `ClockEvent::create(['employee_id' => $employeeId])`.

**Fix**: Add a private resolver method that handles both integer IDs and string employee_numbers:

```php
use App\Modules\Hr\Models\Employee;

private function resolveEmployeeId(int|string $identifier): int
{
    if (is_numeric($identifier)) {
        return (int) $identifier;
    }
    
    $id = Employee::where('employee_number', $identifier)->value('id');
    
    if (!$id) {
        throw new \InvalidArgumentException("Employee not found: {$identifier}");
    }
    
    return (int) $id;
}
```

Then use `$this->resolveEmployeeId($employeeId)` in both `getLatestToday()` and `record()`.

**Why**: Even after Fix 1.1, this defensive layer protects against future misconfigurations and makes the service self-documenting about what it accepts.

**Risk**: Adds a dependency on `App\Modules\Hr\Models\Employee` — acceptable since this is already a consuming-app service.

---

### Fix 1.3 — AttendanceEventListener: Fix Non-Existent Property Access

**File**: [`app/Modules/Attendance/Listeners/AttendanceEventListener.php`](app/Modules/Attendance/Listeners/AttendanceEventListener.php:115)

**Current**:
```php
$employeeNumber = $attendance->employee_number;
```

**Fix**:
```php
$employeeNumber = $attendance->employee?->employee_number;
```

**Why**: The [`attendances`](app/Modules/Attendance/Database/Migrations/2026_06_12_142526_create_attendances_table.php:16) table has no `employee_number` column — only `employee_id` (integer FK). Accessing `$attendance->employee_number` returns `null`, causing `recalculateForDay(null, ...)` to silently fail. The `employee()` relation already exists on the Attendance model (line 159).

**Risk**: None. The `employee` relation is already defined and eager-loaded in `handleRecalculation()` (line 74: `Attendance::with(['employee'])`).

---

### Fix 1.4 — AttendanceAggregator: Fix Leave Check (recalculateForDay)

**File**: [`app/Modules/Attendance/Services/AttendanceAggregator.php`](app/Modules/Attendance/Services/AttendanceAggregator.php:54)

**Current**:
```php
$hasApprovedLeave = LeaveRequest::where('employee_id', $employeeNumber)
```

**Fix**:
```php
$hasApprovedLeave = LeaveRequest::where('employee_id', $employee->id)
```

**Why**: `$employeeNumber` is a string like `"EMP-2025-001"`, but [`leave_requests.employee_id`](app/Modules/Leave/Database/Migrations/2026_06_12_142522_create_leave_requests_table.php:15) is an integer FK. The string will never match any integer ID, so approved leave is **never detected** — employees on leave get incorrectly marked as "unplanned absence."

Note: Move this check to **after** `$employee` is resolved (currently it's before the employee lookup on line 44, which is also a bug — `$employee` doesn't exist yet at line 54).

**Risk**: Requires reordering — the leave check must move after `$employee = Employee::where(...)` on line 44.

---

### Fix 1.5 — AttendanceAggregator: Fix Leave Check (handleLeaveAttendance)

**File**: [`app/Modules/Attendance/Services/AttendanceAggregator.php`](app/Modules/Attendance/Services/AttendanceAggregator.php:143)

**Current**:
```php
$leaveRequest = LeaveRequest::where('employee_id', $employee->employee_number)
```

**Fix**:
```php
$leaveRequest = LeaveRequest::where('employee_id', $employee->id)
```

**Why**: Same as Fix 1.4 — `employee_number` string vs integer FK mismatch.

**Risk**: None. `$employee->id` is always available.

---

## Phase 2: Missing Functionality

### Fix 2.1 — Dispatch ProcessAttendanceJob After Web Clock

**File**: [`app/Modules/Attendance/Services/ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:46)

**Current**: `record()` creates a `ClockEvent` but never triggers attendance recalculation.

**Fix**: Add after `ClockEvent::create()`:
```php
use App\Modules\Attendance\Jobs\ProcessAttendanceJob;

ProcessAttendanceJob::dispatch(
    $this->resolveEmployeeId($employeeId),
    now()->toDateString()
);
```

**Why**: The API flow ([`ClockEventController::processClockEvent()`](app/Modules/Attendance/Http/Controllers/ClockEventController.php:153)) dispatches this job, but the web flow doesn't. Without it, web clock-ins never update the `attendances` table — hours, status, and sessions remain stale.

**Risk**: Low. The job is idempotent (recalculation overwrites previous results). Queue must be running.

---

### Fix 2.2 — Add Idempotency Check to Web Flow

**File**: [`app/Modules/Attendance/Services/ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:46)

**Current**: No duplicate prevention. Double-clicking creates duplicate clock events.

**Fix**: Add before `ClockEvent::create()`:
```php
$now = Carbon::now();

$existing = ClockEvent::where('employee_id', $this->resolveEmployeeId($employeeId))
    ->where('event_type', $eventType)
    ->where('timestamp', '>=', $now->copy()->subSeconds(10))
    ->where('timestamp', '<=', $now)
    ->exists();

if ($existing) {
    return [
        'event_type' => $eventType,
        'timestamp'  => $now->toIso8601String(),
    ];
}
```

**Why**: Mirrors the idempotency check in [`ClockEventController::processClockEvent()`](app/Modules/Attendance/Http/Controllers/ClockEventController.php:120). Uses a 10-second window instead of exact timestamp match since web clocks use `Carbon::now()` which has microsecond precision.

**Risk**: Low. A 10-second window is reasonable for preventing double-clicks without blocking legitimate rapid clock-in/out (e.g., forgetting something and clocking out 30 seconds after clocking in).

---

### Fix 2.3 — Add Database-Level Unique Constraint (Optional Enhancement)

**File**: New migration in `app/Modules/Attendance/Database/Migrations/`

**Fix**: Add a unique index to prevent duplicate clock events at the database level:
```php
$table->unique(['employee_id', 'event_type', 'timestamp'], 
    'clock_events_employee_event_time_unique');
```

**Why**: Application-level idempotency (Fix 2.2) protects against double-clicks, but a DB constraint is the final safety net against race conditions from multiple browser tabs or concurrent API calls.

**Risk**: Medium. Existing data may have duplicates that need cleanup before adding the constraint. Consider making it a separate migration with a `try/catch` or using `WHERE NOT EXISTS` to skip duplicates.

---

## Phase 3: UI/UX Improvements

### Fix 3.1 — Show Clock-In Timestamp When Clocked In

**File**: [`src/Resources/views/livewire/quick-actions/clock-in-out.blade.php`](src/Resources/views/livewire/quick-actions/clock-in-out.blade.php:27)

**Current**: When clocked in, shows "Since 8:00 AM" but not the full timestamp. When clocked out, shows "Last clock-out: Sep 14, 4:30 PM".

**Fix**: Add `lastEventAt` display when clocked in:
```blade
@if ($status === 'clocked_in' && $clockedInSince)
    <p class="text-sm text-secondary mb-1">
        Clocked in at {{ $clockedInSince }}
    </p>
    @if ($lastEventAt)
        <p class="text-xs text-muted mb-3">
            {{ \Carbon\Carbon::parse($lastEventAt)->format('M j, Y g:i A') }}
        </p>
    @endif
@endif
```

Also update the component to set `$this->lastEventAt` when clocking in (currently only set on refresh, not on toggle).

**Why**: Users should see their exact clock-in timestamp, not just the time portion.

---

### Fix 3.2 — Dispatch Success Event for Toast Notification

**File**: [`src/Http/Livewire/QuickActions/ClockInOut.php`](src/Http/Livewire/QuickActions/ClockInOut.php:82)

**Current**: After toggle, only the button color changes. No confirmation message.

**Fix**: After successful toggle, dispatch a browser event for a toast:
```php
$this->dispatch('notify', [
    'type' => 'success',
    'message' => $this->status === 'clocked_in' 
        ? 'Clocked in successfully!' 
        : 'Clocked out successfully!',
]);
```

**Why**: Provides clear feedback that the action succeeded. The `notify` event is already handled by the library's notification system.

---

### Fix 3.3 — Overnight Shift Handling

**File**: [`app/Modules/Attendance/Services/ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:21)

**Current**: `getLatestToday()` uses `Carbon::today()` — if an employee clocks in at 11 PM and the day rolls over, the component shows "Not Clocked In" at midnight.

**Fix**: Extend the query to also check yesterday's last event if today has no events:
```php
public function getLatestToday(int|string $employeeId): ?array
{
    $today = Carbon::today();
    $employeeId = $this->resolveEmployeeId($employeeId);

    $event = ClockEvent::query()
        ->where('employee_id', $employeeId)
        ->whereDate('timestamp', $today)
        ->orderBy('timestamp', 'desc')
        ->first();

    // If no event today, check if there's an unclosed session from yesterday
    if (!$event) {
        $yesterdayEvent = ClockEvent::query()
            ->where('employee_id', $employeeId)
            ->whereDate('timestamp', $today->copy()->subDay())
            ->orderBy('timestamp', 'desc')
            ->first();

        if ($yesterdayEvent && $yesterdayEvent->event_type === 'clock_in') {
            return [
                'event_type' => 'clock_in',
                'timestamp'  => $yesterdayEvent->timestamp instanceof Carbon
                    ? $yesterdayEvent->timestamp->toIso8601String()
                    : (string) $yesterdayEvent->timestamp,
            ];
        }
        
        return null;
    }

    return [
        'event_type' => $event->event_type,
        'timestamp'  => $event->timestamp instanceof Carbon
            ? $event->timestamp->toIso8601String()
            : (string) $event->timestamp,
    ];
}
```

**Why**: Night-shift workers who clock in at 11 PM should still see "Clocked In" after midnight until they clock out.

**Risk**: Low. Only affects the display status, not the actual clock event recording.

---

## Phase 4: Future Enhancements (Deferred)

These are documented for future sprints but NOT included in the current fix scope:

| # | Enhancement | Rationale |
|---|---|---|
| 4.1 | **Browser geolocation** | Capture `latitude`/`longitude` via `navigator.geolocation` before recording. Requires user permission. |
| 4.2 | **Break/lunch support** | Add `break_start`/`break_end` buttons alongside clock in/out. The `clock_event` data config already defines these event types. |
| 4.3 | **Undo last clock event** | Allow undo within 60 seconds of recording. Requires soft-delete or status flag on ClockEvent. |
| 4.4 | **Offline resilience** | Queue clock events in `localStorage` when offline, sync when back online. Requires JS service worker or Alpine plugin. |
| 4.5 | **Geofencing validation** | Validate clock-in location against office coordinates before accepting. Requires company-level geofence config. |

---

## Implementation Order

```
Phase 1 (Critical — do first):
  ├── 1.1  Dashboard config: employee_number → employee_id
  ├── 1.2  ClockEventRecorderService: defensive resolver
  ├── 1.3  AttendanceEventListener: fix null property
  ├── 1.4  AttendanceAggregator: fix leave check (recalculateForDay)
  └── 1.5  AttendanceAggregator: fix leave check (handleLeaveAttendance)

Phase 2 (Missing functionality):
  ├── 2.1  Dispatch ProcessAttendanceJob after web clock
  ├── 2.2  Add idempotency check
  └── 2.3  DB unique constraint (optional, separate migration)

Phase 3 (UX polish):
  ├── 3.1  Show clock-in timestamp when clocked in
  ├── 3.2  Success toast notification
  └── 3.3  Overnight shift handling
```

---

## Files Modified (Summary)

| # | File | Change |
|---|---|---|
| 1.1 | [`dashboard_my_portal.php`](app/Modules/Hr/Data/dashboards/dashboard_my_portal.php:163) | `employee_number` → `employee_id` |
| 1.2 | [`ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php) | Add `resolveEmployeeId()`, use in both methods |
| 1.3 | [`AttendanceEventListener.php`](app/Modules/Attendance/Listeners/AttendanceEventListener.php:115) | `$attendance->employee_number` → `$attendance->employee?->employee_number` |
| 1.4 | [`AttendanceAggregator.php`](app/Modules/Attendance/Services/AttendanceAggregator.php:54) | `$employeeNumber` → `$employee->id`, reorder after employee lookup |
| 1.5 | [`AttendanceAggregator.php`](app/Modules/Attendance/Services/AttendanceAggregator.php:143) | `$employee->employee_number` → `$employee->id` |
| 2.1 | [`ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:46) | Add `ProcessAttendanceJob::dispatch()` |
| 2.2 | [`ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:46) | Add 10-second duplicate window check |
| 2.3 | New migration | Add unique constraint on `clock_events` |
| 3.1 | [`clock-in-out.blade.php`](src/Resources/views/livewire/quick-actions/clock-in-out.blade.php:27) | Show full timestamp when clocked in |
| 3.2 | [`ClockInOut.php`](src/Http/Livewire/QuickActions/ClockInOut.php:107) | Dispatch `notify` event after toggle |
| 3.3 | [`ClockEventRecorderService.php`](app/Modules/Attendance/Services/ClockEventRecorderService.php:21) | Check yesterday's last event for overnight shifts |

---

## Library Philosophy Compliance

All fixes respect the library/consuming-app boundary:

- **Phase 1 fixes** are all in the consuming app (`app/Modules/`) — no library changes needed
- **Phase 3.1 and 3.2** touch library files (`src/`) but only the Blade view and component — both are domain-agnostic (no business nouns)
- **Phase 3.3** is in the consuming app service
- No new `use App\Modules\...` imports in library code
- The [`ClockEventRecorder`](src/Contracts/Attendance/ClockEventRecorder.php) contract remains unchanged