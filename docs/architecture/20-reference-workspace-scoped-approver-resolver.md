# QuickerFaster UI Library — Workspace-Scoped Approver Resolver: Reference Guide

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Status**: Reference (2026-08-16) — consuming-app guide for scoping workflow approvers to a workspace
> **Scope**: How the [`ApproverResolver`](../../src/Contracts/Approvals/ApproverResolver.php) contract works, what the default implementation does (and does not do), and a complete reference implementation that resolves approvers within a single workspace.

**Related files**: [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`18-workflow-approval-testing-checklist.md`](./18-workflow-approval-testing-checklist.md) · [`19-notification-consuming-app-guide.md`](./19-notification-consuming-app-guide.md)

---

## 1. Overview

The workflow engine decides **who may approve a step** by delegating role/user resolution to the [`ApproverResolver`](../../src/Contracts/Approvals/ApproverResolver.php) contract. Every workflow step stores a flat list of **assignee identifiers** — integers (already-resolved user IDs) and strings (role names) — and hands that list to the resolver to produce a flat `int[]` of concrete user IDs.

By default the library resolves role names **globally**: a user holding the `manager` role is an approver regardless of which workspace/company/organization owns the record. That is correct for single-tenant apps but is a **security leak** in multi-tenant apps, where a manager in workspace A must not approve a record belonging to workspace B.

A **workspace-scoped resolver** constrains that resolution to one workspace, so approval authority is derived from *role **and** membership*. You implement this when:

1. Records are owned by a workspace (company, organization, team, tenant).
2. The same role name (`manager`, `reviewer`, `authorizer`) exists in more than one workspace.
3. Approvers must only act on records within their own workspace.

The resolver does **not** read the record directly. The engine extracts the workspace identifier from the workflow context and passes it to the resolver as the `$workspaceId` argument. Your resolver's only job is to honor that scope while expanding roles into user IDs.

---

## 2. The Contract

**Location**: [`src/Contracts/Approvals/ApproverResolver.php`](../../src/Contracts/Approvals/ApproverResolver.php:5)

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

| Part | Type | Meaning |
|------|------|---------|
| `$roleIds` | `array<int\|string>` | A mixed list where integers are already-resolved user IDs (pass through) and strings are role names to expand. |
| `$workspaceId` | `?string` | Optional workspace scope. `null` means "no scope supplied" — the resolver may fall back to the active workspace or global behavior. |
| Return | `int[]` | A flat, deduplicated list of user IDs who may act. |

### 2.1 How `$workspaceId` reaches the resolver

The resolver is deliberately **record-agnostic** — it never receives the workflowable entity or the step definition. Instead, the workspace identifier is carried through the workflow context:

1. The workflowable entity exposes `workspace_id` via [`Workflowable::getWorkflowContext()`](../../src/Contracts/Workflow/Workflowable.php:23), and/or the caller passes it in `$context` when starting the workflow.
2. [`WorkflowEngine::start()`](../../src/Services/Workflow/WorkflowEngine.php:34) merges the entity context with `$context` and persists the result on the [`Workflow`](../../src/Models/Workflow.php) record's `context` column.
3. [`WorkflowEngine::resolveWorkspaceId()`](../../src/Services/Workflow/WorkflowEngine.php:518) reads `context.workspace_id` and returns it as a `?string`.
4. [`ApprovalGuard::canApprove()`](../../src/Services/Approvals/ApprovalGuard.php:23) / [`canSubmit()`](../../src/Services/Approvals/ApprovalGuard.php:36) pass that value straight into [`ApproverResolver::resolve()`](../../src/Contracts/Approvals/ApproverResolver.php:20).

```php
// A workflowable entity must advertise its workspace in its context.
namespace App\Models;

use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;

class Invoice implements Workflowable
{
    public function getWorkflowableId(): int|string
    {
        return $this->getKey();
    }

    public function getWorkflowDefinitionKey(): string
    {
        return 'invoice_approval';
    }

    public function getWorkflowContext(): array
    {
        return [
            'workspace_id' => $this->workspace_id,
        ];
    }
}
```

You may also pass the workspace explicitly at start time:

```php
$engine->start($invoice, [
    'workspace_id' => $invoice->workspace_id,
]);
```

---

## 3. Default Behavior

**Location**: [`src/Services/Approvals/DefaultApproverResolver.php`](../../src/Services/Approvals/DefaultApproverResolver.php:18)

The shipped [`DefaultApproverResolver`](../../src/Services/Approvals/DefaultApproverResolver.php) is a Spatie-permission-based implementation:

1. Splits `$roleIds` into integers (passed through) and strings (role names).
2. Queries the configured role model (`config('permission.models.role')`, defaulting to `Spatie\Permission\Models\Role`) for every named role.
3. Collects the user IDs of **every** user holding those roles, with no workspace filter.
4. Returns `array_values(array_unique($userIds))`.

The resolver **accepts `$workspaceId` for API compatibility but ignores it**. The library has no workspace/company model of its own, so it cannot scope resolution generically.

### 3.1 Limitations for multi-tenant apps

- ❌ A `manager` in workspace A and a `manager` in workspace B are both returned for a step on a workspace-A record.
- ❌ No membership check — the resolver cannot tell whether a user belongs to the record's workspace.
- ❌ Silent cross-workspace leakage in every code path that calls `resolve()`: submit authorization, approval authorization, `all`-mode counting, and recipient resolution.

If approvers must be scoped per workspace, **replace** the default binding with a custom implementation (see §5).

---

## 4. Reference Implementation

The following class is a complete, copy-pasteable starting point. It is **domain-agnostic** — it speaks in terms of *workspace*, *record*, *entity*, and *organization*, and makes no reference to any specific business domain.

Place it at `app/Approvals/WorkspaceScopedApproverResolver.php` (adjust the namespace to your app).

```php
<?php

namespace App\Approvals;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;
use QuickerFaster\UILibrary\Services\Approvals\DefaultApproverResolver;

/**
 * Resolves approvers within a single workspace scope.
 *
 * The library's DefaultApproverResolver resolves role names globally. This
 * resolver constrains the same resolution to one workspace so approvers from
 * one organization cannot act on another organization's records.
 */
class WorkspaceScopedApproverResolver implements ApproverResolver
{
    /**
     * Resolve a mixed list of user IDs and role names into a flat list of
     * user IDs scoped to a single workspace.
     *
     * Convention:
     *   - int    → already-resolved user ID (passed through as-is).
     *   - string → role name; resolve every user holding that role within
     *              the active workspace.
     *
     * @param array<int|string> $roleIds Mixed user IDs (int) and role names (string).
     * @param string|null $workspaceId Workspace scope; falls back to the
     *                                 active workspace when null.
     * @return int[] Flat list of resolved user IDs.
     */
    public function resolve(array $roleIds, ?string $workspaceId = null): array
    {
        if ($roleIds === []) {
            return [];
        }

        $workspaceId = $workspaceId ?? $this->activeWorkspaceId();

        $userIds = [];
        $roleNames = [];

        foreach ($roleIds as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                // Integer (or numeric string) → already-resolved user ID.
                $userIds[] = (int) $id;
            } else {
                // String → role name to resolve within the workspace.
                $roleNames[] = $id;
            }
        }

        if ($roleNames !== []) {
            $userIds = array_merge($userIds, $this->resolveRoleNames($roleNames, $workspaceId));
        }

        $userIds = array_values(array_unique($userIds));

        return $this->fallbackWhenEmpty($userIds, $roleIds);
    }

    /**
     * Resolve role names to the IDs of users holding those roles within the
     * given workspace.
     *
     * @param string[] $roleNames
     * @return int[]
     */
    protected function resolveRoleNames(array $roleNames, ?string $workspaceId): array
    {
        $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        $roles = $roleModel::query()
            ->whereIn('name', $roleNames)
            ->get();

        $userIds = [];

        foreach ($roles as $role) {
            foreach ($role->users as $user) {
                if ($workspaceId !== null && ! $this->belongsToWorkspace($user, $workspaceId)) {
                    continue;
                }

                $userId = method_exists($user, 'getAuthIdentifier')
                    ? $user->getAuthIdentifier()
                    : ($user->id ?? null);

                if ($userId !== null) {
                    $userIds[] = (int) $userId;
                }
            }
        }

        return $userIds;
    }

    /**
     * Decide whether to fall back to global resolution when the workspace
     * scope produced no approvers.
     *
     * Safe default: return the empty set — no workspace-scoped approvers
     * means no one may approve, and the workflow stays pending rather than
     * leaking authority across workspaces. Enable the fallback only when the
     * product rules explicitly allow global approvers to act.
     *
     * @param int[] $userIds
     * @param array<int|string> $roleIds
     * @return int[]
     */
    protected function fallbackWhenEmpty(array $userIds, array $roleIds): array
    {
        if ($userIds !== [] || ! config('app.workspace_approvals.fallback_to_global', false)) {
            return $userIds;
        }

        return app(DefaultApproverResolver::class)->resolve($roleIds, null);
    }

    /**
     * Determine whether the given user belongs to the workspace.
     *
     * Replace with the consuming app's own membership check (pivot table,
     * organization relation, membership column, etc.).
     */
    protected function belongsToWorkspace(mixed $user, string $workspaceId): bool
    {
        $workspaceColumn = config('app.workspace_approvals.foreign_key', 'workspace_id');

        return method_exists($user, 'getAttribute')
            && (string) $user->getAttribute($workspaceColumn) === (string) $workspaceId;
    }

    /**
     * Resolve the active workspace ID from the session, then from the
     * authenticated user's own workspace association.
     */
    protected function activeWorkspaceId(): ?string
    {
        $fromSession = Session::get(
            config('app.workspace_approvals.session_key', 'active_workspace_id')
        );

        if ($fromSession !== null) {
            return (string) $fromSession;
        }

        $user = Auth::user();
        $workspaceColumn = config('app.workspace_approvals.foreign_key', 'workspace_id');

        if ($user && method_exists($user, 'getAttribute')) {
            $value = $user->getAttribute($workspaceColumn);

            return $value !== null ? (string) $value : null;
        }

        return null;
    }
}
```

### 4.1 Customization points

| Concern | Where to change |
|---------|-----------------|
| **Workspace membership check** | `belongsToWorkspace()` — swap the column check for a pivot/relation lookup. |
| **Where the workspace ID comes from** | `activeWorkspaceId()` — session key, authenticated user, or a tenant resolver service. |
| **Fallback policy** | `fallbackWhenEmpty()` — return empty (safe) or delegate to [`DefaultApproverResolver`](../../src/Services/Approvals/DefaultApproverResolver.php) when allowed. |
| **Role model** | `config('permission.models.role')` — honored automatically because the query reads the same key as the default resolver. |
| **Non-Spatie role source** | Replace the `$roleModel` query with your own role/membership repository. |

> **Security note**: Resolving the record's `workspace_id` inside the resolver would require the entity, which the contract does not provide. Do not try to reach into the record here — rely on `$workspaceId` (or your session/authenticated-user fallback) instead.

---

## 5. Binding

The contract is bound in [`UILibraryServiceProvider::register()`](../../src/Providers/UILibraryServiceProvider.php:51), reading `config('ui-library.approvals.approver_resolver')` and defaulting to [`DefaultApproverResolver`](../../src/Services/Approvals/DefaultApproverResolver.php):

```php
$this->app->bind(
    \QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver::class,
    config(
        'ui-library.approvals.approver_resolver',
        \QuickerFaster\UILibrary\Services\Approvals\DefaultApproverResolver::class
    )
);
```

There are two supported ways to replace the default. **Pick one.**

### 5.1 Config override (recommended)

Publish the library config, then set the key to your class:

```bash
php artisan vendor:publish --tag=ui-library-config
```

```php
// config/ui-library.php
'approvals' => [
    'approver_resolver' => \App\Approvals\WorkspaceScopedApproverResolver::class,
    // ...
],
```

Because the service provider resolves the class name from config, no custom provider is required.

### 5.2 Direct re-binding in a service provider

Register a provider that runs **after** the library provider and re-binds the contract:

```php
namespace App\Providers;

use App\Approvals\WorkspaceScopedApproverResolver;
use Illuminate\Support\ServiceProvider;
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;

class ApproverResolutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApproverResolver::class, WorkspaceScopedApproverResolver::class);
    }
}
```

Then register it in `config/app.php`:

```php
'providers' => [
    // ...
    QuickerFaster\UILibrary\Providers\UILibraryServiceProvider::class,
    App\Providers\ApproverResolutionServiceProvider::class,
],
```

> Use **config override (§5.1)** when you simply swap implementations; use **re-binding (§5.2)** when the class has constructor dependencies you need the container to resolve.

---

## 6. Testing

Test the resolver in isolation — it has no HTTP or engine dependency. Assert the role-expansion and workspace-filtering behavior directly.

### 6.1 Setup

Use Laravel's testing helpers with a real (or in-memory) database, and [`RefreshDatabase`](https://laravel.com/docs/database-testing) if you are using models. Create users, roles, and workspace membership in `setUp()`:

```php
namespace Tests\Unit\Approvals;

use App\Approvals\WorkspaceScopedApproverResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkspaceScopedApproverResolverTest extends TestCase
{
    use RefreshDatabase;

    protected WorkspaceScopedApproverResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new WorkspaceScopedApproverResolver();
    }

    private function makeApprover(int $workspaceId, string $role): User
    {
        $user = User::factory()->create(['workspace_id' => $workspaceId]);
        $user->assignRole(Role::findOrCreate($role));

        return $user;
    }
}
```

### 6.2 What to assert

```php
public function test_resolves_only_approvers_in_the_given_workspace(): void
{
    $inScope = $this->makeApprover(workspaceId: 1, role: 'manager');
    $outOfScope = $this->makeApprover(workspaceId: 2, role: 'manager');

    $ids = $this->resolver->resolve(['manager'], (string) 1);

    $this->assertContains($inScope->id, $ids);
    $this->assertNotContains($outOfScope->id, $ids);
}

public function test_passes_integer_user_ids_through_unchanged(): void
{
    $ids = $this->resolver->resolve([42, 'manager'], (string) 1);

    $this->assertContains(42, $ids);
}

public function test_deduplicates_and_returns_empty_for_unknown_role(): void
{
    $this->assertSame([], $this->resolver->resolve(['nonexistent_role'], (string) 1));
    $this->assertSame([42], $this->resolver->resolve([42, '42'], null));
}

public function test_falls_back_to_active_workspace_when_workspace_id_is_null(): void
{
    $inScope = $this->makeApprover(workspaceId: 7, role: 'reviewer');
    $outOfScope = $this->makeApprover(workspaceId: 9, role: 'reviewer');

    session(['active_workspace_id' => 7]);

    $ids = $this->resolver->resolve(['reviewer']);

    $this->assertContains($inScope->id, $ids);
    $this->assertNotContains($outOfScope->id, $ids);
}

public function test_graceful_fallback_returns_empty_when_no_scoped_approvers_exist(): void
{
    // No 'authorizer' user exists in workspace 5 — safe default is empty,
    // never a global leak to other workspaces.
    $this->assertSame([], $this->resolver->resolve(['authorizer'], (string) 5));
}
```

### 6.3 Integration check through the engine

Verify the wiring end-to-end by binding the resolver (see §5) and asserting that an out-of-workspace approver cannot approve a record:

```php
use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;

public function test_out_of_workspace_approver_is_rejected(): void
{
    $invoice = Invoice::factory()->create(['workspace_id' => 1]);
    $outOfScope = $this->makeApprover(workspaceId: 2, role: 'manager');

    $workflow = app(WorkflowEngine::class)->start($invoice);

    $this->actingAs($outOfScope);

    $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

    app(WorkflowEngine::class)->approve($workflow);
}
```

> Keep the unit tests (§6.2) as the source of truth for resolution rules, and reserve the engine-level test (§6.3) for confirming the binding and `$workspaceId` plumbing are wired correctly.
