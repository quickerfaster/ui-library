# AI Prompt: Invitation & Employee Registration Workflow Research

> **Paste this entire document into a new AI session to continue work on the invitation/employee registration feature.**

---

## Task Description

Research best workflow UX for inviting users/employees, analyze the current codebase state, and recommend an implementation approach. **No code implementation is required** — this is a research and analysis deliverable.

---

## Current State Summary

### Invitation System

The invitation system is a **bare placeholder** with no functional implementation:

| Aspect | Current State |
|--------|---------------|
| **Admin Invitations Page** | Placeholder view at [`src/Core/Admin/Resources/views/admin/invitations.blade.php`](src/Core/Admin/Resources/views/admin/invitations.blade.php) — displays "Full invitation management will be implemented in a future update" |
| **System Invitations Page** | Placeholder view at [`src/Core/System/Resources/views/system/invitations.blade.php`](src/Core/System/Resources/views/system/invitations.blade.php) — displays "This page will be implemented in the future" |
| **Admin Route** | `GET /admin/invitations` → catch-all route in [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php:89) |
| **System Route** | `GET /system/invitations` → catch-all route in [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php) |
| **Navigation** | Admin: "Invitations" under "Users" context group ([`src/Core/Admin/Config/navigation.php`](src/Core/Admin/Config/navigation.php:129)). System: "Invitations" under "accounts" context group ([`src/Core/System/Config/navigation.php`](src/Core/System/Config/navigation.php:141)) |
| **`invited` Status** | Defined on User model config at [`src/Core/Admin/Data/user.php`](src/Core/Admin/Data/user.php:39) with `warning` badge color (line 229), but has **no workflow** — no code sets or transitions this status |
| **Dashboard Quick Action** | "Send invitation to new user" action card exists in [`src/Core/Admin/Data/dashboard.php`](src/Core/Admin/Data/dashboard.php:208) but is non-functional |
| **No Invitation Model** | No `Invitation` model, migration, or data config exists anywhere in the codebase |
| **No Email Sending** | No invitation email template, no token generation, no accept-flow exists |

### Employee Registration

Employee registration uses a **manual `user_id` dropdown linking** pattern:

- The consuming app's `Employee` model (in `app/Modules/Hr/Models/`) has a `user_id` foreign key to the `users` table
- When creating/editing an employee, an admin manually selects a user from a dropdown
- There is **no automated pipeline** connecting invitation → user creation → employee record linking
- The [`pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md) §A documents the employee scoping pattern: `Employee::where('user_id', Auth::id())->first()`

### What's Missing

1. **No invitation creation flow** — no form, no email dispatch, no token generation
2. **No invitation acceptance flow** — no public route for invitees to set password and activate
3. **No employee linking automation** — invited users are not automatically linked to employee records
4. **No invitation status tracking** — the `invited` status on User model is never used
5. **No expiration/retry/revoke** — no lifecycle management for invitations

---

## Architecture Rules (Non-Negotiable)

These rules **must** be followed in any recommendation or implementation:

### 1. UI Library Must Remain Completely Decoupled

The library (`src/`) must **never** reference any consuming-app code (`App\Modules\*`). See [`docs/library/25-library-independence-safeguards.md`](docs/library/25-library-independence-safeguards.md) for the full policy.

Key rules:
- No `use App\Modules\...` in `src/`
- No domain nouns (`employee`, `payroll`, `leave`, etc.) in library code
- Library code must pass the "two-domain test": would it work in a CRM, not just HR?

### 2. Consuming App Depends on Library via Contracts/Subclass Patterns

The consuming app can depend on the library through:
- **Contract pattern**: Library defines interface in [`src/Contracts/`](src/Contracts/), consuming app binds implementation
- **Subclass pattern**: Library provides base class with `protected` extension points, consuming app overrides
- **Config-driven**: Domain-specific values passed via config arrays

See [`docs/consuming-app/contracts.md`](docs/consuming-app/contracts.md) for the 8 existing contracts.

### 3. All Module Files Self-Contained

Every file for a module lives under `app/Modules/{ModuleName}/`. See [`docs/consuming-app/module-structure.md`](docs/consuming-app/module-structure.md) for the full directory anatomy.

### 4. Follow Pre-Coding Checklist

Before any code is written, consult [`docs/consuming-app/pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md):
- **§A**: Blade view rules (catch-all awareness, navigation layout, Livewire component views)
- **§B**: Livewire component rules (library vs module boundary, subclass pattern, naming, registration)
- **§C**: Library code rules (no consuming app references, two-domain test, backward compatibility)
- **§D**: Module file rules (correct subdirectories, self-contained test)
- **§E**: Navigation rules (context group match, route coverage, permission, icon)

---

## Key Reference Files

### Library — Invitation-Related Files

| File | Description |
|------|-------------|
| [`src/Core/Admin/Resources/views/admin/invitations.blade.php`](src/Core/Admin/Resources/views/admin/invitations.blade.php) | Admin invitation placeholder view — "Full invitation management will be implemented in a future update" |
| [`src/Core/System/Resources/views/system/invitations.blade.php`](src/Core/System/Resources/views/system/invitations.blade.php) | System invitation placeholder view — "This page will be implemented in the future" |
| [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php:89) | Admin route: `GET /admin/invitations` |
| [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php) | System route: `GET /system/invitations` |
| [`src/Core/Admin/Config/navigation.php`](src/Core/Admin/Config/navigation.php:129) | Admin nav: "Invitations" under "Users" context group, `view_invitation` permission |
| [`src/Core/System/Config/navigation.php`](src/Core/System/Config/navigation.php:141) | System nav: "Invitations" under "accounts" context group, `view_invitation` permission |
| [`src/Core/Admin/Data/user.php`](src/Core/Admin/Data/user.php:39) | User data config — defines `invited` status option and `warning` badge color |
| [`src/Core/Admin/Data/dashboard.php`](src/Core/Admin/Data/dashboard.php:208) | Dashboard quick action: "Send invitation to new user" |
| [`src/Services/Notifications/NotificationService.php`](src/Services/Notifications/NotificationService.php) | Notification dispatch engine — `dispatch()`, `dispatchAsync()`, channels (Database, Mail), template resolution |
| [`src/Services/Notifications/Channels/MailChannel.php`](src/Services/Notifications/Channels/MailChannel.php) | Email notification channel — could be used for invitation emails |

### Consuming App — Employee/Registration Files

| File | Description |
|------|-------------|
| `app/Modules/Hr/Models/Employee.php` | Employee model with `user_id` foreign key |
| `app/Modules/Hr/Data/employee.php` | Employee data config — drives DataTable/Form/Detail |
| `app/Modules/Hr/Config/navigation.php` | HR navigation — includes "people" context with employee management |
| `app/Modules/Hr/Routes/web.php` | HR routes — employee CRUD routes |

### Documentation Files

| File | Description |
|------|-------------|
| [`docs/consuming-app/pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md) | **Must-read before any code** — Blade, Livewire, library, module, and navigation rules |
| [`docs/library/25-library-independence-safeguards.md`](docs/library/25-library-independence-safeguards.md) | Library decoupling rules, grep gates, forbidden patterns |
| [`docs/consuming-app/contracts.md`](docs/consuming-app/contracts.md) | 8 contracts: Workflowable, Documentable, Notifiable, Reportable, CompanyProvider, WorkspaceResolver, ApproverResolver, ApproverLabelResolver |
| [`docs/consuming-app/module-structure.md`](docs/consuming-app/module-structure.md) | Full module anatomy, auto-discovery, naming conventions |
| [`docs/consuming-app/permissions-and-notifications.md`](docs/consuming-app/permissions-and-notifications.md) | Permission auto-generation, notification template registration |
| [`docs/library/27-architecture-boundary.md`](docs/library/27-architecture-boundary.md) | Library vs module boundary — what belongs where |
| [`docs/library/01-core-concepts.md`](docs/library/01-core-concepts.md) | Authentication/onboarding via Laravel Fortify + Spatie Onboard |
| [`docs/library/02-directory-map.md`](docs/library/02-directory-map.md) | Complete `src/` directory tree |
| [`docs/library/06-navigation-system.md`](docs/library/06-navigation-system.md) | Navigation architecture, context groups, sidebar rendering |
| [`docs/library/08-contracts-and-interfaces.md`](docs/library/08-contracts-and-interfaces.md) | Contract details and implementation guides |
| [`docs/project/admin-navigation-context-group-split.md`](docs/project/admin-navigation-context-group-split.md) | Admin navigation design — "Invitations" is tightly coupled to Users context |

### Post-Implementation Reference

| File | Description |
|------|-------------|
| [`plans/post-implementation-guide.md`](plans/post-implementation-guide.md) | Vendor override awareness, cache management, troubleshooting |

---

## What to Investigate

### 1. Best UX Patterns for User Invitation

Research how leading SaaS platforms handle user invitation workflows. Key platforms to study:

| Platform | Notable Patterns |
|----------|-----------------|
| **Gusto** | Employee self-onboarding — invite → employee fills own details → admin approves |
| **Rippling** | Bulk invite via CSV, role + permissions assigned at invite time, employee self-service portal |
| **Slack** | Magic link email, single-click acceptance, optional account creation step |
| **GitHub** | Email invite → accept → choose organization role, pending invite management dashboard |
| **Linear** | Invite by email → auto-join workspace, role assignment during invite |
| **Notion** | Share link + email invite, guest vs member roles, pending invites list |

Focus on:
- The **invite → accept → onboard** flow stages
- How pending/expired invitations are managed
- Whether the invitee sets their own password or the inviter sets it
- How roles/permissions are assigned (at invite time vs after acceptance)
- Bulk invite patterns (CSV upload, copy-paste email list)
- Invitation expiration and resend mechanisms

### 2. Current Invitation Placeholder Analysis

Examine the two placeholder views and their navigation context:

- [`src/Core/Admin/Resources/views/admin/invitations.blade.php`](src/Core/Admin/Resources/views/admin/invitations.blade.php) — Admin-side, under "Users" context group
- [`src/Core/System/Resources/views/system/invitations.blade.php`](src/Core/System/Resources/views/system/invitations.blade.php) — System-side, under "accounts" context group

Questions to answer:
- Should both placeholder views be replaced, or should one be removed?
- What is the distinction between Admin invitations and System invitations?
- Should the invitation management live in the library or the consuming app?

### 3. Current Employee Registration Flow

Trace the existing employee creation flow:
- How does `user_id` get assigned to an employee record?
- Is the user created first, then linked to employee? Or vice versa?
- What happens when a user has no employee record? (See [`pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md) §A — abort 403 pattern)
- How does the Employee Self-Service (ESS) system resolve the current user to their employee record?

### 4. Linking Invited User Email to Employee Record

The core challenge: when an admin invites someone, how does the system know which employee record to link?

Possible approaches to evaluate:
- **Email matching**: Match invitation email to `Employee.work_email` or `User.email`
- **Pre-linked invitation**: Admin selects employee during invite creation → invitation is pre-linked
- **Employee self-claim**: Invitee enters employee ID or identifying info during acceptance
- **HR links after acceptance**: Admin manually links user to employee after they accept

---

## What to Recommend

Your analysis should produce clear recommendations on:

### 1. Where Should Invitation Management Live?

| Option | Description |
|--------|-------------|
| **A: Library** | Generic invitation engine in `src/` — reusable across any domain (HR, CRM, project management). Provides token generation, email dispatch, accept flow, status tracking. Consuming app extends for domain-specific linking. |
| **B: Consuming App** | Invitation logic entirely in `app/Modules/` — HR-specific invitation tied to employee records. Library provides only notification delivery. |
| **C: Hybrid** | Library provides invitation infrastructure (model, token, email channel). Consuming app provides the UI, employee linking, and domain-specific workflow. |

**Consider**: The library already has `NotificationService` with `MailChannel` — could this serve as the invitation email delivery mechanism? The library also has `invited` status on User model — is this a library concern or a consuming-app concern?

### 2. How to Link Invited Users to Employee Records

Recommend a specific linking strategy with rationale. Consider:
- Does the Employee record exist before the invitation? (Pre-hire scenario)
- Does the invitation create the Employee record? (Self-registration scenario)
- What if the invited email doesn't match any employee email?

### 3. The Invitation Workflow UX

Design the end-to-end flow:

```
SEND → ACCEPT → ONBOARD
```

Detail each stage:
- **Send**: Admin form (email, role, optional employee link), bulk CSV, email template
- **Accept**: Public route with signed URL/token, password creation, email verification
- **Onboard**: Post-acceptance steps (profile completion, employee record linking, role activation)

### 4. Should the Library's Notification System Be Used?

The library's [`NotificationService`](src/Services/Notifications/NotificationService.php) already supports:
- Multi-channel delivery (Database, Mail, Broadcast)
- Template resolution with variable substitution
- Async dispatch via `dispatchAsync()`
- Notification preferences per user

Evaluate whether invitation emails should:
- Use `NotificationService` with a new `invitation` template type
- Use a dedicated invitation mail system (Laravel's built-in `Notification` or `Mail` facade)
- Use a hybrid (NotificationService for in-app notification + dedicated mail for the invitation email itself)

---

## Deliverable

**An analysis report** (written to a markdown file) containing:

1. **UX Research Summary**: Key patterns from SaaS platforms, with specific examples
2. **Current State Analysis**: What exists, what's missing, what's blocked
3. **Architecture Recommendation**: Where invitation logic should live (library vs consuming app), with rationale grounded in the library independence safeguards
4. **Linking Strategy**: How to connect invited users to employee records
5. **Workflow Design**: The send → accept → onboard flow, with wireframe-level descriptions
6. **Notification Strategy**: Whether/how to use the library's notification system
7. **Implementation Roadmap**: Phased approach with clear library vs consuming-app boundaries for each phase

**No code implementation.** This is purely research, analysis, and recommendation.

---

## Context for the AI

This is a Laravel application using:
- **UI Library** (`src/`): A decoupled, domain-agnostic UI framework providing DataTables, Forms, Wizards, Dashboards, Navigation, Notifications, Workflows, Approvals, Documents, Reports, Quick Actions, and more
- **Consuming App** (`app/Modules/`): Business modules (HR, Payroll, Leave, Attendance, Organization, Holiday) built on top of the library
- **Livewire**: Full-stack reactive components
- **Laravel Fortify**: Authentication (login, registration, password reset, 2FA)
- **Spatie Onboard**: Multi-step onboarding flows
- **Spatie Permissions**: Role-based access control

The library follows strict decoupling rules — it must never reference consuming-app code. The consuming app extends the library through contracts, subclasses, and config-driven patterns.

When analyzing, always consider: **"Does this belong in the library (reusable mechanism) or the consuming app (domain-specific policy)?"**