# Employee Invitation Flow Investigation Report

> **Status**: ✅ COMPLETE — All 5 fixes implemented (2026-09-22)

## Overview

This report traces the full employee invitation flow in the HR consuming app, identifying how `company_id` is assigned to employees, how employee positions are created, and where mismatches can occur between an employee's company and their position's FK references.

---

## 1. The Two Invitation Entry Points

There are **two distinct paths** for creating invitations:

### Path A: Create Employee + Send Invitation (HrEmployeeForm)

**Files involved:**
- [`HrEmployeeForm.php`](app/Modules/Hr/Http/Livewire/HrEmployeeForm.php:1) — extends `DataTableForm`, adds a "Send Invitation" checkbox
- [`AutoInviteOnEmployeeCreate.php`](app/Modules/Hr/Listeners/AutoInviteOnEmployeeCreate.php:1) — listener that fires after employee save
- [`HrInvitationService.php`](app/Modules/Hr/Services/HrInvitationService.php:1) — creates the pre-linked invitation
- [`InvitationService.php`](src/Services/Invitations/InvitationService.php:1) (UI library) — core invitation creation

**Flow:**
1. Admin fills employee form → `HrEmployeeForm::save()` sets `session('hr_send_invitation_on_create', true)` then calls `parent::save()`
2. `DataTableForm::save()` creates the Employee record with `company_id` auto-injected from `session('current_company_id')` (line 817–824)
3. `DataTableRecordSaved` event fires
4. `AutoInviteOnEmployeeCreate::handleCreated()` picks up the session flag, calls `HrInvitationService::createWithEmployeeLink()` with `role: 'employee'` (hardcoded)
5. `InvitationService::create()` creates the Invitation with token, sends email, dispatches `InvitationSent`

### Path B: Invite Existing Employee (HrInvitationForm)

**Files involved:**
- [`HrInvitationForm.php`](app/Modules/Hr/Http/Livewire/HrInvitationForm.php:1) — extends `DataTableForm`, adds employee selector dropdown
- [`PreLinkInvitationToEmployee.php`](app/Modules/Hr/Listeners/PreLinkInvitationToEmployee.php:1) — links invitation to employee after creation
- [`InvitationRecordListener.php`](src/Core/Admin/Listeners/InvitationRecordListener.php:1) (UI library) — generates token + sends email

**Flow:**
1. Admin opens invitation form, selects an existing employee, fills email + role
2. `HrInvitationForm::save()` flashes `session('hr_invitation_employee_id', $employeeId)` then calls `parent::save()`
3. `DataTableForm::save()` creates a bare Invitation record (no token yet)
4. `DataTableRecordSaved` event fires → two listeners respond:
   - `InvitationRecordListener::handleCreated()` — generates token, sets expiration, sends email
   - `PreLinkInvitationToEmployee::handleCreated()` — sets `invitable_type`/`invitable_id` on the invitation to link it to the employee

### Path C: Generic Invitation (no employee pre-link)

Same as Path B but without selecting an employee — no pre-linking occurs. On acceptance, the system falls back to email matching.

---

## 2. How Employee `company_id` Is Assigned

### During Employee Creation (Admin Form)

**File:** [`DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php:817)

```php
// Line 817–824
if (!$this->isEditMode) {
    $companyId = \Illuminate\Support\Facades\Session::get('current_company_id');
    if ($companyId && $companyId !== 0 && \Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'company_id')) {
        $data['company_id'] = $companyId;
    }
}
```

**Key behavior:**
- `company_id` is **hidden** on the employee new form ([`employee.php` line 188–193](app/Modules/Hr/Data/employee.php:188))
- When admin is in a **specific company context** (session `current_company_id` = e.g. 5): `company_id` is auto-injected as that company's ID
- When admin is in **"All Companies" mode** (session `current_company_id` = 0): `company_id` is **not injected** — it's shown on the form for manual selection (via `loadConfiguration()` at line 170–177)
- The `Employee` model's `boot()` method is **empty** — no `creating`/`saving` event auto-sets `company_id`

### During Onboarding (Step1EmployeeRecord)

**File:** [`Step1EmployeeRecord.php`](app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step1EmployeeRecord.php:78)

```php
// Lines 101–111 — updateOrCreate does NOT include company_id
$employee = Employee::withoutCompanyScope()->updateOrCreate(
    ['user_id' => $user->id],
    [
        'employee_number' => $employeeNumber,
        'first_name' => $this->first_name,
        'last_name' => $this->last_name,
        'email' => $this->email,
        'phone' => $this->phone,
        'hire_date' => $this->hire_date,
        // ❌ company_id is NOT set here
    ]
);
```

**Key behavior:**
- If the employee was **pre-created by admin** (Path A), `company_id` is already set and preserved
- If the employee is being **created for the first time** during onboarding (no pre-linked employee), `company_id` remains **NULL**
- The `updateOrCreate` uses `user_id` as the unique key, so it won't overwrite an existing `company_id`

### Via UserCompanyAssignment (Post-Acceptance)

**File:** [`UserCompanyAssignment.php`](app/Modules/Organization/Http/Livewire/UserCompanyAssignment.php:87)

```php
// Lines 96–103
$employee = Employee::where('user_id', $user->id)->first();
if ($employee) {
    $company = $user->companies()->first();
    \DB::table('employees')->where('id', $employee->id)->update([
        'company_id' => $company?->id,
        'onboarding_status' => $employee->employeePosition()->exists() ? 'complete' : 'position_pending',
    ]);
}
```

This is the **reconciliation point** — when an admin assigns companies to a user, the employee's `company_id` is updated to the first assigned company.

---

## 3. How Employee Position Is Created

### Position Is NEVER Auto-Created

There is **no code anywhere** that auto-creates an `EmployeePosition` during:
- Employee creation
- Invitation sending
- Invitation acceptance
- Onboarding wizard steps

The position must be **manually created by an admin** via the Employee Position data table form.

### Position `company_id` Auto-Set Behavior

**File:** [`EmployeePosition.php`](app/Modules/Hr/Models/EmployeePosition.php:242)

```php
// Lines 242–256 — booted() method
protected static function booted()
{
    static::saving(function (self $position) {
        if ($position->employee_id && $position->isDirty('employee_id')) {
            $employee = \App\Modules\Hr\Models\Employee::withoutCompanyScope()
                ->find($position->employee_id);
            if ($employee && $employee->company_id) {
                $position->company_id = $employee->company_id;
            }
        }
    });
    // ... history tracking ...
}
```

**Key behavior:**
- When creating a new position, `employee_id` IS dirty → `company_id` is auto-set from the employee
- When updating an existing position, `employee_id` is typically NOT dirty → `company_id` is NOT re-synced
- The position's `company_id` is **hidden** on both new and edit forms ([`employee_position.php` line 348–353](app/Modules/Hr/Data/employee_position.php:348))
- `DataTableForm::save()` first injects `company_id` from session, then the `saving` event **overrides** it with the employee's `company_id`

---

## 4. User-Role-Company Relationship

### Role Assignment (During Invitation Acceptance)

**File:** [`InvitationService.php`](src/Services/Invitations/InvitationService.php:48)

```php
// Lines 72–84
if (method_exists($user, 'assignRole') && $invitation->role) {
    $roleName = $invitation->role;
    if (is_numeric($roleName)) {
        $role = \Spatie\Permission\Models\Role::find($roleName);
        $roleName = $role ? $role->name : null;
    }
    if ($roleName) {
        $user->assignRole($roleName);
    }
}
```

- Role is assigned via Spatie's `assignRole()`
- **No company is assigned to the user** during acceptance
- The `AcceptInvitation` component clears `session('current_company_id')` after login (line 93)

### Company Assignment (Post-Acceptance)

Company assignment happens separately via the [`UserCompanyAssignment`](app/Modules/Organization/Http/Livewire/UserCompanyAssignment.php:1) Livewire component:
- Admin must manually assign companies to the user
- This updates the `company_user` pivot table
- It also updates the employee's `company_id` to the first assigned company

### Company Context Resolution

**File:** [`ResolveCompanyContext.php`](src/Http/Middleware/ResolveCompanyContext.php:1) + [`HrsCompanyProvider.php`](app/Modules/Hr/Providers/HrsCompanyProvider.php:1)

When the invited user logs in:
1. `ResolveCompanyContext` middleware runs — session has no `current_company_id` (cleared by AcceptInvitation)
2. `HrsCompanyProvider::getCurrentCompanyId()` is called:
   - Super admins → returns 0 ("All Companies")
   - Users with companies in pivot → returns first company ID
   - Users with employee record → returns `employee.company_id`
   - Otherwise → returns null

---

## 5. The Mismatch: Employee Company vs Position FK References

### Scenario That Produces the Bug

```
1. Admin is in "Company A" context (session current_company_id = 1)
2. Admin creates Employee X → employee.company_id = 1 (auto-injected from session)
3. Admin sends invitation → Employee X accepts, completes onboarding
4. Admin switches to "Company B" context (session current_company_id = 2)
   OR switches to "All Companies" mode (session current_company_id = 0)
5. Admin creates EmployeePosition for Employee X:
   - Selects Department "Engineering" (belongs to Company B, due to CompanyScope filtering)
   - Selects Job Title "Developer" (belongs to Company B)
   - Selects Manager "John" (employee in Company B)
6. DataTableForm::save() injects company_id = 2 (from session)
7. EmployeePosition::saving event fires → overrides company_id = 1 (from Employee X)
8. RESULT:
   - position.company_id = 1 (Company A) ← from employee
   - position.department_id → Department in Company B ← MISMATCH
   - position.job_title_id → Job Title in Company B ← MISMATCH
   - position.manager_id → Manager in Company B ← MISMATCH
```

### Root Cause Chain

| Step | What Happens | File:Line |
|------|-------------|-----------|
| 1 | `company_id` hidden on employee form, auto-injected from session | [`DataTableForm.php:817`](src/Http/Livewire/DataTables/DataTableForm.php:817) |
| 2 | `company_id` hidden on position form, auto-injected from session | [`employee_position.php:348`](app/Modules/Hr/Data/employee_position.php:348) |
| 3 | Position `saving` event overrides `company_id` from employee | [`EmployeePosition.php:247`](app/Modules/Hr/Models/EmployeePosition.php:247) |
| 4 | FK dropdowns (department, job title, manager) are scoped to admin's **current** company via `CompanyScope` | [`CompanyScope.php:29`](src/Scopes/CompanyScope.php:29) |
| 5 | No validation that position FKs belong to the same company as the employee | **MISSING** |

### Additional Issues Found

**Issue A: `company_id` can be NULL on employee**
- If admin creates employee in "All Companies" mode without selecting a company → `company_id` = NULL
- `Step1EmployeeRecord` never sets `company_id` during onboarding
- Employee ends up with `onboarding_status = 'company_pending'`

**Issue B: Role is hardcoded in AutoInviteOnEmployeeCreate**
- [`AutoInviteOnEmployeeCreate.php:37`](app/Modules/Hr/Listeners/AutoInviteOnEmployeeCreate.php:37) always uses `role: 'employee'`
- The admin has no way to choose a different role when using the "Send invitation after saving" checkbox

**Issue C: Position is never created during onboarding**
- The onboarding wizard has 5 steps but none create an EmployeePosition
- Employee ends up with `onboarding_status = 'position_pending'` until admin manually creates it

**Issue D: No company validation on position FK fields**
- When creating a position, there's no check that `department.company_id == employee.company_id`
- The `EmployeePosition::saving` event only syncs `company_id` but doesn't validate FKs

---

## 6. Suggested Fix Approach

### Fix 1: Validate Position FK Company Consistency

Add validation in `EmployeePosition::booted()` saving event to ensure all FK references belong to the same company as the employee:

```
In EmployeePosition::saving():
  After setting company_id from employee, validate:
  - department.company_id == position.company_id
  - jobTitle.company_id == position.company_id  
  - manager.company_id == position.company_id (if manager_id is set)
  - location.company_id == position.company_id (if locations are scoped)
```

### Fix 2: Auto-Set Employee `company_id` During Onboarding

In [`Step1EmployeeRecord::save()`](app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step1EmployeeRecord.php:101), if the employee doesn't have a `company_id`, resolve it from the user's assigned companies:

```
If employee exists but company_id is null:
  - Check user->companies()->first()
  - If found, set company_id on the employee
```

### Fix 3: Scope Position FK Dropdowns to Employee's Company

When editing/creating a position for an employee who already has a `company_id`, the FK dropdowns (department, job title, manager) should be filtered to that employee's company, not the admin's current session company. This could be done via a custom `CompanyProvider` or by overriding the dropdown options in the position form.

### Fix 4: Allow Role Selection in Auto-Invite Flow

Modify [`HrEmployeeForm`](app/Modules/Hr/Http/Livewire/HrEmployeeForm.php:1) to include a role selector dropdown alongside the "Send Invitation" checkbox, and pass it through to [`AutoInviteOnEmployeeCreate`](app/Modules/Hr/Listeners/AutoInviteOnEmployeeCreate.php:35).

### Fix 5: Auto-Create Position During Onboarding (Optional Enhancement)

Add a step to the onboarding wizard that allows the employee (or redirects them) to fill in position details, or auto-create a minimal position record with defaults from their department/company.

---

## Flow Diagram

```mermaid
flowchart TD
    A[Admin creates Employee] --> B{In All Companies mode?}
    B -->|Yes| C[company_id shown on form - admin selects]
    B -->|No| D[company_id auto-injected from session]
    C --> E[Employee record created]
    D --> E
    
    E --> F{Send invitation?}
    F -->|Yes - checkbox| G[AutoInviteOnEmployeeCreate fires]
    F -->|No| H[Employee exists, no user linked]
    
    G --> I[Invitation created with role='employee' hardcoded]
    
    E --> J[Admin uses HrInvitationForm separately]
    J --> K[Invitation created, PreLinkInvitationToEmployee links it]
    
    I --> L[Email sent to employee]
    K --> L
    
    L --> M[Employee clicks accept link]
    M --> N[InvitationService::accept]
    N --> O[User created/activated]
    O --> P[Role assigned via Spatie assignRole]
    P --> Q[LinkInvitationToEmployee sets user_id on employee]
    Q --> R[AcceptInvitation clears session company_id, redirects to onboarding]
    
    R --> S[Step1EmployeeRecord::save]
    S --> T{Employee has company_id?}
    T -->|Yes| U[company_id preserved]
    T -->|No| V[company_id remains NULL - PROBLEM]
    
    U --> W[Onboarding continues]
    V --> W
    
    W --> X[Admin must manually create EmployeePosition]
    X --> Y{Admin company context}
    Y -->|Same as employee| Z[FKs match - OK]
    Y -->|Different from employee| AA[FKs point to wrong company - BUG]
    
    AA --> AB[Position.company_id = employee's company]
    AA --> AC[Position.department_id = other company's department]
    AB & AC --> AD[MISMATCH]