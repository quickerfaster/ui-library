# Invitation Flow — Company Assignment Gap Analysis

> **Status**: ✅ IMPLEMENTED — company_id added to Invitation model, config, service, and acceptance flow (2026-09-22)

## Current State

### Invitation Model (`src/Models/Invitation.php`)
- `$fillable`: email, token, status, role, invitable_type, invitable_id, message, expires_at, accepted_at, revoked_at, reminded_at, created_by
- **NO `company_id` column or field**

### Invitation Config (`src/Core/Admin/Data/invitation.php`)
- Fields: email, status, role, message, token, expires_at, accepted_at, revoked_at, created_by, created_at
- **NO `company_id` field definition**

### Invitation Form (`hr-invitation-form.blade.php`)
- Uses `configKey="admin.invitation"` with standard DataTableForm
- Has employee selector (pre-links invitation to existing employee)
- **NO company dropdown** — even though DataTableForm supports it

### DataTableForm Company Dropdown Behavior (`DataTableForm.php:170-183`)
- **Specific company selected** (session > 0): `company_id` is HIDDEN, auto-injected from session
- **"All Companies" selected** (session = 0): `company_id` is SHOWN as required dropdown
- But this only works if the model HAS a `company_id` column — Invitation does NOT

### InvitationService::accept() (`src/Services/Invitations/InvitationService.php:48-96`)
- Creates/activates user
- Assigns Spatie role
- **Does NOT assign company to user**

### AcceptInvitation (`src/Http/Livewire/Invitations/AcceptInvitation.php:63-133`)
- Calls `InvitationService::accept()`
- Logs user in
- Redirects to onboarding
- **Does NOT assign company**

## The Gap

```
Admin creates invitation → role stored, NO company
         ↓
User accepts invitation → role assigned, NO company  
         ↓
User completes onboarding → Step1EmployeeRecord creates employee
         ↓
resolveCompanyId() finds NO company (user has none)
         ↓
Employee gets company_id = null OR wrong company from session
         ↓
Position FKs point to records in different company
         ↓
ESS shows raw IDs, department/manager missing, job history constraint violations
```

## Suggested Fix (3 Changes)

### 1. Add `company_id` to Invitation Model + Table

**Library**: `src/Models/Invitation.php`
- Add `'company_id'` to `$fillable`
- Add migration: `$table->foreignId('company_id')->nullable()->constrained('companies')`

**Library**: `src/Core/Admin/Data/invitation.php`
- Add `company_id` field definition with `field_type: 'select'`, relationship to Company model
- Add to `hiddenFields.onTable` (show only on forms)

**Library**: `src/Services/Invitations/InvitationService.php`
- Add `?int $companyId = null` parameter to `create()` method
- Store `company_id` on the invitation record

### 2. Assign Company During Invitation Acceptance

**Library**: `src/Services/Invitations/InvitationService.php:accept()`
- After assigning role (line 84), read `company_id` from the invitation
- If set, assign company to user via `UserCompanyAssignment` or direct `company_id` on user model

### 3. DataTableForm Will Auto-Handle the Dropdown

Once the Invitation model has `company_id`:
- In "All Companies" mode: DataTableForm shows company dropdown (required) ✓
- In specific company mode: DataTableForm hides it and auto-injects from session ✓
- No Blade changes needed — DataTableForm handles it automatically

## Impact

| Before | After |
|--------|-------|
| Invitation has no company | Invitation stores target company |
| User gets no company on accept | User gets company from invitation |
| Employee created without company | Employee inherits company from user |
| Position FKs mismatch | Position FKs match employee's company |
| ESS shows raw IDs | ESS shows resolved names |