# QuickerFaster UI Library — Workflow Definition Wizard UX Architecture

> **Package**: `quicker-faster/ui-library`  
> **Namespace**: `QuickerFaster\UILibrary\`  
> **Status**: Architecture Design  
> **Scope**: Workflow definition data model, wizard UX, and plug-in pattern for consuming apps  

**Related files**: [`21-approval-infrastructure-analysis.md`](./21-approval-infrastructure-analysis.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`00-index.md`](../README.md)

---

## 1. Overview

### 1.1 Purpose

The library's [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) is the preferred, fully-migrated workflow engine — but workflow **definitions** are currently hard-coded in PHP config files (`config('ui-library.workflows.definitions.{key}')`). There is no UI for non-developers to define workflows.

This document designs:

1. **A persistent tier model** — database tables that store workflow definitions (initiator/reviewer/authorizer tiers with resolution modes), replacing or augmenting config-file definitions.
2. **A step-by-step wizard UX** — a multi-step Livewire component where administrators configure tiers with specific behaviors through a Bootstrap 5 wizard interface.
3. **A plug-in pattern** — how consuming apps attach a defined workflow to their business entities (e.g., a `LeaveRequest` model gets linked to the "Leave Request Approval" workflow definition).

### 1.2 Relationship to Existing Infrastructure

| Concern | Current State | This Design |
|---------|--------------|-------------|
| Workflow definitions | Config arrays in `ui-library.workflows.definitions` | `WorkflowDefinition` + `WorkflowDefinitionStep` models backed by DB tables; config remains as a fallback/bootstrap source |
| Engine consumption | [`WorkflowEngine::start()`](../../src/Services/Workflow/WorkflowEngine.php:17) reads config via `getDefinition()` | `start()` resolves from DB first, falls back to config |
| `Workflowable` contract | Entity returns a string `definition_key` | Unchanged — entity still returns a key; the resolver maps key → definition |
| Runtime `WorkflowStep` | Created per-instance from config at `start()` time | Created per-instance from the stored `WorkflowDefinitionStep` records at `start()` time |
| Approval UI | Legacy `ApprovalActions` + `ApprovalHistoryTimeline` wired to `ApprovalEngine` | Out of scope (see §21-approval-infrastructure-analysis.md Phases 4-5); this design covers only **definition-time** UX |

### 1.3 Guiding Principles

- **DB-first definitions** — workflow definitions are stored in the database so they can be managed through the UI. Config files serve as seed/defaults, not the runtime source of truth.
- **Contract-driven customization** — any extensibility point (who can approve, how tiers resolve) must be a contract in [`src/Contracts/`](../../src/Contracts/).
- **Follow existing patterns** — the wizard follows the Admin module pattern (Blade view → `<x-qf::navigation-layout>` → `<livewire:qf.*>`) and uses Bootstrap 5 with vanilla JS only (no Alpine.js, no Vue).
- **No `App\Modules\*` dependencies** — the library is standalone.

---

## 2. Tier Model — Data Architecture

### 2.1 Decision: Dedicated `WorkflowDefinition` + `WorkflowDefinitionStep` Models

A definition is a **template** for runtime `Workflow` instances. The template stores tier configuration as ordered steps.

**Why a separate model instead of overloading the existing `Workflow`/`WorkflowStep` tables?**

- `Workflow` and `WorkflowStep` are **runtime** records (one per submitted entity). They capture instance state: who approved, when, comments, current step pointer.
- `WorkflowDefinition` and `WorkflowDefinitionStep` are **template** records (one per workflow type). They capture the design: tier types, assigned users/roles, resolution modes, sequence ordering.
- Mixing them would make querying ("show me all definitions" vs "show me all active approvals") ambiguous and would complicate the wizard's save/update logic.

### 2.2 Database Schema

#### 2.2.1 `workflow_definitions` Table

| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint (PK) | Primary key |
| `key` | string(100) | Unique machine key (e.g., `leave_request`, `payroll_run`). This is what `Workflowable::getWorkflowDefinitionKey()` returns. |
| `name` | string(255) | Human-readable label (e.g., "Leave Request Approval") |
| `description` | text | Optional description shown in the wizard summary and listing |
| `entity_type` | string(255) | Descriptive label only (e.g., "Purchase Order"). NOT used to match entities to definitions — `key` is the sole lookup key. Displayed in the wizard summary and list page. |
| `is_active` | boolean | Toggle; inactive definitions won't start new workflows |
| `notifications` | json | Optional notification configuration: `{"enabled": true, "types": {"submitted": "workflow_submitted", "approved": "workflow_approved", "rejected": "workflow_rejected", "recalled": "workflow_recalled"}}`. `enabled` is derived from the four individual notification toggles; each `types.*` value is a fixed template name when its toggle is on (null when off). Nullable; null means "fall back to config". |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Unique index**: `key` (one definition per business entity type).

#### 2.2.2 `workflow_definition_steps` Table

| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint (PK) | Primary key |
| `workflow_definition_id` | bigint (FK) | Parent definition |
| `sequence` | int | Order within the definition (1 = first tier, 2 = second, etc.) |
| `tier_type` | string(20) | `initiator`, `review`, `authorizer` |
| `name` | string(255) | Display name for this tier/step (e.g., "Manager Review", "HR Director") |
| `description` | text | Optional instructions shown to users at this step |
| `resolution_mode` | string(10) | `any` (parallel — any one assignee can act) or `all` (every assignee must act) |
| `assignees` | json | Who can act at this tier. See §2.3 for the structure. |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Foreign key**: `workflow_definition_id` → `workflow_definitions.id` with cascade delete.

**Index**: `[workflow_definition_id, sequence]` unique — no two steps at the same position.

#### 2.2.3 Migration File

**New file**: [`Database/Migrations/2026_08_15_000003_create_workflow_definition_tables.php`](../../Database/Migrations/2026_08_15_000003_create_workflow_definition_tables.php)

### 2.3 Tier Types and Resolution Behavior

| Tier Type | `tier_type` value | Resolution Behavior | `resolution_mode` | Notes |
|-----------|-------------------|---------------------|-------------------|-------|
| **Initiator** | `initiator` | ANY ONE of the assigned initiators can submit the workflow | Always `any` | If no specific initiators are assigned, anyone with access to the entity can initiate. |
| **Review** | `review` | Serial between review steps, but within a single step, optional parallelism | `any` or `all` per step | Multiple `review` steps form a chain: Step 1 must complete before Step 2. Within a step, `any` means one reviewer suffices; `all` means every assignee must review. |
| **Authorizer** | `authorizer` | ANY ONE authorizer can give final approval | Always `any` | This is the final tier. When completed, the workflow is approved. The engine enforces `any` at hydration time regardless of the stored `resolution_mode` value — a manually-authored or DB-edited authorizer row with `resolution_mode = 'all'` is silently forced to `any` (see [`WorkflowEngine::hydrateFromModel()`](../../src/Services/Workflow/WorkflowEngine.php)). |

**Sequence enforcement**: Steps are ordered by `sequence`. The engine always processes them in order. The wizard enforces: initiator tier(s) come first, review tiers in the middle, authorizer tier(s) last.

### 2.4 Assignee Structure

The `assignees` JSON column stores who can act at a tier. It uses a **self-describing `ids` array** where the type of each entry determines its meaning:

- **Integer** → user ID (already resolved)
- **String** → role name (resolved to user IDs at runtime by `ApproverResolver`)

The `mode` field (`users`, `roles`, `mixed`) is **derived/informational** — it is computed from the `ids` array and stored for convenience, but the `ids` array is the authoritative source.

```json
{
    "mode": "users",
    "ids": [1, 5, 23]
}
```

```json
{
    "mode": "roles",
    "ids": ["manager", "finance_director"]
}
```

```json
{
    "mode": "mixed",
    "ids": [1, "manager"]
}
```

**Resolution at runtime**: The `WorkflowEngine` passes the `ids` array to the configured `ApproverResolver` contract (see §5.3). The default `DefaultApproverResolver` treats integers as already-resolved user IDs (pass-through) and strings as role names (queried by `name` on the configured role model, collecting all users holding that role). This is the customization point for Spatie vs. custom role implementations.

### 2.5 Review Tier — Serial Chain with Optional Parallel Alternatives

This is the most complex tier type. The design uses **multiple `workflow_definition_steps` rows**, all with `tier_type = 'review'`, ordered by `sequence`.

**Example**: A "Leave Request" workflow with:
- One initiator tier
- Two review steps (Manager → HR Director)
- Manager step has 3 parallel alternatives (any one can review)
- HR Director step has 1 reviewer
- One authorizer tier

Produces 4 `workflow_definition_steps` rows:

| sequence | tier_type | name | resolution_mode | assignees |
|----------|-----------|------|-----------------|-----------|
| 1 | initiator | "Employee" | any | `{mode: "roles", ids: ["employee"]}` |
| 2 | review | "Manager Review" | any | `{mode: "users", ids: [5,8,12]}` |
| 3 | review | "HR Director" | any | `{mode: "users", ids: [3]}` |
| 4 | authorizer | "Final Authorization" | any | `{mode: "roles", ids: ["super_admin"]}` |

The `resolution_mode` column controls behavior within a step:
- `any` — one assignee acts, the step is satisfied, advance to next.
- `all` — every assignee must act before the step is satisfied. This is **fully enforced at runtime** by [`WorkflowEngine::approveAllMode()`](../../src/Services/Workflow/WorkflowEngine.php): individual approvals are tracked as [`WorkflowAction`](../../src/Models/WorkflowAction.php) rows (since the runtime `WorkflowStep.approved_by` column can only hold one user), and the step only advances once every resolved assignee has approved. See §5.1.

### 2.6 Eloquent Models

#### 2.6.1 `WorkflowDefinition`

**New file**: [`src/Models/WorkflowDefinition.php`](../../src/Models/WorkflowDefinition.php)

```php
namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    protected $fillable = [
        'key', 'name', 'description', 'entity_type', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowDefinitionStep::class)
            ->orderBy('sequence');
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
```

#### 2.6.2 `WorkflowDefinitionStep`

**New file**: [`src/Models/WorkflowDefinitionStep.php`](../../src/Models/WorkflowDefinitionStep.php)

```php
namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDefinitionStep extends Model
{
    protected $fillable = [
        'workflow_definition_id', 'sequence', 'tier_type',
        'name', 'description', 'resolution_mode', 'assignees',
    ];

    protected $casts = [
        'assignees' => 'array',
        'sequence' => 'integer',
    ];

    public function definition()
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function isInitiator(): bool
    {
        return $this->tier_type === 'initiator';
    }

    public function isReview(): bool
    {
        return $this->tier_type === 'review';
    }

    public function isAuthorizer(): bool
    {
        return $this->tier_type === 'authorizer';
    }
}
```

### 2.7 Relationship to Config Definitions

The existing config-based definitions (`ui-library.workflows.definitions`) remain supported as a **fallback and bootstrap mechanism**:

1. If a `WorkflowDefinition` row exists for a given `key`, the engine uses the DB definition.
2. If no DB row exists, the engine falls back to the config definition (current behavior).
3. A `php artisan ui-library:seed-workflow-definitions` command can be created later to seed the DB from config files.

The [`WorkflowEngine::getDefinition()`](../../src/Services/Workflow/WorkflowEngine.php:136) method is updated to:

```php
public function getDefinition(string $key): ?array
{
    // 1. Try DB
    $definition = WorkflowDefinition::where('key', $key)->where('is_active', true)->first();
    if ($definition) {
        return $this->hydrateFromModel($definition);
    }

    // 2. Fall back to config
    return config("ui-library.workflows.definitions.{$key}");
}
```

---

## 3. Wizard UX — Livewire Component Design

### 3.1 Component Structure

The wizard is a **single Livewire component** with a multi-step Blade view. It follows the same pattern as the access-control-management page: a Blade file that drops a `<livewire:qf.*>` component inside `<x-qf::navigation-layout>`.

#### 3.1.1 File Inventory

| Role | Path | Type |
|------|------|------|
| **Livewire component** | [`src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | New |
| **Blade view** | [`src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php`](../../src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php) | New |
| **Blade page** | [`src/Core/Admin/Resources/views/admin/workflow-definition-wizard.blade.php`](../../src/Core/Admin/Resources/views/admin/workflow-definition-wizard.blade.php) | New |
| **List Livewire component** | [`src/Http/Livewire/Workflows/WorkflowDefinitionList.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionList.php) | Deprecated |
| **List Blade view** | [`src/Resources/views/livewire/workflows/workflow-definition-list.blade.php`](../../src/Resources/views/livewire/workflows/workflow-definition-list.blade.php) | Deprecated |
| **List Blade page** | [`src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php`](../../src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php) | New (embeds `qf.data-table`) |
| **DataTable config** | [`src/Core/Admin/Data/workflow_definition.php`](../../src/Core/Admin/Data/workflow_definition.php) | New |
| **Route** | Add to [`src/Core/Admin/Routes/web.php`](../../src/Core/Admin/Routes/web.php) | Modified |
| **Navigation config** | [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php) | Modified (add page entry) |

#### 3.1.2 Blade Page (Wrapper)

```blade
{{-- src/Core/Admin/Resources/views/admin/workflow-definition-wizard.blade.php --}}
<x-qf::navigation-layout configKey="admin.workflow_definition" context="Workflow Definitions" moduleName="admin" :overrides=[]>
    <div class="row">
        <div class="col-12">
            <livewire:qf.workflow-definition-wizard />
        </div>
    </div>
</x-qf::navigation-layout>
```

#### 3.1.3 Route Entry

Added to [`src/Core/Admin/Routes/web.php`](../../src/Core/Admin/Routes/web.php):

```php
Route::get('/admin/workflow-definition-wizard', function () {
    return view('qf-core::admin.workflow-definition-wizard');
})->name('admin.workflow-definition-wizard');
```

> **Routing note**: This follows the existing pattern from the [`access-control-management`](../../src/Core/Admin/Resources/views/admin/access-control-management.blade.php) page — an explicit named route pointing to a Blade view under `qf-core::admin.*`. No catch-all dependency.

### 3.2 Step Tracker (Progress Indicator)

The wizard uses a **Bootstrap 5 horizontal step indicator** rendered as a row of numbered circles connected by lines:

```
  ●━━━━━●━━━━━●━━━━━●━━━━━●
  1      2      3      4      5
Details Init-  Review- Auth-  Summary
        iators  ers     orizers
```

**Implementation**: Pure Bootstrap 5 CSS — a `d-flex align-items-center` row with styled `<div>` elements. The active step is highlighted with the Bootstrap primary color. Completed steps show a checkmark. The step indicator is rendered at the top of the Blade view and is purely presentational (the Livewire `$step` property drives which panel is shown).

**No Alpine.js, no Vue**: The step transitions happen via Livewire `wire:click` on "Next"/"Back" buttons that call `$this->step = N` in PHP.

### 3.3 Step 1 — Workflow Details

**Purpose**: Capture the definition's identity.

**Fields**:

| Field | Input Type | Validation | Notes |
|-------|-----------|------------|-------|
| Name | `<input type="text">` | Required, max 255 chars | e.g., "Leave Request Approval" |
| Key | `<input type="text">` | Required, unique, slug format (`[a-z0-9_]+`), max 100 chars | Auto-generated from name via Livewire `updatedName()` hook but user-editable. e.g., "leave_request" |
| Description | `<textarea>` | Optional, max 1000 chars | Shown in wizard summary and definition listing |
| Entity Type | `<input type="text">` | Required, max 255 chars | The business entity label. e.g., "Leave Request", "Payroll Run" |
| Active | `<input type="checkbox" class="form-check-input">` | Default: checked | Bootstrap toggle switch |

**UX behavior**:
- The `key` field auto-slugifies from `name` as the user types (debounced via Livewire `wire:model.live.debounce.500ms`).
- If the user manually edits the key, auto-generation stops (tracked by a `$keyManuallyEdited` flag).
- "Next" button validates uniqueness of the key (checks DB + config for duplicates).

### 3.4 Step 2 — Add Initiators

**Purpose**: Define who can start/submit this workflow.

**UX layout**:

```
┌─────────────────────────────────────────────────────────────┐
│  INITIATORS                                                  │
│                                                              │
│  Assignment Mode:  ○ Specific Users  ● By Role  ○ Mixed     │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Search users or roles...  [🔍 Search]                │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  Selected Initiators:                                        │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ 👤 John Doe (user)                          [✕ Remove] │    │
│  │ 👤 Jane Smith (user)                        [✕ Remove] │    │
│  │ 👥 Manager (role)                           [✕ Remove] │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ℹ️  Anyone listed here can initiate this workflow.         │
│     Resolution: ANY ONE of these can submit.                 │
│                                                              │
│  [← Back]                                    [Next →]       │
└─────────────────────────────────────────────────────────────┘
```

**Implementation details**:

- **Assignment mode radio buttons**: Pure Bootstrap 5 `.form-check` / `.btn-group` with `wire:model.live="assigneeMode"`. Switching modes clears the current selection with a confirmation if items exist.
- **Search input**: A `<input type="text" wire:model.live.debounce.300ms="searchTerm">` that triggers a Livewire method to search both users and roles (via configurable `UserProvider` contract — default uses `config('ui-library.user.model')` and Spatie roles).
- **Search results dropdown**: Rendered as a Bootstrap dropdown-menu below the search input. Results show user avatars (via `ApproverLabelResolver` — see §5.2) with "Add" buttons. Clicking "Add" appends to `$initiators` array.
- **Selected list**: A `<div>` with `list-group` items showing name + type badge + remove button (`wire:click="removeInitiator($index)"`).
- **Info banner**: A Bootstrap `.alert.alert-info` with the parallel resolution note.

**Data model**: Each initiator is stored as an entry in the `assignees` JSON of the step (see §2.4). The `resolution_mode` for initiator steps is always `any`.

### 3.5 Step 3 — Add Reviewers (Chain Builder)

**Purpose**: Build a serial chain of review steps, each with optional parallel alternatives.

**UX layout**:

```
┌─────────────────────────────────────────────────────────────┐
│  REVIEW CHAIN                                                │
│                                                              │
│  Reviewers must approve IN ORDER. Within each step,          │
│  if multiple reviewers are added, ANY ONE can approve.       │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ STEP 1 — "Manager Review"                  [✕ Remove] │    │
│  │ ┌───────────────────────────────────────────────┐    │    │
│  │ │ Resolution: ● ANY one  ○ ALL must approve     │    │    │
│  │ └───────────────────────────────────────────────┘    │    │
│  │ Reviewers:                                            │    │
│  │ 👤 Alice M. (user)                        [✕ Remove]  │    │
│  │ 👤 Bob K. (user)                           [✕ Remove]  │    │
│  │ 👤 Carol T. (user)                         [✕ Remove]  │    │
│  │ [+ Add Reviewer to this step]                          │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ STEP 2 — "HR Director Review"             [✕ Remove] │    │
│  │ ┌───────────────────────────────────────────────┐    │    │
│  │ │ Resolution: ● ANY one  ○ ALL must approve     │    │    │
│  │ └───────────────────────────────────────────────┘    │    │
│  │ Reviewers:                                            │    │
│  │ 👥 HR Director (role)                     [✕ Remove]  │    │
│  │ [+ Add Reviewer to this step]                          │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  [+ Add Review Step]                                         │
│                                                              │
│  [← Back]                                    [Next →]       │
└─────────────────────────────────────────────────────────────┘
```

**Implementation details**:

- **Review steps** are stored in a Livewire property: `$reviewSteps = [['name' => '', 'resolution_mode' => 'any', 'assignees' => []]]`.
- **"Add Review Step"** button: `wire:click="addReviewStep"` appends a new empty step to `$reviewSteps`.
- **"Remove Step"** button: `wire:click="removeReviewStep($index)"` removes it from the array. Confirmation if the step has assignees.
- **"Add Reviewer"** (within a step): Opens a search similar to Step 2 but scoped to that step's index. Results append to `$reviewSteps[$index]['assignees']`.
- **"Remove Reviewer"**: `wire:click="removeReviewer($stepIndex, $assigneeIndex)"`.
- **Resolution mode toggle**: Bootstrap `.btn-group` with `wire:model="reviewSteps.{index}.resolution_mode"`.
- **Step name**: An inline `<input>` with `wire:model="reviewSteps.{index}.name"`. Defaults to "Review Step N" if empty.
- **Reordering**: "Move Up" / "Move Down" buttons (`wire:click="moveReviewStep($index, 'up')"` / `wire:click="moveReviewStep($index, 'down')"`) swap array positions. **No drag-and-drop** — simple up/down buttons avoid external dependencies. If SortableJS is desired later, it can be added as an opt-in enhancement via vanilla JS.

**Data model**: Each review step becomes one `workflow_definition_step` row with `tier_type = 'review'`. The `assignees` JSON stores the parallel alternatives.

### 3.6 Step 4 — Add Authorizers

**Purpose**: Define who can give final approval.

**UX layout**:

```
┌─────────────────────────────────────────────────────────────┐
│  AUTHORIZERS                                                 │
│                                                              │
│  Assignment Mode:  ○ Specific Users  ● By Role  ○ Mixed     │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Search users or roles...  [🔍 Search]                │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  Selected Authorizers:                                       │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ 👤 CFO Name (user)                           [✕ Remove] │    │
│  │ 👥 Finance Director (role)                   [✕ Remove] │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ℹ️  Any ONE of these authorizers can give final approval.  │
│     Resolution: ANY ONE of these can approve.                │
│                                                              │
│  [← Back]                                    [Next →]       │
└─────────────────────────────────────────────────────────────┘
```

**Implementation**: Identical to Step 2 (Initiators) in structure, just with different labels and the `tier_type = 'authorizer'`. The component can reuse the same search/selection logic.

**Mode toggle behaviour**: The assignment-mode toggle (users/roles/mixed) is a UI-only filter. Switching it **preserves** existing selections — only the transient search state is cleared. The persisted `assignees.mode` value is always derived from the actual items, never from the toggle position. This prevents the admin from accidentally losing their authorizer selections when switching between user and role views.

### 3.7 Step 5 — Summary & Save

**Purpose**: Review the entire workflow definition before saving.

**UX layout**:

```
┌─────────────────────────────────────────────────────────────┐
│  SUMMARY                                                     │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Workflow Details                                     │    │
│  │ Name: Leave Request Approval                         │    │
│  │ Key: leave_request                                   │    │
│  │ Entity: Leave Request                                │    │
│  │ Status: Active                                       │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Initiators (Any one can submit)                      │    │
│  │ • John Doe (user)                                    │    │
│  │ • Jane Smith (user)                                  │    │
│  │ • Manager (role)                                     │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Review Chain (Must approve in order)                 │    │
│  │                                                      │    │
│  │ Step 1: Manager Review (Any one)                     │    │
│  │ • Alice M. (user)                                    │    │
│  │ • Bob K. (user)                                      │    │
│  │ • Carol T. (user)                                    │    │
│  │                                                      │    │
│  │ Step 2: HR Director Review (Any one)                 │    │
│  │ • HR Director (role)                                 │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │ Authorizers (Any one can approve)                    │    │
│  │ • CFO Name (user)                                    │    │
│  │ • Finance Director (role)                            │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  [← Back]                            [Save Workflow]        │
└─────────────────────────────────────────────────────────────┘
```

**Save action**: `wire:click="save"` — validates all data, creates/updates the `WorkflowDefinition` and its `WorkflowDefinitionStep` rows in a DB transaction, then redirects to a listing page or shows a success message. Validation failures highlight the offending step and auto-navigate to it.

The `finish()` action re-validates `workflowName`, `workflowKey`, and `authorizers` before saving, so jumping directly to the Summary via `goToStep(4)` cannot persist an incomplete definition — it redirects back to the relevant step with an error.

### 3.8 Validation Rules

| Step | Rule | Applied |
|------|------|---------|
| Step 1 | `name` required, max:255; `key` required, unique:workflow_definitions, regex:/^[a-z0-9_]+$/; `entity_type` required, max:255 | On "Next" click from Step 1 |
| Step 2 | At least one initiator (user or role) OR a "allow all" toggle | On "Next" click from Step 2 |
| Step 3 | Review tier is optional (0 steps is valid). Any step with a name must have at least one assignee; empty name + no assignees is the seeded blank and is skipped | On "Next" click from Step 3 |
| Step 4 | At least one authorizer (user or role) | On "Next" click from Step 4 |
| Final | `finish()` re-validates required fields even when the Summary is reached via direct navigation: `workflowName` non-empty, `workflowKey` non-empty, `authorizers` non-empty | On "Save" click from Step 5; failures redirect back to the relevant step |

> **Error-bag reset**: [`validateCurrentStep()`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) calls `resetErrorBag()` before validating, so errors from a previously-failed step are cleared on the next attempt instead of persisting across navigation.

### 3.9 Livewire Component Class Structure

**New file**: [`src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php)

```php
namespace QuickerFaster\UILibrary\Http\Livewire\Workflows;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\WorkflowDefinition;
use QuickerFaster\UILibrary\Models\WorkflowDefinitionStep;

class WorkflowDefinitionWizard extends Component
{
    // Step tracking
    public int $step = 1;
    public const TOTAL_STEPS = 5;

    // Step 1: Details
    public string $name = '';
    public string $key = '';
    public string $description = '';
    public string $entityType = '';
    public bool $isActive = true;
    public bool $keyManuallyEdited = false;

    // Step 2: Initiators
    public string $initiatorMode = 'roles';  // 'users', 'roles', 'mixed'
    public array $initiators = [];
    public string $initiatorSearchTerm = '';

    // Step 3: Reviewers
    public array $reviewSteps = [];

    // Step 4: Authorizers
    public string $authorizerMode = 'roles';
    public array $authorizers = [];
    public string $authorizerSearchTerm = '';

    // Search results
    public array $searchResults = [];

    // Edit mode (existing definition)
    public ?int $definitionId = null;

    public function mount(?int $definitionId = null): void { /* ... */ }
    public function updatedName(string $value): void { /* auto-slugify key */ }
    public function updatedKey(): void { /* mark manually edited */ }

    // Navigation
    public function nextStep(): void { /* validate + advance */ }
    public function previousStep(): void { /* go back */ }
    public function goToStep(int $step): void { /* jump to step */ }

    // Initiator / Authorizer search & management
    public function updatedInitiatorSearchTerm(): void { /* search users/roles */ }
    public function addInitiator(array $assignee): void { /* append */ }
    public function removeInitiator(int $index): void { /* remove */ }
    public function updatedAuthorizerSearchTerm(): void { /* search */ }
    public function addAuthorizer(array $assignee): void { /* append */ }
    public function removeAuthorizer(int $index): void { /* remove */ }

    // Review chain management
    public function addReviewStep(): void { /* append empty step */ }
    public function removeReviewStep(int $index): void { /* remove */ }
    public function moveReviewStep(int $index, string $direction): void { /* reorder */ }
    public function addReviewer(int $stepIndex, array $assignee): void { /* append */ }
    public function removeReviewer(int $stepIndex, int $assigneeIndex): void { /* remove */ }

    // Save
    public function save(): void { /* validate all, persist to DB */ }

    public function render()
    {
        return view('qf::livewire.workflows.workflow-definition-wizard');
    }
}
```

### 3.10 Edit Mode

`mount()` reads `definitionId` from the request query string (the admin wrapper embeds the component with no mount params), then [`loadDefinition()`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) loads the existing `WorkflowDefinition` with its steps and pre-populates all fields — details, initiators, review chain, authorizers, and the four notification toggles. The "Save" button updates the existing record instead of creating a new one.

When editing, `mount()` also discards any stale in-progress session draft before loading the DB record, so `restoreFromSession()` cannot overwrite the freshly loaded data.

### 3.11 Workflow Definition List Page

The list page is rendered by the generic [`qf.data-table`](../../src/Http/Livewire/DataTables/DataTable.php) component driven by the [`admin.workflow_definition`](../../src/Core/Admin/Data/workflow_definition.php) config. The previous [`WorkflowDefinitionList`](../../src/Http/Livewire/Workflows/WorkflowDefinitionList.php) component and its Blade view are deprecated.

- **Component**: `qf.data-table` with `configKey="admin.workflow_definition"`
- **DataTable config**: [`workflow_definition.php`](../../src/Core/Admin/Data/workflow_definition.php) — fields (`name`, `key`, `entity_type`, `is_active`, `description`, `notifications`, `created_at`, `updated_at`), table/list switch views, and a `moreActions` "Edit in Wizard" entry (`action => 'edit'`) linking to `/admin/workflow-definition-wizard?definitionId={id}`
- **Admin page wrapper**: [`workflow-definitions.blade.php`](../../src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php) embeds `<livewire:qf.data-table configKey="admin.workflow_definition" />`

> **Row-action permission fix**: `canPerformAction()` expects a string permission. The `moreActions` entry supplies `'action' => 'edit'`, and both [`row-actions.blade.php`](../../src/Resources/views/livewire/data-tables/partials/row-actions.blade.php) and [`DataTable::handleRowAction()`](../../src/Http/Livewire/DataTables/DataTable.php) extract that string (`$action['action'] ?? $action['permission'] ?? ''`) before calling `canPerformAction()` — fixing the TypeError caused by passing the whole action array.

The wizard's completion card ("Back to Workflows") and its cancel action both return to `/admin/workflow-definitions`, so after saving or cancelling the user lands on the list rather than the empty wizard URL.

---

## 4. Plug-In Pattern — Attaching Workflows to Business Entities

### 4.1 Decision: `Workflowable` Contract + DB Definition Key

The existing [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php) contract is the plug-in point. A consuming app's entity implements it and returns a definition key. At runtime, the engine resolves that key to a definition.

**The flow**:

```
Consuming App                          Library
─────────────                          ───────

LeaveRequest implements            WorkflowEngine::start()
  Workflowable                          │
  ├─ getWorkflowableId(): 42           ├─ reads $entity->getWorkflowDefinitionKey()
  ├─ getWorkflowDefinitionKey():       │   → "leave_request"
  │   "leave_request"                  ├─ calls getDefinition("leave_request")
  └─ getWorkflowContext(): [...]       │   ├─ 1. DB: WorkflowDefinition::where('key', ...)
                                       │   │   → WorkflowDefinition + steps
                                       │   └─ 2. Fallback: config()
                                       ├─ Creates Workflow (runtime instance)
                                       └─ Creates WorkflowStep rows from definition
```

**No changes to `Workflowable`** — the contract already returns a string key. The `WorkflowEngine` is the only component that needs updating to resolve DB definitions.

### 4.2 Config vs. DB vs. Trait — What Goes Where

| Concern | Where | Why |
|---------|-------|-----|
| Workflow definition (tiers, assignees, resolution) | `workflow_definitions` + `workflow_definition_steps` tables | Managed through the wizard UI; persisted data |
| Default/seed definitions | `config('ui-library.workflows.definitions')` | Shipped with the library as bootstrap data; fallback when no DB definition exists |
| Entity linking to a definition | `Workflowable::getWorkflowDefinitionKey()` | The entity declares which workflow it uses — this is code, not config, because it's part of the model's identity |
| Approver resolution (role→users) | `ApproverResolver` contract | Customization point for different apps (Spatie roles vs. custom auth) |

### 4.3 Consuming App Example

```php
// In the consuming app (e.g., Quick-HR)
namespace App\Modules\Hr\Models;

use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model implements Workflowable
{
    public function getWorkflowableId(): int|string
    {
        return $this->id;
    }

    public function getWorkflowDefinitionKey(): string
    {
        return 'leave_request';
    }

    public function getWorkflowContext(): array
    {
        return [
            'department_id' => $this->employee->department_id,
            'days' => $this->total_days,
        ];
    }
}
```

The consuming app's admin uses the wizard at `/admin/workflow-definition-wizard` to define the `leave_request` workflow. The engine picks it up automatically because the key matches.

### 4.4 Navigation Entry for Workflows

The "Workflows" context group appears in the Administration module's sidebar. The group URL points at the list page and a "New Workflow" context item points at the wizard, per [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php):

- `context_groups['Workflows']['url']` → `admin/workflow-definitions` (list page)
- `contexts['Workflows']` contains:
  - `workflow_definition` → `/admin/workflow-definitions` (Workflow Definitions list)
  - `workflow_definition_create` → `/admin/workflow-definition-wizard` (New Workflow)

The list page is a minimal Bootstrap table (see §3.11) rather than a full CRUD DataTable, providing an index of existing definitions and an edit entry point back into the wizard.

---

## 5. Integration with Existing Systems

### 5.1 WorkflowEngine Integration

The [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) implements these behaviours:

| Behaviour | Method | Description |
|-----------|--------|-------------|
| DB-first resolution | `getDefinition()` | Try `WorkflowDefinition` model first, fall back to config |
| Definition hydration | `hydrateFromModel()` | Converts `WorkflowDefinition` + `WorkflowDefinitionStep` rows to the array format `start()` expects. **Initiator steps are collected into a separate `initiators` key**; only `review` and `authorizer` steps populate the `steps` array. |
| Initiator authorization | `start()` | Before creating the workflow, resolves initiator assignees via `ApproverResolver` and calls `ApprovalGuard::canSubmit()`. Throws `AuthorizationException` on failure. Super-admin/bypass roles are honoured. |
| Duplicate prevention | `start()` | Calls `hasActiveWorkflow()` before creating a new workflow; throws `RuntimeException` if one already exists for the entity. |
| Runtime step creation | `start()` | Creates `WorkflowStep` rows **only for review and authorizer tiers**. Initiator steps are not persisted as runtime steps — they are used solely for submit-time authorization. The first review/authorizer step becomes `current_step`. |
| Auto-approve (no gates) | `start()` | If the definition yields **zero** runtime steps (only initiators, no review/authorizer tiers), the workflow is marked `approved` immediately on submission — a workflow with no approval gates is auto-approved instead of remaining stuck `pending`. |
| Approver expansion | `resolveStepRecipientIds()` | Resolves role-based assignees to user IDs via `ApproverResolver` contract at notification time |
| `all` resolution mode | `approve()` → `approveAllMode()` | Tracks per-user approvals as [`WorkflowAction`](../../src/Models/WorkflowAction.php) rows. Prevents duplicate approval, resolves all required assignees, counts distinct approvers, and only advances when `count(distinct approvers) >= count(required users)`. Partial approvals leave the step pending and dispatch a partial-approval event. |
| Authorizer `any` enforcement | `hydrateFromModel()` | Authorizer steps are always forced to `approval_mode = 'any'` regardless of the stored `resolution_mode`. This guarantees "ANY ONE can authorize" semantics even if a row is manually edited to `all`. Review steps keep their stored `any`/`all` mode. |

**Hydrated definition shape** (produced by `hydrateFromModel()`):

```php
[
    'label' => 'Purchase Order Approval',
    'name' => 'Purchase Order Approval',
    'entity_type' => 'Purchase Order',
    'is_active' => true,
    'initiators' => [1, 5, 'manager'],   // mixed user IDs + role names
    'steps' => [                          // review + authorizer only
        ['name' => 'Manager Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => [5, 8, 12]],
        ['name' => 'Finance Director', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['finance_director']],
    ],
]
```

The existing `step_type` values in the current engine map to the new `tier_type` values:
- `'initiation'` (config legacy) → `'initiator'` (DB)
- `'reviewing'` (config legacy) → `'review'` (DB)
- `'authorization'` (config legacy) → `'authorizer'` (DB)

The `approval_mode` values (`any`/`all`) are identical in both systems.

### 5.2 Navigation Integration

The wizard lives under **Administration** (the existing `admin` module), not as a standalone core component. Rationale:

- Workflow definitions are an **administrative concern** — configuring who can approve what — similar to managing roles and permissions.
- The Administration module already hosts access-control, user management, and security. Workflow management fits naturally alongside these.
- This keeps the wizard's Blade page, routes, and navigation config within the existing `src/Core/Admin/` structure.

If a future consuming app wants the wizard under a different navigation context, it can override the route or create its own Blade wrapper.

### 5.3 Contracts for Extensibility

#### 5.3.1 `ApproverResolver` Contract (New)

The missing customization point identified in [`21-approval-infrastructure-analysis.md`](./21-approval-infrastructure-analysis.md) §2.3.

**New file**: [`src/Contracts/Approvals/ApproverResolver.php`](../../src/Contracts/Approvals/ApproverResolver.php)

```php
namespace QuickerFaster\UILibrary\Contracts\Approvals;

interface ApproverResolver
{
    /**
     * Resolve a mixed list of user IDs and role names into a flat list of
     * user IDs who can approve (or submit).
     *
     * Convention:
     *   - int    → already-resolved user ID (passed through as-is).
     *   - string → role name; implementations should resolve the role to
     *              the user IDs of every user holding that role.
     *
     * @param array<int|string> $roleIds Mixed user IDs (int) and role names (string).
     * @param string|null $workspaceId Optional workspace scope for multi-tenant apps.
     * @return int[] Flat list of resolved user IDs.
     */
    public function resolve(array $roleIds, ?string $workspaceId = null): array;
}
```

**Default implementation**: [`src/Services/Approvals/DefaultApproverResolver.php`](../../src/Services/Approvals/DefaultApproverResolver.php) — treats integers (and numeric strings) as already-resolved user IDs (pass-through) and strings as role names, queried via `config('permission.models.role')` (Spatie). The `$workspaceId` argument is accepted for API compatibility but **ignored by the default implementation** — the library has no workspace/company model, so workspace-scoped resolution must be supplied by a consuming app.

#### 5.3.2 `ApproverLabelResolver` Contract

Already recommended in [`21-approval-infrastructure-analysis.md`](./21-approval-infrastructure-analysis.md) §4.2 (F8). Used by the wizard to display user names and avatars in the assignee lists.

**New file**: [`src/Contracts/Approvals/ApproverLabelResolver.php`](../../src/Contracts/Approvals/ApproverLabelResolver.php)

```php
namespace QuickerFaster\UILibrary\Contracts\Approvals;

interface ApproverLabelResolver
{
    public function label($userId): string;
    public function avatar($userId): ?string;
    public function profileRoute($userId): ?string;
}
```

### 5.4 Notification Integration

The existing [`NotificationService`](../../src/Services/Notifications/NotificationService.php) and notification template system (`notification_templates` table) are wired to workflow events through [`WorkflowEngine::notifyTransition()`](../../src/Services/Workflow/WorkflowEngine.php).

Wizard-created (DB-only) definitions can now configure notifications directly:

1. The `workflow_definitions.notifications` JSON column stores `{"enabled": true, "types": {...}}` (see §2.2.1).
2. [`WorkflowEngine::notificationConfig()`](../../src/Services/Workflow/WorkflowEngine.php) reads the DB column **first** and falls back to `config("ui-library.workflows.definitions.{key}.notifications")` when the DB value is null/empty.
3. The wizard exposes **four individual notification toggle switches** on the Summary step — `$notifyOnSubmitted`, `$notifyOnApproved`, `$notifyOnRejected`, and `$notifyOnRecalled`. Each toggle maps to a fixed template name (`workflow_submitted`, `workflow_approved`, `workflow_rejected`, `workflow_recalled`).
4. The `enabled` flag is **derived** from the four toggles (`enabled = any toggle on`) and each `types.*` value is `null` when its toggle is off. The old free-text "type name" inputs and the master "Enable workflow notifications" toggle have been removed.

Channel preferences per workflow remain a separate, future concern.

### 5.5 How the Wizard-Produced Definition Flows into the Engine

```
┌──────────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│  Workflow Wizard     │     │   Database           │     │   Workflow Engine    │
│  (Livewire)          │     │                      │     │                      │
├──────────────────────┤     ├──────────────────────┤     ├──────────────────────┤
│                      │     │                      │     │                      │
│  User configures:    │     │ workflow_definitions │     │ LeaveRequest::       │
│  - Initiators        │────▶│ ├─ key: leave_request│     │ getWorkflowDef-      │
│  - Review chain      │save │ ├─ name: ...         │     │ initionKey()         │
│  - Authorizers       │     │                      │     │   → "leave_request"  │
│                      │     │ workflow_definition_ │     │                      │
│                      │     │ steps                │     │ engine->start()      │
│                      │     │ ├─ seq:1, initiator  │     │   → getDefinition()  │
│                      │     │ ├─ seq:2, review     │◀────│   SELECT * FROM      │
│                      │     │ ├─ seq:3, review     │     │   workflow_defs      │
│                      │     │ └─ seq:4, authorizer │     │   WHERE key = ?      │
│                      │     │                      │     │                      │
│                      │     │                      │     │ Creates Workflow +   │
│                      │     │                      │     │ WorkflowStep runtime │
│                      │     │                      │     │ instances            │
└──────────────────────┘     └──────────────────────┘     └──────────────────────┘
```

---

## 6. Component Inventory

### 6.1 New Files

| # | File | Type | Purpose |
|---|------|------|---------|
| 1 | [`src/Models/WorkflowDefinition.php`](../../src/Models/WorkflowDefinition.php) | Model | Definition template |
| 2 | [`src/Models/WorkflowDefinitionStep.php`](../../src/Models/WorkflowDefinitionStep.php) | Model | Individual tier/step in a definition |
| 3 | [`Database/Migrations/2026_08_15_000001_create_workflow_definition_tables.php`](../../Database/Migrations/2026_08_15_000001_create_workflow_definition_tables.php) | Migration | Tables for definitions |
| 4 | [`src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionWizard.php) | Livewire | Wizard component |
| 5 | [`src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php`](../../src/Resources/views/livewire/workflows/workflow-definition-wizard.blade.php) | Blade | Wizard view |
| 6 | [`src/Core/Admin/Resources/views/admin/workflow-definition-wizard.blade.php`](../../src/Core/Admin/Resources/views/admin/workflow-definition-wizard.blade.php) | Blade | Page wrapper |
| 7 | [`src/Contracts/Approvals/ApproverResolver.php`](../../src/Contracts/Approvals/ApproverResolver.php) | Contract | Role→user resolution |
| 8 | [`src/Contracts/Approvals/ApproverLabelResolver.php`](../../src/Contracts/Approvals/ApproverLabelResolver.php) | Contract | User display/labeling |
| 9 | [`src/Services/Approvals/DefaultApproverResolver.php`](../../src/Services/Approvals/DefaultApproverResolver.php) | Service | Default Spatie-based implementation |
| 10 | [`src/Http/Livewire/Workflows/WorkflowDefinitionList.php`](../../src/Http/Livewire/Workflows/WorkflowDefinitionList.php) | Livewire | Deprecated definition list component |
| 11 | [`src/Resources/views/livewire/workflows/workflow-definition-list.blade.php`](../../src/Resources/views/livewire/workflows/workflow-definition-list.blade.php) | Blade | Deprecated list view |
| 12 | [`src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php`](../../src/Core/Admin/Resources/views/admin/workflow-definitions.blade.php) | Blade | List page wrapper (embeds `qf.data-table`) |
| 13 | [`src/Core/Admin/Data/workflow_definition.php`](../../src/Core/Admin/Data/workflow_definition.php) | Data config | `admin.workflow_definition` DataTable config |

### 6.2 Modified Files

| # | File | Change |
|---|------|--------|
| 1 | [`src/Services/Workflow/WorkflowEngine.php`](../../src/Services/Workflow/WorkflowEngine.php) | Update `getDefinition()` for DB-first resolution; add `hydrateFromModel()`; optional: `expandAssignees()` |
| 2 | [`src/Core/Admin/Routes/web.php`](../../src/Core/Admin/Routes/web.php) | Add `/admin/workflow-definition-wizard` route |
| 3 | [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php) | Add "Workflows" context group + list and "New Workflow" entries |
| 4 | [`src/Providers/UILibraryServiceProvider.php`](../../src/Providers/UILibraryServiceProvider.php) | Bind `ApproverResolver`/`ApproverLabelResolver`; `qf.workflow-definition-list` registration deprecated |

---

## 7. File Path Conventions

All new files live under the existing `src/` tree following established conventions:

```
src/
├── Models/
│   ├── Workflow.php                      (existing)
│   ├── WorkflowStep.php                  (existing)
│   ├── WorkflowAction.php                (existing)
│   ├── WorkflowDefinition.php            (NEW — §2.6.1)
│   └── WorkflowDefinitionStep.php        (NEW — §2.6.2)
│
├── Contracts/
│   ├── Workflow/
│   │   └── Workflowable.php              (existing, unchanged)
│   └── Approvals/
│       ├── ApprovalModelResolver.php     (existing)
│       ├── ApproverResolver.php          (NEW — §5.3.1)
│       └── ApproverLabelResolver.php     (NEW — §5.3.2)
│
├── Services/
│   ├── Workflow/
│   │   └── WorkflowEngine.php            (existing, modified — §5.1)
│   └── Approvals/
│       └── DefaultApproverResolver.php   (NEW — §5.3.1)
│
├── Http/Livewire/
│   ├── Approvals/                        (existing)
│   └── Workflows/
│       ├── WorkflowDefinitionWizard.php  (NEW — §3.9)
│       └── WorkflowDefinitionList.php    (DEPRECATED — §3.11)
│
├── Resources/views/
│   └── livewire/
│       ├── approvals/                    (existing)
│       └── workflows/
│           ├── workflow-definition-wizard.blade.php  (NEW — §3.1.1)
│           └── workflow-definition-list.blade.php    (DEPRECATED — §3.11)
│
├── Core/Admin/
│   ├── Resources/views/admin/
│   │   ├── access-control-management.blade.php  (existing, reference pattern)
│   │   ├── workflow-definition-wizard.blade.php  (NEW — §3.1.2)
│   │   └── workflow-definitions.blade.php        (NEW — §3.11)
│   ├── Routes/
│   │   └── web.php                       (existing, modified — §3.1.3)
│   ├── Data/
│   │   └── workflow_definition.php       (NEW — §3.11)
│   └── Config/
│       └── navigation.php                (existing, modified — §4.4)
│
└── Database/Migrations/
    └── 2026_08_15_000001_create_workflow_definition_tables.php  (NEW — §2.2.3)
```

---

## 8. Design Decisions Summary

| Decision | Rationale |
|----------|-----------|
| Separate `WorkflowDefinition` + `WorkflowDefinitionStep` from runtime `Workflow` + `WorkflowStep` | Templates vs. instances — different lifecycle, different queries, cleaner separation |
| DB-first with config fallback | Enables wizard UI while preserving backward compatibility for apps that define workflows in config |
| `assignees` as JSON with three modes (`users`, `roles`, `mixed`) | Flexibility without over-engineering; roles can be expanded at runtime via `ApproverResolver` |
| Review chain as multiple `review` steps with ordered `sequence` | Naturally models serial progression; within-step parallelism controlled by `resolution_mode` |
| Wizard under Admin module | Follows existing pattern (`access-control-management`); administrative, not business-domain |
| Single Livewire component (not 5 separate components) | Simpler state management; all steps share one `$step` property and one save transaction |
| Up/down buttons for reordering (not drag-and-drop) | No external JS dependency; SortableJS can be added later as opt-in |
| `ApproverResolver` + `ApproverLabelResolver` as contracts | The two customization seams identified in the approval infrastructure analysis; default implementations use Spatie |

---

**Related files**: [`21-approval-infrastructure-analysis.md`](./21-approval-infrastructure-analysis.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`00-index.md`](../README.md)