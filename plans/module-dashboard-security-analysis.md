# Module Dashboard Security Analysis

> **Status**: ✅ IMPLEMENTED
> **Analysis Date**: 2026-09-03
> **Implementation Date**: 2026-09-03

## Executive Summary

**Severity: High** — Module dashboards (e.g., `/hr/dashboard`, `/payroll/dashboard`) are accessible to **any authenticated user** regardless of their role. An `employee`-role user can directly navigate to `/payroll/dashboard` or `/hr/dashboard-people-overview` and view admin-level dashboards. Additionally, **18 HR CRUD routes have NO middleware at all** — not even `auth` — making them accessible to unauthenticated users.

---

## 1. Current State: Route Protection Matrix

### 1.1 Global Middleware (`bootstrap/app.php`)

```php
// File: bootstrap/app.php, line 14-17
$middleware->web(append: [
    \App\Modules\Hr\Http\Middleware\RedirectEssUsersFromAdminViews::class,
]);
```

Only `RedirectEssUsersFromAdminViews` is appended globally. **There is no global `auth` middleware.** Each module route file is responsible for its own `auth` middleware.

### 1.2 Module Route Registration

In [`ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php:126), modules are loaded with only `web` middleware:

```php
\Route::middleware('web')->group($webRoutePath);
```

The `auth` middleware must be applied within each module's route file.

### 1.3 Per-Module Middleware Status

| Module | Route File | Middleware | Status |
|--------|-----------|------------|--------|
| **HR** | [`app/Modules/Hr/Routes/web.php`](app/Modules/Hr/Routes/web.php:7) | `web, auth` (lines 7-105) | ✅ Protected |
| **HR** | [`app/Modules/Hr/Routes/web.php`](app/Modules/Hr/Routes/web.php:107) | `web` + `auth` (lines 107-125) | ✅ Protected |
| **HR** | [`app/Modules/Hr/Routes/web.php`](app/Modules/Hr/Routes/web.php:130) | **NONE** (lines 130-294) | 🔴 **UNPROTECTED** |
| **Payroll** | [`app/Modules/Payroll/Routes/web.php`](app/Modules/Payroll/Routes/web.php:12) | `web` + `auth` (lines 12-151) | ✅ Protected |
| **Payroll** | [`app/Modules/Payroll/Routes/web.php`](app/Modules/Payroll/Routes/web.php:153) | `web, auth` (lines 153+) | ✅ Protected |
| **Attendance** | [`app/Modules/Attendance/Routes/web.php`](app/Modules/Attendance/Routes/web.php:6) | `web` only (lines 6-17) | 🔴 **UNPROTECTED** (clock API) |
| **Attendance** | [`app/Modules/Attendance/Routes/web.php`](app/Modules/Attendance/Routes/web.php:19) | `web, auth` (lines 19+) | ✅ Protected |
| **Leave** | [`app/Modules/Leave/Routes/web.php`](app/Modules/Leave/Routes/web.php:5) | `web, auth` (all routes) | ✅ Protected |
| **Holiday** | [`app/Modules/Holiday/Routes/web.php`](app/Modules/Holiday/Routes/web.php:5) | `web, auth` (all routes) | ✅ Protected |
| **Organization** | [`app/Modules/Organization/Routes/web.php`](app/Modules/Organization/Routes/web.php:5) | `web, auth` (all routes) | ✅ Protected |
| **Library** | [`src/Routes/web.php`](src/Routes/web.php:35) | `web` only (most routes) | ⚠️ Partial |
| **Library** | [`src/Routes/web.php`](src/Routes/web.php:46) | `/home` — **NO auth** | 🔴 **UNPROTECTED** |

---

## 2. Dashboard Inventory — All 26 Dashboards

### 2.1 HR Module (6 dashboards)

| # | URL | Route Name | Auth? | Role-Gated? |
|---|-----|-----------|-------|-------------|
| 1 | `/hr/dashboard` | `hr.dashboard` | ✅ `auth` | ❌ |
| 2 | `/hr/my-portal` | `hr.dashboard-my-portal-overview` | ✅ `auth` | ❌ |
| 3 | `/hr/dashboard-organization-overview` | `hr.dashboard-organization-overview` | ✅ `auth` | ❌ |
| 4 | `/hr/dashboard-people-overview` | `hr.dashboard-people-overview` | ✅ `auth` | ❌ |
| 5 | `/hr/dashboard-manage-overview` | `hr.dashboard-manage-overview` | ✅ `auth` | ❌ |
| 6 | `/hr/team-calendar` | `hr.team-calendar` | ✅ `auth` | ❌ |

### 2.2 Payroll Module (3 dashboards)

| # | URL | Route Name | Auth? | Role-Gated? |
|---|-----|-----------|-------|-------------|
| 7 | `/payroll/dashboard` | `payroll.dashboard` | ✅ `auth` | ❌ |
| 8 | `/payroll/dashboard-processing-overview` | `payroll.dashboard-processing-overview` | ✅ `auth` | ❌ |
| 9 | `/payroll/dashboard-configuration-overview` | `payroll.dashboard-configuration-overview` | ✅ `auth` | ❌ |

### 2.3 Attendance Module (4 dashboards)

| # | URL | Route Name | Auth? | Role-Gated? |
|---|-----|-----------|-------|-------------|
| 10 | `/attendance/dashboard` | `attendance.dashboard` | ✅ `auth` | ❌ |
| 11 | `/attendance/dashboard-time-overview` | `attendance.dashboard-time-overview` | ✅ `auth` | ❌ |
| 12 | `/attendance/dashboard-policies-overview` | `attendance.dashboard-policies-overview` | ✅ `auth` | ❌ |
| 13 | `/attendance/dashboard-scheduling-overview` | `attendance.dashboard-scheduling-overview` | ✅ `auth` | ❌ |

### 2.4 Leave Module (4 dashboards)

| # | URL | Route Name | Auth? | Role-Gated? |
|---|-----|-----------|-------|-------------|
| 14 | `/leave/dashboard` | `leave.dashboard` | ✅ `auth` | ❌ |
| 15 | `/leave/dashboard-leave-overview` | `leave.dashboard-leave-overview` | ✅ `auth` | ❌ |
| 16 | `/leave/dashboard-requests-overview` | `leave.dashboard-requests-overview` | ✅ `auth` | ❌ |
| 17 | `/leave/dashboard-configuration-overview` | `leave.dashboard-configuration-overview` | ✅ `auth` | ❌ |

### 2.5 Holiday Module (2 dashboards)

| # | URL | Route Name | Auth? | Role-Gated? |
|---|-----|-----------|-------|-------------|
| 18 | `/holiday/dashboard` | `holiday.dashboard` | ✅ `auth` | ❌ |
| 19 | `/holiday/dashboard-holidays-overview` | `holiday.dashboard-holidays-overview` | ✅ `auth` | ❌ |

### 2.6 Organization Module (7 dashboards)

| # | URL | Route Name | Auth? | Role-Gated? |
|---|-----|-----------|-------|-------------|
| 20 | `/organization/dashboard` | `organization.dashboard` | ✅ `auth` | ❌ |
| 21 | `/organization/dashboard-overview` | `organization.dashboard-overview` | ✅ `auth` | ❌ |
| 22 | `/organization/dashboard-companies-overview` | `organization.dashboard-companies-overview` | ✅ `auth` | ❌ |
| 23 | `/organization/dashboard-structure-overview` | `organization.dashboard-structure-overview` | ✅ `auth` | ❌ |
| 24 | `/organization/dashboard-locations-overview` | `organization.dashboard-locations-overview` | ✅ `auth` | ❌ |
| 25 | `/organization/dashboard-classification-overview` | `organization.dashboard-classification-overview` | ✅ `auth` | ❌ |
| 26 | `/organization/dashboard-reports-overview` | `organization.dashboard-reports-overview` | ✅ `auth` | ❌ |

---

## 3. Who Can Access What — Current State

### Known Roles (from Spatie/Permission via `HasRoles` trait)

| Role | Description |
|------|-------------|
| `super_admin` | Full system access |
| `admin` | Administrative access |
| `company_admin` | Company-level admin |
| `hr_manager` | HR management |
| `payroll_officer` | Payroll processing |
| `employee` | Basic employee (ESS) |

### Current Access Matrix (actual, not intended)

| Dashboard Group | `super_admin` | `admin` | `company_admin` | `hr_manager` | `payroll_officer` | `employee` | Unauthenticated |
|-----------------|---------------|---------|-----------------|--------------|-------------------|------------|-----------------|
| HR dashboards | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ❌ |
| Payroll dashboards | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ❌ |
| Attendance dashboards | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ❌ |
| Leave dashboards | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ❌ |
| Holiday dashboards | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ❌ |
| Organization dashboards | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ❌ |
| HR CRUD routes (lines 130-294) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ ⚠️ | ✅ 🔴 |

⚠️ = Should NOT have access  
🔴 = Critical vulnerability

---

## 4. `RedirectEssUsersFromAdminViews` Middleware Analysis

**File:** [`app/Modules/Hr/Http/Middleware/RedirectEssUsersFromAdminViews.php`](app/Modules/Hr/Http/Middleware/RedirectEssUsersFromAdminViews.php)

### What it covers:
```php
protected array $adminPatterns = [
    'attendance/attendances',    // → /hr/my-attendance
    'attendance/clock-events',   // → /hr/my-clock-events
    'leave/leave-requests',      // → /hr/my-leave-requests
    'hr/documents',              // → /hr/my-documents-view
    'admin/activity-logs',       // → /hr/my-portal (no ESS equivalent)
];
```

### What it does NOT cover:
- ❌ `/hr/dashboard` — NOT redirected
- ❌ `/hr/dashboard-organization-overview` — NOT redirected
- ❌ `/hr/dashboard-people-overview` — NOT redirected
- ❌ `/hr/dashboard-manage-overview` — NOT redirected
- ❌ `/payroll/dashboard` — NOT redirected
- ❌ `/payroll/dashboard-processing-overview` — NOT redirected
- ❌ `/payroll/dashboard-configuration-overview` — NOT redirected
- ❌ `/attendance/dashboard` — NOT redirected
- ❌ Any other module dashboard — NOT redirected
- ❌ Cross-module access (e.g., `payroll_officer` accessing `/hr/dashboard`) — NOT handled

### Logic flaw:
The middleware only applies to users who have `employee` role AND do NOT have `admin`/`super_admin`. It does not handle:
- `payroll_officer` accessing HR dashboards
- `hr_manager` accessing Payroll dashboards
- `employee` accessing any dashboard directly (not just the 5 patterns)

---

## 5. Sidebar Visibility vs. Route Protection

The sidebar uses [`NavigationFilter`](src/Traits/NavigationFilter.php) and [`NavigationManager`](src/Services/Navigation/NavigationManager.php:413) to hide links based on roles. However, **sidebar filtering is NOT a security measure** — it only hides UI elements. Anyone who knows the URL can still access the route directly.

The [`ModuleServiceProvider`](src/Providers/ModuleServiceProvider.php:80) registers all modules with `'roles' => ['*']` (wildcard — all roles allowed). This means the NavigationManager's `checkModuleGate()` always returns `true` for every module.

---

## 6. Fix Approach Comparison

### Approach A: Add role middleware to each route group in module route files

```php
// In each module's Routes/web.php
Route::middleware(['web', 'auth', 'role:admin,hr_manager,super_admin'])->group(function () {
    Route::get('/hr/dashboard', ...);
});
```

| Pros | Cons |
|------|------|
| Explicit and granular | Requires changes in 6 module route files |
| Easy to understand per-route | Role names duplicated across files |
| Standard Laravel pattern | Hard to maintain consistency |
| | Doesn't handle cross-module access uniformly |

### Approach B: Extend `RedirectEssUsersFromAdminViews` middleware

| Pros | Cons |
|------|------|
| Single file to modify | Only handles `employee` → redirect pattern |
| Already exists in the pipeline | Doesn't handle `payroll_officer` → HR, `hr_manager` → Payroll |
| | Pattern-based URL matching is fragile |
| | Would need to add 20+ patterns |

### Approach C: Global middleware with configurable role mapping ✅ RECOMMENDED

Create a single middleware that checks URL prefix against a role map.

| Pros | Cons |
|------|------|
| Single source of truth | Need to carefully handle ESS exception paths |
| Consistent enforcement across all modules | Slightly more complex initial setup |
| Easy to add new modules/roles | |
| Configurable via config file | |
| Works with existing Spatie role system | |

### Approach D: Permission checks in each dashboard view/component

| Pros | Cons |
|------|------|
| Flexible per-component | NOT a security measure — route still accessible |
| | Scattered across many files |
| | Easy to miss new dashboards |
| | Data still loads before check |

---

## 7. Recommended Fix

### Approach: **Hybrid C + A** — Global Middleware + Fix Unprotected Routes

#### Step 1: Fix the 18 unprotected HR CRUD routes

**File:** [`app/Modules/Hr/Routes/web.php`](app/Modules/Hr/Routes/web.php:130)

Wrap lines 130-294 in `Route::middleware(['web', 'auth'])`:

```php
Route::middleware(['web', 'auth'])->group(function () {
    // All the CRUD routes currently at lines 130-294
    Route::get('attendance-policies/create', ...);
    Route::get('employees/create', ...);
    // ... etc
});
```

#### Step 2: Create `EnsureModuleDashboardAccess` middleware

**New file:** `app/Http/Middleware/EnsureModuleDashboardAccess.php`

This middleware:
1. Allows unauthenticated users through (Laravel's `auth` middleware handles the redirect)
2. Allows all authenticated users to access ESS paths (`/hr/my-*`, `/payroll/my-*`, `/attendance/my-*`, `/leave/my-*`)
3. For admin dashboard paths, checks the user's role against a configurable mapping
4. Redirects unauthorized users to their role-appropriate landing page

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureModuleDashboardAccess
{
    /**
     * URL prefix → allowed roles mapping.
     */
    protected array $moduleRoleMap = [
        'hr'        => ['super_admin', 'admin', 'hr_manager'],
        'payroll'   => ['super_admin', 'admin', 'payroll_officer'],
        'attendance'=> ['super_admin', 'admin', 'hr_manager'],
        'leave'     => ['super_admin', 'admin', 'hr_manager'],
        'holiday'   => ['super_admin', 'admin', 'hr_manager'],
        'organization' => ['super_admin', 'admin', 'company_admin'],
    ];

    /**
     * Paths that are always allowed (ESS views, my-* routes).
     */
    protected array $essPathPrefixes = [
        'hr/my-',
        'payroll/my-',
        'attendance/my-',
        'leave/my-',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $path = $request->path();

        // Allow ESS/my-* paths for all authenticated users
        foreach ($this->essPathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Check module dashboard access
        foreach ($this->moduleRoleMap as $prefix => $allowedRoles) {
            if (str_starts_with($path, $prefix . '/dashboard')) {
                if (!$user->hasAnyRole($allowedRoles)) {
                    return $this->redirectToAppropriateDashboard($user);
                }
                break;
            }
        }

        return $next($request);
    }

    protected function redirectToAppropriateDashboard($user)
    {
        return match (true) {
            $user->hasRole('employee') && !$user->hasAnyRole(['super_admin', 'admin', 'company_admin'])
                => redirect('/hr/my-portal')->with('warning', 'Access restricted. Redirected to your portal.'),
            $user->hasRole('payroll_officer')
                => redirect('/payroll/dashboard-processing-overview')->with('warning', 'Access restricted.'),
            $user->hasRole('hr_manager')
                => redirect('/hr/dashboard-people-overview')->with('warning', 'Access restricted.'),
            default
                => redirect('/home')->with('warning', 'Access restricted.'),
        };
    }
}
```

#### Step 3: Register the middleware in `bootstrap/app.php`

**File:** [`bootstrap/app.php`](bootstrap/app.php:14)

```php
$middleware->web(append: [
    \App\Http\Middleware\EnsureModuleDashboardAccess::class,
    \App\Modules\Hr\Http\Middleware\RedirectEssUsersFromAdminViews::class,
]);
```

Note: `EnsureModuleDashboardAccess` should run BEFORE `RedirectEssUsersFromAdminViews` so that dashboard access is checked first.

#### Step 4: (Optional) Fix library `/home` route

**File:** [`src/Routes/web.php`](src/Routes/web.php:46)

Add `auth` middleware to the `/home` route:

```php
Route::middleware(['auth'])->get('/home', function () {
    return view(config('ui-library.home_view', 'qf::home'));
})->name('home');
```

---

## 8. Intended Access Matrix (After Fix)

| Dashboard Group | `super_admin` | `admin` | `company_admin` | `hr_manager` | `payroll_officer` | `employee` |
|-----------------|---------------|---------|-----------------|--------------|-------------------|------------|
| HR dashboards | ✅ | ✅ | ✅ | ✅ | ❌ → redirected | ❌ → `/hr/my-portal` |
| Payroll dashboards | ✅ | ✅ | ❌ → redirected | ❌ → redirected | ✅ | ❌ → `/hr/my-portal` |
| Attendance dashboards | ✅ | ✅ | ✅ | ✅ | ❌ → redirected | ❌ → `/hr/my-portal` |
| Leave dashboards | ✅ | ✅ | ✅ | ✅ | ❌ → redirected | ❌ → `/hr/my-portal` |
| Holiday dashboards | ✅ | ✅ | ✅ | ✅ | ❌ → redirected | ❌ → `/hr/my-portal` |
| Organization dashboards | ✅ | ✅ | ✅ | ❌ → redirected | ❌ → redirected | ❌ → `/hr/my-portal` |
| ESS views (`/hr/my-*`) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 9. Library-Level vs. Consuming-App-Level

| Change | Level | Rationale |
|--------|-------|-----------|
| `EnsureModuleDashboardAccess` middleware | **Consuming App** | Role definitions and module-to-role mappings are app-specific |
| Fix unprotected HR CRUD routes | **Consuming App** | These are app-level module routes |
| Fix `/home` route auth | **Library** | The library's `/home` route should require auth by default |
| `RedirectEssUsersFromAdminViews` | **Consuming App** (existing) | Already app-specific |

The library already provides the infrastructure (Spatie Role model, `NavigationFilter` trait, `NavigationManager` with `checkModuleGate()`) but the actual role-based route protection must be implemented at the consuming-app level because role names and module-to-role mappings are business-logic decisions.

---

## 10. Files Changed Summary

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Middleware/EnsureModuleDashboardAccess.php` | **CREATE** | New global middleware for role-based dashboard access |
| `bootstrap/app.php` | **MODIFY** | Register `EnsureModuleDashboardAccess` in web middleware stack |
| `app/Modules/Hr/Routes/web.php` | **MODIFY** | Wrap unprotected CRUD routes (lines 130-294) in `auth` middleware |
| `src/Routes/web.php` | **MODIFY** | Add `auth` middleware to `/home` route (library-level fix) |

---

## 11. Implementation Record

> **Status**: ✅ COMPLETED
> **Date**: 2026-09-03

### Files Created

| # | File | Purpose |
|---|------|---------|
| 1 | [`app/Http/Middleware/EnsureModuleDashboardAccess.php`](../../LaravelProjects/hr-consuming-app/app/Http/Middleware/EnsureModuleDashboardAccess.php) | Global middleware enforcing role-based module dashboard access |

### Files Modified

| # | File | Change |
|---|------|--------|
| 1 | [`bootstrap/app.php`](../../LaravelProjects/hr-consuming-app/bootstrap/app.php) | Registered `EnsureModuleDashboardAccess` BEFORE `RedirectEssUsersFromAdminViews` in web middleware stack |
| 2 | [`app/Modules/Hr/Routes/web.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Routes/web.php) | Wrapped 18 unprotected CRUD routes (lines 130-294) in `Route::middleware(['web', 'auth'])` group |
| 3 | [`src/Routes/web.php`](src/Routes/web.php) | Added `auth` middleware to `/home` route (library-level fix) |
| 4 | [`config/ui-library.php`](../../LaravelProjects/hr-consuming-app/config/ui-library.php) | Added `module_access` config array with URL prefix → role mappings (consuming-app override) |
| 5 | [`src/Config/ui-library.php`](src/Config/ui-library.php) | Added `module_access` default config key (empty array — consuming app provides actual mappings) |
| 6 | [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php) | Added roles-based filtering: items with `roles` key are only shown to users with matching roles |
| 7 | [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) | Added roles-based filtering: items with `roles` key are only shown to users with matching roles |

### Middleware Design Decisions

- **`super_admin` and `admin` bypass all checks** — they can access every module dashboard and route
- **ESS paths (`/hr/my-*`) are explicitly allowed** for `employee` and `manager` roles before any admin-prefix check
- **`/home` redirects ESS-only users** to `/hr/my-portal` (prevents employees from seeing the generic admin landing page)
- **URL prefix → role mapping is config-driven via `module_access`** in [`config/ui-library.php`](../../LaravelProjects/hr-consuming-app/config/ui-library.php:428) (consuming app) and [`src/Config/ui-library.php`](src/Config/ui-library.php:467) (library default). The middleware reads from `config('ui-library.module_access')` at runtime, allowing consuming apps to override mappings without touching middleware code:
  - `/hr/my-*` → `employee`, `manager`
  - `/hr/*` → `hr_manager`, `admin`, `super_admin`
  - `/payroll/*` → `payroll_officer`, `admin`, `super_admin`
  - `/organization/*` → `admin`, `super_admin`
  - `/admin/*` → `admin`, `super_admin`
  - `/leave/*` → `hr_manager`, `admin`, `super_admin`
  - `/holiday/*` → `hr_manager`, `admin`, `super_admin`
  - `/attendance/*` → `hr_manager`, `admin`, `super_admin`
  - `/system/*` → `admin`, `super_admin`
- **Navigation roles enforcement** — [`Sidebar`](src/Http/Livewire/Layouts/Navs/Sidebar.php:1) and [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php:1) now filter items by the `roles` key defined in navigation configs, hiding admin-only links from ESS users
- **Unauthorized users are redirected** to their role-appropriate landing page with a flash warning message
- **Middleware runs BEFORE `RedirectEssUsersFromAdminViews`** — dashboard access is checked first, then ESS-specific admin view redirects

### Vulnerabilities Resolved

| # | Severity | Vulnerability | Fix |
|---|----------|-------------|-----|
| 1 | 🔴 Critical | 18 HR CRUD routes with NO middleware (accessible to unauthenticated users) | Wrapped in `Route::middleware(['web', 'auth'])` group |
| 2 | 🟠 High | 26 module dashboards accessible to any authenticated user (including `employee` role) | `EnsureModuleDashboardAccess` middleware enforces role-based access per URL prefix |
| 3 | 🟡 Medium | `/home` route had no `auth` middleware | Added `auth` middleware to the route definition |

### Related Documents

- [`ess-admin-view-leakage-analysis.md`](plans/ess-admin-view-leakage-analysis.md) — ESS admin view leakage fix (complementary, addresses sidebar/data leakage)
- [`ess-comprehensive-analysis.md`](plans/ess-comprehensive-analysis.md) — Full ESS analysis including this security fix