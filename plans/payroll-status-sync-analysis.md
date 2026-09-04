# Payroll & Leave Status Synchronization: Analysis & Recommendations

**Date:** 2026-08-31  
**Status:** Planning  
**Scope:** [`PayrollRun`](app/Modules/Payroll/Models/PayrollRun.php) and [`LeaveRequest`](app/Modules/Leave/Models/LeaveRequest.php) status fields vs. library [`Workflow`](src/Models/Workflow.php) status

---

## 1. Current State — The Two Status Fields

### 1.1 The Model Status Field (`payroll_runs.status` / `leave_requests.status`)

Each domain model carries its own `status` column on its database table. This is the "legacy" status system that predates the workflow engine.

**PayrollRun statuses** (defined in [`PayrollRun`](app/Modules/Payroll/Models/PayrollRun.php:74) and [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php:86-97)):

| Value | Meaning | Workflow Equivalent? |
|-------|---------|---------------------|
| `draft` | Initial state, being configured | No — pre-workflow |
| `verification_complete` | Data verified | No — pre-workflow |
| `adjustments_pending` | Adjustments being made | No — pre-workflow |
| `ready_for_review` | Ready for approval | Maps to workflow `pending` |
| `approved` | Approved | Maps to workflow `approved` |
| `processing` | Payslips being generated | No — post-approval |
| `paid` | Payment completed | No — post-workflow |
| `cancelled` | Run cancelled | Maps to workflow `cancelled` |
| `archived` | Hidden from active views | No — post-workflow |

Default: `draft`

**LeaveRequest statuses** (defined in [`LeaveRequest`](app/Modules/Leave/Models/LeaveRequest.php:58) and [`leave_request.php`](app/Modules/Leave/Data/leave_request.php:117-122)):

| Value | Meaning | Workflow Equivalent? |
|-------|---------|---------------------|
| `Pending` | Awaiting approval | Maps to workflow `pending` |
| `Approved` | Approved | Maps to workflow `approved` |
| `Denied` | Rejected | Maps to workflow `rejected` |
| `Cancelled` | Cancelled | Maps to workflow `cancelled` |

Default: `Pending`

**Critical observation:** LeaveRequest uses **PascalCase** (`Pending`, `Approved`, `Denied`, `Cancelled`) while the Workflow model uses **lowercase** (`pending`, `approved`, `rejected`, `cancelled`). Additionally, LeaveRequest uses `Denied` while Workflow uses `rejected` — semantically equivalent but lexically different.

### 1.2 The Workflow Status Field (`workflows.status`)

The library's [`Workflow`](src/Models/Workflow.php) model has its own `status` column with these values:

| Value | Set By | When |
|-------|--------|------|
| `pending` | [`WorkflowEngine::start()`](src/Services/Workflow/WorkflowEngine.php:64) | Workflow created |
| `approved` | [`WorkflowEngine::advanceToNextStep()`](src/Services/Workflow/WorkflowEngine.php) | Final step approved |
| `rejected` | [`WorkflowEngine::reject()`](src/Services/Workflow/WorkflowEngine.php:310) | Any step rejected |
| `cancelled` | [`WorkflowEngine::recall()`](src/Services/Workflow/WorkflowEngine.php:341) | Workflow recalled |

The [`WorkflowStep`](src/Models/WorkflowStep.php) model has: `pending`, `approved`, `rejected`.

### 1.3 How They Relate (or Don't)

The [`WorkflowEngine`](src/Services/Workflow/WorkflowEngine.php) **never writes to the model's own `status` field**. It exclusively manages `workflows.status` and `workflow_steps.status`. The model's `status` field is only ever written by:

1. **Direct assignment** in the consuming app (e.g., `$run->update(['status' => 'approved'])`)
2. **Bulk actions** via the data-table config's `updateModelField` mechanism
3. **MoreActions** via the data-table config's `updateModelField` mechanism
4. **Deprecated methods** in [`PayrollRunDetail`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php:114-131)

The [`HasWorkflow`](src/Traits/Workflows/HasWorkflow.php) trait provides `activeWorkflow()` (filters to `status = 'pending'`) and `isUnderApproval()` (checks if `activeWorkflow()->exists()`). Neither method looks at the model's own `status` field.

**Result:** Two independent status systems running in parallel with no synchronization mechanism.

---

## 2. Where Each Status Is Read

### 2.1 PayrollRun Detail Page

**Component:** [`PayrollRunDetail`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php)

- [`effectiveStatus()`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php:87-93): Returns `activeWorkflow->status` when a workflow exists, otherwise `$run->status`. This is the **only place** that bridges the two systems.
- Permission flags in [`render()`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php:411-416):
  - `canApprove` = `isUnderApproval()` (reads workflow, not model status)
  - `canMarkPaid` = `effectiveStatus() === 'approved'` (reads workflow when active)
  - `canCancel` = `isUnderApproval()` (reads workflow)
  - `canRecalculate` = `!isUnderApproval() && effectiveStatus() !== 'paid'`
- [`markAsReconciled()`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php:389-406): Checks `$run->status === 'paid'` directly — **bypasses `effectiveStatus()`**, so if a workflow exists and the model status is stale, this check fails.

**Blade:** [`payroll-run-detail.blade.php`](app/Modules/Payroll/Resources/views/livewire/payroll/payroll-run-detail.blade.php)

- Line 3: Computes `$effectiveStatus` identically to the component: `$run->activeWorkflow ? $run->activeWorkflow->status : $run->status`
- Line 74: Approval panel shown when `isUnderApproval() || activeWorkflow`
- Line 79: Status banner **hidden** when under approval (approval panel replaces it)
- Line 80: Status banner badge color uses `$effectiveStatus`
- Line 325: Reconciliation "Mark as Reconciled" checks `$run->status === 'paid'` directly

### 2.2 List View (Data-Table)

**Config:** [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php)

- [`status` field definition](app/Modules/Payroll/Data/payroll_run.php:80-99): Displays `payroll_runs.status` directly with no workflow awareness
- [`badgeColors`](app/Modules/Payroll/Data/payroll_run.php:681-691): Maps model status values to Bootstrap colors
- [`tableDefaultFields`](app/Modules/Payroll/Data/payroll_run.php:420-427): Includes `status` as a default column
- [`switchViews.list.badgeField`](app/Modules/Payroll/Data/payroll_run.php:680): Uses `status` as the badge field

**Problem:** When a PayrollRun has an active workflow (status `pending`), the list view still shows the model's `status` (e.g., `ready_for_review`). The user sees different statuses in the list vs. the detail page.

### 2.3 Bulk Actions

**Config:** [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php:458-488)

| Action | Target Field | Sets To | Condition |
|--------|-------------|---------|-----------|
| Approve Selected | `status` | `approved` | status in [draft, verification_complete, adjustments_pending, ready_for_review] |
| Cancel Selected | `status` | `cancelled` | status in [draft, verification_complete, adjustments_pending, ready_for_review] |
| Archive Selected | `status` | `archived` | status in [paid, cancelled] |

All three use `updateModelField` which directly mutates `payroll_runs.status` — **completely bypassing the WorkflowEngine**. No workflow events are fired, no notifications are sent, no approval guards are checked.

### 2.4 MoreActions (Row-Level Actions)

**Config:** [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php:577-659)

| Action | Mechanism | Sets To | Condition |
|--------|-----------|---------|-----------|
| Approve Run | `updateModelField` | `approved` | status in [verification_complete, ready_for_review] |
| Mark as Paid | `updateModelField` | `paid` | status = approved |
| Cancel Run | `updateModelField` | `cancelled` | status in [draft, verification_complete, adjustments_pending, ready_for_review, approved] |

Same bypass issue as bulk actions.

### 2.5 Reconciliation Tab

**Blade:** [`payroll-run-detail.blade.php`](app/Modules/Payroll/Resources/views/livewire/payroll/payroll-run-detail.blade.php:325)

```blade
@if ($run->status === 'paid' && $run->reconciliation_status !== 'reconciled')
```

Reads `$run->status` directly — does not use `$effectiveStatus`. If a workflow exists and the model status hasn't been synced, this condition will never be true even if the workflow says `approved`.

### 2.6 Dashboards

**Config:** [`dashboard_payroll_overview.php`](app/Modules/Payroll/Data/dashboards/dashboard_payroll_overview.php)

- Line 94: Raw SQL subquery filters `WHERE status = 'Paid'` (note: PascalCase `Paid` vs. lowercase `paid` — this is a separate bug)
- Line 106: Groups by `status` — reads model status directly
- Line 118: Groups by `month` — no status read

### 2.7 LeaveRequest Status Reads

**Config:** [`leave_request.php`](app/Modules/Leave/Data/leave_request.php)

- [`status` field](app/Modules/Leave/Data/leave_request.php:111-124): Displays `leave_requests.status` directly (PascalCase)
- [`badgeColors`](app/Modules/Leave/Data/leave_request.php:427-432): Maps PascalCase values
- [`bulkActions.approve`](app/Modules/Leave/Data/leave_request.php:318-325): Sets `status` to `'Approved'` (PascalCase)
- [`bulkActions.deny`](app/Modules/Leave/Data/leave_request.php:326-329): Sets `status` to `'Denied'` (PascalCase)
- No `effectiveStatus()` equivalent exists anywhere in the Leave module
- No detail Livewire component is registered (`detailComponent` is empty string at line 213)

---

## 3. Options for Syncing

### Option A: Sync on Every Workflow Transition

**Approach:** Listen for WorkflowEngine events in the consuming app and update the model's `status` field to match.

**Implementation:**
- Create event listeners for `WorkflowApproved`, `WorkflowRejected`, `WorkflowRecalled`
- Define a mapping from workflow status to model status
- Update `payroll_runs.status` (or `leave_requests.status`) in the listener

**Pros:**
- Model status always reflects reality — list views, dashboards, and reports work correctly without changes
- No query changes needed in existing code
- Event-driven, decoupled from the engine
- Each module can define its own mapping independently

**Cons:**
- Mapping is lossy for PayrollRun: workflow `pending` maps to which model status? (`ready_for_review`? `draft`?)
- Non-workflow statuses (`draft`, `processing`, `paid`, `archived`) have no workflow equivalent and must be handled separately
- Requires event listeners in every consuming module
- Two sources of truth still exist — they're just kept in sync

### Option B: Remove Model Status Entirely

**Approach:** Deprecate `payroll_runs.status` and `leave_requests.status`. All status reads go through `effectiveStatus()` or a similar computed property that derives from the workflow.

**Implementation:**
- Add a computed `status` accessor on the model that reads from `activeWorkflow` when available
- Rewrite all data-table configs to use the computed accessor
- Rewrite all blade templates, dashboards, and reports
- Handle non-workflow statuses as separate boolean flags or a different column

**Pros:**
- Single source of truth — no sync needed
- Impossible for statuses to diverge
- Cleaner architecture long-term

**Cons:**
- Massive refactor across all modules
- Non-workflow statuses (`draft`, `processing`, `paid`, `archived`) have no workflow equivalent — would need a separate mechanism
- Data-table configs, dashboards, and reports all need rewriting
- Every `$run->status` reference must be audited and potentially changed
- High risk of regressions
- LeaveRequest's PascalCase vs. lowercase mismatch still needs resolution

### Option C: Keep Both but Map Old Statuses to Workflow Statuses

**Approach:** Define a canonical mapping and use it everywhere status is read. The model's `status` field remains but is treated as a "display" value derived from the workflow.

**Implementation:**
- Define mapping: `draft` → no workflow, `ready_for_review` → workflow `pending`, `approved` → workflow `approved`, etc.
- Use mapping in `effectiveStatus()` and data configs
- Add a model accessor that returns the mapped status

**Pros:**
- Minimal code changes
- Backward compatible — existing queries on `payroll_runs.status` still work
- Quick to implement

**Cons:**
- Still two sources of truth — mapping can get out of sync
- Confusing for developers: "which status is the real one?"
- Mapping is bidirectional and lossy
- Doesn't solve the bulk action bypass problem
- Adds indirection without solving the root cause

---

## 4. Recommendation: Option A — Event-Driven Sync

**Option A is recommended** because it provides the best balance of correctness, minimal refactoring, and architectural cleanliness. The key insight is that the WorkflowEngine already fires events at every transition — we simply need to listen for them.

### 4.1 Architecture Boundary

The library must remain app-agnostic. It should **not** know about `payroll_runs.status` or `leave_requests.status`. The sync logic belongs entirely in the consuming app.

However, the library **should** provide the hooks that make sync easy:
- Events are already fired (`WorkflowApproved`, `WorkflowRejected`, `WorkflowRecalled`)
- Each event carries the `Workflow` model, which has `workflowable_type` and `workflowable_id` for polymorphic resolution
- The consuming app can resolve the workflowable model and update its status

### 4.2 Library-Side Changes (Minimal)

**No changes are strictly required.** The events already carry all necessary data. However, one optional improvement:

**Add a `syncModelStatus()` helper to [`HasWorkflow`](src/Traits/Workflows/HasWorkflow.php):**

```php
/**
 * Sync the model's status field with the workflow status.
 * Override in the model to define custom mappings.
 */
public function syncStatusFromWorkflow(Workflow $workflow): void
{
    // Default: copy workflow status directly (works for LeaveRequest after case normalization)
    $this->update(['status' => $workflow->status]);
}
```

This is optional — the consuming app can implement its own sync logic without it. The trait method simply provides a convenient default that models can override.

### 4.3 Consuming App Changes — PayrollRun

#### Step 1: Create Event Listener

**New file:** `app/Modules/Payroll/Listeners/WorkflowStatusSyncListener.php`

```php
namespace App\Modules\Payroll\Listeners;

use QuickerFaster\UILibrary\Events\Workflows\WorkflowApproved;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRejected;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRecalled;
use App\Modules\Payroll\Models\PayrollRun;

class WorkflowStatusSyncListener
{
    /**
     * Maps workflow status to PayrollRun model status.
     */
    protected static array $statusMap = [
        'approved'  => 'approved',
        'rejected'  => 'cancelled',   // rejected workflow = cancelled payroll run
        'cancelled' => 'cancelled',
    ];

    public function handleApproved(WorkflowApproved $event): void
    {
        if (! $event->completed) {
            return; // Only sync on final workflow approval
        }

        $this->sync($event->workflow);
    }

    public function handleRejected(WorkflowRejected $event): void
    {
        $this->sync($event->workflow);
    }

    public function handleRecalled(WorkflowRecalled $event): void
    {
        $this->sync($event->workflow);
    }

    protected function sync($workflow): void
    {
        if ($workflow->workflowable_type !== PayrollRun::class) {
            return;
        }

        $modelStatus = static::$statusMap[$workflow->status] ?? null;
        if (! $modelStatus) {
            return;
        }

        PayrollRun::withoutCompanyScope()
            ->where('id', $workflow->workflowable_id)
            ->update(['status' => $modelStatus]);
    }
}
```

**Register in `PayrollServiceProvider`:**

```php
use QuickerFaster\UILibrary\Events\Workflows\WorkflowApproved;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRejected;
use QuickerFaster\UILibrary\Events\Workflows\WorkflowRecalled;
use App\Modules\Payroll\Listeners\WorkflowStatusSyncListener;

// In boot():
Event::listen(WorkflowApproved::class, [WorkflowStatusSyncListener::class, 'handleApproved']);
Event::listen(WorkflowRejected::class, [WorkflowStatusSyncListener::class, 'handleRejected']);
Event::listen(WorkflowRecalled::class, [WorkflowStatusSyncListener::class, 'handleRecalled']);
```

#### Step 2: Define Status Mapping

The mapping from workflow status to PayrollRun model status:

| Workflow Event | Workflow Status | Model Status | Notes |
|---------------|-----------------|--------------|-------|
| `WorkflowApproved` (completed=true) | `approved` | `approved` | Direct mapping |
| `WorkflowRejected` | `rejected` | `cancelled` | No "rejected" status in PayrollRun; `cancelled` is closest |
| `WorkflowRecalled` | `cancelled` | `cancelled` | Direct mapping |

**Non-workflow statuses** (not affected by sync):
- `draft` — pre-workflow, set when run is created
- `verification_complete` — pre-workflow, set by wizard
- `adjustments_pending` — pre-workflow, set by wizard
- `ready_for_review` — pre-workflow, set by wizard before submitting to workflow
- `processing` — post-approval, set by payroll calculation jobs
- `paid` — post-workflow, set by `markPaid()` action
- `archived` — post-workflow, set by archive action

#### Step 3: Update Data Config to Use Effective Status in List Views

**Modify [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php):**

The `status` field definition should display the effective status. Since the data-table reads the model attribute directly, we need to add a computed accessor on the model:

**Add to [`PayrollRun`](app/Modules/Payroll/Models/PayrollRun.php):**

```php
/**
 * Get the effective status, considering the workflow when one exists.
 * This mirrors PayrollRunDetail::effectiveStatus() for use in data-table configs.
 */
public function getEffectiveStatusAttribute(): string
{
    if ($this->relationLoaded('activeWorkflow') && $this->activeWorkflow) {
        return $this->activeWorkflow->status;
    }
    return $this->status;
}
```

Then update the data config's `status` field to use a computed/display callback, or update the `badgeField` in `switchViews` to use `effective_status` instead of `status`.

**Alternative (simpler):** After sync is implemented, the model's `status` field will be correct for workflow-managed states. The list view will show `approved` instead of `ready_for_review` because the listener already synced it. The only remaining issue is during the brief window between workflow submission and approval — during this time, the model status is `ready_for_review` but the workflow status is `pending`. This is acceptable because:

1. The list view badge for `ready_for_review` is `primary` (blue), which is visually distinct
2. The detail page shows the approval panel, making the workflow state obvious
3. The window is temporary

#### Step 4: Remove Deprecated Methods from PayrollRunDetail

**Remove from [`PayrollRunDetail`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php):**

- `confirmApprove()` (lines 99-108)
- `approve()` (lines 114-131)
- `confirmCancel()` (lines 165-174)
- `cancel()` (lines 180-190)
- Remove `approveRun` and `cancelRun` from `$listeners` (lines 30, 32)

These are already marked `@deprecated` and superseded by the workflow engine's `qf.approval-actions` component.

#### Step 5: Remove or Update Bulk Actions That Bypass Workflow

**Modify [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php):**

- **Remove** the `approve` bulk action (lines 464-471) — approval must go through the WorkflowEngine
- **Remove** the `cancel` bulk action (lines 472-479) — cancellation must go through the WorkflowEngine
- **Keep** the `archive` bulk action (lines 480-487) — archiving is a post-workflow operation

**Modify `moreActions`:**

- **Remove** "Approve Run" (lines 588-601) — must go through workflow
- **Remove** "Cancel Run" (lines 615-628) — must go through workflow
- **Keep** "Mark as Paid" (lines 602-614) — this is a post-workflow operation
- **Keep** "View Payslips", "Full Employee List Report", "Export Bank File"

#### Step 6: Handle Non-Workflow Statuses

Non-workflow statuses (`draft`, `processing`, `paid`, `archived`) are managed by domain logic, not the workflow engine. They should remain as direct model status updates:

| Status | Set By | When |
|--------|--------|------|
| `draft` | Model default / wizard | Run created |
| `verification_complete` | Wizard | Data verified |
| `adjustments_pending` | Wizard | Adjustments being made |
| `ready_for_review` | Wizard | Ready to submit to workflow |
| `processing` | `ProcessPayrollRun` job | Payslip generation started |
| `paid` | `PayrollRunDetail::markPaid()` | Payment confirmed |
| `archived` | Bulk action | Run archived |

The wizard should set `status` to `ready_for_review` before calling `WorkflowEngine::start()`. After that, the event listener takes over.

#### Step 7: Fix Reconciliation Status Check

**In [`payroll-run-detail.blade.php`](app/Modules/Payroll/Resources/views/livewire/payroll/payroll-run-detail.blade.php:325):**

Change:
```blade
@if ($run->status === 'paid' && $run->reconciliation_status !== 'reconciled')
```
To:
```blade
@if ($effectiveStatus === 'paid' && $run->reconciliation_status !== 'reconciled')
```

And in [`PayrollRunDetail::markAsReconciled()`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php:391):

Change:
```php
if ($this->run->status !== 'paid') {
```
To:
```php
if ($this->effectiveStatus() !== 'paid') {
```

---

## 5. Impact on Leave Module

The Leave module has the same fundamental problem but with additional complications.

### 5.1 Case Sensitivity Issue

| Source | Status Values | Case |
|--------|--------------|------|
| [`LeaveRequest`](app/Modules/Leave/Models/LeaveRequest.php:58) model | `Pending`, `Approved`, `Denied`, `Cancelled` | PascalCase |
| [`leave_request.php`](app/Modules/Leave/Data/leave_request.php:117-122) config | `Pending`, `Approved`, `Denied`, `Cancelled` | PascalCase |
| [`Workflow`](src/Models/Workflow.php) model | `pending`, `approved`, `rejected`, `cancelled` | lowercase |

Any comparison like `$leaveRequest->status === $workflow->status` will **always fail** due to case mismatch. This affects any `effectiveStatus()` implementation.

### 5.2 Semantic Mismatch: `Denied` vs `rejected`

LeaveRequest uses `Denied` while Workflow uses `rejected`. These are semantically equivalent but lexically different. The sync mapping must handle this:

| Workflow Status | LeaveRequest Status |
|----------------|---------------------|
| `pending` | `Pending` |
| `approved` | `Approved` |
| `rejected` | `Denied` |
| `cancelled` | `Cancelled` |

### 5.3 No `effectiveStatus()` Equivalent

The Leave module has no detail Livewire component (`detailComponent` is empty in [`leave_request.php`](app/Modules/Leave/Data/leave_request.php:213)). If a detail page is added later, it must include an `effectiveStatus()` method.

For now, the list view is the primary concern. After sync is implemented, the model's `status` field will be updated to match the workflow, so the list view will show correct values.

### 5.4 Same Bulk Action Bypass Issue

**Config:** [`leave_request.php`](app/Modules/Leave/Data/leave_request.php:312-329)

| Action | Sets `status` To | Bypasses Workflow? |
|--------|-----------------|-------------------|
| Approve Selected | `'Approved'` | Yes |
| Deny Selected | `'Denied'` | Yes |

These must be removed or redirected to use the WorkflowEngine, same as PayrollRun.

### 5.5 Recommended Leave Module Changes

1. **Create `WorkflowStatusSyncListener`** in `app/Modules/Leave/Listeners/` with the PascalCase mapping
2. **Register it** in `LeaveServiceProvider`
3. **Remove** `approve` and `deny` bulk actions from [`leave_request.php`](app/Modules/Leave/Data/leave_request.php)
4. **Add `effectiveStatus()` accessor** to [`LeaveRequest`](app/Modules/Leave/Models/LeaveRequest.php) for future use
5. **Normalize case** — consider migrating `leave_requests.status` values to lowercase for consistency with the rest of the system (separate migration, lower priority)

---

## 6. Migration Path for Existing Data

### 6.1 SQL to Sync Existing PayrollRuns

This SQL updates `payroll_runs.status` to match `workflows.status` for records that have an active or recently completed workflow:

```sql
-- Sync payroll_runs.status with the latest workflow status
UPDATE payroll_runs pr
INNER JOIN (
    SELECT 
        w.workflowable_id,
        w.status AS workflow_status,
        w.created_at
    FROM workflows w
    WHERE w.workflowable_type = 'App\\Modules\\Payroll\\Models\\PayrollRun'
    AND w.id IN (
        SELECT MAX(id) 
        FROM workflows 
        WHERE workflowable_type = 'App\\Modules\\Payroll\\Models\\PayrollRun'
        GROUP BY workflowable_id
    )
) latest ON pr.id = latest.workflowable_id
SET pr.status = CASE latest.workflow_status
    WHEN 'approved' THEN 'approved'
    WHEN 'rejected' THEN 'cancelled'
    WHEN 'cancelled' THEN 'cancelled'
    ELSE pr.status  -- 'pending' workflow → don't change model status
END
WHERE latest.workflow_status IN ('approved', 'rejected', 'cancelled');
```

### 6.2 SQL to Sync Existing LeaveRequests

```sql
-- Sync leave_requests.status with the latest workflow status
UPDATE leave_requests lr
INNER JOIN (
    SELECT 
        w.workflowable_id,
        w.status AS workflow_status
    FROM workflows w
    WHERE w.workflowable_type = 'App\\Modules\\Leave\\Models\\LeaveRequest'
    AND w.id IN (
        SELECT MAX(id) 
        FROM workflows 
        WHERE workflowable_type = 'App\\Modules\\Leave\\Models\\LeaveRequest'
        GROUP BY workflowable_id
    )
) latest ON lr.id = latest.workflowable_id
SET lr.status = CASE latest.workflow_status
    WHEN 'approved' THEN 'Approved'
    WHEN 'rejected' THEN 'Denied'
    WHEN 'cancelled' THEN 'Cancelled'
    ELSE lr.status  -- 'pending' workflow → don't change model status
END
WHERE latest.workflow_status IN ('approved', 'rejected', 'cancelled');
```

### 6.3 Handling Ambiguous States

**Scenario: Model says `approved` but workflow says `pending`**

This means the model was approved via the old direct method (bypassing workflow). The workflow was never started or was started after the fact. In this case:
- The model status is authoritative for the business state
- The workflow is stale or was started in error
- **Action:** Do not overwrite. Flag for manual review.

**Scenario: Model says `ready_for_review` but workflow says `approved`**

This means the workflow completed but the model was never synced. This is the exact scenario the migration fixes.
- **Action:** Update model to `approved`.

**Scenario: Multiple workflows exist for the same model**

The `HasWorkflow` trait's `activeWorkflow()` filters to `status = 'pending'`, so only one should be active at a time. However, completed workflows accumulate. The migration uses `MAX(id)` to get the latest.

### 6.4 Rollback Plan

1. Take a snapshot of `payroll_runs.status` and `leave_requests.status` before migration:
   ```sql
   CREATE TABLE payroll_runs_status_backup AS SELECT id, status FROM payroll_runs;
   CREATE TABLE leave_requests_status_backup AS SELECT id, status FROM leave_requests;
   ```
2. If issues arise, restore from backup:
   ```sql
   UPDATE payroll_runs pr
   INNER JOIN payroll_runs_status_backup bk ON pr.id = bk.id
   SET pr.status = bk.status;
   ```

---

## 7. Summary Table

| Problem | Solution | Files to Change |
|---------|----------|----------------|
| WorkflowEngine never updates model status | Create event listeners that sync on `WorkflowApproved` (completed), `WorkflowRejected`, `WorkflowRecalled` | **New:** `app/Modules/Payroll/Listeners/WorkflowStatusSyncListener.php`<br>**New:** `app/Modules/Leave/Listeners/WorkflowStatusSyncListener.php`<br>**Modify:** `app/Modules/Payroll/Providers/PayrollServiceProvider.php`<br>**Modify:** `app/Modules/Leave/Providers/LeaveServiceProvider.php` |
| List view shows stale model status | After sync, model status is correct. Optionally add `getEffectiveStatusAttribute()` accessor | **Modify:** `app/Modules/Payroll/Models/PayrollRun.php`<br>**Modify:** `app/Modules/Leave/Models/LeaveRequest.php` |
| Bulk actions bypass workflow | Remove `approve` and `cancel`/`deny` bulk actions from data configs | **Modify:** `app/Modules/Payroll/Data/payroll_run.php`<br>**Modify:** `app/Modules/Leave/Data/leave_request.php` |
| MoreActions bypass workflow | Remove `Approve Run` and `Cancel Run` moreActions | **Modify:** `app/Modules/Payroll/Data/payroll_run.php` |
| Deprecated approve/cancel methods in PayrollRunDetail | Remove deprecated methods and their listener registrations | **Modify:** `app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php` |
| Reconciliation checks raw `$run->status` | Change to use `effectiveStatus()` / `$effectiveStatus` | **Modify:** `app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php`<br>**Modify:** `app/Modules/Payroll/Resources/views/livewire/payroll/payroll-run-detail.blade.php` |
| LeaveRequest PascalCase vs. Workflow lowercase | Map in sync listener; consider long-term normalization | **New:** `app/Modules/Leave/Listeners/WorkflowStatusSyncListener.php` |
| LeaveRequest `Denied` vs. Workflow `rejected` | Map `rejected` → `Denied` in sync listener | **New:** `app/Modules/Leave/Listeners/WorkflowStatusSyncListener.php` |
| LeaveRequest has no `effectiveStatus()` | Add accessor to model for future use | **Modify:** `app/Modules/Leave/Models/LeaveRequest.php` |
| Existing data out of sync | Run migration SQL to backfill | **New:** database migration or manual SQL script |
| No library-side hook for sync | Optional: add `syncStatusFromWorkflow()` to `HasWorkflow` trait | **Optional:** `src/Traits/Workflows/HasWorkflow.php` |

---

## Appendix A: Status Flow Diagram

```mermaid
stateDiagram-v2
    [*] --> draft: Run created
    draft --> verification_complete: Data verified
    verification_complete --> adjustments_pending: Adjustments needed
    adjustments_pending --> ready_for_review: Adjustments done
    ready_for_review --> pending_workflow: WorkflowEngine::start()
    
    state pending_workflow {
        [*] --> pending: Workflow created
        pending --> approved: All steps approved
        pending --> rejected: Step rejected
        pending --> cancelled: Recalled
    }
    
    pending_workflow --> approved: WorkflowApproved event
    pending_workflow --> cancelled: WorkflowRejected event
    pending_workflow --> cancelled: WorkflowRecalled event
    
    approved --> processing: Payslip generation starts
    processing --> paid: Payment confirmed
    paid --> archived: Archived
    cancelled --> archived: Archived
```

**Key:** The dashed arrows from `pending_workflow` states to model states represent the event listener sync. The `WorkflowSubmitted` event does NOT trigger a sync — the model stays at `ready_for_review` while the workflow is `pending`.

## Appendix B: Event Payload Reference

All workflow events carry the `Workflow` model, which provides:

```php
$event->workflow->workflowable_type  // e.g., 'App\Modules\Payroll\Models\PayrollRun'
$event->workflow->workflowable_id    // e.g., 42
$event->workflow->status             // 'pending', 'approved', 'rejected', 'cancelled'
$event->workflow->definition_key     // 'payroll_run', 'leave_request'
```

The `WorkflowApproved` event additionally carries:
```php
$event->completed  // bool — true when this was the final approval step
```

Sync should only happen when `$event->completed === true` for `WorkflowApproved`, to avoid updating the model status on intermediate step approvals.

## Appendix C: Future Work — Iterative Review & Resubmit Gap

### Current Limitation

The workflow engine is **linear forward-only** — once a workflow reaches a terminal status (`approved`, `rejected`, `cancelled`), there is no mechanism to:

1. **Send back for revision** — An approver at step 2 cannot return the workflow to the submitter for changes and then continue from step 2. The only option is to reject (which terminates the entire workflow) or approve.

2. **Reopen after recall** — When a submitter recalls a workflow (status → `cancelled`), the workflow is permanently closed. The submitter must start a brand new workflow from scratch, losing all history and context from the previous one.

3. **Skip/reroute** — There is no way to redirect a workflow step to a different approver mid-flight.

### Impact on Consuming App

After a PayrollRun is recalled:
- `effectiveStatus()` returns `'cancelled'` (model status, synced from workflow)
- `isUnderApproval()` returns `false` (no pending workflow)
- The approval panel and timeline are hidden
- There is **no "Resubmit for Approval" button** — the initiator has no path to restart the workflow from the detail page
- The only way to restart is through the `PayrollRunWizard`, which is designed for new runs, not resubmission

The same applies to LeaveRequest after recall.

### Recommended Library Enhancements (Future)

| Feature | Description | Priority |
|---------|-------------|----------|
| `returnToStep(int $stepIndex)` | Reset a specific step to `pending` without cancelling the workflow. Allows approvers to send work back for revision. | Medium |
| `reopen()` | Reopen a recalled/cancelled workflow, resetting it to `pending` and preserving history. Alternative: allow `start()` to be called on a model that already has a terminal workflow. | Medium |
| `reroute(int $stepIndex, $newApproverId)` | Change the assigned approver for a pending step. | Low |
| `rejectedRunWizardGuard` | Prevent rejected runs from editing data via the Continue Wizard. Currently both recall and reject map model status to `cancelled`, so the wizard's condition check (`status IN ['draft', 'cancelled']`) allows rejected runs to access the wizard and modify data. The wizard should be read-only for rejected runs, or the `finalize()` method should block resubmission when the most recent workflow was rejected. | Low |

#### Known Gap: Rejected Runs Can Edit via Continue Wizard

**Status:** Accepted for now, documented for future.

Both recall and reject set `PayrollRun.status` to `'cancelled'` (via `SyncPayrollRunStatus` listener). The "Continue Wizard" moreAction condition checks `status IN ['draft', 'cancelled']`, so rejected runs can still open the wizard and edit data. The `finalize()` method in `PayrollRunWizard` does not check whether the most recent workflow was rejected before starting a new one.

**Current mitigation:** The "Resubmit for Approval" button on the detail page checks `$this->run->workflow?->status === 'cancelled'` and is hidden for rejected runs. However, a determined user could still use the wizard to edit data and call `finalize()`, which would start a new workflow on a rejected run.

**Future fix options:**
1. Add a `workflow.status` check to `PayrollRunWizard::finalize()` to block submission when the most recent workflow was rejected
2. Make the wizard read-only (disable save/submit buttons) when `workflow.status === 'rejected'`
3. Split the moreAction into two: "Continue Wizard" (draft only) and "View Run" (cancelled/rejected, read-only)

### Immediate Consuming App Fix (Implemented)

Added a "Resubmit for Approval" button to `PayrollRunDetail` that appears when `effectiveStatus() === 'cancelled'`. This calls `WorkflowEngine::start()` to create a new workflow, giving the initiator a path to resubmit after recall without going through the wizard.

---

## Session Progress Log

### Session 3 — Recall/Reject Flow & Wizard Fixes (2026-08-31)

**Recall Confirmation Bug:**
- Fixed `confirmAction()` in [`ApprovalActions.php`](src/Http/Livewire/Approvals/ApprovalActions.php) and [`ApprovalPanel.php`](src/Http/Livewire/Approvals/ApprovalPanel.php) — added missing `'recall'` branch. Previously clicking "Confirm Recall" did nothing.

**Resubmit After Recall:**
- Added `resubmitForApproval()` method and "Resubmit for Approval" button to [`PayrollRunDetail`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php) — appears when `effectiveStatus() === 'cancelled'` and `workflow.status === 'cancelled'` (recalled, not rejected).
- Uses library `AlertModal` for confirmation (not browser `wire:confirm`).
- Fixed `dispatch('showAlert', ...)` calls to use associative arrays matching `AlertModal::show(array $params)`.

**Continue Wizard for Cancelled Runs:**
- Added `'cancelled'` to the "Continue Wizard" moreAction condition in [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php).
- Added `'action' => 'continue_wizard'` key to fix permission gating (was checking non-existent `_payroll-run` permission).

**Wizard Empty UI Fix:**
- Root cause: `PayrollRunWizard::finalize()` sets `current_step = 4`, but wizard only has 3 steps. Blade template has no UI for step 4.
- Fix 1: [`SyncPayrollRunStatus::handleWorkflowRecalled()`](app/Modules/Payroll/Listeners/SyncPayrollRunStatus.php) now resets `current_step` to 3 on recall.
- Fix 2: [`PayrollRunWizard::mount()`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunWizard.php) clamps `currentStep` to `min(..., 3)` as safety net.

**Recall vs. Reject Distinction:**
- Both map workflow status to model `cancelled` via `SyncPayrollRunStatus` listener.
- `$canResubmit` flag checks `$this->run->workflow?->status === 'cancelled'` — only shows Resubmit for recalled runs, not rejected.
- Fixed [`HasWorkflow::workflow()`](src/Traits/Workflows/HasWorkflow.php) — added `->latest()` so the relationship returns the most recent workflow (was returning arbitrary/oldest row without ordering).
- Eager-loaded `workflow` in [`PayrollRunDetail::mount()`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php).

**Known Gap (Documented):**
- Rejected runs can still access Continue Wizard and edit data (both recall and reject → model `cancelled`). Resubmit button is hidden, but `finalize()` in wizard doesn't check workflow status. Documented in Appendix C for future fix.