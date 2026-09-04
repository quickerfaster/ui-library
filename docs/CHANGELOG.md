# QuickerFaster UI Library — Changelog

> **Package**: `quicker-faster/ui-library`
> **Date**: 2026-08-30
> **Status**: Current — All 14 fix/audit categories + 19 new items + 4 home page & runtime polish items + 3 access control improvements + Phase 5 Navigation & UX Polish + App\Modules Resolution & ActivityLogs Contract completed + Architecture Blueprint Split + Access Control & Navigation UX Polish + Authorization, Seeding & Install Fixes (observations 17-23) + Module Auto-Discovery + Tenancy Foundation + DataTable Record Events + HasWorkflow Trait + Resolver Config Bindings + DataTable Runtime Bridges + Drawer Decoupled Pattern + Cross-Module Cleanup + WorkspaceScopedApproverResolver Default + Approval UI Constructor→Boot Refactor + Payroll Approval Integration

---

> ⚠️ **Testing status (2026-08-16)**: The workflow/approval foundation has been implemented and unit-verified (`php -l`, config validation), but has **NOT** yet been tested end-to-end in a consuming app. Further adjustments may be needed once integrated into a real consuming app (e.g., Spatie role/permission seeding, notification template registration, workspace-scoped approver resolution, and runtime workflow execution against real entities).

## ApprovalPanel Combined Component & Approval UX Polish — 2026-08-31

### Library — ApprovalPanel Combined Component

- **`ApprovalPanel` created** at [`src/Http/Livewire/Approvals/ApprovalPanel.php`](src/Http/Livewire/Approvals/ApprovalPanel.php) with blade view at [`src/Resources/views/livewire/approvals/approval-panel.blade.php`](src/Resources/views/livewire/approvals/approval-panel.blade.php). Combines [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) + [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) into a single cohesive unit with 3 display modes: `banner` (colored alert banner with actions + drawer-accessible timeline), `card` (full card wrapper with header), and `inline` (flat row of buttons). Registered as `qf.approval-panel` in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php).

### Library — `displayMode` Property on Approval Components

- **`ApprovalActions`** — added `$displayMode` property accepting `inline` (default), `banner`, or `card`. Banner mode renders as a colored alert with icon, status message, and action buttons. Card mode wraps content in a `.card` with `.card-header` and `.card-body`.
- **`ApprovalHistoryTimeline`** — added `$displayMode` property accepting `full` (default), `compact`, or `steps-only`. Compact mode renders smaller avatars without comment blocks.

### Library — Button Contrast Fix (Banner Mode)

- **`actions.blade.php`** — banner mode buttons use `btn-light` + `text-success` (Approve), `text-danger` (Reject), and `text-dark` (Recall) for proper contrast on colored alert backgrounds.

### Library — Recall Button: Comment Modal

- **`ApprovalActions`** — Recall button changed from `wire:confirm="..."` to `openCommentModal('recall')`, matching the Approve/Reject pattern where a comment modal collects reviewer notes before the action executes.

### Library — `ApprovalRequestListView` Status Filter Fixes

- **`ApprovalRequestListView`** — fixed conflicting `WHERE` clauses on the status filter that caused incorrect result counts.
- **`ApprovalRequestListView`** — added "All statuses" option to the status filter dropdown.
- **`ApprovalRequestListView`** — fixed initial default status selection so the list loads with the correct filter on first render.
- **`ApprovalRequestListView`** — page title is now reactive based on the `$status` filter value (e.g., "Pending Approvals" vs "All Approval Requests").

### Library — `DefaultAuthorizationProvider` Array-Valued Condition Support

- **`DefaultAuthorizationProvider::evaluateConditions()`** — now handles array-valued conditions with `in_array()` in addition to the existing scalar `===` comparison. This fixes authorization checks where a condition value is an array (e.g., multi-select field values).

### Consuming App — Leave Module

- **`workflows.php`** — workflow definition config created for leave request approvals.
- **`leave-requests/show.blade.php`** — embedded [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) and [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) components for workflow-driven leave approval UI.
- **`approvals.blade.php`** — dedicated approvals page with [`ApprovalRequestListView`](src/Http/Livewire/Approvals/ApprovalRequestListView.php) filtered to leave request workflows.
- **Navigation** — added "Leave Approvals" sidebar item.
- **Notification seeder** — seeded workflow notification templates for the leave module.

### Consuming App — PayrollRun Detail Page

- **Approval panel repositioned** — moved below the page title; status banner hidden when the run is under approval (the approval component renders its own status).
- **`effectiveStatus()` helper** — added to resolve the display status considering both the business state and workflow state.
- **Button visibility** — business action buttons (Mark Paid, Recalculate, Reports) now aligned with workflow statuses — gated on `isUnderApproval()` and workflow completion state.

### Consuming App — Timeline Drawer Button Fix

- **Event name fix** — `open-drawer` → `openDrawer` (the Drawer component listens for camelCase `openDrawer`, not kebab-case).
- **Parameter name fix** — `componentParams` → `params` (the Drawer component expects `params`, not `componentParams`).

### Consuming App — Stale Overrides Deleted

- **Removed** vendor-published `top-nav.blade.php` override that was masking library updates.
- **Removed** vendor-published `sidebar-item.blade.php` override that was masking library updates.
- **Removed** vendor-published `approval-request-list.blade.php` override that was masking library updates.

## WorkflowEngine Hardening & Notification Template Infrastructure — 2026-09-01

### Library — WorkflowEngine Hardening

- **Initiator notification on workflow start** — [`WorkflowEngine::start()`](src/Services/Workflow/WorkflowEngine.php) now dispatches a `workflow_submitted` notification to the workflow initiator (the authenticated user who started the workflow), in addition to the existing step-approver notifications. Previously, only step approvers were notified; the initiator received no confirmation.
- **Empty recipient logging** — [`WorkflowEngine::notifyTransition()`](src/Services/Workflow/WorkflowEngine.php) now logs a warning when `$recipientIds` is empty (e.g., no users have the required role for a workflow step), preventing silent notification failures.
- **Disabled config logging** — [`WorkflowEngine::notifyTransition()`](src/Services/Workflow/WorkflowEngine.php) now logs a warning when notification config is disabled or empty for a workflow definition.

### Consuming App — Notification Template Seeding

- **4 notification template seeders invoked** — [`DatabaseSeeder`](hr-consuming-app:database/seeders/DatabaseSeeder.php) now calls all 4 template seeders: [`WorkflowNotificationTemplateSeeder`](hr-consuming-app:app/Modules/Payroll/Database/Seeders/WorkflowNotificationTemplateSeeder.php) (Payroll, 8 templates), [`EssNotificationTemplateSeeder`](hr-consuming-app:app/Modules/Hr/Database/Seeders/EssNotificationTemplateSeeder.php) (HR, 12 templates), [`LeaveWorkflowNotificationTemplateSeeder`](hr-consuming-app:app/Modules/Leave/Database/Seeders/LeaveWorkflowNotificationTemplateSeeder.php) (Leave, 8 templates), and the library's built-in [`NotificationTemplateSeeder`](src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php) (8 templates). **25 total templates** across `database` and `mail` channels.
- **Templates cover**: `workflow_submitted`, `workflow_approved`, `workflow_rejected`, `workflow_recalled`, `payslip_ready`, `leave_approved`, `leave_denied`, `leave_submitted`, `upcoming_holiday`, `clock_out_reminder`, `document_generated`, `report_ready`, and more.

### Consuming App — TemplateVariableRegistry Implementation

- **`NotificationVariableRegistry` created** — [`app/Services/NotificationVariableRegistry.php`](hr-consuming-app:app/Services/NotificationVariableRegistry.php) implements [`TemplateVariableRegistry`](src/Contracts/Notifications/TemplateVariableRegistry.php) with **19 notification types**, each declaring its available `{placeholder}` variables. Bound in [`AppServiceProvider`](hr-consuming-app:app/Providers/AppServiceProvider.php), replacing the library's [`DefaultTemplateVariableRegistry`](src/Services/Notifications/DefaultTemplateVariableRegistry.php).
- **19 types covered**: `workflow_submitted`, `workflow_approved`, `workflow_rejected`, `workflow_recalled`, `payslip_ready`, `leave_approved`, `leave_denied`, `leave_submitted`, `upcoming_holiday`, `clock_out_reminder`, `document_generated`, `report_ready`, `workflow_stage_changed`, `workflow_approval`, `workflow_denied`, `workflow_cancelled`, `user_welcome`, `user_password_reset`, and more.

### Consuming App — Module Self-Containment Fix

- **`PayrollRoleSeeder` split into module-scoped seeders** — The root-level [`database/seeders/PayrollRoleSeeder.php`](hr-consuming-app:database/seeders/PayrollRoleSeeder.php) was deleted and replaced with two module-scoped seeders: [`PayrollRoleSeeder`](hr-consuming-app:app/Modules/Payroll/Database/Seeders/PayrollRoleSeeder.php) (creates `payroll_officer` role, assigns to test user) and [`HrRoleSeeder`](hr-consuming-app:app/Modules/Hr/Database/Seeders/HrRoleSeeder.php) (creates `hr_manager` role). This ensures each module is fully self-contained — copying `app/Modules/Payroll/` into any Laravel app with the library provides everything needed.

## Per-Module UX Review & Payroll Wizard Fixes — 2026-09-01

### Library — Notification Click-to-Navigate

- **`navigateToNotification()` added to [`NotificationsIndex`](src/Http/Livewire/Notifications/NotificationsIndex.php) and [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php)** — clicking a notification now marks it as read and navigates to the related entity via `$this->redirect($url)`.
- **`resolveWorkflowableUrl()` always returns a URL** — [`WorkflowEngine`](src/Services/Workflow/WorkflowEngine.php) now falls back to `/workflows/{id}` when `workflowable_type`/`workflowable_id` is missing, ensuring every notification has a navigable URL.
- **URL not overridable by caller data** — `array_merge` order changed in `notifyTransition()` so library defaults (including `url`) take precedence.

### Library — Dashboard Hardening

- **`Dashboard::mount()` exception handling** — [`Dashboard`](src/Http/Livewire/Dashboards/Dashboard.php) now catches `InvalidArgumentException` from missing config files and renders a graceful error instead of a blank page.

### Library — Admin Module Fixes (13 issues)

- **19 explicit routes added** — [`Admin Routes`](src/Core/Admin/Routes/web.php) now has explicit routes for all sidebar links, eliminating catch-all dependency.
- **`company_id` hiddenFields contradiction fixed** — [`user.php`](src/Core/Admin/Data/user.php) `company_id` removed from `hiddenFields.onTable`, `onNewForm`, `onEditForm`.
- **4 action cards converted** — ad-hoc events (`openInviteUserModal`, `openRoleWizard`, `openPermissionWizard`, `syncPermissions`) → `openDrawer`/`navigate` in [`dashboard.php`](src/Core/Admin/Data/dashboard.php).
- **`notification_log.php` hiddenFields** — `created_at` removed from `hiddenFields.onTable`.
- **`role.php` guard_name** — `visible` changed to `true`.
- **`permission.php` fieldDefinitions** — added `name` and `guard_name` fields, enabled `addButton`.
- **2 missing views created** — `general-settings.blade.php`, `onboarding.blade.php` with explicit routes.

### Library — System Module Fixes (2 issues)

- **`openSetupWizard` → `navigate`** — 3 action cards in System dashboards converted from unhandled event to `/setup/wizard` navigation.
- **Cross-module URLs fixed** — `/admin/onboarding` → `/system/onboarding`, `/admin/tours` → `/system/tours` with new views and routes.

### Consuming App — Organization Module Fixes (2 issues)

- **11 double-prefixed URLs fixed** — `organization/organization/` → `organization/` in [`sidebar_menu.php`](hr-consuming-app:app/Modules/Organization/Config/sidebar_menu.php) and [`top_bar_menu.php`](hr-consuming-app:app/Modules/Organization/Config/top_bar_menu.php).

### Consuming App — HR Module Fixes (12 issues)

- **5 double-prefixed URLs** — `/hr/hr/` → `/hr/` in `top-nav-links.blade.php`.
- **Missing views created** — `teams.blade.php`, `tags.blade.php`, `my-documents.blade.php`, `team-calendar.blade.php`.
- **12 index routes added** — explicit routes for all data table pages.
- **`xxxxxx/` scaffold directory deleted** — 32 orphaned files removed.
- **Bottom bar malformed Blade fixed** — `}}` in class attributes + missing leading `/` on hrefs.

### Consuming App — Attendance Module Fixes (20+ issues)

- **13 hiddenFields contradictions fixed** — `company_id`, `attendance_id`, `assignable_type` removed from `hiddenFields.onTable` across 13 configs.
- **12 wrong model references** — `App\Modules\Hr\Models\*` → `App\Modules\Attendance\Models\*` in 2 dashboards.
- **6 wrong URL prefixes** — `/hr/` → `/attendance/` in `view_all_link` URLs.
- **5 ad-hoc events converted** — `openClockModal`, `openPolicyWizard`, `openWorkPatternWizard`, `openBulkPatternAssignment` → `navigate`/`openDrawer`.
- **2 missing routes added** — `adjust-attendance`, `attendance-work-sessions`.

### Consuming App — Leave Module Fixes (7 issues)

- **Denied/Rejected mismatch** — dashboard stat filters changed from `'Rejected'` to `'Denied'`.
- **2 broken URLs fixed** — `/leave/my-leave-balance` → `/leave/my-leave`, `/leave/leave-request` → `/leave/leave-requests`.
- **`openLeaveWizard` → `navigate`** — ad-hoc event converted.
- **11 missing CRUD views created** — create/show/edit placeholders for leave-types, leave-requests, leave-balances, leave-approvers.
- **3 missing index routes added**.

### Consuming App — Payroll Module Fixes (10 issues)

- **12 wrong model references** — `App\Modules\Hr\Models\*` → `App\Modules\Payroll\Models\*` in `dashboard_payroll_overview.php`.
- **10 hiddenFields contradictions fixed** — `company_id` removed from `hiddenFields.onTable` across 10 configs.
- **5 missing routes added** — `approvals`, `payroll-run-adjustments`, `employee-adjustment-profiles`, `payslip-items`, `payroll-policy-assignments`, `payroll-wizard`.
- **Route name mismatch** — `payroll.payroll-employees.edit` → `payroll.payroll-runs.edit`.
- **`PayrollExecutiveSummary` Livewire component registered**.
- **`approved_by` → `approved_by_user_id`** in hiddenFields.

### Consuming App — Holiday Module Fixes (3 issues)

- **Orphan route documented** — `/holiday/dashboard` kept but noted as not in sidebar.
- **Missing leading `/`** — context group URL fixed.
- **Empty `system_info` field group removed**.

### Consuming App — Payroll Wizard Fixes (2 issues)

- **CSRF "Page Expired"** — wizard route moved inside `web` + `auth` middleware group in [`web.php`](hr-consuming-app:app/Modules/Payroll/Routes/web.php).
- **"All Company" mode** — `?:` operator replaced with strict `!== null` check in [`PayrollRunWizard.php`](hr-consuming-app:app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunWizard.php) to preserve `company_id = 0`.

### Consuming App — Stale View Fix

- **Views republished** — `php artisan vendor:publish --tag=ui-library-views --force` to sync notification blade changes to consuming app.

## CompanyProvider Implementation (Consuming Application) — 2026-08-30

### Library

- **`CompanyProvider` implemented** in a consuming application (`App\YourModule\Providers\YourModelCompanyProvider`). The library itself ships with `NullCompanyProvider` as the default no-op — the consuming app provides the first real-world implementation.
- **Binds** in the module's own service provider — self-contained within the module, following the modular binding pattern (each module owns its contract bindings).
- **Resolves** user→company chain: `User → YourModel (by user_id) → Company (by company_id)` — no `company_id` column on the `users` table. The `CompanyProvider` contract lets each app resolve the company however it needs.
- **Enables** company switcher in TopNav via `loadCompanies()` and `switchCompany()`.

### Documentation

- **Updated** [`multi-tenancy.md`](docs/consuming-app/multi-tenancy.md) — reframed as "Company Scoping & Multi-Company" with: multi-tenancy vs multi-company distinction at the top, three-layer model reference from [`27-architecture-boundary.md`](docs/library/27-architecture-boundary.md), Mermaid sequence diagram of the session-based company scoping flow, and clarification that `users` table does not need `company_id`.
- **Created** [`multi-tenancy-vs-multi-company.md`](docs/consuming-app/multi-tenancy-vs-multi-company.md) — concise reference distinguishing database-level multi-tenancy (deployment concern) from column-level multi-company (library mechanism), with an enablement checklist.
- **Updated** [`contracts.md`](docs/consuming-app/contracts.md) §6 — CompanyProvider cookbook now shows the modular binding pattern (module ServiceProvider, not AppServiceProvider) with a generic implementation example.
- **Updated** [`consuming-app/README.md`](docs/consuming-app/README.md) — added link to new `multi-tenancy-vs-multi-company.md`.

## Workflow/Approval Foundation Polish & Consuming-App Integration — 2026-08-30

### Library — Approver Resolution

- **`WorkspaceScopedApproverResolver` created** as the new default approver resolver at [`src/Services/Approvals/WorkspaceScopedApproverResolver.php`](src/Services/Approvals/WorkspaceScopedApproverResolver.php). Resolves approvers within a single workspace scope — splits `$roleIds` into integers (pass-through user IDs) and strings (role names), queries Spatie roles by name, filters users by workspace membership via `belongsToWorkspace()`, and falls back to session/authenticated-user workspace when `$workspaceId` is null. Returns empty (not global) when no workspace-scoped approvers exist — safe default prevents cross-workspace approval leakage.
- **`config/ui-library.php`** — `approvals.approver_resolver` default changed from [`DefaultApproverResolver`](src/Services/Approvals/DefaultApproverResolver.php) to [`WorkspaceScopedApproverResolver`](src/Services/Approvals/WorkspaceScopedApproverResolver.php). The old `DefaultApproverResolver` is still available for single-tenant apps that want global role resolution.
- **`UILibraryServiceProvider.php`** — added explicit [`ApprovalGuard`](src/Services/Approvals/ApprovalGuard.php) singleton binding so consuming apps can resolve it from the container.

### Library — Approval UI Components (Constructor → Boot Refactor)

- **`ApprovalRequestListView.php`** — removed `__construct()`, moved all dependency resolution to `boot()`. Added `selectWorkflow()` method that redirects to the workflowable entity's detail page instead of rendering inline. This follows the Livewire best practice of avoiding constructor injection for Livewire components.
- **`ApprovalActions.php`** — removed `__construct()`, moved dependency resolution to `boot()`.
- **`ApprovalHistoryTimeline.php`** — removed `__construct()`, moved dependency resolution to `boot()`.

### Library — Blade & Config Fixes

- **`sidebar-item.blade.php`** — `$item['key']` → `$item['modelName'] ?? $item['key']` fallback. When a navigation item has a `modelName` (e.g., for DataTable config resolution), it is used as the sidebar key; otherwise falls back to the item key.
- **`Admin navigation.php`** — added `modelName` keys to Notifications context items (`notifications` and `notification-preferences`) so the sidebar correctly resolves their DataTable configs.
- **`Admin notifications.blade.php`** + **`notification-preferences.blade.php`** — removed incorrect `configKey` prop that was causing DataTable resolution failures.
- **`DefaultAuthorizationProvider.php`** — `evaluateConditions()` now handles array values with `in_array()` in addition to scalar `===` comparison. This fixes authorization checks where a condition value is an array (e.g., multi-select field values).

### Consuming App — HR Module

- **`HrsCompanyProvider`** created in `app/Modules/Hr/Providers/` — implements [`CompanyProvider`](src/Contracts/Navigation/CompanyProvider.php) to resolve the user→company chain through HR domain models. Bound in `HrsServiceProvider::register()`.
- **`HrsApproverResolver`** created in `app/Modules/Hr/Providers/` — a consuming-app-specific override of [`ApproverResolver`](src/Contracts/Approvals/ApproverResolver.php) for cases where the library's default `WorkspaceScopedApproverResolver` doesn't match the app's user model (e.g., when `company_id` is not on the `users` table). Bound in `HrsServiceProvider::register()`.

### Consuming App — Payroll Module

- **Payroll `approvals.blade.php`** created — a dedicated approvals page for the Payroll module with [`ApprovalRequestListView`](src/Http/Livewire/Approvals/ApprovalRequestListView.php) filtered to `definition-key="payroll_run"`. Navigation config updated with a "Payroll Approvals" sidebar item.
- **`WorkflowNotificationTemplateSeeder`** created — seeds the four `workflow_*` notification templates (`workflow_submitted`, `workflow_approved`, `workflow_rejected`, `workflow_recalled`) for `database` and `mail` channels.
- **PayrollRun detail page** updated — embedded [`ApprovalActions`](src/Http/Livewire/Approvals/ApprovalActions.php) and [`ApprovalHistoryTimeline`](src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) components for workflow-driven approve/reject/recall UI and step-by-step progress visualization.
- **Stale overrides deleted** — removed vendor-published `top-nav.blade.php` and `sidebar-item.blade.php` overrides that were masking library updates.
- **Payroll `show.blade.php` config key fix** — corrected a corrupted config key (`\u0001` → `payroll`) that was causing DataTable resolution failures.

### Documentation

- **Updated** [`20-reference-workspace-scoped-approver-resolver.md`](docs/consuming-app/20-reference-workspace-scoped-approver-resolver.md) — reframed from "how to write your own" to "the library now ships this as default; override only if your User model differs."
- **Updated** [`contracts.md`](docs/consuming-app/contracts.md) §8 — noted the new `WorkspaceScopedApproverResolver` default and fixed the incorrect `resolveApprovers()` method signature to match the actual [`ApproverResolver::resolve()`](src/Contracts/Approvals/ApproverResolver.php) contract.
- **Updated** [`08-contracts-and-interfaces.md`](docs/library/08-contracts-and-interfaces.md) — added missing `ApproverResolver` and `ApproverLabelResolver` contract sections with full signatures and default implementation references.
- **Updated** [`18-workflow-approval-testing-checklist.md`](docs/consuming-app/18-workflow-approval-testing-checklist.md) — §2.3 and §4.1 updated to reference `WorkspaceScopedApproverResolver` as the new default; clarified that consuming apps only need a custom resolver when their User model doesn't have `company_id`.
- **Updated** [`payroll-approval-implementation-plan.md`](plans/payroll-approval-implementation-plan.md) — marked completed items with implementation notes on what was actually built vs planned.

## DataTable Runtime Bridges, Drawer Decoupled Pattern & Cross-Module Cleanup — 2026-08-27

### Library

- **Drawer decoupled pattern**: `wire:click="$dispatch('openDrawer', {component, params, title})"` — the Drawer listens for `openDrawer`/`closeDrawer`/`formSaved`, so buttons can open a form drawer without embedding a `<livewire:qf.drawer>` wrapper.
- **View publish path fix**: `ui-library-views` publishes to `resources/views/vendor/qf/`, and `ui-library-core-views` publishes core module (Admin/System) views to `resources/views/vendor/qf-core/{module}/`.
- **Row-actions Show link**: now uses `getShowUrl()`, carrying pagination/filter/sort context to detail pages.
- **`?filter[]` bridge**: `?filter[field]=value` (implies `=`) and `?filter[field][operator]=value` with operators `=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `not like`, `between`; relative dates (`today`, `+N days`, `-N days`) resolved for `datepicker`/`datetimepicker` fields.
- **`?hiddenFields[]` bridge**: `?hiddenFields[onTable][]=field` hides columns/fields via query string.
- **Dynamic page title**: `:page-title` prop on DataTable; the blade `@script` sets `document.title` and the heading.
- **`view_all_link_target`**: List widget config key (`_self`/`_blank`); `rel="noopener noreferrer"` is added for `_blank`.
- **`between` operator fix**: FilterService routes `between` through `whereBetween()`.

### Consuming-App Patterns

- **Cross-module config keys**: after a module split, use the owning module prefix (e.g. `payroll.payroll_payslip`, not `hr.payroll_payslip`).
- **Cross-module model FQCNs**: Eloquent relationships and config `model` values must use the owning module namespace (e.g. `App\Modules\YourModule\Models\YourModel`, not a stale reference to a pre-split namespace).
- **Dashboard "View all" link pattern**: `?filter[employee_id]={{ employee_id }}&hiddenFields[onTable][]=employee_id` with `view_all_link_target: '_blank'` and an `employee_id` dashboard parameter.
- **Parent refresh after Drawer save**: `formSaved` listener pattern (`'formSaved' => 'refreshEmployee'`).
- **Self-service edit suppression**: `@if($this->canEdit())` guard pattern for edit buttons in self-service mode.

## My Profile, Cross-Module Namespace Cleanup & Payroll Fixes — 2026-08-28

### Library

- **PolicyCalculationBuilder JSON key alignment**: `DataTableForm::validatePolicyCalculationLogic()` reads `individual_value` and `organization_value` from the JSON. The consuming app's `PolicyCalculationBuilder::buildJson()` now emits these keys alongside the legacy `employee_value`/`employer_value` keys.

### Consuming App

- **My Profile feature**: New `my-profile.blade.php` resolves current user's employee and mounts `qf.employee-detail` in self-service mode. Added to sidebar "My Portal" context group and user dropdown.
- **Cross-module view namespace cleanup**: Payroll module: 16 stale `hr::livewire.payroll.*` → `payroll::livewire.payroll.*` references fixed. Hr module: 4 stale `hr::attendance-*` → `attendance::attendance-*` route references fixed. All modules scanned, zero remaining stale cross-module references.
- **Payroll URL prefix fixes**: 10 `/hr/payroll-*` URLs corrected to `/payroll/*` across 6 files (wizard, runs, payslips).
- **Payroll routes middleware fix**: CRUD routes wrapped in `Route::middleware(['web', 'auth'])` to fix 403 Unauthenticated for authenticated users.
- **CompanyProvider analysis**: Documented that the company switcher requires a real `CompanyProvider` implementation (currently `NullCompanyProvider`). User→model→Company relationship chain documented.
- **Top-nav quick actions fix**: Stale `top-nav.blade.php` vendor override updated with Quick Actions UI (command palette + ranked dropdown).

## Per-Instance DataTable Overrides, prefill[] Convention & Drawer Polish — 2026-08-27

### Library

- **Per-instance `crudType`, `simpleActions`, `moreActions` overrides**: `DataTable` now accepts `$crudType`, `$simpleActions`, `$moreActions` mount params (null defaults = use config). `render()` resolves override first, falls back to config. Properties set before blade renders. Source: [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:51).
- **`prefill[]` query-parameter convention**: `?prefill[field_name]=value` pre-fills Add form fields. The page-header merges `request()->query('prefill', [])` into `prefilledData`. Source: [`page-header.blade.php`](src/Resources/views/components/layouts/partials/page-header.blade.php:83).
- **Drawer inline form save behavior**: When `$inline = true` (Drawer context), `DataTableForm::save()` skips the alert dispatch and does NOT redirect after save. The Drawer closes via `formSaved` event. Source: [`DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php:849,860).
- **Drawer Discard Changes**: Dispatches `closeDrawer` instead of navigating, closing the Drawer without a page change.
- **Drawer close animation**: `Drawer::close()` dispatches `drawerClosed` (triggers `bsDrawer.hide()` with animation). Content clears after animation via `drawerHidden` → `cleanup()`. No self-referential `closeDrawer` dispatch. Source: [`Drawer.php`](src/Http/Livewire/Drawer.php:46), [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php:307,323).
- **Document model**: Auto-sets `documentable_type`, `file_path`, `file_name`, `uploaded_at` on upload.

### Consuming-App Patterns

- **Per-instance overrides**: `@livewire('qf.data-table', ['configKey' => '...', 'crudType' => 'drawers'])` for embedded tables; `simpleActions` to suppress Edit/Delete per instance.
- **Prefill convention**: `?prefill[employee_id]=42` pre-selects employee #42 in the Add form drawer.

## [Module UX Polish — 2026-08-19]

### Context Group Splits
- HR: People → People + Manage (operational vs administrative)
- Payroll: → Processing + Configuration
- Attendance: Time → Time + Scheduling + Policies
- Leave: → Requests + Configuration

### Dashboards
- Aligned all 6 modules to the Admin module's per-context-group overview dashboard pattern
- Enriched overall dashboards with stat cards, charts, trends, lists, grouped lists, and action cards
- Payroll: 19 widgets; Attendance: 18 widgets; Leave + Holiday: enriched
- Organization: enriched general dashboard with 12 stat cards, 3 charts, 3 trends, 7 lists, 6 action cards — matching Payroll/Attendance/HR richness

### Named Routes
- Added {module}.dashboard named routes for all 6 modules
- Added per-context-group overview dashboard routes

### Fixes
- Fixed manage-context sidebar items redirecting to people context
- Fixed leave-types missing index view + route
- Fixed holiday dashboard calendar_id column reference
- Fixed duplicate Organization migrations
- Fixed duplicate "Dashboard" tab in Organization top nav — library's [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) had an unconditional hardcoded Dashboard tab colliding with Organization's `dashboard` context group; added `@if (!isset($this->items['dashboard']))` guard
- Fixed 404s on Organization Reports sidebar links (`/organization/reports/{companies,departments,locations,growth}`) — added 4 routes + 4 views in consuming app
- Fixed 404s on Audit context group pages (Activity Log, Login History, System Events, Exports) — created 4 minimal views in [`src/Core/Admin/Resources/views/admin/`](src/Core/Admin/Resources/views/admin/) in the library

### Organization Report Views
- Converted 4 Organization report Blade views from full dashboard renders to minimal placeholder pages matching the audit-view pattern, with per-page headings and future-implementation descriptions

### Documentation
- Created [`docs/project/organization-reports-future-differentiation.md`](docs/project/organization-reports-future-differentiation.md) documenting the 4 report pages as placeholders with per-page future intent

### Mobile Navigation & Permission Guards
- Added `canAccessView()` permission checks to mobile/overflow inline rendering in [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) — mobile "More" dropdown and overflow tabs now respect the same permission model as desktop tabs
- Added case-sensitivity guard for `'Dashboard'` context key in [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) — the hardcoded Dashboard tab guard now uses exact-case `'Dashboard'` matching to prevent false duplicates when a module defines a `dashboard` (lowercase) context group

### Admin Navigation — Context Group Split
- Split "Users & Permissions" (8 items) into three context groups:
  - **Users** (5 items): Overview, Users, Invitations, User Groups, User Preferences
  - **Access** (3 items): Roles, Access Control, Sessions
  - **Security** — Sessions moved out, now focused on 2FA, API tokens, audit policy
- Created analysis doc at [`docs/project/admin-navigation-context-group-split.md`](docs/project/admin-navigation-context-group-split.md)
- Fixed Admin Dashboard link: `admin/dashboard-overview` → `admin/dashboard`
- Fixed Admin Dashboard active-state: `context="dashboard"` → `context="Dashboard"` in blade view for case-sensitive context matching

### Admin Sidebar 404 Audit
- Audited all Admin sidebar links for missing views and routes
- Created 16 placeholder Blade views for missing pages across Users, Access, Security, Audit, and Settings context groups
- Added 10 missing routes for admin pages
- Created catalog at [`docs/project/admin-placeholder-pages.md`](docs/project/admin-placeholder-pages.md) documenting all placeholder views, their routes, and future implementation intent

### Dashboard Title Standardization
- Created naming standard at [`docs/project/module-dashboard-naming-standard.md`](docs/project/module-dashboard-naming-standard.md) — all general dashboard titles follow `"{Module} Dashboard"` pattern
- Standardized 4 module dashboard titles: Organization (`"Organization Dashboard"`), HR (`"HR Dashboard"`), Leave (`"Leave Dashboard"`), Admin (`"Admin Dashboard"`)

### Drawer Integration
- Created analysis at [`docs/project/dashboard-drawer-integration-analysis.md`](docs/project/dashboard-drawer-integration-analysis.md) — cataloged 93 action cards across 8 modules, identified 22 "Easy Win" drawer candidates
- Converted 23 dashboard action cards from `navigate` events to `openDrawer` events across Organization, HR, Leave, Holiday, and Attendance modules
- Fixed [`action_card.blade.php`](src/Resources/views/widgets/action_card.blade.php): changed `wire:click` to `$dispatch` for `openDrawer` events — ensures drawer parameters (component, params, title) are passed as named arguments rather than a single JSON object
- **Task AL** — Converted 21 "Possible" action cards from `navigate` + `url` to `openDrawer` events; changed 13 data table configs from `pages`/`modals` to `drawers` crudType. 2 cards excluded (custom wizards). ⚠️ Some converted cards still do not open the drawer — likely causes: missing `crudType` on the data table config, incorrect `configKey`, or the entity's form doesn't support drawer rendering. Needs investigation per card (tracked in [`navigation-ux-backlog.md`](docs/project/navigation-ux-backlog.md)).

### Quick Actions Design
- Created feature design doc at [`docs/project/quick-actions-feature-design.md`](docs/project/quick-actions-feature-design.md) — full specification for a Cmd+K command palette, top-nav ⚡ button, dashboard widget, and per-module action registration system

### Quick Actions (Command Palette) — Phase 1 MVP
- **Task V**: Command palette MVP — new [`ActionRegistry`](src/Services/QuickActions/ActionRegistry.php) service discovers quick-actions configs from Core and business modules via the same 3-tier priority chain as NavigationManager; [`QuickActionsPanel`](src/Http/Livewire/QuickActions/QuickActionsPanel.php) Livewire component with modal overlay, server-side search, and 3 action types (`navigate`, `event`, `drawer`); [`quick-actions-panel.blade.php`](src/Resources/views/livewire/quick-actions/quick-actions-panel.blade.php) Blade view with inline scoped CSS and category-grouped results; [`quick-actions.js`](public/assets/js/quick-actions.js) vanilla JS with Cmd+K/Ctrl+K global listener, client-side `include`/`match` filtering, arrow key navigation, Enter to select, Escape to close, Livewire re-render survival; [`quick-actions.php`](src/Core/Admin/Config/quick-actions.php) library config with 8 admin actions; modifications to [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php) (singleton binding + `qf.quick-actions-panel` Livewire registration), [`ui-library.php`](src/Config/ui-library.php) (new `quick_actions` config section with `command_palette`, `top_nav_button`, `tracking`, `ranking` keys), [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) (`loadQuickActionsConfig()` + `openQuickActions()` + `quickActionsEnabled`/`quickActionsIcon`/`quickActionsTitle` properties), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) (search button in right-side icon area), [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) (conditional panel include + JS asset reference)
- **Task W**: [`ActionRegistry::normalizeActions()`](src/Services/QuickActions/ActionRegistry.php:170) closure fix — replaced `array_map` with `foreach` to avoid scope issues with `$this` inside closures
- **Task X**: Panel single-root fix — wrapped the command palette blade content in a single `<div>` root element to satisfy Livewire's single-root requirement
- **Task Y**: Panel action loading fix — changed [`QuickActionsPanel::loadActions()`](src/Http/Livewire/QuickActions/QuickActionsPanel.php:80) from `authorizedFor()` to `all()` so all registered actions load into the JS data attribute for client-side filtering; authorization is still enforced server-side when executing
- **Task Z**: Quick actions registered for all 7 remaining modules (System, Organization, HR, Attendance, Leave, Payroll, Holiday) — 40 new actions across consuming-app modules (6 System, 6 Organization, 6 HR, 6 Attendance, 6 Leave, 6 Payroll, 4 Holiday), 48 total across library + consuming app
- **Task AA**: URL path fallback fix — [`QuickActionsPanel::resolveActionUrl()`](src/Http/Livewire/QuickActions/QuickActionsPanel.php:213) now handles both named routes (via `route()`) and URL paths (catches `Exception` for non-named routes, falls back to `url()` when the `route` value starts with `/`)

### Quick Actions (Tracking + Ranking) — Task AE (2026-08-20)

Phase 2 of the Quick Actions feature — personalized ranking driven by usage tracking.

- **New migration** [`2026_08_20_000001_create_user_action_histories_table.php`](Database/Migrations/2026_08_20_000001_create_user_action_histories_table.php) — `user_action_histories` table (`id`, `user_id` FK cascade, `action_id`, nullable `executed_at`, timestamps, `[user_id, action_id]` index)
- **New model** [`UserActionHistory`](src/Models/UserActionHistory.php) — fillable `user_id`/`action_id`/`executed_at`; `user()` relation resolves the user model via `config('ui-library.user.model')`
- **New service** [`ActionTracker`](src/Services/QuickActions/ActionTracker.php) — `record($actionId, $userId = null)` inserts one history row per palette execution, gated by `ui-library.quick_actions.tracking.enabled`
- **New service** [`RankingEngine`](src/Services/QuickActions/RankingEngine.php) — `score($actions, $userId = null)` orders actions by `score = 0.6 × recency_factor + 0.4 × frequency_factor`; `recency_factor = exp(-days_since / 7)` (half-life), `frequency_factor = 1 - exp(-count / 5)` (saturation). Never-executed actions score 0 and fall to the bottom but still appear (stable sort preserves original order on ties)
- **Modified** [`QuickActionsPanel`](src/Http/Livewire/QuickActions/QuickActionsPanel.php) — `loadActions()` runs actions through `RankingEngine` when authenticated; `executeAction()` records each execution via `ActionTracker`
- **Modified** [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php) — registered `ActionTracker` + `RankingEngine` singletons
- **Modified** [`ui-library.php`](src/Config/ui-library.php) — `tracking` + `ranking` config defaults (`recency_weight` 0.6, `frequency_weight` 0.4, `half_life_days` 7, `frequency_saturation` 5)

### Quick Actions (⚡ Button + Dashboard Widget) — Task AG (2026-08-20)

Phase 3 of the Quick Actions feature — top-nav ⚡ button dropdown + dashboard widget, completing the feature's core UX entry points.

- **New widget processor** [`QuickActionsWidgetProcessor`](src/Widgets/QuickActionsWidgetProcessor.php) — returns `type: 'quick_actions'` with the current user's top-ranked frequent actions from [`RankingEngine`](src/Services/QuickActions/RankingEngine.php)
- **New widget view** [`quick_actions.blade.php`](src/Resources/views/widgets/quick_actions.blade.php) — card widget listing ranked action rows (clickable, executes the action)
- **Modified** [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) — added ⚡ button properties (`quickActionsEnabled`, `quickActionsIcon`, `quickActionsTitle`), `loadQuickActions()` method that fetches top-ranked actions from `RankingEngine`, `executeQuickAction()` method, and `execute-quick-action` listener
- **Modified** [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) — ⚡ dropdown button in right-side icon area with top N ranked actions and "More actions…" link to the full command palette
- **Modified** [`WidgetProcessor`](src/Services/Widgets/WidgetProcessor.php) — registered `'quick_actions' => QuickActionsWidgetProcessor::class` in `$map`
- **Modified** [`ui-library.php`](src/Config/ui-library.php) — ⚡ button + command palette config defaults

### Quick Actions (Phase 4 — Favorites, Shortcuts, Analytics) — Task AI (2026-08-20)

Phase 4 of the Quick Actions feature — user favorites/pinning, keyboard shortcut badges, analytics, and first-visit discoverability.

- **New migration** [`2026_08_20_000002_create_user_favorite_actions_table.php`](Database/Migrations/2026_08_20_000002_create_user_favorite_actions_table.php) — `user_favorite_actions` table (`id`, `user_id` FK cascade, `action_id`, timestamps, `[user_id, action_id]` unique index)
- **New model** [`UserFavoriteAction`](src/Models/UserFavoriteAction.php) — fillable `user_id`/`action_id`; `user()` relation resolves via `config('ui-library.user.model')`
- **New partial** [`action-item.blade.php`](src/Resources/views/livewire/quick-actions/partials/action-item.blade.php) — reusable action row with star toggle, shortcut badge, icon, label, and category
- **Modified** [`QuickActionsPanel.php`](src/Http/Livewire/QuickActions/QuickActionsPanel.php) — `toggleFavorite()` method, `$favoriteActionIds` computed property, pinned actions always appear at top regardless of ranking score; `executeAction()` records execution via `ActionTracker`
- **Modified** [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) — `loadQuickActions()` now respects favorites (pinned actions float to top of ⚡ dropdown)
- **Modified** [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) — ⚡ button gains first-visit pulse animation (CSS keyframe + `hasSeenQuickActions` session flag)
- **Modified** [`QuickActionsWidgetProcessor.php`](src/Widgets/QuickActionsWidgetProcessor.php) — widget now shows pinned actions first, then ranked frequent actions
- **Modified** [`quick_actions.blade.php`](src/Resources/views/widgets/quick_actions.blade.php) — star toggle + shortcut badges in widget rows
- **Modified** [`quicker-faster.js`](public/assets/js/quicker-faster.js) — first-visit pulse animation trigger on ⚡ button
- **Modified** [`ui-library.php`](src/Config/ui-library.php) — `favorites` config key (`enabled`, `max_pinned`)

### Keyboard Shortcut Fixes — Task AJ (2026-08-20)

Attempted to fix sidebar search shortcut and Cmd+1..9 browser conflicts. Changes made but **not yet working** — likely stale JS asset cache in the consuming app.

- **Modified** [`quicker-faster.js`](public/assets/js/quicker-faster.js) — changed sidebar filter shortcut from `Cmd+K` to `Cmd+Shift+K` to avoid conflict with the command palette
- **Modified** [`quick-actions.js`](public/assets/js/quick-actions.js) — changed quick launch shortcuts from `Cmd+1..9` to `Cmd+Shift+1..9` to avoid browser tab-switching conflicts
- **Modified** [`QuickActionsPanel.php`](src/Http/Livewire/QuickActions/QuickActionsPanel.php) — updated shortcut badges from `⌘1` to `⌘⇧1`
- **Modified** [`quick-actions-panel.blade.php`](src/Resources/views/livewire/quick-actions/quick-actions-panel.blade.php) — updated footer hint to show `⌘⇧1`–`⌘⇧9`
- **Re-published** JS assets to the consuming app's `public/vendor/ui-library/` directory

**⚠️ Known issues (moved to backlog):**
1. `Cmd+Shift+K` still opens quick actions instead of sidebar search — sidebar filter shortcut not working
2. `Cmd+Shift+1..9` do nothing — quick launch shortcuts don't trigger
3. Likely root cause: stale JS asset cache in the consuming app; may need hard cache-busting or browser cache clear

### P0 Quick Wins — Task AC (2026-08-20)

- **Admin case-sensitivity normalized** — normalized `'Dashboard'` → `'dashboard'` across:
  - [`src/Core/Admin/Config/navigation.php`](src/Core/Admin/Config/navigation.php) (`context_groups` key + `contexts`)
  - 6 Blade views ([`dashboard`](src/Core/Admin/Resources/views/admin/dashboard.blade.php), [`dashboard-overview`](src/Core/Admin/Resources/views/admin/dashboard-overview.blade.php), and 4 `dashboard/*` sub-views)
  - [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) — hardcoded Dashboard tab guard simplified to a single `!isset($this->items['dashboard'])` check
- **Admin dashboard 404s fixed** — added explicit named routes to [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php) for `admin/dashboard-overview` and `admin/dashboard-security-overview`
- **System quick-actions config confirmed** — the System module's quick-actions config already exists with 5 actions (no change needed)

### Employee Self Service (ESS) — Phase 1: My Portal Dashboard + Navigation Foundation — Task AQ (2026-08-20)

ESS Phase 1 delivers the "My Portal" employee dashboard — a unified, role-gated landing page that aggregates employee-scoped widgets from multiple modules. This is the foundation for the full ESS feature set (Phases 2–4 outlined in [`employee-self-service-design.md`](docs/project/employee-self-service-design.md)).

**New files (consuming app):**
- [`dashboard_my_portal.php`](hr-consuming-app:app/Modules/Hr/Data/dashboards/dashboard_my_portal.php) — 11 widgets: 1× `profile_header` (employee photo, name, department, manager, hire date), 4× `stat` (Leave Balance, Hours This Week, Pending Approvals, Upcoming Holidays), 4× `action_card` (Request Leave, Clock In/Out, View Payslip, Update My Info), 1× `activity_log` (Recent Activity feed), 1× `quick_actions` (Frequent Actions widget). Roles gated to `employee, manager`.
- [`my-portal.blade.php`](hr-consuming-app:app/Modules/Hr/Resources/views/hr/my-portal.blade.php) — Blade view with `<x-qf::navigation-layout context="my-portal">`

**Modified files (consuming app):**
- [`navigation.php`](hr-consuming-app:app/Modules/Hr/Config/navigation.php) — added `my-portal` context group (order 1) with `roles: ['employee', 'manager']` and 6 context items: Overview, My Leave, My Attendance, My Payslips, My Account, My Preferences
- [`web.php`](hr-consuming-app:app/Modules/Hr/Routes/web.php) — added `GET /hr/my-portal` → `hr.dashboard-my-portal-overview`
- [`quick-actions.php`](hr-consuming-app:app/Modules/Hr/Config/quick-actions.php) — added 7 employee-scoped quick actions under "Self Service" category: Request Leave, Clock In, View Latest Payslip, Update My Info, View My Schedule, My Documents, My Preferences

**No library changes required** — all infrastructure (dashboard widget grid, navigation layout, quick actions, widget processors) already exists in the library.

**Design doc**: [`docs/project/employee-self-service-design.md`](docs/project/employee-self-service-design.md) §4–§5

### Employee Self Service (ESS) — Phase 2: Employee-Scoped Views — Task AS (2026-08-20)

ESS Phase 2 delivers employee-scoped "my-*" views in each domain module, accessible from the My Portal sidebar.

**New files (consuming app):**
- [`my-leave.blade.php`](hr-consuming-app:app/Modules/Leave/Resources/views/leave/my-leave.blade.php) — data table of employee's leave requests filtered by `employee_id`, with "New Request" button linking to leave wizard
- [`my-attendance.blade.php`](hr-consuming-app:app/Modules/Attendance/Resources/views/attendance/my-attendance.blade.php) — data table of employee's attendance records + clock events, with Clock In/Out button
- [`my-payslips.blade.php`](hr-consuming-app:app/Modules/Payroll/Resources/views/payroll/my-payslips.blade.php) — data table of employee's payslips with View/Download actions

**Modified files (consuming app):**
- [`navigation.php`](hr-consuming-app:app/Modules/Hr/Config/navigation.php) — 3 sidebar items (My Leave, My Attendance, My Payslips) in `my-portal` context group now resolve to the new views
- [`web.php`](hr-consuming-app:app/Modules/Leave/Routes/web.php) — added `GET /leave/my-leave` → `leave.my-leave`
- [`web.php`](hr-consuming-app:app/Modules/Attendance/Routes/web.php) — added `GET /attendance/my-attendance` → `attendance.my-attendance`
- [`web.php`](hr-consuming-app:app/Modules/Payroll/Routes/web.php) — added `GET /payroll/my-payslips` → `payroll.my-payslips`

**No library changes required.**

### Employee Self Service (ESS) — Phase 3: Interactive Features — Task AT (2026-08-20)

ESS Phase 3 delivers interactive ESS features: clock in/out toggle, leave request wizard wiring, and payslip download.

**New files (library):**
- [`ClockEventRecorder.php`](src/Contracts/Attendance/ClockEventRecorder.php) — library contract defining `clockIn(int $employeeId): ClockEvent` and `clockOut(int $employeeId): ClockEvent` interfaces
- [`ClockInOut.php`](src/Http/Livewire/Widgets/ClockInOut.php) — standalone Livewire component that queries latest `ClockEvent`, shows current status ("Clocked In since 8:00 AM" or "Not clocked in"), provides a large toggle button, dispatches `clockIn()`/`clockOut()` methods, emits `clockEventRecorded` event
- [`clock-in-out.blade.php`](src/Resources/views/livewire/widgets/clock-in-out.blade.php) — Blade view with prominent button, status text, and last-event timestamp

**New files (consuming app):**
- `ClockEventRecorderService.php` — consuming-app service implementing the `ClockEventRecorder` contract, creates `ClockEvent` records via the module's model

**Modified files (consuming app):**
- Module service provider — binds `ClockEventRecorder` contract to the consuming app's implementation
- [`dashboard_my_portal.php`](hr-consuming-app:app/Modules/Hr/Data/dashboards/dashboard_my_portal.php) — Clock In/Out action_card now opens `qf.clock-in-out` in a drawer; `ClockInOut` component renders above the dashboard widget grid
- [`my-portal.blade.php`](hr-consuming-app:app/Modules/Hr/Resources/views/hr/my-portal.blade.php) — includes the `ClockInOut` Livewire component above the dashboard

**Library change required**: `ClockEventRecorder` contract + `ClockInOut` Livewire component. This is the only library change needed across all ESS phases.

### Employee Self Service (ESS) — Phase 4: Notifications & Polish — Task AT (2026-08-20)

ESS Phase 4 delivers proactive notifications, the "Team Who's Out" widget, and cross-module dashboard aggregation.

**New files (library):**
- [`TeamWhoIsOutWidgetProcessor.php`](src/Widgets/TeamWhoIsOutWidgetProcessor.php) — queries approved leave requests overlapping today, filtered by the employee's department/team; returns a list of colleagues with leave type and dates
- [`team_whos_out.blade.php`](src/Resources/views/widgets/team_whos_out.blade.php) — renders a compact list with avatars, names, and leave dates
- [`CompositeDashboardResolver.php`](src/Services/Config/Dashboards/CompositeDashboardResolver.php) — accepts multiple dashboard config keys, merges their widget definitions, resolves placeholders across all modules, and returns a unified widget array

**Modified files (library):**
- [`WidgetProcessor.php`](src/Services/Widgets/WidgetProcessor.php) — registered `'team_whos_out' => TeamWhoIsOutWidgetProcessor::class` in `$map`

**New files (consuming app):**
- [`EssNotificationTemplateSeeder.php`](hr-consuming-app:app/Modules/Hr/Database/Seeders/EssNotificationTemplateSeeder.php) — seeds 12 notification templates: `payslip_ready` (Payroll), `leave_approved`/`leave_denied`/`leave_submitted` (Leave), `upcoming_holiday` (Holiday), `clock_out_reminder` (Attendance), plus 6 additional ESS event templates

**Modified files (consuming app):**
- [`dashboard_my_portal.php`](hr-consuming-app:app/Modules/Hr/Data/dashboards/dashboard_my_portal.php) — added `team_whos_out` widget showing colleagues on leave today

**Design doc**: [`docs/project/employee-self-service-design.md`](docs/project/employee-self-service-design.md) — all 4 phases now complete.

### Sidebar Link 404 Resolution — Task AW (2026-08-20)

Completed the sidebar link audit fix: all 43 previously-404 sidebar links across System (34 URLs) and Organization (4 URLs + 3 dashboard sub-pages + 2 additional) now return 200.

**System Module — 30 placeholder views + 9 routes:**
- Created placeholder Blade views under [`src/Core/System/Resources/views/system/`](src/Core/System/Resources/views/system/) covering Dashboard (5 views: overview, platform-health, recent-activity, usage-statistics, notifications), Accounts (6 views: overview, accounts, account-groups, account-statuses, invitations, account-activity), Subscriptions (7 views: overview, subscriptions, trials, renewals, invoices, payments, subscription-history), Plans (6 views: overview, plans, features, limits, pricing, promotions), Applications (6 views: overview, installed-applications, marketplace, dependencies, versions, updates), System Settings (8 sub-views: branding, localization, email, notifications, storage, security, backups, system-logs), and Setup (1 view: wizard)
- Added 9 named routes in [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php)
- System module: 2→36 URLs now return 200; 0 remain as 404

**Organization Module — 4 placeholder views + 4 routes (consuming app):**
- Created placeholder views for `organization/dashboard/{organization-summary,growth,recent-changes}` and `organization/organization-chart`
- Added 4 routes in the consuming app's Organization routes file
- Organization module: 14→18 URLs now return 200; 0 remain as 404

**Post-resolution audit**: 100 URLs return 200, 52 return 403 (permission-restricted), **0 return 404**. Updated [`docs/project/sidebar-link-audit.md`](docs/project/sidebar-link-audit.md) with resolution details and final per-module breakdown.

**No library API changes** — all placeholder views follow the existing minimal "Coming Soon" pattern established in Admin sidebar audit work.

### Component Restoration — Task BE (2026-08-20)

Restored the `qf.employee-detail` Livewire component from a backup of the original HR module.

- **New files (consuming app):**
  - [`EmployeeDetail.php`](hr-consuming-app:app/Modules/Hr/Http/Livewire/EmployeeDetail.php) — displays employee detail information
  - [`employee-detail.blade.php`](hr-consuming-app:app/Modules/Hr/Resources/views/livewire/employee-detail.blade.php) — Blade view for employee detail
- **Modified files (consuming app):**
  - Module service provider — registered `qf.employee-detail` Livewire component

### Component Restoration — Task BF (2026-08-20)

Restored the `qf.searchable-employee-dropdown` Livewire component and conducted a full audit of all Payroll module Livewire components.

- **New files (consuming app):**
  - [`SearchableEmployeeDropdown.php`](hr-consuming-app:app/Modules/Hr/Http/Livewire/SearchableEmployeeDropdown.php) — searchable employee dropdown with server-side filtering
  - [`searchable-employee-dropdown.blade.php`](hr-consuming-app:app/Modules/Hr/Resources/views/livewire/searchable-employee-dropdown.blade.php) — Blade view for the dropdown
- **Component audit**: Discovered and registered 6 missing Payroll Livewire components in [`PayrollServiceProvider.php`](hr-consuming-app:app/Modules/Payroll/Providers/PayrollServiceProvider.php). Skipped `TaxBandsRepeater` (no usage found).
- **Total restored components**: 8 across both tasks (2 HR + 6 Payroll)

### Bug Fix: `/employees/1` 404 — Task BG (2026-08-20)

Fixed two issues causing a 404 on employee detail pages (`/employees/{id}`).

- **Library**: [`ResolvesModels.php`](src/Concerns/ResolvesModels.php) — added a `$companyId === 0` guard to skip the `WHERE company_id` clause when no company is set, preventing the company-scoping query from filtering out records when company context is absent
- **Consuming app**: [`EmployeeDetail.php`](hr-consuming-app:app/Modules/Hr/Http/Livewire/EmployeeDetail.php) — fixed config key from `hr.employee_payroll_profile` to `payroll.employee_payroll_profile` (the config lives in the Payroll module after the HR split)

---

## 2026-08-19 — HR Navigation: People + Manage Split & Dashboard Pattern Alignment

### HR People → People + Manage Context Group Split
- Split the HR `people` context group (10 items) into two context groups:
  - **People** (6 items, daily use): Overview, Employees, Profiles, Current Jobs, Teams, Employee Groups
  - **Manage** (4 items, occasional/rare): Job Titles, Tags, Job History, Documents
- Added the `manage` context group alongside the existing `Organization` and `people` groups in `app/Modules/Hr/Config/navigation.php`.
- Added the `hr.dashboard-manage-overview` named route.

### Per-Context-Group Overview Dashboards
- Added the HR Manage overview dashboard — `app/Modules/Hr/Resources/views/dashboard-manage-overview.blade.php` + `app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php` — following the Admin module's per-context-group overview pattern.
- Confirmed all six business modules expose overview dashboards whose context-group headers point at their own dashboard view.

### Named Dashboard Routes
- All six business modules (Organization, HR, Attendance, Leave, Payroll, Holiday) now expose named overview-dashboard routes (`{module}.dashboard-{group}-overview`).

### Navigation Labels & Reordering
- Normalized navigation labels and reordered HR context items so daily-use People items precede the occasional/rare Manage items.

### Missing Dashboard Views
- Created missing overview-dashboard Blade views and data configs across the business modules.

**Files**: `app/Modules/Hr/Config/navigation.php`, `app/Modules/Hr/Routes/web.php`, `app/Modules/Hr/Resources/views/dashboard-manage-overview.blade.php`, `app/Modules/Hr/Data/dashboards/dashboard_manage_overview.php`

---

## [Architecture Refactor — 2026-08-18]

### Organization Split
- Moved Organization domain (Company, Department, Location, Branch, Team, BusinessUnit, Division) from library `src/Core/Organization/` to standalone module `app/Modules/Organization/`
- Library now keeps only minimal `Company` model (name, code) + `companies` table (id, name, code, timestamps) as scoping anchor
- Library scoping mechanism preserved: `CompanyScope`, `HasCompanyScope`, `ResolveCompanyContext`, `NullCompanyProvider`, `CompanyProvider` contract
- Organization module extends library's minimal Company with business fields via ALTER TABLE migration

### HR Module Split
- Split monolithic `app/Modules/Hr/` (45 models) into 5 sub-modules:
  - `app/Modules/Attendance/` — 12 models (time tracking, shifts, clock events)
  - `app/Modules/Leave/` — 5 models (leave types, requests, balances, approvers)
  - `app/Modules/Payroll/` — 11 models (payroll runs, payslips, policies)
  - `app/Modules/Holiday/` — 2 models (holidays, calendars)
  - `app/Modules/Hr/` — 15 models (core employee/people, slimmed down)
- Each module is self-contained with its own migrations, routes, views, configs, and service provider

### Documents
- Library `documents` table is now the single source of truth (polymorphic file-attachment)
- HR module uses ALTER TABLE to add domain-specific columns (`employee_id`, `type`, `expiry_date`)
- Pattern established: library owns base tables, modules ALTER for domain columns

### Verification
- `migrate:fresh --seed`: 72 migrations, 0 errors
- `ui-library:discover`: 5 modules, 3 listeners, 1 workflow, 364 permissions
- `route:list`: 158 routes
- `check-domain-independence.sh`: PASS
- `phpunit`: 69 tests, 181 assertions

## [Organization Domain — 2026-08-17]

### Added
- `src/Core/Organization/` — domain-agnostic organizational structure (Company, Department, Location, Branch, Team, BusinessUnit, Division)
- Models with generic columns: `state_code`, `country_code`, `currency_code`, `parent_department_id`
- 7 migrations (`2026_06_11_000003`–`2026_06_11_000009`) for companies, branches, departments, divisions, business_units, locations, teams
- Data configs (form, table, detail) for each entity; dashboards: companies_overview, locations_overview, structure_overview
- `OrganizationSeeder` with demo data
- Library web routes under `/organization/*`

### Changed
- Company model: `state`→`state_code`, `country`→`country_code` (consistent with Branch/Location naming)
- Dashboard role configs: `hr_admin`→`organization_manager` (domain-neutral)

### Fixed
- Removed duplicate Organization migrations from the consuming app's `database/migrations/` (now loaded exclusively from package via `UILibraryServiceProvider::loadMigrationsFrom()`)
- Extended `scripts/check-domain-independence.sh` DOMAIN_TERMS to catch role-name leakage (`hr_admin|hr_manager|hr_staff|hr_supervisor`)

### Verification (all passing)
- `composer dump-autoload`: 10,398 classes
- `php artisan route:list`: 140 routes
- `php artisan migrate --pretend`: clean, no duplicate table errors
- `php artisan ui-library:discover`: hr module discovered (1 module, 3 listeners, 1 workflow, 364 permissions)
- `bash scripts/check-domain-independence.sh`: all 3 checks pass

---

## 2026-08-17 — Module Auto-Discovery System

A convention-based auto-discovery system scans `app/Modules/*` and registers business-module assets without manual service-provider wiring. The [`DiscoveryRegistrar`](src/Services/Discovery/DiscoveryRegistrar.php) is the central coordinator; [`ModuleServiceProvider`](src/Providers/ModuleServiceProvider.php) drives registration during boot, and `php artisan ui-library:discover` renders a debuggable summary.

### Listeners
- Scans `app/Modules/{Module}/Listeners/*.php` via reflection on `handle()` method signatures.
- Fixed case-sensitivity in listener class resolution.
- One-to-many event→listener map (a single event can have multiple listeners).
- Content-hash cache keys (file paths + mtimes) with finite TTL — self-invalidates on deploy.

### Reports
- Scans business modules for classes implementing [`Reportable`](src/Contracts/Reports/Reportable.php).
- Resolves the report-type key from the new `#[ReportType('key')]` attribute, falling back to `const REPORT_TYPE` and finally `getReportType()`.
- Auto-registers discovered reports into `ui-library.reports.report_types`.

### Workflows
- Merges `app/Modules/{Module}/Config/workflows.php` into `ui-library.workflows.definitions` via deep-merge.

### Permissions
- Auto-generates CRUD permission names from discovered models (`view_{entity}`, `create_{entity}`, `edit_{entity}`, `delete_{entity}`).
- `app/Modules/{Module}/Config/permissions.php` can override or extend the auto-generated names.

### Notifications
- Reads `app/Modules/{Module}/Data/notifications.php` for notification templates and channel configuration.

### Opt-outs
- Global toggles: `ui-library.discovery.listeners`, `ui-library.discovery.reports`, `ui-library.discovery.workflows`.
- Per-module opt-outs: `ui-library.modules.{module}.auto_register_listeners`, `auto_register_reports`, `auto_register_workflows`, `auto_register_permissions`, `auto_register_notifications`.
- Cache TTL: `ui-library.discovery.cache_ttl` (default 86400s).

### New command
- `php artisan ui-library:discover` — dumps all discovered modules, listeners, reports, workflows, configs, permissions, and notifications.

**Files created**: [`DiscoveryRegistrar.php`](src/Services/Discovery/DiscoveryRegistrar.php), [`ReportType.php`](src/Attributes/ReportType.php), [`DiscoverCommand.php`](src/Console/Commands/DiscoverCommand.php)
**Files modified**: [`ui-library.php`](src/Config/ui-library.php) (new `discovery` section), [`ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php)

---

## 2026-08-17 — Tenancy / Multi-Tenancy Foundation

Added a domain-agnostic tenancy foundation using "company" as the tenant term (consistent with the existing `CompanyProvider`, company switcher, and `company_id` convention).

- **New global scope**: [`CompanyScope`](src/Scopes/CompanyScope.php) — automatically filters Eloquent queries by the tenant column when a current company ID is present in the session.
- **New trait**: [`HasCompanyScope`](src/Traits/HasCompanyScope.php) — apply to any Eloquent model to auto-register the `CompanyScope`. Provides `withoutCompanyScope()` for bypass queries.
- **New middleware**: [`ResolveCompanyContext`](src/Http/Middleware/ResolveCompanyContext.php) (alias `qf.resolve-company-context`) — resolves the current company at request start and persists it to the session, so the scope works reliably on both web and API routes.
- **Config**: `ui-library.tenancy.column` (default `company_id`) and `ui-library.tenancy.session_key` (default `current_company_id`).

**Files created**: [`CompanyScope.php`](src/Scopes/CompanyScope.php), [`HasCompanyScope.php`](src/Traits/HasCompanyScope.php), [`ResolveCompanyContext.php`](src/Http/Middleware/ResolveCompanyContext.php)
**Files modified**: [`ui-library.php`](src/Config/ui-library.php) (new `tenancy` section)

---

## 2026-08-17 — DataTable Record Events

New event/listener infrastructure for hooking into DataTable record lifecycle changes without modifying the library's DataTable or DataTableForm components.

- **New event**: [`DataTableRecordSaved`](src/Events/DataTableRecordSaved.php) — dispatched from [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) and [`DataTable`](src/Http/Livewire/DataTables/DataTable.php). Payload: `$oldRecord`, `$newRecord`, `$model` (FQCN), `$action` (one of `created`/`updated`/`deleted`/`restored`), and optional `$component`.
- **New abstract listener**: [`DataTableRecordListener`](src/Listeners/DataTableRecordListener.php) — base class with `handleCreated()`, `handleUpdated()`, `handleDeleted()`, `handleRestored()` hooks. Subclasses override the relevant hook and filter by model/status in their own domain logic.

**Files created**: [`DataTableRecordSaved.php`](src/Events/DataTableRecordSaved.php), [`DataTableRecordListener.php`](src/Listeners/DataTableRecordListener.php)
**Files modified**: [`DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php), [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php)

---

## 2026-08-17 — Workflow Convenience: `HasWorkflow` Trait

- **New trait**: [`HasWorkflow`](src/Traits/Workflows/HasWorkflow.php) — provides convenience methods on any model implementing [`Workflowable`](src/Contracts/Workflow/Workflowable.php):
  - `workflow()` — returns the latest [`Workflow`](src/Models/Workflow.php) for the model.
  - `workflows()` — returns all workflows (morphMany).
  - `activeWorkflow()` — returns the currently active (non-terminal) workflow.
  - `isUnderApproval()` — boolean check for pending/in_progress workflows.
  - `getWorkflowableId()` — returns the polymorphic identifier.
- The [`Workflowable`](src/Contracts/Workflow/Workflowable.php) contract remains the participation point; `HasWorkflow` is an optional convenience layer.

**Files created**: [`HasWorkflow.php`](src/Traits/Workflows/HasWorkflow.php)

---

## 2026-08-17 — Resolver Config Bindings

- **New config key**: `ui-library.navigation.workspace_resolver` — binds the [`WorkspaceResolver`](src/Contracts/Navigation/WorkspaceResolver.php) contract. Defaults to [`NullWorkspaceResolver`](src/Services/Navigation/NullWorkspaceResolver.php) (empty context, no filtering). Consuming apps publish and point to their own implementation.
- **Existing keys documented**: `ui-library.navigation.company_provider` (default [`DefaultCompanyProvider`](src/Services/Navigation/DefaultCompanyProvider.php)), `ui-library.approvals.approver_resolver`, `ui-library.approvals.approver_label_resolver`.

**Files modified**: [`ui-library.php`](src/Config/ui-library.php)

---

## 2026-08-17 — Bug Fixes

- **Recursive `{id}` placeholder resolution**: DataTable params with nested `{id}` references (e.g., `{parent_id}` resolving to a record that itself contains `{id}`) now resolve correctly through recursive expansion.
- **Config parity in export/import jobs**: The `session_key` and `column` config keys are now consistently read from `ui-library.tenancy` in both export and import job classes, fixing a mismatch where one path used a hardcoded fallback.

**Files modified**: [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php), export/import job classes

---

## 2026-08-16 — Catch-All Route Security Hardening

Hardened the centralized `/{module}/{view}/{id?}` catch-all route in [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php) with a config-driven module allow-list, directory-traversal sanitization, per-view authorization, and a named rate limiter.

- **New config section** `ui-library.catch_all` in [`ui-library.php`](src/Config/ui-library.php):
  - `allowed_modules` (default `['admin', 'system', 'organization', 'common']`) — modules the catch-all may resolve; business modules are appended automatically by [`ModuleServiceProvider`](src/Providers/ModuleServiceProvider.php).
  - `require_auth` (default `true`) — re-checked in the handler for defense in depth.
  - `gate` (default `null`) — optional Laravel Gate ability checked via `Gate::allows($gate, [$module, $view, $id])`.
  - `authorization_callback` (default `null`) — optional callable `($user, $module, $view, $id)` returning `true`/`false`; takes precedence over `gate`.
  - `rate_limiting.enabled` (default `true`), `max_attempts` (60), `decay_minutes` (1).
- **Route hardening**: module allow-list (`abort(404)`), explicit null-byte/slash/backslash/`..`/leading-dot sanitization (`abort(400)`), and per-view authorization (`abort(401)`/`abort(403)`).
- **Rate limiter**: `qf-catch-all` named limiter registered in [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php), keyed by authenticated user id (falling back to client IP), applied via `throttle:qf-catch-all` middleware when enabled.

**Files modified**: [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php), [`src/Config/ui-library.php`](src/Config/ui-library.php), [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php)

---

## 2026-08-16 — Legacy Dead Code Removed

Deleted three legacy Artisan commands superseded by [`InstallCommand.php`](src/Console/Commands/InstallCommand.php) and the cleaner publish/cleanup flow:

- [`QuickerFasterInstallUI.php`](src/Commands/QuickerFasterInstallUI.php) — superseded by [`InstallCommand.php`](src/Console/Commands/InstallCommand.php)
- [`CleanExports.php`](src/Commands/CleanExports.php) — no longer used
- [`CleanImportErrors.php`](src/Commands/CleanImportErrors.php) — no longer used

**Files removed**: `src/Commands/QuickerFasterInstallUI.php`, `src/Commands/CleanExports.php`, `src/Commands/CleanImportErrors.php`

---

## 2026-08-16 — Phase 6.1–6.3 Navigation Configs Completed

Completed the three Core navigation configs to the full context-group + context-item structure:

- [`System navigation`](src/Core/System/Config/navigation.php) — 7 context groups, 42 items (Dashboard, Accounts, Subscriptions, Plans, Applications, Settings, Setup)
- [`Admin navigation`](src/Core/Admin/Config/navigation.php) — 7 groups, 33 items (added Dashboard and Security; expanded Users & Permissions and Audit)
- [`Organization navigation`](src/Core/Organization/Config/navigation.php) — 7 groups, 28 items (added Classification and Reports; expanded Companies, Structure, and Locations)

**Files modified**: [`src/Core/System/Config/navigation.php`](src/Core/System/Config/navigation.php), [`src/Core/Admin/Config/navigation.php`](src/Core/Admin/Config/navigation.php), [`src/Core/Organization/Config/navigation.php`](src/Core/Organization/Config/navigation.php)

---

## 2026-08-16 — Dashboard/Widget Visual Polish (hero/stats/color)

Added optional dashboard-level and per-widget visual fields, plus a shared card partial and consolidated utility CSS.

- **Dashboard config** gains optional `hero` (title/description/icon) and `stats` (gradient stat row) fields, resolved via [`DashboardResolver::getHero()`](src/Services/Config/Dashboards/DashboardResolver.php) / `getStats()` and rendered by [`Dashboard.php`](src/Http/Livewire/Dashboards/Dashboard.php).
- **Per-widget `color`** field threaded through all 11 widget processors in [`src/Widgets/`](src/Widgets/).
- **Reusable partial** [`widgets/partials/card.blade.php`](src/Resources/views/widgets/partials/card.blade.php) wraps widget output in a polished card (header icon/title/description/actions, hover lift).
- **Utility CSS** (`.transition-hover`, `.opacity-6`, `.opacity-8`, `.min-width-0`) consolidated into [`public/assets/css/quicker-faster.css`](public/assets/css/quicker-faster.css).

**Files created**: [`src/Resources/views/widgets/partials/card.blade.php`](src/Resources/views/widgets/partials/card.blade.php)
**Files modified**: [`src/Http/Livewire/Dashboards/Dashboard.php`](src/Http/Livewire/Dashboards/Dashboard.php), [`src/Services/Config/Dashboards/DashboardResolver.php`](src/Services/Config/Dashboards/DashboardResolver.php), [`src/Widgets/*`](src/Widgets/), [`public/assets/css/quicker-faster.css`](public/assets/css/quicker-faster.css)

---

## 2026-08-16 — Notifications Dashboard Overview

Added a config-driven analytics/summary dashboard for the Notifications context group, matching the pattern used by the Workflows, Users & Permissions, Audit, and General Settings groups.

- **Dashboard config**: [`dashboard_notifications_overview.php`](src/Core/Admin/Data/dashboards/dashboard_notifications_overview.php) defines stat cards (total notifications, unread notifications, total templates, failed deliveries), "Notifications by Type" and "Notifications by Channel" doughnut charts, a 30-day notification activity trend, a recent notifications list, and action cards linking to the notification logs and preferences pages.
- **Blade wrapper**: [`dashboard-notifications-overview.blade.php`](src/Core/Admin/Resources/views/admin/dashboard-notifications-overview.blade.php) embeds `<livewire:qf.dashboard>` within the navigation layout.
- **Navigation**: [`navigation.php`](src/Core/Admin/Config/navigation.php) now points the Notifications group header to `admin/dashboard-notifications-overview` and adds a `notifications_overview` context item (`view_notifications_overview`) at order 1.

**Files created**: [`dashboard_notifications_overview.php`](src/Core/Admin/Data/dashboards/dashboard_notifications_overview.php), [`dashboard-notifications-overview.blade.php`](src/Core/Admin/Resources/views/admin/dashboard-notifications-overview.blade.php)
**Files modified**: [`navigation.php`](src/Core/Admin/Config/navigation.php)

---

## 2026-08-16 — Workflows Dashboard Overview

Added a config-driven analytics/summary dashboard for the Workflows context group, matching the pattern used by the Users & Permissions, Audit, and General Settings groups.

- **Dashboard config**: [`dashboard_workflows_overview.php`](src/Core/Admin/Data/dashboards/dashboard_workflows_overview.php) defines stat cards (total/active definitions, total workflows, pending approvals), a "Workflows by Status" doughnut chart, a 30-day workflow activity trend, a recent workflows list, and action cards linking to the definition wizard and definitions list.
- **Blade wrapper**: [`dashboard-workflows-overview.blade.php`](src/Core/Admin/Resources/views/admin/dashboard-workflows-overview.blade.php) embeds `<livewire:qf.dashboard>` within the navigation layout.
- **Navigation**: [`navigation.php`](src/Core/Admin/Config/navigation.php) now points the Workflows group header to `admin/dashboard-workflows-overview` and adds a `workflows_overview` context item (`view_workflows_overview`) at order 1.

**Files created**: [`dashboard_workflows_overview.php`](src/Core/Admin/Data/dashboards/dashboard_workflows_overview.php), [`dashboard-workflows-overview.blade.php`](src/Core/Admin/Resources/views/admin/dashboard-workflows-overview.blade.php)
**Files modified**: [`navigation.php`](src/Core/Admin/Config/navigation.php)

---

## 2026-08-16 — Notification Consuming-App Guide

Created [`19-notification-consuming-app-guide.md`](consuming-app/19-notification-consuming-app-guide.md), a comprehensive guide documenting the four consuming-app concerns the notification engine deliberately leaves to the application:

- **🟢 Throttling & Scheduling** — `dispatchAsync()` + `SendNotification` (`ShouldQueue`) as the async primitive; consuming-app examples for `Redis::throttle`, queue worker `--max-jobs`/`--rate`, `delay()`, and scheduled commands.
- **🟢 Audience Segmentation** — `dispatch(Notifiable $notifiable, ...)` per-recipient; consuming-app examples for querying target audiences, looping, and chunking large lists.
- **🟡 Inline Actions** — `NotificationAction` contract + `NotificationActionRegistry` + `actions` JSON column + button rendering; consuming-app examples for implementing handlers, registering them, and populating the `actions` column.
- **🟡 Template Variables** — `TemplateVariableRegistry` contract + `DefaultTemplateVariableRegistry` + dot-notation `renderTemplate()`; consuming-app examples for registering placeholders, building a template CRUD UI, and providing a preview renderer.
- **Testing guidance** — concrete PHPUnit examples for verifying delivery, throttling, segmentation, inline actions, and template variable rendering.

The guide also flags two library seams: `dispatch()` does not yet accept an `$actions` argument, and `renderTemplate()` is `protected` (not public API for previews).

**Files created**: [`19-notification-consuming-app-guide.md`](consuming-app/19-notification-consuming-app-guide.md)
**Files modified**: [`18-workflow-approval-testing-checklist.md`](consuming-app/18-workflow-approval-testing-checklist.md) (added cross-reference in §3.4)

---

## 2026-08-16 — Workflow Definition List Refactored to DataTable

The custom [`WorkflowDefinitionList`](src/Http/Livewire/Workflows/WorkflowDefinitionList.php) Livewire component and its [`workflow-definition-list.blade.php`](src/Resources/views/livewire/workflows/workflow-definition-list.blade.php) view have been deprecated in favor of the generic [`DataTable`](src/Http/Livewire/DataTables/DataTable.php) component.

- **New config**: [`workflow_definition.php`](src/Core/Admin/Data/workflow_definition.php) — DataTable config with key `admin.workflow_definition`, defining fields (`name`, `key`, `entity_type`, `is_active`, `description`, `notifications`, `created_at`, `updated_at`), table/list switch views, `moreActions` linking to the wizard via `?definitionId={id}`, and controls (search, column management, filtering, per-page).
- **Admin page**: [`workflow-definitions.blade.php`](src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php) now embeds `<livewire:qf.data-table configKey="admin.workflow_definition" />` instead of the deprecated `qf.workflow-definition-list`.
- **Service provider**: The `qf.workflow-definition-list` Livewire registration in [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) is commented out with a deprecation note.
- **Deprecated files**: [`WorkflowDefinitionList.php`](src/Http/Livewire/Workflows/WorkflowDefinitionList.php) and [`workflow-definition-list.blade.php`](src/Resources/views/livewire/workflows/workflow-definition-list.blade.php) now carry `@deprecated` docblocks.

**Files created**: [`workflow_definition.php`](src/Core/Admin/Data/workflow_definition.php)
**Files modified**: [`workflow-definitions.blade.php`](src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php), [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php), [`WorkflowDefinitionList.php`](src/Http/Livewire/Workflows/WorkflowDefinitionList.php), [`workflow-definition-list.blade.php`](src/Resources/views/livewire/workflows/workflow-definition-list.blade.php)

---

## 2026-08-16 — Workflow Definition Wizard Fixes

Five follow-up issues in the wizard and its list page were fixed:

### Validation error persistence fix
[`WorkflowDefinitionWizard::validateCurrentStep()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) now calls `resetErrorBag()` before validating, so errors from a previously-failed step are cleared on the next attempt instead of persisting across navigation.

### Tag retention across navigation fix
[`repopulateCurrentPicker()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) / [`repopulatePicker()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) rebuild the transient `$fields`/`$selectedLabels` picker state on navigation, so initiator/authorizer tags no longer disappear on Back/Continue/jump.

### TypeError fix (`canPerformAction`)
[`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php) and [`DataTable::handleRowAction()`](src/Http/Livewire/DataTables/DataTable.php) were passing the whole `moreActions` entry (an array) to `canPerformAction()`, which expects a string. They now extract the permission string (`$action['action'] ?? $action['permission'] ?? ''`); the new [`workflow_definition.php`](src/Core/Admin/Data/workflow_definition.php) config supplies `'action' => 'edit'`.

### Edit pre-load fix
[`WorkflowDefinitionWizard::mount()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) now reads `definitionId` from the request query string (the admin wrapper passes no mount params), and `loadDefinition()` pre-populates all fields including the four notification toggles.

### Notification toggles
The four free-text notification "type name" inputs on the Summary step were replaced with four toggle switches (`$notifyOnSubmitted`, `$notifyOnApproved`, `$notifyOnRejected`, `$notifyOnRecalled`). The master "Enable workflow notifications" toggle was removed; `enabled` is derived (`enabled = any toggle on`) and each `types.*` value is a fixed template name or `null`.

**Files changed**: [`WorkflowDefinitionWizard.php`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php), [`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php), [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php), [`workflow_definition.php`](src/Core/Admin/Data/workflow_definition.php)

---

## 2026-08-15 — Cache-Busting & Navigation Layout Cleanup

- Added a `?v=1.0.4` cache-busting query string to the [`quicker-faster.css`](src/Resources/views/components/layouts/navigation-layout.blade.php) stylesheet link so browsers no longer serve the stale cached step-tracker CSS indefinitely.
- Wrapped the theme CSS `<link id="pagestyle">` in a conditional so it only renders when `config('ui-library.theme.css')` is non-null (no more empty `href=""`).
- Removed the duplicate opening `<body>` tag in the navigation layout.

**Files changed**: [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php)

---

## 2026-08-15 — Step 5 (Summary + Plug-In Usage) Fix Pass

Nine discrepancies found during the Step 5 (Summary + Plug-In Usage) code walkthrough were resolved — three genuine fixes and six documentation updates:

### Fixes

| # | Severity | Area | Fix |
|---|----------|------|-----|
| 1 | Yellow | [`WorkflowDefinitionWizard::finish()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Added `validateFinalStep()` so saving re-validates `workflowName`, `workflowKey`, and `authorizers`, preventing an incomplete definition from being saved via direct navigation to the Summary. |
| 2 | Yellow | Workflow definition list page | New [`WorkflowDefinitionList`](src/Http/Livewire/Workflows/WorkflowDefinitionList.php) component, [`workflow-definition-list.blade.php`](src/Resources/views/livewire/workflows/workflow-definition-list.blade.php) view, and [`admin/workflow-definitions.blade.php`](src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php) page wrapper served at `/admin/workflow-definitions`; navigation updated to point at the list with a separate "New Workflow" item; wizard completion/cancel return to the list. |
| 3 | Green | [`WorkflowDefinitionWizard::saveDefinition()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Moved `showAlert` dispatch for skipped review steps out of the `DB::transaction` closure — warnings are collected and dispatched after commit. |
| 4 | Green | [`WorkflowDefinitionWizard`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Corrected the "Step 6 Notifications" docblock to "Notifications configuration (within Step 5 — Summary)". |
| 5 | Green | [`Workflowable::getWorkflowDefinitionKey()`](src/Contracts/Workflow/Workflowable.php) | Updated docstring to document DB-first resolution with config fallback. |
| 6 | Green | [`WorkflowDefinition`](src/Models/WorkflowDefinition.php) | Documented `entity_type` as descriptive-only (not used for matching; `key` is the sole lookup key). |
| 7 | Green | [`WorkflowDefinitionWizard`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Documented the Summary pipeline "Initiator" node as presentation-only vs. runtime `WorkflowStep` rows. |
| 8 | Green | [`WorkflowDefinition`](src/Models/WorkflowDefinition.php) & [`WorkflowEngine::getDefinition()`](src/Services/Workflow/WorkflowEngine.php) | Documented `is_active = false` as "un-startable" (skipped in DB-first lookup), not merely "hidden". |
| 9 | Green | [`workflow-definition-wizard.blade.php`](src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php) | Clarified the notifications section is a Livewire toggle, not a Bootstrap collapse. |

**Files changed**: [`WorkflowDefinitionWizard.php`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php), [`Workflowable.php`](src/Contracts/Workflow/Workflowable.php), [`WorkflowDefinition.php`](src/Models/WorkflowDefinition.php), [`WorkflowEngine.php`](src/Services/Workflow/WorkflowEngine.php), [`WorkflowDefinitionList.php`](src/Http/Livewire/Workflows/WorkflowDefinitionList.php), [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php), [`navigation.php`](src/Core/Admin/Config/navigation.php), and three Blade views.

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](library/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](project/23-workflow-approval-implementation-plan.md)

---

## 2026-08-15 — Step 3 (Add Reviewers) Fix Pass

Eight discrepancies found during the Step 3 (Add Reviewers) code walkthrough were fixed across the workflow engine, wizard, and definition models:

### Fixes

| # | Severity | Area | Fix |
|---|----------|------|-----|
| 1 | Yellow | [`WorkflowDefinitionWizard::validateCurrentStep()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Added `case 2` (`validateReviewSteps()`) — named review steps must have at least one assignee; empty seeded blanks are skipped; the review tier remains optional. |
| 2 | Critical | [`WorkflowEngine::approve()`](src/Services/Workflow/WorkflowEngine.php) | Enforced `all` resolution mode via per-user [`WorkflowAction`](src/Models/WorkflowAction.php) tracking. `any` mode retains the original single-approval path. |
| 3 | Yellow | [`WorkflowEngine::reject()`](src/Services/Workflow/WorkflowEngine.php) | Added the same [`ApprovalGuard::canApprove()`](src/Services/Approvals/ApprovalGuard.php) authorization check used by `approve()`; unauthorized users now get `AuthorizationException`. |
| 4 | Green | [`WorkflowDefinitionWizard::saveDefinition()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Named review steps with no assignees now dispatch a `showAlert` warning before being skipped. |
| 5 | Green | [`WorkflowDefinitionStep`](src/Models/WorkflowDefinitionStep.php) & [`ReviewerChainBuilder`](src/Http/Livewire/Workflows/ReviewerChainBuilder.php) | Added docblocks clarifying `resolution_mode` (`any`/`all` runtime enforcement) vs. `assignees.mode` (`users`/`roles`/`mixed` informational). |
| 6 | Yellow | [`WorkflowEngine::start()`](src/Services/Workflow/WorkflowEngine.php) | A definition with no review/authorizer steps now auto-approves the workflow on submission instead of leaving it stuck `pending`. |
| 7 | Yellow | DB notifications | New migration [`2026_08_15_000004_add_notifications_to_workflow_definitions.php`](Database/Migrations/2026_08_15_000004_add_notifications_to_workflow_definitions.php) adds a `notifications` JSON column; [`WorkflowDefinition`](src/Models/WorkflowDefinition.php) gained fillable + cast; [`WorkflowEngine::notificationConfig()`](src/Services/Workflow/WorkflowEngine.php) now reads DB-first; wizard gained an optional notifications toggle + type fields. |
| 8 | Green | [`admin/workflow-definition-wizard.blade.php`](src/Core/Admin/Resources/views/admin/workflow-definition-wizard.blade.php) | Added a docblock explaining the thin-wrapper role vs. the Livewire component view. |

**Files changed**: [`WorkflowEngine.php`](src/Services/Workflow/WorkflowEngine.php), [`WorkflowDefinitionWizard.php`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php), [`WorkflowDefinition.php`](src/Models/WorkflowDefinition.php), [`WorkflowDefinitionStep.php`](src/Models/WorkflowDefinitionStep.php), [`ReviewerChainBuilder.php`](src/Http/Livewire/Workflows/ReviewerChainBuilder.php), [`WorkflowApproved.php`](src/Events/Workflows/WorkflowApproved.php), new migration, and two Blade views.

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](library/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](project/23-workflow-approval-implementation-plan.md)

---

## 2026-08-15 — Step 4 (Add Authorizers) Fix Pass

Seven discrepancies found during the Step 4 (Add Authorizers) code walkthrough were fixed across the workflow engine, wizard, and definition models:

### Fixes

| # | Severity | Area | Fix |
|---|----------|------|-----|
| A | Yellow | [`WorkflowEngine::hydrateFromModel()`](src/Services/Workflow/WorkflowEngine.php) | Authorizer steps are now forced to `approval_mode = 'any'` during hydration, regardless of the stored `resolution_mode`. A manually-authored or DB-edited authorizer row with `resolution_mode = 'all'` would otherwise make every authorizer approve — contradicting "ANY ONE can authorize". Review steps keep their stored `any`/`all` mode. |
| B | Green | [`WorkflowDefinitionWizard::resetPicker()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | The assignment-mode toggle (users/roles/mixed) is now non-destructive: switching mode preserves the `$initiators`/`$authorizers` selection, clears only the transient search state, and rebuilds `$fields`/`$selectedLabels` from the preserved selection so badges continue to render. |
| C | Green | [`WorkflowDefinitionStep`](src/Models/WorkflowDefinitionStep.php) & [`WorkflowStep`](src/Models/WorkflowStep.php) | Docblocks added/updated clarifying that `resolution_mode` is the definition column (`any`/`all`) mapping to the runtime `WorkflowStep::approval_mode`. |
| D | Green | [`WorkflowDefinitionWizard`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Added a class docblock mapping the 1-based UI steps (Step 1–5) to the 0-based internal `$currentStep` (0–4). |
| E | Green | [`WorkflowDefinitionWizard`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Documented the intentional split: the wizard requires ≥1 authorizer (`validateAssignees()`), but the engine auto-approves zero-step definitions. Normal wizard-created definitions always include an authorizer; only manually-authored config definitions can hit the auto-approve path. |

**Files changed**: [`WorkflowEngine.php`](src/Services/Workflow/WorkflowEngine.php), [`WorkflowDefinitionWizard.php`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php), [`WorkflowDefinitionStep.php`](src/Models/WorkflowDefinitionStep.php), [`WorkflowStep.php`](src/Models/WorkflowStep.php)

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](library/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](project/23-workflow-approval-implementation-plan.md)

---

## 2026-08-15 — Approval & Workflow Infrastructure Bug Fixes

Eight bugs discovered during a code-grounded walkthrough of the Workflow Definition Wizard's "Add Initiators" step were fixed across the approval/workflow infrastructure:

### Critical Fixes

- **Initiator authorization enforced** ([`WorkflowEngine::start()`](src/Services/Workflow/WorkflowEngine.php)): The engine now resolves initiator assignees and calls [`ApprovalGuard::canSubmit()`](src/Services/Approvals/ApprovalGuard.php) before creating a workflow. Unauthorized submitters receive an `AuthorizationException`. Super-admin/bypass roles are honoured.
- **`hydrateFromModel()` respects `tier_type`** ([`WorkflowEngine::hydrateFromModel()`](src/Services/Workflow/WorkflowEngine.php)): Initiator steps are now collected into a separate `initiators` key in the hydrated definition array. Only `review` and `authorizer` steps populate the `steps` array. Runtime `WorkflowStep` rows are created only for review/authorizer tiers.
- **Duplicate workflow prevention** ([`WorkflowEngine::start()`](src/Services/Workflow/WorkflowEngine.php)): `hasActiveWorkflow()` is now called before creating a new workflow; throws `RuntimeException` if one already exists.

### Convention Alignment

- **Role storage unified to string names** ([`WorkflowDefinitionWizard::updatedSearches()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php)): The initiator/authorizer picker now stores roles by their `name` string (matching [`ReviewerChainBuilder`](src/Http/Livewire/Workflows/ReviewerChainBuilder.php)). User IDs remain integers.
- **`DefaultApproverResolver` int=user/string=role convention** ([`DefaultApproverResolver::resolve()`](src/Services/Approvals/DefaultApproverResolver.php)): Integers are treated as already-resolved user IDs (pass-through); strings are treated as role names (queried by `name`). The [`ApproverResolver`](src/Contracts/Approvals/ApproverResolver.php) contract docblock documents this convention.
- **`normalizeAssignees()` self-describing classification** ([`WorkflowDefinitionWizard::normalizeAssignees()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php)): Now classifies by id type (int = user, string = role) independent of the stored `mode` field, which is derived/informational.
- **Mode toggle synced on reload** ([`WorkflowDefinitionWizard::loadDefinition()`](src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php)): `initiatorMode`/`authorizerMode` are now synced from `detectMode()` after loading a saved definition, ensuring the UI toggle matches the items.

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](library/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](project/23-workflow-approval-implementation-plan.md)

## 2026-08-14 — Access Control & Navigation UX Polish

Follow-up UX refinements across the sidebar filter and the Access Control Manager.

### Sidebar Filter — Label, Data Attributes & SPA Survival

- **Label change**: the filter placeholder changed from "Filter modules..." to **"Search menu..."** (Spanish **"Buscar menú..."**) via the `filter_modules` key in [`public/lang/en/nav.php`](public/lang/en/nav.php) and [`public/lang/es/nav.php`](public/lang/es/nav.php).
- **`data-filterable` fix**: filterable attributes now live on the correct elements — [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) (nav items), the collapsible header and static label in [`sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php), and the inline static section label in [`sidebar.blade.php`](src/Resources/views/livewire/navs/sidebar.blade.php).
- **`wire:navigate` SPA survival**: the filter now survives Livewire SPA navigations via document-level event delegation plus a `livewire:navigated` re-init in [`quicker-faster.js`](public/assets/js/quicker-faster.js).

### Access Control Manager — Bulk Toggle Switches

The per-action bulk ON/OFF buttons were consolidated from **14 buttons into 7 Bootstrap toggle switches** (one per action: `view`, `create`, `edit`, `delete`, `print`, `export`, `import`).

- New [`getBulkToggleStatesProperty()`](src/Http/Livewire/AccessControls/AccessControlManager.php) returns `'on'` / `'off'` / `'mixed'` per action.
- Switches are state-aware and color-coded (`success` / `light` / `secondary`).
- `wire:loading` spinner + `wire:loading.attr="disabled"`, scoped per switch via `wire:target="bulkToggle('...', ...)"`.

### Reactive Permission State

- New `refresh-toggle-state` Livewire event: [`ToggleButton::refreshState()`](src/Http/Livewire/Buttons/ToggleButton.php) and [`ToggleButtonGroup::refreshState()`](src/Http/Livewire/Buttons/ToggleButtonGroup.php) listen for it.
- [`AccessControlManager::bulkToggle()`](src/Http/Livewire/AccessControls/AccessControlManager.php) dispatches the event with the fresh permission names.
- The "What user can do" badge recomputes via `getUpdatedDescription()` from `$buttonStates`.

### Model Search Accuracy

- [`getFilteredResourceNamesProperty()`](src/Http/Livewire/AccessControls/AccessControlManager.php) rewritten for word-based AND matching.
- New `buildResourceSearchText()` builds a rich searchable string from the raw basename, `Str::headline`, snake, kebab, plural, display label, and permission action labels.

### Permission Card Expand/Collapse Chevron

- Added `fas fa-chevron-down collapse-chevron` and the `collapse-chevron-trigger` class to the permission card header in [`toggle-button-group.blade.php`](src/Resources/views/livewire/buttons/toggle-button-group.blade.php).
- CSS rotation via `.collapse-chevron-trigger[aria-expanded="true"] .collapse-chevron` in [`quicker-faster.css`](public/assets/css/quicker-faster.css).
- Fixed the missing stylesheet link: [`quicker-faster.css`](public/assets/css/quicker-faster.css) is now linked in [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php).

**Docs updated**: [`sidebar-filter.md`](docs/components/sidebar-filter.md), [`06-navigation-system.md`](library/06-navigation-system.md), [`07-component-catalog.md`](library/07-component-catalog.md), [`implementation-plan.md`](project/implementation-plan.md).

---

## 2026-08-14 — Architecture Blueprint Split (17 Topic Files)

The monolithic [`ai-optimized-architecture-blueprint.md`](project/ai-optimized-architecture-blueprint.md) (~3,000 lines) was split into **17 topic files** under [`docs/architecture/`](README.md) (`01-` through `17-*`), each focused on a single architectural concern. The original blueprint is marked **SUPERSEDED** and retained as historical reference only.

- **Authoritative index**: [`00-index.md`](README.md) now maps all 17 topic files plus `phase-5-navigation-ux.md`, with cross-references and reading orders by role.
- **Status**: All 17 topic files are `✅ EXISTS`; no `⏸️ DEFERRED` topic files remain.
- **Blueprint**: [`ai-optimized-architecture-blueprint.md`](project/ai-optimized-architecture-blueprint.md) is marked `⚠️ SUPERSEDED`.

---

## 2026-08-14 — App\Modules Resolution + ActivityLogs Contract

### Standalone Library: Zero Executable `App\Modules\*` References

The library is now fully standalone — no executable `App\Modules\*` references remain. All previously documented hardcoded imports in [`implementation-plan.md` §11](project/implementation-plan.md) have been resolved or confirmed already decoupled.

- **WizardForm import swap**: [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) now imports [`QuickerFaster\UILibrary\Services\ActivityLogger`](src/Services/ActivityLogger.php) instead of `App\Modules\Admin\Services\ActivityLogger`.
- **ActivityLogWidgetProcessor decoupling**: new [`ActivityLogs\ActivityLogModelResolver`](src/Contracts/ActivityLogs/ActivityLogModelResolver.php) contract with `resolveModel(): ?string`, plus a default [`ActivityLogModelResolver`](src/Services/ActivityLogs/ActivityLogModelResolver.php) returning `config('ui-library.activity_logs.model')`.
- **`ui-library.activity_logs.model` config key**: env `UI_LIBRARY_ACTIVITY_LOG_MODEL` (default `null`). When unset, [`ActivityLogWidgetProcessor`](src/Widgets/ActivityLogWidgetProcessor.php) gracefully no-ops instead of failing.
- **Service provider binding**: default `ActivityLogModelResolver` bound in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php) alongside `ApprovalModelResolver`.
- **dashboard-control.blade.php cleanup**: removed the dormant commented-out `<select>` block that referenced `App\Modules\Production\Models\ProductionProcess::all()`.

**Verification**: `grep` across `src/` confirms zero executable `App\Modules\*` references remain — only docblock/comment references persist, which are non-blocking.

**Files**: [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php), [`src/Contracts/ActivityLogs/ActivityLogModelResolver.php`](src/Contracts/ActivityLogs/ActivityLogModelResolver.php) (new), [`src/Services/ActivityLogs/ActivityLogModelResolver.php`](src/Services/ActivityLogs/ActivityLogModelResolver.php) (new), [`ui-library.php`](src/Config/ui-library.php), [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php), [`ActivityLogWidgetProcessor.php`](src/Widgets/ActivityLogWidgetProcessor.php), [`dashboard-control.blade.php`](src/Resources/views/components/dashboards/dashboard-control.blade.php)

---

## 2026-08-14 — Phase 5 Navigation & UX Polish

### WorkspaceTabs
Browser-style tab system with session persistence. Livewire component [`WorkspaceTabs.php`](src/Http/Livewire/Layouts/Navs/WorkspaceTabs.php) plus a vanilla JS tab strip supporting click-to-switch, close button, middle-click close, right-click context menu (close others / close all to right / close all), overflow chevron, and Ctrl+W / Ctrl+Shift+T keyboard shortcuts. State persists via the `workspace_tabs`, `workspace_active_tab`, and `workspace_recently_closed` session keys.

### Breadcrumbs
5-level collapsible Blade component [`Breadcrumbs.php`](src/Components/Breadcrumbs.php) supporting `Application → Workspace → Section → Page → Record`. Collapses to first + "..." + last 2 segments via a vanilla JS dropdown.

### Sidebar Filter
Real-time client-side fuzzy search (word-based, case-insensitive, 150ms debounce) via `data-sidebar-filter` / `data-filterable` / `data-filter-text` attributes. Arrow/Enter/Escape/Ctrl+K keyboard navigation. No server round-trip.

### Sidebar → Tabs Integration
`ui-library.navigation.open_in_tabs` config toggles sidebar clicks into workspace tabs via the `openWorkspaceTab` event.

### Vanilla JS Architecture
All client-side interactivity uses vanilla JS (IIFE in [`quicker-faster.js`](public/assets/js/quicker-faster.js)) via `data-*` attributes and `Livewire.dispatch()`. No Alpine.js `x-data` directives were introduced.

**Docs**: [`phase-5-navigation-ux.md`](library/phase-5-navigation-ux.md), [`workspace-tabs.md`](docs/components/workspace-tabs.md), [`breadcrumbs.md`](docs/components/breadcrumbs.md), [`sidebar-filter.md`](docs/components/sidebar-filter.md)

---

## 2026-08-14 — Access Control Management Improvements

### Access Control Filtering Config (#38)
**Problem**: The AccessControlManager hardcoded which roles, modules, and models appeared in the permission assignment UI, with no way for consuming apps to tailor the lists without editing component code.

**Fix**: Added a new `access_control` config section to [`ui-library.php`](src/Config/ui-library.php) with `roles.include/exclude`, `modules.include/exclude`, and `models.include/exclude`. The AccessControlManager applies these filters when resolving assignable roles, available modules, and model permission cards. `'*'` includes everything; arrays of keys/names restrict or hide entries.

**Files**: [`ui-library.php`](src/Config/ui-library.php), [`AccessControlManager.php`](src/Http/Livewire/AccessControls/AccessControlManager.php)

---

### Access Control Consolidation (#39)
**Problem**: Permission assignment and role assignment lived on two separate pages, forcing users to switch contexts to manage a single access-control workflow.

**Fix**: Merged "Assign Permissions" and "Assign Roles" into a single "Access Control" page using Bootstrap tabs. The consolidated view at [`access-control-management.blade.php`](src/Core/Admin/Resources/views/admin/access-control-management.blade.php) hosts both workflows in one place.

**Files**: New [`access-control-management.blade.php`](src/Core/Admin/Resources/views/admin/access-control-management.blade.php) view

---

### Model Search + Bulk Permission Toggles (#40)
**Problem**: With many models in a module, finding a specific model to assign permissions was tedious, and granting or revoking a single action (e.g. `view`) across all models required toggling each card individually.

**Fix**: Added a `$modelSearch` property and a `getFilteredResourceNamesProperty()` computed property that live-filters the permission cards by model name or action label. Added a `bulkToggle($action, $value)` method that toggles a single action (`view`, `create`, `edit`, `delete`, `print`, `export`, `import`) across every model in the selected module at once.

**Files**: [`AccessControlManager.php`](src/Http/Livewire/AccessControls/AccessControlManager.php)

---

## 2026-08-13 — Home Page & Runtime Polish

### Polished Home Page (#34)
**Problem**: The default Laravel `/home` route rendered a generic, unstyled page. The library had no welcome dashboard to orient new users after login.

**Fix**: Replaced the default `/home` view with a full welcome dashboard featuring a hero section, key statistics (users, roles, modules), module cards with icons and descriptions, and a "Getting Started" guide section. The dashboard is rendered via a dedicated Livewire component and Blade view, both config-driven.

**Files**: New [`HomePage.php`](src/Http/Livewire/Pages/HomePage.php) Livewire component, new [`home-page.blade.php`](src/Resources/views/livewire/pages/home-page.blade.php) view, updated [`web.php`](src/Core/Admin/Routes/web.php) route registration

---

### `roles.deleted_at` Fix (#35)
**Problem**: The home page dashboard queried roles using a SoftDeletes-enabled Role model, but the `roles` table (standard Spatie `spatie/laravel-permission`) does not include a `deleted_at` column. This caused a `SQLSTATE[42S22]: Column not found` error when the dashboard tried to count roles.

**Fix**: Switched to the standard Spatie `Spatie\Permission\Models\Role` model (which does not use SoftDeletes) for all role queries on the home page. Additionally wrapped role-related queries in `rescue()` calls to gracefully degrade if the roles table or Spatie package is not available.

**Files**: [`HomePage.php`](src/Http/Livewire/Pages/HomePage.php)

---

### `$activeContext` Null Fix (#36)
**Problem**: [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php) and [`MenuRenderer`](src/Http/Livewire/Layouts/Navs/MenuRenderer.php) had `mount()` signatures with non-nullable `$activeContext` parameters. When no context was active (e.g., on the home page or pages without a context group), Livewire threw a type error because `null` was passed.

**Fix**: Made the `$activeContext` parameter nullable in both `TopNav::mount()` and `MenuRenderer::mount()` by adding `?string $activeContext = null`. Both components now handle a null active context gracefully — no context tab is highlighted, and the horizontal bar renders without an active indicator.

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`MenuRenderer.php`](src/Http/Livewire/Layouts/Navs/MenuRenderer.php)

---

### `getControls()` on Null Fix (#37)
**Problem**: [`page-header.blade.php`](src/Resources/views/components/layouts/partials/page-header.blade.php) called `$configResolver->getControls()` without first checking if `$configResolver` was null. On pages where no Data config was resolved (e.g., the home page dashboard), this caused a `Call to a member function getControls() on null` error.

**Fix**: Added a null guard around the `$configResolver->getControls()` call. When `$configResolver` is null, the page header renders without action buttons (create, export, etc.), which is the correct behavior for non-CRUD pages like the dashboard.

**Files**: [`page-header.blade.php`](src/Resources/views/components/layouts/partials/page-header.blade.php)

---

## 2026-08-12 — Comprehensive Fix & Audit Pass

### Navigation Fixes

#### Sidebar URL Generation Fix (#1)
**Problem**: Module name was duplicated in sidebar URLs (e.g., `admin/admin/users`), caused by route values already containing the module prefix when `NavigationLayout` prepended it again.

**Fix**: Route resolution logic updated to detect pre-prefixed routes and avoid double-prefixing. Normalization added in `NavigationManager`.

**Files**: [`NavigationLayout.php`](src/Components/NavigationLayout.php), [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php), [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php), [`NavigationManager.php`](src/Services/Navigation/NavigationManager.php), [`top-nav-item.blade.php`](src/Resources/views/livewire/navs/partials/top-nav-item.blade.php)

---

#### Sidebar ↔ Horizontal Toggle Button Config Fix (#2)
**Problem**: Config resolution for the toggle button had a priority inversion — session state was checked *before* the navigation config's `allow_switch` setting.

**Fix**: Reordered resolution chain so config `allow_switch` is the authoritative gate. Session consulted only after config permits.

**Files**: [`NavigationLayout.php`](src/Components/NavigationLayout.php), [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php), [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php), [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php), [`MenuRenderer.php`](src/Http/Livewire/Layouts/Navs/MenuRenderer.php), [`sidebar.blade.php`](src/Resources/views/livewire/navs/sidebar.blade.php)

---

#### Phase 1 Overflow "More" Dropdown (#3)
**Problem**: TopNav overflow for `max_visible_items: 6` needed verification. Regression where overflow items navigated to incorrect URLs when `route` was null.

**Fix**: Verified overflow logic works correctly. Fixed URL fallback when `route` is null. Added `wire:navigate` for SPA transitions.

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php), [`top-nav-item.blade.php`](src/Resources/views/livewire/navs/partials/top-nav-item.blade.php)

---

#### Phase 1 HorizontalContextMenu Overflow (#5)
**Problem**: `HorizontalContextMenu` needed its own overflow handling — visible items + "More" dropdown scoped to the active context group.

**Fix**: Added `getVisibleItems()` and `getOverflowItems()` methods. Active item promotion: if the active page is in overflow, it's promoted to visible list.

**Files**: [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php), [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php), [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php)

---

#### Phase 2 Cross-Context Dropdowns (#6)
**Problem**: Phase 1 only showed items for the active context group. Users needed to see all context groups simultaneously.

**Fix**: Two new config keys: `show_all_contexts` and `hide_topnav_contexts`. When enabled, every context group becomes a Bootstrap dropdown trigger showing its items + Phase 1 overflow.

**Files**: 8 modified + 1 new doc — see [`docs/navigation-cross-context-dropdowns.md`](project/navigation-cross-context-dropdowns.md)

---

### Rendering Fixes

#### Icon `fa` Prefix Fix (#7)
**Problem**: Font Awesome icons rendered as empty squares — missing `fa` base class.

**Fix**: Added `fa` to all `<i>` tags: `<i class="fas fa-home fa">`.

**Files**: [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) (3 icons), [`top-nav-item.blade.php`](src/Resources/views/livewire/navs/partials/top-nav-item.blade.php) (2 icons), [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) (1 icon), [`sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) (1 icon)

---

### Architecture & Decoupling

#### Audit & Reconciliation (#8)
Audited all 31 navigation and data config files across Admin, System, Organization, and Common modules. Aligned to original Quick-HR patterns: added `key`, `permission`, `page_title` fields; normalized route formats; ensured `order` sort keys present.

#### PHP 8.4 TypeError Fix (#9)
Added explicit `= null` defaults to nullable typed properties in all Livewire nav components to resolve PHP 8.4 deprecation warnings.

**Files**: [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php), [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php), [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php)

#### Dual-Location Config Resolution (#10)
Extended [`ModelConfigRepository`](src/Services/Config/ModelConfigRepository.php) with progressive path fallback: tries `app/Modules/` first, then falls back to `src/Core/`. Core modules can now use `<livewire:qf.data-table config-key="organization.company" />`.

**Files**: [`ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php)

#### HR Decoupling Audit (#12)
Comprehensive `grep` audit across entire `src/` directory. All consuming-app module references removed or abstracted behind contracts. Domain-specific Livewire components deleted. 30+ files reviewed/modified. 7 remaining hardcoded imports documented in [`implementation-plan.md` §11](project/implementation-plan.md).

---

### Company Switcher (#13)
Three blockers resolved:
1. Role gate removed — `show_company_switcher` now purely config-driven
2. Empty data gracefully handled with fallback when `NullCompanyProvider` returns empty
3. Default selection logic: auto-selects first company when session key is missing

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php), [`ui-library.php`](src/Config/ui-library.php)

---

### Documentation

#### UX Analysis (#4)
Evaluated 6 strategies for horizontal bar section header rendering. Produced design doc at [`plans/horizontal-bar-sections-ux-analysis.md`](plans/horizontal-bar-sections-ux-analysis.md).

#### Context Group Overview Views & Configs (#11)
Created 16 new Data config files for overview/dashboard pages. Updated Admin, System, and Organization navigation configs with overview items.

#### Config-Doc Alignment (#14)
Added 5 missing config keys to navigation config stubs: `max_visible_items`, `promote_active_item`, `show_all_contexts`, `hide_topnav_contexts`, `breadcrumb.enabled`.

#### Docs Updated
- [`docs/implementation-plan.md`](project/implementation-plan.md) — Added §0 "Completed Work Summary" with all 14 categories; updated statuses
- [`docs/navigation-workspace-architecture.md`](project/navigation-workspace-architecture.md) — Added §7 "Changelog"; updated date and status
- [`docs/navigation-cross-context-dropdowns.md`](project/navigation-cross-context-dropdowns.md) — Updated to reflect both Phase 1 and Phase 2 completion; added §8 "Changelog"
- [`docs/architecture-discrepancy-analysis.md`](project/architecture-discrepancy-analysis.md) — All 3 gap categories marked resolved; §8 recommendations updated with completion notes
- [`docs/CHANGELOG.md`](docs/CHANGELOG.md) — This file (new)

---

## 2026-08-13 — User Model, Config & Navigation Polish

### Config Consolidation (#15)
**Problem**: Two separate config files (`quicker-faster-ui.php` and `ui-library.php`) caused confusion about which config keys belonged where. The `quicker-faster-ui.php` config was a legacy artifact from before the library rename.

**Fix**: Removed `quicker-faster-ui.php` entirely. All config keys merged into [`ui-library.php`](src/Config/ui-library.php) as the single source of truth. Updated all references across the codebase.

**Files**: [`ui-library.php`](src/Config/ui-library.php), all service providers and components referencing the old config file

---

### User Profile Dropdown Menu (#16)
**Problem**: The TopNav user profile area had no dropdown menu — clicking the user avatar did nothing. Users had no quick access to profile, account settings, or preferences.

**Fix**: Added a config-driven `user_menu` section to [`ui-library.php`](src/Config/ui-library.php) with three default entries: "My Profile", "My Account", "My Preferences". The [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php) renders these as a Bootstrap dropdown from the user avatar. Each menu item supports `route`, `url`, `icon`, and `permission` keys. Menu is fully customizable per application.

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php), [`ui-library.php`](src/Config/ui-library.php)

---

### My Account & My Preferences Views (#17)
**Problem**: "My Account" and "My Preferences" pages existed only in a consuming application. These are generic user-facing pages that belong in the library.

**Fix**: Migrated both views from the consuming app to the library:
- **My Account**: Profile editing (name, email, avatar), password change, account status
- **My Preferences**: Notification preferences, language, timezone, date format, theme

Both views use the standard DataTableForm pattern with config-driven field definitions.

**Files**: New views at `src/Resources/views/profile/account.blade.php` and `src/Resources/views/profile/preferences.blade.php`; new Data configs at `src/Core/Admin/Data/`

---

### `withoutCompanyScope()` Fix (#18)
**Problem**: [`ResolvesModels.php`](src/Traits/ResolvesModels.php) called `withoutCompanyScope()` on models that may not have that scope, causing `BadMethodCallException` for models without multi-tenant scoping.

**Fix**: Added `method_exists()` guard before calling `withoutCompanyScope()`. Models without the scope are resolved normally. Models with the scope have it temporarily removed during resolution.

**Files**: [`ResolvesModels.php`](src/Traits/ResolvesModels.php)

---

### Missing Authorization Methods (#19)
**Problem**: [`AuthorizationService`](src/Services/AuthorizationService.php) was missing `authorizeView()`, `authorizeCreate()`, and `authorizeUpdate()` methods. The DataTable and DataTableForm components called these methods but they didn't exist, causing fatal errors.

**Fix**: Added the three missing authorization methods to [`AuthorizationService`](src/Services/AuthorizationService.php). Each method checks the corresponding Spatie permission (`view_{entity}`, `create_{entity}`, `update_{entity}`) and throws a 403 if denied. Methods accept an optional `$model` parameter for record-level authorization.

**Files**: [`AuthorizationService.php`](src/Services/AuthorizationService.php)

---

### Missing `profile` Relation Fix (#20)
**Problem**: The [`user.php`](src/Core/Admin/Data/user.php) Data config referenced a `profile` relation on the User model that didn't exist. Four eager-loading locations also referenced this non-existent relation, causing errors when loading user records.

**Fix**: Removed the `profile` relation reference from the user Data config. Added `method_exists()` or `relationLoaded()` guards in 4 eager-loading locations to safely skip the relation when it doesn't exist on the resolved User model.

**Files**: [`user.php`](src/Core/Admin/Data/user.php), 4 eager-loading locations in DataTable/Form components

---

### Missing ActivityLogger Methods (#21)
**Problem**: [`ActivityLogger`](src/Services/ActivityLogger.php) was missing `created()` and `updated()` static convenience methods. DataTableForm called `ActivityLogger::created()` and `ActivityLogger::updated()` after save operations, causing fatal errors.

**Fix**: Added `created(string $model, int $id, ?User $user = null)` and `updated(string $model, int $id, ?User $user = null)` static methods to [`ActivityLogger`](src/Services/ActivityLogger.php). Both methods log the action with the model name, record ID, and acting user.

**Files**: [`ActivityLogger.php`](src/Services/ActivityLogger.php)

---

### `company_id` & `status` Not Saving (#22)
**Problem**: The `company_id` and `status` fields were in the `hiddenFields` array in DataTableForm, preventing them from being submitted. Additionally, the User model's `$fillable` array didn't include these fields, so even when submitted they were silently discarded by Eloquent's mass-assignment protection.

**Fix**:
1. Removed `company_id` and `status` from `hiddenFields` in [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php)
2. Added `$fillable` auto-merge via the [`HasUILibraryUser`](src/Traits/HasUILibraryUser.php) trait to ensure `company_id` and `status` are always fillable

**Files**: [`DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php), [`HasUILibraryUser.php`](src/Traits/HasUILibraryUser.php)

---

### User Model Unification (#23)
**Problem**: The library had no standard User model. Each consuming application used its own User model (`App\Models\User`, `App\Modules\Admin\Models\User`, etc.), making it impossible for the library to reference users consistently. The install command had no way to inject library traits into the application's User model.

**Fix**: Comprehensive user model unification:
1. Created [`HasUILibraryUser`](src/Traits/HasUILibraryUser.php) trait with `$fillable` auto-merge, `profile()` relation (with safe fallback), and library-required accessors
2. Added config-driven model resolution via `ui-library.models.user` config key — the library resolves the User model class from config, never hardcoding an FQCN
3. Enhanced [`InstallCommand`](src/Console/Commands/InstallCommand.php) to automatically inject `HasUILibraryUser` into the application's User model using token-based injection

**Files**: [`HasUILibraryUser.php`](src/Traits/HasUILibraryUser.php) (new), [`ui-library.php`](src/Config/ui-library.php), [`InstallCommand.php`](src/Console/Commands/InstallCommand.php), [`UserModelTraitInjector.php`](src/Services/UserModelTraitInjector.php) (new)

---

### Company Dropdown Behavior (#24)
**Problem**: The company dropdown in TopNav showed users instead of companies, and the hide/show logic was inverted — the dropdown was hidden when it should be visible and vice versa.

**Fix**:
1. Restored correct hide/show logic: dropdown visible when `show_company_switcher` config is `true` AND the `CompanyProvider` returns companies
2. Fixed the company list to show companies (not users) by correcting the data source in [`TopNav::loadCompanies()`](src/Http/Livewire/Layouts/Navs/TopNav.php)

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php)

---

### Missing `status` & `company_id` Columns (#25)
**Problem**: The users table migration didn't include `status` and `company_id` columns, but the Data config and form expected them. New user creation failed because these columns didn't exist.

**Fix**: Added a new migration adding `status` (string, default 'active') and `company_id` (nullable foreign key) columns to the users table. The migration is published as part of the install command.

**Files**: New migration in `src/Core/Admin/Database/Migrations/`

---

### `$fillable` Auto-Merge (#26)
**Problem**: The `HasUILibraryUser` trait added `$fillable` properties, but if the consuming app's User model already defined `$fillable`, the trait's values would be overwritten (or vice versa depending on trait boot order).

**Fix**: Added `initializeHasUILibraryUser()` boot hook to the trait. This hook merges the library-required fillable fields (`status`, `company_id`) with any existing `$fillable` array on the model, ensuring both the application's and library's fillable fields are respected.

**Files**: [`HasUILibraryUser.php`](src/Traits/HasUILibraryUser.php)

---

### Install Command Trait Injection Fix (#27)
**Problem**: The install command's User model trait injection used fragile regex patterns that could corrupt the User model file if it had complex syntax (multi-line class declarations, existing traits, etc.).

**Fix**: Replaced regex-based injection with a token-based [`UserModelTraitInjector`](src/Services/UserModelTraitInjector.php) that:
1. Parses the PHP file into tokens using `token_get_all()`
2. Locates the class declaration and its opening brace
3. Inserts the `use HasUILibraryUser;` statement at the correct position inside the class body
4. Adds the import statement to the top of the file if not already present
5. Writes the modified file back, preserving all formatting

**Files**: [`UserModelTraitInjector.php`](src/Services/UserModelTraitInjector.php) (new), [`InstallCommand.php`](src/Console/Commands/InstallCommand.php)

---

### Company Dropdown Pre-Selection Fix (#28)
**Problem**: When editing a user record, the company dropdown was not pre-selecting the user's current company. The `loadRecord()` method in DataTableForm only used the primary key for lookups, ignoring foreign key relationships.

**Fix**: Added foreign key fallback logic to [`DataTableForm::loadRecord()`](src/Http/Livewire/DataTables/DataTableForm.php). When loading a record for editing, the method now checks the Data config's `belongsTo` relationships and pre-selects the corresponding foreign key values in dropdown fields.

**Files**: [`DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php)

---

### Success Feedback Messages (#29)
**Problem**: After saving a record in DataTableForm or completing a wizard in WizardForm, there was no visual feedback. Users had no confirmation that their action succeeded.

**Fix**: Added `$this->dispatch('showAlert', ['type' => 'success', 'message' => 'Record saved successfully.'])` calls in both [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php) and [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php) after successful save/complete operations. The alert is rendered by a global Alpine.js listener in the layout.

**Files**: [`DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php), [`WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php)

---

### Self-Edit Authorization Bypass (#30)
**Problem**: Users could not edit their own profile if they lacked the `update_user` permission. The authorization check in DataTableForm treated all edits equally, preventing self-service profile updates.

**Fix**: Added a self-edit bypass in [`AuthorizationService::authorizeUpdate()`](src/Services/AuthorizationService.php). When the record being edited is the currently authenticated user (`$model->id === auth()->id()`), the authorization check is skipped. Users can always edit their own record regardless of permission settings.

**Files**: [`AuthorizationService.php`](src/Services/AuthorizationService.php)

---

### Module Switcher Config (#31)
**Problem**: The module switcher dropdown showed all modules to all users. There was no way to restrict which modules appear for which roles.

**Fix**: Added flexible role-based configuration to the module switcher. Each module entry in [`ui-library.php`](src/Config/ui-library.php) now supports a `roles` array. The [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php) filters modules based on the authenticated user's roles before rendering the switcher dropdown. Modules with empty `roles` are visible to all authenticated users.

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`ui-library.php`](src/Config/ui-library.php)

---

### Background Jobs Config (#32)
**Problem**: The background jobs widget in the dashboard was hardcoded to show all job statuses to all users. There was no role-based filtering for sensitive job information.

**Fix**: Added flexible role-based configuration for background jobs visibility. The `background_jobs` config key in [`ui-library.php`](src/Config/ui-library.php) now supports a `roles` array controlling who can view job statuses, and a `visible_statuses` array controlling which job statuses are displayed.

**Files**: [`ui-library.php`](src/Config/ui-library.php), dashboard widget processors

---

### Notification Icon (#33)
**Problem**: The TopNav had no notification bell icon. Users had no way to see in-app notifications without navigating to a dedicated notifications page.

**Fix**: Added a notification bell icon to the TopNav with flexible configuration:
- `notifications.enabled` — toggle the icon entirely
- `notifications.polling_interval` — Livewire polling interval in seconds (default: 30)
- `notifications.max_display` — maximum unread count to show before displaying "99+"
- The icon shows an unread count badge and opens a dropdown with recent notifications

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php), [`ui-library.php`](src/Config/ui-library.php)

---

## 2026-08-15 — Authorization, Seeding & Install Fixes

### 403 Fix — Email Fallback Bypass & Error Surfacing (#41)

**Problem**: When the `model_has_roles` pivot table was empty (e.g., after a fresh install where role assignment silently failed), super admins were locked out of every admin page with a 403 error. The `AuthorizationService::isBypassAllowed()` only checked Spatie roles, and the `SuperAdminSeeder`/`UserSeeder` silently swallowed role-assignment failures.

**Fix**:
1. Added email-based fallback bypass in [`AuthorizationService::isBypassAllowed()`](src/Services/AccessControl/AuthorizationService.php): when the configured `SUPER_ADMIN_EMAIL` matches the authenticated user's email, the bypass is granted regardless of Spatie role state. This protects against seed failures where role assignment silently fails.
2. Added `AuthorizationService::isBypassAllowed()` calls to all 15 methods in [`DefaultAuthorizationProvider`](src/Services/DataTables/DefaultAuthorizationProvider.php) — `canAccessView`, `canView`, `canCreate`, `canUpdate`, `canDelete`, `canRestore`, `canForceDelete`, `canPerformAction`, `canExport`, `canImport`, `canPrint`, `canBulkDelete`, `canBulkRestore`, `canBulkForceDelete`, `canBulkExport`, `canBulkUpdate`.
3. Replaced silent `catch` blocks in [`SuperAdminSeeder`](src/Core/Admin/Database/Seeders/SuperAdminSeeder.php) and [`UserSeeder`](src/Core/Admin/Database/Seeders/UserSeeder.php) with `\Log::error()` + `$this->command->error()` calls so role-assignment failures are surfaced during `php artisan db:seed`.

**Files**: [`AuthorizationService.php`](src/Services/AccessControl/AuthorizationService.php), [`DefaultAuthorizationProvider.php`](src/Services/DataTables/DefaultAuthorizationProvider.php), [`SuperAdminSeeder.php`](src/Core/Admin/Database/Seeders/SuperAdminSeeder.php), [`UserSeeder.php`](src/Core/Admin/Database/Seeders/UserSeeder.php)

---

### Module Switcher Email Fallback (#42)

**Problem**: The module switcher dropdown in [`TopNav::loadModules()`](src/Http/Livewire/Layouts/Navs/TopNav.php) filtered modules by role only. If role assignment failed during seeding, the super admin would not see the admin/system modules in the switcher, making it impossible to navigate to the admin panel to fix the issue.

**Fix**: Added the same email-based fallback bypass from [`AuthorizationService::isBypassAllowed()`](src/Services/AccessControl/AuthorizationService.php) to [`TopNav::loadModules()`](src/Http/Livewire/Layouts/Navs/TopNav.php). When the authenticated user's email matches `SUPER_ADMIN_EMAIL`, the module role filter is bypassed — the super admin always sees all modules regardless of role state.

**Files**: [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php)

---

### `company_admin` Role Seeded (#43)

**Problem**: The library had no `company_admin` role — only `super_admin`, `admin`, and `user`. Multi-tenant applications needed a company-scoped admin role that could manage users and settings within their own company but not access system-level configuration.

**Fix**:
1. Added `company_admin` role creation in [`RoleSeeder`](src/Core/Admin/Database/Seeders/RoleSeeder.php) with `view dashboard`, `manage users`, and `manage settings` permissions.
2. Added `company_admin` to the admin module's `roles` array in [`ui-library.php`](src/Config/ui-library.php) so company admins can access the Administration module.
3. Added `company_admin` to [`AuthorizationService::ADMIN_ROLES`](src/Services/AccessControl/AuthorizationService.php) and `ADMIN_ROLES_ARRAY` so company admins benefit from the admin bypass in authorization checks.
4. Added `company.admin@example.com` seed user in [`UserSeeder`](src/Core/Admin/Database/Seeders/UserSeeder.php) with the `company_admin` role.

**Files**: [`RoleSeeder.php`](src/Core/Admin/Database/Seeders/RoleSeeder.php), [`ui-library.php`](src/Config/ui-library.php), [`AuthorizationService.php`](src/Services/AccessControl/AuthorizationService.php), [`UserSeeder.php`](src/Core/Admin/Database/Seeders/UserSeeder.php)

---

### InstallCommand Separate-Process Seeders (#44)

**Problem**: The [`InstallCommand::runSeeders()`](src/Console/Commands/InstallCommand.php) called seeders via `Artisan::call('db:seed', ['--class' => ...])` in the same PHP process. Since the install command modifies the User model source file (injecting `HasRoles` and `HasUILibraryUser` traits) before running seeders, the in-memory class definition was stale — the seeders used the old User model without the traits, causing role assignment to silently fail.

**Fix**: Replaced in-process `Artisan::call()` with separate-process execution via [`Symfony\Component\Process\Process`](src/Console/Commands/InstallCommand.php). Each seeder now runs as `php artisan db:seed --class={FQCN} --force` in its own PHP process, which boots fresh with the updated User model file. The `AccessControlPermissionSeeder` was also added to the seeder list.

**Files**: [`InstallCommand.php`](src/Console/Commands/InstallCommand.php)

---

### Dependencies Seeders Integration (#45)

**Problem**: The library's `dependencies/database/seeders/` directory (containing app-level seeders like `DatabaseSeeder`) was not publishable. Consuming apps had no way to get the standard seeder setup that integrates `AccessControlPermissionSeeder` and the `employee` role.

**Fix**:
1. Created [`AccessControlPermissionSeeder`](src/Core/Admin/Database/Seeders/AccessControlPermissionSeeder.php) — a library-level seeder that calls [`AccessControlPermissionService::seedPermissionNames()`](src/Services/AccessControl/AccessControlPermissionService.php) to seed `view_*`, `create_*`, `edit_*`, `delete_*`, `print_*`, `export_*`, `import_*` permissions for all discovered models.
2. Added `employee` role to [`RoleSeeder`](src/Core/Admin/Database/Seeders/RoleSeeder.php) with `view dashboard` permission.
3. Added `ui-library-seeders` publishable tag in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php) that publishes `dependencies/database/seeders/` to the app's `database/seeders/` directory.

**Files**: [`AccessControlPermissionSeeder.php`](src/Core/Admin/Database/Seeders/AccessControlPermissionSeeder.php) (new), [`RoleSeeder.php`](src/Core/Admin/Database/Seeders/RoleSeeder.php), [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php)

---

### Permission Seeding Fix — Removed `description` (#46)

**Problem**: [`AccessControlPermissionService::checkPermissionsExistsOrCreate()`](src/Services/AccessControl/AccessControlPermissionService.php) passed a `description` field to `Permission::create()`. The standard Spatie `permissions` table does not have a `description` column, causing a `SQLSTATE[42S22]: Column not found` error during permission seeding.

**Fix**: Removed the `description` key from the `Permission::create()` call. Only `name` and `guard_name` are now passed, matching the standard Spatie schema.

**Files**: [`AccessControlPermissionService.php`](src/Services/AccessControl/AccessControlPermissionService.php)

---

### Breeze Exit Code Fix (#47)

**Problem**: The [`InstallCommand::scaffoldAuth()`](src/Console/Commands/InstallCommand.php) set `$this->hasErrors = true` when Laravel Breeze was not installed. This caused the install command to exit with `FAILURE` even though Breeze not being installed is a normal, expected condition — the library's own auth views work independently.

**Fix**: Removed `$this->hasErrors = true` from the Breeze-not-installed branch. The branch now only emits a warning with instructions for installing Breeze later, and the install command exits with `SUCCESS` as long as no other errors occurred.

**Files**: [`InstallCommand.php`](src/Console/Commands/InstallCommand.php)

---

## Prior Milestones

### 2026-08-11 — Navigation System Polish
- Module Switcher replaced with inline Bootstrap 5 dropdown (ModuleSwitcher component deleted)
- Sidebar `activeContext` linkage: context-driven sidebar rendering
- Workspace parameter support: `WorkspaceResolver` contract + `WorkspaceFilter`
- Sidebar customization: `sidebar` config key with `section_label`, `collapsible`, `expanded_default`
- Icon mode complete: section headers collapse to compact icons; chevron expand indicator
- `determineModuleName()` fix: TopNav no longer overwrites explicit `moduleName` prop

### 2026-08-10 — Phases 3.1–3.5 Engine Services
- Workflow Engine, Document Engine, Notification Engine built
- Scheduled Reports engine with cron integration
- Reference Data module with Countries, Currencies, Languages

### 2026-08-09 — Phase 4.1 Organization Extraction
- Organization module extracted to `src/Core/Organization/` with 7 models
- `ModelConfigRepository` extended for dual-location config resolution
- Organization navigation config with 6 workspaces

### 2026-08-08 — Phase 2.5 Complete Decoupling
- ApprovalEngine decoupled via `ApprovalModelResolver` contract
- TopNav decoupled via `CompanyProvider` contract
- Domain-specific document services moved to consuming app
- Domain-specific Livewire components deleted

---

*End of changelog.*