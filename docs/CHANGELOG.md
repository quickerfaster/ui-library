# QuickerFaster UI Library — Changelog

> **Package**: `quicker-faster/ui-library`
> **Date**: 2026-08-15
> **Status**: Current — All 14 fix/audit categories + 19 new items + 4 home page & runtime polish items + 3 access control improvements + Phase 5 Navigation & UX Polish + App\Modules Resolution & ActivityLogs Contract completed + Architecture Blueprint Split + Access Control & Navigation UX Polish + Authorization, Seeding & Install Fixes (observations 17-23)

---

> ⚠️ **Testing status (2026-08-16)**: The workflow/approval foundation has been implemented and unit-verified (`php -l`, config validation), but has **NOT** yet been tested end-to-end in a consuming app. Further adjustments may be needed once integrated into a real consuming app (e.g., Spatie role/permission seeding, notification template registration, workspace-scoped approver resolution, and runtime workflow execution against real entities).

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

Created [`19-notification-consuming-app-guide.md`](docs/architecture/19-notification-consuming-app-guide.md), a comprehensive guide documenting the four consuming-app concerns the notification engine deliberately leaves to the application:

- **🟢 Throttling & Scheduling** — `dispatchAsync()` + `SendNotification` (`ShouldQueue`) as the async primitive; consuming-app examples for `Redis::throttle`, queue worker `--max-jobs`/`--rate`, `delay()`, and scheduled commands.
- **🟢 Audience Segmentation** — `dispatch(Notifiable $notifiable, ...)` per-recipient; consuming-app examples for querying target audiences, looping, and chunking large lists.
- **🟡 Inline Actions** — `NotificationAction` contract + `NotificationActionRegistry` + `actions` JSON column + button rendering; consuming-app examples for implementing handlers, registering them, and populating the `actions` column.
- **🟡 Template Variables** — `TemplateVariableRegistry` contract + `DefaultTemplateVariableRegistry` + dot-notation `renderTemplate()`; consuming-app examples for registering placeholders, building a template CRUD UI, and providing a preview renderer.
- **Testing guidance** — concrete PHPUnit examples for verifying delivery, throttling, segmentation, inline actions, and template variable rendering.

The guide also flags two library seams: `dispatch()` does not yet accept an `$actions` argument, and `renderTemplate()` is `protected` (not public API for previews).

**Files created**: [`19-notification-consuming-app-guide.md`](docs/architecture/19-notification-consuming-app-guide.md)
**Files modified**: [`18-workflow-approval-testing-checklist.md`](docs/architecture/18-workflow-approval-testing-checklist.md) (added cross-reference in §3.4)

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

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](docs/architecture/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](docs/architecture/23-workflow-approval-implementation-plan.md)

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

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](docs/architecture/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](docs/architecture/23-workflow-approval-implementation-plan.md)

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

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](docs/architecture/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](docs/architecture/23-workflow-approval-implementation-plan.md)

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

**Docs updated**: [`22-workflow-definition-wizard-ux.md`](docs/architecture/22-workflow-definition-wizard-ux.md), [`23-workflow-approval-implementation-plan.md`](docs/architecture/23-workflow-approval-implementation-plan.md)

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

**Docs updated**: [`sidebar-filter.md`](docs/components/sidebar-filter.md), [`06-navigation-system.md`](docs/architecture/06-navigation-system.md), [`07-component-catalog.md`](docs/architecture/07-component-catalog.md), [`implementation-plan.md`](docs/implementation-plan.md).

---

## 2026-08-14 — Architecture Blueprint Split (17 Topic Files)

The monolithic [`ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) (~3,000 lines) was split into **17 topic files** under [`docs/architecture/`](docs/architecture/00-index.md) (`01-` through `17-*`), each focused on a single architectural concern. The original blueprint is marked **SUPERSEDED** and retained as historical reference only.

- **Authoritative index**: [`00-index.md`](docs/architecture/00-index.md) now maps all 17 topic files plus `phase-5-navigation-ux.md`, with cross-references and reading orders by role.
- **Status**: All 17 topic files are `✅ EXISTS`; no `⏸️ DEFERRED` topic files remain.
- **Blueprint**: [`ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) is marked `⚠️ SUPERSEDED`.

---

## 2026-08-14 — App\Modules Resolution + ActivityLogs Contract

### Standalone Library: Zero Executable `App\Modules\*` References

The library is now fully standalone — no executable `App\Modules\*` references remain. All previously documented hardcoded imports in [`implementation-plan.md` §11](docs/implementation-plan.md) have been resolved or confirmed already decoupled.

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

**Docs**: [`phase-5-navigation-ux.md`](docs/architecture/phase-5-navigation-ux.md), [`workspace-tabs.md`](docs/components/workspace-tabs.md), [`breadcrumbs.md`](docs/components/breadcrumbs.md), [`sidebar-filter.md`](docs/components/sidebar-filter.md)

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

**Files**: 8 modified + 1 new doc — see [`docs/navigation-cross-context-dropdowns.md`](docs/navigation-cross-context-dropdowns.md)

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
Comprehensive `grep` audit across entire `src/` directory. All `App\Modules\Hr\*` and `App\Modules\Admin\*` references removed or abstracted behind contracts. HR-specific Livewire components deleted. 30+ files reviewed/modified. 7 remaining hardcoded imports documented in [`implementation-plan.md` §11](docs/implementation-plan.md).

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
- [`docs/implementation-plan.md`](docs/implementation-plan.md) — Added §0 "Completed Work Summary" with all 14 categories; updated statuses
- [`docs/navigation-workspace-architecture.md`](docs/navigation-workspace-architecture.md) — Added §7 "Changelog"; updated date and status
- [`docs/navigation-cross-context-dropdowns.md`](docs/navigation-cross-context-dropdowns.md) — Updated to reflect both Phase 1 and Phase 2 completion; added §8 "Changelog"
- [`docs/architecture-discrepancy-analysis.md`](docs/architecture-discrepancy-analysis.md) — All 3 gap categories marked resolved; §8 recommendations updated with completion notes
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
**Problem**: "My Account" and "My Preferences" pages existed only in the HR application. These are generic user-facing pages that belong in the library.

**Fix**: Migrated both views from the HR app to the library:
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
- EmployeeDocumentService moved to HR app
- HR Custom Livewire components deleted

---

*End of changelog.*