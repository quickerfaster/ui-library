# QuickerFaster UI Library — Workflow & Approval Consuming-App Integration & Testing Checklist

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Status**: Guidance (2026-08-16) — consuming-app integration checklist; the foundation has NOT yet been tested end-to-end in a consuming app
> **Scope**: How a consuming application should verify the workflow/approval foundation end-to-end, register notification templates, write custom resolvers, and handle workspace/multi-tenant scoping

**Related files**: [`23-workflow-approval-implementation-plan.md`](../project/23-workflow-approval-implementation-plan.md) · [`22-workflow-definition-wizard-ux.md`](../library/22-workflow-definition-wizard-ux.md) · [`21-approval-infrastructure-analysis.md`](../library/21-approval-infrastructure-analysis.md) · [`08-contracts-and-interfaces.md`](../library/08-contracts-and-interfaces.md) · [`09-engines-and-services.md`](../library/09-engines-and-services.md) · [`19-notification-consuming-app-guide.md`](./19-notification-consuming-app-guide.md)

---

> ⚠️ **Testing status (2026-08-16)**: The workflow/approval foundation has been implemented and unit-verified (`php -l`, config validation), but has **NOT** yet been tested end-to-end in a consuming app. Further adjustments may be needed once integrated into a real consuming app (e.g., Spatie role/permission seeding, notification template registration, workspace-scoped approver resolution, and runtime workflow execution against real entities).

---

## 1. Purpose

The workflow/approval docs ([`21-`](../library/21-approval-infrastructure-analysis.md), [`22-`](../library/22-workflow-definition-wizard-ux.md), [`23-`](../project/23-workflow-approval-implementation-plan.md), [`24-`](../library/24-workflow-wizard-ux-polish.md)) describe the implemented foundation and the fix passes, but none of them tells a consuming-app developer **how to verify the foundation works against real entities**. This document fills that gap.

It is a **checklist and integration guide**, not a re-specification of the engine. Read [`23-workflow-approval-implementation-plan.md`](../project/23-workflow-approval-implementation-plan.md) for the implementation history and [`22-workflow-definition-wizard-ux.md`](../library/22-workflow-definition-wizard-ux.md) for the wizard and data model.

---

## 2. Integration Prerequisites (verify before testing)

These are the known seams that are **library-agnostic** and therefore must be supplied or confirmed by the consuming app. They are the most likely places to need adjustment during first integration.

### 2.1 Spatie role/permission seeding

The engine and guard are role-driven, but the library's [`RoleSeeder`](../../src/Core/Admin/Database/Seeders/RoleSeeder.php) seeds only generic roles/permissions (`view dashboard`, `manage users`, `manage roles`, `manage permissions`, `manage settings`, `manage modules`). It does **not** seed the workflow-specific permission names used by the admin navigation:

- `view_workflows_overview` — referenced by [`navigation.php`](../../src/Core/Admin/Config/navigation.php) for the Workflows overview page.
- `view_workflow_definition` — referenced for the workflow definitions list and the "New Workflow" wizard entry.

[`AccessControlPermissionSeeder`](../../src/Core/Admin/Database/Seeders/AccessControlPermissionSeeder.php) generates `view_*`/`create_*`/`edit_*`/`delete_*`/`print_*`/`export_*`/`import_*` for every model discovered by [`ModelDiscovery`](../../src/Services/AccessControl/ModelDiscovery.php), which should cover model-backed permissions (e.g., `view_workflow_definition`), but **not** the dashboard-only `view_workflows_overview` name.

**Checklist:**
- [ ] Confirm `view_workflows_overview` is seeded (or rely on the super-admin bypass during initial testing).
- [ ] Confirm `view_workflow_definition` is seeded.
- [ ] Confirm the consuming app's roles grant these to the intended admin personas.
- [ ] Decide whether [`InstallCommand`](../../src/Console/Commands/InstallCommand.php) should seed workflow permissions as part of `ui-library:install`.

### 2.2 Notification template registration

The wizard persists four fixed notification template names ([`WorkflowDefinitionWizard`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php)):

- `workflow_submitted`
- `workflow_approved`
- `workflow_rejected`
- `workflow_recalled`

However, [`NotificationTemplateSeeder`](../../src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php) currently seeds only `document_generated`, `report_ready`, and `workflow_stage_changed`. The four workflow names are **not yet seeded**, so a consuming app must register them (or the seeder should be extended).

**Checklist:**
- [ ] Seed (or confirm) the four `workflow_*` templates in `notification_templates` for the channels the app uses (`database`, `mail`).
- [ ] Confirm [`WorkflowEngine::notificationConfig()`](../../src/Services/Workflow/WorkflowEngine.php) resolves the DB definition's `notifications` JSON (DB-first) with config fallback.

### 2.3 Workspace / multi-tenant approver resolution

[`Workflowable::getWorkflowContext()`](../../src/Contracts/Workflow/Workflowable.php) may return a `workspace_id`. [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) passes it through to [`ApproverResolver::resolve()`](../../src/Contracts/Approvals/ApproverResolver.php) via [`ApprovalGuard`](../../src/Services/Approvals/ApprovalGuard.php).

The library now ships [`WorkspaceScopedApproverResolver`](../../src/Services/Approvals/WorkspaceScopedApproverResolver.php) as the **default** — it resolves approvers within a single workspace scope by checking `$user->company_id` against `$workspaceId`. The legacy [`DefaultApproverResolver`](../../src/Services/Approvals/DefaultApproverResolver.php) (global resolution, ignores `$workspaceId`) is still available for single-tenant apps.

**Most consuming apps do not need a custom resolver.** You only need one when your User model does **not** have a direct `company_id` column (e.g., the user→workspace relationship goes through an intermediary model).

**Checklist:**
- [ ] Confirm your User model has a `company_id` column (or configure `app.workspace_approvals.foreign_key`).
- [ ] If not, bind a custom `ApproverResolver` (see §4.1) that honors `$workspaceId` through your membership model.

---

## 3. End-to-End Testing Checklist

Run these against a **real consuming-app entity** (a model that implements [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php)), not against mocks.

### 3.1 Definition management (wizard)

- [ ] `php artisan migrate` creates `workflow_definitions` and `workflow_definition_steps` (migration [`2026_08_15_000003_create_workflow_definition_tables.php`](../../Database/Migrations/2026_08_15_000003_create_workflow_definition_tables.php)).
- [ ] `/admin/workflow-definition-wizard` renders the 5-step wizard.
- [ ] Create a definition with an initiator, a review chain, and an authorizer; save; confirm rows appear in the DB.
- [ ] Re-open the definition via `?definitionId=N`; confirm all fields (including the four notification toggles) pre-load.
- [ ] `/admin/workflow-definitions` lists the saved definition via `qf.data-table`, and "Edit in Wizard" links work.

### 3.2 Runtime workflow execution

- [ ] Call `WorkflowEngine::start($entity)` for an entity whose `getWorkflowDefinitionKey()` matches a saved DB definition; confirm the DB-first definition is used (not config fallback).
- [ ] Confirm `Workflow` + `WorkflowStep` runtime rows are created **only** for review/authorizer tiers (initiators are not persisted as steps).
- [ ] Confirm a workflow with zero review/authorizer steps auto-approves on submission.
- [ ] Confirm duplicate submission is blocked (`hasActiveWorkflow()` → `RuntimeException`).
- [ ] Confirm an unauthorized submitter is rejected (`ApprovalGuard::canSubmit()` → `AuthorizationException`).

### 3.3 Approvals, `all` mode, and rejections

- [ ] As an authorized approver, approve a `pending` step; confirm it advances to the next step and the `WorkflowAction` log records the decision.
- [ ] For an `all`-mode review step, confirm the step stays `pending` until every resolved assignee has approved (per-user [`WorkflowAction`](../../src/Models/WorkflowAction.php) tracking), and duplicate approval throws.
- [ ] Confirm an authorizer step is always `any`-mode even if the stored `resolution_mode` is `all`.
- [ ] Confirm an unauthorized user cannot approve or reject (`AuthorizationException`).
- [ ] Confirm `reject()` records the rejection and the workflow is marked rejected.
- [ ] Confirm `recall()` is submitter-only and marks the workflow cancelled.

### 3.4 Events & notifications

- [ ] With `Event::fake()`, assert `WorkflowSubmitted`, `WorkflowApproved`, `WorkflowRejected`, and `WorkflowRecalled` are dispatched at the correct transitions.
- [ ] With real listeners, confirm a notification record appears in `notification_logs` for a workflow whose `notifications` config is enabled (after seeding the templates per §2.2).

> **Notification-specific consuming-app guidance** (throttling/scheduling, audience segmentation, inline actions, template variables, and their tests) now lives in [`19-notification-consuming-app-guide.md`](./19-notification-consuming-app-guide.md). Use that guide for the four consuming-app notification concerns instead of this checklist.

### 3.5 Approval UI primitives

- [ ] Mount [`ApprovalActions`](../../src/Http/Livewire/Approvals/ApprovalActions.php) against a pending `Workflow`; confirm approve/reject/recall buttons render and function.
- [ ] Mount [`ApprovalHistoryTimeline`](../../src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php); confirm step progress and approver labels render via [`ApproverLabelResolver`](../../src/Contracts/Approvals/ApproverLabelResolver.php).
- [ ] Mount [`ApprovalRequestListView`](../../src/Http/Livewire/Approvals/ApprovalRequestListView.php) with `view="pending"` and `view="submitted"`; confirm the correct queues render.
- [ ] Confirm a user without the required role cannot see approve/reject controls.

---

## 4. Writing Custom Resolvers

### 4.1 `ApproverResolver`

The library now ships [`WorkspaceScopedApproverResolver`](../../src/Services/Approvals/WorkspaceScopedApproverResolver.php) as the **default**. Most consuming apps do **not** need a custom resolver — the default works when your User model has a `company_id` column.

You only need a custom resolver when your User model does **not** have a direct workspace column. Implement [`ApproverResolver`](../../src/Contracts/Approvals/ApproverResolver.php):

```php
namespace App\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;

class TenantApproverResolver implements ApproverResolver
{
    public function resolve(array $roleIds, ?string $workspaceId = null): array
    {
        // $roleIds: int = already-resolved user ID (pass through);
        //          string = role name to resolve.
        // $workspaceId: scope role→user resolution to a workspace/company.
        // Return a flat int[] of user IDs.
    }
}
```

Bind it via config `ui-library.approvals.approver_resolver` to override the default.

**Customization points:**
- Override `belongsToWorkspace()` when membership is determined by a pivot table or intermediary model.
- Override `activeWorkspaceId()` when the workspace ID comes from a non-standard source.
- Resolve roles against a custom role model instead of Spatie.
- Resolve manager/department/entity-level approvers from the workflow context.

See [20-reference-workspace-scoped-approver-resolver.md](./20-reference-workspace-scoped-approver-resolver.md) for the full reference.

### 4.2 `ApproverLabelResolver`

Implement [`ApproverLabelResolver`](../../src/Contracts/Approvals/ApproverLabelResolver.php):

```php
namespace App\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;

class AppApproverLabelResolver implements ApproverLabelResolver
{
    public function label($userId): string { /* display name */ }
    public function avatar($userId): ?string { /* avatar URL or null */ }
    public function profileRoute($userId): ?string { /* route or null */ }
}
```

Bind it via config `ui-library.approvals.approver_label_resolver` or the service container. The default [`DefaultApproverLabelResolver`](../../src/Services/Approvals/DefaultApproverLabelResolver.php) probes `name`/`full_name`/`email` for the label, and `avatar_url`/`avatar`/`profile_photo_url`/`photo` for the avatar; it returns `null` for the profile route.

---

## 5. Suggested Follow-Up Items (not yet implemented)

These are open items worth scheduling after the foundation is proven in at least one consuming app:

1. **Seed workflow permissions in `ui-library:install`** — extend [`InstallCommand`](../../src/Console/Commands/InstallCommand.php) / seeders so `view_workflows_overview` and `view_workflow_definition` are granted to the intended roles without manual steps.
2. **Seed the four workflow notification templates** — extend [`NotificationTemplateSeeder`](../../src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php) with `workflow_submitted`, `workflow_approved`, `workflow_rejected`, and `workflow_recalled`.
3. ~~**Document a reference workspace-scoped `ApproverResolver`**~~ — ✅ DONE. The library now ships [`WorkspaceScopedApproverResolver`](../../src/Services/Approvals/WorkspaceScopedApproverResolver.php) as the default. See [20-reference-workspace-scoped-approver-resolver.md](./20-reference-workspace-scoped-approver-resolver.md) for the full reference.
4. **Add automated `WorkflowEngine` / `ApprovalGuard` / wizard tests** — the plan references `tests/Services/Workflow/WorkflowEngineTest.php`, `tests/Services/Approvals/ApprovalGuardTest.php`, and `tests/Http/Livewire/Workflows/WorkflowDefinitionWizardTest.php`; confirm they exist and cover the `all`-mode and auto-approve edge cases.
5. **Resolve the `docs/architecture/` numeric-prefix collisions** — the workflow docs reuse `12-`/`13-`/`14-`/`15-`, which collide with [`12-ai-quick-start.md`](../library/12-ai-quick-start.md), [`13-adr.md`](../library/13-adr.md), [`14-integration-map.md`](../library/14-integration-map.md), and [`15-gaps-and-recommendations.md`](../library/15-gaps-and-recommendations.md). Consider renumbering the workflow set into a non-colliding range.

---

**Related files**: [`23-workflow-approval-implementation-plan.md`](../project/23-workflow-approval-implementation-plan.md) · [`22-workflow-definition-wizard-ux.md`](../library/22-workflow-definition-wizard-ux.md) · [`21-approval-infrastructure-analysis.md`](../library/21-approval-infrastructure-analysis.md) · [`08-contracts-and-interfaces.md`](../library/08-contracts-and-interfaces.md) · [`00-index.md`](../README.md)
