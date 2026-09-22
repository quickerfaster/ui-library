# AI Prompt — Fix Payroll Run Wizard Employee Name Error

## Context

You are working on the QuickerFaster UI Library (quicker-faster/ui-library), a Laravel + Livewire 3 + Bootstrap 5 package at `/Users/mac/Projects/Libraries/ui-library`. The consuming app is at `/Users/mac/Projects/LaravelProjects/hr-consuming-app`.

## Library Philosophy (Critical — Do Not Violate)

From `docs/library/pilosophy.txt`:
- The library must be completely decoupled from the consuming app. The consuming app can depend on the library, but never the reverse.
- Convention over configuration — modules follow predictable folder conventions.
- qf namespace convention — all library assets use the qf prefix (views: `qf::`, Blade: `<x-qf::>`, Livewire: `qf.`).

## The Problem

When a specific company is selected via the company switcher and the payroll run wizard's first step form is filled and submitted, the adjustments step (step 2) crashes with:

```
ErrorException: Attempt to read property "first_name" on null
at app/Modules/Payroll/Resources/views/livewire/payroll/wizard-adjustments.blade.php:131
```

Line 131:
```blade
{{ $emp->employee->first_name }} {{ $emp->employee->last_name }}
```

`$emp->employee` is `null` — the `employee` relationship on the `EmployeePosition` model returns null because of `CompanyScope`.

## Root Cause Analysis

The `render()` method in [`PayrollWizardAdjustments.php:386-396`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardAdjustments.php:386) loads positions with `withoutCompanyScope()`:

```php
$baseQuery = EmployeePosition::withoutCompanyScope()
    ->where('employment_status', 'Active')
    ->whereNull('deleted_at');
```

But the `employee` relationship on `EmployeePosition` is a standard `belongsTo` that applies `CompanyScope` on the `Employee` model. When the session company doesn't match the employee's company, `$emp->employee` returns `null`.

This is the **same CompanyScope pattern** we've been fixing across the entire codebase:
- [`EmployeeDetail.php:205`](app/Modules/Hr/Http/Livewire/EmployeeDetail.php:205) — loads `employeeProfile` with `withoutGlobalScopes()`
- [`EmployeeDetail.php:272-278`](app/Modules/Hr/Http/Livewire/EmployeeDetail.php:272) — resolves FK display names with `withoutGlobalScopes()->withTrashed()`
- [`my-portal.blade.php:17-23`](app/Modules/Hr/Resources/views/hr/my-portal.blade.php:17) — same pattern for dashboard card
- [`Step1EmployeeRecord.php`](app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step1EmployeeRecord.php) — uses `withoutCompanyScope()` for employee lookup

## The Fix

The `$employees` collection passed to the Blade view needs the `employee` relationship loaded without CompanyScope. There are two approaches:

**Option A (Recommended)**: Eager-load the `employee` relationship without global scopes in the query:

```php
$baseQuery = EmployeePosition::withoutCompanyScope()
    ->with(['employee' => fn ($q) => $q->withoutGlobalScopes()])
    ->where('employment_status', 'Active')
    ->whereNull('deleted_at');
```

**Option B**: Add a null-safe fallback in the Blade:

```blade
{{ $emp->employee?->first_name ?? 'Unknown' }}
```

Option A is preferred because it fixes the root cause and ensures all employee data (name, number, etc.) is available throughout the wizard.

## Key Files

| File | Purpose |
|------|---------|
| [`PayrollWizardAdjustments.php:386-396`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardAdjustments.php:386) | Loads employees for the adjustments step |
| [`wizard-adjustments.blade.php:131`](app/Modules/Payroll/Resources/views/livewire/payroll/wizard-adjustments.blade.php:131) | Renders employee name |
| [`EmployeePosition.php:129-132`](app/Modules/Hr/Models/EmployeePosition.php:129) | `employee()` relationship (standard belongsTo) |
| [`Employee.php`](app/Modules/Hr/Models/Employee.php) | Uses `HasCompanyScope` trait |
| [`CompanyScope.php`](src/Scopes/CompanyScope.php) | Filters by `company_id` matching session |

## Also Check

The same issue may exist in:
- [`PayrollWizardPreview.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardPreview.php) — the preview step also accesses employee data
- Any other Blade view that accesses `$emp->employee` or `$position->employee`

## Pre-Implementation Checklist

- [ ] Read [`PayrollWizardAdjustments.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardAdjustments.php) full `render()` method
- [ ] Check [`PayrollWizardPreview.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardPreview.php) for the same pattern
- [ ] Check the Blade for all `$emp->employee` accesses
- [ ] Apply `withoutGlobalScopes()` to the `employee` eager-load

## Post-Implementation Checklist

- [ ] Run `php -l` on all modified PHP files
- [ ] Run `php artisan optimize:clear` in the consuming app
- [ ] Test: switch to a specific company, create a payroll run, verify step 2 shows employee names
- [ ] Test: switch to "All Companies", verify multi-company payroll runs still work
- [ ] Update [`docs/debug-checklist.md`](docs/debug-checklist.md) with any new lessons learned