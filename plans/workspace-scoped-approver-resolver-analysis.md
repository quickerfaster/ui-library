# Workspace-Scoped Approver Resolver: Design Analysis

> **Status**: Design Analysis (2026-08-30)  
> **Scope**: Library architecture decision — should the library ship a built-in `WorkspaceScopedApproverResolver`?  
> **Audience**: Library maintainers and consuming-app developers  
> **Decision**: NOT YET MADE — this document informs the decision

---

## Table of Contents

1. [What the Library Already Provides](#1-what-the-library-already-provides)
2. [The Gap](#2-the-gap)
3. [Three Options](#3-three-options)
4. [Critical Dependency Question: How to Query Users by Company ID](#4-critical-dependency-question)
5. [Independence Safeguards Evaluation](#5-independence-safeguards-evaluation)
6. [Recommendation](#6-recommendation)
7. [Conceptual Design](#7-conceptial-design)
8. [Impact on Payroll Approval Plan](#8-impact-on-payroll-approval-plan)
9. [Summary](#9-summary)

---

## 1. What the Library Already Provides

### 1.1 DefaultApproverResolver

[`DefaultApproverResolver`](src/Services/Approvals/DefaultApproverResolver.php:18) is the current shipped implementation of [`ApproverResolver`](src/Contracts/Approvals/ApproverResolver.php:5). Key characteristics:

| Aspect | Detail |
|--------|--------|
| **Role source** | `config('permission.models.role')` (Spatie Permission) |
| **Resolution logic** | Splits `$roleIds` into integers (passed through) and strings (role names); queries Spatie roles by name; collects ALL users holding those roles |
| **Workspace scoping** | Accepts `$workspaceId` for API compatibility but **ignores it** entirely |
| **Return value** | `int[]` — deduplicated user IDs |
| **External dependencies** | Spatie Permission (via `config('permission.models.role')`) |

### 1.2 ApproverResolver Contract

[`ApproverResolver`](src/Contracts/Approvals/ApproverResolver.php:5) defines one method:

```php
public function resolve(array $roleIds, ?string $workspaceId = null): array;
```

The `$workspaceId` parameter is the contract-level seam for workspace scoping. It was deliberately included in the contract signature so that consuming apps could implement scoped resolution without the library needing to change.

### 1.3 How `$workspaceId` Flows

The workspace identifier travels through the engine stack:

```
Workflowable::getWorkflowContext()['workspace_id']
  → WorkflowEngine::start() persistis on Workflow.context
  → WorkflowEngine::resolveWorkspaceId() reads context.workspace_id
  → ApprovalGuard::canApprove() / canSubmit() passies it to resolve()
  → DefaultApproverResolver::resolve() IGNORES it ← THE GAP
```

[`WorkflowEngine::resolveWorkspaceId()`](src/Services/Workflow/WorkflowEngine.php:516) extracts `context.workspace_id` and casts to `?string`.

### 1.4 Config & Binding

The binding is in [`UILibraryServiceProvider::register()`](src/Providers/UILibraryServiceProvider.php:55):

```php
$this->app->bind(
    ApproverResolver::class,
    config('ui-library.approvals.approver_resolver', DefaultApproverResolver::class)
);
```

The config default at [`ui-library.ph p`](src/Config/ui-library.php:441):

```php
'approvals' => [
    'approver_resolver' => DefaultApproverResolver::class,
    'approver_label_resolver' => DefaultApproverLabelResolver::class,
    'bypass_roles' => ['super_admin'],
],
```

### 1.5 Tenancy Infrastructure

The library already ships domain-agnostic tenancy infrastructure that the resolver can build on:

| Component | Location | Key Configuration |
|-----------|----------|-------------------|
| `CompanyScope` | [`src/Scopes/CompanyScope.php`](src/Scopes/CompanyScope.php:21) | `tenancy.column` (`'company_id'`), `tenancy.session_key` (`'current_company_id'`) |
| `HasCompanyScope` trait | [`src/Traits/HasCompanyScope.php`](src/Traits/HasCompanyScope.php:22) | Same tenancy config |
| `ResolveCompanyContext` middleware | [`src/Http/Middleware/ResolveCompanyContext.php`](src/Http/Middleware/ResolveCompanyContext.php) | Same tenancy config |
| Tenancy config | [`ui-library.ph p`](src/Config/ui-library.php:571) | `column` + `session_key` |

**Key precedent**: `CompanyScope` already queries models by a **configurable column name** (`config('ui-library.tenancy.column')`) filtered by a **configurable session key** (`config('ui-library.tenancy.session_key')`). This is the exact pattern a library-level `WorkspaceScopedApproverResolver` would follow.

### 1.6 User Model Resolution Precedent

The library already resolves the User model via config in [`WorkflowEngine::resolveNotifiable()`](src/Services/Workflow/WorkflowEngine.php:637):

```php
$userModel = config('ui-library.user.model', \App\Models\User::class);
$user = $userModel::find($id);
```

This establishes that the library already queries the consuming app's User model — it does so through a config key, not a hardcoded FQCN.

---

## 2. The Gap

### 2.1 Cross-Company Approval Leakage

The current `DefaultApproverResolver` resolves role names **globally**. A user holding the `payroll_officer` role in Company A is returned as an approver for a workflow scoped to Company B. This is a security leak in multi-company apps.

**Affected code paths** (every one that calls `resolve()`):

| Code Path | Method | Line |
|-----------|--------|------|
| Submit authorization | `ApprovalGuard::canSubmit()` → `canAct()` | [ApprovalGuard.php:38](src/Services/Approvals/ApprovalGuard.php:38) |
| Approve authorization | `ApprovalGuard::canApprove()` → `canAct()` | [ApprovalGuard.php:23](src/Services/Approvals/ApprovalGuard.php:23) |
| Reject authorization | `WorkflowEngine::reject()` → `canApprove()` | [WorkflowEngine.php:299](src/Services/Workflow/WorkflowEngine.php:299) |
| Step recipient resolution | `WorkflowEngine::resolveStepRecipientIds()` | [WorkflowEngine.php:532](src/Services/Workflow/WorkflowEngine.php:532) |
| All-approver resolution | `WorkflowEngine::resolveAllApproverIds()` | [WorkflowEngine.php:550](src/Services/Workflow/WorkflowEngine.php:550) |
| All-mode counting | `WorkflowEngine::approveAllMode()` → `resolve()` | [WorkflowEngine.php:230](src/Services/Workflow/WorkflowEngine.php:230) |

### 2.2 The Current "Fix" (Consuming-App Side)

The reference document [`docs/consuming-app/20-reference-workspace-scoped-approver-resolver.md`](docs/consuming-app/20-reference-workspace-scoped-approver-resolver.md) provides a complete `WorkspaceScopedApproverResolver` class that consuming apps copy into `app/Approvals/` and bind via config override. Key design decisions in the reference:

1. **`belongs ToWorkspace()`** — checks `$user->getAttribute($workspaceColumn) === $workspaceId` (simple column comparison)
2. **`activeWorkspaceId()`** — falls back to session (`active_workspace_id`) then authenticated user's column
3. **`fallbackWhenEmpty()`** — safe default: returns empty when no scoped approvers exist (no global leak)
4. **Uses `config('app.workspace_approvals.*')`** — app-level config keys, not library config

### 2.3 The Problem with "Copy This to Your App"

Every multi-company consuming app duplicates:

- The same split-and-resolve logic (already in `DefaultApproverResolver`)
- The same Spatie role querying (already in `DefaultApproverResolver`)
- The same workspace membership check (simple column comparison on the User model)
- Only the `belongs ToWorkspace()` method might differ between apps

For apps where users have a direct `company_id` column (the common case), the resolver is identical across every consuming app. Forcing each app to maintain its own copy is unnecessary duplication.

---

## 3. Three Options

### 3.1 Option A: Add `WorkspaceScopedApproverResolver` to the Library as the Default

**What changes:**
- New file: `src/Services/Approvals/WorkspaceScopedApproverResolver.php`
- Config change: `approvals.approver_resolver` default switches to `WorkspaceScopedApproverResolver::class`
- `DefaultApproverResolver` is retained (for backward compatibility or single-company apps)

**How it works:**
- Reads `config('ui-library.tenancy.column')` for the workspace column
- Reads `config('auth.providers.users.model')` for the User model
- Reads `config('permission.models.role')` for the Spatie role model
- Queries: `UserModel::where($tenancyColumn, $workspaceId)` then intersects with Spatie role users
- Falls back to session-based company ID when `$workspaceId` is null
- Returns global results when both workspaceId and session are null (backward compat)

**Pros:**
- Multi-company apps get workspace scoping immediately, no code required
- Builds on existing `tenancy.column` and `tenancy.session_key` config
- Follows the exact pattern of `CompanyScope` (already in the library)
- Single source of truth; consuming apps don't duplicate resolution logic
- The `WorkspaceResolver` contract already exists for navigation; the resolver uses the same conceptual workspace

**Cons:**
- Consuming apps where users don't have a direct `company_id` column must STILL override
- If the default changes from `DefaultApproverResolver` to `WorkspaceScopedApproverResolver`, it's technically a behavior change for existing single-company apps (though the fallback logic makes it backward-compatible)

### 3.2 Option B: Keep as Consuming-App Responsibility (Current Plan)

**What changes:** Nothing in the library. Each consuming app implements its own resolver.

**Pros:**
- Maximum flexibility — each app implements exactly what its data model requires
- No risk of the library making wrong assumptions about the User model schema
- Zero library changes needed

**Cons:**
- Every multi-company app duplicates the same Spatie + tenancy column logic
- The gap remains: `DefaultApproverResolver` silently ignores `$workspaceId`
- New consuming-app developers must discover and read the reference doc
- The library ships a resolver that is **known to be insecure** for multi-company apps
- Testing burden is on each consuming app

### 3.3 Option C: Library Provides Both, Config-Driven Choice

**What changes:**
- New file: `src/Services/Approvals/WorkspaceScopedApproverResolver.php`
- Config: `approvals.approver_resolver` defaults to `WorkspaceScopedApproverResolver::class`
- `DefaultApproverResolver` remains available for single-company apps or as a fallback

**This is effectively Option A with explicit acknowledgment that both resolvers coexist.** The config key already supports this — consuming apps that want the simple/global behavior set the config back:

```php
// config/ui-library.php (consuming app override)
'approvals' => [
    'approver_resolver' => \QuickerFaster\UILibrary\Services\Approvals\DefaultApproverResolver::class,
],
```

**Pros (same as Option A, plus):**
- Single-company apps can explicitly opt into global resolution
- The library provides the two most common patterns out of the box
- Clear documentation: "use `DefaultApproverResolver` for single-company, `WorkspaceScopedApproverResolver` for multi-company"

**Cons (same as Option A):**
- Apps with non-standard user-company relationships must still override

---

## 4. Critical Dependency Question

### 4.1 How Would the Library Resolver Query Users by Company ID?

The `WorkspaceScopedApproverResolver` needs to answer: "which users holding role X belong to workspace Y?"

**The library's approach uses three config keys, all of which already exist:**

| Concern | Config Key | Default | Precedent |
|---------|-----------|---------|-----------|
| User model class | `config('auth.providers.users.model')` | `App\Models\User` | Already used in [`WorkflowEngine::resolveNotifiable()`](src/Services/Workflow/WorkflowEngine.php:639) and `config('ui-library.user.model')` |
| Tenancy column | `config('ui-library.tenancy.column')` | `'company_id'` | Already used in [`CompanyScope`](src/Scopes/CompanyScope.php:32) |
| Tenancy session key | `config('ui-library.tenancy.session_key')` | `'current_company_id'` | Already used in [`CompanyScope`](src/Scopes/CompanyScope.php:31) |
| Role model | `config('permission.models.role')` | `Spatie\Permission\Models\Role` | Already used in [`DefaultApproverResolver`](src/Services/Approvals/DefaultApproverResolver.php:53) |

### 4.2 The Query (Pseudocode)

```php
// 1. Resolve the User model
$userModel = config('auth.providers.users.model');

// 2. Resolve the tenancy column
$tenancyColumn = config('ui-library.tenancy.column', 'company_id');

// 3. Resolve roles
$roleModel = config('permission.models.role');
$roles = $roleModel::whereIn('name', $roleNames)->get();

// 4. For each role's users, filter by tenancy column
foreach ($roles as $role) {
    foreach ($role->users as $user) {
        if ($workspaceId !== null 
            && (string) $user->getAttribute($tenancyColumn) !== (string) $workspaceId) {
            continue; // User does not belong to this workspace
        }
        $userIds[] = $user->getAuthIdentifier();
    }
}
```

This is **fully agnostic**:
- No hardcoded `company_id` string — uses the configurable `tenancy.column`
- No hardcoded User FQCN — uses Laravel's standard `auth.providers.users.model`
- No hardcoded `App\Models\*` namespace

### 4.3 The `CompanyScope` Precedent

[`CompanyScope`](src/Scopes/CompanyScope.php:29) already does exactly this pattern:

```php
$column = (string) config('ui-library.tenancy.column', 'company_id');
$companyId = Session::get(config('ui-library.tenancy.session_key'));
$builder->where("{$table}.{$column}", $companyId);
```

The library **already assumes** that models implementing `HasCompanyScope` have a column matching `tenancy.column`. The `WorkspaceScopedApproverResolver` would make the **same** assumption about the User model — and it's the same assumption the library already makes elsewhere.

### 4.4 When It Doesn't Work: The Consuming App with User → Employee → Company

A consuming app where the `users` table has no `company_id` column (company membership goes through `User → Employee → company_id`) **cannot** use the library resolver directly.

**This is expected and acceptable** because:
1. The consuming app overrides via the existing config key
2. The library resolver is a **default**, not a **requirement**
3. This is exactly the same pattern as every other library default — it works for the common case, and consuming apps override for the special case

**What the consuming app would do:**

```php
// config/ui-library.php (published)
'approvals' => [
    'approver_resolver' => \App\Approvals\CustomApproverResolver::class,
],
```

The custom resolver would traverse `User → Employee → Company` to determine membership. This is a consuming-app concern because only the consuming app knows its own data model.

---

## 5. Independence Safeguards Evaluation

### 5.1 §3.1 — "No Hardcoded References to Consuming-App Models"

> The library MUST NOT reference any consuming-app class by its FQCN.

| Check | Status |
|-------|--------|
| User model reference | ✅ Uses `config('auth.providers.users.model')` — Laravel standard, not an app-specific FQCN |
| Role model reference | ✅ Uses `config('permission.models.role')` — already in `DefaultApproverResolver` |
| Tenancy column name | ✅ Uses `config('ui-library.tenancy.column')` — library config key |
| No `use App\Models\User` | ✅ No consuming-app namespace imported |

### 5.2 §3.2 — "Config-Driven, Not Domain-Driven"

> All behavior is driven by config keys the consuming app can override.

| Check | Status |
|-------|--------|
| Column name | ✅ `config('ui-library.tenancy.column')` — overridable |
| Session key | ✅ `config('ui-library.tenancy.session_key')` — overridable |
| Role model | ✅ `config('permission.models.role')` — overridable |
| User model | ✅ `config('auth.providers.users.model')` — Laravel standard |
| Resolver class | ✅ `config('ui-library.approvals.approver_resolver')` — already the binding key |
| Config key naming | ✅ Uses existing `tenancy.*` and `approvals.*` keys — no new domain-specific keys |
| No `if ($module === 'hr')` | ✅ No branching on module/domain names |

### 5.3 §4.1 — "Grep Gate (Domain Terms)"

The resolver uses these domain nouns:
- `workspace` — ✅ Generic/structural, already in the library (`WorkspaceResolver`, workspace tabs)
- `company` — ✅ The library's chosen domain-agnostic tenant term (`CompanyScope`, `CompanyProvider`, `company_id`)
- `user` — ✅ Framework term
- `role` — ✅ Framework term (Spatie)

No domain-specific terms (`employee`, `payroll`, `invoice`, `inventory`) appear.

### 5.4 §4.4 — "Config Audit Checklist"

| Rule | Check |
|------|-------|
| Key uses generic/structural nouns | ✅ Uses existing `tenancy.*` and `approvals.*` keys |
| Default value is domain-neutral | ✅ Library class + config keys |
| Examples pass two-domain test | ✅ Works for accounting (users.company_id) and HR (override) |
| No hardcoded business module name | ✅ No module names referenced |
| Config consumed with fallback | ✅ Each `config()` call has a default |
| Consuming app can override | ✅ Via `approvals.approver_resolver` config key |

### 5.5 §6.4 — "Two-Domain Test"

> "Would this work identically for both an HR app and an inventory app?"

| App | Does the library resolver work? | Action Needed |
|-----|----------------------------------|--------------|
| **Accounting app** (users have `company_id` column) | ✅ Yes — direct column match | None — works out of the box |
| **Inventory app** (users have `company_id` column) | ✅ Yes — direct column match | None — works out of the box |
| **CRM app** (users have `company_id` column) | ✅ Yes — direct column match | None — works out of the box |
| **HR app** (users → employees → company) | ❌ No — indirect relationship | Override via config with custom resolver |

This passes the two-domain test: it works for at least two unrelated domains (accounting + inventory) out of the box, and the extension mechanism (config override) handles the HR case. The resolver itself is domain-agnostic — it speaks only in terms of "users," "roles," "workspace," and "tenancy column."

### 5.6 §5.1 — "Concern Ownership Table"

The current table assigns `ApproverResolver` as:

| Concern | Library | Consuming App |
|---------|:------:|:------------:|
| `ApproverResolver` | Contract + **default implementation** | Business-specific binding |

Adding `WorkspaceScopedApproverResolver` keeps this assignment — it's a **better** default implementation. The consuming app still owns the business-specific binding when needed.

---

## 6. Recommendation

### 6.1 Chosen Option: **Option C** — Library Provides Both, `WorkspaceScopedApproverResolver` as Default

**Rationale:**

1. **The library already has the infrastructure.** `CompanyScope` already queries by a configurable `tenancy.column`. The tenancy config already exists. The User model resolution pattern already exists (`res olveNotifiable()`). The `ApproverResolver` contract already accepts `$workspaceId`. The only missing piece is a resolver that actually uses it.

2. **The common case should work out of the box.** For the majority of multi-company apps (where users have a direct `company_id`), workspace scoping should be the default, not something each app implements from scratch.

3. **The library owns the pattern; consuming apps own the exceptions.** This is the library's established philosophy: contracts + sensible defaults, overridable via config. `WorkspaceScopedApproverResolver` is a sensible default for multi-company apps, just as `DefaultApproverResolver` is a sensible default for single-company apps.

4. **Security by default.** A library that ships a resolver known to leak approvals across workspaces is shipping insecure defaults. Adding `WorkspaceScopedApproverResolver` as the default fixes this at the library level.

5. **Minimal duplication across consuming apps.** Accounting, inventory, CRM, and any other app with `company_id` on users all benefit from the same implementation. Only apps with non-standard user-company relationships (like the HR app's `User → Employee → Company`) need custom resolvers.

### 6.2 What Changes in the Library

| Action | File | Description |
|--------|------|-------------|
| **CREATE** | `src/Services/Approvals/WorkspaceScopedApproverResolver.php` | New resolver class |
| **MODIFY** | `src/Config/ui-library.php` | Change `approvals.approver_resolver` default from `DefaultApproverResolver` to `WorkspaceScopedApproverResolver` |
| **MODIFY** | `docs/consuming-app/20-reference-workspace-scoped-approver-resolver.md` | Update from "copy this to your app" to "the library provides this; here's how to customize" |
| **KEEP** | `src/Services/Approvals/DefaultApproverResolver.php` | Retained for single-company apps or explicit opt-in |

### 6.3 What Does NOT Change

- The [`ApproverResolver`](src/Contracts/Approvals/ApproverResolver.php) contract — unchanged
- [`ApprovalGuard`](src/Services/Approvals/ApprovalGuard.php) — unchanged (already passes `$workspaceId`)
- [`WorkflowEngine`](src/Services/Workflow/WorkflowEngine.php) — unchanged (already passes `$workspaceId`)
- The binding mechanism in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php:55) — unchanged (reads from config)
- The config override mechanism — unchanged (consuming apps publish and set the key)
- `CompanyScope` / `HasCompanyScope` — unchanged

---

## 7. Conceptual Design

### 7.1 Class: `WorkspaceScopedApproverResolver`

**Namespace**: `QuickerFaster\UILibrary\Services\Approvals`

**Implements**: [`ApproverResolver`](src/Contracts/Approvals/ApproverResolver.php:5)

**Dependencies** (all via `config()`):

| Dependency | Config Key | Default |
|------------|-----------|---------|
| User model | `auth.providers.users.model` | `App\Models\User` |
| Tenancy column | `ui-library.tenancy.column` | `'company_id'` |
| Tenancy session key | `ui-library.tenancy.session_key` | `'current_company_id'` |
| Role model | `permission.models.role` | `Spatie\Permission\Models\Role` |

### 7.2 Core Logic (Pseudocode)

```
method resolve(roleIds, workspaceId):
    if roleIds is empty:
        return []

    // Fallback: if no workspaceId provided, try session
    if workspaceId is null:
        workspaceId = session(config('ui-library.tenancy.session_key'))

    userIDs = []
    roleNames = []

    // Split: integers pass through, strings are role names
    for each id in roleIds:
        if id is int or numeric-string:
            userIDs[] = (int) id
        else:
            roleNames[] = id

    if roleNames is not empty:
        roleModel = config('permission.models.role')
        roles = roleModel::whereIn('name', roleNames)->get()

        tenancyColumn = config('ui-library.tenancy.column', 'company_id')

        for each role in roles:
            for each user in role.users:
                // Scope check: when workspaceId is set, filter by tenancy column
                if workspaceId is not null:
                    if user.getAttribute(tenancyColumn) != workspaceId:
                        continue  // Skip — user not in this workspace

                // When workspaceId is null, include all users (backward compat)
                userId = user.getAuthIdentifier() ?? user.id
                if userId is not null:
                    userIDs[] = (int) userId

    return array_values(array_unique(userIDs))
```

### 7.3 Key Design Decisions

**Decision 1: When `$workspaceId` is `null`, try session fallback.**

`CompanyScope` reads `Session::get('current_company_id')`. The resolver does the same — if the engine doesn't pass a workspaceId (e.g., legacy workflow without context), the resolver falls back to the session's current company. This aligns with how the rest of the library resolves tenancy.

**Decision 2: When both `$workspaceId` and session are `null`, return global results.**

This preserves backward compatibility. Single-company apps that never set a workspace context get the same behavior as `DefaultApproverResolver`. This also handles queue jobs where no HTTP session exists.

**Decision 3: When `$workspaceId` IS provided but no users match, return empty.**

This is the secure default. If the engine explicitly scoped the request to workspace X and no approvers exist there, the resolver does NOT fall back to global resolution. This prevents cross-workspace leakage.

**Decision 4: Do NOT include a `fallbackWhenEmpty()` that delegates to `DefaultApproverResolver`.**

The reference implementation has an optional fallback-to-global gated by `config('app.workspace_approvals.fallback_to_global')`. The library resolver should NOT include this — it's an app-level policy decision. A consuming app that wants this behavior can override the resolver.

**Decision 5: Do NOT include `belongs ToWorkspace()` as a separate overridable method.**

The reference implementation uses a protected method for membership checking, suggesting consuming apps "just override this method." That pattern is fragile — changing a protected method in a library class is not a supported extension point. Instead, consuming apps that need different membership logic should **replace the entire resolver** via config. This is the library's established pattern.

### 7.4 Config Change

In [`src/Config/ui-library.php`](src/Config/ui-library.php:440):

```php
// BEFORE
'approvals' => [
    'approver_resolver' => \QuickerFaster\UILibrary\Services\Approvals\DefaultApproverResolver::class,
    // ...
],

// AFTER
'approvals' => [
    'approver_resolver' => \QuickerFaster\UILibrary\Services\Approvals\WorkspaceScopedApproverResolver::class,
    // ...
],
```

### 7.5 Migration Path for Existing Apps

**Single-company apps** (no workspace concept):
- No action needed. When `$workspaceId` is always `null` and the session has no company, the resolver returns global results — identical to `DefaultApproverResolver` behavior.

**Multi-company apps with `company_id` on users**:
- No action needed. The resolver automatically scopes by `tenancy.column`. These apps were previously vulnerable to cross-company approval leakage; upgrading the library fixes this automatically.

**Multi-company apps WITHOUT `company_id` on users** (e.g., HR app with `User → Employee → Company`):
- The resolver would return **empty** for scoped queries (safe — prevents leakage).
- The app must provide a custom resolver and bind it via config override:
  ```php
  // config/ui-library.php (published)
  'approvals' => [
      'approver_resolver' => \App\Approvals\CustomApproverResolver::class,
  ],
  ```

**Apps that want to keep global resolution explicitly:**
- Publish config and set back to `DefaultApproverResolver::class`.

---

## 8. Impact on Payroll Approval Plan

The [`plans/payroll-approval-implementation-plan.md`](plans/payroll-approval-implementation-plan.md) §3a currently describes the `ApproverResolver` binding as a **consuming-app responsibility** — the app must copy the reference implementation and adapt it.

If the library ships `WorkspaceScopedApproverResolver`, §3a changes significantly:

### 8.1 Before (Current Plan)

> **Status**: ❌ NOT DONE — relies on library's `DefaultApproverResolver` (global Spatie resolution, no workspace scoping)
>
> **The Solution**: Copy the reference implementation, adapt `belongs ToWorkspace()`, bind it.

### 8.2 After (With Library Resolver)

> **Status**: ✅ RESOLVED BY LIBRARY — `WorkspaceScopedApproverResolver` is now the default
>
> **What the library provides**: Automatic workspace scoping via `tenancy.column` config. When `PayrollRun::getWorkflowContext()` returns `'workspace_id' => $this->company_id`, the resolver automatically filters approvers by that workspace.
>
> **What the consuming app must verify**:
> 1. The User model has a `company_id` column matching `config('ui-library.tenancy.column')`. **If not** (e.g., HR app where company membership goes through `User → Employee → company_id`), the app must provide a custom resolver.
> 2. The `tenancy.column` config matches the actual column name on the User model.
> 3. The `tenancy.session_key` config matches the session key used by the company switcher.

### 8.3 Updated Priority Order

The payroll plan's Priority 1 ("ApproverResolver Binding") changes from a **must-implement** to a **must-verify**:

| Priority | Before | After |
|----------|--------|-------|
| P1: ApproverResolver | 🔴 Implement custom resolver | �� **Verify** library resolver works with app's User model. Only implement custom if needed. |
| P2-P6 | Unchanged | Unchanged |

### 8.4 HR App-Specific Note

The HR consuming app's `users` table has no `company_id` column. Company membership is through `User → Employee (by user_id) → company_id`. Therefore:

- The library's `WorkspaceScopedApproverResolver` will **not** work for the HR app (it will return empty for scoped queries — safe, but non-functional)
- The HR app **must still implement a custom resolver**
- The custom resolver queries: `User → Employee::where('user_id', users.id)->where('company_id', $workspaceId)` to determine workspace membership
- This is the correct separation: the library provides the common pattern, and the HR app's unique data model requires a custom implementation

The payroll approval plan should be updated to reflect this nuance — the resolver gap is now a **library-provided default** with a **documented override path** for apps with non-standard user-company relationships.

---

## 9. Summary

| Question | Answer |
|----------|--------|
| Should the library ship a `WorkspaceScopedApproverResolver`? | **Yes.** |
| Should it be the default? | **Yes.** The fallback behavior (null workspaceId → global resolution) ensures backward compatibility. |
| Does it violate independence safeguards? | **No.** It uses configurable keys (`tenancy.column`, `auth.providers.users.model`, `permission.models.role`) — no hardcoded FQCNs or domain terms. |
| What about apps where users don't have `company_id`? | They override via `config('ui-library.approvals.approver_resolver')` — the same extension point that already exists. |
| Is this consistent with existing library patterns? | **Yes.** It mirrors `CompanyScope` (configurable tenancy column + session key) and `resolveNotifiable()` (config-driven User model resolution). |
| What's the migration impact? | **Near-zero** for most apps. Multi-company apps with `company_id` on users get security automatically. Single-company apps see identical behavior. Apps with non-standard models override as they already would. |
| What changes in the payroll plan? | Priority 1 shifts from "implement custom resolver" to "verify library resolver works." The HR app still needs a custom resolver due to its `User → Employee → Company` relationship. |

### Files to Create/Modify

| # | Action | File |
|---|--------|------|
| 1 | **CREATE** | `src/Services/Approvals/WorkspaceScopedApproverResolver.php` |
| 2 | **MODIFY** | `src/Config/ui-library.php` §440 — change default `approver_resolver` |
| 3 | **MODIFY** | `docs/consuming-app/20-reference-workspace-scoped-approver-resolver.md` — reframe as "library provides this" |
| 4 | **MODIFY** | `plans/payroll-approval-implementation-plan.md` §3a — update from "must implement" to "must verify" |

---

*This is a design document. No implementation code has been written. The decision to proceed should be made by library maintainers after reviewing this analysis.*