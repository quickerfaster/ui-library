# HR Invitation Entry Points — Analysis & Recommendation

> **Date**: 2026-09-09
> **Status**: Analysis
> **References**: [`hr-invitation-integration-strategy.md`](hr-invitation-integration-strategy.md) (Options A–D), HR navigation config, HR dashboard configs

---

## 1. Current HR Navigation Structure

### 1.1 Context Groups

The HR module defines five context groups in [`navigation.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Config/navigation.php:4):

| Group | Label | Order | Permission Gate | Landing URL |
|---|---|---|---|---|
| `dashboard` | Dashboard | 0 | `view_hidden_dashboard` | `hr/dashboard` |
| `my-portal` | My Portal | 1 | `view_my_portal` | `hr/my-portal` |
| `Organization` | Organization | 10000 | `view_organization_overview` | `hr/dashboard-organization-overview` |
| `people` | People | 999 | **none** (roles: `['*']`) | `hr/dashboard-people-overview` |
| `manage` | Manage | 1000 | `view_manage_overview` | `hr/dashboard-manage-overview` |

### 1.2 People Context Group Items

The `people` group currently has six sidebar items ([`navigation.php:127-182`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Config/navigation.php:127)):

| # | Key | Label | Route | Permission |
|---|---|---|---|---|
| 1 | `people_overview` | Overview | `/hr/dashboard-people-overview` | `view_people_overview` |
| 2 | `employee` | Employees | `/hr/employees` | `view_employee` |
| 3 | `employee_profile` | Profiles | `/hr/employee-profiles` | `view_employee_profile` |
| 4 | `employee_position` | Current Jobs | `/hr/employee-positions` | `view_employee_position` |
| 5 | `team` | Teams | `/hr/teams` | `view_team` |
| 6 | `employee_group` | Employee Groups | `/hr/employee-groups` | `view_employee_group` |

**Key observation**: The People group has no group-level permission gate — only `roles => ['*']`. This means all authenticated users see the People group. Individual items are gated by their own permissions.

### 1.3 Admin Module Invitations (for comparison)

The library's admin module places Invitations under the "Users" context group ([`navigation.php:129-137`](/Users/mac/Projects/Libraries/ui-library/src/Core/Admin/Config/navigation.php:129)):

```
Users context group:
  ├── Overview       → /admin/dashboard-users-overview
  ├── Users          → /admin/users           (view_user)
  ├── Invitations    → /admin/invitations     (view_invitation)
  ├── User Groups    → /admin/user-groups     (view_user_group)
  └── ...
```

The admin dashboard also has:
- A **"Pending Invitations" stat widget** on the Users Overview dashboard ([`dashboard_users_overview.php:60-76`](/Users/mac/Projects/Libraries/ui-library/src/Core/Admin/Data/dashboards/dashboard_users_overview.php:60)), linking to `/admin/invitations?status=pending`
- An **"Invite User" action card** on the main admin dashboard ([`dashboard.php:204-230`](/Users/mac/Projects/Libraries/ui-library/src/Core/Admin/Data/dashboard.php:204)), opening a drawer with `admin.invitation` DataTableForm

---

## 2. Suggestion 1 Analysis — Sidebar Link in People Context Group

### 2.1 Fit Assessment

**Does "Invitations" fit naturally alongside employee management items?**

Partially. The People group is about managing the employee lifecycle — employees, profiles, positions, teams, groups. Invitations are a *pre-employment* concern: they happen before someone becomes an employee. This creates a conceptual tension:

- **For**: Invitations are how people enter the system; they're the first step in the employee lifecycle. HR admins managing people would naturally want to see who's been invited.
- **Against**: Invitations are not about *existing* employees. They're about *prospective* users. The People group is entirely about current workforce data.

### 2.2 Route Options

| Option | Route | Pros | Cons |
|---|---|---|---|
| **A. Link to existing admin page** | `/admin/invitations` | Zero new code; reuses existing DataTable, filters, row actions | Cross-module navigation; HR admins need `view_invitation` permission; leaves HR context |
| **B. New HR-specific view** | `/hr/invitations` | Stays in HR context; can add employee-specific columns/filters | Requires new route, blade view, and potentially a new DataTable config; duplicates admin functionality |
| **C. HR view wrapping admin DataTable** | `/hr/invitations` using `admin.invitation` config | Stays in HR context; reuses existing DataTable; minimal new code | Still needs route + blade; same data as admin page |

**Recommendation**: **Option C** — create an HR route that renders the same `admin.invitation` DataTable but within the HR layout. This gives HR admins a contextual view without duplicating logic. The library's `InvitationDataTable` is config-driven and works with any config key.

### 2.3 Permission

The library already defines `view_invitation` as the permission for the admin Invitations link. The HR sidebar link should use the same permission for consistency. No new permission is needed.

However, note that the People group itself has no permission gate. If an HR admin has `view_invitation` but not `view_employee`, they'd see the Invitations link but not the Employees link — which is actually fine, as it reflects their actual permissions.

### 2.4 Alternative: New "Onboarding" Context Group

A dedicated "Onboarding" context group could house:
- Invitations
- Employee onboarding status
- New hire checklist
- Document collection status

This is conceptually cleaner but adds a new top-level navigation item. Given the current five groups already cover the HR domain well, a new group is **not recommended at this stage**. It could be considered in a future phase when onboarding features mature.

### 2.5 Recommended Sidebar Item

```php
[
    'key' => 'invitation',
    'label' => 'Invitations',
    'icon' => 'fas fa-envelope-open-text',
    'route' => '/hr/invitations',
    'permission' => 'view_invitation',
    'order' => 7,
    'page_title' => null,
],
```

Place it at order 7 (after Employee Groups at order 6) within the `people` context group.

---

## 3. Suggestion 2 Analysis — Dashboard Action Card

### 3.1 Dashboard Candidates

The HR module has nine dashboards. The relevant ones for an invitation action card:

| Dashboard | File | Relevance | Current Action Cards |
|---|---|---|---|
| **People Overview** | [`people_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/people_overview.php) | **High** — workforce metrics, hiring trends, recent hires | "Process Payroll" (misplaced here — payroll isn't a people concern) |
| **HR Main Dashboard** | [`dashboard.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard.php) | **Medium** — cross-module summary | "Request Leave", "Process Payroll", "Clock In/Out", "View My Team" |
| **Manage Overview** | [`dashboard_manage_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php) | **Low** — job titles, tags, documents | "Job Titles", "Tags", "Job History" |

### 3.2 Recommendation: People Overview Dashboard

The **People Overview** dashboard is the best fit because:
1. It already shows hiring-related widgets: "Total Employees", "Recent Hires", "New Hires Trend"
2. Invitations are the step *before* hiring — a natural extension of the hiring pipeline
3. It has only one action card ("Process Payroll") which is arguably misplaced
4. The dashboard description is "Key workforce metrics, hiring trends, and team analytics"

### 3.3 Action Card Design

Two approaches:

**Approach A: Stat + Action (Recommended)**

A `stat` widget showing "Pending Invitations" count, combined with an `action_card` for "Send Invitation":

```php
// Stat widget — Pending Invitations
[
    'type' => 'stat',
    'title' => 'Pending Invitations',
    'size' => 'col-12',
    'model' => 'QuickerFaster\\UILibrary\\Models\\Invitation',
    'icon' => 'fas fa-envelope-open-text',
    'aggregate' => 'count',
    'conditions' => [
        ['status', '=', 'pending'],
    ],
    'width' => 3,
    'link' => '/hr/invitations?status=pending',
],

// Action card — Send Invitation
[
    'type' => 'action_card',
    'title' => 'Send Invitation',
    'size' => 'col-12',
    'icon' => 'fas fa-paper-plane',
    'description' => 'Invite a new user to the platform',
    'actions' => [
        [
            'label' => 'Invite',
            'event' => 'openDrawer',
            'params' => [
                'component' => 'qf.data-table-form',
                'params' => [
                    'configKey' => 'admin.invitation',
                    'recordId' => null,
                ],
                'title' => 'Send Invitation',
            ],
            'style' => 'primary',
        ],
    ],
    'width' => 3,
],
```

This mirrors the admin dashboard pattern: a stat showing pending count (clickable, linking to filtered list) plus an action card to create new invitations.

**Approach B: Action Card Only**

Just the "Send Invitation" action card without the stat. Simpler but less informative — HR admins can't see at a glance how many invitations are pending.

**Recommendation**: **Approach A** — the stat widget provides situational awareness (are there pending invitations needing follow-up?) while the action card provides the call to action. This is the same pattern used successfully in the admin Users Overview dashboard.

### 3.4 Comparison with Admin Dashboard Quick Action

| Aspect | Admin Dashboard | Proposed HR People Overview |
|---|---|---|
| **Stat widget** | "Pending Invitations" (width: 3) | "Pending Invitations" (width: 3) |
| **Action card** | "Invite User" → opens `admin.invitation` drawer | "Send Invitation" → opens `admin.invitation` drawer |
| **Context** | User management context | People/hiring context |
| **Audience** | System administrators | HR administrators |

The HR version is intentionally similar — it reuses the same library components (`qf.data-table-form` with `admin.invitation` config). The difference is *where* HR admins discover it: in their familiar People dashboard rather than navigating to the Admin module.

---

## 4. Recommendation

### 4.1 Implementation Priority

```
Phase 1 (This Effort) — Entry Points:
  1. Sidebar link in People context group     [Suggestion 1]
  2. Dashboard stat + action card             [Suggestion 2]

Phase 2 (Already Planned) — Deep Integration:
  3. Employee selector in invitation form     [Strategy Doc: Option A]
  4. Invitations tab on employee profile      [Strategy Doc: Option D]

Phase 3 (Future):
  5. "Send Invitation" on employee creation   [Strategy Doc: Option B]
```

### 4.2 Rationale

**Why both Suggestion 1 and Suggestion 2?**

They serve complementary discovery paths:
- **Sidebar link** — for HR admins who navigate by menu structure. They think "I need to manage invitations" → look in the People section → find the link.
- **Dashboard card** — for HR admins who land on the dashboard and scan for actions. The "Pending Invitations" stat catches their attention; the "Send Invitation" button lets them act immediately.

Together they provide two distinct discovery paths, matching how different users navigate the system.

**Why Phase 1 before Phase 2?**

Entry points (sidebar + dashboard) are lower effort and provide immediate value. They give HR admins a way to *find* invitations from the HR context. Deep integration (employee selector, profile tab) requires more work and builds on having the entry points in place.

**Why not a new "Onboarding" context group?**

Premature. The current five groups cover the HR domain well. A new group would be warranted when there are 3+ onboarding-related items (invitations, onboarding checklist, document collection, new hire tracking). At that point, invitations could be moved from People to Onboarding.

### 4.3 Cross-Module Navigation

Both suggestions involve navigating between the HR module and library-provided admin pages. This is acceptable because:

1. The library's invitation system is domain-agnostic by design
2. The `admin.invitation` DataTable config already handles all CRUD operations
3. The HR module's `HrInvitationService` adds employee-specific logic on top
4. Creating HR-specific duplicates of library pages would violate DRY and create maintenance burden

The HR route (`/hr/invitations`) should render the library's invitation view wrapped in the HR layout, not redirect to `/admin/invitations`. This keeps the user in the HR context while reusing the library's UI.

---

## 5. Implementation Notes

### 5.1 Files to Create

| File | Purpose |
|---|---|
| `app/Modules/Hr/Resources/views/invitations/index.blade.php` | HR-specific blade view wrapping the library's InvitationDataTable |
| (No new Livewire components needed) | The library's `InvitationDataTable` and `BulkInvite` are reused directly |

### 5.2 Files to Modify

| File | Change |
|---|---|
| [`app/Modules/Hr/Config/navigation.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Config/navigation.php:127) | Add `invitation` item to `people` context group (after `employee_group` at order 7) |
| [`app/Modules/Hr/Data/dashboards/people_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/people_overview.php) | Add "Pending Invitations" stat widget + "Send Invitation" action card |
| `app/Modules/Hr/Routes/web.php` | Add `GET /hr/invitations` route |

### 5.3 Library vs Consuming-App Boundaries

| Concern | Library (ui-library) | Consuming App (hr-consuming-app) |
|---|---|---|
| **Invitation model + CRUD** | ✅ `Invitation` model, `InvitationService`, `InvitationDataTable` | — |
| **Invitation form config** | ✅ `admin.invitation` DataTable config | — |
| **Admin routes + views** | ✅ `/admin/invitations` route, blade view | — |
| **HR route** | — | ✅ `/hr/invitations` route |
| **HR blade view** | — | ✅ Wraps library DataTable in HR layout |
| **HR navigation config** | — | ✅ Sidebar link in People group |
| **HR dashboard config** | — | ✅ Stat + action card widgets |
| **Employee-specific logic** | — | ✅ `HrInvitationService` (already exists) |

**No library changes are required.** All changes are in the consuming app's HR module.

### 5.4 HR Blade View Structure

The HR invitations view should mirror the admin view at [`invitations.blade.php`](/Users/mac/Projects/Libraries/ui-library/src/Core/Admin/Resources/views/admin/invitations.blade.php:8) but use the HR layout:

```blade
{{-- app/Modules/Hr/Resources/views/invitations/index.blade.php --}}
<x-qf::navigation-layout configKey="admin.invitation" context="people" moduleName="hr" :overrides="[]">
    <div class="d-flex justify-content-end mb-3">
        <livewire:qf.bulk-invite />
    </div>
    <livewire:qf.invitation-data-table configKey="admin.invitation" />
</x-qf::navigation-layout>
```

This reuses:
- The library's `navigation-layout` component (with HR context)
- The library's `InvitationDataTable` Livewire component
- The library's `BulkInvite` Livewire component
- The library's `admin.invitation` DataTable config

### 5.5 Permission Considerations

- The sidebar link uses `view_invitation` (already defined by the library)
- The dashboard widgets should be gated by the same permission
- HR admins who currently have `view_invitation` (to access `/admin/invitations`) will automatically see the new HR entry points
- No new permissions or roles need to be created

### 5.6 Dashboard Widget Placement

In [`people_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/people_overview.php), the new widgets should be placed:

1. **"Pending Invitations" stat** — after the existing stat row (after "Profile Completion" at index 3), as a new stat in the same row. This keeps all stats together.
2. **"Send Invitation" action card** — replace or sit alongside the existing "Process Payroll" action card (index 7). "Process Payroll" is arguably misplaced on a People dashboard and could be moved to the main HR dashboard.

---

## Summary

| Suggestion | Verdict | Effort | Impact |
|---|---|---|---|
| **S1: Sidebar link in People group** | ✅ Implement | Low — 1 config entry + 1 route + 1 blade view | High — primary navigation discovery |
| **S2: Dashboard stat + action card** | ✅ Implement | Low — 2 widget config entries | High — dashboard-level discovery + situational awareness |
| **New "Onboarding" context group** | ❌ Defer | Medium | Low until onboarding features mature |

Both suggestions should be implemented together as **Phase 1** of HR invitation integration. They require no library changes, reuse existing components, and provide immediate value by giving HR admins contextual access to invitations from within the HR module.