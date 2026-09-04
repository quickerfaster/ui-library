# Employee Self-Service (ESS) — Comprehensive Analysis

**Date:** 2026-09-02  
**Scope:** Full ESS implementation across UX/UI, Navigation, and Workflow  
**Benchmarked against:** BambooHR, Workday, SAP SuccessFactors

---

## 1. Current ESS Feature Map

| # | Feature | Status | Navigation Path | UI Pattern | Workflow |
|---|---------|--------|----------------|------------|----------|
| 1 | **My Portal Dashboard** | ✅ Complete | `hr/my-portal` (sidebar: My Portal → Overview) | Dashboard with widgets (profile header, stats, action cards, activity feed, quick actions, team who's out) | No |
| 2 | **Leave Hub** | ✅ Complete | Sidebar: My Portal → Leave (`/hr/leave-hub`) | Tabbed view: Overview (dashboard with leave balance + team who's out), My Leaves (filtered data table), Apply (wizard) | Yes — 2-step: Manager Review → HR Authorization |
| 4 | **Clock In/Out** | ✅ Complete | Inline on My Portal + action card drawer | Standalone Livewire toggle component with status display | No (direct recording) |
| 5 | **My Attendance** | ⚠️ Partial | Sidebar: My Portal → My Attendance (`/attendance/my-attendance`) | Filtered data table (scoped to employee) | No (view-only) |
| 6 | **My Payslips** | 🔀 Consolidated | → Profile Hub (`/hr/my-profile` → Payslips tab) | Consolidated into the Profile Hub as the "Payslips" tab. Old URL `/payroll/my-payslips` redirects to `/hr/my-profile`. | No (view-only) |
| 7 | **My Profile** | ✅ Complete | Sidebar: My Portal → My Profile (`/hr/my-profile`) | Tabbed hub: Overview (dashboard), Personal, Contact, Employment, History, Work Patterns, Payslips, Documents, Attendance (9 tabs). Consolidates former My Profile + My Account + My Preferences + My Payslips + My Documents into a single page. Uses [`EmployeeDetail`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Http/Livewire/EmployeeDetail.php:1) with `isSelfServiceMode` auto-detection. | No |
| 8 | **My Documents** | 🔀 Consolidated | → Profile Hub (`/hr/my-profile` → Documents tab) | Consolidated into the Profile Hub as the "Documents" tab. Old URL `/hr/my-documents` redirects to `/hr/my-profile`. | No |
| 9 | **Team Calendar** | ✅ Complete | Route exists: `/hr/team-calendar` | Dashboard with stat cards (people out today/this week/this month, pending requests), team who's out widget, upcoming leave list, currently out list — all model-driven from LeaveRequest | No |
| 10 | **Team Who's Out** | ✅ Complete | Widget on My Portal dashboard | Model-driven widget querying approved LeaveRequests scoped to current company, showing employee name, leave type, date range, and return date | No |
| 11 | **Sick Call Report** | ✅ Complete | Via Cmd+K palette or quick actions | 2-step wizard (Reason & Duration → Notification Prefs) | No (notification only) |
| 12 | **Notifications** | ✅ Complete | Bell icon in top bar | Filterable list (type, read/unread), mark read, navigate, inline actions | Yes (workflow notifications) |
| 13 | **Approvals (Inbox)** | ✅ Complete | Leave module: `/leave/approvals`; Payroll: `/payroll/approvals` | Pending/submitted queues, filterable by workflow key and status | Yes |
| 14 | **Quick Actions (Cmd+K)** | ✅ Complete | `Cmd+K` / `Ctrl+K` or search icon | Command palette with personalized ranking, favorites, keyboard shortcuts | No |
| 15 | **Company Directory** | ❌ Missing | — | — | — |
| 16 | **Org Chart** | ❌ Missing | — | — | — |
| 17 | **Benefits Enrollment** | ❌ Missing | — | — | — |
| 18 | **Learning / Development** | ❌ Missing | — | — | — |
| 19 | **Goals / Performance** | ❌ Missing | — | — | — |
| 20 | **Announcements / Feed** | ❌ Missing | — | — | — |
| 21 | **Onboarding Tasks** | ❌ Missing | — | — | — |
| 22 | **Personal Info Change Requests** | ❌ Missing | — | — | — |

---

## 2. Navigation & Information Architecture Analysis

### 2.1 How an Employee Navigates ESS Features

The ESS navigation has **two primary entry points** (consolidated as of 2026-09-03):

**Entry Point A — My Portal Sidebar (Task-Oriented):**
```
My Portal
├── Overview        → /hr/my-portal (dashboard hub)
├── My Profile      → /hr/my-profile (tabbed hub: Overview, Personal, Contact, Employment, History, Work Patterns, Payslips, Documents, Attendance)
├── Leave           → /hr/leave-hub (tabbed hub: Overview, My Leaves, Apply)
└── Team Calendar   → /hr/my-team-calendar (team absence dashboard)
```

**Entry Point B — My Portal Dashboard Action Cards (Task-Oriented):**
- "Request Leave" → opens wizard in drawer
- "Clock In / Out" → opens clock component in drawer
- "View Payslip" → navigates to `/payroll/my-payslips`
- "Update My Info" → navigates to `/hr/my-profile`

**Entry Point C — Cmd+K Command Palette (Action-Oriented):**
- Global search/command palette for all registered actions

**Entry Point D — Top Bar Notifications + Module Menus:**
- Bell icon → notification center
- Leave module sidebar (admin-oriented: Requests, Configuration)
- Payroll module sidebar (admin-oriented)

### 2.2 Task-Oriented vs. CRUD-Oriented

| Section | Orientation | Assessment |
|---------|-------------|------------|
| My Portal sidebar | **Task-oriented** ✅ | "My Leave", "My Attendance", "My Payslips" — employee-centric labels |
| My Portal dashboard | **Task-oriented** ✅ | Action cards for common tasks, stat cards for personal metrics |
| Leave module sidebar | **CRUD-oriented** ⚠️ | "Leave Requests", "Leave Balances", "Leave Types" — admin-centric |
| HR module (Organization/People/Manage) | **CRUD-oriented** ⚠️ | "Employees", "Departments", "Job Titles" — admin-centric |
| Cmd+K palette | **Action-oriented** ✅ | Search and execute any action |

**Verdict:** The ESS-specific navigation (My Portal) is well-designed and task-oriented. However, when an employee clicks "My Leave" they're dropped into the Leave module which has an admin-oriented sidebar. This creates a **context switch** where the employee sees admin navigation items they may not need.

### 2.3 Multiple Entry Points — Good or Confusing?

The system has **4+ ways** to access the same feature:

| Feature | Entry Points |
|---------|-------------|
| Leave Hub | 1. My Portal sidebar → Leave 2. My Portal action card 3. Cmd+K 4. Quick Actions widget |
| Clock In/Out | 1. My Portal inline component 2. My Portal action card drawer 3. Cmd+K |
| My Payslips | 1. My Portal sidebar 2. My Portal action card 3. Cmd+K |

**Assessment:** Multiple entry points are a **strength** when they serve different contexts (dashboard widget vs. sidebar navigation vs. keyboard shortcut). This mirrors how BambooHR and Workday provide multiple paths to the same action. ✅ **FIXED (2026-09-03):** The Leave Hub consolidation merges the former "My Leave" (`/leave/my-leave`) and "Request Leave" (`/hr/employee-self-service`) into a single tabbed view at `/hr/leave-hub` with Overview, My Leaves, and Apply tabs. Old URLs redirect to the new hub.

### 2.4 My Portal as a Hub

The My Portal dashboard serves as an effective hub:

- **Profile Header** (Row 1): Identity confirmation — photo, name, department, position, manager, hire date
- **Stat Cards** (Row 2): At-a-glance metrics — leave balance, hours this week, pending approvals, upcoming holidays
- **Action Cards** (Row 3): Primary tasks — request leave, clock in/out, view payslip, update info
- **Activity + Quick Actions** (Row 4): Recent activity feed + top 5 quick actions
- **Team Who's Out** (Row 5): Team awareness

This layout mirrors **Workday's "My Worklet"** and **BambooHR's "Home"** dashboard pattern. The information hierarchy is logical and scannable.

---

## 3. Strengths

### 3.1 Architectural Strengths (Ahead of or On Par with Industry)

1. **Config-Driven Everything** — Navigation, dashboards, workflows, wizards, and field definitions are all declarative PHP config arrays. This is more maintainable than BambooHR's hard-coded UI and rivals SAP SuccessFactors' metadata-driven approach.

2. **Contract-Based Architecture** — `ClockEventRecorder`, `ApproverResolver`, `ApproverLabelResolver`, `Notifiable` are all interfaces that consuming apps implement. This is a **clean architecture pattern** that Workday and SAP SF achieve only through extensive middleware.

3. **Generic Workflow Engine** — The `WorkflowEngine` + `ApprovalGuard` + `WorkflowDefinition` system is domain-agnostic. Leave requests and payroll runs use the same engine. This is architecturally superior to BambooHR's siloed approval logic.

4. **Wizard Framework** — Multi-step wizards with draft save, balance callbacks, approval path preview, custom validation, and dynamic field loading. This rivals Workday's guided processes.

5. **Cmd+K Command Palette** — Personalized ranking (recency + frequency), favorites/pinning, keyboard shortcuts (⌘1–⌘9). This is a **modern UX pattern** that none of the big three HR SaaS platforms offer natively.

6. **Calendar Enhancement Contracts** — `CalendarEnhancementProvider` interface for weekend disabling, holiday highlighting, and team absence display on datepickers. This is a sophisticated UX detail.

7. **Notification Action Registry** — Inline actions on notifications (not just navigation). This is more advanced than BambooHR's basic notification list.

### 3.2 UX Patterns That Work Well

1. **Drawer-based wizards** — The leave request wizard opens in a slide-over drawer from the dashboard, keeping context. This is better than full-page navigation.

2. **Inline Clock In/Out** — Prominently placed above the dashboard grid with clear status display. Mirrors mobile-first clock patterns.

3. **Approval Panel with Timeline** — Combined approve/reject/recall actions + history timeline in a single component. Clean and contextual.

4. **Status visibility** — Leave requests show Draft → Pending → Approved/Denied/Cancelled states. The approval panel shows current step and approver list.

5. **Draft save** — "Leave request saved as draft. You can resume it later from My Leave." Reduces abandonment.

6. **Completion screen with next actions** — After submitting leave: "View My Requests", "Request Another", "Team Calendar". Guides the user forward.

---

## 4. Weaknesses & Gaps

### 4.1 Features Present in BambooHR/Workday/SAP That Are Missing

| Feature | BambooHR | Workday | SAP SF | Our ESS |
|---------|----------|---------|--------|---------|
| Company Directory | ✅ | ✅ | ✅ | ❌ |
| Org Chart | ✅ | ✅ | ✅ | ❌ |
| Benefits Enrollment | ✅ | ✅ | ✅ | ❌ |
| Learning Management | ❌ | ✅ | ✅ | ❌ |
| Goals & Performance | ❌ | ✅ | ✅ | ❌ |
| Announcements / Company Feed | ✅ | ❌ | ✅ | ❌ |
| Mobile App | ✅ | ✅ | ✅ | ⚠️ (API only) |
| "My Team" View (Managers) | ❌ | ✅ | ✅ | ❌ |
| Unified Inbox | ❌ | ✅ | ✅ | ❌ |
| AI Recommendations | ❌ | ❌ | ✅ | ❌ |
| Onboarding Checklist | ✅ | ✅ | ✅ | ❌ |
| Document Self-Service Upload | ✅ | ✅ | ✅ | ❌ |
| Personal Info Change Requests | ✅ | ✅ | ✅ | ❌ |

### 4.2 Navigation & IA Issues

1. **Context Confusion** — ✅ **FIXED (2026-09-03).** The Leave Hub (`/hr/leave-hub`) now lives entirely within the HR module under `context="my-portal"` and `moduleName="hr"`, eliminating the cross-module routing issue. The former "My Leave" (`/leave/my-leave`) and "Request Leave" (`/hr/employee-self-service`) have been consolidated into a single tabbed view with Overview, My Leaves, and Apply tabs.

2. **Admin-Oriented Sidebars Leak into ESS** — ✅ **FIXED (2026-09-03).** The Leave Hub uses the HR module's `my-portal` context, so employees no longer see the Leave module's admin-oriented sidebar with "Requests" and "Configuration" contexts.

3. **Redundant Standalone Pages** — ✅ **FIXED (2026-09-03).** `/hr/employee-self-service` and `/leave/my-leave` now redirect to `/hr/leave-hub`. The wizard and data table are embedded as tabs within the hub.

4. **No Breadcrumbs in ESS Views** — All ESS views disable breadcrumbs (`'breadcrumb' => ['enabled' => false]`). While this simplifies the UI, it removes orientation for deeper navigation.

5. **My Documents Not Scoped** — ✅ **FIXED (2026-09-03).** My Documents has been consolidated into the Profile Hub as the "Documents" tab at `/hr/my-profile`. The old `/hr/my-documents` URL redirects to the Profile Hub.

### 4.3 Workflow Gaps

1. **No Delegation** — Managers cannot delegate approval authority during absence. Both Workday and SAP SF support this.

2. **No Escalation** — If an approver doesn't act within N days, there's no automatic escalation. Workday has time-based escalation rules.

3. **No Bulk Approval** — Approvers must handle requests one at a time. BambooHR and Workday support bulk approve.

4. **No Mobile Push Notifications** — Notifications are in-app only. All three competitors have push notifications.

5. **Payroll Workflow Not Employee-Facing** — The payroll workflow is for payroll officers/HR managers only. Employees have no workflow for payslip disputes or queries.

### 4.4 UX Pattern Weaknesses

1. **ESS Views Are Raw Data Tables** — My Leave and My Attendance render as generic `qf.data-table` components. There's no ESS-specific UX (no summary cards, no visual status indicators, no quick filters). Compare to BambooHR's "Time Off" page which shows a calendar view with balance summary. **Note:** My Payslips now has inline "View Payslip" and "Download PDF" actions via the data table row dropdown (see Recommendation #5).

2. **My Profile Uses Admin Component** — ✅ **FIXED (2026-09-02).** The [`my-profile.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/my-profile.blade.php:16) now uses the ESS-specific config key `hr.employee_ess` which restricts visible tabs to overview/personal/contact/employment/history/workpatterns/payslips/documents/attendance and hides compensation, bank info, and tax info sections.

3. **My Account Uses Admin Form** — ✅ **FIXED (2026-09-03).** The former standalone "My Account" page (`/hr/my-account`) and "My Preferences" page (`/hr/my-preferences`) have been consolidated into the Profile Hub. The old `/hr/my-account` URL redirects to `/hr/my-profile`.

4. **Team Who's Out Data Is Empty** — ✅ **IMPLEMENTED (2026-09-02).** The [`TeamWhoIsOutWidgetProcessor`](src/Widgets/TeamWhoIsOutWidgetProcessor.php:1) now supports a `model` config key that queries approved LeaveRequests scoped to the current company (via `HasCompanyScope`). The dashboard config in [`dashboard_my_portal.php`](hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard_my_portal.php:240) specifies `model => LeaveRequest::class` with `conditions => [['status', '=', 'Approved']]`. The processor filters to leaves spanning today (`start_date <= today AND end_date >= today`), eager-loads `employee` and `leaveType` relations, and formats member data with name, leave type badge, date range, and return date.

5. **Upcoming Holidays Is a Placeholder** — Shows "—" with a TODO comment. This should be wired to the Holiday module.

6. **No Calendar View for Leave** — ✅ **IMPLEMENTED (2026-09-02).** A `monthly` switch view has been added to the DataTable library and the [`leave_request.php`](hr-consuming-app/app/Modules/Leave/Data/leave_request.php:500) config. The [`monthly-view.blade.php`](src/Resources/views/livewire/data-tables/partials/monthly-view.blade.php:1) groups leave requests by month with timeline cards showing date blocks, employee name, leave type, duration, color-coded status badges, and row actions. Employees can switch between Table, List, Cards, and Monthly views from the View dropdown.

7. **No Visual Leave Balance** — The stat card shows a number, but there's no visual bar showing used vs. available. BambooHR shows a progress bar.

8. **Team Calendar Is Now Implemented** ✅ — The [`team-calendar.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/team-calendar.blade.php:1) placeholder has been replaced with a full dashboard (see Recommendation #2). Shows stats, team who's out, upcoming leave, and currently out lists — all model-driven from `LeaveRequest`.

---

## 5. Comparison Matrix

| Capability | Our ESS | BambooHR | Workday | SAP SF | Gap |
|------------|---------|----------|---------|--------|-----|
| **Leave Requests** | ✅ Wizard + drawer + draft + balance preview + approval path | ✅ Wizard | ✅ Guided process | ✅ Tile-based | **On par / ahead** (Cmd+K, draft save) |
| **Leave Calendar View** | ✅ Monthly timeline view | ✅ Calendar | ✅ Calendar | ✅ Calendar | **On par** |
| **Leave Balance Visualization** | ⚠️ Stat number only | ✅ Progress bar | ✅ Chart | ✅ Tile | **Behind** |
| **Clock In/Out** | ✅ Livewire toggle + API | ✅ Mobile app | ✅ Mobile + web | ✅ Mobile + web | **On par** (web); **Behind** (no mobile app) |
| **My Attendance History** | ⚠️ Raw data table | ✅ Calendar + stats | ✅ Calendar | ✅ Calendar | **Behind** |
| **Payslip Access** | ✅ Data table + PDF view + download | ✅ Visual payslip | ✅ Visual payslip | ✅ Visual payslip | **On Par** |
| **Personal Info Update** | ⚠️ Admin form | ✅ Employee form | ✅ Self-service form | ✅ Self-service form | **Behind** |
| **Company Directory** | ❌ | ✅ With org chart | ✅ With org chart | ✅ With org chart | **Missing** |
| **Org Chart** | ❌ | ✅ | ✅ | ✅ | **Missing** |
| **Team Who's Out** | ✅ Model-driven widget (real data) | ✅ Calendar overlay | ✅ Team calendar | ✅ Team view | **On par** |
| **Team Calendar** | ✅ Dashboard with stats + team who's out + leave lists | ✅ | ✅ | ✅ | **On par** |
| **Notifications** | ✅ Filterable + inline actions | ✅ Basic list | ✅ Unified inbox | ✅ Unified inbox | **On par** (features); **Behind** (no unified inbox) |
| **Approvals** | ✅ Pending/submitted queues + timeline | ✅ | ✅ Unified inbox | ✅ Unified inbox | **On par** (features); **Behind** (no unified inbox) |
| **Cmd+K Quick Actions** | ✅ Personalized ranking + favorites | ❌ | ❌ | ❌ | **Ahead** |
| **Draft Save** | ✅ Leave wizard | ❌ | ✅ | ✅ | **On par** |
| **Sick Call Report** | ✅ Wizard | ❌ | ✅ | ✅ | **On par** |
| **Benefits Enrollment** | ❌ | ✅ | ✅ | ✅ | **Missing** |
| **Learning / Development** | ❌ | ❌ | ✅ | ✅ | **Missing** |
| **Goals / Performance** | ❌ | ❌ | ✅ | ✅ | **Missing** |
| **Announcements / Feed** | ❌ | ✅ | ❌ | ✅ | **Missing** |
| **Onboarding** | ❌ | ✅ | ✅ | ✅ | **Missing** |
| **Mobile App** | ⚠️ API only (Android sync) | ✅ iOS + Android | ✅ iOS + Android | ✅ iOS + Android | **Behind** |
| **Bulk Approvals** | ❌ | ✅ | ✅ | ✅ | **Missing** |
| **Approval Delegation** | ❌ | ✅ | ✅ | ✅ | **Missing** |
| **Approval Escalation** | ❌ | ❌ | ✅ | ✅ | **Missing** |
| **Document Self-Service** | ✅ (2026-09-04) | ✅ | ✅ | ✅ | **On par** — LeaveRequest attachments migrated from JSON column to polymorphic [`DocumentEngine`](src/Services/Documents/DocumentEngine.php:1) + [`documents`](Database/migrations/2026_06_12_142526_create_documents_table.php:1) table via [`Documentable`](src/Contracts/Documents/Documentable.php:1) contract. [`LeaveDocumentUpload`](hr-consuming-app/app/Modules/Leave/Http/Livewire/LeaveDocumentUpload.php:1) Livewire component provides upload, preview, download, and delete UI on the leave request detail page. |
| **Config-Driven Architecture** | ✅ Fully declarative | ❌ Hard-coded | ⚠️ Metadata-driven | ✅ Metadata-driven | **Ahead** |

---

## 6. Prioritized Recommendations

Ordered by **impact ÷ effort** (highest impact, lowest effort first):

### 1. Wire Up Team Who's Out Widget Data (High Impact, Low Effort) ✅ IMPLEMENTED (2026-09-02)
**Problem:** The widget exists but `data` is an empty array.
**Solution:** Updated [`TeamWhoIsOutWidgetProcessor`](src/Widgets/TeamWhoIsOutWidgetProcessor.php:1) to support a `model` config key. When `model` is provided, the processor queries approved LeaveRequests scoped to the current company (via `HasCompanyScope`), filters to leaves spanning today (`start_date <= today AND end_date >= today`), eager-loads `employee` and `leaveType` relations, and formats member data with name, leave type badge, date range, and return date. The dashboard config in [`dashboard_my_portal.php`](hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard_my_portal.php:240) now specifies `model => LeaveRequest::class` with `conditions => [['status', '=', 'Approved']]`.
**Why first:** The widget, processor, and blade template are all built. Only the data plumbing is missing. This immediately gives employees team awareness — a core ESS feature.

### 2. Build Team Calendar View (High Impact, Medium Effort) ✅ IMPLEMENTED (2026-09-02)
**Problem:** [`team-calendar.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/team-calendar.blade.php:1) was a placeholder.
**Solution:** Replaced the placeholder with a [`livewire:qf.dashboard`](hr-consuming-app/app/Modules/Hr/Resources/views/team-calendar.blade.php:7) component backed by a new dashboard config at [`dashboard_team_calendar.php`](hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard_team_calendar.php:1). The dashboard uses the library's existing widget types:
- **Stat cards** — "People Out Today", "People Out This Week", "People Out This Month", "Pending Requests" — all querying `LeaveRequest` with date-range conditions
- **Team Who's Out widget** — reuses the existing [`TeamWhoIsOutWidgetProcessor`](src/Widgets/TeamWhoIsOutWidgetProcessor.php:1) with model-based querying for approved leaves spanning today
- **Upcoming Leave list** — approved leave starting this month, ordered by start date
- **Currently Out list** — employees currently on leave (start ≤ today ≤ end), ordered by return date

All widgets are model-driven via the `LeaveRequest` model, which uses `HasCompanyScope` for automatic company scoping. No custom calendar component was needed — the dashboard + list widget pattern provides a clean, functional team calendar view.
**Why second:** Team calendar is the #1 coordination tool in BambooHR/Workday. The calendar infrastructure already exists in the datepicker enhancements.

### 3. Create ESS-Optimized My Profile View (High Impact, Medium Effort) ✅ IMPLEMENTED (2026-09-02)
**Problem:** [`my-profile.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/my-profile.blade.php:16) used the admin `qf.employee-detail` component.
**Solution:** Created [`employee_ess.php`](hr-consuming-app/app/Modules/Hr/Data/employee_ess.php:1) — an ESS-specific DataTableForm config that:
- Uses the same `Employee` model but with a `self_service` configuration block controlling allowed tabs and hidden sections
- Restricts visible tabs to `['overview', 'personal', 'contact', 'employment', 'history', 'workpatterns', 'payslips', 'documents', 'attendance']` — hiding payroll and clockevents
- Hides Compensation, Bank Info, and Tax Info sub-sections via `hideCompensation`, `hideBankInfo`, `hideTaxInfo` flags
- Disables all edit buttons via `hideEditButtons` and `canEdit()` returning false

Updated [`EmployeeDetail.php`](hr-consuming-app/app/Modules/Hr/Http/Livewire/EmployeeDetail.php:75) to:
- Read ESS restrictions from the `hr.employee_ess` config via `ConfigResolver`
- Fall back to safe defaults if the config is missing
- Expose `hideCompensation()`, `hideBankInfo()`, `hideTaxInfo()` helper methods

Updated [`employee-detail.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/livewire/employee-detail.blade.php:364) to:
- Guard the Compensation card with `@if (!$this->isSelfServiceMode || !$this->hideCompensation())`

Updated [`my-profile.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/my-profile.blade.php:16) to pass `configKey="hr.employee_ess"` instead of `"hr.employee"`.

**What the ESS view now shows:**
- Overview tab (dashboard with profile header, stats)
- Personal tab (personal info, identification docs)
- Contact tab (address, contact details, emergency contact)
- Employment tab (job details — department, position, manager, location, shift)
- History tab (employment history and job changes)
- Work Patterns tab (work schedule and shift patterns)
- Payslips tab (payslip access and PDF download)
- Documents tab (employee documents)
- Attendance tab (full attendance data table filtered to employee)
- NO Compensation, Bank Info, Tax Info, Payroll, or Clock Events

**Why third:** "My Info" is the most-visited ESS page after the dashboard in BambooHR. The current admin component is inappropriate for self-service.

### 4. Add Leave Calendar View to My Leave (High Impact, Medium Effort) ✅ IMPLEMENTED (2026-09-02)
**Problem:** [`my-leave.blade.php`](hr-consuming-app/app/Modules/Leave/Resources/views/leave/my-leave.blade.php:23) shows only a data table.
**Solution:** Added a `monthly` switch view to the DataTable library and leave request config.
**Implementation Details:**
- Extended [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:362) to support `monthly` view mode in `setViewMode()`, `toggleViewMode()`, `getNextViewModeProperty()`, `initializeFromConfig()`, and `render()`
- Added "Monthly" option to the View dropdown in [`data-table.blade.php`](src/Resources/views/livewire/data-tables/data-table.blade.php:99) — shown only when `switchViews.monthly` is configured
- Created [`monthly-view.blade.php`](src/Resources/views/livewire/data-tables/partials/monthly-view.blade.php:1) — groups leave requests by month, renders a timeline card for each request showing:
  - Date block with day number and weekday label (start → end for multi-day)
  - Employee name, leave type, date range with day count
  - Color-coded status badge (Approved/Pending/Draft/Denied/Cancelled)
  - Row actions on hover
- Added `monthly` view config to [`leave_request.php`](hr-consuming-app/app/Modules/Leave/Data/leave_request.php:500) with `dateField`, `endDateField`, `titleFields`, `subtitleFields`, `badgeField`, and `badgeColors`
**Why fourth:** Every major HR SaaS shows leave on a calendar. The data table alone is insufficient for planning.

### 5. Leave Hub Consolidation ✅ IMPLEMENTED (2026-09-03)
**Problem:** "My Leave" (`/leave/my-leave`) and "Request Leave" (`/hr/employee-self-service`) were separate sidebar items, causing context confusion (Leave module admin sidebar leaking into ESS) and redundant standalone pages.
**Solution:** Created a consolidated Leave Hub at `/hr/leave-hub` with three tabs:
- **Overview** — Dashboard with leave balance widgets and team who's out
- **My Leaves** — Filtered data table scoped to the employee
- **Apply** — The existing leave request wizard

**Implementation Details:**
- Created [`LeaveHub.php`](hr-consuming-app/app/Modules/Hr/Http/Livewire/LeaveHub.php:1) — Livewire component with `$activeTab` state and `switchTab()` method, resolves employee from `Auth::id()`
- Created [`leave-hub.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/leave-hub.blade.php:1) — Tabbed view using Bootstrap `nav nav-tabs` with `wire:click` handlers, embedding `qf.dashboard`, `qf.data-table`, and `qf.wizard` components
- Updated [`navigation.php`](hr-consuming-app/app/Modules/Hr/Config/navigation.php:55) — Replaced `my_leave` (order 3) and `employee_self_service` (order 6) with single `leave_hub` entry at order 3, renumbered remaining items (4-9 → 4-7)
- Added redirects in [`web.php`](hr-consuming-app/app/Modules/Hr/Routes/web.php:39) — `/leave/my-leave` and `/hr/employee-self-service` → `/hr/leave-hub`
**Why fifth:** Eliminates navigation clutter, fixes context confusion, and provides a unified leave experience matching BambooHR's "Time Off" page pattern.

### 6. Payslip PDF Viewer ✅ IMPLEMENTED (2026-09-02)
**Problem:** The My Payslips page showed payslips as a raw data table with no PDF preview or download capability from the table rows.
**Solution:** Added route-based `moreActions` to the data table library's [`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php:133) — actions can now specify `route` + `routeParam` keys to render as direct `<a href>` links instead of Livewire `wire:click` handlers. The `moreActions` dropdown items render as standard link-based navigation when a `route` or `url` key is present, with optional `newTab` support.

Updated [`payroll_payslip.php`](hr-consuming-app/app/Modules/Payroll/Data/payroll_payslip.php:520) to add two row actions:
- **"View Payslip"** — Opens `payslips.view` route (streams PDF in new tab) with 👁 icon
- **"Download PDF"** — Triggers `payslips.download` route (force-downloads the PDF) with � icon

Both routes already existed in [`w eb.php`](hr-consuming-app/app/Modules/Payroll/Routes/web.php:62) and were handled by `PayslipController` which delegates to `PayslipService::generatePdf()` for PDF generation via DomPDF.
**Impact:** Employees can now view and download their payslip PDFs directly from the My Payslips data table row actions dropdown.

### 7. Implement Unified Inbox (High Impact, High Effort)

**Problem:** Approvals and notifications are separate. Leave approvals at `/leave/approvals`, payroll at `/payroll/approvals`, notifications in the bell icon.  
**Solution:** Create a single "Inbox" page aggregating all pending approvals across modules + unread notifications. Add an "Inbox" item to the My Portal sidebar.  
**Why seventh:** This is Workday's signature ESS feature. It reduces cognitive load and ensures nothing is missed.

### 8. My Profile Consolidation ✅ IMPLEMENTED (2026-09-03) — 🔄 REFACTORED (2026-09-03)
**Problem:** "My Profile" (`/hr/my-profile`), "My Account" (`/hr/my-account`), and "My Preferences" (`/hr/my-preferences`) were separate sidebar items and pages, fragmenting the employee's personal information across multiple views.
**Solution:** The My Profile page now uses the [`EmployeeDetail`](hr-consuming-app/app/Modules/Hr/Http/Livewire/EmployeeDetail.php:1) component directly (the same component used for admin employee detail views), with self-service mode auto-detected via `request()->is('hr/my-profile')`. This eliminates the separate `ProfileHub` component entirely, providing a single source of truth for employee profile rendering.

The ESS view shows these tabs (filtered by `getAllowedTabs()`):
- **Overview** — Dashboard with profile header and quick stats (uses `hr.dashboard_profile_hub` in self-service mode)
- **Personal** — Employee personal info with inline field rendering
- **Contact** — Address, phone, emergency contact
- **Employment** — Department, position, manager, location (compensation hidden via `hideCompensation()`)
- **History** — Employment history and job changes
- **Work Patterns** — Work schedule and shift patterns
- **Payslips** — Payslip access and PDF download
- **Documents** — Employee documents
- **Attendance** — Full attendance data table filtered to employee (their own records only)

**Security Guards (all active in self-service mode):**
- `canEdit()` → hides all edit buttons
- `hideCompensation()` → hides salary/compensation card
- `hideBankInfo()` → hides bank information card
- `hideTaxInfo()` → hides tax withholding card
- `getAllowedTabs()` → filters visible tabs to ESS-safe subset
- `loadData()` → enforces ownership (abort 403 if viewing another employee)
- Print button hidden via `!$this->isSelfServiceMode`
- Employee dropdown and prev/next navigation hidden (no `$recordIds` in ESS)

**Implementation Details (2026-09-03 refactor):**
- Removed [`ProfileHub.php`](hr-consuming-app/app/Modules/Hr/Http/Livewire/ProfileHub.php:1) and [`profile-hub.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/livewire/profile-hub.blade.php:1)
- Removed `qf.profile-hub` registration from [`HrsServiceProvider.php`](hr-consuming-app/app/Modules/Hr/Providers/HrsServiceProvider.php:35)
- Updated [`my-profile.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/my-profile.blade.php:12) to use `@livewire('qf.employee-detail', ['configKey' => 'hr.employee', 'recordId' => auth()->user()->employee->id, 'inline' => true])`
- Added 4 view guards to [`employee-detail.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/livewire/employee-detail.blade.php:1): print button, bank info card, tax withholding card, overview dashboard conditional
- Removed `my_account` from [`navigation.php`](hr-consuming-app/app/Modules/Hr/Config/navigation.php:91), renumbered `my_documents` from order 8 → 7
- Added redirect in [`web.php`](hr-consuming-app/app/Modules/Hr/Routes/web.php:41): `/hr/my-account` → `/hr/my-profile`
**Why eighth:** Eliminates navigation clutter (7→6 sidebar items), provides a unified profile experience matching BambooHR's "My Info" page pattern. **Further consolidated (2026-09-03):** My Payslips and My Documents merged into Profile Hub as tabs, reducing sidebar from 6→5 items. **Refactored (2026-09-03):** ProfileHub component eliminated — EmployeeDetail now serves both admin and ESS contexts via `isSelfServiceMode` flag, providing a single source of truth.

### 9. Wire Upcoming Holidays Stat (Medium Impact, Low Effort)
**Problem:** The stat card shows "—" with a TODO comment.
**Solution:** Query the Holiday module for upcoming holidays in the next 30 days and display the count.
**Why ninth:** Trivial to implement, removes a visible placeholder from the dashboard.

### 10. Add Visual Leave Balance (Medium Impact, Medium Effort)
**Problem:** Leave balance is a raw number.
**Solution:** Add a progress bar or donut chart showing used vs. total for each leave type. BambooHR does this well.
**Why tenth:** Improves the dashboard's information density and visual appeal.

### 11. Scope My Documents to Employee (Medium Impact, Low Effort) ✅ CONSOLIDATED (2026-09-03)
**Problem:** [`my-documents.blade.php`](hr-consuming-app/app/Modules/Hr/Resources/views/my-documents.blade.php:2) showed all documents without employee filtering.
**Solution:** My Documents has been consolidated into the Profile Hub as the "Documents" tab at `/hr/my-profile`. The old `/hr/my-documents` URL redirects to the Profile Hub. Employee-scoped document access is now handled through the Profile Hub's tabbed interface.
**Why eleventh:** Eliminates a standalone page and provides document access within the unified profile experience.

### 12. Add Announcements Widget to My Portal (Medium Impact, Medium Effort)
**Problem:** No company communication channel in ESS.  
**Solution:** Add an announcements/list widget to the My Portal dashboard. HR can post announcements that appear on all employee dashboards.  
**Why twelfth:** BambooHR's company feed is a key engagement feature. Builds organizational culture.

---

## Summary

The ESS implementation has a **strong architectural foundation** — config-driven, contract-based, with a well-designed My Portal hub, wizard framework, workflow engine, and Cmd+K command palette. The leave request flow is **on par with or ahead of** major HR SaaS platforms in terms of UX patterns (drawer wizard, draft save, balance preview, approval path).

However, the implementation is **uneven**. While the leave request wizard is polished, most other ESS views are raw data tables with no ESS-specific UX. Key features that define BambooHR/Workday/SAP SuccessFactors — team calendar, org chart, company directory, visual leave balance, unified inbox — are either missing or placeholder.

The **top 6 recommendations** (Team Who's Out data ✅, Team Calendar ✅, ESS Profile ✅, Leave Calendar View ✅, Leave Hub Consolidation ✅, My Profile Consolidation ✅) have all been implemented. The Leave Hub consolidation merges the former "My Leave" and "Request Leave" sidebar items into a single tabbed "Leave" entry point with Overview, My Leaves, and Apply tabs. The My Profile consolidation merges the former "My Profile", "My Account", "My Preferences", "My Payslips", and "My Documents" into a single tabbed "My Profile" page with Overview, Personal, Contact, Employment, History, Work Patterns, Payslips, Documents, and Attendance tabs (9 tabs total) — reducing the My Portal sidebar from 7 items to 4 (Overview, My Profile, Leave, Team Calendar). The ESS now provides a competitive employee self-service experience on par with major HR SaaS platforms for core features.

### ESS Admin View Leakage Fix ✅ (2026-09-03)

**Problem:** When ESS users clicked "View All" links on dashboard widgets (Recent Attendance, Upcoming Time Off, Recent Clock Events, Expiring Documents, Recent Location Activity), they were navigated to admin views with `context="time"`, `context="leave"`, or `context="manage"`. This caused:
1. **Data leakage** — URL-based `filter[employee_id]` could be removed by the user, exposing ALL employees' records
2. **Sidebar context leakage** — The sidebar switched from "My Portal" to admin context groups (Time, Leave, Manage)
3. **Breadcrumb leakage** — Breadcrumbs showed admin paths instead of "My Portal"

**Solution:** Hybrid approach (Approach A + C-light from [`ess-admin-view-leakage-analysis.md`](plans/ess-admin-view-leakage-analysis.md)):

**Primary Fix — 5 ESS Thin-Wrapper Blade Views:**
| View | File | Config Key | Route |
|------|------|-----------|-------|
| My Attendance | [`ess/attendance.blade.php`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/ess/attendance.blade.php) | `attendance.attendance` | `/hr/my-attendance` |
| My Leave Requests | [`ess/leave-requests.blade.php`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/ess/leave-requests.blade.php) | `leave.leave_request` | `/hr/my-leave-requests` |
| My Clock Events | [`ess/clock-events.blade.php`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/ess/clock-events.blade.php) | `attendance.clock_event` | `/hr/my-clock-events` |
| My Payslips | [`ess/payslips.blade.php`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/ess/payslips.blade.php) | `payroll.payroll_payslip` | `/hr/my-payslips-view` |
| My Documents | [`ess/documents.blade.php`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/ess/documents.blade.php) | `hr.document` | `/hr/my-documents-view` |

Each view uses `context="my-portal"` (keeping sidebar in ESS mode) and `page-query-filters` (hardcoded, non-removable `employee_id` scoping).

**Dashboard Widget Link Updates:**
- "Upcoming Time Off" → `/hr/my-leave-requests`
- "Recent Attendance" → `/hr/my-attendance`
- "Recent Clock Events" → `/hr/my-clock-events`
- "Expiring Documents" → `/hr/my-documents-view`
- "Recent Location Activity" → `show_view_all: false` (no ESS equivalent for admin activity logs)

**Safety Net — Middleware:**
[`RedirectEssUsersFromAdminViews`](../../../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Http/Middleware/RedirectEssUsersFromAdminViews.php) intercepts ESS-only users (role `employee`, not `admin`/`super_admin`) who navigate directly to admin URLs and redirects them to the ESS wrapper equivalents.

**Files Created:** 5 blade views + 1 middleware
**Files Modified:** `web.php` (routes), `dashboard_employee_overview.php` (widget links), `bootstrap/app.php` (middleware registration)
**Library Changes:** None — all changes in consuming app

The **strategic gaps** (unified inbox, mobile app, benefits, learning, performance) represent longer-term investments that would be needed to compete with Workday/SAP SF at the enterprise level, but are not critical for the current market position.

### Role-Based Post-Login Redirect ✅ (2026-09-03)

**Problem:** After login, all users were redirected to `/home` regardless of their role. ESS employees, payroll officers, and HR managers each have dedicated dashboards that should serve as their landing page.

**Solution:** Overrode Fortify's `LoginResponse` contract via a singleton binding in the consuming app's [`FortifyServiceProvider`](../../../../LaravelProjects/hr-consuming-app/app/Providers/FortifyServiceProvider.php:56). The binding implements `Laravel\Fortify\Contracts\LoginResponse` with a `toResponse()` method that uses `match(true)` to route users based on their Spatie role:

| Role | Condition | Landing Page |
|------|-----------|-------------|
| ESS Employee | `hasRole('employee')` AND NOT `hasAnyRole(['super_admin', 'admin', 'company_admin'])` | `/hr/my-portal` |
| Payroll Officer | `hasRole('payroll_officer')` | `/payroll/dashboard-processing-overview` |
| HR Manager | `hasRole('hr_manager')` | `/hr/dashboard-people-overview` |
| Admin / Others | Default fallback | `/home` |

**Key design decisions:**
- Uses `redirect()->intended()` so that if a user was redirected to login from a deep link, they return to that link instead of being forced to their role-based landing page
- The ESS employee check explicitly excludes users with elevated roles (`super_admin`, `admin`, `company_admin`) — an admin who also has the `employee` role will NOT be redirected to My Portal
- The binding is placed in the consuming app's `FortifyServiceProvider`, NOT the library — this is a consuming-app concern since role definitions and dashboard URLs are app-specific
- No library changes required — Fortify's `LoginResponse` contract is designed for this exact override pattern

**Impact:** ESS employees now land directly on their My Portal dashboard after login instead of the generic `/home` page. This eliminates an unnecessary navigation step and provides immediate access to their clock in/out, leave balance, and quick actions.

### Module Dashboard Security Fix ✅ (2026-09-03)

**Problem:** Two security vulnerabilities were identified in the module dashboard routing:

1. 🔴 **Critical**: 18 HR CRUD routes (attendance-policies, employees, employee-job-histories, employee-profiles, employee-positions, locations — create/show/edit) had **NO middleware at all**, making them accessible to unauthenticated users.
2. 🟠 **High**: 26 module dashboards across HR, Payroll, Attendance, Leave, Holiday, and Organization modules were accessible to **any authenticated user** regardless of role. An `employee`-role user could directly navigate to `/payroll/dashboard` or `/hr/dashboard-people-overview` and view admin-level dashboards.

**Solution:** Two-pronged fix:

1. **New `EnsureModuleDashboardAccess` middleware** — Global middleware registered in `bootstrap/app.php` that enforces role-based access per URL prefix:
   - `/hr/my-*` (ESS routes) → `employee`, `manager`
   - `/hr/*` (admin HR) → `hr_manager`, `admin`, `super_admin`
   - `/payroll/*` → `payroll_officer`, `admin`, `super_admin`
   - `/organization/*` → `admin`, `super_admin`
   - `/admin/*` → `admin`, `super_admin`
   - `/home` → any authenticated user (ESS-only users redirected to `/hr/my-portal`)
   - `super_admin` and `admin` bypass all checks
   - Unauthorized users are redirected to their role-appropriate landing page

2. **Wrapped 18 unprotected CRUD routes** in `Route::middleware(['web', 'auth'])` group in [`app/Modules/Hr/Routes/web.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Routes/web.php).

3. **Added `auth` middleware to `/home` route** in the library's [`src/Routes/web.php`](src/Routes/web.php).

4. **Refactored hardcoded role mapping into `module_access` config** — The `EnsureModuleDashboardAccess` middleware originally had URL prefix → role mappings hardcoded in the class. These were extracted to a declarative `module_access` config key in both the consuming app's [`config/ui-library.php`](../../LaravelProjects/hr-consuming-app/config/ui-library.php:428) and the library's [`src/Config/ui-library.php`](src/Config/ui-library.php:467). The middleware now reads from `config('ui-library.module_access')` — with the consuming app's config overriding the library's defaults. The library default is an empty array (`[]`), while the consuming app defines 9 URL prefix mappings.

5. **Fixed navigation `roles` key enforcement** — The [`Sidebar`](src/Http/Livewire/Layouts/Navs/Sidebar.php:1) and [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php:1) components now filter navigation items by the `roles` key defined in navigation configs. Previously, all items were shown to all authenticated users regardless of the `roles` array. The filter checks `Auth::user()->hasAnyRole($item['roles'] ?? [])` — items with no `roles` key or an empty `roles` array are shown to everyone, while items with specific roles are only shown to users who have at least one of those roles.

**Files Created:** 1 middleware
**Files Modified:** `bootstrap/app.php`, `app/Modules/Hr/Routes/web.php`, `src/Routes/web.php`, `config/ui-library.php` (consuming app), `src/Config/ui-library.php` (library), [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php:1), [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php:1)
**Library Changes:** 3 (`src/Routes/web.php` — `/home` route `auth` middleware; `src/Config/ui-library.php` — `module_access` default; `Sidebar.php` + `TopNav.php` — roles enforcement)
**Full Analysis:** [`module-dashboard-security-analysis.md`](plans/module-dashboard-security-analysis.md)

### DataTable Detail View crudType-Aware Click Handlers ✅ (2026-09-04)

**Problem:** The [`list-view.blade.php`](src/Resources/views/livewire/data-tables/partials/list-view.blade.php:1) and [`card-view.blade.php`](src/Resources/views/livewire/data-tables/partials/card-view.blade.php:1) partials used `wire:click="show({{ $record->id }})"` for all row/card clicks, which only worked for the `modal` crudType. When a DataTable was configured with `crudType: 'drawers'` or `crudType: 'pages'`, clicking a row or card would open a modal instead of the configured behavior.

**Solution:** Both [`list-view.blade.php`](src/Resources/views/livewire/data-tables/partials/list-view.blade.php:11) and [`card-view.blade.php`](src/Resources/views/livewire/data-tables/partials/card-view.blade.php:12) now use `crudType`-aware click handlers:

| crudType | Click Behavior | Implementation |
|----------|---------------|----------------|
| `drawers` | Dispatches `openDrawer` event with `qf.data-table-detail` component | `Livewire.dispatch('openDrawer', { component: 'qf.data-table-detail', params: {...}, title: 'View ...' })` |
| `pages` | Navigates to the show URL via `window.location` | `window.location='{{ $this->getShowUrl($record->id) }}'` |
| `modal` (default) | Opens a modal via Livewire `show()` method | `wire:click="show({{ $record->id }})"` |

All click handlers use `event.target.closest('.stop-propagation')` to prevent navigation when clicking action buttons (edit, delete, bulk select, more actions dropdown). The [`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php:1) partial also uses `crudType`-aware rendering for Show and Edit buttons:

- **Show button**: `pages` → `<a href>` link; `drawers` → `openDrawer` dispatch; `modal` → `wire:click="show()"`
- **Edit button**: `pages` → `<a href>` route link; `drawers` → `openDrawer` dispatch with `qf.data-table-form`; `modal` → `wire:click="edit()"`

**Files Modified:** [`list-view.blade.php`](src/Resources/views/livewire/data-tables/partials/list-view.blade.php:1), [`card-view.blade.php`](src/Resources/views/livewire/data-tables/partials/card-view.blade.php:1), [`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php:1)
**Library Changes:** 3 blade partials — all in library

### LeaveRequest Attachments → Polymorphic Documents Migration ✅ (2026-09-04)

**Problem:** `LeaveRequest` had a JSON `attachments` column that bypassed the UI library's polymorphic document system. The library provides [`DocumentEngine`](src/Services/Documents/DocumentEngine.php:1) + [`documents`](Database/migrations/2026_06_12_142526_create_documents_table.php:1) table + [`Documentable`](src/Contracts/Documents/Documentable.php:1) contract for a dedicated document upload system. `LeaveRequest` already implemented `Documentable` with all 4 methods + `documents()` morphMany relationship.

**Solution:**
1. Removed `attachments` field definition from [`leave_request.php`](hr-consuming-app/app/Modules/Leave/Data/leave_request.php:147) config
2. Removed `'attachments'` from `$fillable` and `$casts` in `LeaveRequest` model
3. Created migration to drop `attachments` JSON column from `leave_requests` table
4. Created [`LeaveDocumentUpload`](hr-consuming-app/app/Modules/Leave/Http/Livewire/LeaveDocumentUpload.php:1) Livewire component providing upload, preview, download, and delete UI on the leave request detail page
5. `LeaveRequest` uses the [`HasDocuments`](src/Traits/Documents/HasDocuments.php:1) trait (if available) or implements [`Documentable`](src/Contracts/Documents/Documentable.php:1) directly
**Full Details:** [`leave-module-cleanup-plan.md`](plans/leave-module-cleanup-plan.md#phase-5-migrate-attachments-to-polymorphic-documents--2026-09-04)

### Document Upload as Wizard Step 2 ✅ (2026-09-04)

**Problem:** The ESS leave request wizard had no step for uploading supporting documents. Employees had to submit the request first, then navigate to the leave request detail page to upload documents — a disjointed experience.

**Solution:** Added document upload as wizard step 2 (between form and review), transforming the 2-step wizard (Form → Review) into a 3-step flow (Form → Documents → Review).

**Implementation Details:**
- Created [`LeaveDocumentUploadWizardStep`](hr-consuming-app/app/Modules/Leave/Http/Livewire/LeaveDocumentUploadWizardStep.php:1) adapter component that accepts wizard-standard props (`$configKey`, `$presetData`, `$stepIndex`, `$recordId`) and resolves the `LeaveRequest` model
- Created [`leave-document-upload-wizard-step.blade.php`](hr-consuming-app/app/Modules/Leave/Resources/views/livewire/leave-document-upload-wizard-step.blade.php:1) embedding the existing [`LeaveDocumentUpload`](hr-consuming-app/app/Modules/Leave/Http/Livewire/LeaveDocumentUpload.php:1) component via `@livewire('leave-document-upload', ['leaveRequest' => $leaveRequest])`
- Registered component in [`LeaveServiceProvider`](hr-consuming-app/app/Modules/Leave/Providers/LeaveServiceProvider.php:47)
- Updated [`employee_self_service.php`](hr-consuming-app/app/Modules/Leave/Data/wizards/employee_self_service.php:48) wizard config: added step 1 (`'Supporting Documents'` with `requiresLink => true`), renumbered review step from 1 to 2
- Auto-advances past document step (upload is optional) via `stepFormSaved` dispatch

**Architecture:** The adapter pattern keeps the existing non-wizard `LeaveDocumentUpload` component unchanged and reusable on the leave request detail page. The wizard step wrapper handles wizard-specific concerns (record resolution from preset data, `stepFormSaved` dispatch on save event).

---

## 7. Future Implementation Considerations

This section provides a condensed view of the 28 weaknesses identified across the ESS analysis. For the complete catalogue with detailed notes, effort estimates, and full implementation ordering, see [`ess-strengths-weaknesses-comparison.md`](plans/ess-strengths-weaknesses-comparison.md#6-future-implementation-considerations).

### 7.1 Top 10 Priorities (Impact ÷ Effort)

Ordered by highest value for lowest implementation cost:

| # | Item | Category | Priority | Effort | Key Rationale |
|---|------|----------|----------|--------|---------------|
| 1 | **Unified Inbox** | Capability Gap | P1 | Medium | Workday's signature ESS feature. Merge approvals + notifications + tasks. Data already exists. |
| 2 | **Company Directory + Org Chart** | Feature Gap | P1 | Medium | Most-visited pages after dashboard. Employee model + manager relationships already exist. |
| 3 | **Personal Info Change Requests** | Feature Gap | P1 | Medium | Replace admin form with ESS-optimized form. Leverages existing WorkflowEngine. |
| 4 | **Leave Balance Visualization** | Capability Gap | P1 | Low | Progress bars per leave type. Stat card already has the number. Config-only change. |
| 5 | **Announcements / Company Feed** | Feature Gap | P2 | Low | List widget processor already exists. Mainly config + simple CRUD. |
| 6 | **"My Team" Manager View** | Feature Gap | P1 | Medium | Team attendance, approvals, performance overview for managers. |
| 7 | **Raw Data Table → ESS Views** | Nav/IA Issue | P1 | Medium | Replace generic data tables with summary cards, visual status indicators, quick filters. |
| 8 | **My Profile Consolidation** ✅ | Capability Gap | P2 | Low | Merged My Profile + My Account + My Preferences + My Payslips + My Documents into single tabbed hub. Sidebar reduced 7→5 items. |
| 9 | **My Documents Scoping** ✅ | Capability Gap | P2 | Low | Consolidated into Profile Hub as "Documents" tab. Old URL redirects to `/hr/my-profile`. |
| 10 | **Upcoming Holidays Widget** | Capability Gap | P2 | Low | Wire stat card to Holiday module. Currently shows "—" placeholder. |

### 7.2 Summary by Category

| Category | Count | P1 | P2 | P3 |
|----------|-------|----|----|-----|
| Feature Gaps (Complete Absence) | 10 | 4 | 3 | 3 |
| Capability Gaps (Behind on Implementation) | 13 | 2 | 5 | 6 |
| Navigation/IA Issues | 5 | 1 | 3 | 1 |
| **Total** | **28** | **7** | **11** | **10** |

### 7.3 Planning Note

These 28 items should be reviewed and prioritized in the next planning cycle. After implementing the top 10 priorities (items 1–10 above), the ESS would achieve **full parity with BambooHR for SMB** and close the gap with Workday/SAP SuccessFactors for mid-market organizations. The remaining 18 items represent enterprise-scale features that require significant investment and are not critical for the current market position.

**Reference:** Full catalogue at [`ess-strengths-weaknesses-comparison.md §6`](plans/ess-strengths-weaknesses-comparison.md#6-future-implementation-considerations) — includes all 28 items with detailed notes, effort estimates, and complete implementation ordering.