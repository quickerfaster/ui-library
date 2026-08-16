# QuickerFaster UI Library — Approval Infrastructure Analysis

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Status**: Analysis & Recommendation
> **Scope**: Foundational (package-level) vs. business-module approval UI

**Related files**: [`00-index.md`](./00-index.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`16-phase-history.md`](./16-phase-history.md)

---

## 1. Current Inventory

The approval infrastructure spans two layers that are **not currently reconciled**: a **legacy tier-based `ApprovalEngine`** (deprecated in documentation but still the only engine wired to Livewire UI) and a **generic `WorkflowEngine`** (preferred for new features but with no UI).

### 1.1 Files That Exist

| File | Status | Notes |
|------|--------|-------|
| [`src/Contracts/Approvals/ApprovalModelResolver.php`](../../src/Contracts/Approvals/ApprovalModelResolver.php) | ✅ Exists | Contract — `resolveRequestModel()`, `resolveTierModel()`, `resolveLogModel()`, `resolveTierApprovalModel()` |
| [`src/Services/Approvals/ApprovalModelResolver.php`](../../src/Services/Approvals/ApprovalModelResolver.php) | ✅ Exists | Default impl — reads `config('ui-library.approvals.models.*')` |
| [`src/Services/Approvals/ApprovalEngine.php`](../../src/Services/Approvals/ApprovalEngine.php) | ✅ Exists | Legacy engine — `startApproval()`, `approve()`, `reject()`, `recall()`. ⚠️ Documented as **DEPRECATED** |
| [`src/Models/ApprovalRequest.php`](../../src/Models/ApprovalRequest.php) | ✅ Exists | **Incomplete** — see §1.3 |
| [`src/Models/ApprovalTier.php`](../../src/Models/ApprovalTier.php) | ✅ Exists | **Incomplete** — see §1.3 |
| [`src/Http/Livewire/Approvals/ApprovalActions.php`](../../src/Http/Livewire/Approvals/ApprovalActions.php) | ✅ Exists | Inline action buttons + comment modal |
| [`src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php`](../../src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) | ✅ Exists | Inline tier/activity timeline |
| [`src/Traits/Approvals/HasApproval.php`](../../src/Traits/Approvals/HasApproval.php) | ✅ Exists | `approvalRequest()`, `isUnderApproval()`, `getActiveApproval()`, `canBeEditedWhileUnderApproval()` |
| [`src/Services/Config/Approvals/ApprovalConfigResolver.php`](../../src/Services/Config/Approvals/ApprovalConfigResolver.php) | ✅ Exists | Reads module config via `ModelConfigRepository`; `getModelClass()`, `getTiers()`, `getNotifications()`, `lockWhileApproving()` |
| [`src/Resources/views/livewire/approvals/actions.blade.php`](../../src/Resources/views/livewire/approvals/actions.blade.php) | ✅ Exists | Submit / Approve / Reject / Recall buttons + Bootstrap comment modal |
| [`src/Resources/views/livewire/approvals/timeline.blade.php`](../../src/Resources/views/livewire/approvals/timeline.blade.php) | ✅ Exists | Tier list with status badges + collapsible activity log |
| [`src/Resources/views/components/status/approval-status-badge.blade.php`](../../src/Resources/views/components/status/approval-status-badge.blade.php) | ✅ Exists | Shared status badge component (draft/pending/approved/rejected/cancelled) |
| [`src/Services/Workflow/WorkflowEngine.php`](../../src/Services/Workflow/WorkflowEngine.php) | ✅ Exists | **NEW** generic engine — supersedes ApprovalEngine; parallel, fully migrated, contract-driven |

### 1.2 Files That Are Referenced But Missing

| File | Status | Impact |
|------|--------|--------|
| [`src/Models/ApprovalLog.php`](../../src/Models/ApprovalLog.php) | ❌ **MISSING** | Referenced by [`ApprovalModelResolver::resolveLogModel()`](../../src/Services/Approvals/ApprovalModelResolver.php) and `config('ui-library.approvals.models.log')`. `ApprovalEngine::log()` instantiates it → **fatal error at runtime** |
| [`src/Models/ApprovalTierApproval.php`](../../src/Models/ApprovalTierApproval.php) | ❌ **MISSING** | Referenced by `resolveTierApprovalModel()` and `config('ui-library.approvals.models.tier_approval')`. `ApprovalEngine::approve()` (`all` mode) instantiates it → **fatal error at runtime** |
| Approval table migrations | ❌ **MISSING** | No migration exists under [`Database/Migrations/`](../../Database/Migrations/) for `approval_requests`, `approval_tiers`, `approval_logs`, `approval_tier_approvals`. The models point at tables that are never created by the package |
| Approval routes/controllers | ❌ **MISSING** | No approval listing/detail routes exist in [`src/Routes/web.php`](../../src/Routes/web.php) |

### 1.3 Inconsistencies Between Engine and Models

The legacy engine writes columns that the library models do not declare, and the models omit relations the engine and Blade views assume exist.

**`ApprovalTier` mismatch** — the engine writes these columns in [`startApproval()`](../../src/Services/Approvals/ApprovalEngine.php) and [`approve()`](../../src/Services/Approvals/ApprovalEngine.php):

| Column written by engine | In library `$fillable`? |
|--------------------------|--------------------------|
| `tier_type` | ❌ |
| `name` | ❌ |
| `roles` | ❌ |
| `approval_mode` | ❌ |
| `approved_by` | ❌ |
| `approved_at` | ❌ |
| `comments` | ✅ |

Library [`ApprovalTier`](../../src/Models/ApprovalTier.php) `$fillable` is only `approval_request_id`, `sequence`, `approver_id`, `status`, `comments`, `decided_at` — none of which match the engine's `approved_by`/`approved_at` convention and all of which omit the tier metadata columns. The Quick-HR reference model ([`App\Modules\System\Models\ApprovalTier`](../../../../LaravelProjects/quick-hr/app/Modules/System/Models/ApprovalTier.php)) has the correct full fillable list, plus `approver()`, `tierApprovals()`, and `roles`/`approved_at` casts — none of which exist in the library copy.

**`ApprovalRequest` mismatch** — the engine writes `current_tier_id` and `completed_at`, and the timeline view calls `$request->logs` and `$request->currentTier`. The library [`ApprovalRequest`](../../src/Models/ApprovalRequest.php) declares only `approvable_type`, `approvable_id`, `status`, `submitted_by`, `submitted_at` (missing `current_tier_id`, `completed_at`) and defines **no** `logs()`, `currentTier()`, or `submitter()` relations.

> **Conclusion**: the legacy approval stack is **non-functional out of the box** — it will fail with missing-model fatals and silently drop tier metadata due to fillable mismatches. It only "worked" historically because the Quick-HR app supplied its own `App\Modules\System\Models\*` models and migration.

---

## 2. Approval Flow

There are **two parallel flows**. The documented path of record says `WorkflowEngine` is preferred, but the only Livewire UI (`ApprovalActions`, `ApprovalHistoryTimeline`) is hard-wired to the legacy `ApprovalEngine` + `ApprovalRequest` models.

### 2.1 Legacy `ApprovalEngine` Flow (the only UI-wired path)

1. **Entry** — A business model opts in via [`HasApproval`](../../src/Traits/Approvals/HasApproval.php) (e.g. `App\Modules\Hr\Models\PayrollRun`, `LeaveRequest`). Entry is triggered either by [`ApprovalActions::submitForApproval()`](../../src/Http/Livewire/Approvals/ApprovalActions.php) (button click) or programmatically via [`ApprovalEngine::startApproval()`](../../src/Services/Approvals/ApprovalEngine.php) (e.g. [`PayrollRunWizard`](../../../../LaravelProjects/quick-hr/app/Modules/Hr/Http/Livewire/Payroll/PayrollRunWizard.php)). `startApproval()` guards against duplicate active requests via `hasActiveApproval()`, then creates the `ApprovalRequest` and its tiers inside a DB transaction.

2. **Tiers** — Tiers come from a module config file loaded by [`ApprovalConfigResolver::getTiers()`](../../src/Services/Config/Approvals/ApprovalConfigResolver.php) (via [`ModelConfigRepository`](../../src/Services/Config/ModelConfigRepository.php)). Config files live at `app/Modules/{Module}/Data/approvals/{key}.php` (business) or `src/Core/{Module}/Data/approvals/{key}.php` (core). Example keys: `hr.approvals.leave_request_approval`, `hr.approvals.payroll_run_approval`. Each tier has `type` (`initiation`/`reviewing`/`authorization`), `name`, `roles`, and `approval_mode` (`any`/`all`). Tiers are **strictly sequential** (`sequence` column, advanced one at a time). Parallel `all` mode is stubbed and incomplete (see §2.3).

3. **Approvers** — Approvers are **role-based** (a `roles` array on each tier). **This is the weakest point**: role checking is a placeholder in both the engine (`// TODO: Check if the approver has any of the roles...`) and the Livewire component (`$hasRole = true; // placeholder`). The customization point is [`ApprovalModelResolver`](../../src/Contracts/Approvals/ApprovalModelResolver.php), but it only resolves **model FQCNs** — it has **no approver-resolution contract**.

4. **Notifications** — [`ApprovalConfigResolver::getNotifications()`](../../src/Services/Config/Approvals/ApprovalConfigResolver.php) exposes an `on_submit`/`on_approve`/`on_reject` notification map from config (see [`leave_request_approval.php`](../../../../LaravelProjects/quick-hr/app/Modules/Hr/Data/approvals/leave_request_approval.php)), **but no code path ever calls it**. The engine and both Livewire components ignore it entirely. There is **zero integration** with the library's [`NotificationService`](../../src/Services/Notifications/NotificationService.php) / [`NotificationEventSubscriber`](../../src/Listeners/NotificationEventSubscriber.php).

5. **Decisions** —
   - `approve()` — validates `status === pending`, records the tier approval, marks the tier `approved`, logs via `log()`, then `advanceToNextTier()`. When no pending tier remains, the request is marked `approved` and `completed_at` set.
   - `reject()` — marks the request `rejected`, sets `completed_at`, logs. **Does not mark the current tier `rejected`** (inconsistent with the timeline view, which expects per-tier `rejected` state).
   - `recall()` — submitter-only, marks request `cancelled`, logs.
   - **No events are dispatched** (no `ApprovalRequested`, `ApprovalApproved`, `ApprovalRejected`), so there is no hook for callbacks/escalation.

6. **Visibility** — Only **inline** components exist: [`ApprovalActions`](../../src/Http/Livewire/Approvals/ApprovalActions.php) and [`ApprovalHistoryTimeline`](../../src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php), both keyed by `configKey` + `approvableId` and mounted within a record's own page. There is **no** standalone dashboard, listing page, or "my approvals" queue.

### 2.2 `WorkflowEngine` Flow (preferred, no UI)

[`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) implements the same lifecycle (`start`/`approve`/`reject`/`recall`/`advanceToNextStep`) against its own [`Workflow`](../../src/Models/Workflow.php), [`WorkflowStep`](../../src/Models/WorkflowStep.php), [`WorkflowAction`](../../src/Models/WorkflowAction.php) models, all backed by a shipped migration ([`2026_08_08_000001_create_workflow_tables.php`](../../Database/Migrations/2026_08_08_000001_create_workflow_tables.php)). It is:

- **Contract-driven** via [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php) (`getWorkflowableId()`, `getWorkflowDefinitionKey()`, `getWorkflowContext()`).
- **Config-driven** via `ui-library.workflows.definitions.{key}`.
- **Fully migrated and functional** (no missing models/migrations).

It has **no Livewire components, no Blade views, and no controllers** — documentation explicitly states UI is "provided by the legacy ApprovalActions and ApprovalHistoryTimeline" (which are wired to the *legacy* models, not `Workflow`).

### 2.3 Approver Authorization Gap (Both Engines)

- `WorkflowEngine::approve()` has **no role check at all** — any authenticated user can approve any step.
- `ApprovalEngine::approve()` has a TODO placeholder.
- `ApprovalActions::determinePermissions()` hardcodes `$hasRole = true`.

There is no `ApproverResolver` contract, no workspace/company scoping of approvers, and no per-record approver overrides. The Quick-HR app's domain-specific `LeaveApprover` model and `leave_approvers` table are a **business-module** concept (employee→approver relationships, approval levels, max days) and should **not** be generalized into the package.

---

## 3. UI Gap Analysis

| UI Capability | Library | Quick-HR Reference | Verdict |
|---------------|---------|--------------------|---------|
| Dedicated approval management/listing page | ❌ None | ❌ None (approvals surfaced via dashboards only) | **Missing** |
| "My Pending Approvals" list (approver view) | ❌ None | ⚠️ Dashboard "Pending Approvals" widgets (stat/list) only; no dedicated queue | **Missing** (dashboards are per-module, not a unified approver inbox) |
| "My Submitted Approvals" list (requester view) | ❌ None | ⚠️ Implied in leave/attendance "my requests"; no unified queue | **Missing** |
| Approval detail view (tiers, state, history) | ⚠️ Partial — inline `ApprovalHistoryTimeline` only | ⚠️ Partial — payroll detail embeds approval section | **Partial** (inline component exists, but no standalone detail screen) |
| Approval configuration UI (tiers, approvers, conditions) | ❌ None (config files only) | ⚠️ `LeaveApprover` CRUD (`hr.leave_approver`) — domain-specific | **Business-module-only** |
| Approval workflow visualization (tier progress, who approved/rejected) | ⚠️ Partial — `timeline.blade.php` renders tier status + approver name | ⚠️ Partial | **Partial** (exists but coupled to legacy `ApprovalTier` schema that is itself incomplete) |
| Integration with notification/activity systems | ❌ None (config key exists, never consumed) | ❌ None (leave/payroll use their own `session()->flash` messages) | **Missing** |

### 3.1 What the Reference Quick-HR App Has (Business-Module Concerns)

From the reference app (`/Users/mac/Projects/LaravelProjects/quick-hr`):

- **Domain approver config**: [`LeaveApprover`](../../../../LaravelProjects/quick-hr/app/Modules/Hr/Models/LeaveApprover.php) model + `leave_approvers` table + `hr.leave_approver` Data config. This is Leave-specific and must remain in the HR module.
- **Approval config files**: [`leave_request_approval.php`](../../../../LaravelProjects/quick-hr/app/Modules/Hr/Data/approvals/leave_request_approval.php) and [`payroll_run_approval.php`](../../../../LaravelProjects/quick-hr/app/Modules/Hr/Data/approvals/payroll_run_approval.php) — these are the **business-module input** to the library's config-driven engine. The pattern (declaring tiers/roles/notifications) is correct and should be preserved.
- **Approval schema (System module)**: [`2026_06_12_142535_create_approval_tables.php`](../../../../LaravelProjects/quick-hr/app/Modules/System/Database/Migrations/2026_06_12_142535_create_approval_tables.php) and the four `App\Modules\System\Models\Approval*` models — these are the **original** source of the legacy schema. They were never fully ported to the library (see §1.2/§1.3).
- **"Pending Approvals" dashboard widgets**: stat/list widgets scattered across `dashboard_leave_overview.php`, `dashboard_time_overview.php`, etc. These are module-scoped dashboard tiles, not a reusable approval inbox.

---

## 4. Foundational vs. Business-Module Recommendation

### 4.1 Guiding Principle

The library is **contract-driven** and its engines follow a **Contract → Service → Model → Migration → (UI)** pattern. The approval layer should be treated the same way: ship the **generic, reusable building blocks** (engine correctness, contracts, and composable Livewire primitives) and leave **domain-specific routing/configuration screens** to business modules.

Because `WorkflowEngine` is already the documented successor and is the only *complete* (migrated, contract-driven) engine, the recommendation is to **consolidate UI on `WorkflowEngine`** while either (a) fixing or (b) clearly deprecating-and-removing the legacy `ApprovalEngine`. The analysis below assumes the pragmatic path: **keep the legacy engine fixed enough not to break, but build new foundational UI against the `Workflow` models.**

### 4.2 Foundational (should ship in the package)

| # | Building Block | Type | Rationale |
|---|----------------|------|-----------|
| F1 | `ApprovalModelResolver` fixes + missing models | Models/Migration | Close the gap that makes the legacy engine non-functional: add [`ApprovalLog`](../../src/Models/ApprovalLog.php), [`ApprovalTierApproval`](../../src/Models/ApprovalTierApproval.php), a shared migration, and align `ApprovalTier`/`ApprovalRequest` fillable+relations with the reference schema |
| F2 | `ApproverResolver` contract | Contract | New contract to resolve *who may approve* a step/request. Default implementation returns all users holding the step's `roles` (Spatie). This is the missing customization point — business modules override it for manager/department/company routing |
| F3 | `ApprovalGuard` / authorization in engine | Service | Centralize the role/approver check used by both engines and the Livewire components (eliminates the `$hasRole = true` placeholder) |
| F4 | `ApprovalNotificationDispatcher` | Service/Listener | Wire the existing `getNotifications()` config map into the engine lifecycle via the library's [`NotificationService`](../../src/Services/Notifications/NotificationService.php). Dispatches on submit/approve/reject/recall. Emits library events (`ApprovalSubmitted`, `ApprovalApproved`, `ApprovalRejected`, `ApprovalRecalled`) for business-module listeners |
| F5 | `ApprovalRequestListView` (generic) | Livewire | Reusable pending/submitted queue component. Props: `view` (`pending`/`submitted`), optional `configKey`/module filter, optional `approvableType` filter. Renders a table of `Workflow`/`ApprovalRequest` rows with status badges, submitter, date, and links to the record. Exposes slot/column config |
| F6 | `ApprovalActions` (refactor to engine-agnostic) | Livewire | Refactor existing component so it targets `Workflow` (or an `Approvalable` abstraction) rather than the legacy `ApprovalRequest`, with `ApprovalGuard`-driven `canApprove`/`canReject`/`canRecall` |
| F7 | `ApprovalHistoryTimeline` (refactor to engine-agnostic) | Livewire | Same refactor — render `WorkflowStep`/`WorkflowAction` progress (or legacy `ApprovalTier`/`ApprovalLog`), with approver labels resolved via an `ApproverLabelResolver` |
| F8 | `ApproverLabelResolver` contract | Contract | Resolve display name/avatar/route for a user id, so the timeline/list can render approver identity without hardcoding the User model. Default uses `config('ui-library.user.model')` |
| F9 | Config-driven list columns + status badge reuse | Config/Blade | Extend the existing [`approval-status-badge`](../../src/Resources/views/components/status/approval-status-badge.blade.php) and let lists/timelines read column/label config from a shared approval config key |

### 4.3 Business-Module (should NOT ship in package)

| Concern | Why it stays in modules |
|---------|--------------------------|
| **Approval configuration screens** (defining tiers, approvers, conditions via UI) | Tiers/approvers are inherently domain-shaped (leave vs. payroll vs. procurement differ). The library should accept **config files** (as it does today) plus the `ApproverResolver` contract, not build a tier-builder UI |
| **Per-entity approver records** (e.g. `LeaveApprover`, `approval_level`, `max_approval_days`, `leave_type_ids`) | These are HR-specific relational rules. The generic engine already handles `roles`; entity-level approver tables remain a module's own CRUD (as `hr.leave_approver` does) |
| **Module-specific dashboards/queues** ("Pending Leave Approvals", "Payroll Approvals") | The generic queue (F5) is foundational; the module decides how to filter/label/route its slice |
| **Conditional/escalation rules** (timeouts, amount thresholds, dynamic tier skipping) | Not implemented anywhere; if needed later, they belong behind `Workflowable::getWorkflowContext()` and module-level step resolvers, not hardcoded package defaults |

### 4.4 Recommended Implementation Plan (phased, dependency-ordered)

> Note: the library's docs already position `WorkflowEngine` as the successor. Phases 1–2 stabilize the existing (legacy) surface without expanding it; Phases 3–5 build the reusable UI on the **workflow** foundation. This avoids investing new UI in a deprecated engine.

**Phase 1 — Repair the legacy approval foundation (unblock standalone install)**
1. Add [`ApprovalLog`](../../src/Models/ApprovalLog.php) and [`ApprovalTierApproval`](../../src/Models/ApprovalTierApproval.php) models.
2. Ship a package migration for the four approval tables (mirror the Quick-HR System migration, minus company coupling).
3. Fix [`ApprovalTier`](../../src/Models/ApprovalTier.php) and [`ApprovalRequest`](../../src/Models/ApprovalRequest.php) fillable/casts/relations to match the engine's actual writes (`current_tier_id`, `completed_at`, `tier_type`, `name`, `roles`, `approval_mode`, `approved_by`, `approved_at`, `logs()`, `currentTier()`, `tierApprovals()`).

**Phase 2 — Introduce the authorization contract**
4. Add [`ApproverResolver`](../../src/Contracts/Approvals/ApproverResolver.php) contract + Spatie default.
5. Add `ApprovalGuard` and consume it in both [`ApprovalEngine`](../../src/Services/Approvals/ApprovalEngine.php) and [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) (remove TODOs).

**Phase 3 — Wire notifications + events**
6. Add `ApprovalNotificationDispatcher` consuming `getNotifications()`.
7. Dispatch `ApprovalSubmitted/Approved/Rejected/Recalled` events from both engines.
8. Add a default subscriber that routes events to [`NotificationService`](../../src/Services/Notifications/NotificationService.php) and a [`NotificationTemplateSeeder`](../../src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php) entry for approval types.

**Phase 4 — Build reusable UI primitives on `WorkflowEngine`**
9. Refactor [`ApprovalActions`](../../src/Http/Livewire/Approvals/ApprovalActions.php) and [`ApprovalHistoryTimeline`](../../src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) to target `Workflow` (engine-agnostic) with `ApprovalGuard`-driven permissions and `ApproverLabelResolver` labels.
10. Add the generic [`ApprovalRequestListView`](../../src/Http/Livewire/Approvals/ApprovalRequestListView.php) Livewire component + Blade view, config-driven columns, registered as `qf.approval-request-list`.

**Phase 5 — Contracts for identity/labeling + docs**
11. Add [`ApproverLabelResolver`](../../src/Contracts/Approvals/ApproverLabelResolver.php) contract + default.
12. Document the full approval story (config schema, component usage, module extension) and mark the legacy engine's status explicitly (fixed but frozen; new features use `WorkflowEngine`).

---

## 5. Decision Summary

- **Foundational approval UI is needed**, but only as **composable primitives** — a generic pending/submitted queue, engine-agnostic action buttons, an engine-agnostic history timeline, and a status badge — built on the already-complete `WorkflowEngine`.
- The legacy `ApprovalEngine` must first be **repaired** (missing models + migration + fillable alignment) to stop being a latent runtime failure, but **no new UI should be invested in it**.
- **Approver resolution** and **approver labeling** are the two missing contract seams; they are the package-level extension points business modules override.
- **Approval configuration screens** (tier builders, approver CRUD, conditional rules) remain **business-module concerns** driven by config files and the new contracts, not by packaged UI.

---

**Related files**: [`00-index.md`](./00-index.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`10-settings-and-config.md`](./10-settings-and-config.md)
