# Onboarding Gap Tracking — Implementation Plan

> **Date**: 2026-09-19
> **Status**: Plan — Ready for Implementation
> **Related**: [CHANGELOG.md](../CHANGELOG.md), [onboarding_overview.php](../../app/Modules/Hr/Data/dashboards/onboarding_overview.php)

## Problem

After an employee accepts an invitation and creates their profile, two critical gaps must be filled by HR before the employee is fully operational:

1. **Company Assignment** (`employees.company_id`) — without it, company-scoped queries, payroll, and attendance policies fail
2. **Job/Position Info** (`employee_position` record) — without it, no department, manager, employment status, or org hierarchy

Currently, there is no visibility into these gaps. HR managers cannot easily identify which employees need attention.

## Solution Overview

Add onboarding completion tracking integrated into the existing onboarding infrastructure:

```
┌─────────────────────────────────────────────────────────────┐
│ Onboarding Overview Dashboard (/hr/onboarding-overview)      │
├────────────┬────────────┬────────────┬──────────────────────┤
│ Pending    │ Recent     │ Missing    │ Missing    │ Incomplete│
│ Invitations│ Hires      │ Position   │ Company    │ Onboarding│
│    12      │    5       │    8 🟠    │    3 🔴    │   11 ⚠️   │
├────────────┴────────────┴────────────┴────────────┴─────────┤
│ [Recent Invitations]                        [View All →]    │
│ [Incomplete Onboarding]                     [Fix All →]     │
│ [Send Invitation]  [Onboard New Hire]                       │
└─────────────────────────────────────────────────────────────┘
```

---

## Step 1: Add `onboarding_status` to Employees Table

### 1.1 Create Migration

**File**: `app/Modules/Hr/Database/Migrations/2026_09_19_000000_add_onboarding_status_to_employees.php`

```php
Schema::table('employees', function (Blueprint $table) {
    $table->string('onboarding_status')->nullable()->after('company_id')
        ->comment('complete, position_pending, company_pending');
});
```

### 1.2 Create Model Observer

**File**: `app/Modules/Hr/Observers/EmployeeOnboardingObserver.php`

Auto-compute `onboarding_status` on `saving`:

```php
class EmployeeOnboardingObserver
{
    public function saving(Employee $employee): void
    {
        if (! $employee->user_id) {
            $employee->onboarding_status = null; // not yet accepted
            return;
        }
        if (! $employee->company_id) {
            $employee->onboarding_status = 'company_pending';
            return;
        }
        // Check after save since position may not be loaded yet
    }

    public function saved(Employee $employee): void
    {
        if (! $employee->user_id || ! $employee->company_id) {
            return;
        }
        
        $hasPosition = $employee->employeePosition()->exists();
        
        $status = $hasPosition ? 'complete' : 'position_pending';
        
        if ($employee->onboarding_status !== $status) {
            $employee->quietly()->update(['onboarding_status' => $status]);
        }
    }
}
```

**Register in** `HrsServiceProvider::boot()`:
```php
Employee::observe(EmployeeOnboardingObserver::class);
```

### 1.3 Add Accessor to Employee Model

**File**: `app/Modules/Hr/Models/Employee.php`

```php
public function getOnboardingStatusLabelAttribute(): string
{
    return match($this->onboarding_status) {
        'complete' => 'Complete',
        'position_pending' => 'Needs Position',
        'company_pending' => 'Needs Company',
        default => 'Not Started',
    };
}

public function getOnboardingStatusColorAttribute(): string
{
    return match($this->onboarding_status) {
        'complete' => 'success',
        'position_pending' => 'warning',
        'company_pending' => 'danger',
        default => 'secondary',
    };
}
```

---

## Step 2: Add Stat Widgets to Onboarding Overview Dashboard

**File**: `app/Modules/Hr/Data/dashboards/onboarding_overview.php`

Add three new stat widgets after the existing "Recent Hires This Month" widget:

```php
// Missing Position — employees with user_id but no EmployeePosition
[
    'type'       => 'stat',
    'title'      => 'Missing Position',
    'size'       => 'col-12',
    'model'      => 'App\\Modules\\Hr\\Models\\Employee',
    'icon'       => 'fas fa-user-tag',
    'aggregate'  => 'count',
    'conditions' => [
        ['onboarding_status', '=', 'position_pending'],
    ],
    'width'      => 3,
    'color'      => 'warning',
],

// Missing Company — employees with user_id but no company_id
[
    'type'       => 'stat',
    'title'      => 'Missing Company',
    'size'       => 'col-12',
    'model'      => 'App\\Modules\\Hr\\Models\\Employee',
    'icon'       => 'fas fa-building',
    'aggregate'  => 'count',
    'conditions' => [
        ['onboarding_status', '=', 'company_pending'],
    ],
    'width'      => 3,
    'color'      => 'danger',
],

// Incomplete Onboarding — sum of both
[
    'type'       => 'stat',
    'title'      => 'Incomplete Onboarding',
    'size'       => 'col-12',
    'model'      => 'App\\Modules\\Hr\\Models\\Employee',
    'icon'       => 'fas fa-exclamation-triangle',
    'aggregate'  => 'count',
    'conditions' => [
        ['onboarding_status', '!=', 'complete'],
        ['onboarding_status', '!=', null],
    ],
    'width'      => 3,
    'color'      => 'warning',
],
```

---

## Step 3: Add "Incomplete Onboarding" List Widget

**File**: `app/Modules/Hr/Data/dashboards/onboarding_overview.php`

Add after the "Recent Invitations" list:

```php
[
    'type'        => 'list',
    'title'       => 'Incomplete Onboarding',
    'size'        => 'col-12',
    'model'       => 'App\\Modules\\Hr\\Models\\Employee',
    'icon'        => 'fas fa-clipboard-list',
    'description' => 'Employees needing position or company assignment',
    'limit'       => 10,
    'sort'        => ['created_at', 'asc'],
    'conditions'  => [
        ['onboarding_status', '!=', 'complete'],
        ['onboarding_status', '!=', null],
    ],
    'columns'     => [
        ['label' => 'Name',   'field' => 'full_name'],
        ['label' => 'Email',  'field' => 'email'],
        ['label' => 'Status', 'field' => 'onboarding_status', 'format' => 'badge'],
    ],
    'width'            => 6,
    'show_view_all'    => true,
    'view_all_link'    => '/hr/employees',
],
```

---

## Step 4: Add Onboarding Status to Employee Data Table

**File**: `app/Modules/Hr/Data/employee.php`

Add a computed/virtual column for the data table:

```php
'columns' => [
    // ... existing columns ...
    'onboarding_status' => [
        'label'      => 'Onboarding',
        'field_type' => 'badge',
        'virtual'    => true,  // computed by accessor
        'sortable'   => true,
        'filterable' => true,
        'colors'     => [
            'complete'         => 'success',
            'position_pending' => 'warning', 
            'company_pending'  => 'danger',
        ],
    ],
],
```

---

## Step 5: Enhance Employee Invitation Tab

**File**: The `qf.employee-invitation-status` Livewire component (library or consuming app override)

Add onboarding completion checklist below the invitation history:

```blade
{{-- Onboarding Completion Status --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="fw-bold mb-0">Onboarding Progress</h6>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">
                <span>Company Assignment</span>
                @if ($employee->company_id)
                    <span class="badge bg-success">✅ {{ $employee->company->name }}</span>
                @else
                    <span class="badge bg-danger">Missing</span>
                @endif
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>Job / Position</span>
                @if ($employee->employeePosition)
                    <span class="badge bg-success">✅ {{ $employee->employeePosition->position->title ?? 'Assigned' }}</span>
                @else
                    <span class="badge bg-warning">Missing</span>
                @endif
            </li>
        </ul>
    </div>
</div>
```

---

## Step 6: Add Onboarding Status to Invitations Data Table

**File**: Library's `src/Core/Admin/Data/invitation.php` or HR override

Add a column showing the linked employee's onboarding status:

```php
'employee_status' => [
    'label'      => 'Employee Status',
    'field_type' => 'badge',
    'virtual'    => true,
    'relation'   => 'employee',  // invitation → employee
    'colors'     => [
        'complete'         => 'success',
        'position_pending' => 'warning',
        'company_pending'  => 'danger',
        null               => 'secondary',
    ],
],
```

---

## Implementation Order

| Step | File | Type | Depends On |
|:---:|------|------|:---:|
| 1 | Migration + Observer + Model | Code | — |
| 2 | `onboarding_overview.php` | Config | Step 1 |
| 3 | `onboarding_overview.php` | Config | Step 1 |
| 4 | `employee.php` | Config | Step 1 |
| 5 | `employee-invitation-status` | Blade/PHP | Step 1 |
| 6 | `invitation.php` | Config | Step 1 |

Steps 2-6 can be done in parallel after Step 1.

---

## Verification Checklist

- [ ] Migration runs without errors
- [ ] Existing employees get correct `onboarding_status` computed
- [ ] Creating a new employee via invitation → status starts as `company_pending`
- [ ] Assigning company → status changes to `position_pending`
- [ ] Assigning position → status changes to `complete`
- [ ] Onboarding overview dashboard shows correct counts
- [ ] "Incomplete Onboarding" list shows correct employees
- [ ] Employee data table filterable by onboarding status
- [ ] Invitation tab shows position + company status
- [ ] Invitations data table shows employee onboarding status