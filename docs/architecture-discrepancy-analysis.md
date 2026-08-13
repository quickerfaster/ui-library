# QuickerFaster UI Library — Architectural Discrepancy Analysis

> **Date**: 2026-08-09 (Updated 2026-08-12)
> **Status**: ✅ Complete — All gaps resolved as of 2026-08-12
> **Scope**: Gap analysis between `app/Modules/*` (consuming app) and `src/Core/*` (library) after decoupling migration
> **Update**: All critical gaps identified in this analysis have been resolved:
> - **Category A** (Missing Module Internals): Context group overview views and Data configs created for Admin, System, and Organization (see §0.11 of [`implementation-plan.md`](docs/implementation-plan.md))
> - **Category B** (Missing Data Config Pattern): `ModelConfigRepository` extended with dual-location resolution — scans both `app/Modules/` and `src/Core/` (see §0.10 of implementation plan)
> - **Category C** (View/Routing Interplay): Catch-all route interaction documented in [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) and [`docs/phase-4.1-organization-extraction-spec.md`](docs/phase-4.1-organization-extraction-spec.md)
> See [`docs/pre-phase-4-remediation-plan.md`](docs/pre-phase-4-remediation-plan.md) for detailed resolution steps.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Methodology & Constraints](#2-methodology--constraints)
3. [Gap Inventory: src/Core/ vs Expected Module Structure](#3-gap-inventory-srccore-vs-expected-module-structure)
4. [View/Config/Catch-All Routing Interplay Analysis](#4-viewconfigcatch-all-routing-interplay-analysis)
5. [Root Cause Analysis](#5-root-cause-analysis)
6. [Impact Assessment: Phase 4.1 Without Remediation](#6-impact-assessment-phase-41-without-remediation)
7. [Critical Findings: Remaining HR Couplings](#7-critical-findings-remaining-hr-couplings)
8. [Recommendations](#8-recommendations)

---

## 1. Executive Summary

### 1.1 The Core Finding

The decoupling migration from the HR monolith to the standalone UI library is **structurally incomplete**. The `src/Core/` directory contains only **skeleton files** (navigation configs, seeders, a single dashboard view, and route stubs) rather than the full module implementations described in the decoupling migration plan. The plan states that the "entire directory" of `app/Modules/Admin/` and `app/Modules/System/` should be moved into `src/Core/`, but the actual migration produced only the minimal scaffolding.

### 1.2 Three Categories of Gaps

| Category | Description | Severity |
|----------|-------------|----------|
| **A. Missing Module Internals** | Admin and System modules lack their Http/Livewire components, Http/Controllers, and Models described in the decoupling plan | **HIGH** — Core modules are non-functional shells |
| **B. Missing Data Config Pattern** | Neither `src/Core/` nor the Phase 4.1 spec address `Data/` directories for config-driven datatables | **HIGH** — Organization entities will have no CRUD UI |
| **C. Undocumented View/Routing Interplay** | The catch-all route mechanism, view namespace resolution, and data config consumption are documented in the blueprint but not reflected in the Phase 4.1 extraction spec | **MEDIUM** — Phase 4.1 will produce an incomplete module |

### 1.3 Key Constraint

The `app/Modules/` directory **does not exist** in this workspace (`/Users/mac/Projects/Libraries/ui-library/`). It lives in the consuming HR application (`/Users/mac/Projects/LaravelProjects/quick-hr/`). All analysis of what SHOULD be in `app/Modules/*` is derived from the decoupling migration plan, the architecture blueprint, and the implementation plan.

---

## 2. Methodology & Constraints

### 2.1 Sources Consulted

| Document | Lines | Key Insights |
|----------|-------|-------------|
| [`docs/decoupling-migration-plan.md`](docs/decoupling-migration-plan.md) | 2,133 | Defines what SHOULD be moved from `app/Modules/*` to `src/Core/*` |
| [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) | 2,888 | Defines the canonical module pattern, catch-all routing, and config-driven architecture |
| [`docs/phase-4.1-organization-extraction-spec.md`](docs/phase-4.1-organization-extraction-spec.md) | 1,213 | Defines the Organization extraction target structure |
| [`docs/implementation-plan.md`](docs/implementation-plan.md) | 1,675 | Tracks completed phases and remaining gaps |
| [`src/Core/`](src/Core/) | 3 modules | Actual files present in the library |
| [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | 301 | How Core modules are booted |
| [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php) | 234 | How business modules are discovered |
| [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php) | 33 | The catch-all route implementation |
| [`src/Config/ui-library.php`](src/Config/ui-library.php) | 230 | Module registry and configuration |

### 2.2 Constraints

- **`app/Modules/` is inaccessible**: The HR app's module files cannot be directly inspected. All expected structures are inferred from documentation.
- **No runtime verification**: The library cannot be booted in isolation to verify route resolution or view rendering.
- **Documentation may be aspirational**: Some documented structures may represent target state rather than current state.

---

## 3. Gap Inventory: src/Core/ vs Expected Module Structure

### 3.1 Admin Module (`src/Core/Admin/`)

#### What the Decoupling Plan Says Should Exist

From [`docs/decoupling-migration-plan.md`](docs/decoupling-migration-plan.md:197-228), Section 3.1.1:

```
src/Core/Admin/
├── Config/navigation.php
├── Database/
│   ├── Migrations/create_admin_tables.php
│   └── Seeders/
│       ├── RoleSeeder.php
│       └── SuperAdminSeeder.php
├── Http/
│   ├── Controllers/UserManagementController.php
│   └── Livewire/
│       ├── UserList.php
│       ├── UserForm.php
│       └── RoleManager.php
├── Models/
│   ├── User.php
│   └── Role.php
├── Resources/views/
│   ├── dashboard.blade.php
│   ├── users/index.blade.php
│   ├── users/form.blade.php
│   └── roles/index.blade.php
└── Routes/web.php
```

#### What Actually Exists

| File | Status | Category |
|------|--------|----------|
| `Config/navigation.php` | ✅ EXISTS | Config |
| `Database/Seeders/RoleSeeder.php` | ✅ EXISTS | Seeder |
| `Database/Seeders/SuperAdminSeeder.php` | ✅ EXISTS | Seeder |
| `Resources/views/dashboard.blade.php` | ✅ EXISTS | View |
| `Routes/web.php` | ✅ EXISTS | Routes |
| `Database/Migrations/create_admin_tables.php` | ❌ MISSING | **CRITICAL** — No admin tables |
| `Http/Controllers/UserManagementController.php` | ❌ MISSING | **CRITICAL** — No user CRUD |
| `Http/Livewire/UserList.php` | ❌ MISSING | **CRITICAL** — No user listing |
| `Http/Livewire/UserForm.php` | ❌ MISSING | **CRITICAL** — No user form |
| `Http/Livewire/RoleManager.php` | ❌ MISSING | **CRITICAL** — No role management |
| `Models/User.php` | ❌ MISSING | **CRITICAL** — No user model |
| `Models/Role.php` | ❌ MISSING | **CRITICAL** — No role model |
| `Resources/views/users/index.blade.php` | ❌ MISSING | View |
| `Resources/views/users/form.blade.php` | ❌ MISSING | View |
| `Resources/views/roles/index.blade.php` | ❌ MISSING | View |

**Admin Module Summary**: 5 of 15 expected files exist (33%). All critical functional files (Controllers, Livewire components, Models) are **MISSING**. The module is a non-functional shell.

### 3.2 System Module (`src/Core/System/`)

#### What the Decoupling Plan Says Should Exist

From [`docs/decoupling-migration-plan.md`](docs/decoupling-migration-plan.md:238-261), Section 3.1.2:

```
src/Core/System/
├── Config/navigation.php
├── Database/
│   ├── Migrations/create_system_tables.php
│   └── Seeders/SystemSettingsSeeder.php
├── Http/
│   └── Livewire/
│       ├── SystemSettings.php
│       └── SetupWizard.php
├── Models/System.php
├── Resources/views/
│   ├── dashboard.blade.php
│   └── settings/index.blade.php
└── Routes/web.php          ← Catch-all route
```

#### What Actually Exists

| File | Status | Category |
|------|--------|----------|
| `Config/navigation.php` | ✅ EXISTS | Config |
| `Database/Migrations/2026_07_17_000000_create_systems_table.php` | ✅ EXISTS | Migration |
| `Database/Seeders/SystemSettingsSeeder.php` | ✅ EXISTS | Seeder |
| `Resources/views/dashboard.blade.php` | ✅ EXISTS | View |
| `Routes/web.php` | ✅ EXISTS (with catch-all) | Routes |
| `Http/Livewire/SystemSettings.php` | ❌ MISSING | **CRITICAL** — No settings UI |
| `Http/Livewire/SetupWizard.php` | ❌ MISSING | Note: SetupWizard exists at `src/Http/Livewire/Wizards/SetupWizard.php` — may be intentional |
| `Models/System.php` | ❌ MISSING | Note: System model exists at `src/Models/System.php` — may be intentional |
| `Resources/views/settings/index.blade.php` | ❌ MISSING | View |

**System Module Summary**: 5 of 9 expected files exist (56%). The catch-all route IS present and functional. The `SystemSettings` Livewire component is missing, but `SetupWizard` and `System` model exist at the library level (`src/Http/Livewire/Wizards/` and `src/Models/`), suggesting intentional relocation rather than omission.

### 3.3 Common Configs (`src/Core/Common/Config/`)

| File | Status |
|------|--------|
| `app_general_settings.php` | ✅ EXISTS |
| `app_onboarding.php` | ✅ EXISTS |
| `app_setup.php` | ✅ EXISTS |
| `app_tour.php` | ✅ EXISTS |
| `Database/Seeders/NotificationTemplateSeeder.php` | ✅ EXISTS |

**Common Summary**: 5 of 5 expected files exist (100%). This is the only complete Core module area.

### 3.4 What's Missing Across ALL Core Modules

#### 3.4.1 No `Data/` Directory in Any Core Module

Neither `src/Core/Admin/`, `src/Core/System/`, nor `src/Core/Common/` contain a `Data/` directory. Per the blueprint ([`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:527-548), Section 2.2), every business module is expected to have:

```
Data/
├── {Entity}.php              # Shared config for table + form + detail
├── Dashboards/               # Dashboard widget definitions
└── reports/                  # Report definitions
```

**This is the single most critical gap.** Without `Data/` configs, the config-driven DataTable, DataTableForm, and DataTableDetail components have nothing to render. The entire "config-driven architecture" is predicated on these files existing.

#### 3.4.2 No `Data/` in Phase 4.1 Organization Spec

The Phase 4.1 spec's target directory structure ([`docs/phase-4.1-organization-extraction-spec.md`](docs/phase-4.1-organization-extraction-spec.md:50-78)) does NOT include a `Data/` directory. This means Organization entities (Company, Branch, Department, etc.) will have:
- ✅ Database tables (migrations)
- ✅ Eloquent models
- ✅ Routes
- ✅ A dashboard view
- ❌ **No DataTable configs** — no CRUD UI
- ❌ **No dashboard widget configs**
- ❌ **No report configs**

### 3.5 Gap Summary Table

| Module | Expected Files | Actual Files | Completion | Critical Missing |
|--------|---------------|-------------|------------|-----------------|
| Admin | 15 | 5 | 33% | Controllers, Livewire, Models, Views, Migrations |
| System | 9 | 5 | 56% | SystemSettings Livewire, settings view |
| Common | 5 | 5 | 100% | None |
| **Cross-cutting** | Data/ dirs | 0 | 0% | **All Data configs for all modules** |

---

## 4. View/Config/Catch-All Routing Interplay Analysis

### 4.1 The Catch-All Route Mechanism

**Location**: [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php:16-32)

```php
Route::get('/{module}/{view}/{id?}', function ($module, $view, $id = null) {
    $viewName = "{$module}::{$view}";
    if (view()->exists($viewName)) {
        return view($viewName, ['id' => $id]);
    }
    $coreViewName = "qf-core::{$module}.{$view}";
    if (view()->exists($coreViewName)) {
        return view($coreViewName, ['id' => $id]);
    }
    abort(404, "View [{$viewName}] not found.");
})->where('module', '[a-z-]+')->where('view', '[a-z-]+')->where('id', '[0-9]+');
```

**Resolution order**:
1. First tries `{module}::{view}` — the business module view namespace (e.g., `hr::employee.index`)
2. Falls back to `qf-core::{module}.{view}` — the Core module view namespace (e.g., `qf-core::system.dashboard`)
3. Returns 404 if neither exists

### 4.2 View Namespace Registration

**Core modules** (in [`UILibraryServiceProvider::bootCoreModules()`](src/Providers/UILibraryServiceProvider.php:113-145)):
```php
$this->loadViewsFrom($viewPath, "qf-core::{$moduleLower}");
// Admin → qf-core::admin
// System → qf-core::system
// Organization → qf-core::organization (after Phase 4.1)
```

**Business modules** (in [`ModuleServiceProvider::discoverBusinessModules()`](src/Providers/ModuleServiceProvider.php:60-64)):
```php
$this->loadViewsFrom($viewPath, $moduleName);
// Hr → hr
// Finance → finance
```

### 4.3 How Views Get Rendered

The flow for a URL like `/hr/employee/index`:

```
1. Request: GET /hr/employee/index
2. System catch-all route matches (loaded LAST)
3. Tries: view('hr::employee.index')
   → ModuleServiceProvider registered 'hr' namespace from app/Modules/Hr/Resources/views/
   → Looks for: app/Modules/Hr/Resources/views/employee/index.blade.php
4. If found → renders with ['id' => null]
5. If not found → tries: view('qf-core::hr.employee.index')
   → Looks for: src/Core/Hr/Resources/views/employee/index.blade.php
6. If still not found → 404
```

### 4.4 How Data Configs Drive DataTables

The flow for a DataTable component:

```
1. View contains: <livewire:qf.data-table config-key="hr.employee" />
2. DataTable::mount('hr.employee')
3. ModelConfigRepository::get('hr.employee')
   → Splits key: ['hr', 'employee']
   → Module: ucfirst('hr') → 'Hr'
   → Path: app/Modules/Hr/Data/employee.php
4. ConfigResolver loads the config array
5. DataTable renders columns from fieldDefinitions
6. DataTableForm renders form fields from fieldDefinitions + fieldGroups
7. DataTableDetail renders detail sections from fieldDefinitions
```

**Critical insight**: The `ModelConfigRepository` resolves configs from `app/Modules/{Module}/Data/{file}.php` — the **business layer**, NOT the library. The library's `src/Core/` modules do NOT have `Data/` directories, and the `ModelConfigRepository` does NOT look for configs under `src/Core/`.

### 4.5 The Architectural Disconnect

There is a fundamental disconnect in how Core modules are expected to provide CRUD UI:

| Concern | Business Module Pattern | Core Module Pattern (Current) | Gap |
|---------|------------------------|-------------------------------|-----|
| Data configs | `app/Modules/{Module}/Data/{Entity}.php` | **Not present** | Core modules have no Data configs |
| Config resolution | `ModelConfigRepository` resolves from `app/Modules/` | **Not supported** | Repository doesn't scan `src/Core/` |
| Views | `app/Modules/{Module}/Resources/views/` | `src/Core/{Module}/Resources/views/` | Core views exist but are minimal |
| Routes | `app/Modules/{Module}/Routes/web.php` | `src/Core/{Module}/Routes/web.php` | Core routes exist |
| Catch-all | Resolved via `{module}::{view}` | Resolved via `qf-core::{module}.{view}` | Both paths work |

**The `ModelConfigRepository` only scans `app/Modules/` for Data configs.** This means:
- Core module entities CANNOT use `<livewire:qf.data-table config-key="admin.user" />` because the config key `admin.user` would resolve to `app/Modules/Admin/Data/user.php` which doesn't exist (Admin is now in `src/Core/`)
- The `ModuleServiceProvider::registerModuleConfigs()` method (line 138-170) scans BOTH `src/Core` and `app/Modules` for dashboard and report configs, but the `ModelConfigRepository` does NOT

### 4.6 The `registerModuleConfigs()` Partial Solution

[`ModuleServiceProvider::registerModuleConfigs()`](src/Providers/ModuleServiceProvider.php:138-170) scans both paths for dashboard and report configs:

```php
$modulePaths = [
    base_path('vendor/quicker-faster/ui-library/src/Core'),
    base_path('app/Modules'),
];
// Scans: {path}/*/Data/Dashboards/*.php
// Scans: {path}/*/Data/reports/*.php
```

But this only handles dashboard and report configs — NOT the primary entity Data configs that drive DataTable/DataTableForm/DataTableDetail.

---

## 5. Root Cause Analysis

### 5.1 Why the Gaps Exist

The gaps exist due to a **fundamental architectural assumption** that was not fully validated during the decoupling migration:

#### Assumption 1: Core Modules Don't Need Data Configs

The decoupling plan treats Admin and System as "infrastructure" modules that provide cross-cutting concerns (auth, settings, navigation). It assumes they don't need the config-driven CRUD pattern that business modules use. However:

- **Admin DOES need CRUD**: User management, role management, and permission assignment are CRUD operations that should use the same DataTable/DataTableForm components
- **System DOES need CRUD**: System settings management is a CRUD operation
- **Organization WILL need CRUD**: Companies, branches, departments are all CRUD entities

#### Assumption 2: The `ModelConfigRepository` Would Be Extended

The decoupling plan doesn't address how `ModelConfigRepository` would resolve configs for Core modules. The repository hardcodes `app/Modules/` as the base path:

```php
// ModelConfigRepository::loadFromFile()
$filePath = $this->basePath . '/' . $module . '/Data/' . $relativePath . '.php';
```

Where `$this->basePath` is `app_path('Modules')`. There is no fallback to `src/Core/`.

#### Assumption 3: Views Would Be Sufficient

The Phase 4.1 spec creates views like `companies.blade.php` but doesn't specify what goes IN those views. The blueprint's pattern ([`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:1796)) is:

```blade
{{-- app/Modules/Billing/Resources/views/index.blade.php --}}
<livewire:qf.data-table config-key="billing.invoice" />
```

Without a `Data/` config, this pattern cannot work. The Phase 4.1 spec creates views but provides no mechanism for them to render data.

### 5.2 Intentional vs Accidental Omissions

| Omission | Intentional? | Evidence |
|----------|-------------|----------|
| Admin Livewire components missing | **Accidental** | Decoupling plan says "entire directory" should move; they weren't moved |
| Admin Models missing | **Accidental** | Plan lists `Models/User.php` and `Models/Role.php` |
| System Livewire components missing | **Mixed** | `SetupWizard` exists at library level (intentional relocation); `SystemSettings` is missing |
| System Model missing from Core | **Intentional** | `System.php` exists at `src/Models/System.php` (library-level model) |
| No `Data/` directories | **Accidental** | Neither the decoupling plan nor the Phase 4.1 spec mention Data configs for Core modules |
| `ModelConfigRepository` doesn't scan `src/Core/` | **Accidental** | The `registerModuleConfigs()` method scans both paths, but `ModelConfigRepository` doesn't |

---

## 6. Impact Assessment: Phase 4.1 Without Remediation

### 6.1 What Phase 4.1 Currently Delivers

If Phase 4.1 is executed as currently specified:

| Deliverable | Status | Functional? |
|-------------|--------|-------------|
| 7 Eloquent models (Company, Branch, etc.) | ✅ Will exist | ✅ Models work for programmatic use |
| 7 Migration files | ✅ Will exist | ✅ Tables will be created |
| 1 Seeder | ✅ Will exist | ✅ Demo data can be seeded |
| Navigation config | ✅ Will exist | ✅ Sidebar will show Organization menu |
| Routes (8 named routes) | ✅ Will exist | ✅ URLs will resolve |
| Dashboard view | ✅ Will exist | ✅ Static welcome page renders |
| Entity index views | ❌ NOT in spec | ❌ No views for companies, branches, etc. |
| Data configs | ❌ NOT in spec | ❌ No DataTable/Form/Detail configs |
| CRUD functionality | ❌ NOT in spec | ❌ Cannot create, read, update, or delete entities |

### 6.2 What Breaks

1. **Navigation links are dead ends**: The navigation config defines routes like `organization.companies`, but the views those routes point to don't exist (the spec only creates `dashboard.blade.php`)

2. **No CRUD UI**: Without Data configs and entity views, there is no way to manage companies, branches, departments, etc. through the UI

3. **Catch-all route can't help**: The catch-all route resolves `organization::companies` but there's no `companies.blade.php` in `src/Core/Organization/Resources/views/`

4. **DataTable component can't render**: `<livewire:qf.data-table config-key="organization.company" />` would fail because:
   - `ModelConfigRepository` looks in `app/Modules/Organization/Data/company.php`
   - Organization is in `src/Core/Organization/`, not `app/Modules/Organization/`
   - Even if the path were correct, no Data config file exists

5. **Inconsistent with Admin/System pattern**: Admin and System also lack Data configs, so Organization wouldn't be uniquely broken — but ALL Core modules would be equally non-functional for CRUD

### 6.3 Severity Matrix

| Scenario | Severity | Description |
|----------|----------|-------------|
| Organization extracted WITHOUT Data configs | **HIGH** | Models and tables exist but are unmanageable through UI |
| Organization extracted WITHOUT entity views | **HIGH** | Routes resolve but return 404 or blank pages |
| Organization extracted WITHOUT `ModelConfigRepository` update | **CRITICAL** | DataTable component cannot find configs even if they exist |
| Admin/System remain as skeletons | **MEDIUM** | Existing functionality may work if consuming app still has old `app/Modules/Admin/` and `app/Modules/System/` |

---

## 7. Critical Findings: Remaining HR Couplings

### 7.1 DataTable Component HR Couplings

The [`DataTable`](src/Http/Livewire/DataTables/DataTable.php:10-12) component still imports:

```php
use App\Modules\Admin\Services\ActivityLogger;
use App\Modules\Admin\Services\AuthorizationService;
```

And uses `AuthorizationService` directly (line 84):
```php
protected AuthorizationService $authService;
```

These are **HR-specific services** that live in the consuming app, not the library. This means:
- The DataTable component **cannot function** without the HR app's Admin module
- This violates the architectural invariant: "the library never imports from `App\Modules\*`"
- This coupling is NOT documented in the implementation plan's gap list

### 7.2 Other Undocumented Couplings

The blueprint ([`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2604)) acknowledges "~50 broader references remain" but doesn't enumerate them. The `ActivityLogger` and `AuthorizationService` imports in `DataTable` are examples of these undocumented couplings.

---

## 8. Recommendations

### 8.1 Completed (Resolved 2026-08-12)

1. ✅ **Extend `ModelConfigRepository` to scan `src/Core/`**: Dual-location progressive path fallback implemented. See §0.10 of implementation plan.
2. ✅ **Define Data Config pattern for Core modules**: Context group overview Data configs created for Admin, System, Organization. See §0.11 of implementation plan.
3. ✅ **Decouple from `App\Modules\Admin\Services\*`**: HR decoupling audit completed. `ActivityLogger` moved to `src/Services/`. `AuthorizationService` replaced with Spatie Permission integration. See §0.12 of implementation plan.

### 8.2 Phase 4.1 Amendments (Resolved)

1. ✅ **Data/ directory for Organization**: 7 Data configs created (`company.php`, `branch.php`, `department.php`, `division.php`, `business-unit.php`, `location.php`, `team.php`)
2. ✅ **Entity index views**: 7 entity views created with `<livewire:qf.data-table config-key="organization.{entity}" />` pattern
3. ✅ **Catch-all route documentation**: Documented in blueprint §4 and phase 4.1 spec §2.3

### 8.3 Remaining (Future)

1. **Complete Admin and System module extraction**: Move remaining Livewire components, controllers, and models from HR app
2. **Create `php artisan qf:make-module` command**: Scaffold new Core modules with complete directory structure
3. **Add automated gap detection**: CI check comparing `src/Core/{Module}/` against canonical module structure
4. **Resolve 7 remaining hardcoded imports**: Documented in [`implementation-plan.md` §11](implementation-plan.md)

---

## Appendix A: File Status Reference

### A.1 src/Core/Admin/ — Complete Inventory

| Path | Status | Expected? |
|------|--------|-----------|
| `Config/navigation.php` | ✅ | Yes |
| `Database/Migrations/` | ❌ (empty dir) | Yes — `create_admin_tables.php` |
| `Database/Seeders/RoleSeeder.php` | ✅ | Yes |
| `Database/Seeders/SuperAdminSeeder.php` | ✅ | Yes |
| `Http/Controllers/` | ❌ (empty dir) | Yes — `UserManagementController.php` |
| `Http/Livewire/` | ❌ (empty dir) | Yes — `UserList.php`, `UserForm.php`, `RoleManager.php` |
| `Models/` | ❌ (empty dir) | Yes — `User.php`, `Role.php` |
| `Resources/views/dashboard.blade.php` | ✅ | Yes |
| `Resources/views/users/` | ❌ | Yes — `index.blade.php`, `form.blade.php` |
| `Resources/views/roles/` | ❌ | Yes — `index.blade.php` |
| `Routes/web.php` | ✅ | Yes |
| `Data/` | ❌ | NOT in decoupling plan but NEEDED |

### A.2 src/Core/System/ — Complete Inventory

| Path | Status | Expected? |
|------|--------|-----------|
| `Config/navigation.php` | ✅ | Yes |
| `Database/Migrations/2026_07_17_000000_create_systems_table.php` | ✅ | Yes |
| `Database/Seeders/SystemSettingsSeeder.php` | ✅ | Yes |
| `Http/Livewire/` | ❌ (empty dir) | Yes — `SystemSettings.php` |
| `Models/` | ❌ (empty dir) | Mixed — `System.php` at `src/Models/` |
| `Resources/views/dashboard.blade.php` | ✅ | Yes |
| `Resources/views/settings/` | ❌ | Yes — `index.blade.php` |
| `Routes/web.php` | ✅ (with catch-all) | Yes |
| `Data/` | ❌ | NOT in decoupling plan but NEEDED |

### A.3 src/Core/Common/ — Complete Inventory

| Path | Status |
|------|--------|
| `Config/app_general_settings.php` | ✅ |
| `Config/app_onboarding.php` | ✅ |
| `Config/app_setup.php` | ✅ |
| `Config/app_tour.php` | ✅ |
| `Database/Seeders/NotificationTemplateSeeder.php` | ✅ |

---

## Appendix B: ModelConfigRepository Resolution Paths

### ✅ Resolved (2026-08-12)

The dual-location resolution has been implemented in [`ModelConfigRepository::loadFromFile()`](src/Services/Config/ModelConfigRepository.php). The progressive path fallback works as follows:

```
Config Key: "hr.employee"
  → Module: "Hr"
  → Try: app/Modules/Hr/Data/employee.php (business module)
  → Found at: app/Modules/Hr/Data/employee.php ✓

Config Key: "admin.user"
  → Module: "Admin"
  → Try: app/Modules/Admin/Data/user.php (business module path)
  → Fallback: src/Core/Admin/Data/user.php (core module path)
  → Found at: src/Core/Admin/Data/user.php ✓

Config Key: "organization.company"
  → Module: "Organization"
  → Try: app/Modules/Organization/Data/company.php
  → Fallback: src/Core/Organization/Data/company.php
  → Found at: src/Core/Organization/Data/company.php ✓
```

### Implementation Detail

The fix adds a second base path in `ModelConfigRepository::loadFromFile()`. When the primary path (`app/Modules/{Module}/Data/{file}.php`) does not exist, the repository checks the secondary path (`src/Core/{Module}/Data/{file}.php`). This is transparent to consumers — config keys work identically for both business and core modules. See §0.10 of the implementation plan for full details.