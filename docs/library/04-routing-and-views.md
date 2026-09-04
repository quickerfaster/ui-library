# QuickerFaster UI Library — Routing & Views

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](../README.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`13-adr.md`](./13-adr.md) · [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)

---

## 4. Routing & View Resolution

### 4.1 Route Loading Order

The route loading order is critical: explicit routes must take precedence over the catch-all pattern. The registration sequence is:

1. **Library routes** — [`src/Routes/web.php`](../../src/Routes/web.php) (export, import, print, socialite, setup, `company.switch`; loaded by [`UILibraryServiceProvider::boot()`](../../src/Providers/UILibraryServiceProvider.php))
2. **Core module routes** — `src/Core/*/Routes/web.php` (Admin, System, Organization; loaded by [`UILibraryServiceProvider::bootCoreModules()`](../../src/Providers/UILibraryServiceProvider.php))
3. **Non-system business module web routes** — `app/Modules/*/Routes/web.php` (loaded by [`ModuleServiceProvider::discoverBusinessModules()`](../../src/Providers/ModuleServiceProvider.php))
4. **Non-system business module API routes** — `app/Modules/*/Routes/api.php` (loaded with `api` prefix + middleware)
5. **System catch-all route** — [`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php) (loaded LAST at the end of `discoverBusinessModules()`)

This ensures explicit module routes take precedence over the catch-all pattern.

> **Blueprint §6.4 vs current code**: The original blueprint's 5-step order placed the catch-all at `app/Modules/System/Routes/web.php`. In the current implementation the catch-all lives in `src/Core/System/Routes/web.php` (Core), and it is explicitly loaded LAST after all business modules.

### 4.2 View Namespace Registration

Three namespace families exist:

| Source | Namespace | Example |
|--------|-----------|---------|
| Library views (`src/Resources/views/`) | `qf` | `view('qf::layouts.app')` |
| Core module views (`src/Core/*/Resources/views/`) | `qf-core` | `view('qf-core::system.dashboard')` |
| Business module views (`app/Modules/*/Resources/views/`) | lowercase module name | `view('hr::dashboard')` |

- **Library** views are registered by [`UILibraryServiceProvider::registerViews()`](../../src/Providers/UILibraryServiceProvider.php) under `qf`.
- **Core** modules (Admin, System, Organization) share a single **`qf-core`** namespace, registered in [`UILibraryServiceProvider::bootCoreModules()`](../../src/Providers/UILibraryServiceProvider.php). The first Core module registers the namespace; subsequent modules add paths via `addNamespace()`. So `qf-core::admin.dashboard` resolves to `src/Core/Admin/Resources/views/admin/dashboard.blade.php`.
- **Business** modules register their own lowercase alias in [`ModuleServiceProvider::discoverBusinessModules()`](../../src/Providers/ModuleServiceProvider.php).

### 4.3 Catch-All Route Pattern

The System module at [`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php) contains the catch-all:

```php
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/{module}/{view}/{id?}', function ($module, $view, $id = null) {
        // 1. Module allow-list (config-driven)
        $allowedModules = config('ui-library.catch_all.allowed_modules', []);
        if (!in_array($module, $allowedModules, true)) {
            abort(404);
        }

        // 2. Directory-traversal sanitization (defense in depth)
        foreach ([$module, $view] as $segment) {
            if (
                str_contains($segment, "\0")
                || str_contains($segment, '\\')
                || str_contains($segment, '/')
                || str_contains($segment, '..')
                || str_starts_with($segment, '.')
            ) {
                abort(400);
            }
        }

        // 3. Per-view authorization (config-driven)
        $user = auth()->user();

        if (config('ui-library.catch_all.require_auth', true) && !$user) {
            abort(401);
        }

        $authCallback = config('ui-library.catch_all.authorization_callback');
        if ($authCallback && is_callable($authCallback)) {
            if (!$authCallback($user, $module, $view, $id)) {
                abort(403);
            }
        } else {
            $gate = config('ui-library.catch_all.gate');
            if ($gate && !Gate::allows($gate, [$module, $view, $id])) {
                abort(403);
            }
        }

        // 4. View resolution
        $viewName = "{$module}::{$view}";
        if (view()->exists($viewName)) {
            return view($viewName, ['id' => $id]);
        }

        $coreViewName = "qf-core::{$module}.{$view}";
        if (view()->exists($coreViewName)) {
            return view($coreViewName, ['id' => $id]);
        }

        $underscoreView = str_replace('-', '_', $view);
        $coreViewNameUnderscore = "qf-core::{$module}.{$underscoreView}";
        if (view()->exists($coreViewNameUnderscore)) {
            return view($coreViewNameUnderscore, ['id' => $id]);
        }

        abort(404, "View [{$viewName}] not found.");
    })
    ->where('module', '[a-z-]+')
    ->where('view', '[a-z-]+')
    ->where('id', '[0-9]+')
    ->middleware(config('ui-library.catch_all.rate_limiting.enabled', true) ? 'throttle:qf-catch-all' : []);
});
```

This is loaded LAST so explicit module routes take precedence. The `qf-catch-all` named rate limiter is registered in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php).

#### Two-Tier View Resolution (with underscore fallback)

The catch-all resolves a view through the following fallback chain:

1. **Business namespace** — `{module}::{view}` → `app/Modules/{Module}/Resources/views/{view}.blade.php`
2. **Core namespace** — `qf-core::{module}.{view}` → `src/Core/{Module}/Resources/views/{module}/{view}.blade.php`
3. **Core namespace (underscore)** — `qf-core::{module}.{view-with-underscores}` — if the view path uses underscores instead of hyphens (e.g., `business-units` → `business_units`)

If none resolve, the route aborts with `404`.

Route parameter constraints: `module` and `view` accept `[a-z-]+`; `id` accepts `[0-9]+`.

#### Security Gates (config-driven)

The catch-all now applies four security gates before view resolution, all configured under `ui-library.catch_all`:

1. **Module allow-list** (`allowed_modules`, default `['admin', 'system', 'organization', 'common']`) — non-listed modules `abort(404)`; business modules are appended automatically by [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php).
2. **Directory-traversal sanitization** — null byte, slash, backslash, `..`, and leading-dot checks `abort(400)`.
3. **Per-view authorization** — `authorization_callback` (callable, takes precedence) or `gate` (Gate ability); `require_auth` re-checked in the handler.
4. **Rate limiting** — `throttle:qf-catch-all` middleware when `rate_limiting.enabled` is `true`.

#### Component Resolution Map (catch-all row)

From the blueprint §2.3:

```
Catch-all route (System module)    →  app/Modules/{Module}/Resources/views/**
                                       /{module}/{view}/{id?} → view({module}::{view})
```

> **⚠️ Important**: Views rendered by the catch-all route are standard Laravel views — they do NOT have Livewire state. Variables like `$activeTab`, `$formData`, or any component property will be `undefined`. Always use a thin wrapper blade with `@livewire('component-name')` when you need Livewire interactivity. See [Pre-Coding Checklist](../consuming-app/pre-coding-checklist.md) §A.

### 4.4 Rationale & Trade-offs (ADR-001)

**Why** (see [`13-adr.md`](./13-adr.md) — ADR-001):
- Eliminates repetitive route boilerplate for CRUD-like screen modules
- Matches the convention that views are stored under module resource folders
- New modules require zero route configuration for basic view rendering

**Trade-offs**:
- Requires route validation and authorization checks in the catch-all handler
- Risk of accidental view exposure if authorization is weak
- Needs a clear module allow-list and view existence checks

### 4.5 Security Hardening — ✅ Implemented (2026-08-16)

The centralized route pattern `/{module}/{view}/{id?}` can be abused if authorization and validation are weak. All recommendations from [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md) §10.7 are now implemented (see §4.3):

- ✅ Strict module allow-lists in the catch-all handler (`ui-library.catch_all.allowed_modules`)
- ✅ Explicit per-view authorization via `authorization_callback` or `gate`
- ✅ Directory-traversal sanitization of `$module` and `$view`
- ✅ Rate limiting via the `qf-catch-all` named limiter

The config schema for `ui-library.catch_all` is documented in [`10-settings-and-config.md`](./10-settings-and-config.md).

---

**Related files**: [`00-index.md`](../README.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`13-adr.md`](./13-adr.md) · [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)
