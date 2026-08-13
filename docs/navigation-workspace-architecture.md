# Navigation & Workspace Architecture

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-13
> **Status**: ✅ Complete — covers config-to-UI mapping, workspace analysis, implementation, sidebar customization, icon mode, module switcher, cross-context dropdowns, user profile menu, notification icon, module switcher config, background jobs config, and all 14 fix/audit categories + 19 new items

---

## Table of Contents

1. [HR Navigation Config → UI Behavior Mapping](#1-hr-navigation-config--ui-behavior-mapping)
   - [1.1 Config Schema Reference](#11-config-schema-reference)
   - [1.2 Data Flow: Config → Rendered UI](#12-data-flow-config--rendered-ui)
   - [1.3 TopNav: context_groups → Tabs](#13-topnav-context_groups--tabs)
   - [1.4 Sidebar: contexts → Menu Items](#14-sidebar-contexts--menu-items)
   - [1.5 Active Context Detection](#15-active-context-detection)
   - [1.6 Permission Resolution](#16-permission-resolution)
   - [1.7 Layout Configuration](#17-layout-configuration)
2. [Workspace Parameter Analysis Summary](#2-workspace-parameter-analysis-summary)
   - [2.1 Current Architecture Gap](#21-current-architecture-gap)
   - [2.2 The Five Scenarios](#22-the-five-scenarios)
   - [2.3 Design Options Comparison](#23-design-options-comparison)
   - [2.4 Option D: Hybrid Two-Tier Filtering (Recommended)](#24-option-d-hybrid-two-tier-filtering-recommended)
   - [2.5 Integration with Existing NavigationFilter](#25-integration-with-existing-navigationfilter)
3. [Workspace Parameter Implementation Summary](#3-workspace-parameter-implementation-summary)
   - [3.1 File Inventory](#31-file-inventory)
   - [3.2 WorkspaceResolver Contract](#32-workspaceresolver-contract)
   - [3.3 NullWorkspaceResolver (Default)](#33-nullworkspaceresolver-default)
   - [3.4 WorkspaceFilter Service](#34-workspacefilter-service)
   - [3.5 Filter Integration Points](#35-filter-integration-points)
   - [3.6 Consuming App: Custom Resolver Binding](#36-consuming-app-custom-resolver-binding)
   - [3.7 Performance Characteristics](#37-performance-characteristics)
4. [Sidebar Customization Config Keys](#4-sidebar-customization-config-keys)
   - [4.1 The `sidebar` Config Section](#41-the-sidebar-config-section)
   - [4.2 Three Rendering Modes](#42-three-rendering-modes)
   - [4.3 Config Schema Reference](#43-config-schema-reference)
5. [Icon Mode Behavior](#5-icon-mode-behavior)
   - [5.1 Toggle Mechanism](#51-toggle-mechanism)
   - [5.2 Section Headers in Icon Mode](#52-section-headers-in-icon-mode)
   - [5.3 Expand Indicator Chevron](#53-expand-indicator-chevron)
   - [5.4 Empty Section Body Fix](#54-empty-section-body-fix)
6. [Module Switcher Bootstrap Dropdown Integration](#6-module-switcher-bootstrap-dropdown-integration)
   - [6.1 Before: Livewire Component](#61-before-livewire-component)
   - [6.2 After: Inline Bootstrap Dropdown](#62-after-inline-bootstrap-dropdown)
   - [6.3 TopNav determineModuleName() Fix](#63-topnav-determinemodulename-fix)
   - [6.4 Icon Base Class Fixes](#64-icon-base-class-fixes)
7. [Changelog](#7-changelog)
8. [Cross-References](#8-cross-references)
9. [User Profile Dropdown Menu](#9-user-profile-dropdown-menu)
   - [9.1 Overview](#91-overview)
   - [9.2 Config Schema](#92-config-schema)
   - [9.3 Menu Item Keys](#93-menu-item-keys)
   - [9.4 Rendering](#94-rendering)
   - [9.5 Permission Filtering](#95-permission-filtering)
10. [Notification Icon](#10-notification-icon)
   - [10.1 Overview](#101-overview)
   - [10.2 Config Schema](#102-config-schema)
   - [10.3 Config Keys](#103-config-keys)
   - [10.4 Rendering](#104-rendering)
   - [10.5 Behavior](#105-behavior)
11. [Module Switcher Role-Based Configuration](#11-module-switcher-role-based-configuration)
   - [11.1 Overview](#111-overview)
   - [11.2 Config Schema](#112-config-schema)
   - [11.3 Filtering Logic](#113-filtering-logic)
   - [11.4 Behavior](#114-behavior)
12. [Background Jobs Role-Based Configuration](#12-background-jobs-role-based-configuration)
   - [12.1 Overview](#121-overview)
   - [12.2 Config Schema](#122-config-schema)
   - [12.3 Config Keys](#123-config-keys)
   - [12.4 Behavior](#124-behavior)

---

## 1. HR Navigation Config → UI Behavior Mapping

### 1.1 Config Schema Reference

The HR module's navigation is defined in a single PHP config file at [`app/Modules/Hr/Config/navigation.php`](../../LaravelProjects/quick-hr/app/Modules/Hr/Config/navigation.php) (514 lines). The config has four top-level sections:

| Section | Purpose | Drives |
|---|---|---|
| `context_groups` | Top-level navigation categories (tabs) | [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php) |
| `contexts` | Menu items organized by context group | [`Sidebar`](src/Http/Livewire/Layouts/Navs/Sidebar.php) |
| `layout` | UI chrome toggles (top bar, sidebar state, breadcrumbs) | [`NavigationLayout`](src/Components/NavigationLayout.php) |
| `shared_items` / `shared_top_items` | Header, footer, and top bar shared items | Renderer views |

#### context_groups (7 groups in HR config)

Each entry is keyed by a slug and defines a top-nav tab/button:

```php
// Example: People group
'people' => [
    'label' => 'People',           // Display text
    'icon'  => 'fas fa-user-tie',  // Font Awesome icon class
    'order' => 999,                // Sort priority (lower = first)
    'route' => NULL,               // Named route (null = use url)
    'url'   => 'hr/dashboard-people-overview',  // Fallback URL path
],
```

The HR config defines these groups: `Organization`, `policies`, `reports`, `people`, `payroll`, `leave`, `time`.

#### contexts (10+ context keys in HR config)

Each context key maps to an array of menu items. The key must match a `context_groups` slug:

```php
// Example: People context items
'people' => [
    [
        'key'        => 'people_overview',
        'label'      => 'Overview',
        'icon'       => 'fas fa-chart-bar',
        'route'      => '/hr/dashboard-people-overview',
        'permission' => 'view_people_overview',
        'order'      => 1,
    ],
    [
        'key'        => 'employee',
        'label'      => 'Employees',
        'icon'       => 'fas fa-user-tie',
        'route'      => '/hr/employees',
        'permission' => 'view_employee',
        'order'      => 2,
    ],
    // ... 7 more items for 'people'
],
```

Notable: the HR config has three **orphan contexts** — `attendance_adjustment`, `clock_event`, and `attendance_session` — which have entries in `contexts` but no corresponding key in `context_groups`. These are legacy items that render when their parent group (likely `time`) is active but have no direct top-nav tab.

---

### 1.2 Data Flow: Config → Rendered UI

The complete pipeline from PHP config array to rendered HTML:

```
+--------------------------------------------------+
| navigation.php (PHP array)                        |
|   context_groups: [People, Payroll, Leave, ...]   |
|   contexts: { people: [...], payroll: [...] }     |
|   layout: { top_bar, sidebar, ... }               |
|   shared_items: { header: [], footer: [] }        |
+----------------------+---------------------------+
                       |
                       v
+--------------------------------------------------+
| NavigationLayout::loadNavigationConfig()          |
|   1. resolveNavigationConfigPath(moduleName)      |
|      - Core path → Vendor fallback → Business     |
|      - Published override                         |
|   2. require $configPath                          |
|   3. $this->contextGroups = config[context_groups]|
|   4. $this->contextItems  = config[contexts]      |
|   5. $this->layoutConfig  = config[layout]        |
|   6. filterVisibleItems() on all arrays           |
|   7. WorkspaceFilter on contextGroups + items     |
|   8. Sort by order                                |
+----------------------+---------------------------+
                       |
          +------------+------------+
          |                         |
          v                         v
+------------------+     +-----------------------+
| TopNav (Livewire) |     | Sidebar (Livewire)     |
|                   |     |                        |
| Receives:         |     | Receives:              |
| - items[]         |     | - items[] (filtered    |
|   (contextGroups) |     |   by activeContext)    |
| - activeContext   |     | - activeContext        |
| - moduleName      |     | - headerItems[]        |
| - leftShared[]    |     | - footerItems[]        |
| - rightShared[]   |     | - state                |
|                   |     | - currentModelName     |
| mount() stores    |     |                        |
| props, loads      |     | mount() builds         |
| companies via     |     | moduleSections if      |
| CompanyProvider   |     | no items passed        |
+------------------+     +-----------------------+
          |                         |
          v                         v
+--------------------------------------------------+
| Blade Views                                       |
|   qf::components.layouts.navigation-layout        |
|     qf::livewire.navs.top-nav                     |
|     qf::livewire.navs.sidebar                     |
|     qf::livewire.navs.bottom-bar                  |
+--------------------------------------------------+
```

### 1.3 TopNav: context_groups → Tabs

[`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php) receives `$items` (the `context_groups` array) and `$activeContext` from [`NavigationLayout`](src/Components/NavigationLayout.php).

**Rendering logic**:

- The first `maxDesktop` (default: 5) items render as visible tabs
- Remaining items go into an overflow dropdown (desktop) or hamburger menu (mobile)
- The item matching `$activeContext` gets the `active` CSS class
- Clicking a tab dispatches `contextSelected` Livewire event
- The overflow selector also navigates to the item's `route` or `url`

**Quick-HR rendering example** (7 context groups, `maxDesktop=5`):

```
Visible tabs:    [People] [Payroll] [Leave] [Time] [Organization]
Overflow menu:   [Policies] [Reports]
Active tab:      People (highlighted)
```

**Key method**: [`handleOverflowSelect()`](src/Http/Livewire/Layouts/Navs/TopNav.php:138) — resolves the selected overflow item's route and redirects.

The `TopNav` also handles:
- **Company switching** via [`loadCompanies()`](src/Http/Livewire/Layouts/Navs/TopNav.php:51) using [`CompanyProvider`](src/Contracts/Navigation/CompanyProvider.php)
- **Company switcher visibility** gated by `config('ui-library.navigation.show_company_switcher')` and role checks
- Session persistence via `current_company_id`

### 1.4 Sidebar: contexts → Menu Items

[`Sidebar`](src/Http/Livewire/Layouts/Navs/Sidebar.php) receives `$items` — this is the `contextItems[$activeContext]` subset, meaning only items belonging to the currently-selected context group.

**Two rendering paths**:

1. **Context-driven** (P0 fix): When `$items` is non-empty AND `$activeContext` is set, the sidebar renders the passed items directly — no module sections. This enables the `context_groups → sidebar` linkage: selecting a top-nav tab shows only that context group's items.

2. **Module-driven** (Phase 4.5 fallback): When `$items` is empty (no active context), [`buildModuleSections()`](src/Http/Livewire/Layouts/Navs/Sidebar.php:110) tries [`NavigationManager::getSections()`](src/Services/Navigation/NavigationManager.php:44) first, then falls back to the module registry approach.

**Quick-HR sidebar rendering example** (People context active):

```
Sidebar: People
  [fas fa-chart-bar]   Overview
  [fas fa-user-tie]    Employees
  [fas fa-id-card]     Profiles
  [fas fa-briefcase]   Current Jobs
  [fas fa-user-friends] Teams
  [fas fa-layer-group] Employee Groups
  [fas fa-tags]        Tags
  [fas fa-history]     Job History
  [fas fa-folder-open] Documents
  [fas fa-briefcase]   Job Titles
```

**Sidebar features**:
- Toggle between `full` (icon + label) and `icon` (icon only) states via [`toggleState()`](src/Http/Livewire/Layouts/Navs/Sidebar.php:286)
- State persisted to session and localStorage
- Settings button appears when [`settingsContext`](src/Components/NavigationLayout.php:256-269) is resolved (module has Config/settings.php with matching context groups)
- Module sections are collapsible with auto-expand for sections containing the active route

### 1.5 Active Context Detection

[`NavigationLayout::setActiveContext()`](src/Components/NavigationLayout.php:169) determines which context group is "active" based on the current URL:

```
1. Explicit context parameter passed to component
   → if ($this->context && isset($this->contextGroups[$this->context]))
      use it

2. Route matching (primary detection)
   for each context group:
     for each item in contexts[group]:
       if item.route matches currentRouteName (named route)
         → activeContext = group
         return
       if item.route (as URL path) matches currentPath or is prefix
         → activeContext = group
         return

3. Fallback: first key in contextGroups
```

**Prefix matching**: If the current URL is `/hr/employees/123/edit`, it matches the `employee` item with route `/hr/employees` because `str_starts_with('/hr/employees/123/edit', '/hr/employees')` is true. This means nested pages within a context group correctly highlight the parent tab.

The same matching logic is used in [`getCurrentContextItem()`](src/Components/NavigationLayout.php:202) to identify the currently-active sidebar item for breadcrumb generation.

### 1.6 Permission Resolution

Two distinct filtering layers (in order of application):

| Layer | Scope | Mechanism | When |
|---|---|---|---|
| **Workspace filtering** | Structural removal | [`WorkspaceFilter`](src/Services/Navigation/WorkspaceFilter.php) — `feature` gates + `workspace` constraints | Before visibility |
| **Visibility filtering** | Runtime auth check | [`NavigationFilter`](src/Traits/NavigationFilter.php) — `visibility` rules | After workspace |

[`NavigationFilter`](src/Traits/NavigationFilter.php) supports these visibility rules:

| Rule | Behavior |
|---|---|
| `any` | Always visible (default) |
| `auth` | Visible only when authenticated |
| `guest` | Visible only when NOT authenticated |
| `role:{name}` | Visible when user has the Spatie role |
| `permission:{name}` | Visible when user has the Spatie permission (via `AuthorizationService::canAccessView()`) |

Both [`NavigationLayout`](src/Components/NavigationLayout.php) and [`NavigationManager`](src/Services/Navigation/NavigationManager.php) `use NavigationFilter` — meaning all navigation arrays (context groups, context items, shared items, module sections) pass through [`filterVisibleItems()`](src/Traits/NavigationFilter.php:15).

**Important order**: Workspace filtering runs FIRST (structural removal), then visibility filtering runs SECOND (auth check). This means:
- Items removed by workspace constraints never reach the visibility check
- Permission checks only run on items that passed workspace filtering
- No wasted auth checks on items already excluded by workspace context

### 1.7 Layout Configuration

The `layout` section of the navigation config controls UI chrome:

```php
'layout' => [
    'top_bar' => [
        'enabled' => true,          // Show/hide entire top bar
    ],
    'context_menu' => [
        'type'        => 'sidebar',  // 'sidebar' or 'horizontal'
        'position'    => 'left',     // 'left' or 'right'
        'allow_switch' => true,      // User can toggle menu type
        'default_type' => 'sidebar',
    ],
    'sidebar' => [
        'initial_state' => 'full',   // 'full' or 'icon'
    ],
    'bottom_bar' => [
        'enabled' => true,           // Mobile bottom navigation
    ],
    'breadcrumb' => [
        'enabled' => true,           // Breadcrumb trail
    ],
    'title' => [
        'enabled' => true,           // Page title
    ],
],
```

Session overrides: `context_menu_type` and `sidebar_state` read from session in [`NavigationLayout`](src/Components/NavigationLayout.php:88-95), allowing user preferences to override config defaults.

---

## 2. Workspace Parameter Analysis Summary

### 2.1 Current Architecture Gap

The existing navigation pipeline loads config as a static PHP array, filters only by auth visibility (`any`/`auth`/`guest`/`role:`/`permission:`), and renders. There is no mechanism to say:

- "For company B, don't show Departments" (multi-tenant feature toggling)
- "For payroll managers only, show Payroll Runs" (role-based structural exclusion)
- "For engineering department, show engineering-specific reports" (department-scoped items)
- "Hide the Time tab when Time & Attendance is not licensed" (feature-flagged groups)
- "Show 'Current Payroll Run' only during active payroll processing" (temporal state)

**Source**: [`plans/workspace-parameter-analysis.md`](plans/workspace-parameter-analysis.md)

---

### 2.2 The Five Scenarios

| # | Scenario | Problem | Affected Config Sections |
|---|---|---|---|
| **1** | **Multi-Company / Multi-Tenant** | Sidebar items shown for features disabled in the active tenant | `Organization`: `department`; `people`: `employee_group`, `tag`, `team`; `policies`: `work_pattern`; `payroll`: `payroll_policy`; `time`: `shift`, `shift_schedule` |
| **2** | **Role-Based Items** | All 10 payroll items loaded for every user; permission check is runtime, not structural | `payroll`: all 10 items (`payroll_overview` through `payroll_policy_assignment`) |
| **3** | **Department-Level Variations** | Generic "Saved Reports" is the only reports item; no department-scoped report shortcuts | `reports`: `saved_report` (1 item); `time`: second `saved_report` entry |
| **4** | **Feature-Flagged Modules** | "Time" tab visible for Company B even though they didn't license the module | `context_groups['time']` (entire group); `time`: all 7 items; 3 orphan contexts |
| **5** | **Payroll Period / Temporal** | "Payroll Runs" always shown; no way to promote active-run items or show processing state | `payroll`: `payroll_run`; shared items |

For detailed walkthroughs of each scenario with before/after config examples, see [`plans/workspace-parameter-analysis.md` §2](plans/workspace-parameter-analysis.md).

---

### 2.3 Design Options Comparison

| Option | Mechanism | Pros | Cons | Verdict |
|---|---|---|---|---|
| **A: Top-Level Workspace Key** | Complete config replacement trees per workspace | Clean separation, easy to reason about | Config duplication, Cartesian explosion with dimensions, not composable | ❌ Fails for real multi-tenant |
| **B: Per-Item Workspace Scoping** | `workspace` key on each config item | Fine-grained, no duplication, incremental | Verbose for large configs, inclusion-only, workspace list maintenance | ✅ Good for items; needs group-level complement |
| **C: Workspace as context_group Modifier** | `workspace_overrides` on groups with include/exclude lists | Group-level thinking, explicit overrides, include/exclude model | No item-level additions, tight coupling to resolver keys, conflict resolution | ✅ Good for groups; insufficient alone |
| **D: Hybrid Two-Tier** | `feature` gate on groups + `workspace` constraint map on items | Best of B + C, no duplication, composable, handles all 5 scenarios | Requires two filtering passes (minor overhead) | ✅ RECOMMENDED |

---

### 2.4 Option D: Hybrid Two-Tier Filtering (Recommended)

The workspace parameter works at **two levels**:

#### Tier 1: Context Group Level (`feature` gate)

```php
'context_groups' => [
    'people' => [
        'label' => 'People',
        'icon'  => 'fas fa-user-tie',
        'order' => 999,
        'url'   => 'hr/dashboard-people-overview',
        // No feature gate → always visible
    ],
    'payroll' => [
        'label'   => 'Payroll',
        'icon'    => 'fas fa-file-invoice-dollar',
        'order'   => 999,
        'url'     => 'hr/dashboard-payroll-overview',
        'feature' => 'payroll',  // ← only shown if in workspace['features']
    ],
    'time' => [
        'label'   => 'Time',
        'icon'    => 'fas fa-user-clock',
        'order'   => 999,
        'url'     => 'hr/dashboard-time-overview',
        'feature' => 'time_attendance',  // ← feature flag gate
    ],
],
```

**Resolution rule**: A group with a `feature` key is only kept when that feature string is present in the workspace's `features` array. Groups without `feature` are always kept. This directly addresses **Scenario 4** (feature-flagged modules).

#### Tier 2: Context Item Level (`workspace` constraint map)

```php
'contexts' => [
    'Organization' => [
        // No workspace constraint → always visible (subject to visibility filter)
        ['key' => 'company_profile_overview', 'label' => 'Overview', ...],

        // Scenario 1: Only show when departments feature is enabled for this tenant
        ['key' => 'department', 'label' => 'Departments', ...,
         'workspace' => ['feature' => 'departments']],
    ],
    'payroll' => [
        // Scenario 2: Only payroll admins and managers
        ['key' => 'pay_schedule', 'label' => 'Pay Schedules', ...,
         'workspace' => ['role' => 'payroll_admin']],

        ['key' => 'payroll_run', 'label' => 'Payroll Runs', ...,
         'workspace' => ['role' => 'payroll_manager']],

        // Everyone can see their own payslips (no constraint)
        ['key' => 'payroll_payslip', 'label' => 'Payslips', ...],

        // Scenario 5: Only during active payroll processing
        ['key' => 'current_payroll_run', 'label' => '⚠ Current Payroll Run',
         'order' => 0, 'workspace' => ['payroll_state' => 'active']],
    ],
    'reports' => [
        ['key' => 'saved_report', 'label' => 'Saved Reports', ...],

        // Scenario 3: Department-scoped shortcuts
        ['key' => 'eng_attendance_report', 'label' => 'Attendance Report', ...,
         'workspace' => ['department_type' => 'engineering']],

        ['key' => 'sales_headcount_report', 'label' => 'Headcount Report', ...,
         'workspace' => ['department_type' => 'sales']],
    ],
],
```

**Resolution rule**: An item matches if ALL keys in its `workspace` constraint match the resolved workspace map. If `workspace` is absent or empty, the item is always included. The `AND` logic across keys is implicit. This addresses **Scenarios 1, 2, 3, and 5**.

#### Workspace Constraint Syntax

```php
'workspace' => [
    'feature'         => 'departments',          // Exact match (single value)
    'role'            => 'payroll_admin',         // Exact match
    'payroll_state'   => 'active',                // Exact match
    'department_type' => 'engineering',           // Exact match
]
```

**Matching logic** (implemented in [`WorkspaceFilter::filterContextItems()`](src/Services/Navigation/WorkspaceFilter.php:68)):
- For each key in `workspace`, check if `workspace_resolver_output[key]` exists
- If key is missing from workspace context → constraint fails → item excluded
- If value does not match → constraint fails → item excluded
- All constraints must pass → item included

#### What a Resolved Workspace Looks Like

```php
// Example workspace for: Company A, Payroll Manager, Engineering Dept, Active Payroll
[
    'company_id'      => 5,
    'features'        => ['core_hr', 'payroll', 'departments', 'time_attendance', 'leave_management'],
    'role'            => 'payroll_manager',
    'department_id'   => 12,
    'department_type' => 'engineering',
    'payroll_state'   => 'active',
]
```

---

### 2.5 Integration with Existing NavigationFilter

The workspace filter integrates as an additional pass **before** the existing [`NavigationFilter`](src/Traits/NavigationFilter.php) visibility checks:

```
Raw Config Array
       │
       ▼
┌─────────────────────────────┐
│ WORKSPACE FILTERING          │  ← NEW (Phase: workspace)
│ WorkspaceFilter              │
│ ├─ filterContextGroups()     │     feature gate on groups
│ └─ filterContextItems()      │     workspace constraints on items
│ Structural removal           │
└─────────────┬───────────────┘
              │
              ▼
┌─────────────────────────────┐
│ VISIBILITY FILTERING         │  ← EXISTING
│ NavigationFilter             │
│ └─ filterVisibleItems()      │     any/auth/guest/role:/permission:
│ Runtime auth check           │
└─────────────┬───────────────┘
              │
              ▼
        Sorted Array → Render
```

**Rationale for this order**:
1. Workspace filtering is **structural** — items removed here should never have been in the config for this request context
2. Visibility filtering is **runtime auth** — it checks the current user's permissions
3. Running workspace first means permission checks are never performed on items already excluded by workspace context (no wasted `Auth::user()->hasRole()` calls)

See the implementation in [`NavigationLayout::loadNavigationConfig()`](src/Components/NavigationLayout.php:114) and [`NavigationManager::loadModuleNavItems()`](src/Services/Navigation/NavigationManager.php:314).

---

## 3. Workspace Parameter Implementation Summary

### 3.1 File Inventory

#### New Files (5 created)

| # | File | Role |
|---|---|---|
| 1 | [`src/Contracts/Navigation/WorkspaceResolver.php`](src/Contracts/Navigation/WorkspaceResolver.php) | **Contract** — interface for resolving the active workspace context map. Two methods: `resolve(): array` and `hasFeature(string): bool`. |
| 2 | [`src/Services/Navigation/NullWorkspaceResolver.php`](src/Services/Navigation/NullWorkspaceResolver.php) | **Default implementation** — returns empty context (`[]`). All items pass filtering. `hasFeature()` always returns `true`. Fully backward compatible. |
| 3 | [`src/Services/Navigation/WorkspaceFilter.php`](src/Services/Navigation/WorkspaceFilter.php) | **Filter engine** — accepts a resolved workspace array via constructor. Three filter methods: `filterContextGroups()`, `filterContextItems()`, `getWorkspace()`. All in-memory `array_filter` operations. |
| 4 | [`src/Providers/WorkspaceServiceProvider.php`](src/Providers/WorkspaceServiceProvider.php) | **Service provider** — binds `WorkspaceResolver` contract to `NullWorkspaceResolver` as a singleton. Consuming apps override with their own binding. |
| 5 | [`plans/workspace-parameter-analysis.md`](plans/workspace-parameter-analysis.md) | **Analysis document** — the 5 scenarios, design options comparison (A/B/C/D), Option D recommendation with full config examples and implementation approach. |

#### Modified Files (3 changed)

| # | File | Change Description |
|---|---|---|
| 1 | [`src/Components/NavigationLayout.php`](src/Components/NavigationLayout.php) | **Added workspace filtering in [`loadNavigationConfig()`](src/Components/NavigationLayout.php:154-161)**. After loading raw config arrays, applies `WorkspaceFilter::filterContextGroups()` and `WorkspaceFilter::filterContextItems()` before the existing `filterVisibleItems()` pass. Uses `app(WorkspaceResolver::class)->resolve()` to get workspace context. |
| 2 | [`src/Services/Navigation/NavigationManager.php`](src/Services/Navigation/NavigationManager.php) | **Added workspace filtering in [`loadModuleNavItems()`](src/Services/Navigation/NavigationManager.php:365-369)**. After flattening all context items, applies `WorkspaceFilter::filterContextItems()` to remove items gated by workspace constraints. Added `use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver` import. |
| 3 | [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | **Registered workspace bindings in [`register()`](src/Providers/UILibraryServiceProvider.php:70-73)**. Added singleton binding for `WorkspaceResolver` contract → `NullWorkspaceResolver`. Also registers `NavigationManager` singleton (Phase 4.5). |

---

### 3.2 WorkspaceResolver Contract

**File**: [`src/Contracts/Navigation/WorkspaceResolver.php`](src/Contracts/Navigation/WorkspaceResolver.php) (29 lines)

```php
namespace QuickerFaster\UILibrary\Contracts\Navigation;

interface WorkspaceResolver
{
    /**
     * Get the current workspace context map.
     *
     * Returns key-value pairs representing the current workspace context,
     * for example:
     *   ['company_id' => 1, 'role' => 'payroll_admin',
     *    'department_type' => 'engineering', 'features' => ['departments', 'time']]
     *
     * Navigation items can define `workspace` constraints that are matched
     * against this map. Context groups can define `feature` gates that are
     * checked against the `features` array.
     *
     * @return array
     */
    public function resolve(): array;

    /**
     * Check if a specific feature is enabled in the current workspace.
     *
     * @param  string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool;
}
```

**Design decisions**:
- `resolve()` returns a flat key-value map — simple, no nesting, easy to match against
- `hasFeature()` is a convenience method so consumers don't need to manually check `in_array('feature', $workspace['features'] ?? [])`
- The contract is purposefully minimal — consuming apps define what dimensions they need (company, role, department, payroll state, etc.)

---

### 3.3 NullWorkspaceResolver (Default)

**File**: [`src/Services/Navigation/NullWorkspaceResolver.php`](src/Services/Navigation/NullWorkspaceResolver.php) (29 lines)

```php
namespace QuickerFaster\UILibrary\Services\Navigation;

use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;

class NullWorkspaceResolver implements WorkspaceResolver
{
    public function resolve(): array
    {
        return [];  // Empty map → all items pass filter
    }

    public function hasFeature(string $feature): bool
    {
        return true;  // All features considered enabled
    }
}
```

**Why this works for backward compatibility**: When `resolve()` returns `[]`, both [`WorkspaceFilter::filterContextGroups()`](src/Services/Navigation/WorkspaceFilter.php:36-38) and [`WorkspaceFilter::filterContextItems()`](src/Services/Navigation/WorkspaceFilter.php:70-72) short-circuit — if the workspace is empty, all items pass through. Existing installations without a custom resolver see zero behavioral change.

---

### 3.4 WorkspaceFilter Service

**File**: [`src/Services/Navigation/WorkspaceFilter.php`](src/Services/Navigation/WorkspaceFilter.php) (107 lines)

```php
namespace QuickerFaster\UILibrary\Services\Navigation;

class WorkspaceFilter
{
    protected array $workspace;

    public function __construct(array $workspace)
    {
        $this->workspace = $workspace;
    }

    public function filterContextGroups(array $groups): array
    {
        if (empty($this->workspace)) {
            return $groups;  // No workspace → passthrough
        }

        $enabledFeatures = $this->workspace['features'] ?? [];

        return array_filter($groups, function ($group) use ($enabledFeatures) {
            if (!isset($group['feature'])) {
                return true;  // No feature gate → always visible
            }

            if (empty($enabledFeatures)) {
                return false;  // No features at all → exclude gated groups
            }

            return in_array($group['feature'], $enabledFeatures, true);
        });
    }

    public function filterContextItems(array $items): array
    {
        if (empty($this->workspace)) {
            return $items;  // No workspace → passthrough
        }

        return array_filter($items, function ($item) {
            if (!isset($item['workspace']) || !is_array($item['workspace'])) {
                return true;  // No constraint → always visible
            }

            foreach ($item['workspace'] as $constraintKey => $constraintValue) {
                if (!array_key_exists($constraintKey, $this->workspace)) {
                    return false;  // Key missing from workspace → exclude
                }

                if ($this->workspace[$constraintKey] !== $constraintValue) {
                    return false;  // Value mismatch → exclude
                }
            }

            return true;  // All constraints passed
        });
    }

    public function getWorkspace(): array
    {
        return $this->workspace;
    }
}
```

**Key behaviors**:

| Condition | `filterContextGroups` result | `filterContextItems` result |
|---|---|---|
| Empty workspace (`[]`) | All groups pass through | All items pass through |
| Group has no `feature` key | Always visible | N/A |
| Group `feature` in workspace `features[]` | Visible | N/A |
| Group `feature` NOT in workspace `features[]` | Excluded | N/A |
| Item has no `workspace` key | N/A | Always visible |
| Item `workspace` all keys match | N/A | Visible |
| Item `workspace` any key missing from workspace | N/A | Excluded |
| Item `workspace` any value mismatches | N/A | Excluded |

---

### 3.5 Filter Integration Points

#### Integration Point 1: NavigationLayout

**File**: [`src/Components/NavigationLayout.php`](src/Components/NavigationLayout.php), lines 154-161

```php
// Apply workspace filtering: remove groups gated by feature flags
// and items constrained by workspace context (role, department, etc.)
$workspaceResolver = app(WorkspaceResolver::class);
$workspaceFilter = new WorkspaceFilter($workspaceResolver->resolve());
$this->contextGroups = $workspaceFilter->filterContextGroups($this->contextGroups);
foreach ($this->contextItems as $group => &$items) {
    $items = $workspaceFilter->filterContextItems($items);
}
```

This sits **after** raw config loading and **before** `filterVisibleItems()` — ensuring workspace filtering is the first structural pass. The workspace is resolved once via the container and the `WorkspaceFilter` instance is created inline (lightweight, no service provider binding needed).

#### Integration Point 2: NavigationManager

**File**: [`src/Services/Navigation/NavigationManager.php`](src/Services/Navigation/NavigationManager.php), lines 365-369

```php
// Apply workspace filtering to remove items gated by
// workspace constraints (role, department_type, etc.)
$workspaceResolver = app(WorkspaceResolver::class);
$workspaceFilter = new WorkspaceFilter($workspaceResolver->resolve());
$allItems = array_values($workspaceFilter->filterContextItems($allItems));
```

This is in [`loadModuleNavItems()`](src/Services/Navigation/NavigationManager.php:314) — the method that flattens all context items from a module's navigation config. Workspace filtering is applied **after** flattening but **before** normalization completes, so excluded items don't participate in key generation or ordering. `array_values()` re-indexes the array after `array_filter()` preserves keys.

#### Integration Point 3: UILibraryServiceProvider

**File**: [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php), lines 69-73

```php
// Workspace context resolver (multi-tenant / role-based navigation filtering)
$this->app->singleton(
    \QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver::class,
    \QuickerFaster\UILibrary\Services\Navigation\NullWorkspaceResolver::class
);
```

Bound as a **singleton** — the resolver is instantiated once per request lifecycle, and `resolve()` is called at most once (when the first `NavigationLayout` or `NavigationManager` needs the workspace). This avoids redundant resolution across multiple components.

---

### 3.6 Consuming App: Custom Resolver Binding

The library ships with `NullWorkspaceResolver` which applies no filtering. Consuming applications implement the [`WorkspaceResolver`](src/Contracts/Navigation/WorkspaceResolver.php) contract and bind it in their `AppServiceProvider`:

```php
// In app/Providers/AppServiceProvider.php

use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;
use App\Services\Navigation\HrWorkspaceResolver;

public function register(): void
{
    $this->app->singleton(WorkspaceResolver::class, HrWorkspaceResolver::class);
}
```

**Example Quick-HR implementation**:

```php
namespace App\Services\Navigation;

use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;

class HrWorkspaceResolver implements WorkspaceResolver
{
    public function resolve(): array
    {
        $companyId = session('active_company_id');

        return [
            'company_id'      => $companyId,
            'features'        => $this->getCompanyFeatures($companyId),
            'role'            => auth()->user()?->getActiveRole(),
            'department_id'   => session('active_department_id'),
            'department_type' => $this->getDepartmentType(),
            'payroll_state'   => $this->getPayrollState(),
        ];
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->resolve()['features'] ?? [], true);
    }

    protected function getCompanyFeatures(?int $companyId): array
    {
        if (!$companyId) return [];
        return \App\Models\Company::find($companyId)
            ?->enabledFeatures()
            ->pluck('slug')
            ->toArray() ?? [];
    }

    protected function getDepartmentType(): ?string
    {
        $deptId = session('active_department_id');
        return \App\Models\Department::find($deptId)?->type;
    }

    protected function getPayrollState(): string
    {
        return \App\Models\PayrollPeriod::isActive() ? 'active' : 'idle';
    }
}
```

**Config changes needed in HR navigation.php**:

1. Add `feature` keys to gated `context_groups` (e.g., `'feature' => 'payroll'`, `'feature' => 'time_attendance'`)
2. Add `workspace` constraint arrays to scoped `contexts` items (e.g., `'workspace' => ['role' => 'payroll_admin']`)
3. Add department-scoped report items with `workspace` constraints
4. Add payroll-state-dependent items with `workspace` constraints

---

### 3.7 Performance Characteristics

| Concern | Characteristic |
|---|---|
| **Config file loading** | `require $configPath` — Laravel caches via opcache. Static PHP array, sub-millisecond to load |
| **Workspace resolution** | `WorkspaceResolver::resolve()` called at most once per request. Result reused in both `NavigationLayout` and `NavigationManager` |
| **Filtering overhead** | `WorkspaceFilter` uses native `array_filter` with simple string/int comparisons. O(n) where n = number of items (typically < 100). Negligible |
| **Database queries** | **Zero** — all filtering is in-memory on a static config array. No per-item DB calls |
| **Session lookups** | `company_id`, `department_id` from session — already loaded by Laravel's session middleware |
| **Memory** | Workspace map is a small associative array (5-6 keys). `WorkspaceFilter` instance is lightweight |
| **Backward compatibility** | With `NullWorkspaceResolver` (default), both `filterContextGroups()` and `filterContextItems()` short-circuit on `empty($this->workspace)` — zero overhead beyond the `empty()` check |

**No new database tables, no cache keys, no file I/O beyond the existing config `require`** — the entire workspace filtering system operates purely in PHP memory on already-loaded data.

---

## 4. Sidebar Customization Config Keys

### 4.1 The `sidebar` Config Section

Navigation config files (`navigation.php`) can now include a `sidebar` key to control sidebar grouping behavior. This key is read by [`Sidebar::buildModuleSections()`](src/Http/Livewire/Layouts/Navs/Sidebar.php) and [`MenuRenderer`](src/Http/Livewire/Layouts/Navs/MenuRenderer.php) to determine how context items are grouped and rendered.

```php
// In any module's Config/navigation.php:
return [
    'context_groups' => [ /* ... */ ],
    'contexts' => [ /* ... */ ],
    'sidebar' => [
        'section_label'   => 'My Custom Section',  // Custom header label
        'collapsible'     => true,                  // Enable collapse toggle
        'expanded_default' => true,                  // Sections start expanded
    ],
    'layout' => [ /* ... */ ],
];
```

### 4.2 Three Rendering Modes

The sidebar supports three distinct rendering modes, resolved in priority order:

| Priority | Mode | Data Source | When Active |
|---|---|---|---|
| 1 | **Context-driven** | `$items` from [`NavigationLayout`](src/Components/NavigationLayout.php) (active context group's items) | When `$this->items` is non-empty AND `$this->activeContext` is set |
| 2 | **NavigationManager sections** | [`NavigationManager::getSections()`](src/Services/Navigation/NavigationManager.php) | Phase 4.5 config-driven path — sections from `navigation.php` sidebar config |
| 3 | **Module registry fallback** | [`Sidebar::buildModuleSections()`](src/Http/Livewire/Layouts/Navs/Sidebar.php) → module registry | Phase 4.3 legacy — builds sections from `config('ui-library.modules')` |

**Mode 1 (Context-driven)** enables the Quick-HR pattern: clicking a top nav tab (e.g., "People") shows only that context group's sidebar items (e.g., Employees, Profiles, Teams). The `$activeContext` parameter was added to [`Sidebar::mount()`](src/Http/Livewire/Layouts/Navs/Sidebar.php:52) and passed from [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php).

### 4.3 Config Schema Reference

| Key | Type | Default | Description |
|---|---|---|---|
| `section_label` | `string\|null` | `null` | Custom label for the section header. Falls back to module/context name if null. |
| `collapsible` | `bool` | `true` | Whether section headers show a collapse/expand toggle chevron. |
| `expanded_default` | `bool` | `true` | Whether sections start in the expanded state. When `false`, section bodies are hidden on initial render. |

These keys are read from both the `sidebar` section of `navigation.php` and from the module registry's per-module `sidebar` configuration in `config('ui-library.navigation.sidebar')`.

---

## 5. Icon Mode Behavior

### 5.1 Toggle Mechanism

The sidebar supports two visual states controlled by [`Sidebar::toggleState()`](src/Http/Livewire/Layouts/Navs/Sidebar.php:286):

- **Full mode** (`icon + label`): Navigation items show both their icon and text label. Section headers display the full section label. This is the default state (`config('ui-library.navigation.sidebar.initial_state', 'full')`).
- **Icon mode** (`icon only`): Navigation items show only their icon. Hovering reveals a tooltip with the label. Section headers collapse to a compact icon representation.

State is persisted to both session (`sidebar_state`) and `localStorage`, surviving page refreshes and browser restarts.

### 5.2 Section Headers in Icon Mode

When the sidebar is in iconized mode, collapsible section headers undergo specific behavioral changes:

1. **Compact rendering**: Section header text is hidden; only the section icon (or a default `fa-folder` icon) is displayed.
2. **Indentation fix**: Iconized items within sections use smaller left padding to compensate for the narrower sidebar width, preventing items from appearing misaligned.
3. **Tooltip on hover**: The section label is shown as a Bootstrap tooltip when hovering over the iconized section header.

These behaviors are implemented in [`sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) and [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) using CSS classes gated by the `sidebar_state` session variable.

### 5.3 Expand Indicator Chevron

Collapsible sections display a chevron indicator that:
- Points **down** (`fa-chevron-down`) when the section is expanded
- Points **right** (`fa-chevron-right`) when the section is collapsed
- Is hidden entirely when `collapsible` is `false`

The chevron is implemented as a Font Awesome icon in the section header, toggled via Blade conditional on the section's collapse state. In iconized mode, the chevron is hidden to preserve space.

### 5.4 Empty Section Body Fix

A CSS bug was corrected where collapsed section bodies (`display: none`) would still render an empty `<div>` with visible padding/borders, creating a visual gap. The fix ensures that:
- When collapsed, the section body container has zero height and no visible padding
- CSS transition on collapse/expand is smooth and doesn't leave residual space
- `aria-hidden` attributes correctly reflect the collapsed state for accessibility

---

## 6. Module Switcher Bootstrap Dropdown Integration

### 6.1 Before: Livewire Component

Prior to 2026-08-11, the module/application switcher was a standalone Livewire component ([`ModuleSwitcher`](src/Http/Livewire/Layouts/Navs/ModuleSwitcher.php)) with:
- A dedicated Blade view at `src/Resources/views/livewire/navs/module-switcher.blade.php`
- 42 lines of custom JavaScript for dropdown behavior
- `navigation.switcher_style` config key (`dropdown` or `icons`)
- Livewire component lifecycle overhead for what is essentially a static dropdown

### 6.2 After: Inline Bootstrap Dropdown

The ModuleSwitcher component was **deleted** and replaced with an inline Bootstrap 5 dropdown rendered directly in the [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) template:

```blade
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button"
       data-bs-toggle="dropdown" aria-expanded="false">
        <i class="{{ $currentModuleIcon }} me-1"></i>
        {{ $currentModuleName }}
    </a>
    <ul class="dropdown-menu">
        @foreach ($userFacingModules as $module)
            <li>
                <a class="dropdown-item {{ $module['active'] ? 'active' : '' }}"
                   href="{{ url($module['route']) }}">
                    <i class="{{ $module['icon'] }} me-2"></i>
                    {{ $module['label'] }}
                    @if ($module['active'])
                        <i class="fas fa-check ms-auto"></i>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</li>
```

**Benefits**:
- Zero custom JavaScript — Bootstrap 5 handles dropdown open/close natively
- Zero Livewire overhead — no component mount, no hydration, no state management
- 42 lines of custom JS deleted
- ModuleSwitcher.php and module-switcher.blade.php deleted
- Active module shown with checkmark icon
- Only `user_facing: true` modules listed

### 6.3 TopNav determineModuleName() Fix

[`TopNav::determineModuleName()`](src/Http/Livewire/Layouts/Navs/TopNav.php) had a bug where it would **always overwrite** the `$this->moduleName` property, even when an explicit `moduleName` was passed as a Livewire prop from [`NavigationLayout`](src/Components/NavigationLayout.php). This caused the Top Nav to show the wrong module name and fail to render context groups.

**Fix**: The method now checks if `$this->moduleName` is already set (passed as a prop) before attempting to derive it from the URL. If explicitly set, it is preserved.

### 6.4 Icon Base Class Fixes

Seven `<i>` tags across top nav and sidebar Blade partials were missing the required `fa` base class for Font Awesome, causing icons to render as empty squares. The following partials were fixed:

- [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) — 3 icons
- [`top-nav-item.blade.php`](src/Resources/views/livewire/navs/partials/top-nav-item.blade.php) — 2 icons
- [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) — 1 icon
- [`sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) — 1 icon

Each was changed from e.g., `<i class="fas fa-home">` to `<i class="fas fa-home fa">` to ensure proper Font Awesome rendering.

---

## 7. Changelog

| Date | Change | Details |
|---|---|---|
| **2026-08-13** | User Profile Dropdown Menu | Config-driven `user_menu` with "My Profile", "My Account", "My Preferences" entries. Rendered as Bootstrap dropdown from user avatar in TopNav. See §9. |
| **2026-08-13** | Notification Icon | Bell icon with unread count badge added to TopNav. Configurable via `notifications.enabled`, `notifications.polling_interval`, `notifications.max_display`. See §10. |
| **2026-08-13** | Module Switcher Role-Based Config | Module switcher now filters by user roles via `roles` array in each module's config entry. Modules with empty `roles` are visible to all authenticated users. See §11. |
| **2026-08-13** | Background Jobs Role-Based Config | Dashboard background jobs widget now supports `roles` and `visible_statuses` config for role-based visibility filtering. See §12. |
| **2026-08-13** | Company Dropdown Behavior Fix | Restored correct hide/show logic; fixed company list to show companies (not users). |
| **2026-08-13** | Config Consolidation | Removed `quicker-faster-ui.php`; all config keys merged into `ui-library.php` as single source of truth. |
| **2026-08-12** | Cross-Context Dropdowns (Phase 2) integrated | `show_all_contexts` and `hide_topnav_contexts` config keys documented. HorizontalContextMenu now supports rendering all context groups as dropdown triggers. See [`docs/navigation-cross-context-dropdowns.md`](docs/navigation-cross-context-dropdowns.md). |
| **2026-08-12** | Sidebar URL generation fix | Module name duplication bug resolved — route values are no longer double-prefixed. |
| **2026-08-12** | Toggle button config priority fix | `allow_switch` now correctly gates the sidebar/horizontal toggle before session state is consulted. |
| **2026-08-12** | Icon `fa` prefix fix | 7 `<i>` tags across top nav and sidebar partials corrected to include the `fa` base class. |
| **2026-08-12** | Company switcher fix | Role gate removed; `show_company_switcher` is now purely config-driven. Default selection logic added. |
| **2026-08-12** | PHP 8.4 nullable property fix | Explicit `= null` defaults on all nullable typed properties in Livewire nav components. |
| **2026-08-12** | Config-doc alignment | 5 missing config keys added to Admin, System, and Organization navigation configs. |
| **2026-08-11** | Module Switcher → Bootstrap dropdown | `ModuleSwitcher` Livewire component deleted; replaced with inline Bootstrap 5 dropdown in `TopNav`. |
| **2026-08-11** | Sidebar `activeContext` linkage | `Sidebar::mount()` accepts `$activeContext`, enabling context-driven sidebar rendering. |
| **2026-08-11** | Workspace parameter support | `WorkspaceResolver` contract + `WorkspaceFilter` service + `NullWorkspaceResolver` default. |
| **2026-08-11** | Sidebar customization | `sidebar` config key with `section_label`, `collapsible`, `expanded_default`. Three rendering modes. |
| **2026-08-11** | Icon mode complete | Section headers collapse to compact icons in iconized mode. Chevron expand indicator added. |
| **2026-08-11** | `determineModuleName()` fix | TopNav no longer overwrites explicitly passed `moduleName` prop. |

## 8. Cross-References

| Document | Relationship |
|---|---|
| [`docs/architecture/06-navigation-system.md`](docs/architecture/06-navigation-system.md) | Planned topic file for navigation architecture (currently deferred; content lives in blueprint) |
| [`docs/architecture/08-contracts-and-interfaces.md`](docs/architecture/08-contracts-and-interfaces.md) | Documents all contracts including `WorkspaceResolver` |
| [`plans/workspace-parameter-analysis.md`](plans/workspace-parameter-analysis.md) | Full analysis: 5 scenarios, options A-D, implementation approach |
| [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) | Monolithic blueprint — sections 4.1, 7.5 cover navigation |
| [`docs/implementation-plan.md`](docs/implementation-plan.md) | Phased implementation roadmap |
| [`docs/context-groups-navigation-analysis.md`](docs/context-groups-navigation-analysis.md) | Earlier analysis of context groups navigation pattern |

**Source files referenced**:

| File | Lines |
|---|---|
| HR [`navigation.php`](../../LaravelProjects/quick-hr/app/Modules/Hr/Config/navigation.php) | 1-514 |
| [`NavigationLayout.php`](src/Components/NavigationLayout.php) | 1-333 |
| [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) | 1-193 |
| [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php) | 1-325 |
| [`NavigationFilter.php`](src/Traits/NavigationFilter.php) | 1-55 |
| [`WorkspaceResolver.php`](src/Contracts/Navigation/WorkspaceResolver.php) | 1-29 |
| [`NullWorkspaceResolver.php`](src/Services/Navigation/NullWorkspaceResolver.php) | 1-29 |
| [`WorkspaceFilter.php`](src/Services/Navigation/WorkspaceFilter.php) | 1-107 |
| [`WorkspaceServiceProvider.php`](src/Providers/WorkspaceServiceProvider.php) | 1-29 |
| [`NavigationManager.php`](src/Services/Navigation/NavigationManager.php) | 1-581 |
| [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | 1-359 |

---

## 9. User Profile Dropdown Menu

### 9.1 Overview

The TopNav user profile area now renders a config-driven Bootstrap dropdown menu when the user avatar is clicked. The menu is defined in [`ui-library.php`](src/Config/ui-library.php) under the `user_menu` key.

### 9.2 Config Schema

```php
// In config/ui-library.php
'user_menu' => [
    [
        'label'      => 'My Profile',
        'icon'       => 'fas fa-user',
        'route'      => 'profile.show',
        'permission' => null,  // null = always visible
    ],
    [
        'label'      => 'My Account',
        'icon'       => 'fas fa-cog',
        'route'      => 'profile.account',
        'permission' => null,
    ],
    [
        'label'      => 'My Preferences',
        'icon'       => 'fas fa-sliders-h',
        'route'      => 'profile.preferences',
        'permission' => null,
    ],
],
```

### 9.3 Menu Item Keys

| Key | Type | Required | Description |
|---|---|---|---|
| `label` | `string` | Yes | Display text for the menu item |
| `icon` | `string` | No | Font Awesome icon class (e.g., `fas fa-user`) |
| `route` | `string` | No | Named route or URL path. If omitted, `url` is used. |
| `url` | `string` | No | Direct URL fallback when `route` is not set |
| `permission` | `string\|null` | No | Spatie permission required to see this item. `null` = always visible. |

### 9.4 Rendering

The menu is rendered in [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) as a Bootstrap 5 dropdown:

```blade
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
       role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="{{ auth()->user()->profile_photo_url }}"
             class="avatar avatar-sm rounded-circle me-2"
             alt="{{ auth()->user()->name }}">
        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach ($userMenuItems as $item)
            <li>
                <a class="dropdown-item" href="{{ $item['route'] ?? url($item['url']) }}">
                    @if (!empty($item['icon']))
                        <i class="{{ $item['icon'] }} me-2"></i>
                    @endif
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</li>
```

### 9.5 Permission Filtering

Menu items with a `permission` key are filtered via `AuthorizationService::canAccessView()`. Items without a `permission` key (or with `null`) are always visible. This allows role-specific menu items:

```php
'user_menu' => [
    ['label' => 'My Profile', 'route' => 'profile.show'],
    ['label' => 'Admin Panel', 'route' => 'admin.dashboard', 'permission' => 'access_admin'],
],
```

---

## 10. Notification Icon

### 10.1 Overview

A notification bell icon has been added to the TopNav, positioned between the company switcher and the user profile dropdown. It displays an unread count badge and opens a dropdown with recent notifications.

### 10.2 Config Schema

```php
// In config/ui-library.php
'notifications' => [
    'enabled'           => true,
    'polling_interval'  => 30,    // Livewire polling in seconds
    'max_display'       => 99,    // Maximum unread count before "99+"
],
```

### 10.3 Config Keys

| Key | Type | Default | Description |
|---|---|---|---|
| `enabled` | `bool` | `true` | Show/hide the notification bell icon entirely |
| `polling_interval` | `int` | `30` | Seconds between Livewire polling for new notifications |
| `max_display` | `int` | `99` | Maximum number shown in badge; excess displays as "99+" |

### 10.4 Rendering

The notification icon is rendered in [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php):

```blade
@if (config('ui-library.notifications.enabled', true))
    <li class="nav-item dropdown" wire:poll.{{ $pollingInterval }}s>
        <a class="nav-link position-relative" href="#" role="button"
           data-bs-toggle="dropdown" aria-expanded="false"
           aria-label="{{ __('Notifications') }}">
            <i class="fas fa-bell"></i>
            @if ($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size: 0.6rem;">
                    {{ $unreadCount > $maxDisplay ? $maxDisplay . '+' : $unreadCount }}
                </span>
            @endif
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 320px;">
            {{-- Recent notifications rendered here --}}
        </ul>
    </li>
@endif
```

### 10.5 Behavior

- **Polling**: The component polls every `polling_interval` seconds via `wire:poll`
- **Badge**: Shows unread count; displays "99+" when count exceeds `max_display`
- **Dropdown**: Lists recent notifications with timestamps and read/unread styling
- **Disabled**: When `enabled: false`, the icon is completely hidden

---

## 11. Module Switcher Role-Based Configuration

### 11.1 Overview

The module switcher dropdown now supports role-based filtering. Each module entry in [`ui-library.php`](src/Config/ui-library.php) can specify a `roles` array controlling which users see the module in the switcher.

### 11.2 Config Schema

```php
// In config/ui-library.php
'modules' => [
    'admin' => [
        'enabled'     => true,
        'label'       => 'Administration',
        'icon'        => 'fa-shield-haltered',
        'route'       => 'admin.dashboard',
        'order'       => 900,
        'roles'       => ['super_admin'],   // Only super_admins see this
        'core'        => true,
        'user_facing' => true,
    ],
    'hr' => [
        'enabled'     => true,
        'label'       => 'HR',
        'icon'        => 'fa-users',
        'route'       => 'hr.dashboard',
        'order'       => 100,
        'roles'       => [],                // Empty = visible to all authenticated users
        'core'        => false,
        'user_facing' => true,
    ],
],
```

### 11.3 Filtering Logic

In [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php), the module list is filtered before rendering:

1. Modules with `user_facing: false` are always excluded from the switcher
2. Modules with an empty `roles` array are visible to all authenticated users
3. Modules with a non-empty `roles` array are only visible to users who have at least one of the specified roles
4. The active module is always shown regardless of role (user is already in it)

### 11.4 Behavior

- **Super admin**: Sees all modules (Admin, System, Organization, HR, Payroll, etc.)
- **HR manager**: Sees only HR, Leave, Time modules (as configured)
- **Employee**: Sees only employee-facing modules
- **Active module**: Always visible in the switcher (user is currently using it)

---

## 12. Background Jobs Role-Based Configuration

### 12.1 Overview

The dashboard background jobs widget now supports role-based visibility configuration. This controls who can see job statuses and which statuses are displayed.

### 12.2 Config Schema

```php
// In config/ui-library.php
'background_jobs' => [
    'enabled'          => true,
    'roles'            => ['super_admin', 'admin'],  // Who can view job statuses
    'visible_statuses' => ['queued', 'processing', 'completed', 'failed'],
],
```

### 12.3 Config Keys

| Key | Type | Default | Description |
|---|---|---|---|
| `enabled` | `bool` | `true` | Show/hide the background jobs widget entirely |
| `roles` | `array` | `['super_admin', 'admin']` | Roles allowed to view job statuses |
| `visible_statuses` | `array` | `['queued', 'processing', 'completed', 'failed']` | Which job statuses to display |

### 12.4 Behavior

- **Role check**: Users without a matching role see a "not authorized" message instead of job data
- **Status filtering**: Only jobs with statuses in `visible_statuses` are shown
- **Disabled**: When `enabled: false`, the widget is hidden from all dashboards
- **Empty roles**: An empty `roles` array means all authenticated users can view job statuses
