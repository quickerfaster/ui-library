# QuickerFaster UI Library — Pre-Phase 4 Remediation Plan

> **Date**: 2026-08-09
> **Status**: ✅ ALL STEPS COMPLETED — 2026-08-09
> **Purpose**: Resolve three critical issues blocking Phase 4.1 (Extract Organization into Core)
> **Target**: All remediation steps completed. Phase 4.1 is unblocked.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Issue 1 — Complete Phase 2.5 Decoupling](#2-issue-1--complete-phase-25-decoupling)
   - [2.1 Audit and Remove Residual `App\Modules` References](#21-audit-and-remove-residual-appmodules-references)
   - [2.2 Audit and Fix Service Provider Bindings](#22-audit-and-fix-service-provider-bindings)
   - [2.3 Audit and Fix Config Key References](#23-audit-and-fix-config-key-references)
   - [2.4 Complete `src/Core/Admin/` Module Skeleton](#24-complete-srccoreadmin-module-skeleton)
   - [2.5 Complete `src/Core/System/` Module Skeleton](#25-complete-srccoresystem-module-skeleton)
   - [2.6 Extend `ModelConfigRepository` to Scan `src/Core/`](#26-extend-modelconfigrepository-to-scan-srccore)
   - [2.7 Verify All Contracts Match Their Implementations](#27-verify-all-contracts-match-their-implementations)
3. [Issue 2 — Document Phase 3.5 (Reference Data)](#3-issue-2--document-phase-35-reference-data)
   - [3.1 Write Phase 3.5 Documentation Content](#31-write-phase-35-documentation-content)
   - [3.2 Update Blueprint Completed Phases List](#32-update-blueprint-completed-phases-list)
   - [3.3 Update Implementation Plan Task Table](#33-update-implementation-plan-task-table)
   - [3.4 Add Phase 3.5 to the Architecture Index](#34-add-phase-35-to-the-architecture-index)
4. [Issue 3 — Fix Blueprint and Index](#4-issue-3--fix-blueprint-and-index)
   - [4.1 Verify and Fix `docs/architecture/00-index.md`](#41-verify-and-fix-docsarchitecture00-indexmd)
   - [4.2 Update Blueprint Phase Statuses to Reflect Reality](#42-update-blueprint-phase-statuses-to-reflect-reality)
   - [4.3 Validate All Cross-References Between Documents](#43-validate-all-cross-references-between-documents)
   - [4.4 Decision on 17-File Split](#44-decision-on-17-file-split)
5. [Execution Sequence](#5-execution-sequence)
6. [Pre-Phase 4.1 Readiness Checklist](#6-pre-phase-41-readiness-checklist)

---

## 1. Executive Summary

### 1.1 What This Plan Covers

This plan addresses three critical issues discovered during architectural analysis of the QuickerFaster UI Library. These issues make the codebase unstable for Phase 4.1 (Extract Organization into Core) and must be resolved first.

### 1.2 The Three Issues

| # | Issue | Severity | Impact on Phase 4.1 |
|---|-------|----------|---------------------|
| 1 | **Incomplete Phase 2.5 Decoupling** — Residual `App\Modules` references, stale service provider bindings, incomplete Core module skeletons, and a `ModelConfigRepository` that cannot resolve Core module Data configs | **CRITICAL** | Organization entities will have no CRUD UI; DataTable components will throw `InvalidArgumentException` |
| 2 | **Missing Phase 3.5 Documentation** — Reference Data was fully implemented but never documented in the blueprint, implementation plan, or architecture index | **HIGH** | Creates confusion about what was built vs what was planned; the implementation plan still shows it as "Not Started" |
| 3 | **Broken Blueprint Index** — The proposed `docs/architecture/00-index.md` references 17 files that don't exist; the main blueprint omits Phase 3.5 from its completed phases list | **MEDIUM** | AI agents and developers reading the index will be misled about the actual state of the codebase |

### 1.3 What "Done" Looks Like

- Zero `App\Modules` namespace references in library code (path references like `base_path('app/Modules')` are acceptable for business module discovery)
- `DataTable.php` no longer imports from `App\Modules\Admin\Services\*`
- `ModelConfigRepository` resolves configs from both `app/Modules/` and `src/Core/`
- `src/Core/Admin/` and `src/Core/System/` have complete `Data/` directories with at least one example config each
- The blueprint has a dedicated Phase 3.5 section documenting the Reference Data architecture
- The implementation plan task table shows Phase 3.5 as "✅ Complete"
- The `00-index.md` accurately reflects the current codebase state
- All cross-references between documents are valid

---

## 2. Issue 1 — Complete Phase 2.5 Decoupling

### 2.1 Audit and Remove Residual `App\Modules` References

#### 2.1.1 Findings Summary

The audit found **3 residual `App\Modules` namespace references** and **8 `app/Modules` path references** in library code. Additionally, `DataTable.php` has **2 undocumented HR couplings** that were not addressed in Phase 2.5.

#### 2.1.2 Residual `App\Modules` Namespace References (MUST FIX)

| # | File | Line(s) | Code | Decision |
|---|------|---------|------|----------|
| R1 | [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php) | 213 | `return "App\\Modules\\{$moduleName}\\{$directory}\\{$className}";` | **UPDATE** — Change to use configurable namespace prefix. The `getClassFromFile()` method constructs FQCNs for business module event listeners. This is correct behavior for business modules (they live in `App\Modules\`), but the namespace prefix should be configurable via `ui-library.module_paths.business_namespace`. |
| R2 | [`src/Http/Livewire/AccessControls/AccessControlManager.php`](src/Http/Livewire/AccessControls/AccessControlManager.php) | 118-119 | `app_path("Modules/".ucfirst($this->selectedModule)."/Models")` and `"App\\Modules\\".ucfirst($this->selectedModule)."\\Models\\"` | **UPDATE** — Replace with a config-driven model discovery mechanism. The `AccessControlManager` scans business module Models directories for permission assignment. This should use the `ModuleServiceProvider`'s discovery pattern or a dedicated `ModelDiscovery` service. |
| R3 | [`src/Services/AccessControl/AccessControlPermissionService.php`](src/Services/AccessControl/AccessControlPermissionService.php) | 71-72 | `app_path("Modules/" . $moduleName . "/Models")` and `"App\\Modules\\" . $moduleName . "\\Models\\"` | **UPDATE** — Same pattern as R2. Consolidate with the fix for R2 into a shared `ModelDiscovery` service. |

#### 2.1.3 `app/Modules` Path References (ACCEPTABLE — Business Module Discovery)

| # | File | Line(s) | Code | Decision |
|---|------|---------|------|----------|
| P1 | [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php) | 30 | `$businessPath = base_path('app/Modules');` | **DOCUMENT_AS_KNOWN_GAP** — This is the canonical business module discovery path. It is correct and intentional. |
| P2 | [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php) | 89 | Comment: `// Load System catch-all route LAST (from Core, not app/Modules)` | **KEEP** — Informational comment, no code change needed. |
| P3 | [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php) | 142 | `base_path('app/Modules')` in `registerModuleConfigs()` | **DOCUMENT_AS_KNOWN_GAP** — Correctly scans business modules for dashboard/report configs. |
| P4 | [`src/Config/ui-library.php`](src/Config/ui-library.php) | 39 | `'business' => base_path('app/Modules')` | **DOCUMENT_AS_KNOWN_GAP** — Configurable path for business module discovery. |
| P5 | [`src/Services/System/ApplicationInfo.php`](src/Services/System/ApplicationInfo.php) | 55 | `File::directories(base_path('app/Modules'))` | **UPDATE** — Should use `config('ui-library.module_paths.business')` instead of hardcoded path. |
| P6 | [`src/Http/Livewire/AccessControls/ModuleSelector.php`](src/Http/Livewire/AccessControls/ModuleSelector.php) | 33 | `File::directories(base_path('app/Modules'))` | **UPDATE** — Should use `config('ui-library.module_paths.business')` instead of hardcoded path. |
| P7 | [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) | 11 | Comment: `Base path where module configs are stored (e.g., app/Modules)` | **KEEP** — Comment only. |
| P8 | [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) | 22 | `$this->basePath = app_path('Modules');` | **UPDATE** — This is the critical `ModelConfigRepository` fix. See [Section 2.6](#26-extend-modelconfigrepository-to-scan-srccore). |

#### 2.1.4 Undocumented HR Couplings in DataTable.php (CRITICAL)

| # | File | Line(s) | Code | Decision |
|---|------|---------|------|----------|
| C1 | [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) | 10 | `use App\Modules\Admin\Services\ActivityLogger;` | **UPDATE** — Extract behind a `ActivityLogger` contract or remove if logging is non-essential. The `ActivityLogger` is used for audit trail logging of data operations. |
| C2 | [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) | 12 | `use App\Modules\Admin\Services\AuthorizationService;` | **UPDATE** — Extract behind an `AuthorizationService` contract. This is used at line 84 (`protected AuthorizationService $authService;`) and line 130 (`$this->authService->canAccessView($user, $viewName)`). The library already has Spatie Permission integration; this should use a configurable authorization contract. |

#### 2.1.5 Remediation Steps for Section 2.1

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.1.1 | Make `getClassFromFile()` namespace prefix configurable | [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php:210-214) | Architect → Code | `grep -r "App\\\\Modules" src/Providers/ModuleServiceProvider.php` returns zero (except in config key default value) | `getClassFromFile()` reads namespace from `config('ui-library.module_paths.business_namespace', 'App\\Modules')` |
| 2.1.2 | Create `ModelDiscovery` service to replace hardcoded model scanning in AccessControl classes | New: [`src/Services/AccessControl/ModelDiscovery.php`](src/Services/AccessControl/); Modify: [`AccessControlManager.php`](src/Http/Livewire/AccessControls/AccessControlManager.php:118-119), [`AccessControlPermissionService.php`](src/Services/AccessControl/AccessControlPermissionService.php:71-72) | Architect → Code | `grep -r "App\\\\Modules" src/Http/Livewire/AccessControls/` returns zero; `grep -r "App\\\\Modules" src/Services/AccessControl/` returns zero | `ModelDiscovery` service scans both `src/Core/` and `app/Modules/` for model classes; both consumers use the service |
| 2.1.3 | Replace hardcoded `base_path('app/Modules')` with config lookup in ApplicationInfo and ModuleSelector | [`src/Services/System/ApplicationInfo.php`](src/Services/System/ApplicationInfo.php:55), [`src/Http/Livewire/AccessControls/ModuleSelector.php`](src/Http/Livewire/AccessControls/ModuleSelector.php:33) | Architect → Code | Both files use `config('ui-library.module_paths.business')` | Hardcoded paths replaced with config references |
| 2.1.4 | Decouple `DataTable` from `ActivityLogger` | [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:10) | Architect → Code | `grep "ActivityLogger" src/Http/Livewire/DataTables/DataTable.php` returns zero | Activity logging is either removed or uses a contract-based approach with a default no-op implementation |
| 2.1.5 | Decouple `DataTable` from `AuthorizationService` | [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:12,84,130) | Architect → Code | `grep "AuthorizationService" src/Http/Livewire/DataTables/DataTable.php` returns zero | Authorization check uses a contract (`DataTableAuthorizationProvider`) with a default implementation using Spatie Permission |

---

### 2.2 Audit and Fix Service Provider Bindings

#### 2.2.1 Current Binding Audit

All bindings in [`UILibraryServiceProvider::register()`](src/Providers/UILibraryServiceProvider.php:16-78) were audited:

| Binding | Line(s) | Status | Issue |
|---------|---------|--------|-------|
| `SettingsManager` | 28-40 | ✅ OK | Config-driven resolvers |
| `ModelConfigRepository` | 42 | ⚠️ NEEDS UPDATE | Only scans `app/Modules/` — see Section 2.6 |
| `ApprovalModelResolver` | 44-45 | ✅ OK | Contract → implementation |
| `WorkflowEngine` | 47 | ✅ OK | Singleton |
| `DocumentEngine` | 49 | ✅ OK | Singleton |
| `NotificationService` | 51-56 | ✅ OK | Channels pre-registered |
| `ReportEngine` | 58 | ✅ OK | Singleton |
| `ReferenceDataProvider` | 60-63 | ✅ OK | Contract → implementation |
| `CompanyProvider` | 65-68 | ✅ OK | Configurable, defaults to `NullCompanyProvider` |
| `path.public` | 71-77 | ✅ OK | Shared hosting compatibility |

#### 2.2.2 Core Module Boot Audit

The [`bootCoreModules()`](src/Providers/UILibraryServiceProvider.php:113-145) method only boots `Admin` and `System` (line 117). After Phase 4.1, `Organization` must be added. However, this is a Phase 4.1 concern, not a remediation concern.

**Finding**: The `bootCoreModules()` method correctly registers views, routes, and migrations for Admin and System. No changes needed for remediation.

#### 2.2.3 Missing Bindings

The following bindings are needed to support the decoupling fixes in Section 2.1:

| Binding | Purpose | Contract |
|---------|---------|----------|
| `DataTableAuthorizationProvider` | Replaces hardcoded `AuthorizationService` in DataTable | New contract: [`src/Contracts/DataTables/DataTableAuthorizationProvider.php`](src/Contracts/DataTables/) |
| `ActivityLogger` (optional) | Replaces hardcoded `ActivityLogger` in DataTable | New contract: [`src/Contracts/DataTables/ActivityLogger.php`](src/Contracts/DataTables/) or remove logging |

#### 2.2.4 Remediation Steps for Section 2.2

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.2.1 | Add `DataTableAuthorizationProvider` contract and default implementation | New: [`src/Contracts/DataTables/DataTableAuthorizationProvider.php`](src/Contracts/DataTables/); New: [`src/Services/DataTables/DefaultAuthorizationProvider.php`](src/Services/DataTables/); Modify: [`UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | Architect → Code | Contract has `canAccessView(User, string): bool` method; default implementation uses Spatie Permission | Contract exists, default implementation exists, binding registered in service provider |
| 2.2.2 | Decide on ActivityLogger: remove or create contract | [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) | Architect → Code | Decision documented in plan | If removed: no `ActivityLogger` references remain. If kept: contract exists with no-op default. |

---

### 2.3 Audit and Fix Config Key References

#### 2.3.1 Config File Audit

The [`src/Config/ui-library.php`](src/Config/ui-library.php) was audited for stale references:

| Config Key | Line(s) | Status | Issue |
|------------|---------|--------|-------|
| `modules.admin` | 12-20 | ✅ OK | Correctly configured |
| `modules.system` | 21-29 | ✅ OK | Correctly configured |
| `module_paths.core` | 38 | ✅ OK | Set at runtime by `UILibraryServiceProvider` |
| `module_paths.business` | 39 | ✅ OK | Points to `app/Modules` |
| `home_route` | 47 | ✅ OK | Configurable via env |
| `socialite` | 54-70 | ✅ OK | |
| `settings` | 77-86 | ✅ OK | |
| `navigation` | 93-107 | ✅ OK | |
| `approvals.models` | 135-142 | ✅ OK | Points to library models |
| `workflows.definitions` | 151-162 | ✅ OK | Empty by default |
| `documents` | 169-173 | ✅ OK | |
| `notifications` | 180-183 | ✅ OK | |
| `reports` | 190-198 | ✅ OK | |
| `reference_data` | 205-215 | ✅ OK | Phase 3.5 config |
| `features` | 222-229 | ✅ OK | |

**Finding**: No stale config keys found. The config is clean.

#### 2.3.2 Missing Config Keys

The following config keys are needed for the remediation:

| Config Key | Purpose | Location |
|------------|---------|----------|
| `module_paths.business_namespace` | Configurable namespace prefix for business modules (default: `App\Modules`) | [`src/Config/ui-library.php`](src/Config/ui-library.php) `module_paths` section |
| `datatables.authorization_provider` | FQCN for DataTable authorization provider | [`src/Config/ui-library.php`](src/Config/ui-library.php) new `datatables` section |

#### 2.3.3 Remediation Steps for Section 2.3

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.3.1 | Add `business_namespace` to `module_paths` config | [`src/Config/ui-library.php`](src/Config/ui-library.php:37-40) | Architect → Code | `config('ui-library.module_paths.business_namespace')` returns `'App\\Modules'` | Config key exists with sensible default |
| 2.3.2 | Add `datatables` config section with `authorization_provider` key | [`src/Config/ui-library.php`](src/Config/ui-library.php) | Architect → Code | `config('ui-library.datatables.authorization_provider')` returns FQCN | Config key exists with default implementation FQCN |

---

### 2.4 Complete `src/Core/Admin/` Module Skeleton

#### 2.4.1 Current State

Per the [discrepancy analysis](docs/architecture-discrepancy-analysis.md:100-118), Admin has **5 of 15 expected files (33%)**. All critical functional files are missing.

#### 2.4.2 What's Missing

| # | Missing File | Category | Priority for Remediation |
|---|-------------|----------|--------------------------|
| M1 | `Database/Migrations/create_admin_tables.php` | Migration | **DEFER** — Admin tables (users, roles) are typically provided by the consuming app or by Laravel's default migrations. The library should not own the `users` table. |
| M2 | `Http/Controllers/UserManagementController.php` | Controller | **DEFER** — Controllers are not needed if using config-driven DataTable pattern |
| M3 | `Http/Livewire/UserList.php` | Livewire | **DEFER** — The library's `DataTable` component handles listing |
| M4 | `Http/Livewire/UserForm.php` | Livewire | **DEFER** — The library's `DataTableForm` component handles forms |
| M5 | `Http/Livewire/RoleManager.php` | Livewire | **DEFER** — Spatie Permission handles roles |
| M6 | `Models/User.php` | Model | **DEFER** — The consuming app owns the User model |
| M7 | `Models/Role.php` | Model | **DEFER** — Spatie Permission owns the Role model |
| M8 | `Resources/views/users/index.blade.php` | View | **CREATE** — Entity view for users DataTable |
| M9 | `Resources/views/users/form.blade.php` | View | **DEFER** — DataTableForm handles forms via modal |
| M10 | `Resources/views/roles/index.blade.php` | View | **CREATE** — Entity view for roles DataTable |
| M11 | `Data/` directory | Data Config | **CREATE** — Critical for config-driven CRUD |

#### 2.4.3 Revised Admin Module Target

The original decoupling plan assumed Admin would have full MVC (Controllers, Livewire components, Models). However, the library's architecture is **config-driven**: DataTable, DataTableForm, and DataTableDetail render from PHP config files. Admin does NOT need custom Livewire components or controllers — it needs **Data configs** and **entity views**.

**Revised target structure for Admin**:

```
src/Core/Admin/
├── Config/
│   └── navigation.php                  ✅ EXISTS
├── Data/                               ← CREATE
│   ├── user.php                        # DataTable/Form/Detail config for User
│   └── role.php                        # DataTable/Form/Detail config for Role
├── Database/
│   └── Seeders/
│       ├── RoleSeeder.php              ✅ EXISTS
│       └── SuperAdminSeeder.php        ✅ EXISTS
├── Resources/
│   └── views/
│       ├── dashboard.blade.php         ✅ EXISTS
│       ├── users.blade.php             ← CREATE (embeds <livewire:qf.data-table config-key="admin.user" />)
│       └── roles.blade.php             ← CREATE (embeds <livewire:qf.data-table config-key="admin.role" />)
└── Routes/
    └── web.php                         ✅ EXISTS (routes already defined for users, roles, permissions)
```

**Key insight**: The Admin routes at [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php:10-20) already define routes for `/admin/users`, `/admin/roles`, and `/admin/permissions`. These routes return views that don't exist (`qf-core::admin.users`, `qf-core::admin.roles`, `qf-core::admin.permissions`). Creating the entity views will make these routes functional.

#### 2.4.4 Remediation Steps for Section 2.4

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.4.1 | Create `src/Core/Admin/Data/user.php` | New: [`src/Core/Admin/Data/user.php`](src/Core/Admin/Data/) | Architect → Code | `ModelConfigRepository::get('admin.user')` returns config array | Data config exists with `model`, `fieldDefinitions`, and standard DataTable/Form/Detail keys |
| 2.4.2 | Create `src/Core/Admin/Data/role.php` | New: [`src/Core/Admin/Data/role.php`](src/Core/Admin/Data/) | Architect → Code | `ModelConfigRepository::get('admin.role')` returns config array | Data config exists |
| 2.4.3 | Create `src/Core/Admin/Resources/views/users.blade.php` | New: [`src/Core/Admin/Resources/views/users.blade.php`](src/Core/Admin/Resources/views/) | Architect → Code | Visiting `/admin/users` renders the DataTable | View exists and embeds `<livewire:qf.data-table config-key="admin.user" />` |
| 2.4.4 | Create `src/Core/Admin/Resources/views/roles.blade.php` | New: [`src/Core/Admin/Resources/views/roles.blade.php`](src/Core/Admin/Resources/views/) | Architect → Code | Visiting `/admin/roles` renders the DataTable | View exists and embeds `<livewire:qf.data-table config-key="admin.role" />` |
| 2.4.5 | Create `src/Core/Admin/Resources/views/permissions.blade.php` | New: [`src/Core/Admin/Resources/views/permissions.blade.php`](src/Core/Admin/Resources/views/) | Architect → Code | Visiting `/admin/permissions` renders the DataTable | View exists and embeds `<livewire:qf.data-table config-key="admin.permission" />` |

---

### 2.5 Complete `src/Core/System/` Module Skeleton

#### 2.5.1 Current State

Per the [discrepancy analysis](docs/architecture-discrepancy-analysis.md:144-158), System has **5 of 9 expected files (56%)**. The `SystemSettings` Livewire component is missing, but `SetupWizard` and `System` model exist at the library level.

#### 2.5.2 What's Missing

| # | Missing File | Category | Priority for Remediation |
|---|-------------|----------|--------------------------|
| M1 | `Http/Livewire/SystemSettings.php` | Livewire | **CREATE** — Needed for system settings management UI |
| M2 | `Resources/views/settings/index.blade.php` | View | **CREATE** — Entity view for system settings |
| M3 | `Data/` directory | Data Config | **CREATE** — Critical for config-driven CRUD |

#### 2.5.3 Revised System Module Target

```
src/Core/System/
├── Config/
│   └── navigation.php                  ✅ EXISTS
├── Data/                               ← CREATE
│   └── system_setting.php              # DataTable/Form/Detail config for SystemSetting
├── Database/
│   ├── Migrations/
│   │   └── 2026_07_17_000000_create_systems_table.php  ✅ EXISTS
│   └── Seeders/
│       └── SystemSettingsSeeder.php    ✅ EXISTS
├── Resources/
│   └── views/
│       ├── dashboard.blade.php         ✅ EXISTS
│       └── settings.blade.php          ← CREATE (embeds <livewire:qf.data-table config-key="system.system_setting" />)
└── Routes/
    └── web.php                         ✅ EXISTS (with catch-all)
```

**Note**: The `SystemSettings` Livewire component is NOT needed if we use the config-driven DataTable pattern. The existing `SettingsPanel` component at [`src/Http/Livewire/Settings/SettingsPanel.php`](src/Http/Livewire/Settings/SettingsPanel.php) can handle settings rendering. The System module just needs a Data config and entity view.

#### 2.5.4 Remediation Steps for Section 2.5

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.5.1 | Create `src/Core/System/Data/system_setting.php` | New: [`src/Core/System/Data/system_setting.php`](src/Core/System/Data/) | Architect → Code | `ModelConfigRepository::get('system.system_setting')` returns config array | Data config exists with `model` pointing to `SystemSetting::class` |
| 2.5.2 | Create `src/Core/System/Resources/views/settings.blade.php` | New: [`src/Core/System/Resources/views/settings.blade.php`](src/Core/System/Resources/views/) | Architect → Code | Visiting `/system/settings` renders the DataTable | View exists and embeds `<livewire:qf.data-table config-key="system.system_setting" />` |

---

### 2.6 Extend `ModelConfigRepository` to Scan `src/Core/`

#### 2.6.1 Current State

The [`ModelConfigRepository`](src/Services/Config/ModelConfigRepository.php:22) hardcodes its base path to `app_path('Modules')`:

```php
// Line 22
$this->basePath = app_path('Modules');
```

And [`loadFromFile()`](src/Services/Config/ModelConfigRepository.php:95-112) constructs paths using only this single base path:

```php
// Line 105
$filePath = $this->basePath . '/' . $module . '/Data/' . $relativePath . '.php';
```

This means config keys like `admin.user` resolve to `app/Modules/Admin/Data/user.php` — which doesn't exist because Admin is now at `src/Core/Admin/`.

#### 2.6.2 Required Changes

The `ModelConfigRepository` must support **two base paths** with a resolution order:

1. **First**: Try `app/Modules/{Module}/Data/{file}.php` (business module override)
2. **Second**: Try `src/Core/{Module}/Data/{file}.php` (core module default)

This allows business modules to override Core module Data configs by placing a file at the same relative path in `app/Modules/`.

#### 2.6.3 Implementation Approach

```php
class ModelConfigRepository
{
    protected array $basePaths;

    public function __construct()
    {
        $this->basePaths = [
            app_path('Modules'),                                          // Business modules (higher priority)
            base_path('vendor/quicker-faster/ui-library/src/Core'),       // Core modules (fallback)
        ];
    }

    protected function loadFromFile(string $configKey): array
    {
        $parts = explode('.', $configKey);
        if (count($parts) < 2) {
            throw new \InvalidArgumentException("Invalid config key format: {$configKey}");
        }

        $module = ucfirst(array_shift($parts));
        $relativePath = implode(DIRECTORY_SEPARATOR, $parts);

        foreach ($this->basePaths as $basePath) {
            $filePath = $basePath . '/' . $module . '/Data/' . $relativePath . '.php';
            if (File::exists($filePath)) {
                return require $filePath;
            }
        }

        throw new \InvalidArgumentException(
            "Configuration not found for key: {$configKey}. " .
            "Searched paths: " . implode(', ', array_map(fn($p) => 
                $p . '/' . $module . '/Data/' . $relativePath . '.php', $this->basePaths))
        );
    }
}
```

#### 2.6.4 Remediation Steps for Section 2.6

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.6.1 | Refactor `ModelConfigRepository` to use multiple base paths | [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php:20-112) | Architect → Code | `ModelConfigRepository::get('admin.user')` resolves to `src/Core/Admin/Data/user.php`; `ModelConfigRepository::get('hr.employee')` still resolves to `app/Modules/Hr/Data/employee.php` | Repository scans both paths with correct resolution order |
| 2.6.2 | Add `core` path to `ModelConfigRepository` constructor | [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php:20-23) | Architect → Code | Constructor accepts configurable paths or uses sensible defaults | Core path is configurable via constructor or config |

---

### 2.7 Verify All Contracts Match Their Implementations

#### 2.7.1 Contract-Implementation Mapping

| Contract | Implementation | Status |
|----------|---------------|--------|
| [`Workflowable`](src/Contracts/Workflow/Workflowable.php) | Implemented by business models (not library) | ✅ OK — Contract is well-defined |
| [`Documentable`](src/Contracts/Documents/Documentable.php) | Implemented by business models (not library) | ✅ OK — Contract is well-defined |
| [`Notifiable`](src/Contracts/Notifications/Notifiable.php) | Implemented by business models (not library) | ✅ OK — Contract is well-defined |
| [`NotificationChannel`](src/Contracts/Notifications/NotificationChannel.php) | [`DatabaseChannel`](src/Services/Notifications/Channels/DatabaseChannel.php), [`MailChannel`](src/Services/Notifications/Channels/MailChannel.php) | ✅ OK — Both implement `send()` and `getName()` |
| [`Reportable`](src/Contracts/Reports/Reportable.php) | Implemented by business modules | ✅ OK — Contract is well-defined |
| [`FieldType`](src/Contracts/FieldTypes/FieldType.php) | 14 field types via `FieldFactory` | ✅ OK — All implement required methods |
| [`Widget`](src/Contracts/Widgets/Widget.php) | 19 widget processors | ✅ OK — All implement required methods |
| [`ModuleContract`](src/Contracts/Modules/ModuleContract.php) | No library implementation found | ⚠️ **GAP** — Contract exists but no library class implements it. Business modules are expected to implement it, but there's no default/base class. |
| [`NavigationProvider`](src/Contracts/Navigation/NavigationProvider.php) | No library implementation found | ⚠️ **GAP** — Contract exists but navigation is currently resolved from config files, not from a provider class. |
| [`SettingsProvider`](src/Contracts/Settings/SettingsProvider.php) | No library implementation found | ⚠️ **GAP** — Contract exists but `SettingsManager` uses its own resolver pattern, not this contract. |
| [`CompanyProvider`](src/Contracts/Navigation/CompanyProvider.php) | [`NullCompanyProvider`](src/Services/Navigation/NullCompanyProvider.php) | ✅ OK — Default no-op implementation exists |
| [`ApprovalModelResolver`](src/Contracts/Approvals/ApprovalModelResolver.php) | [`ApprovalModelResolver`](src/Services/Approvals/ApprovalModelResolver.php) | ✅ OK — Implementation exists |
| [`ReferenceDataProvider`](src/Contracts/ReferenceData/ReferenceDataProvider.php) | [`ReferenceDataService`](src/Services/ReferenceData/ReferenceDataService.php) | ✅ OK — Implementation exists |
| [`OnboardingCondition`](src/Contracts/OnboardingCondition.php) | Used as callable in config | ✅ OK — Contract is a single `__invoke` method |

#### 2.7.2 Gaps Found

| Gap | Contract | Severity | Recommendation |
|-----|----------|----------|----------------|
| G1 | `ModuleContract` | **LOW** | Create a `BaseModule` abstract class in `src/Modules/` that implements `ModuleContract` and reads from config. This is a Phase 4 concern, not a remediation blocker. |
| G2 | `NavigationProvider` | **LOW** | The current config-file-based navigation works. The `NavigationProvider` contract was designed for a future refactor (Phase 4.5). Document as "planned for Phase 4.5". |
| G3 | `SettingsProvider` | **LOW** | The `SettingsManager` uses its own resolver pattern. The `SettingsProvider` contract was designed for a future refactor. Document as "planned for future phase". |

#### 2.7.3 Remediation Steps for Section 2.7

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 2.7.1 | Document unimplemented contracts in blueprint | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) | Architect | Gaps G1-G3 are documented with planned resolution phases | Blueprint's contracts section notes which contracts are implemented vs planned |
| 2.7.2 | Verify `NotificationChannel` contract matches implementations | [`src/Contracts/Notifications/NotificationChannel.php`](src/Contracts/Notifications/NotificationChannel.php), [`DatabaseChannel.php`](src/Services/Notifications/Channels/DatabaseChannel.php), [`MailChannel.php`](src/Services/Notifications/Channels/MailChannel.php) | Architect → Code | Contract method signatures match implementation method signatures | All `NotificationChannel` implementations satisfy the contract |

---

## 3. Issue 2 — Document Phase 3.5 (Reference Data)

### 3.1 Write Phase 3.5 Documentation Content

#### 3.1.1 What Phase 3.5 Built

Phase 3.5 (Reference Data) was implemented as a **polymorphic key-value store** rather than the separate-table-per-type approach described in the original implementation plan. The actual implementation is simpler and more flexible:

| Component | File | Purpose |
|-----------|------|---------|
| **Contract** | [`ReferenceDataProvider`](src/Contracts/ReferenceData/ReferenceDataProvider.php) | Interface with 6 methods: `getAll()`, `getById()`, `getTypes()`, `create()`, `update()`, `delete()` |
| **Service** | [`ReferenceDataService`](src/Services/ReferenceData/ReferenceDataService.php) | Implements `ReferenceDataProvider`. Uses `Cache::remember()` with configurable TTL. Registered as singleton bound to the contract. |
| **Model** | [`ReferenceDataItem`](src/Models/ReferenceDataItem.php) | Single polymorphic model with columns: `type`, `key`, `value` (JSON), `meta` (JSON), `is_active`. Scopes: `ofType()`, `active()`. |
| **Migration** | [`2026_08_09_000002_create_reference_data_items_table.php`](Database/Migrations/2026_08_09_000002_create_reference_data_items_table.php) | Creates `reference_data_items` table with unique index on `[type, key]` |
| **Config** | [`ui-library.reference_data`](src/Config/ui-library.php:205-215) | Defines 6 types: countries, currencies, languages, timezones, payment_methods, document_types. Each with `label` and `icon`. |
| **Binding** | [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php:60-63) | `ReferenceDataProvider` → `ReferenceDataService` |

#### 3.1.2 Architecture Pattern

The Reference Data engine follows the same **Contract → Service → Model → Migration** pattern established by the other Phase 3 engines:

```
┌──────────────────────────────────────────────────────────────┐
│  REFERENCE DATA ARCHITECTURE                                  │
│  ─────────────────────────────                                │
│                                                               │
│  Contract: ReferenceDataProvider                              │
│  ├── getAll(string $type): Collection                         │
│  ├── getById(string $type, int|string $id): ?array            │
│  ├── getTypes(): array                                        │
│  ├── create(string $type, string $key, mixed $value,          │
│  │          array $meta = []): array                          │
│  ├── update(int|string $id, array $data): array               │
│  └── delete(int|string $id): bool                             │
│                                                               │
│  Service: ReferenceDataService implements ReferenceDataProvider│
│  ├── Uses Cache::remember() with configurable TTL             │
│  ├── Cache key pattern: "reference_data:{type}"               │
│  └── Auto-flushes cache on create/update/delete               │
│                                                               │
│  Model: ReferenceDataItem                                     │
│  ├── type: string (indexed)                                   │
│  ├── key: string                                              │
│  ├── value: json                                              │
│  ├── meta: json (nullable)                                    │
│  ├── is_active: boolean (default: true)                       │
│  └── Unique constraint: [type, key]                           │
│                                                               │
│  Config: ui-library.reference_data                            │
│  ├── cache_ttl: 3600                                          │
│  └── types: { countries, currencies, languages,               │
│              timezones, payment_methods, document_types }     │
└──────────────────────────────────────────────────────────────┘
```

#### 3.1.3 How It Differs from the Original Plan

The [implementation plan's Phase 3.5](docs/implementation-plan.md:665-727) described a different approach:

| Aspect | Original Plan | Actual Implementation | Why the Change |
|--------|--------------|----------------------|----------------|
| Data model | Separate tables per type (countries, currencies, languages, measurement_units) | Single polymorphic `reference_data_items` table | Simpler, more flexible — new types can be added via config without migrations |
| Location | `src/Core/ReferenceData/` as a Core module | `src/Services/ReferenceData/` as a service + `src/Models/ReferenceDataItem.php` | Follows the Phase 3 engine pattern (Contract → Service → Model) rather than the Core module pattern |
| Seeders | `CountrySeeder`, `CurrencySeeder`, `LanguageSeeder` | No seeders shipped | Seed data is the consuming app's responsibility; the library provides the storage mechanism |
| Navigation | Navigation config, routes, views | No UI — programmatic API only | Reference data is infrastructure, not user-facing |

#### 3.1.4 Documentation Content to Insert into Blueprint

The following content should be inserted into [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) as a new **Section 16: Phase 3.5 — Reference Data Engine**, placed after Section 15 (Phase 3.4: Scheduled Reports Engine) at approximately line 2888:

```markdown
## 16. Phase 3.5: Reference Data Engine

### 16.1 Overview

Polymorphic, cache-backed reference data engine. Provides a single unified store for slowly-changing lookup data (countries, currencies, languages, timezones, payment methods, document types). Any number of reference data types can be registered via config without additional migrations.

### 16.2 Architecture

- **Contract**: [`ReferenceDataProvider`](src/Contracts/ReferenceData/ReferenceDataProvider.php) — 6 methods: `getAll()`, `getById()`, `getTypes()`, `create()`, `update()`, `delete()`. Any service can implement this contract to provide reference data from an alternative source (API, external database, etc.).
- **Service**: [`ReferenceDataService`](src/Services/ReferenceData/ReferenceDataService.php) — default implementation using the `reference_data_items` table. Uses `Cache::remember()` with configurable TTL (`ui-library.reference_data.cache_ttl`, default: 3600s). Auto-flushes cache on create/update/delete.
- **Model**: [`ReferenceDataItem`](src/Models/ReferenceDataItem.php) — single polymorphic model. Columns: `type` (indexed string), `key` (string), `value` (JSON), `meta` (nullable JSON), `is_active` (boolean, default true). Unique constraint on `[type, key]`. Scopes: `ofType($type)`, `active()`.
- **Config**: `ui-library.reference_data` — `cache_ttl` (int), `types` (array of type keys with `label` and `icon`). Default types: `countries`, `currencies`, `languages`, `timezones`, `payment_methods`, `document_types`.
- **Migration**: [`2026_08_09_000002_create_reference_data_items_table.php`](Database/Migrations/2026_08_09_000002_create_reference_data_items_table.php)

### 16.3 Integration

- Registered as singleton in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php) — `ReferenceDataProvider` contract bound to `ReferenceDataService`
- Zero `App\Modules` references — fully decoupled
- **No dedicated Controller, Livewire component, or Blade views** — reference data is accessed programmatically; consuming apps build their own management UI if needed
- **No seeders shipped** — seed data (ISO countries, currencies, etc.) is the consuming app's responsibility

### 16.4 Usage Example

```php
use QuickerFaster\UILibrary\Contracts\ReferenceData\ReferenceDataProvider;

$refData = app(ReferenceDataProvider::class);

// Get all active countries
$countries = $refData->getAll('countries');

// Get a specific item
$item = $refData->getById('currencies', 1);

// Create a new reference data item
$refData->create('payment_methods', 'bank_transfer', 'Bank Transfer', [
    'requires_approval' => true,
]);

// Update an item
$refData->update(1, ['is_active' => false]);

// Delete an item
$refData->delete(1);

// Get all registered types
$types = $refData->getTypes(); // ['countries', 'currencies', 'languages', ...]

// Flush all reference data cache
$refData->flushCache();
```

### 16.5 Reference Data Items Table Schema

The [`reference_data_items`](Database/Migrations/2026_08_09_000002_create_reference_data_items_table.php) table:

| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `type` | string | Reference data type (e.g., `countries`, `currencies`). Indexed. |
| `key` | string | Unique key within the type (e.g., `US`, `NG`) |
| `value` | json | The primary value — flexible JSON for simple strings or complex objects |
| `meta` | json | Optional metadata (e.g., `{ "iso3": "USA", "phone_code": "+1" }`) |
| `is_active` | boolean | Soft enable/disable. Default: `true`. |
| `timestamps` | timestamp | `created_at`, `updated_at` |

**Unique constraint**: `[type, key]` — ensures no duplicate keys within a type.

### 16.6 Design Rationale

**Why a single polymorphic table instead of separate tables per type?**

1. **Extensibility without migrations**: Adding a new reference data type (e.g., `tax_codes`) requires only a config entry — no migration needed.
2. **Consistent API**: All types use the same `getAll()`, `create()`, `update()`, `delete()` methods.
3. **Simpler caching**: Single cache key pattern (`reference_data:{type}`) with unified flush strategy.
4. **Flexible value storage**: JSON columns allow simple values (`"United States"`) or complex objects (`{"name": "United States", "iso3": "USA", "phone_code": "+1"}`) in the same structure.

**Trade-off**: Loses relational integrity (no foreign keys to reference data items). This is acceptable because reference data is slowly-changing and typically cached. For relational reference data, business modules should use their own tables with proper foreign keys.
```

### 3.2 Update Blueprint Completed Phases List

#### 3.2.1 Current State

The blueprint's completed phases list at lines 2411-2416:

```markdown
**Completed Phases**:
- **Phase 2.5**: Decoupling (CompanyProvider, ApprovalModelResolver, migration relocation, HR-specific code removal)
- **Phase 3.1**: Workflow Engine
- **Phase 3.2**: Document Engine
- **Phase 3.3**: Notification Engine
- **Phase 3.4**: Scheduled Reports Engine
```

**Missing**: Phase 3.5 Reference Data Engine.

#### 3.2.2 Required Change

Add after line 2416:

```markdown
- **Phase 3.5**: Reference Data Engine ([`ReferenceDataService`](src/Services/ReferenceData/ReferenceDataService.php) + [`ReferenceDataProvider`](src/Contracts/ReferenceData/ReferenceDataProvider.php) contract + [`ReferenceDataItem`](src/Models/ReferenceDataItem.php) model)
```

Also update the architectural invariants section (lines 2418-2431) to add:

```markdown
- Reference data types are config-driven via `ui-library.reference_data.types`
- Reference data is cache-backed with configurable TTL via `ui-library.reference_data.cache_ttl`
```

### 3.3 Update Implementation Plan Task Table

#### 3.3.1 Current State

The implementation plan's summary table at line 1653 shows:

```markdown
| 3.5 | Reference Data module | P1 | 3 | Medium | — | ⬜ Not Started |
```

#### 3.3.2 Required Change

Update line 1653 to:

```markdown
| 3.5 | Reference Data module | P1 | 3 | Medium | — | ✅ Complete |
```

Also update the Phase 3 Summary table (lines 732-739) to mark 3.5 as complete.

### 3.4 Add Phase 3.5 to the Architecture Index

#### 3.4.1 Current State

The [`00-index.md`](docs/architecture/00-index.md) file `16-phase-history.md` description (lines 252-260) mentions:

> "Completed phases: Phase 2.5 decoupling (5 couplings resolved, new contracts, migration relocation, deleted items), Phase 3.1 Workflow Engine, Phase 3.2 Document Engine, Phase 3.3 Notification Engine, Phase 3.4 Scheduled Reports Engine."

**Missing**: Phase 3.5 Reference Data Engine.

#### 3.4.2 Required Change

Update line 253 to include Phase 3.5:

```markdown
**What it covers**: Completed phases: Phase 2.5 decoupling (5 couplings resolved, new contracts, migration relocation, deleted items), Phase 3.1 Workflow Engine, Phase 3.2 Document Engine, Phase 3.3 Notification Engine, Phase 3.4 Scheduled Reports Engine, Phase 3.5 Reference Data Engine.
```

Also update the cross-reference table (lines 393-408) to add:

```markdown
| Reference Data engine | [`09-engines-and-services.md`](09-engines-and-services.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md) |
```

#### 3.4.3 Remediation Steps for Section 3

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 3.1 | Insert Phase 3.5 documentation into blueprint | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) (after line 2888) | Architect | Blueprint has Section 16 titled "Phase 3.5: Reference Data Engine" | Section exists with all subsections (Overview, Architecture, Integration, Usage Example, Schema, Design Rationale) |
| 3.2 | Update blueprint completed phases list | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2411-2416) | Architect | Completed phases list includes Phase 3.5 | Line item for Phase 3.5 present in completed phases |
| 3.3 | Update blueprint architectural invariants | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2418-2431) | Architect | Invariants include reference data entries | Two new invariant lines added |
| 3.4 | Update implementation plan task table | [`docs/implementation-plan.md`](docs/implementation-plan.md:1653) | Architect | Task 3.5 shows "✅ Complete" | Status updated from "⬜ Not Started" to "✅ Complete" |
| 3.5 | Update implementation plan Phase 3 summary | [`docs/implementation-plan.md`](docs/implementation-plan.md:732-739) | Architect | Phase 3 summary reflects 3.5 completion | Summary table updated |
| 3.6 | Update 00-index.md phase history description | [`docs/architecture/00-index.md`](docs/architecture/00-index.md:253) | Architect | Description includes Phase 3.5 | Text updated |
| 3.7 | Update 00-index.md cross-reference table | [`docs/architecture/00-index.md`](docs/architecture/00-index.md:393-408) | Architect | Cross-reference includes Reference Data engine | Row added |

---

## 4. Issue 3 — Fix Blueprint and Index

### 4.1 Verify and Fix `docs/architecture/00-index.md`

#### 4.1.1 Accuracy Audit

| Aspect | Status | Issue |
|--------|--------|-------|
| File map (lines 26-46) | ⚠️ **INACCURATE** | Lists 17 files that don't exist. The index is aspirational — it describes a proposed restructuring, not the current state. |
| Topic file descriptions (lines 50-281) | ⚠️ **MISLEADING** | Each description says "Source: Blueprint Section X" — implying the topic files exist, but they don't. |
| Reading order (lines 284-308) | ⚠️ **MISLEADING** | References files that don't exist. |
| Relationship map (lines 312-387) | ✅ ACCURATE | The flow diagram correctly describes the actual architecture. |
| Cross-reference quick links (lines 391-408) | ⚠️ **INCOMPLETE** | Missing Reference Data engine entry. |
| Related documents (lines 412-421) | ✅ ACCURATE | All referenced documents exist. |
| Implementation notes (lines 425-465) | ⚠️ **ASPIRATIONAL** | Describes work to be done, not work completed. |

#### 4.1.2 Required Fixes

1. **Add a clear status banner** at the top of the index:

```markdown
> **⚠️ STATUS: PROPOSED — NOT YET IMPLEMENTED**
> 
> This index describes a **proposed restructuring** of the architecture documentation. The 17 topic files referenced below **do not exist yet**. The canonical architecture documentation is currently in the monolithic [`docs/ai-optimized-architecture-blueprint.md`](../ai-optimized-architecture-blueprint.md) (~2,888 lines).
> 
> **Current state**: Only this index file (`00-index.md`) exists. All other files in the `docs/architecture/` directory are planned but not created.
> 
> **To read the architecture documentation now**: See [`docs/ai-optimized-architecture-blueprint.md`](../ai-optimized-architecture-blueprint.md).
```

2. **Update the file map** to mark each file's actual status:

```
docs/architecture/
├── 00-index.md                          ← ✅ EXISTS: Master index (PROPOSED restructuring)
├── 01-core-concepts.md                  # ❌ NOT CREATED
├── 02-directory-map.md                  # ❌ NOT CREATED
├── 03-module-pattern.md                 # ❌ NOT CREATED
... (all 16 topic files marked as NOT CREATED)
└── 17-view-config-routing-interplay.md  # ❌ NOT CREATED
```

3. **Add Phase 3.5** to the `16-phase-history.md` description (see Section 3.4).

4. **Add Reference Data** to the cross-reference table (see Section 3.4).

#### 4.1.3 Remediation Steps for Section 4.1

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 4.1.1 | Add status banner to 00-index.md | [`docs/architecture/00-index.md`](docs/architecture/00-index.md) (after line 7) | Architect | Banner clearly states the index is proposed, not implemented | Status banner present and accurate |
| 4.1.2 | Mark all topic files as NOT CREATED in file map | [`docs/architecture/00-index.md`](docs/architecture/00-index.md:26-46) | Architect | Each file shows "❌ NOT CREATED" | File map accurately reflects reality |
| 4.1.3 | Add Phase 3.5 to phase history description | [`docs/architecture/00-index.md`](docs/architecture/00-index.md:253) | Architect | Description includes Phase 3.5 | Text updated (same as step 3.6) |
| 4.1.4 | Add Reference Data to cross-reference table | [`docs/architecture/00-index.md`](docs/architecture/00-index.md:393-408) | Architect | Cross-reference includes Reference Data | Row added (same as step 3.7) |

---

### 4.2 Update Blueprint Phase Statuses to Reflect Reality

#### 4.2.1 Current Inaccuracies

| Blueprint Section | Line(s) | Issue | Fix |
|-------------------|---------|-------|-----|
| Completed Phases list | 2411-2416 | Missing Phase 3.5 | Add Phase 3.5 entry (see Section 3.2) |
| Architectural invariants | 2418-2431 | Missing reference data invariants | Add reference data invariants (see Section 3.2) |
| Section 11 (Phase 2.5) | 2541-2613 | States "~50 broader references remain" — some of these are the DataTable HR couplings found in this audit | Add a note about the newly discovered DataTable couplings (C1, C2) and that they are addressed in this remediation plan |
| "The library never imports from `App\Modules\*`" | 2419 | **FALSE** — DataTable.php still imports `App\Modules\Admin\Services\*` | Update to: "The library never imports from `App\Modules\*` (except for 2 known couplings in DataTable.php addressed in the Pre-Phase 4 Remediation Plan)" |

#### 4.2.2 Remediation Steps for Section 4.2

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 4.2.1 | Add Phase 3.5 to completed phases list | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2411-2416) | Architect | Phase 3.5 listed | Same as step 3.2 |
| 4.2.2 | Add reference data invariants | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2418-2431) | Architect | Invariants include reference data | Same as step 3.3 |
| 4.2.3 | Update "never imports from App\Modules" invariant | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2419) | Architect | Invariant acknowledges known exceptions | Text updated with link to remediation plan |
| 4.2.4 | Add DataTable couplings to Phase 2.5 remaining items | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:2602-2612) | Architect | Section 11.8 lists DataTable couplings | Two new entries added to remaining items table |

---

### 4.3 Validate All Cross-References Between Documents

#### 4.3.1 Cross-Reference Matrix

| From Document | References | Status |
|---------------|-----------|--------|
| [`architecture-discrepancy-analysis.md`](docs/architecture-discrepancy-analysis.md) | Blueprint, decoupling plan, Phase 4.1 spec, implementation plan, source files | ✅ All valid |
| [`decoupling-migration-plan.md`](docs/decoupling-migration-plan.md) | Source files | ✅ All valid |
| [`00-index.md`](docs/architecture/00-index.md) | 17 topic files, blueprint, related docs | ⚠️ 17 topic files don't exist |
| [`phase-4.1-organization-extraction-spec.md`](docs/phase-4.1-organization-extraction-spec.md) | Blueprint, discrepancy analysis, source files | ✅ All valid |
| [`implementation-plan.md`](docs/implementation-plan.md) | Source files, gap analysis | ✅ All valid |
| [`ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) | Source files | ✅ All valid |

#### 4.3.2 Remediation Steps for Section 4.3

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 4.3.1 | Verify all source file links in blueprint are valid | [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) | Architect | All `[text](src/...)` links point to existing files | No broken source links |
| 4.3.2 | Verify all document cross-references are valid | All docs in `docs/` and `docs/architecture/` | Architect | All `[text](../file.md)` links point to existing files | No broken document links |

---

### 4.4 Decision on 17-File Split

#### 4.4.1 Analysis

The proposed 17-file split of the blueprint is a **worthwhile long-term goal** but is **not a prerequisite for Phase 4.1**. The current monolithic blueprint (~2,888 lines) is functional and accurate (after the fixes in this plan). The split would:

- **Benefit**: Make documentation more AI-friendly (smaller context windows)
- **Benefit**: Make documentation more maintainable (single topic per file)
- **Cost**: ~3,670 lines of new files to create, cross-reference, and maintain
- **Risk**: The split could introduce inconsistencies if not done carefully

#### 4.4.2 Decision: **DEFER with Preparation**

The 17-file split should be **deferred** until after Phase 4.1. However, the `00-index.md` should be updated now to accurately reflect the current state (see Section 4.1) so it doesn't mislead readers.

**Rationale**:
1. The split is documentation work, not code work — it doesn't block Phase 4.1
2. Phase 4.1 will add Organization module documentation that would need to be incorporated into the split files
3. Doing the split after Phase 4.1 means the topic files can include Organization from the start
4. The current monolithic blueprint is functional and accurate after the fixes in this plan

#### 4.4.3 Recommended Approach for Future Split

When the split is undertaken (suggested as Phase 4.6a):

1. Create all 17 files by extracting from the blueprint
2. Add Organization-specific content to relevant files (02-directory-map, 03-module-pattern, 05-data-configs)
3. Mark the original blueprint as "SUPERSEDED" with a link to the index
4. Update all cross-references

#### 4.4.4 Remediation Steps for Section 4.4

| Step | Action | Affected File(s) | Owner | Validation Check | Definition of Done |
|------|--------|-----------------|-------|------------------|--------------------|
| 4.4.1 | Document the deferral decision in 00-index.md | [`docs/architecture/00-index.md`](docs/architecture/00-index.md) | Architect | Index explains that split is deferred | Implementation notes section updated with deferral rationale |
| 4.4.2 | Add "Post-Phase 4.1" note to implementation notes | [`docs/architecture/00-index.md`](docs/architecture/00-index.md:425-465) | Architect | Notes reflect deferred status | Text updated |

---

## 5. Execution Sequence

### 5.1 Ordered Steps by Dependency

Steps are listed in execution order. Steps within the same dependency level can be parallelized.

| Seq | Step ID | Description | Issue | Effort | Depends On | Parallel Group |
|-----|---------|-------------|-------|--------|------------|----------------|
| 1 | 2.6.1 | Refactor `ModelConfigRepository` to use multiple base paths | 1 | Medium | — | A |
| 2 | 2.6.2 | Add `core` path to `ModelConfigRepository` constructor | 1 | Tiny | 2.6.1 | A |
| 3 | 2.3.1 | Add `business_namespace` to `module_paths` config | 1 | Tiny | — | A |
| 4 | 2.3.2 | Add `datatables` config section | 1 | Tiny | — | A |
| 5 | 2.1.1 | Make `getClassFromFile()` namespace prefix configurable | 1 | Tiny | 2.3.1 | B |
| 6 | 2.1.2 | Create `ModelDiscovery` service | 1 | Medium | 2.3.1 | B |
| 7 | 2.1.3 | Replace hardcoded `base_path('app/Modules')` in ApplicationInfo and ModuleSelector | 1 | Tiny | 2.3.1 | B |
| 8 | 2.2.1 | Add `DataTableAuthorizationProvider` contract and default implementation | 1 | Small | — | B |
| 9 | 2.2.2 | Decide on ActivityLogger: remove or create contract | 1 | Tiny | — | B |
| 10 | 2.1.4 | Decouple `DataTable` from `ActivityLogger` | 1 | Small | 2.2.2 | C |
| 11 | 2.1.5 | Decouple `DataTable` from `AuthorizationService` | 1 | Small | 2.2.1 | C |
| 12 | 2.4.1 | Create `src/Core/Admin/Data/user.php` | 1 | Small | 2.6.1 | D |
| 13 | 2.4.2 | Create `src/Core/Admin/Data/role.php` | 1 | Small | 2.6.1 | D |
| 14 | 2.5.1 | Create `src/Core/System/Data/system_setting.php` | 1 | Small | 2.6.1 | D |
| 15 | 2.4.3 | Create `src/Core/Admin/Resources/views/users.blade.php` | 1 | Tiny | 2.4.1 | E |
| 16 | 2.4.4 | Create `src/Core/Admin/Resources/views/roles.blade.php` | 1 | Tiny | 2.4.2 | E |
| 17 | 2.4.5 | Create `src/Core/Admin/Resources/views/permissions.blade.php` | 1 | Tiny | 2.4.1 | E |
| 18 | 2.5.2 | Create `src/Core/System/Resources/views/settings.blade.php` | 1 | Tiny | 2.5.1 | E |
| 19 | 2.7.1 | Document unimplemented contracts in blueprint | 1 | Tiny | — | F |
| 20 | 2.7.2 | Verify `NotificationChannel` contract matches implementations | 1 | Tiny | — | F |
| 21 | 3.1 | Insert Phase 3.5 documentation into blueprint | 2 | Small | — | F |
| 22 | 3.2 | Update blueprint completed phases list | 2 | Tiny | 3.1 | F |
| 23 | 3.3 | Update blueprint architectural invariants | 2 | Tiny | 3.1 | F |
| 24 | 3.4 | Update implementation plan task table (3.5 status) | 2 | Tiny | — | F |
| 25 | 3.5 | Update implementation plan Phase 3 summary | 2 | Tiny | 3.4 | F |
| 26 | 3.6 | Update 00-index.md phase history description | 2 | Tiny | — | F |
| 27 | 3.7 | Update 00-index.md cross-reference table | 2 | Tiny | — | F |
| 28 | 4.1.1 | Add status banner to 00-index.md | 3 | Tiny | — | F |
| 29 | 4.1.2 | Mark all topic files as NOT CREATED in file map | 3 | Tiny | — | F |
| 30 | 4.1.3 | Add Phase 3.5 to phase history (same as 3.6) | 3 | — | — | (merged with 3.6) |
| 31 | 4.1.4 | Add Reference Data to cross-reference (same as 3.7) | 3 | — | — | (merged with 3.7) |
| 32 | 4.2.1 | Add Phase 3.5 to completed phases (same as 3.2) | 3 | — | — | (merged with 3.2) |
| 33 | 4.2.2 | Add reference data invariants (same as 3.3) | 3 | — | — | (merged with 3.3) |
| 34 | 4.2.3 | Update "never imports from App\Modules" invariant | 3 | Tiny | — | F |
| 35 | 4.2.4 | Add DataTable couplings to Phase 2.5 remaining items | 3 | Tiny | — | F |
| 36 | 4.3.1 | Verify all source file links in blueprint | 3 | Tiny | — | F |
| 37 | 4.3.2 | Verify all document cross-references | 3 | Tiny | — | F |
| 38 | 4.4.1 | Document deferral decision in 00-index.md | 3 | Tiny | — | F |
| 39 | 4.4.2 | Add "Post-Phase 4.1" note to implementation notes | 3 | Tiny | — | F |

### 5.2 Parallel Execution Groups

| Group | Steps | Total Effort | Description |
|-------|-------|-------------|-------------|
| **A** | 1, 2, 3, 4 | Medium + 3×Tiny | Foundation: ModelConfigRepository + config keys. Must complete before B, C, D. |
| **B** | 5, 6, 7, 8, 9 | Medium + Small + 3×Tiny | Decoupling: Service providers, ModelDiscovery, Authorization contract. Can run in parallel with A if using feature flags. |
| **C** | 10, 11 | 2×Small | DataTable decoupling. Depends on B (contracts must exist first). |
| **D** | 12, 13, 14 | 3×Small | Data configs for Admin and System. Depends on A (ModelConfigRepository must scan src/Core/). |
| **E** | 15, 16, 17, 18 | 4×Tiny | Entity views. Depends on D (Data configs must exist for views to reference). |
| **F** | 19-39 | 21×Tiny | Documentation updates. Can run entirely in parallel with all other groups. |

### 5.3 Effort Summary

| Effort Level | Count | Steps |
|-------------|-------|-------|
| Tiny | 28 | 2, 3, 4, 5, 7, 9, 15, 16, 17, 18, 19, 20, 22, 23, 24, 25, 26, 27, 28, 29, 34, 35, 36, 37, 38, 39 |
| Small | 9 | 8, 10, 11, 12, 13, 14, 21 |
| Medium | 2 | 1, 6 |
| Large | 0 | — |

---

## 6. Pre-Phase 4.1 Readiness Checklist

Each item must be **objectively verifiable** before Phase 4.1 implementation can begin.

### 6.1 Code Quality Gates

| # | Check | Verification Command | Must Return |
|---|-------|---------------------|-------------|
| CQ-1 | Zero `App\Modules` namespace references in library code (excluding config defaults and business module discovery) | `grep -r "App\\\\Modules" src/ --include="*.php" \| grep -v "ModuleServiceProvider.php\|Config/ui-library.php"` | Empty (or only config defaults) |
| CQ-2 | `DataTable.php` has no HR couplings | `grep -r "ActivityLogger\|AuthorizationService" src/Http/Livewire/DataTables/DataTable.php` | Empty |
| CQ-3 | `ModelConfigRepository` scans both `app/Modules/` and `src/Core/` | `grep -c "basePaths\|base_path.*Core" src/Services/Config/ModelConfigRepository.php` | At least 1 match for multi-path support |
| CQ-4 | `ModelConfigRepository::get('admin.user')` resolves successfully | Unit test or manual verification | Returns config array (not `InvalidArgumentException`) |
| CQ-5 | `ModelConfigRepository::get('system.system_setting')` resolves successfully | Unit test or manual verification | Returns config array (not `InvalidArgumentException`) |
| CQ-6 | `ApplicationInfo` and `ModuleSelector` use config for business module path | `grep "base_path.*app/Modules" src/Services/System/ApplicationInfo.php src/Http/Livewire/AccessControls/ModuleSelector.php` | Empty (both use `config('ui-library.module_paths.business')`) |

### 6.2 File Existence Gates

| # | Check | Expected File |
|---|-------|---------------|
| FE-1 | Admin Data config for User exists | [`src/Core/Admin/Data/user.php`](src/Core/Admin/Data/) |
| FE-2 | Admin Data config for Role exists | [`src/Core/Admin/Data/role.php`](src/Core/Admin/Data/) |
| FE-3 | System Data config for SystemSetting exists | [`src/Core/System/Data/system_setting.php`](src/Core/System/Data/) |
| FE-4 | Admin users view exists | [`src/Core/Admin/Resources/views/users.blade.php`](src/Core/Admin/Resources/views/) |
| FE-5 | Admin roles view exists | [`src/Core/Admin/Resources/views/roles.blade.php`](src/Core/Admin/Resources/views/) |
| FE-6 | Admin permissions view exists | [`src/Core/Admin/Resources/views/permissions.blade.php`](src/Core/Admin/Resources/views/) |
| FE-7 | System settings view exists | [`src/Core/System/Resources/views/settings.blade.php`](src/Core/System/Resources/views/) |
| FE-8 | `DataTableAuthorizationProvider` contract exists | [`src/Contracts/DataTables/DataTableAuthorizationProvider.php`](src/Contracts/DataTables/) |
| FE-9 | `DataTableAuthorizationProvider` default implementation exists | [`src/Services/DataTables/DefaultAuthorizationProvider.php`](src/Services/DataTables/) |
| FE-10 | `ModelDiscovery` service exists | [`src/Services/AccessControl/ModelDiscovery.php`](src/Services/AccessControl/) |

### 6.3 Documentation Gates

| # | Check | Expected State |
|---|-------|---------------|
| DC-1 | Blueprint has Phase 3.5 section | Section 16 exists in [`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md) |
| DC-2 | Blueprint completed phases list includes Phase 3.5 | Line item present at lines 2411-2416 |
| DC-3 | Implementation plan shows Phase 3.5 as complete | Task 3.5 status is "✅ Complete" at line 1653 |
| DC-4 | 00-index.md has status banner | Banner at top of file stating the index is proposed |
| DC-5 | 00-index.md file map shows accurate file statuses | Each topic file marked as "❌ NOT CREATED" |
| DC-6 | Blueprint invariant updated for DataTable couplings | Line 2419 acknowledges known exceptions |
| DC-7 | All cross-references between documents are valid | No broken links in any doc file |

### 6.4 Config Gates

| # | Check | Expected State |
|---|-------|---------------|
| CF-1 | `module_paths.business_namespace` config key exists | `config('ui-library.module_paths.business_namespace')` returns `'App\\Modules'` |
| CF-2 | `datatables.authorization_provider` config key exists | `config('ui-library.datatables.authorization_provider')` returns a valid FQCN |

### 6.5 Contract Gates

| # | Check | Expected State |
|---|-------|---------------|
| CT-1 | All contracts have either an implementation or a documented gap | Gaps G1-G3 documented in blueprint |
| CT-2 | `NotificationChannel` implementations match contract | `DatabaseChannel` and `MailChannel` implement all contract methods |
| CT-3 | `ReferenceDataProvider` contract matches `ReferenceDataService` implementation | All 6 contract methods are implemented |

---

> **Document Version**: 1.0  
> **Next Step**: Review and approval → switch to Code mode for sequential execution of Groups A through F