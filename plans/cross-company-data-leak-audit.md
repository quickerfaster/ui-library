# Cross-Company Data Leak Audit

> **Status**: ✅ COMPLETE — Audit done, 1 medium risk found and fixed (2026-09-22)

## Scope
All `withoutGlobalScopes()`, `withoutCompanyScope()`, and `withTrashed()` calls introduced or modified by our changes across 14 files.

## Risk Levels
- 🔴 **HIGH**: Exposes sensitive data from other companies to unauthorized users
- 🟡 **MEDIUM**: Could expose non-sensitive metadata or cause data integrity issues
- 🟢 **LOW**: Scoped to user's own records, no cross-company exposure

---

## Our Changes — Risk Assessment

### 1. EmployeeDetail.php — `resolvePositionDisplayNames()` (lines 272-278)
```php
JobTitle::withoutGlobalScopes()->withTrashed()->find($jobTitleId)
Department::withoutGlobalScopes()->withTrashed()->find($departmentId)
// ... etc.
```
- **What**: Resolves FK values to human-readable names for DISPLAY
- **Who sees it**: ESS user viewing their own profile, or admin viewing any employee
- **Risk**: 🟢 LOW — Only exposes display names (labels), not sensitive data. The FK values are already visible to the user. This is a convenience, not a leak.
- **Mitigation**: The user already has the FK value; the name is just a label.

### 2. EmployeeDetail.php — Profile company_id sync (lines 222-227)
```php
if ($this->profile && $this->employee->company_id
    && $this->profile->company_id !== $this->employee->company_id) {
    $this->profile->updateQuietly(['company_id' => $this->employee->company_id]);
}
```
- **What**: WRITE — syncs profile company_id to match employee
- **Who triggers it**: Anyone viewing the employee detail page (ESS or admin)
- **Risk**: 🟡 MEDIUM — In admin mode, if an admin views an employee from company A while their session is company B, this could incorrectly change the profile's company_id. The ESS mode has a guard (`abort(403)` for cross-user access), but admin mode does not.
- **Mitigation**: Add `isSelfServiceMode` guard or only sync when `company_id` is null.

### 3. EmployeeDetail.php — `employeeProfile` load without scopes (line 205)
```php
'employeeProfile' => fn ($q) => $q->withoutGlobalScopes()
```
- **What**: READ — loads profile for display
- **Who sees it**: ESS user (own profile) or admin
- **Risk**: 🟢 LOW — The profile is displayed to the user who owns it (ESS) or an admin who already has access. No cross-company exposure of profile data to unauthorized users.

### 4. my-portal.blade.php — Dashboard card resolution (lines 17-23)
```php
Department::withoutGlobalScopes()->withTrashed()->find($position->department_id)
JobTitle::withoutGlobalScopes()->withTrashed()->find($position->job_title_id)
Employee::withoutGlobalScopes()->withTrashed()->find($position->manager_id)
```
- **What**: READ — resolves display names for the dashboard card
- **Who sees it**: ESS user viewing their own portal
- **Risk**: 🟢 LOW — Same as #1. Only display names. The user is viewing their own data.

### 5. Step1EmployeeRecord.php — Employee lookup (lines 51, 94-95, 127-132)
```php
Employee::withoutCompanyScope()->where('user_id', $user->id)->first()
Employee::withoutCompanyScope()->where('email', $user->email)->whereNull('user_id')->first()
```
- **What**: READ — finds the user's own employee record during onboarding
- **Who triggers it**: The authenticated user during self-onboarding
- **Risk**: 🟢 LOW — Scoped to the current user's own record (by user_id or email). Cannot access other users' records.

### 6. Step1EmployeeRecord.php — Position existence check (line 223)
```php
EmployeePosition::withoutGlobalScopes()->where('employee_id', $employee->id)->exists()
```
- **What**: READ — checks if position exists for the user's own employee
- **Risk**: 🟢 LOW — Scoped to the user's own employee_id.

### 7. EmployeeOnboardingWizard.php — Pre-linked detection (lines 52-56)
```php
Employee::withoutCompanyScope()->where('user_id', $user->id)->first()
Employee::withoutCompanyScope()->where('email', $user->email)->whereNull('user_id')->first()
```
- **What**: READ — finds pre-linked employee during onboarding
- **Risk**: 🟢 LOW — Same as #5. Scoped to current user.

### 8. EmployeeOnboardingWizard.php — Profile/Payroll check (lines 72-73, 82-83)
```php
EmployeeProfile::withoutCompanyScope()->where('employee_id', $this->employeeId)->first()
```
- **What**: READ — checks if profile exists
- **Risk**: 🟢 LOW — Scoped to the user's own employee_id.

### 9. InvitationService.php — Company assignment on accept (lines 89-103)
```php
$user->company_id = $invitation->company_id;
UserCompanyAssignment::firstOrCreate([...]);
```
- **What**: WRITE — assigns company to user on invitation acceptance
- **Risk**: 🟢 LOW — Uses the company_id from the invitation (set by admin). No cross-company exposure.

### 10. AutoInviteOnEmployeeCreate.php — Company from session
```php
session('hr_invite_company_id')
```
- **What**: READ — reads company from session set by admin form
- **Risk**: 🟢 LOW — Session value set by the admin who created the employee.

### 11-14. DataTable.php, DataTableForm.php, card-view, list-view
- **Risk**: 🟢 LOW — No scope-bypassing calls. UI/structural changes only.

---

## Pre-Existing Patterns (Not Our Changes)

### Onboarding Steps (Step2, Step3)
```php
Employee::withoutCompanyScope()->where('user_id', Auth::id())->first()
EmployeeProfile::withoutCompanyScope()->where('employee_id', $employee->id)->first()
```
- **Risk**: 🟢 LOW — Same pattern as our Step1 changes. Scoped to current user.

---

## Summary

| Risk Level | Count | Items |
|:---:|:---:|-------|
| 🔴 HIGH | 0 | None |
| 🟡 MEDIUM | 1 | Profile company_id sync in admin mode (EmployeeDetail.php:222) |
| 🟢 LOW | 13 | All other changes |

### Recommended Fix for the Medium Risk

The profile `company_id` sync at [`EmployeeDetail.php:222-227`](app/Modules/Hr/Http/Livewire/EmployeeDetail.php:222) should be guarded:

```php
// Only sync in self-service mode (ESS user viewing own profile)
// or when the profile has no company assigned yet
if ($this->profile && $this->employee->company_id
    && ($this->isSelfServiceMode || !$this->profile->company_id)
    && $this->profile->company_id !== $this->employee->company_id) {
    $this->profile->updateQuietly(['company_id' => $this->employee->company_id]);
}
```

This prevents an admin from accidentally changing a profile's company assignment when viewing cross-company employees.