# Dashboard Action Button Drawer Integration Analysis

> **Date:** 2026-08-19  
> **Scope:** Analysis of all dashboard action cards across UI Library and HR Consuming App for drawer-based add/edit potential  
> **Status:** Analysis complete — no code modified

---

## 1. How the Drawer Event System Works

### Architecture

The drawer system is a Livewire-powered slide-in panel (Bootstrap offcanvas) that hosts any Livewire component without leaving the current page.

| Component | File | Role |
|-----------|------|------|
| **Drawer Livewire Component** | [`src/Http/Livewire/Drawer.php`](src/Http/Livewire/Drawer.php) | Listens for `openDrawer`/`closeDrawer` events, manages state |
| **Drawer Blade View** | [`src/Resources/views/livewire/drawer.blade.php`](src/Resources/views/livewire/drawer.blade.php) | Bootstrap offcanvas rendering the hosted component |
| **Navigation Layout JS** | [`src/Resources/views/components/layouts/navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php:307) | Listens for `drawerOpened`/`drawerClosed` to show/hide the Bootstrap offcanvas |

### Event Flow

```mermaid
sequenceDiagram
    participant Button as Action Card Button
    participant LW as Livewire
    participant Drawer as Drawer.php
    participant JS as navigation-layout JS
    participant DOM as Bootstrap Offcanvas

    Button->>LW: wire:click="$dispatch('openDrawer', {component, params, title})"
    LW->>Drawer: openDrawer event received
    Drawer->>Drawer: Set component, params, title; isOpen = true
    Drawer->>LW: $this->dispatch('drawerOpened')
    LW->>JS: drawerOpened event
    JS->>DOM: bsDrawer.show()
    DOM-->>Drawer: Renders @livewire($component, $componentParams)
```

### Key Parameters for `openDrawer`

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `component` | string | Livewire component alias | `'qf.data-table-form'` |
| `params` | array | Component parameters | `['configKey' => 'hr.company', 'recordId' => null]` |
| `title` | string | Drawer header title (optional) | `'Add Company'` |

### How Data Tables Use Drawers

Data table configs specify `crudType` to control how add/edit forms open:

- **`'drawers'`** — Row action buttons dispatch `openDrawer` with `qf.data-table-form` or `qf.data-table-detail`
- **`'modals'`** — Uses Bootstrap modals (default)
- **`'pages'`** — Full page navigation with `wire:navigate`

The row-actions template ([`src/Resources/views/livewire/data-tables/partials/row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php:33)) dispatches:

```blade
{{-- View/Detail --}}
wire:click="$dispatch('openDrawer', {
    component: 'qf.data-table-detail',
    params: { configKey: '{{ $configKey }}', recordId: '{{ $record->id }}' }
})"

{{-- Edit --}}
wire:click="$dispatch('openDrawer', {
    component: 'qf.data-table-form',
    params: { configKey: '{{ $configKey }}', recordId: '{{ $record->id }}' }
})"
```

The page-header create button ([`src/Resources/views/components/layouts/partials/page-header.blade.php`](src/Resources/views/components/layouts/partials/page-header.blade.php:76)) also uses the same pattern for `crudType === 'drawers'`.

### How Action Cards Currently Work

The action card Blade view ([`src/Resources/views/widgets/action_card.blade.php`](src/Resources/views/widgets/action_card.blade.php:25)) renders buttons as:

```blade
wire:click="{{ $action['event'] }}({{ json_encode($action['params'] ?? []) }})"
```

Current action card configs use these event types:
- **`navigate`** + `{ url: '/path' }` — Full page navigation (most common)
- **`openXxxWizard`** / **`openXxxModal`** — Custom wizard/modal events
- **`syncPermissions`** — Custom action events

---

## 2. Complete Inventory of Dashboard Action Cards

### Legend

| Column | Meaning |
|--------|---------|
| **Event** | Current event type (`navigate`, custom wizard, etc.) |
| **Target Entity** | What entity the action creates/edits |
| **Entity crudType** | The `crudType` setting in the entity's data table config |
| **Drawer Candidate** | Whether this action card is a good candidate for drawer conversion |

### 2.1 UI Library — Admin Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 1 | [`src/Core/Admin/Data/dashboard.php:204`](src/Core/Admin/Data/dashboard.php:204) | Invite User | `openInviteUserModal` | Custom modal | User | `drawers` | ❌ Custom modal |
| 2 | [`src/Core/Admin/Data/dashboard.php:227`](src/Core/Admin/Data/dashboard.php:227) | Add Role | `openRoleWizard` | Custom wizard | Role | `modals` | ❌ Custom wizard |
| 3 | [`src/Core/Admin/Data/dashboard.php:249`](src/Core/Admin/Data/dashboard.php:249) | Add Permission | `openPermissionWizard` | Custom wizard | Permission | `modal` | ❌ Custom wizard |
| 4 | [`src/Core/Admin/Data/dashboard.php:271`](src/Core/Admin/Data/dashboard.php:271) | Sync Permissions | `syncPermissions` | Custom action | Permission | `modal` | ❌ Custom action |
| 5 | [`src/Core/Admin/Data/dashboards/dashboard_users_overview.php:168`](src/Core/Admin/Data/dashboards/dashboard_users_overview.php:168) | Add New User | `navigate` | `/admin/users` | User | `drawers` | ✅ **Easy Win** |
| 6 | [`src/Core/Admin/Data/dashboards/dashboard_users_overview.php:190`](src/Core/Admin/Data/dashboards/dashboard_users_overview.php:190) | Manage Roles | `navigate` | `/admin/roles` | Role | `modals` | ⚠️ Possible |
| 7 | [`src/Core/Admin/Data/dashboards/dashboard_access_overview.php:100`](src/Core/Admin/Data/dashboards/dashboard_access_overview.php:100) | Manage Roles | `navigate` | `/admin/roles` | Role | `modals` | ⚠️ Possible |
| 8 | [`src/Core/Admin/Data/dashboards/dashboard_access_overview.php:122`](src/Core/Admin/Data/dashboards/dashboard_access_overview.php:122) | Access Control | `navigate` | `/admin/access-control-management` | Access Control | N/A | ❌ Complex page |
| 9 | [`src/Core/Admin/Data/dashboards/dashboard_workflows_overview.php:132`](src/Core/Admin/Data/dashboards/dashboard_workflows_overview.php:132) | New Workflow | `navigate` | `/admin/workflow-definition-wizard` | Workflow Definition | N/A | ❌ Multi-step wizard |
| 10 | [`src/Core/Admin/Data/dashboards/dashboard_workflows_overview.php:154`](src/Core/Admin/Data/dashboards/dashboard_workflows_overview.php:154) | Workflow Definitions | `navigate` | `/admin/workflow-definitions` | Workflow Definition | N/A | ❌ List page |
| 11 | [`src/Core/Admin/Data/dashboards/dashboard_notifications_overview.php:144`](src/Core/Admin/Data/dashboards/dashboard_notifications_overview.php:144) | Notification Logs | `navigate` | `/admin/notification-logs` | Notification Log | N/A | ❌ List page |
| 12 | [`src/Core/Admin/Data/dashboards/dashboard_notifications_overview.php:166`](src/Core/Admin/Data/dashboards/dashboard_notifications_overview.php:166) | Preferences | `navigate` | `/admin/notification-preferences` | Notification Preference | N/A | ❌ Settings page |

### 2.2 UI Library — System Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 13 | [`src/Core/System/Data/dashboard.php:49`](src/Core/System/Data/dashboard.php:49) | Setup Wizard | `openSetupWizard` | Custom wizard | Setup | N/A | ❌ Custom wizard |
| 14 | [`src/Core/System/Data/dashboard.php:71`](src/Core/System/Data/dashboard.php:71) | General Settings | `navigate` | `/system/settings` | System Setting | N/A | ❌ Settings page |
| 15 | [`src/Core/System/Data/dashboards/dashboard_settings_overview.php:49`](src/Core/System/Data/dashboards/dashboard_settings_overview.php:49) | General Settings | `navigate` | `/system/settings` | System Setting | N/A | ❌ Settings page |
| 16 | [`src/Core/System/Data/dashboards/dashboard_settings_overview.php:71`](src/Core/System/Data/dashboards/dashboard_settings_overview.php:71) | Setup Wizard | `openSetupWizard` | Custom wizard | Setup | N/A | ❌ Custom wizard |
| 17 | [`src/Core/System/Data/dashboards/dashboard_settings_overview.php:93`](src/Core/System/Data/dashboards/dashboard_settings_overview.php:93) | Onboarding | `navigate` | `/admin/onboarding` | Onboarding | N/A | ❌ Settings page |
| 18 | [`src/Core/System/Data/dashboards/dashboard_setup_overview.php:68`](src/Core/System/Data/dashboards/dashboard_setup_overview.php:68) | Setup Wizard | `openSetupWizard` | Custom wizard | Setup | N/A | ❌ Custom wizard |
| 19 | [`src/Core/System/Data/dashboards/dashboard_setup_overview.php:90`](src/Core/System/Data/dashboards/dashboard_setup_overview.php:90) | Onboarding | `navigate` | `/admin/onboarding` | Onboarding | N/A | ❌ Settings page |
| 20 | [`src/Core/System/Data/dashboards/dashboard_setup_overview.php:112`](src/Core/System/Data/dashboards/dashboard_setup_overview.php:112) | Guided Tours | `navigate` | `/admin/tours` | Tour | N/A | ❌ List page |

### 2.3 HR Consuming App — HR Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 21 | [`app/Modules/Hr/Data/dashboards/dashboard.php:360`](app/Modules/Hr/Data/dashboards/dashboard.php:360) | Request Leave | `openLeaveWizard` | Custom wizard | Leave Request | `drawers` | ❌ Custom wizard |
| 22 | [`app/Modules/Hr/Data/dashboards/dashboard.php:382`](app/Modules/Hr/Data/dashboards/dashboard.php:382) | Process Payroll | `openPayrollWizard` | Custom wizard | Payroll Run | `pages` | ❌ Custom wizard |
| 23 | [`app/Modules/Hr/Data/dashboards/dashboard.php:404`](app/Modules/Hr/Data/dashboards/dashboard.php:404) | Clock In/Out | `openClockModal` | Custom modal | Clock Event | `modals` | ❌ Custom modal |
| 24 | [`app/Modules/Hr/Data/dashboards/dashboard.php:426`](app/Modules/Hr/Data/dashboards/dashboard.php:426) | View My Team | `navigate` | `/hr/team-calendar` | Team Calendar | N/A | ❌ Calendar page |
| 25 | [`app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:182`](app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:182) | Add New Location | `openLocationWizard` | Custom wizard | Location | `pages` | ❌ Custom wizard |
| 26 | [`app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:204`](app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:204) | Add Department | `openDepartmentWizard` | Custom wizard | Department | `drawers` | ❌ Custom wizard |
| 27 | [`app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:226`](app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:226) | Manage Companies | `navigate` | `/hr/companies` | Company | `drawers` | ✅ **Easy Win** |
| 28 | [`app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:248`](app/Modules/Hr/Data/dashboards/dashboard_organization_overview.php:248) | Job Titles | `navigate` | `/hr/job-titles` | Job Title | `drawers` | ✅ **Easy Win** |
| 29 | [`app/Modules/Hr/Data/dashboards/people_overview.php:133`](app/Modules/Hr/Data/dashboards/people_overview.php:133) | Process Payroll | `openPayrollWizard` | Custom wizard | Payroll Run | `pages` | ❌ Custom wizard |
| 30 | [`app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php:168`](app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php:168) | Job Titles | `navigate` | `/hr/job-titles` | Job Title | `drawers` | ✅ **Easy Win** |
| 31 | [`app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php:186`](app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php:186) | Tags | `navigate` | `/hr/tags` | Tag | `drawers` | ✅ **Easy Win** |
| 32 | [`app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php:204`](app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php:204) | Job History | `navigate` | `/hr/employee-job-histories` | Employee Job History | `pages` | ⚠️ Possible |
| 33 | [`app/Modules/Hr/Data/dashboards/dashboard_employee_overview.php:213`](app/Modules/Hr/Data/dashboards/dashboard_employee_overview.php:213) | Quick Contact | (needs review) | — | — | — | ⚠️ Needs review |

### 2.4 HR Consuming App — Leave Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 34 | [`app/Modules/Leave/Data/dashboards/dashboard.php:251`](app/Modules/Leave/Data/dashboards/dashboard.php:251) | Request Leave | `navigate` | `/leave/leave-requests/create` | Leave Request | `drawers` | ✅ **Easy Win** |
| 35 | [`app/Modules/Leave/Data/dashboards/dashboard.php:273`](app/Modules/Leave/Data/dashboards/dashboard.php:273) | Add Leave Type | `navigate` | `/leave/leave-types/create` | Leave Type | `drawers` | ✅ **Easy Win** |
| 36 | [`app/Modules/Leave/Data/dashboards/dashboard.php:295`](app/Modules/Leave/Data/dashboards/dashboard.php:295) | Configure Approvers | `navigate` | `/leave/leave-approvers/create` | Leave Approver | `modals` | ⚠️ Possible |
| 37 | [`app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:243`](app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:243) | Request Leave | (needs review) | — | Leave Request | `drawers` | ✅ **Easy Win** |
| 38 | [`app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:265`](app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:265) | My Leave Balance | (needs review) | — | Leave Balance | `modals` | ❌ View-only |
| 39 | [`app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:287`](app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:287) | Manage Leave Types | (needs review) | — | Leave Type | `drawers` | ✅ **Easy Win** |
| 40 | [`app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:309`](app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php:309) | Approval Rules | (needs review) | — | Leave Approver | `modals` | ⚠️ Possible |
| 41 | [`app/Modules/Leave/Data/dashboards/dashboard_requests_overview.php:147`](app/Modules/Leave/Data/dashboards/dashboard_requests_overview.php:147) | Request Leave | (needs review) | — | Leave Request | `drawers` | ✅ **Easy Win** |
| 42 | [`app/Modules/Leave/Data/dashboards/dashboard_requests_overview.php:163`](app/Modules/Leave/Data/dashboards/dashboard_requests_overview.php:163) | View Balances | (needs review) | — | Leave Balance | `modals` | ❌ View-only |
| 43 | [`app/Modules/Leave/Data/dashboards/dashboard_configuration_overview.php:94`](app/Modules/Leave/Data/dashboards/dashboard_configuration_overview.php:94) | Add Leave Type | (needs review) | — | Leave Type | `drawers` | ✅ **Easy Win** |
| 44 | [`app/Modules/Leave/Data/dashboards/dashboard_configuration_overview.php:110`](app/Modules/Leave/Data/dashboards/dashboard_configuration_overview.php:110) | Configure Approvers | (needs review) | — | Leave Approver | `modals` | ⚠️ Possible |

### 2.5 HR Consuming App — Payroll Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 45 | [`app/Modules/Payroll/Data/dashboards/dashboard.php:347`](app/Modules/Payroll/Data/dashboards/dashboard.php:347) | Run Payroll | `openPayrollWizard` | Custom wizard | Payroll Run | `pages` | ❌ Custom wizard |
| 46 | [`app/Modules/Payroll/Data/dashboards/dashboard.php:369`](app/Modules/Payroll/Data/dashboards/dashboard.php:369) | Create Pay Schedule | `navigate` | `/pay-schedules/create` | Pay Schedule | `pages` | ⚠️ Possible |
| 47 | [`app/Modules/Payroll/Data/dashboards/dashboard.php:391`](app/Modules/Payroll/Data/dashboards/dashboard.php:391) | Add Payroll Policy | `navigate` | `/payroll-policies/create` | Payroll Policy | `pages` | ⚠️ Possible |
| 48 | [`app/Modules/Payroll/Data/dashboards/dashboard.php:413`](app/Modules/Payroll/Data/dashboards/dashboard.php:413) | Export Bank File | `openBankFileExport` | Custom event | Bank File | N/A | ❌ Custom export |
| 49 | [`app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:222`](app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:222) | Start New Pay Run | (needs review) | — | Payroll Run | `pages` | ❌ Complex workflow |
| 50 | [`app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:244`](app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:244) | Run Payroll Report | (needs review) | — | Report | N/A | ❌ Report |
| 51 | [`app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:266`](app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:266) | Bulk Import Profiles | (needs review) | — | Payroll Profile | `pages` | ❌ Bulk import |
| 52 | [`app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:288`](app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php:288) | Export Bank File | (needs review) | — | Bank File | N/A | ❌ Custom export |
| 53 | [`app/Modules/Payroll/Data/dashboards/dashboard_processing_overview.php:121`](app/Modules/Payroll/Data/dashboards/dashboard_processing_overview.php:121) | Start New Pay Run | (needs review) | — | Payroll Run | `pages` | ❌ Complex workflow |
| 54 | [`app/Modules/Payroll/Data/dashboards/dashboard_processing_overview.php:137`](app/Modules/Payroll/Data/dashboards/dashboard_processing_overview.php:137) | Run Payroll Report | (needs review) | — | Report | N/A | ❌ Report |
| 55 | [`app/Modules/Payroll/Data/dashboards/dashboard_processing_overview.php:153`](app/Modules/Payroll/Data/dashboards/dashboard_processing_overview.php:153) | Export Bank File | (needs review) | — | Bank File | N/A | ❌ Custom export |
| 56 | [`app/Modules/Payroll/Data/dashboards/dashboard_configuration_overview.php:110`](app/Modules/Payroll/Data/dashboards/dashboard_configuration_overview.php:110) | Bulk Import Profiles | (needs review) | — | Payroll Profile | `pages` | ❌ Bulk import |

### 2.6 HR Consuming App — Holiday Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 57 | [`app/Modules/Holiday/Data/dashboards/dashboard.php:223`](app/Modules/Holiday/Data/dashboards/dashboard.php:223) | Add Holiday | `navigate` | `/holiday/holidays/create` | Holiday | `pages` | ⚠️ Possible |
| 58 | [`app/Modules/Holiday/Data/dashboards/dashboard.php:245`](app/Modules/Holiday/Data/dashboards/dashboard.php:245) | Create Calendar | `navigate` | `/holiday/holiday-calendars/create` | Holiday Calendar | `drawers` | ✅ **Easy Win** |
| 59 | [`app/Modules/Holiday/Data/dashboards/dashboard.php:267`](app/Modules/Holiday/Data/dashboards/dashboard.php:267) | Batch Create Holidays | `navigate` | `/holiday/holiday-batch-creation` | Holiday | `pages` | ❌ Batch operation |
| 60 | [`app/Modules/Holiday/Data/dashboards/dashboard_holidays_overview.php:202`](app/Modules/Holiday/Data/dashboards/dashboard_holidays_overview.php:202) | Add Holiday | (needs review) | — | Holiday | `pages` | ⚠️ Possible |
| 61 | [`app/Modules/Holiday/Data/dashboards/dashboard_holidays_overview.php:224`](app/Modules/Holiday/Data/dashboards/dashboard_holidays_overview.php:224) | Batch Create | (needs review) | — | Holiday | `pages` | ❌ Batch operation |
| 62 | [`app/Modules/Holiday/Data/dashboards/dashboard_holidays_overview.php:246`](app/Modules/Holiday/Data/dashboards/dashboard_holidays_overview.php:246) | Manage Calendars | (needs review) | — | Holiday Calendar | `drawers` | ✅ **Easy Win** |

### 2.7 HR Consuming App — Attendance Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 63 | [`app/Modules/Attendance/Data/dashboards/dashboard.php:340`](app/Modules/Attendance/Data/dashboards/dashboard.php:340) | Clock In | `openClockModal` | Custom modal | Clock Event | `modals` | ❌ Custom modal |
| 64 | [`app/Modules/Attendance/Data/dashboards/dashboard.php:372`](app/Modules/Attendance/Data/dashboards/dashboard.php:372) | Record Attendance | `navigate` | `/attendance/attendances/create` | Attendance | `pages` | ⚠️ Possible |
| 65 | [`app/Modules/Attendance/Data/dashboards/dashboard.php:394`](app/Modules/Attendance/Data/dashboards/dashboard.php:394) | Create Shift | `navigate` | `/attendance/shifts` | Shift | `drawers` | ✅ **Easy Win** |
| 66 | [`app/Modules/Attendance/Data/dashboards/dashboard.php:416`](app/Modules/Attendance/Data/dashboards/dashboard.php:416) | Add Work Pattern | `navigate` | `/attendance/work-patterns` | Work Pattern | `pages` | ⚠️ Possible |
| 67 | [`app/Modules/Attendance/Data/dashboards/dashboard_time_overview.php:151`](app/Modules/Attendance/Data/dashboards/dashboard_time_overview.php:151) | Quick Actions | (needs review) | — | — | — | ⚠️ Needs review |
| 68 | [`app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:197`](app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:197) | Create New Policy | (needs review) | — | Attendance Policy | `pages` | ⚠️ Possible |
| 69 | [`app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:219`](app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:219) | Manage Assignments | (needs review) | — | Policy Assignment | `modals` | ⚠️ Possible |
| 70 | [`app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:241`](app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:241) | Create Work Pattern | (needs review) | — | Work Pattern | `pages` | ⚠️ Possible |
| 71 | [`app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:263`](app/Modules/Attendance/Data/dashboards/dashboard_policies_overview.php:263) | Bulk Assign Patterns | (needs review) | — | Employee Work Pattern | `modals` | ❌ Bulk operation |
| 72 | [`app/Modules/Attendance/Data/dashboards/dashboard_scheduling_overview.php:209`](app/Modules/Attendance/Data/dashboards/dashboard_scheduling_overview.php:209) | Create Shift | (needs review) | — | Shift | `drawers` | ✅ **Easy Win** |
| 73 | [`app/Modules/Attendance/Data/dashboards/dashboard_scheduling_overview.php:231`](app/Modules/Attendance/Data/dashboards/dashboard_scheduling_overview.php:231) | Add Work Pattern | (needs review) | — | Work Pattern | `pages` | ⚠️ Possible |

### 2.8 HR Consuming App — Organization Module

| # | Dashboard Config | Action Card Label | Event | URL / Target | Entity | Entity crudType | Drawer Candidate |
|---|-----------------|-------------------|-------|-------------|--------|-----------------|-----------------|
| 74 | [`app/Modules/Organization/Data/dashboard.php:529`](app/Modules/Organization/Data/dashboard.php:529) | Add Company | `navigate` | `/organization/companies` | Company | `drawers` | ✅ **Easy Win** |
| 75 | [`app/Modules/Organization/Data/dashboard.php:551`](app/Modules/Organization/Data/dashboard.php:551) | Manage Structure | `navigate` | `/organization/departments` | Department | `drawers` | ✅ **Easy Win** |
| 76 | [`app/Modules/Organization/Data/dashboard.php:573`](app/Modules/Organization/Data/dashboard.php:573) | Add Location | `navigate` | `/organization/locations` | Location | `pages` | ⚠️ Possible |
| 77 | [`app/Modules/Organization/Data/dashboard.php:595`](app/Modules/Organization/Data/dashboard.php:595) | Add Team | `navigate` | `/organization/teams` | Team | `drawers` | ✅ **Easy Win** |
| 78 | [`app/Modules/Organization/Data/dashboard.php:617`](app/Modules/Organization/Data/dashboard.php:617) | Add Branch | `navigate` | `/organization/branches` | Branch | (not found) | ⚠️ Possible |
| 79 | [`app/Modules/Organization/Data/dashboard.php:639`](app/Modules/Organization/Data/dashboard.php:639) | Add Division | `navigate` | `/organization/divisions` | Division | (not found) | ⚠️ Possible |
| 80 | [`app/Modules/Organization/Data/dashboards/dashboard_companies_overview.php:168`](app/Modules/Organization/Data/dashboards/dashboard_companies_overview.php:168) | Add Company | `navigate` | `/organization/companies` | Company | `drawers` | ✅ **Easy Win** |
| 81 | [`app/Modules/Organization/Data/dashboards/dashboard_companies_overview.php:190`](app/Modules/Organization/Data/dashboards/dashboard_companies_overview.php:190) | Add Branch | `navigate` | (needs review) | Branch | (not found) | ⚠️ Possible |
| 82 | [`app/Modules/Organization/Data/dashboards/dashboard_structure_overview.php:148`](app/Modules/Organization/Data/dashboards/dashboard_structure_overview.php:148) | Add Department | (needs review) | — | Department | `drawers` | ✅ **Easy Win** |
| 83 | [`app/Modules/Organization/Data/dashboards/dashboard_structure_overview.php:170`](app/Modules/Organization/Data/dashboards/dashboard_structure_overview.php:170) | Add Division | (needs review) | — | Division | (not found) | ⚠️ Possible |
| 84 | [`app/Modules/Organization/Data/dashboards/dashboard_locations_overview.php:172`](app/Modules/Organization/Data/dashboards/dashboard_locations_overview.php:172) | Add Location | (needs review) | — | Location | `pages` | ⚠️ Possible |
| 85 | [`app/Modules/Organization/Data/dashboards/dashboard_locations_overview.php:194`](app/Modules/Organization/Data/dashboards/dashboard_locations_overview.php:194) | Add Team | (needs review) | — | Team | `drawers` | ✅ **Easy Win** |
| 86 | [`app/Modules/Organization/Data/dashboards/dashboard_teams_overview.php:114`](app/Modules/Organization/Data/dashboards/dashboard_teams_overview.php:114) | Add Team | (needs review) | — | Team | `drawers` | ✅ **Easy Win** |
| 87 | [`app/Modules/Organization/Data/dashboards/dashboard_teams_overview.php:136`](app/Modules/Organization/Data/dashboards/dashboard_teams_overview.php:136) | Manage Departments | (needs review) | — | Department | `drawers` | ✅ **Easy Win** |
| 88 | [`app/Modules/Organization/Data/dashboards/dashboard_classification_overview.php:144`](app/Modules/Organization/Data/dashboards/dashboard_classification_overview.php:144) | Add Company | (needs review) | — | Company | `drawers` | ✅ **Easy Win** |
| 89 | [`app/Modules/Organization/Data/dashboards/dashboard_classification_overview.php:166`](app/Modules/Organization/Data/dashboards/dashboard_classification_overview.php:166) | Add Business Unit | (needs review) | — | Business Unit | (not found) | ⚠️ Possible |
| 90 | [`app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:40`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:40) | Company Reports | (needs review) | — | Report | N/A | ❌ Report |
| 91 | [`app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:62`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:62) | Department Reports | (needs review) | — | Report | N/A | ❌ Report |
| 92 | [`app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:84`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:84) | Location Reports | (needs review) | — | Report | N/A | ❌ Report |
| 93 | [`app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:106`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php:106) | Growth Reports | (needs review) | — | Report | N/A | ❌ Report |

---

## 3. Summary Statistics

| Category | Count |
|----------|-------|
| **Total action cards found** | **93** |
| **Easy Wins** (entity has `crudType: 'drawers'`) | **22** (all converted in Task R) |
| **Possible** (entity has `crudType: 'pages'` or `'modals'`) | **22** (21 converted in Task AL, 2 excluded as custom wizards) |
| **Not suitable** (custom wizards, modals, reports, batch ops, settings) | **44** |
| **Needs review** (overview dashboards not fully read) | **7** |

> **Task AL update (2026-08-20):** 21 of the 22 "Possible" action cards were converted from `navigate` + `url` to `openDrawer` events, and 13 data table configs were changed from `pages`/`modals` to `drawers` crudType. 2 cards were excluded (custom wizards). ⚠️ Some converted cards still do not open the drawer — likely causes: missing `crudType` on the data table config, incorrect `configKey`, or the entity's form doesn't support drawer rendering. Needs investigation per card (tracked in [`navigation-ux-backlog.md`](navigation-ux-backlog.md)).

---

## 4. Prioritized Candidates for Drawer Conversion

### 4.1 Tier 1 — Easy Wins (Entity already uses `crudType: 'drawers'`)

These entities already have drawer-based data table forms. Converting the dashboard action card is a simple config change — just swap the event from `navigate` to `openDrawer`.

| Priority | Action Card | Dashboard Config | Entity | Config Key | Current Event | New Event |
|----------|------------|-----------------|--------|------------|---------------|-----------|
| 🔴 P1 | Add New User | `admin.dashboards.dashboard_users_overview` | User | `admin.user` | `navigate` → `/admin/users` | `openDrawer` |
| 🔴 P1 | Add Company | `organization.dashboard` | Company | `hr.company` | `navigate` → `/organization/companies` | `openDrawer` |
| 🔴 P1 | Add Company | `organization.dashboards.dashboard_companies_overview` | Company | `hr.company` | `navigate` | `openDrawer` |
| 🔴 P1 | Add Company | `organization.dashboards.dashboard_classification_overview` | Company | `hr.company` | (needs review) | `openDrawer` |
| 🔴 P1 | Manage Structure | `organization.dashboard` | Department | `hr.department` | `navigate` → `/organization/departments` | `openDrawer` |
| 🔴 P1 | Add Department | `organization.dashboards.dashboard_structure_overview` | Department | `hr.department` | (needs review) | `openDrawer` |
| 🔴 P1 | Manage Departments | `organization.dashboards.dashboard_teams_overview` | Department | `hr.department` | (needs review) | `openDrawer` |
| 🔴 P1 | Add Team | `organization.dashboard` | Team | `hr.team` | `navigate` → `/organization/teams` | `openDrawer` |
| 🔴 P1 | Add Team | `organization.dashboards.dashboard_locations_overview` | Team | `hr.team` | (needs review) | `openDrawer` |
| 🔴 P1 | Add Team | `organization.dashboards.dashboard_teams_overview` | Team | `hr.team` | (needs review) | `openDrawer` |
| 🟡 P2 | Manage Companies | `hr.dashboards.dashboard_organization_overview` | Company | `hr.company` | `navigate` → `/hr/companies` | `openDrawer` |
| 🟡 P2 | Job Titles | `hr.dashboards.dashboard_organization_overview` | Job Title | `hr.job_title` | `navigate` → `/hr/job-titles` | `openDrawer` |
| 🟡 P2 | Job Titles | `hr.dashboards.dashboard_manage_overview` | Job Title | `hr.job_title` | `navigate` → `/hr/job-titles` | `openDrawer` |
| 🟡 P2 | Tags | `hr.dashboards.dashboard_manage_overview` | Tag | `hr.tag` | `navigate` → `/hr/tags` | `openDrawer` |
| 🟡 P2 | Request Leave | `leave.dashboards.dashboard` | Leave Request | `leave.leave_request` | `navigate` → `/leave/leave-requests/create` | `openDrawer` |
| 🟡 P2 | Request Leave | `leave.dashboards.dashboard_leave_overview` | Leave Request | `leave.leave_request` | (needs review) | `openDrawer` |
| 🟡 P2 | Request Leave | `leave.dashboards.dashboard_requests_overview` | Leave Request | `leave.leave_request` | (needs review) | `openDrawer` |
| 🟡 P2 | Add Leave Type | `leave.dashboards.dashboard` | Leave Type | `leave.leave_type` | `navigate` → `/leave/leave-types/create` | `openDrawer` |
| 🟡 P2 | Add Leave Type | `leave.dashboards.dashboard_configuration_overview` | Leave Type | `leave.leave_type` | (needs review) | `openDrawer` |
| 🟡 P2 | Manage Leave Types | `leave.dashboards.dashboard_leave_overview` | Leave Type | `leave.leave_type` | (needs review) | `openDrawer` |
| 🟢 P3 | Create Calendar | `holiday.dashboards.dashboard` | Holiday Calendar | `holiday.holiday_calendar` | `navigate` → `/holiday/holiday-calendars/create` | `openDrawer` |
| 🟢 P3 | Manage Calendars | `holiday.dashboards.dashboard_holidays_overview` | Holiday Calendar | `holiday.holiday_calendar` | (needs review) | `openDrawer` |
| 🟢 P3 | Create Shift | `attendance.dashboards.dashboard` | Shift | `attendance.shift` | `navigate` → `/attendance/shifts` | `openDrawer` |
| 🟢 P3 | Create Shift | `attendance.dashboards.dashboard_scheduling_overview` | Shift | `attendance.shift` | (needs review) | `openDrawer` |

### 4.2 Tier 2 — Possible (Entity uses `crudType: 'pages'` or `'modals'`)

> **Task AL update (2026-08-20):** 21 of these 22 "Possible" cards were converted to `openDrawer` events, and 13 data table configs had their `crudType` changed from `pages`/`modals` to `drawers`. 2 cards were excluded (custom wizards). ⚠️ Some converted cards still do not open the drawer — see [`navigation-ux-backlog.md`](navigation-ux-backlog.md) for the investigation backlog item.

These would require either changing the entity's `crudType` to `'drawers'` (which may need form layout adjustments) or adapting the drawer to host page-based forms.

| Priority | Action Card | Entity | Current crudType | Notes |
|----------|------------|--------|-----------------|-------|
| 🟢 P3 | Manage Roles | Role | `modals` | Modal → drawer is straightforward |
| 🟢 P3 | Configure Approvers | Leave Approver | `modals` | Modal → drawer is straightforward |
| 🟢 P3 | Add Location | Location | `pages` | Page form may need layout adjustments for drawer width |
| 🟢 P3 | Add Holiday | Holiday | `pages` | Page form may need layout adjustments |
| 🟢 P3 | Record Attendance | Attendance | `pages` | Page form may need layout adjustments |
| 🟢 P3 | Add Work Pattern | Work Pattern | `pages` | Page form may need layout adjustments |
| 🟢 P3 | Create Pay Schedule | Pay Schedule | `pages` | Page form may need layout adjustments |
| 🟢 P3 | Add Payroll Policy | Payroll Policy | `pages` | Page form may need layout adjustments |
| 🟢 P3 | Add Branch | Branch | (not found) | Would need data config created |
| 🟢 P3 | Add Division | Division | (not found) | Would need data config created |

---

## 5. Implementation Approach

### 5.1 For Easy Wins (Tier 1)

The change is minimal — modify the action card config in the dashboard PHP file:

**Before (current):**
```php
'actions' => array (
    0 => array (
        'label' => 'Create',
        'event' => 'navigate',
        'params' => array (
            'url' => '/organization/companies',
        ),
        'style' => 'primary',
    ),
),
```

**After (drawer):**
```php
'actions' => array (
    0 => array (
        'label' => 'Create',
        'event' => 'openDrawer',
        'params' => array (
            'component' => 'qf.data-table-form',
            'params' => array (
                'configKey' => 'hr.company',
                'recordId' => null,
            ),
            'title' => 'Add Company',
        ),
        'style' => 'primary',
    ),
),
```

**Key parameters:**
- `component`: Always `'qf.data-table-form'` for add/edit forms
- `params.configKey`: The entity's data config key (e.g., `'hr.company'`, `'leave.leave_type'`)
- `params.recordId`: `null` for new records, or a specific ID for editing
- `title`: Human-readable drawer title

### 5.2 For Possible Candidates (Tier 2)

Two approaches:

**Approach A — Change entity crudType to `'drawers'`:**
1. Change `'crudType' => 'pages'` to `'crudType' => 'drawers'` in the entity's data config
2. Verify form layout renders correctly in drawer width (drawer uses full width, pages use `col-lg-8`)
3. Update the dashboard action card config as in Tier 1

**Approach B — Keep page crudType, use drawer for dashboard only:**
1. The drawer can host `qf.data-table-form` regardless of the entity's `crudType`
2. The form will render with page-appropriate layout (wider columns)
3. This works but may look slightly different than native drawer forms

### 5.3 Event Dispatch Reference

The `action_card.blade.php` renders buttons as:
```blade
wire:click="{{ $action['event'] }}({{ json_encode($action['params'] ?? []) }})"
```

For `openDrawer`, Livewire will call:
```php
$this->dispatch('openDrawer', component: 'qf.data-table-form', params: ['configKey' => 'hr.company'], title: 'Add Company');
```

The `Drawer.php` component's `open()` method signature is:
```php
public function open(string $component, array $params = [], string $title = ''): void
```

**Important:** The `action_card.blade.php` passes params as a single JSON object to the Livewire `wire:click` handler. The `openDrawer` event expects named parameters (`component`, `params`, `title`). The JSON structure must match:

```json
{
    "component": "qf.data-table-form",
    "params": {"configKey": "hr.company", "recordId": null},
    "title": "Add Company"
}
```

### 5.4 Verification Checklist Per Candidate

For each converted action card:
1. [ ] Update the dashboard config PHP file
2. [ ] Clear config cache: `php artisan config:clear`
3. [ ] Verify the drawer opens on button click
4. [ ] Verify the form loads with correct fields
5. [ ] Test creating a new record via the drawer form
6. [ ] Verify the dashboard refreshes after save (the `formSaved` → `close` listener handles this)
7. [ ] Test on mobile viewport (drawer should be full-width on small screens)

---

## 6. Entities with `crudType: 'drawers'` (Reference)

For quick reference, these entities already have drawer-based data table forms:

| Module | Entity | Config Key | Data Config File |
|--------|--------|------------|-----------------|
| Admin (Library) | User | `admin.user` | [`src/Core/Admin/Data/user.php:136`](src/Core/Admin/Data/user.php:136) |
| HR | Company | `hr.company` | [`app/Modules/Hr/Data/company.php:228`](app/Modules/Hr/Data/company.php:228) |
| HR | Department | `hr.department` | [`app/Modules/Hr/Data/department.php:125`](app/Modules/Hr/Data/department.php:125) |
| HR | Job Title | `hr.job_title` | [`app/Modules/Hr/Data/job_title.php:75`](app/Modules/Hr/Data/job_title.php:75) |
| HR | Employee Group | `hr.employee_group` | [`app/Modules/Hr/Data/employee_group.php:130`](app/Modules/Hr/Data/employee_group.php:130) |
| HR | Tag | `hr.tag` | [`app/Modules/Hr/Data/tag.php:104`](app/Modules/Hr/Data/tag.php:104) |
| HR | Document | `hr.document` | [`app/Modules/Hr/Data/document.php:139`](app/Modules/Hr/Data/document.php:139) |
| HR | Team | `hr.team` | [`app/Modules/Hr/Data/team.php:115`](app/Modules/Hr/Data/team.php:115) |
| Leave | Leave Request | `leave.leave_request` | [`app/Modules/Leave/Data/leave_request.php:263`](app/Modules/Leave/Data/leave_request.php:263) |
| Leave | Leave Type | `leave.leave_type` | [`app/Modules/Leave/Data/leave_type.php:129`](app/Modules/Leave/Data/leave_type.php:129) |
| Holiday | Holiday Calendar | `holiday.holiday_calendar` | [`app/Modules/Holiday/Data/holiday_calendar.php:166`](app/Modules/Holiday/Data/holiday_calendar.php:166) |
| Payroll | Payroll Run Adjustment | `payroll.payroll_run_adjustment` | [`app/Modules/Payroll/Data/payroll_run_adjustment.php:191`](app/Modules/Payroll/Data/payroll_run_adjustment.php:191) |
| Payroll | Employee Adjustment Profile | `payroll.employee_adjustment_profile` | [`app/Modules/Payroll/Data/employee_adjustment_profile.php:182`](app/Modules/Payroll/Data/employee_adjustment_profile.php:182) |
| Attendance | Shift Schedule | `attendance.shift_schedule` | [`app/Modules/Attendance/Data/shift_schedule.php:303`](app/Modules/Attendance/Data/shift_schedule.php:303) |
| Attendance | Attendance Session | `attendance.attendance_session` | [`app/Modules/Attendance/Data/attendance_session.php:262`](app/Modules/Attendance/Data/attendance_session.php:262) |
| Attendance | Attendance Adjustment | `attendance.attendance_adjustment` | [`app/Modules/Attendance/Data/attendance_adjustment.php:158`](app/Modules/Attendance/Data/attendance_adjustment.php:158) |
| Attendance | Shift | `attendance.shift` | [`app/Modules/Attendance/Data/shift.php:182`](app/Modules/Attendance/Data/shift.php:182) |

---

## 7. Not Suitable for Drawer Conversion

These action cards should remain as-is:

| Reason | Count | Examples |
|--------|-------|---------|
| **Custom wizard/modal events** | 12 | `openSetupWizard`, `openLeaveWizard`, `openPayrollWizard`, `openClockModal`, `openRoleWizard`, `openPermissionWizard`, `openLocationWizard`, `openDepartmentWizard` |
| **Settings/configuration pages** | 6 | General Settings, Onboarding, Notification Preferences, Access Control |
| **List/navigation pages** | 5 | Workflow Definitions, Notification Logs, Guided Tours, View My Team |
| **Reports** | 5 | Company Reports, Department Reports, Payroll Report, etc. |
| **Batch/bulk operations** | 4 | Batch Create Holidays, Bulk Import Profiles, Bulk Assign Patterns |
| **Custom export actions** | 3 | Export Bank File |
| **View-only / read actions** | 2 | My Leave Balance, View Balances |
| **Complex multi-step workflows** | 4 | Run Payroll, Start New Pay Run, New Workflow |
| **Custom actions** | 2 | Sync Permissions |

---

## 8. Conclusion

- **93 total action cards** were identified across 8 modules (Admin, System, HR, Leave, Payroll, Holiday, Attendance, Organization)
- **22 are Easy Wins** — all converted to `openDrawer` events in Task R ✅
- **22 are Possible** — 21 converted to `openDrawer` events in Task AL (2 excluded as custom wizards); ⚠️ some still need debugging
- **44 are Not Suitable** — custom wizards, modals, reports, batch operations, or settings pages that don't benefit from drawer conversion
- **7 need further review** — overview dashboard configs that weren't fully read in this analysis

**Implementation status:** Tasks R + AL converted 44 action cards (22 Easy Wins + 21 Possible) to drawer-based interactions. The remaining work is: (a) investigate and fix non-working drawer cards from Task AL, and (b) review the 7 "Needs Review" overview dashboard configs for any missed drawer opportunities.