# Plan: Remove Legacy LeaveApprover + Move Wizard to Leave Module

## Overview

Three coordinated changes:
1. **Remove** the legacy `LeaveApprover` system (model, config, views, routes, nav, quick-actions, dashboard widgets, migrations)
2. **Move** the `employee_self_service` wizard config from `app/Modules/Hr/Data/wizards/` to `app/Modules/Leave/Data/wizards/`
3. **Update** all references to point to the new location

---

## Phase 1: Remove Legacy LeaveApprover

### 1.1 Delete Files (10 files)

| # | File | Reason |
|---|------|--------|
| 1 | `app/Modules/Leave/Models/LeaveApprover.php` | Legacy model |
| 2 | `app/Modules/Leave/Data/leave_approver.php` | Legacy DataTable config |
| 3 | `app/Modules/Leave/Resources/views/leave-approvers.blade.php` | List view |
| 4 | `app/Modules/Leave/Resources/views/leave-approvers/create.blade.php` | Create view |
| 5 | `app/Modules/Leave/Resources/views/leave-approvers/edit.blade.php` | Edit view |
| 6 | `app/Modules/Leave/Resources/views/leave-approvers/show.blade.php` | Show view |
| 7 | `app/Modules/Hr/Resources/views/components/layouts/navbars/auth/leave-approvers-tab-bar-links.blade.php` | Tab bar link |
| 8 | `app/Modules/Leave/Database/Migrations/2026_06_12_142524_create_leave_approvers_table.php` | Migration |
| 9 | `app/Modules/Leave/Database/Migrations/2026_06_12_142525_create_leave_approver_leave_type_table.php` | Pivot migration |
| 10 | `app/Modules/Leave/Database/Factories/LeaveApproverFactory.php` | Factory (if exists) |

### 1.2 Edit Files — Remove LeaveApprover References (6 files)

| # | File | Change |
|---|------|--------|
| 1 | `app/Modules/Leave/Routes/web.php` | Remove lines 105-130 (all LeaveApprover routes) |
| 2 | `app/Modules/Leave/Config/navigation.php` | Remove the `leave_approver` navigation item (lines 79-85 under `configuration`) |
| 3 | `app/Modules/Leave/Config/quick-actions.php` | Remove the `leave.manage_approvers` quick action entry (lines 49-60) |
| 4 | `app/Modules/Leave/Data/dashboards/dashboard.php` | Remove the LeaveApprover widget at line 75-77 |
| 5 | `app/Modules/Leave/Data/dashboards/dashboard_configuration_overview.php` | Remove 3 LeaveApprover references (lines 22-24, 31-33, 74-76) |
| 6 | `app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php` | Remove the `leave.leave_approver` configKey reference (line 331) |

---

## Phase 2: Move Wizard Config to Leave Module

### 2.1 Create and Move

| # | Action |
|---|--------|
| 1 | Create directory `app/Modules/Leave/Data/wizards/` |
| 2 | Copy `app/Modules/Hr/Data/wizards/employee_self_service.php` → `app/Modules/Leave/Data/wizards/employee_self_service.php` |
| 3 | Fix `models.primary` on line 84: `App\Modules\Hr\Models\LeaveRequest` → `App\Modules\Leave\Models\LeaveRequest` |
| 4 | Delete `app/Modules/Hr/Data/wizards/employee_self_service.php` |

### 2.2 The wizard `id` stays as `'employee_self_service'`

The wizard is resolved by its `id` field, not by its file path. The `leave_request.php` config references `'wizard' => 'employee_self_service'` (line 281) — this is the wizard ID, not a config key, so it does NOT need to change.

---

## Phase 3: Update Config Key References

### 3.1 Files that reference `hr.wizards.employee_self_service` (config key)

| # | File | Line | Change |
|---|------|------|--------|
| 1 | `app/Modules/Hr/Data/dashboards/dashboard_my_portal.php` | 135 | `hr.wizards.employee_self_service` → `leave.wizards.employee_self_service` |
| 2 | `app/Modules/Hr/Resources/views/employee-self-service.blade.php` | 14 | `hr.wizards.employee_self_service` → `leave.wizards.employee_self_service` |

### 3.2 Files that reference `employee_self_service` as a wizard type (NOT a config key)

These use `event: 'openLeaveWizard'` with `params: {type: 'employee_self_service'}` — the `type` is the wizard ID, not a file path. **No change needed.**

| # | File | Line |
|---|------|------|
| 1 | `app/Modules/Hr/Data/dashboards/dashboard.php` | 370-373 |
| 2 | `app/Modules/Hr/Data/dashboards/default.php` | 370-373 |

### 3.3 Files that reference `employee_self_service` as wizard ID in DataTable config

The `leave_request.php` line 281 uses `'wizard' => 'employee_self_service'` — this is the wizard ID. **No change needed.**

---

## Phase 4: Verification

| # | Check |
|---|-------|
| 1 | `php artisan route:list` — no leave-approvers routes |
| 2 | `php artisan tinker` — `App\Modules\Leave\Models\LeaveApprover` class not found (expected) |
| 3 | Navigate to `/leave/leave-approvers` — should 404 |
| 4 | Navigate to `/hr/my-portal` — "Request Leave" action card opens wizard from `leave.wizards.employee_self_service` |
| 5 | Navigate to `/hr/employee-self-service` — wizard loads from `leave.wizards.employee_self_service` |
| 6 | Navigate to `/leave/leave-requests` — "Request Leave" button opens wizard by ID `employee_self_service` |

---

## Post-Implementation Notes

### Architecture Violations Discovered and Fixed

During the UX implementation, a library independence audit revealed **14 architecture violations** where the library (`src/`) contained references to consuming-app domain classes (`App\Modules\Leave\Models\*`, `App\Modules\Holiday\Models\*`). All 14 have been fixed:

| # | Violation | Library File | Fix Applied |
|---|-----------|-------------|-------------|
| 1 | `LeaveType` model reference | [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Extracted to [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) subclass |
| 2 | `LeaveBalance` model reference | [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Extracted to [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) subclass |
| 3 | `LeaveRequest` model reference (conflicts) | [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Extracted to [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) subclass |
| 4 | `LeaveRequest` model reference (real-time) | [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Extracted to [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) subclass |
| 5 | `LeaveType` model reference (info) | [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Extracted to [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) subclass |
| 6 | `LeaveRequest` model reference (working days) | [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Extracted to [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) subclass |
| 7 | `Holiday` model reference | [`DatepickerField.php`](src/Components/FieldTypes/DatepickerField.php) | Replaced with [`CalendarEnhancementProvider`](src/Contracts/FieldTypes/CalendarEnhancementProvider.php) contract |
| 8 | `LeaveRequest` model reference (absences) | [`DatepickerField.php`](src/Components/FieldTypes/DatepickerField.php) | Replaced with [`CalendarEnhancementProvider`](src/Contracts/FieldTypes/CalendarEnhancementProvider.php) contract |
| 9 | Hardcoded review step | [`wizard-form.blade.php`](src/Resources/views/livewire/wizards/wizard-form.blade.php) | Made configurable via `showReviewStep` |
| 10 | Hardcoded draft message | [`wizard-form.blade.php`](src/Resources/views/livewire/wizards/wizard-form.blade.php) | Made configurable via `draftMessage` |
| 11 | Hardcoded field hints | [`wizard-form.blade.php`](src/Resources/views/livewire/wizards/wizard-form.blade.php) | Made configurable via `hint` key |
| 12 | Hardcoded completion links (`/hr/`) | [`wizard.blade.php`](src/Resources/views/livewire/wizards/wizard.blade.php) | Made configurable via `completion` config |
| 13 | Module URLs used `/hr/` prefix | Navigation and route configs | Updated to `/leave/` prefix |
| 14 | Wizard config in wrong module | `app/Modules/Hr/Data/wizards/` | Moved to `app/Modules/Leave/Data/wizards/` |

### New Architectural Patterns

**Contract/Provider Pattern for Calendar Data** — The [`CalendarEnhancementProvider`](src/Contracts/FieldTypes/CalendarEnhancementProvider.php) contract defines `getHolidays()` and `getTeamAbsences()` methods. The library's [`DatepickerField`](src/Components/FieldTypes/DatepickerField.php) consumes this contract via the service container, never referencing domain models directly. The consuming app binds [`LeaveCalendarEnhancementProvider`](hr-consuming-app:app/Modules/Leave/Services/LeaveCalendarEnhancementProvider.php) as the implementation.

**Subclass Pattern for Domain-Specific Wizard Logic** — The [`LeaveWizardForm`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) extends [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) and overrides six domain-specific methods. The library base class provides generic wizard infrastructure (step navigation, field rendering, save/submit); the consuming-app subclass supplies leave-specific logic (balance checking, conflict detection, leave type resolution).

**Configurable UI Pattern** — Review step visibility, draft messages, field hints, and completion links are now driven by wizard config keys rather than hardcoded in Blade templates. This allows any consuming-app module to customize the wizard UX without modifying library code.

### New Files Created

| File | Location | Purpose |
|------|----------|---------|
| [`LeaveWizardForm.php`](hr-consuming-app:app/Modules/Leave/Http/Livewire/LeaveWizardForm.php) | Consuming app | Domain-specific wizard subclass |
| [`CalendarEnhancementProvider.php`](src/Contracts/FieldTypes/CalendarEnhancementProvider.php) | Library | Contract for domain-specific calendar data |
| [`LeaveCalendarEnhancementProvider.php`](hr-consuming-app:app/Modules/Leave/Services/LeaveCalendarEnhancementProvider.php) | Consuming app | Implements calendar contract for Leave module |
| [`LeaveBalanceResolver.php`](hr-consuming-app:app/Modules/Leave/Services/LeaveBalanceResolver.php) | Consuming app | Balance calculation service |

---

## Phase 5: Migrate Attachments to Polymorphic Documents ✅ (2026-09-04)

### 5.1 Background

`LeaveRequest` had a JSON `attachments` column that bypassed the UI library's polymorphic document system. The library provides [`DocumentEngine`](src/Services/Documents/DocumentEngine.php:1) + [`documents`](Database/migrations/2026_06_12_142526_create_documents_table.php:1) table + [`Documentable`](src/Contracts/Documents/Documentable.php:1) contract for a dedicated document upload system. `LeaveRequest` already implemented `Documentable` with all 4 methods + `documents()` morphMany relationship.

### 5.2 Changes Made

# | File | Change |
|---|------|--------|
1 | `app/Modules/Leave/Data/leave_request.php` | Removed `attachments` field definition (lines 147-157), removed from `request_details` field group |
2 | `app/Modules/Leave/Models/LeaveRequest.php` | Removed `'attachments'` from `$fillable` and `'attachments' => 'array'` from `$casts` |
3 | `app/Modules/Leave/Database/Migrations/2026_09_04_000000_drop_attachments_from_leave_requests.php` | Migration to drop `attachments` JSON column from `leave_requests` table |

### 5.3 New Files Created

| File | Location | Purpose |
|------|----------|---------|
| [`LeaveDocumentUpload.php`](hr-consuming-app/app/Modules/Leave/Http/Livewire/LeaveDocumentUpload.php:1) | Consuming app | Livewire component providing upload, preview, download, and delete UI for leave request documents. Uses [`DocumentEngine`](src/Services/Documents/DocumentEngine.php:1) for all operations. |
| `2026_09_04_000000_drop_attachments_from_leave_requests.php` | Consuming app | Migration to drop `attachments` JSON column from `leave_requests` table |

### 5.4 HasDocuments Trait

The library provides a [`HasDocuments`](src/Traits/Documents/HasDocuments.php:1) trait that models can use to implement the [`Documentable`](src/Contracts/Documents/Documentable.php:1) contract with sensible defaults. `LeaveRequest` uses this trait (or implements `Documentable` directly) to provide:

- `documents()` — morphMany relationship to [`Document`](src/Models/Document.php:1)
- `getDocumentableId()` — returns the model's primary key
- `getDocumentType()` — returns the model's class basename
- `getDocumentStoragePath()` — returns a storage path based on the model type and ID
- `getDocumentTemplateData()` — returns template variables for document generation

### 5.5 Verification

- `LeaveRequest` implements [`Documentable`](src/Contracts/Documents/Documentable.php:1) with `getDocumentableId()`, `getDocumentType()`, `getDocumentStoragePath()`, `getDocumentTemplateData()`
- `documents()` morphMany relationship targets [`Document`](src/Models/Document.php:1) model
- Migration ran successfully: `2026_09_04_000000_drop_attachments_from_leave_requests ........ 71.26ms DONE`
- [`LeaveDocumentUpload`](hr-consuming-app/app/Modules/Leave/Http/Livewire/LeaveDocumentUpload.php:1) component renders on the leave request detail page, providing full CRUD for documents via the polymorphic document system