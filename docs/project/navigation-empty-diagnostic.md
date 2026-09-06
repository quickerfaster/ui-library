# Navigation Empty Diagnostic Report

**Date**: 2026-08-10  
**Project**: QuickerFaster UI Library + Quick-HR Reference App  
**Symptom**: Sidebar and top navigation bars render completely empty

---

## 1. Executive Summary

**Root Cause**: The sidebar-item permission check in [`sidebar-item.blade.php`](../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php:30-34) constructs Spatie permission names from the **last URL path segment** (which is plural, e.g., `employees` → `view_employees`), but Quick-HR's actual Spatie permissions use **singular** names (e.g., `view_employee`). This causes every single sidebar item to fail its permission check for non-admin users. Additionally, the `NavigationManager` and `NavigationLayout` use **two completely separate data pipelines** that produce different navigation structures, creating a structural disconnect between the top nav (context groups from module config) and the sidebar (module-registry sections from NavigationManager).

---

## 2. Config Inventory

### 2.1 Quick-HR Navigation Configs

#### `/app/Modules/Admin/Config/navigation.php`

| Key | Structure |
|-----|-----------|
| `context_groups` | 3 groups: `Users & Permissions`, `audit`, `General Settings`. Each has: `label`, `icon`, `order`, `route` (NULL), `url` |
| `contexts` | 3 context arrays, each containing items with: `key`, `label`, `icon`, `route` (URL path like `/admin/users`), `permission` (e.g., `view_user`), `order`, `page_title` |
| `layout` | `top_bar`, `context_menu`, `sidebar`, `bottom_bar`, `breadcrumb`, `title` |
| `shared_items` | `header` ([]), `footer` ([]) |
| `shared_top_items` | `left` ([]), `right` ([]) |

#### `/app/Modules/Hr/Config/navigation.php`

| Key | Structure |
|-----|-----------|
| `context_groups` | 7 groups: `Organization`, `policies`, `reports`, `people`, `payroll`, `leave`, `time`. Each has: `label`, `icon`, `order`, `route` (NULL), `url` |
| `contexts` | 10 context arrays (~50+ items total). Each item has: `key`, `label`, `icon`, `route` (URL path like `/hr/employees`), `permission` (e.g., `view_employee`), `order`, `page_title` |
| `layout` | Same structure as Admin |
| `shared_items` | `header` ([]), `footer` ([]) |
| `shared_top_items` | `left` ([]), `right` ([]) |

### 2.2 Library Navigation Configs

#### `src/Core/Admin/Config/navigation.php`

| Key | Structure |
|-----|-----------|
| `context_groups` | 2 groups: `users`, `access`. Each has: `label`, `icon`, `route` (**named route** like `admin.users`), `order` |
| `contexts` | 2 context arrays. Items have: `label`, `route` (**named route**), `icon`, `order` — **NO `key`, `permission`, or `page_title`** |
| `layout` | Same structure |
| `shared_items` | `header` ([]), `footer` ([]) |
| `shared_top_items` | `left` ([]), `right` ([]) |

#### `src/Core/Organization/Config/navigation.php`

Same structure as Core Admin — uses **named routes** (`organization.companies`), no `key`/`permission`/`page_title` on items.

#### `src/Core/System/Config/navigation.php`

Same structure — uses **named routes** (`system.settings`), no `key`/`permission`/`page_title`.

---

## 3. Structural Comparison: Quick-HR vs Library Configs

### Critical Differences

| Aspect | Quick-HR Configs | Library Core Configs |
|--------|-----------------|---------------------|
| **Route format** | URL paths: `/hr/employees` | Named routes: `admin.users` |
| **Item keys** | Has `key` (e.g., `employee`) | No `key` field |
| **Permissions** | Has `permission` (e.g., `view_employee`) | No `permission` field |
| **Page titles** | Has `page_title` | No `page_title` |
| **Context group `route`** | Always `NULL` | Named route string |
| **Context group `url`** | Has `url` fallback | No `url` field |

### Compatibility Assessment

The library's `NavigationManager::loadModuleNavItems()` reads `$config['contexts']` and flattens all items. It does NOT validate or transform the item structure — it passes items through as-is. This means:

- Quick-HR items pass through with their `key`, `permission`, `route` (URL path), etc.
- Library items pass through with their `label`, `route` (named), `icon`, `order`.

The config structures are **compatible at the loading level** — both are read successfully. The problem is downstream in rendering.

---

## 4. Pipeline Trace: Config → Rendered HTML

### Pipeline A: Top Navigation Bar

```
NavigationLayout::__construct()
  └─ loadNavigationConfig()
       └─ resolveNavigationConfigPath('hr')
            → app/Modules/Hr/Config/navigation.php  ✓ FOUND
       └─ $this->contextGroups = $config['context_groups']  → 7 groups
       └─ filterVisibleItems($this->contextGroups)  → all pass (no 'visibility' key, defaults to 'any')
  └─ setActiveContext()  → matches current route to a context group
  └─ render() → view('qf::components.layouts.navigation-layout')

navigation-layout.blade.php (line 128):
  <livewire:qf.top-nav :items="$contextGroups" ... />
       ↓
TopNav::mount($items = $contextGroups)
  └─ $this->items = $items  → 7 context groups

top-nav.blade.php (line 108-115):
  @foreach ($visibleDesktop as $key => $item)
      @include('qf::livewire.navs.partials.top-nav-item', ...)
  @endforeach
```

**Status**: ✅ Pipeline complete. Context groups should render as top nav tabs.  
**Potential issue**: If `top-nav-item.blade.php` also does a permission check, items could be hidden. Need to verify that partial.

### Pipeline B: Sidebar Navigation

```
NavigationLayout::__construct()
  └─ loadNavigationConfig()
       └─ $this->contextItems = $config['contexts']  → 10 context arrays
  └─ setActiveContext()  → e.g., 'people'
  └─ render() → view('qf::components.layouts.navigation-layout')

navigation-layout.blade.php (line 158):
  <livewire:qf.sidebar :items="$contextItems[$activeContext] ?? []" ... />
       ↓
Sidebar::mount($items = context items for active context)
  └─ $this->items = $items  ← STORED but IGNORED for rendering
  └─ buildModuleSections()
       └─ NavigationManager::getSections()
            └─ config('ui-library.navigation.sidebar') → sections key is EMPTY (all commented out)
            └─ buildFromModuleRegistry()  ← FALLBACK PATH
                 └─ getUserFacingModules()
                      → organization (user_facing=true, roles=['*'])
                      → admin (user_facing=true, roles=['super_admin'])
                      → hr (user_facing=true, roles=['*'])
                 └─ For each module: loadModuleNavItems()
                      → organization: 8 items from Core/Organization/Config/navigation.php
                      → admin: 3 items from Core/Admin/Config/navigation.php
                      → hr: ~50 items from app/Modules/Hr/Config/navigation.php
                 └─ filterVisibleItems() → all pass (no 'visibility' key)
                 └─ checkModuleGate()
                      → admin: roles=['super_admin'] → SKIPPED for non-admin users
                 └─ $this->moduleSections = [organization, hr]  (for non-admin)
       ↓
sidebar.blade.php (line 34):
  $sections = !empty($sidebarSections) ? $sidebarSections : ($moduleSections ?? []);
  → $sidebarSections is UNDEFINED (no view composer provides it)
  → falls through to $moduleSections
  → $sections = [organization, hr]
       ↓
For each section → sidebar-section.blade.php
  For each item → sidebar-item.blade.php
       ↓
  🔴 FAILURE POINT (line 30-34):
  $splittedUrl = explode('/', $item['route']);
  $viewName = $splittedUrl[count($splittedUrl) - 1];  // e.g., 'employees'
  $hasPermission = app(DefaultAuthorizationProvider::class)
      ->canAccessView(auth()->user(), $viewName);
```

**Status**: 🔴 **BREAKS HERE** — Permission check constructs wrong permission name.

---

## 5. Root Cause Analysis

### 🔴 FAILURE POINT #1 (PRIMARY): Permission Name Mismatch in sidebar-item.blade.php

**File**: [`src/Resources/views/livewire/navs/partials/sidebar-item.blade.php`](../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php:30-34)

**Mechanism**:
```php
$splittedUrl = explode('/', $item['route']);       // '/hr/employees' → ['', 'hr', 'employees']
$viewName = count($splittedUrl) > 0
    ? $splittedUrl[count($splittedUrl) - 1]          // 'employees'
    : '';
$hasPermission = app(DefaultAuthorizationProvider::class)
    ->canAccessView(auth()->user(), $viewName);      // checks 'view_employees'
```

[`DefaultAuthorizationProvider::canAccessView()`](../src/Services/DataTables/DefaultAuthorizationProvider.php:26-34):
```php
public function canAccessView(Authenticatable $user, string $viewName): bool
{
    if (AuthorizationService::isBypassAllowed($user)) {
        return true;  // ← Admins bypass this
    }
    return $user->can('view_' . $viewName);  // ← 'view_employees'
}
```

**The mismatch**:

| Route | URL Segment (viewName) | Constructed Permission | Actual Spatie Permission | Match? |
|-------|----------------------|----------------------|--------------------------|--------|
| `/hr/employees` | `employees` | `view_employees` | `view_employee` | ❌ |
| `/hr/departments` | `departments` | `view_departments` | `view_department` | ❌ |
| `/hr/locations` | `locations` | `view_locations` | `view_location` | ❌ |
| `/hr/companies` | `companies` | `view_companies` | `manage-system` | ❌ |
| `/hr/leave-types` | `leave-types` | `view_leave-types` | `view_leave_type` | ❌ |
| `/hr/attendances` | `attendances` | `view_attendances` | `view_attendance` | ❌ |

**Every single Quick-HR item fails this check for non-admin users.** The URL segments are plural; the Spatie permissions are singular.

**For admin users** (super_admin, admin): [`AuthorizationService::isBypassAllowed()`](../src/Services/AccessControl/AuthorizationService.php:40-51) returns `true`, so items pass. Admins should see the sidebar.

### 🟡 FAILURE POINT #2: Dual Data Pipelines — NavigationLayout vs NavigationManager

**Files**: 
- [`NavigationLayout.php`](../src/Components/NavigationLayout.php:112-156) — loads module config, extracts `context_groups` and `contexts`
- [`NavigationManager.php`](../src/Services/Navigation/NavigationManager.php:138-199) — builds sections from module registry

**Mechanism**: Two completely separate data sources feed the two nav bars:

| Nav Bar | Data Source | Structure |
|---------|------------|-----------|
| **Top Nav** | `NavigationLayout::$contextGroups` (from module's `navigation.php` → `context_groups`) | Flat list of context groups |
| **Sidebar** | `NavigationManager::getSections()` → `buildFromModuleRegistry()` (from `config('ui-library.modules')` + each module's `navigation.php` → `contexts`) | Module-grouped sections with flattened items |

The `$items` passed from `NavigationLayout` to `Sidebar` (`$contextItems[$activeContext]`) are **only used as a fallback** when `$moduleSections` is empty ([sidebar.blade.php:34](../src/Resources/views/livewire/navs/sidebar.blade.php:34)). Since `NavigationManager` always populates `$moduleSections`, the `$items` from `NavigationLayout` are **never rendered**.

**Impact**: The sidebar shows ALL modules' items (organization, admin, hr) regardless of which context group is active in the top nav. The top nav and sidebar are structurally disconnected.

### 🟡 FAILURE POINT #3: `$sidebarSections` View Variable Undefined

**File**: [`sidebar.blade.php`](../src/Resources/views/livewire/navs/sidebar.blade.php:31-34)

```blade
@php
    // Prefer $sidebarSections from NavigationManager (Phase 4.5),
    // fall back to $moduleSections from Sidebar::buildModuleSections() (Phase 4.3)
    $sections = !empty($sidebarSections) ? $sidebarSections : ($moduleSections ?? []);
@endphp
```

`$sidebarSections` is never defined — there is no view composer, no data passed from `NavigationLayout`, and the `Sidebar` component does not set it. It always falls through to `$moduleSections`. This is not a bug per se (the fallback works), but it means the intended Phase 4.5 "config-driven sidebar" path is dead code.

### 🟢 NON-FAILURE: Config Discovery Works Correctly

[`ModuleServiceProvider::discoverBusinessModules()`](../src/Providers/ModuleServiceProvider.php:28-108) correctly discovers `app/Modules/Hr` and `app/Modules/Admin`, registers them in `config('ui-library.modules')`, and loads their routes and views.

[`NavigationManager::resolveNavigationConfigPath()`](../src/Services/Navigation/NavigationManager.php:511-536) correctly resolves:
- Core modules: `vendor/quicker-faster/ui-library/src/Core/{Module}/Config/navigation.php`
- Business modules: `app/Modules/{Module}/Config/navigation.php`

Both Quick-HR configs are found and loaded successfully.

### 🟢 NON-FAILURE: Top Nav Context Groups Load Correctly

[`NavigationLayout::loadNavigationConfig()`](../src/Components/NavigationLayout.php:112-156) correctly loads the HR config and extracts 7 context groups. The `filterVisibleItems()` pass succeeds (no `visibility` key → defaults to `'any'`). The top nav should render context group tabs.

---

## 6. Fix Recommendations

### Fix #1 (CRITICAL): Use the `permission` key from the nav config instead of deriving from URL

**File**: [`src/Resources/views/livewire/navs/partials/sidebar-item.blade.php`](../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php:30-34)

**Current code**:
```php
$splittedUrl = explode('/', $item['route']);
$viewName = count($splittedUrl) > 0 ? $splittedUrl[count($splittedUrl) - 1] : '';
$hasPermission = app(\QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider::class)
    ->canAccessView( auth()->user(), $viewName);
```

**Problem**: Derives `viewName` from URL segment (plural), but Spatie permissions use singular names.

**Recommended fix** — Use the `permission` key already present in Quick-HR configs:
```php
@php
$hasPermission = true;
if (!empty($item['permission'])) {
    $hasPermission = app(\QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider::class)
        ->canAccessView(auth()->user(), substr($item['permission'], 5)); // strip 'view_' prefix
}
// Fallback for items without explicit permission: derive from URL as before
if (empty($item['permission'])) {
    $splittedUrl = explode('/', $item['route']);
    $viewName = count($splittedUrl) > 0 ? $splittedUrl[count($splittedUrl) - 1] : '';
    $hasPermission = app(\QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider::class)
        ->canAccessView(auth()->user(), $viewName);
}
@endphp
```

**Alternative (cleaner)**: Change `DefaultAuthorizationProvider::canAccessView()` to accept the full permission string directly, or add a new method `canAccessByPermission(string $permission)` that checks the exact permission without the `view_` prefix construction.

### Fix #2 (STRUCTURAL): Align NavigationLayout and NavigationManager data sources

**Files**: 
- [`NavigationLayout.php`](../src/Components/NavigationLayout.php)
- [`NavigationManager.php`](../src/Services/Navigation/NavigationManager.php)
- [`sidebar.blade.php`](../src/Resources/views/livewire/navs/sidebar.blade.php)

**Problem**: The top nav uses `context_groups` from the module config; the sidebar uses `NavigationManager` which builds from the module registry. These are disconnected.

**Recommended fix**: Either:
1. **Option A**: Have `NavigationLayout` pass `$moduleSections` (from `NavigationManager`) to the sidebar view as `$sidebarSections`, making the Phase 4.5 path work.
2. **Option B**: Have the sidebar use `$items` (the active context items from `NavigationLayout`) instead of `NavigationManager`, restoring the pre-4.5 behavior where sidebar shows only the active context's items.
3. **Option C**: Merge both approaches — use `NavigationManager` for the section structure but filter to only show items matching the active context.

### Fix #3 (CLEANUP): Remove dead `$sidebarSections` code path or implement it

**File**: [`sidebar.blade.php`](../src/Resources/views/livewire/navs/sidebar.blade.php:31-34)

Either:
- Register a view composer that provides `$sidebarSections` from `NavigationManager`, or
- Remove the `$sidebarSections` check and always use `$moduleSections`

### Fix #4 (DEFENSE): Add `visibility` key handling for Quick-HR configs

Quick-HR configs don't have a `visibility` key. While this defaults to `'any'` (visible), it would be safer to document this requirement or add a default in the config loading step.

---

## 7. Verification Steps

After applying fixes, verify:

1. **Non-admin user with `view_employee` permission**: Should see "Employees" in the sidebar under the HR section
2. **Non-admin user with `view_department` permission**: Should see "Departments" in the sidebar
3. **Admin user**: Should see all items (bypass check)
4. **Top nav**: Should show context group tabs matching the active module
5. **Context switching**: Clicking a different top nav tab should update the sidebar items

---

## Appendix: Complete File Reference

| File | Role |
|------|------|
| [`app/Modules/Hr/Config/navigation.php`](/Users/mac/Projects/LaravelProjects/quick-hr/app/Modules/Hr/Config/navigation.php) | Quick-HR HR module nav config (514 lines) |
| [`app/Modules/Admin/Config/navigation.php`](/Users/mac/Projects/LaravelProjects/quick-hr/app/Modules/Admin/Config/navigation.php) | Quick-HR Admin module nav config (121 lines) |
| [`src/Core/Admin/Config/navigation.php`](../src/Core/Admin/Config/navigation.php) | Library Admin nav config |
| [`src/Core/Organization/Config/navigation.php`](../src/Core/Organization/Config/navigation.php) | Library Organization nav config |
| [`src/Core/System/Config/navigation.php`](../src/Core/System/Config/navigation.php) | Library System nav config |
| [`src/Config/ui-library.php`](../src/Config/ui-library.php) | Module registry + navigation config structure |
| [`src/Providers/ModuleServiceProvider.php`](../src/Providers/ModuleServiceProvider.php) | Module discovery and registration |
| [`src/Services/Navigation/NavigationManager.php`](../src/Services/Navigation/NavigationManager.php) | Navigation section/item builder |
| [`src/Components/NavigationLayout.php`](../src/Components/NavigationLayout.php) | Layout component that loads nav config |
| [`src/Http/Livewire/Layouts/Navs/Sidebar.php`](../src/Http/Livewire/Layouts/Navs/Sidebar.php) | Sidebar Livewire component |
| [`src/Http/Livewire/Layouts/Navs/TopNav.php`](../src/Http/Livewire/Layouts/Navs/TopNav.php) | TopNav Livewire component |
| [`src/Resources/views/livewire/navs/sidebar.blade.php`](../src/Resources/views/livewire/navs/sidebar.blade.php) | Sidebar Blade view |
| [`src/Resources/views/livewire/navs/top-nav.blade.php`](../src/Resources/views/livewire/navs/top-nav.blade.php) | TopNav Blade view |
| [`src/Resources/views/livewire/navs/partials/sidebar-section.blade.php`](../src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) | Sidebar section partial |
| [`src/Resources/views/livewire/navs/partials/sidebar-item.blade.php`](../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) | **🔴 Sidebar item partial — contains the bug** |
| [`src/Resources/views/components/layouts/navigation-layout.blade.php`](../src/Resources/views/components/layouts/navigation-layout.blade.php) | Main navigation layout view |
| [`src/Traits/NavigationFilter.php`](../src/Traits/NavigationFilter.php) | Visibility filter trait |
| [`src/Services/AccessControl/AuthorizationService.php`](../src/Services/AccessControl/AuthorizationService.php) | Admin bypass logic |
| [`src/Services/DataTables/DefaultAuthorizationProvider.php`](../src/Services/DataTables/DefaultAuthorizationProvider.php) | Permission check implementation |