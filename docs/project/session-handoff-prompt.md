# Session Handoff Prompt

Copy the entire fenced block below and paste it into a new AI session. It is self-contained and orients the new session on the architecture, current state, immediate next tasks, and working conventions.

```markdown
You are continuing work on a two-repository Laravel project: a domain-independent UI library ("QuickFaster UI Library") and a consuming HR application built on top of it.

---

## Project Overview

### Two Repositories

| Role | Path | Description |
|------|------|-------------|
| **Library** | `/Users/mac/Projects/Libraries/ui-library` | Domain-independent UI framework. Provides navigation, dashboards, data tables, Livewire components, widget processors, quick actions, workflows, notifications, and multi-tenancy scoping. Must NOT contain any domain-specific (HR, Payroll, etc.) code. |
| **Consuming App** | `/Users/mac/Projects/LaravelProjects/hr-consuming-app` | HR application with 6 business modules (Organization, HR, Attendance, Leave, Payroll, Holiday) built on top of the library. Each module is self-contained with its own migrations, models, routes, views, configs, dashboards, and service provider. |

### Library Purpose
The library provides generic infrastructure only — navigation rendering, dashboard widget grids, data table CRUD, form wizards, workflow/approval engine, notification dispatch, quick actions command palette, multi-tenancy scoping, and module auto-discovery. All business logic and domain-specific models live in the consuming app's modules.

---

## Architecture Rules

### 1. Library Is Domain-Independent
- The library must never reference `App\Modules\*` paths, HR-specific models, or any business domain.
- Domain-specific features go in the consuming app's `app/Modules/{Module}/` directories.
- The library provides contracts/interfaces; the consuming app binds implementations via service providers.
- Run `bash scripts/check-domain-independence.sh` from the library root to verify no domain leakage.

### 2. Consuming App Modules Are Self-Contained
Each module under `app/Modules/{Module}/` follows this structure:
```
app/Modules/{Module}/
├── Config/
│   ├── navigation.php       # Context groups + sidebar items
│   ├── quick-actions.php    # Cmd+K actions
│   └── permissions.php      # Permission overrides
├── Data/
│   ├── {entity}.php         # Data table configs (form, table, detail)
│   └── dashboards/
│       └── dashboard_*.php  # Dashboard widget configs
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Http/Livewire/           # Module-specific Livewire components
├── Models/
├── Providers/
│   └── {Module}ServiceProvider.php
├── Resources/views/{module}/
└── Routes/web.php
```

### 3. Operational vs Configuration Context Group Split
Each module's sidebar is split into context groups:
- **Operational** (daily use): e.g., HR → People (Employees, Profiles, Teams, Employee Groups)
- **Configuration** (occasional/rare): e.g., HR → Manage (Job Titles, Tags, Job History, Documents)
- Groups are defined in `navigation.php` with `context_groups` containing `label`, `context`, `icon`, `order`, `roles`, and `items`.

### 4. Dashboard Config Patterns
- Dashboard configs are PHP arrays in `Data/dashboards/dashboard_*.php` returning `['widgets' => [...], 'roles' => [...], 'layout' => [...]]`.
- Widget types include: `stat`, `chart`, `trend`, `list`, `grouped_list`, `action_card`, `activity_log`, `quick_actions`, `team_whos_out`, `profile_header`.
- Dashboard naming convention: `dashboard_{module}.php` for general dashboards, `dashboard_{context}_overview.php` for per-context-group overviews.
- Blade views use `<x-qf::navigation-layout context="...">` and `<livewire:qf.dashboard config-key="...">`.

### 5. Navigation Patterns
- Navigation configs are PHP arrays returning `['context_groups' => [...]]`.
- Each context group has: `label`, `context` (lowercase, unique within module), `icon`, `order`, optional `roles` (array of role names gating visibility).
- Each item has: `label`, `route` or `url`, `icon`, `order`, optional `permission`.
- Named routes follow `{module}.dashboard-{context}-overview` for overview pages.
- The library renders navigation via [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php) + [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) + [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php).

### 6. Drawer System
- Drawers provide slide-in panels for add/edit forms without page navigation.
- Action cards trigger drawers via `$dispatch('openDrawer', { component, params, title })`.
- Data table configs use `'crudType' => 'drawers'` to render forms in drawers instead of full pages.
- The drawer system is in [`action_card.blade.php`](src/Resources/views/widgets/action_card.blade.php) with `$dispatch` event dispatching.

### 7. Quick Actions System
- Cmd+K / Ctrl+K opens a command palette with 48 registered actions across all 8 modules.
- Actions are registered in `Config/quick-actions.php` arrays with `id`, `label`, `category`, `icon`, `type` (`navigate`/`event`/`drawer`), `route`/`url`, optional `shortcut`/`roles`.
- The library discovers actions via [`ActionRegistry`](src/Services/QuickActions/ActionRegistry.php) (3-tier priority: Core → business module → consuming app override).
- Usage tracking via [`ActionTracker`](src/Services/QuickActions/ActionTracker.php) → `user_action_histories` table.
- Personalized ranking via [`RankingEngine`](src/Services/QuickActions/RankingEngine.php): `score = 0.6 × recency_factor + 0.4 × frequency_factor`.
- Favorites/pinning via [`UserFavoriteAction`](src/Models/UserFavoriteAction.php) → `user_favorite_actions` table.
- ⚡ button in top-nav opens a dropdown with top-ranked + pinned actions.
- `quick_actions` dashboard widget shows frequent actions in a card.

### 8. Multi-Tenancy
- `company_id` column is the tenant anchor. Global scope: [`CompanyScope`](src/Scopes/CompanyScope.php) + [`HasCompanyScope`](src/Traits/HasCompanyScope.php).
- [`ResolvesModels.php`](src/Concerns/ResolvesModels.php) resolves models within company scope.
- When `$companyId === 0`, the `WHERE company_id` clause is skipped (fixes lookup when no company context is set).

### 9. Library Module Structure (Core modules)
- `src/Core/Admin/` — Admin module (Users, Roles, Access Control, Workflows, Notifications, Audit, Settings)
- `src/Core/System/` — System module (Dashboard, Accounts, Subscriptions, Plans, Applications, Settings, Setup)
- `src/Core/Common/` — Common/shared configs (app_general_settings, app_onboarding, app_setup, app_tour)

---

## Step 1 — Read the Architecture Docs First

Before making any changes, read these documents (paths relative to the library repo at `/Users/mac/Projects/Libraries/ui-library`):

1. [`docs/library/27-architecture-boundary.md`](docs/library/27-architecture-boundary.md) — library vs module boundary
2. [`docs/library/02-directory-map.md`](docs/library/02-directory-map.md) — library directory structure
3. [`docs/consuming-app/module-structure.md`](docs/consuming-app/module-structure.md) — module anatomy
4. [`docs/consuming-app/data-configs.md`](docs/consuming-app/data-configs.md) — data table config format
5. [`docs/consuming-app/multi-tenancy.md`](docs/consuming-app/multi-tenancy.md) — tenancy/scoping
6. [`docs/consuming-app/permissions-and-notifications.md`](docs/consuming-app/permissions-and-notifications.md)
7. [`docs/library/06-navigation-system.md`](docs/library/06-navigation-system.md) — navigation architecture
8. [`docs/library/04-routing-and-views.md`](docs/library/04-routing-and-views.md) — routing conventions
9. [`docs/project/library-vs-module-boundary-analysis.md`](docs/project/library-vs-module-boundary-analysis.md)
10. [`docs/project/multitenancy-foundation-analysis.md`](docs/project/multitenancy-foundation-analysis.md)
11. [`docs/project/implementation-plan-organization-hr-split.md`](docs/project/implementation-plan-organization-hr-split.md)
12. [`docs/project/navigation-ux-analysis.md`](docs/project/navigation-ux-analysis.md)
13. [`docs/project/navigation-ux-backlog.md`](docs/project/navigation-ux-backlog.md) — **critical**: current active priority + known issues
14. [`docs/project/employee-self-service-design.md`](docs/project/employee-self-service-design.md) — ESS design
15. [`docs/project/quick-actions-feature-design.md`](docs/project/quick-actions-feature-design.md) — quick actions design
16. [`docs/project/dashboard-drawer-integration-analysis.md`](docs/project/dashboard-drawer-integration-analysis.md)
17. [`docs/project/sidebar-link-audit.md`](docs/project/sidebar-link-audit.md) — final sidebar audit results
18. [`docs/project/data-table-config-audit.md`](docs/project/data-table-config-audit.md) — data table audit
19. [`docs/CHANGELOG.md`](docs/CHANGELOG.md) — full history of all changes

---

## Current State

### Overall
- The library is domain-independent: it provides only the scoping mechanism + generic infrastructure.
- 6 self-contained modules in the consuming app: Organization, HR, Attendance, Leave, Payroll, Holiday.
- Each module has its own migrations, models, routes, views, configs, dashboards, and service provider.
- Modules follow an operational-vs-configuration context group split.
- All modules have overall + per-context-group overview dashboards.

### Navigation (0 sidebar 404s)
- All 152 sidebar links audited: 100 return 200, 52 return 403 (permission-restricted), **0 return 404**.
- Admin navigation split: "Users & Permissions" → "Users" + "Access" + "Security".
- HR navigation split: "People" → "People" + "Manage".
- Admin context-group keys case-normalized to lowercase (`'Dashboard'` → `'dashboard'`).
- Mobile navigation permission guards added.
- Sidebar link audit doc: [`docs/project/sidebar-link-audit.md`](docs/project/sidebar-link-audit.md).

### Dashboards
- All 8 modules have standardized `"{Module} Dashboard"` titles.
- Organization general dashboard enriched (12 stat cards, 3 charts, 3 trends, 7 lists, 6 action cards).
- My Portal ESS dashboard: 11 employee-scoped widgets + 7 quick actions.

### Quick Actions (All 4 Phases Complete)
- **Phase 1**: Cmd+K command palette, 48 actions across 8 modules, [`ActionRegistry`](src/Services/QuickActions/ActionRegistry.php) discovery, client-side filtering, arrow key nav.
- **Phase 2**: Usage tracking ([`ActionTracker`](src/Services/QuickActions/ActionTracker.php)) + personalized ranking ([`RankingEngine`](src/Services/QuickActions/RankingEngine.php): `score = 0.6 × recency + 0.4 × frequency`).
- **Phase 3**: ⚡ top-nav dropdown button + `quick_actions` dashboard widget.
- **Phase 4**: Favorites/pinning (star toggle), shortcut badges (⌘⇧1–9), first-visit pulse animation.

### Drawer Integration
- 44 action cards converted from `navigate` to `openDrawer` (23 Easy Win + 21 Possible).
- 13 data table configs changed to `drawers` crudType.
- [`action_card.blade.php`](src/Resources/views/widgets/action_card.blade.php) uses `$dispatch` for proper drawer parameter passing.
- ⚠️ Some converted cards still do not open the drawer (tracked in backlog).

### Employee Self Service (All 4 Phases Complete)
- **Phase 1**: My Portal dashboard + navigation foundation (`my-portal` context group, 6 sidebar items).
- **Phase 2**: Employee-scoped views (my-leave, my-attendance, my-payslips).
- **Phase 3**: Clock In/Out interactive component ([`ClockEventRecorder`](src/Contracts/Attendance/ClockEventRecorder.php) contract + [`ClockInOut`](src/Http/Livewire/Widgets/ClockInOut.php) Livewire component).
- **Phase 4**: Notifications & Polish (12 ESS notification templates, `TeamWhoIsOut` widget, [`CompositeDashboardResolver`](src/Services/Config/Dashboards/CompositeDashboardResolver.php)).

### Data Tables
- All 36 data table configs audited and optimized across 5 dimensions:
  - 11 configs gained `switchViews` (table/list toggle)
  - 2 configs had default views corrected
  - 3 configs fixed for add button visibility
  - 7 configs optimized to 5–7 default fields
  - 2 configs had row actions reviewed

### Components
- 8 missing/crashed components restored:
  - 2 HR Livewire components (`qf.employee-detail`, `qf.searchable-employee-dropdown`)
  - 6 Payroll Livewire components registered in `PayrollServiceProvider.php`
  - `TaxBandsRepeater` skipped (no usage found)

### Bug Fixes
- `/employees/1` 404: [`ResolvesModels.php`](src/Concerns/ResolvesModels.php) skips `WHERE company_id` when `$companyId === 0`; [`EmployeeDetail.php`](hr-consuming-app:app/Modules/Hr/Http/Livewire/EmployeeDetail.php) config key corrected to `payroll.employee_payroll_profile`.

---

## Recently Completed Tasks (2026-08-19 – 2026-08-20)

A total of 59 tasks completed across 33 task letters (A–BG). Here are the key milestones; see [`docs/CHANGELOG.md`](docs/CHANGELOG.md) for full details.

### Organization Dashboard & Reports (Tasks A–G)
- Enriched Organization general dashboard (12 stats, 3 charts, 3 trends, 7 lists, 6 action cards)
- Fixed duplicate "Dashboard" top-nav tab (library guard)
- Fixed 404s on Organization Reports + Audit context pages
- Converted report views to minimal placeholders; created differentiation doc

### Navigation Fixes (Tasks H–N)
- Mobile nav permission guards + case-sensitivity guard
- Admin nav split: "Users & Permissions" → "Users" + "Access" + "Security"
- Admin sidebar 404 audit: 16 placeholder views + 10 routes
- Admin Dashboard link + active-state fixes

### Dashboard Standardization (Tasks O–P)
- Created naming standard doc; standardized 4 module dashboard titles

### Drawer Integration (Tasks Q–S, AL)
- Analysis: cataloged 93 action cards, identified 22 "Easy Win" candidates
- Converted 23 dashboard action cards from `navigate` to `openDrawer`
- Fixed `wire:click` → `$dispatch` for proper drawer parameters
- Converted 21 "Possible" cards; 13 data table configs to `drawers` crudType
- ⚠️ Some converted cards still not working (tracked in backlog)

### Quick Actions (Tasks T, V–Z, AA, AC, AE, AG, AI, AJ)
- Phase 1: Cmd+K command palette MVP (48 actions, 8 modules)
- Fixes: closure fix, single-root, loading/search, URL path fallback
- Phase 2: Personalized ranking via usage tracking
- Phase 3: ⚡ button + dashboard widget
- Phase 4: Favorites, shortcuts, analytics, first-visit pulse
- P0 quick wins: Admin case-sensitivity, dashboard 404s
- ⚠️ Keyboard shortcut fixes attempted but not working (stale JS cache)

### Employee Self Service (Tasks AN–AU)
- Research, architecture analysis, legacy analysis, UX blueprint
- Phase 1: My Portal dashboard + navigation foundation
- Phase 2: Employee-scoped views (my-leave, my-attendance, my-payslips)
- Phase 3: Clock In/Out interactive component
- Phase 4: Notifications & Polish (12 templates, TeamWhoIsOut widget, CompositeDashboardResolver)

### Sidebar Link 404 Resolution (Tasks AV–AX)
- 152 sidebar URLs tested; all 43 previously-404 links fixed
- System module: 30 placeholder views + 9 routes
- Organization module: 4 placeholder views + 4 routes
- Final result: 100×200, 52×403, **0×404**

### Data Table Audit & Optimization (Tasks AY–BD)
- 36 configs audited across 5 dimensions
- 11 switchViews added, 2 default views corrected, 3 add buttons fixed, 7 fields optimized, 2 row actions reviewed

### Component Restoration (Tasks BE–BG)
- Restored `qf.employee-detail` + `qf.searchable-employee-dropdown` from backup
- Discovered and registered 6 missing Payroll Livewire components
- Fixed `/employees/1` 404 (library ResolvesModels + consuming app config key)

### Notification Infrastructure & Workflow Hardening (2026-09-01)

- **Library**: [`WorkflowEngine`](src/Services/Workflow/WorkflowEngine.php) hardened — initiator notification on workflow start + warning logs for empty recipients and disabled config.
- **Consuming App — Template Seeding**: 4 notification template seeders called from `DatabaseSeeder` — 25 templates across Payroll, HR, Leave, and library built-ins.
- **Consuming App — TemplateVariableRegistry**: `NotificationVariableRegistry` implemented with 19 notification types, each declaring available `{placeholder}` variables. Bound in `AppServiceProvider`.
- **Consuming App — Module Self-Containment**: Root-level `PayrollRoleSeeder` split into module-scoped [`PayrollRoleSeeder`](hr-consuming-app:app/Modules/Payroll/Database/Seeders/PayrollRoleSeeder.php) and [`HrRoleSeeder`](hr-consuming-app:app/Modules/Hr/Database/Seeders/HrRoleSeeder.php).

### Per-Module UX Review & Payroll Wizard Fixes (2026-09-01)

- **8 modules audited, 49 broken issues fixed** across library and consuming app.
- **Top bug patterns**: `company_id` hiddenFields contradictions (40+ configs), wrong model namespaces (24 refs), double-prefixed URLs (16), ad-hoc events (14), missing routes (20+).
- **Payroll Wizard**: CSRF "Page Expired" fixed (route middleware), "All Company" mode fixed (`?:` → strict null check).
- **Notification click-to-navigate**: `navigateToNotification()` with `$this->redirect($url)` in both drawer and full page. `resolveWorkflowableUrl()` always returns a URL.
- **Stale views**: Consuming app views republished to sync notification blade changes.

---

## 🔄 Active Priority: Per-Module Physical UX Review

**The user is now conducting a physical review of each module** — clicking through features, links, workflows, dashboards, and data tables. Issues found will be reported and fixed module-by-module before moving to the next.

### Module Review Checklist

- [ ] **Admin** — Admin dashboard, Users, Access, Workflows, Security, Audit, General Settings, Notifications
- [ ] **System** — System dashboard, Accounts, Subscriptions, Plans, Applications, System Settings, Setup
- [ ] **Organization** — Organization dashboard, Companies, Structure, Teams, Locations, Classification, Reports
- [ ] **HR** — My Portal, Organization, People, Manage
- [ ] **Attendance** — Time, Scheduling, Policies
- [ ] **Leave** — Requests, Configuration
- [ ] **Payroll** — Processing, Configuration
- [ ] **Holiday** — Holidays, Calendars, Batch Create

> **Note:** User is conducting physical review. Issues will be reported and fixed module-by-module. **This takes priority over all other backlog items.** Do not start other backlog tasks until the current module review is complete and the user explicitly moves to a new task.

---

## Current Progress Summary

### Navigation
- All duplicate "Dashboard" top-nav tabs fixed (library guard + case-sensitivity fix)
- Admin context groups case-normalized (`'Dashboard'` → `'dashboard'`)
- Admin navigation split: "Users & Permissions" → "Users" + "Access" + "Security"
- HR navigation split: "People" → "People" + "Manage"
- Mobile navigation permission guards added (`canAccessView()` checks)
- **0 sidebar 404s** — all 152 sidebar links audited and resolving (100×200, 52×403)

### Dashboards
- All 8 modules have `"{Module} Dashboard"` titles following the naming standard
- All modules have per-context-group overview dashboards
- Organization general dashboard enriched to match Payroll/Attendance/HR richness
- My Portal ESS dashboard built with 11 employee-scoped widgets + 7 quick actions

### Quick Actions (All 4 Phases Complete)
- **Phase 1**: Cmd+K command palette with 48 actions across all 8 modules
- **Phase 2**: Usage tracking + personalized ranking (score = 0.6 × recency + 0.4 × frequency)
- **Phase 3**: ⚡ button dropdown + `quick_actions` dashboard widget
- **Phase 4**: User favorites/pinning, shortcut badges, first-visit pulse

### Drawer Integration
- 44 action cards converted to drawers (23 "Easy Win" + 21 "Possible")
- 13 data table configs changed to `drawers` crudType
- ⚠️ Some converted cards still not opening (tracked in backlog)

### Employee Self Service (All 4 Phases Complete)
- My Portal dashboard, employee-scoped views, Clock In/Out, notifications/polish
- 11 widgets, 7 quick actions, 12 notification templates

### Data Tables
- All 36 configs audited and optimized (switchViews, default views, add buttons, fields, row actions)

### Components
- 8 missing/crashed components restored (2 HR + 6 Payroll)

### Documentation
- 15+ documentation files created/updated covering architecture, navigation, quick actions, ESS, drawers, data tables, placeholder pages

### Notifications & Workflow Hardening
- WorkflowEngine now dispatches initiator notification on workflow start + logs warnings for empty recipients/disabled config
- 25 notification templates seeded across 4 module-specific seeders
- `NotificationVariableRegistry` with 19 notification types implementing the `TemplateVariableRegistry` contract
- `PayrollRoleSeeder` and `HrRoleSeeder` created as module-scoped seeders

### Per-Module UX Review
- 8 modules audited and fixed: Admin (13), System (2), Organization (2), HR (12), Attendance (20+), Leave (7), Payroll (10), Holiday (3)
- Payroll wizard CSRF and "All Company" mode fixed
- Notification click-to-navigate working in both drawer and full page

---

## Known Issues

### ⚠️ Issue 1: Keyboard Shortcuts Not Working
- `Cmd+Shift+K` still opens quick actions instead of triggering sidebar filter search
- `Cmd+Shift+1..9` quick launch shortcuts don't trigger at all
- **Likely root cause**: Stale JS asset cache in the consuming app (`public/vendor/ui-library/`). The library files were updated (`quicker-faster.js`, `quick-actions.js`) and re-published, but the browser may be serving cached versions.
- **Fix approaches to try**: Hard cache-busting with versioned asset publishing, browser cache clear, or adding version query strings to asset references.
- **Tracked in**: [`navigation-ux-backlog.md`](docs/project/navigation-ux-backlog.md) as P2 items.
- **Files**: [`quicker-faster.js`](public/assets/js/quicker-faster.js), [`quick-actions.js`](public/assets/js/quick-actions.js), [`QuickActionsPanel.php`](src/Http/Livewire/QuickActions/QuickActionsPanel.php), [`quick-actions-panel.blade.php`](src/Resources/views/livewire/quick-actions/quick-actions-panel.blade.php)

### ⚠️ Issue 2: Non-Working Drawer Action Cards
- Some of the 21 "Possible" converted cards from Task AL still do not open the drawer.
- **Likely causes**: Missing `crudType` on the data table config, incorrect `configKey` reference, or the entity's form doesn't support drawer rendering.
- **Needs**: Per-card investigation. Test each card in the UI, diagnose root cause, fix or revert to `navigate`.
- **Tracked in**: [`navigation-ux-backlog.md`](docs/project/navigation-ux-backlog.md) as P2 items.

---

## Remaining Backlog — Prioritized

> ⚠️ **The per-module physical UX review is the active priority** (see "🔄 Active Priority" section above). All remaining backlog items below are **paused** until the physical review completes module-by-module. Do not start any backlog task unless explicitly requested by the user as part of the current module review.

### Priority 1 — Next Up

#### 1. Review 7 "Needs Review" Overview Dashboard Configs
**Repo**: Consuming app only. Quick audit of dashboard configs flagged during drawer conversion work (Task AL). Verify widget types, model references, configKey values, and route resolution.

#### 2. Fix Non-Working Drawer Action Cards from Task AL
**Repo**: Consuming app only. Test all 21 "Possible" converted cards; for non-working ones, diagnose and fix (add `crudType`, correct `configKey`, or revert to `navigate`).

#### 3. Implement 4 Distinct Organization Report Pages
**Repo**: Consuming app only. Create 4 distinct dashboard configs for the Organization report placeholders (companies, departments, locations, growth). Differentiation spec at [`docs/project/organization-reports-future-differentiation.md`](docs/project/organization-reports-future-differentiation.md).

### Priority 2 — After P1

| # | Task | Effort | Repo | Notes |
|---|---|---|---|---|
| 4 | Implement 21 Admin placeholder pages | Large | Library | Cataloged at [`docs/project/admin-placeholder-pages.md`](docs/project/admin-placeholder-pages.md) |
| 5 | Split Payroll/Attendance/Leave context groups | Medium | Consuming | Reduce sidebar clutter |
| 6 | Remove structural entities from HR navigation | Medium | Consuming | Companies, Departments, Locations duplicated |
| 7 | Fix Attendance routes and orphaned contexts | Medium | Consuming | `/hr/...` prefixes on Attendance routes |
| 8 | Populate `page_title` values | Small | Both | Improve breadcrumbs and page titles |
| 9 | Fix keyboard shortcuts (Cmd+Shift+K, Cmd+Shift+1..9) | Small | Library + Consuming | Stale JS cache issue |

---

## Working Conventions

### Repositories
- Two separate git repos: library (`/Users/mac/Projects/Libraries/ui-library`) and consuming app (`/Users/mac/Projects/LaravelProjects/hr-consuming-app`).
- **Commit changes separately** — never commit library and consuming app changes in the same commit.
- Library changes go through the standard PR/review process (if applicable).

### After Making Changes
- Run `php artisan optimize:clear` in the consuming app after any route, view, or config changes.
- Run `php artisan vendor:publish --tag=ui-library-assets --force` to republish JS/CSS assets from the library to the consuming app's `public/vendor/ui-library/`.
- Run `bash scripts/check-domain-independence.sh` from the library root after any library changes to verify no domain leakage.
- Library migrations must be published before running: `php artisan vendor:publish --tag=ui-library-migrations`.

### Code Conventions
- Dashboard data configs use `widgets`, `roles`, and `layout` blocks.
- Blade views use `<x-qf::navigation-layout context="...">` and `<livewire:qf.dashboard config-key="...">`.
- Named routes follow the `{module}.dashboard-{context}-overview` convention.
- Livewire components use `qf.` prefix: `qf.data-table`, `qf.dashboard`, `qf.quick-actions-panel`, etc.
- Navigation config context and context group keys are **lowercase** (e.g., `'dashboard'` not `'Dashboard'`).
- Permission checks use Spatie's `can()` method or `AuthorizationService`.

---

## Dev Server Info

| Detail | Value |
|--------|-------|
| **Consuming app URL** | `http://localhost:8899` |
| **Login email** | `test@example.com` |
| **Login password** | `password` |
| **Start server** | `cd /Users/mac/Projects/LaravelProjects/hr-consuming-app && php artisan serve --port=8899` |
| **Alternative login** | `super.admin@example.com` / `password` (super admin bypass) |
| **Clear cache** | `cd /Users/mac/Projects/LaravelProjects/hr-consuming-app && php artisan optimize:clear` |
| **Publish assets** | `cd /Users/mac/Projects/LaravelProjects/hr-consuming-app && php artisan vendor:publish --tag=ui-library-assets --force` |

---

## Key Files to Reference

### Library — Core Architecture
| File | Purpose |
|------|---------|
| [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | Main service provider — registers Livewire components, singleton bindings, publishes assets |
| [`src/Config/ui-library.php`](src/Config/ui-library.php) | Master config — navigation, dashboards, quick_actions, tenancy, discovery, access_control |
| [`src/Services/Navigation/NavigationManager.php`](src/Services/Navigation/NavigationManager.php) | Builds the navigation tree from Core + module configs |
| [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) | Resolves data table/dashboard configs from Core or `app/Modules/` |
| [`src/Services/QuickActions/ActionRegistry.php`](src/Services/QuickActions/ActionRegistry.php) | Discovers quick-actions configs from all modules |
| [`src/Concerns/ResolvesModels.php`](src/Concerns/ResolvesModels.php) | Model resolution with company-scoping; `$companyId === 0` guard |

### Library — Navigation Views
| File | Purpose |
|------|---------|
| [`src/Resources/views/components/layouts/navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) | Main layout: sidebar + top-nav + content area + Cmd+K panel + JS includes |
| [`src/Resources/views/livewire/navs/sidebar.blade.php`](src/Resources/views/livewire/navs/sidebar.blade.php) | Sidebar rendering |
| [`src/Resources/views/livewire/navs/top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) | Top navigation bar (context tabs, module switcher, ⚡, notifications, user menu) |
| [`src/Resources/views/livewire/navs/partials/sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) | Individual sidebar item |
| [`src/Resources/views/livewire/navs/partials/sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) | Collapsible sidebar section header |

### Library — Quick Actions
| File | Purpose |
|------|---------|
| [`src/Http/Livewire/QuickActions/QuickActionsPanel.php`](src/Http/Livewire/QuickActions/QuickActionsPanel.php) | Command palette Livewire component |
| [`src/Resources/views/livewire/quick-actions/quick-actions-panel.blade.php`](src/Resources/views/livewire/quick-actions/quick-actions-panel.blade.php) | Command palette modal UI |
| [`public/assets/js/quick-actions.js`](public/assets/js/quick-actions.js) | Vanilla JS: Cmd+K listener, client-side filtering, arrow key nav |
| [`src/Services/QuickActions/RankingEngine.php`](src/Services/QuickActions/RankingEngine.php) | Action ranking: `0.6 × recency + 0.4 × frequency` |
| [`src/Widgets/QuickActionsWidgetProcessor.php`](src/Widgets/QuickActionsWidgetProcessor.php) | Dashboard widget processor for quick actions |

### Library — Widgets & Dashboards
| File | Purpose |
|------|---------|
| [`src/Services/Widgets/WidgetProcessor.php`](src/Services/Widgets/WidgetProcessor.php) | Routes widget types to processors via `$map` |
| [`src/Resources/views/widgets/action_card.blade.php`](src/Resources/views/widgets/action_card.blade.php) | Action card rendering; `$dispatch('openDrawer', ...)` |
| [`src/Services/Config/Dashboards/DashboardResolver.php`](src/Services/Config/Dashboards/DashboardResolver.php) | Resolves dashboard configs and widget definitions |

### Consuming App — Key Config Files
| File | Purpose |
|------|---------|
| `app/Modules/{Module}/Config/navigation.php` | Module navigation (context groups + sidebar items) |
| `app/Modules/{Module}/Config/quick-actions.php` | Module quick actions for Cmd+K |
| `app/Modules/{Module}/Data/dashboards/dashboard_*.php` | Dashboard widget configs |
| `app/Modules/{Module}/Data/{entity}.php` | Data table configs (form, table, detail) |
| `app/Modules/{Module}/Routes/web.php` | Module routes |

### Documentation
| File | Purpose |
|------|---------|
| [`docs/CHANGELOG.md`](docs/CHANGELOG.md) | Full change history for all 59 tasks |
| [`docs/project/navigation-ux-backlog.md`](docs/project/navigation-ux-backlog.md) | Active priority + full backlog |
| [`docs/project/sidebar-link-audit.md`](docs/project/sidebar-link-audit.md) | 152-URL sidebar audit with per-module breakdown |
| [`docs/project/employee-self-service-design.md`](docs/project/employee-self-service-design.md) | ESS design (all 4 phases) |
| [`docs/project/quick-actions-feature-design.md`](docs/project/quick-actions-feature-design.md) | Quick actions feature specification |
| [`docs/project/dashboard-drawer-integration-analysis.md`](docs/project/dashboard-drawer-integration-analysis.md) | Drawer conversion analysis |
| [`docs/project/data-table-config-audit.md`](docs/project/data-table-config-audit.md) | 36-config data table audit |
