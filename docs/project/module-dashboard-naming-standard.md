# Module General Dashboard Naming Standard

**Status:** Proposed  
**Date:** 2026-08-19  
**Scope:** General (main) dashboard titles across all modules

---

## 1. Current Titles — Full Survey

Each module's general dashboard is defined in a PHP config file that returns an array with a `title` key. The Blade view for every module passes `'title' => ['enabled' => false]` in the [`NavigationLayout`](src/Components/NavigationLayout.php) overrides, meaning the page heading is rendered by the dashboard Livewire component using the config `title` field — not by the Blade wrapper.

### Library Modules (UI Library)

| # | Module | Current Title | Config File |
|---|--------|--------------|-------------|
| 1 | Admin | **"User & Permission Overview"** | [`src/Core/Admin/Data/dashboard.php`](src/Core/Admin/Data/dashboard.php:4) |
| 2 | System | **"System Dashboard"** | [`src/Core/System/Data/dashboard.php`](src/Core/System/Data/dashboard.php:4) |

### Consuming App Modules (HR App)

| # | Module | Current Title | Config File |
|---|--------|--------------|-------------|
| 3 | Organization | **"Organization Overview"** | [`app/Modules/Organization/Data/dashboard.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Organization/Data/dashboard.php:4) |
| 4 | HR | **"HR Executive Dashboard"** | [`app/Modules/Hr/Data/dashboards/dashboard.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard.php:4) |
| 5 | Attendance | **"Attendance Dashboard"** | [`app/Modules/Attendance/Data/dashboards/dashboard.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Attendance/Data/dashboards/dashboard.php:4) |
| 6 | Leave | **"Leave Management Dashboard"** | [`app/Modules/Leave/Data/dashboards/dashboard.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/dashboards/dashboard.php:4) |
| 7 | Payroll | **"Payroll Dashboard"** | [`app/Modules/Payroll/Data/dashboards/dashboard.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Payroll/Data/dashboards/dashboard.php:4) |
| 8 | Holiday | **"Holiday Dashboard"** | [`app/Modules/Holiday/Data/dashboards/dashboard.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Holiday/Data/dashboards/dashboard.php:4) |

### Blade View Confirmation

All eight Blade views follow the same pattern — `title` is disabled in overrides, so the dashboard config `title` is the sole source of the page heading:

| Module | Blade View | `title` Override |
|--------|-----------|-----------------|
| Admin | [`src/Core/Admin/Resources/views/admin/dashboard.blade.php`](src/Core/Admin/Resources/views/admin/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| System | [`src/Core/System/Resources/views/system/dashboard.blade.php`](src/Core/System/Resources/views/system/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| Organization | [`app/Modules/Organization/Resources/views/organization/dashboard.blade.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Organization/Resources/views/organization/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| HR | [`app/Modules/Hr/Resources/views/dashboard.blade.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| Attendance | [`app/Modules/Attendance/Resources/views/dashboard.blade.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Attendance/Resources/views/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| Leave | [`app/Modules/Leave/Resources/views/dashboard.blade.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Leave/Resources/views/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| Payroll | [`app/Modules/Payroll/Resources/views/dashboard.blade.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Payroll/Resources/views/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |
| Holiday | [`app/Modules/Holiday/Resources/views/dashboard.blade.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Holiday/Resources/views/dashboard.blade.php:8) | `'title' => ['enabled' => false]` |

---

## 2. Analysis of Inconsistencies

Five of eight modules (62.5%) already use the `"{Module} Dashboard"` pattern. The three outliers plus one legacy issue:

| Module | Current Title | Problem |
|--------|--------------|---------|
| **Admin** | "User & Permission Overview" | Legacy name from the old "User & Permission" context group before the Admin/System split. Does not identify the module. |
| **Organization** | "Organization Overview" | Uses "Overview" instead of "Dashboard". Inconsistent with the majority. |
| **HR** | "HR Executive Dashboard" | Extra "Executive" qualifier adds unnecessary specificity. The dashboard serves all HR users, not just executives. |
| **Leave** | "Leave Management Dashboard" | Extra "Management" qualifier is redundant — all module dashboards are for management. |

---

## 3. Proposed Standard

### Convention: `"{Module} Dashboard"`

Every module's general dashboard title SHALL follow the format:

> **`"{Module Name} Dashboard"`**

Where `{Module Name}` is the module's display name (typically the studly-case module directory name).

### Rationale

1. **Majority rule** — 5 of 8 modules (System, Attendance, Payroll, Holiday, and implicitly Admin after fix) already follow this pattern. Standardizing to the majority minimizes churn.

2. **Clarity** — The format immediately answers two questions: "Which module am I in?" and "What kind of page is this?" (a dashboard).

3. **Scalability** — New modules have a clear, predictable naming rule with zero ambiguity.

4. **Distinction from context-group overviews** — Context-group overview dashboards (e.g., "User & Permission Overview", "Payroll Overview") use "Overview" to indicate they aggregate across sub-contexts. The general dashboard uses "Dashboard" to indicate it's the module's primary landing page. This creates a useful semantic distinction:
   - **General dashboard** → `"{Module} Dashboard"` (module landing page)
   - **Context-group overview** → `"{Context Group} Overview"` (cross-context aggregation)

5. **No unnecessary qualifiers** — Words like "Executive", "Management", or "Admin" in the title are redundant because:
   - The module name already conveys the domain (HR, Leave, Payroll)
   - The sidebar/navigation already provides context about which module the user is in
   - Extra words create inconsistency and set a precedent for future modules to add their own qualifiers

### Rejected Alternatives

| Alternative | Why Rejected |
|-------------|-------------|
| `"{Module} Overview"` | Conflicts with context-group overview dashboards. "Overview" implies aggregation across sub-contexts, not a module landing page. |
| `"{Module} Management Dashboard"` | Redundant — all dashboards are for management. Adds noise. |
| Keep existing names as-is | Perpetuates inconsistency. "User & Permission Overview" is factually wrong for the Admin module. |

---

## 4. Modules Requiring Changes

Only the `title` field in each dashboard config file needs to be changed. No Blade views, routes, or other files are affected.

| # | Module | Current Title | → | New Title | Config File to Edit |
|---|--------|--------------|---|-----------|---------------------|
| 1 | **Admin** | "User & Permission Overview" | → | "Admin Dashboard" | [`src/Core/Admin/Data/dashboard.php`](src/Core/Admin/Data/dashboard.php:4) |
| 2 | **Organization** | "Organization Overview" | → | "Organization Dashboard" | `app/Modules/Organization/Data/dashboard.php:4` |
| 3 | **HR** | "HR Executive Dashboard" | → | "HR Dashboard" | `app/Modules/Hr/Data/dashboards/dashboard.php:4` |
| 4 | **Leave** | "Leave Management Dashboard" | → | "Leave Dashboard" | `app/Modules/Leave/Data/dashboards/dashboard.php:4` |

### Already Compliant (No Changes Needed)

| Module | Current Title |
|--------|--------------|
| System | "System Dashboard" |
| Attendance | "Attendance Dashboard" |
| Payroll | "Payroll Dashboard" |
| Holiday | "Holiday Dashboard" |

---

## 5. Implementation Steps

Each change is a single-line edit to the `title` value in the dashboard config file.

### Step 1 — Admin (Library)

**File:** [`src/Core/Admin/Data/dashboard.php`](src/Core/Admin/Data/dashboard.php:4)

```diff
- 'title' => 'User & Permission Overview',
+ 'title' => 'Admin Dashboard',
```

### Step 2 — Organization (Consuming App)

**File:** `app/Modules/Organization/Data/dashboard.php` (line 4)

```diff
- 'title' => 'Organization Overview',
+ 'title' => 'Organization Dashboard',
```

### Step 3 — HR (Consuming App)

**File:** `app/Modules/Hr/Data/dashboards/dashboard.php` (line 4)

```diff
- 'title' => 'HR Executive Dashboard',
+ 'title' => 'HR Dashboard',
```

### Step 4 — Leave (Consuming App)

**File:** `app/Modules/Leave/Data/dashboards/dashboard.php` (line 4)

```diff
- 'title' => 'Leave Management Dashboard',
+ 'title' => 'Leave Dashboard',
```

### Verification

After making changes, verify each module's general dashboard page displays the new title:

| Module | URL |
|--------|-----|
| Admin | `/admin/dashboard` |
| System | `/system/dashboard` |
| Organization | `/organization/dashboard` |
| HR | `/hr/dashboard` |
| Attendance | `/attendance/dashboard` |
| Leave | `/leave/dashboard` |
| Payroll | `/payroll/dashboard` |
| Holiday | `/holiday/dashboard` |

---

## 6. Final State

After all changes are applied, every module's general dashboard title will follow the `"{Module} Dashboard"` convention:

| Module | Title |
|--------|-------|
| Admin | Admin Dashboard |
| System | System Dashboard |
| Organization | Organization Dashboard |
| HR | HR Dashboard |
| Attendance | Attendance Dashboard |
| Leave | Leave Dashboard |
| Payroll | Payroll Dashboard |
| Holiday | Holiday Dashboard |