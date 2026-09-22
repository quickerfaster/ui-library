AI Prompt — Restrict ESS Attendance Tab Actions to Employee Role

> **Status**: ✅ COMPLETE — All fixes implemented and verified (2026-09-22)

## Context

You are working on the QuickerFaster UI Library (quicker-faster/ui-library), a Laravel + Livewire 3 + Bootstrap 5 package at `/Users/mac/Projects/Libraries/ui-library`. The consuming app is at `/Users/mac/Projects/LaravelProjects/hr-consuming-app`.

## Library Philosophy (Critical — Do Not Violate)

From `docs/library/pilosophy.txt`:
- The library must be completely decoupled from the consuming app. The consuming app can depend on the library, but never the reverse.
- Convention over configuration — modules follow predictable folder conventions.
- qf namespace convention — all library assets use the qf prefix (views: `qf::`, Blade: `<x-qf::>`, Livewire: `qf.`).

## What's Been Done (Key Recent Fixes)

### 1. Bulk & Row Action Permission Filtering
- [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) now has `filterBulkActionsByPermission()` (line ~1393) and `filterSimpleActionsByPermission()` (line ~1428)
- Bulk actions (delete, restore, forceDelete, export, updateField) are filtered by `canBulk*()` before reaching the Blade view
- Row actions (show, edit, delete, restore, forceDelete) are filtered by `can*()` with `isBypassAllowed()` check for admin roles
- `moreActions` already had per-record filtering in [`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php:10-23)

### 2. Column Visibility Persistence
- [`HasColumnPreferences`](src/Traits/DataTables/HasColumnPreferences.php) now accepts optional `$fallback` parameter
- Column visibility survives page refresh (was intersecting with defaults, stripping user-added columns)

### 3. Organization Module Config Modernization
- All 7 configs in `app/Modules/Organization/Data/` modernized to match HR `employee.php` format
- Added `fieldGroups`, `relationship` definitions, `searchable_fields`, `hiddenFields`, `simpleActions`, `tableDefaultFields`, modern `controls`

### 4. Clock-In/Out Fixes
- `withoutCompanyScope()` added to Employee lookups in `ClockEventRecorderService`, `ProcessAttendanceJob`, `AttendanceAggregator`, `AttendanceCalculator`, `GeofenceValidator`
- `withoutGlobalScope(CompanyScope::class)` added to EmployeePosition eager loads
- Geofence now blocks clock-in when no position assigned (was silently skipping)
- Errors now propagate to user instead of silent logging

### 5. Cross-Company Record Mismatch Prevention
- [`EmployeePosition::booted()`](app/Modules/Hr/Models/EmployeePosition.php:242) auto-sets `company_id` from selected employee

## The Problem

The ESS attendance tab at `https://hr-consuming-app.test/hr/my-profile?tab=attendance` renders a DataTable that exposes actions inappropriate for the employee role:

### Row Actions (simpleActions / moreActions) That Should Be Hidden:
- "Approve for Payroll"
- "Mark as Resolved"
- "Adjust Attendance"
- Any admin/HR-only actions

### Bulk Selection Actions That Should Be Hidden:
- "Delete"
- "Permanently Delete" (forceDelete)
- "Restore Selected"

### Root Cause Analysis

The `filterBulkActionsByPermission()` and `filterSimpleActionsByPermission()` methods were added to `DataTable.php` but they filter based on the **current user's Spatie permissions** (e.g., `delete_attendance`, `restore_attendance`). If the employee role has been granted these permissions (even inadvertently), the actions will appear.

Additionally, `moreActions` are filtered per-record in `row-actions.blade.php` using `canPerformAction()`, which also checks Spatie permissions.

The issue may be:
1. The employee role has been granted `delete_attendance`, `restore_attendance`, etc. permissions
2. The attendance data config includes `simpleActions` and `bulkActions` that shouldn't apply to ESS views
3. The ESS view doesn't override the DataTable's `simpleActions` or `controls` to restrict actions

## The Task

Investigate and fix the ESS attendance tab so that employee-role users only see actions appropriate for self-service viewing.

### Step 1: Investigate the ESS Attendance View

File: `app/Modules/Attendance/Resources/views/attendance/my-attendance.blade.php` (or similar)

Check how the DataTable is embedded — does it pass `:simple-actions` or `:controls` overrides?

### Step 2: Check the Attendance Data Config

File: `app/Modules/Attendance/Data/attendance.php`

Check:
- `simpleActions` — does it include 'edit', 'delete', 'restore', 'forceDelete'?
- `controls.bulkActions` — does it include delete, restore, forceDelete?
- `moreActions` — does it include admin-only actions?

### Step 3: Check Employee Role Permissions

The employee role may have been granted permissions like `delete_attendance`, `restore_attendance`, `edit_attendance`. Check the Spatie roles/permissions configuration.

### Step 4: Implement the Fix

**Option A (Consuming App — Recommended):** Override the DataTable props in the ESS view to restrict actions:

```blade
<livewire:qf.data-table 
    configKey="attendance.attendance" 
    :simpleActions="['show']"
    :controls="['bulkActions' => []]"
    :filters="[['employee_id', '=', $employee->id]]" 
/>
```

**Option B (Library):** Add role-based filtering to `filterSimpleActionsByPermission()` and `filterBulkActionsByPermission()` — but this couples the library to role concepts.

**Option C (Consuming App):** Create a separate ESS-specific data config that inherits from the main attendance config but overrides actions.

### Step 5: Verify

- Log in as an employee
- Visit `/hr/my-profile?tab=attendance`
- Verify only "Show" (view) action appears on rows
- Verify no bulk selection checkboxes or bulk action toolbar appears
- Verify no "Approve for Payroll", "Mark as Resolved", "Adjust Attendance" actions appear

## Key Files Reference

| File | Purpose |
|------|---------|
| [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) | Main data table — `filterBulkActionsByPermission()`, `filterSimpleActionsByPermission()` |
| [`src/Resources/views/livewire/data-tables/partials/row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php) | Row action buttons with per-record permission checks |
| [`src/Resources/views/livewire/data-tables/data-table.blade.php`](src/Resources/views/livewire/data-tables/data-table.blade.php) | Main data table Blade view with bulk action toolbar |
| `app/Modules/Attendance/Data/attendance.php` | Attendance data config |
| `app/Modules/Attendance/Resources/views/attendance/my-attendance.blade.php` | ESS attendance view |
| [`src/Services/DataTables/DefaultAuthorizationProvider.php`](src/Services/DataTables/DefaultAuthorizationProvider.php) | Permission checks for DataTable actions |
| [`src/Services/AccessControl/AuthorizationService.php`](src/Services/AccessControl/AuthorizationService.php) | `isBypassAllowed()` — admin role bypass |

## Pre-Implementation Checklist

- [ ] Read the ESS attendance view to see how the DataTable is embedded
- [ ] Read the attendance data config for `simpleActions`, `bulkActions`, `moreActions`
- [ ] Check if the employee Spatie role has `delete_attendance`, `restore_attendance`, `edit_attendance` permissions
- [ ] Verify the `filterBulkActionsByPermission()` and `filterSimpleActionsByPermission()` methods are working correctly

## Post-Implementation Checklist

- [ ] Run `php -l` on all modified PHP files
- [ ] Run `php artisan optimize:clear` in the consuming app
- [ ] Test as employee: visit `/hr/my-profile?tab=attendance`
- [ ] Verify no delete/restore/forceDelete bulk actions
- [ ] Verify no admin-only row actions
- [ ] Test as admin: verify admin actions still appear on the main attendance page
- [ ] Update [`docs/debug-checklist.md`](docs/debug-checklist.md) with any new lessons learned