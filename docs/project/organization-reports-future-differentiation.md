# Organization Reports — Future Differentiation Plan

> **Created**: 2026-08-19
> **Status**: Placeholder — awaiting implementation
> **Module**: Organization (`app/Modules/Organization/`)
> **Repository**: hr-consuming-app

---

## 1. Current State: Minimal Placeholder Pages

The Organization module's **Reports** context group has 5 sidebar links. The **Overview** page renders the full hub dashboard; the **4 report-specific pages** are minimal placeholder pages (matching the library's audit-view pattern) with per-page headings and future-implementation descriptions:

| Sidebar Link | Route | Blade View | Content |
|---|---|---|---|
| Overview | `/organization/dashboard-reports-overview` | [`dashboard-reports-overview.blade.php`](app/Modules/Organization/Resources/views/organization/dashboard-reports-overview.blade.php) | Full hub dashboard (`dashboard_reports_overview`) |
| Company Reports | `/organization/reports/companies` | [`reports-companies.blade.php`](app/Modules/Organization/Resources/views/organization/reports-companies.blade.php) | Minimal placeholder |
| Department Reports | `/organization/reports/departments` | [`reports-departments.blade.php`](app/Modules/Organization/Resources/views/organization/reports-departments.blade.php) | Minimal placeholder |
| Location Reports | `/organization/reports/locations` | [`reports-locations.blade.php`](app/Modules/Organization/Resources/views/organization/reports-locations.blade.php) | Minimal placeholder |
| Growth Reports | `/organization/reports/growth` | [`reports-growth.blade.php`](app/Modules/Organization/Resources/views/organization/reports-growth.blade.php) | Minimal placeholder |

The 4 report-specific Blade views are **minimal placeholder pages** — each renders a per-page heading (Company Reports, Department Reports, Location Reports, Growth Reports) and a short future-implementation description. They do **not** reference the `dashboard_reports_overview` config or render dashboard widgets.

The shared hub dashboard config ([`dashboard_reports_overview.php`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php)) is used only by the **Overview** page and contains:

- **Stat widgets**: Total Companies, Total Departments, Total Locations
- **Action cards**: Links to Company Reports, Department Reports, Location Reports, Growth Reports
- **List widgets**: Recent Companies (latest 5), Recent Locations (latest 5)

This was deployed as a **temporary measure** to fix 404 errors — previously these routes returned 404 because the views didn't exist. The views were created as placeholders to get the pages rendering, but each page is **meant to present different data** to the user.

---

## 2. Intended Future State: Domain-Specific Reports

Each of the 4 report pages should present **domain-appropriate data and widgets** relevant to its subject:

### 2.1 Company Reports (`/organization/reports/companies`)

**Purpose**: Reports and analytics scoped to companies.

**Suggested widgets**:
- Company count by status (active/inactive) — `stat` or `chart` widget
- Companies by type/industry — `chart` (pie/donut) widget
- Companies created over time — `trend` widget
- Top companies by employee count — `list` widget
- Company geographic distribution — `chart` widget

### 2.2 Department Reports (`/organization/reports/departments`)

**Purpose**: Reports and analytics scoped to departments.

**Suggested widgets**:
- Department count — `stat` widget
- Departments per company — `chart` (bar) widget
- Department hierarchy depth analysis — `stat` or `metric` widget
- Departments with most sub-departments — `list` widget
- Recently created/updated departments — `list` widget

### 2.3 Location Reports (`/organization/reports/locations`)

**Purpose**: Reports and analytics scoped to locations.

**Suggested widgets**:
- Location count by country/region — `chart` (map or bar) widget
- Locations per company — `chart` widget
- Active vs. inactive locations — `stat` widget
- Recently added locations — `list` widget
- Location type distribution (office, remote, warehouse, etc.) — `chart` widget

### 2.4 Growth Reports (`/organization/reports/growth`)

**Purpose**: Trend and growth analytics across the organization over time.

**Suggested widgets**:
- Company creation trend (monthly/quarterly) — `trend` widget
- Department creation trend — `trend` widget
- Location expansion trend — `trend` widget
- Headcount growth overlay — `chart` widget
- Year-over-year comparison metrics — `metric` or `stat` widgets

---

## 3. Implementation Plan

### 3.1 Create Individual Dashboard Configs

Each report page needs its own dashboard config file under [`app/Modules/Organization/Data/dashboards/`](app/Modules/Organization/Data/dashboards/):

| Config File | Config Key (dot notation) |
|---|---|
| `dashboard_reports_companies.php` | `organization.dashboards.dashboard_reports_companies` |
| `dashboard_reports_departments.php` | `organization.dashboards.dashboard_reports_departments` |
| `dashboard_reports_locations.php` | `organization.dashboards.dashboard_reports_locations` |
| `dashboard_reports_growth.php` | `organization.dashboards.dashboard_reports_growth` |

Each config follows the standard dashboard config schema (see [`module-structure.md`](../consuming-app/module-structure.md) section 1.3 for naming conventions). The existing [`dashboard_reports_overview.php`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php) can serve as a structural reference.

### 3.2 Update Blade Views

Each of the 4 report Blade views is currently a minimal placeholder (no dashboard config). In the future, each must be converted to a full dashboard by adding a `<livewire:qf.dashboard>` reference with its own config key:

| Blade View | Current state | Future `configKey` |
|---|---|---|
| [`reports-companies.blade.php`](app/Modules/Organization/Resources/views/organization/reports-companies.blade.php) | Minimal placeholder | `organization.dashboards.dashboard_reports_companies` |
| [`reports-departments.blade.php`](app/Modules/Organization/Resources/views/organization/reports-departments.blade.php) | Minimal placeholder | `organization.dashboards.dashboard_reports_departments` |
| [`reports-locations.blade.php`](app/Modules/Organization/Resources/views/organization/reports-locations.blade.php) | Minimal placeholder | `organization.dashboards.dashboard_reports_locations` |
| [`reports-growth.blade.php`](app/Modules/Organization/Resources/views/organization/reports-growth.blade.php) | Minimal placeholder | `organization.dashboards.dashboard_reports_growth` |

Each view must add the `<x-qf::navigation-layout configKey="...">` attribute and the `<livewire:qf.dashboard config-key="...">` attribute with its own config key.

### 3.3 No Route Changes Required

The routes in [`web.php`](app/Modules/Organization/Routes/web.php) (lines 35–49) already point to the correct views and do not need modification:

```php
Route::get('/organization/reports/companies', function () {
    return view('organization::organization.reports-companies');
})->name('organization.reports.companies');
// ... (departments, locations, growth follow the same pattern)
```

### 3.4 No Navigation Config Changes Required

The navigation config in [`navigation.php`](app/Modules/Organization/Config/navigation.php) (lines 222–268) already defines the correct routes, labels, icons, and permissions for each report link. No changes needed.

---

## 4. Relevant Files Reference

| File | Path (relative to consuming app root) | Role |
|---|---|---|
| Routes | [`app/Modules/Organization/Routes/web.php`](app/Modules/Organization/Routes/web.php) (lines 35–49) | Defines the 4 report routes |
| Blade Views | [`app/Modules/Organization/Resources/views/organization/reports-*.blade.php`](app/Modules/Organization/Resources/views/organization/) | 4 minimal placeholder pages (per-page heading + description) |
| Dashboard Config | [`app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php`](app/Modules/Organization/Data/dashboards/dashboard_reports_overview.php) | Shared hub config (template for new configs) |
| Nav Config | [`app/Modules/Organization/Config/navigation.php`](app/Modules/Organization/Config/navigation.php) (lines 222–268) | Reports context group with 5 sidebar items |

---

## 5. Related Backlog Items

- [Navigation UX Backlog](navigation-ux-backlog.md) — P2 item: *"Fix Organization: entities without models/routes … remove or stub"*
- The Overview page (`dashboard-reports-overview`) should continue to serve as the hub/landing page for the Reports context group, with action cards linking to each specialized report page.