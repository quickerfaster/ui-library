# Phase 4.1: Extract Organization into Core — Implementation Specification

> **Status**: ✅ IMPLEMENTED — 2026-08-09
> **Date**: 2026-08-09
> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\Core\Organization\`
> **Risk Level**: HIGH — Highest cascade impact of any Phase 4 task
> **Amendments**: Added Sections 2.2 (Views & Data Configs), 2.3 (Catch-All Routing), 5.1a (Data Configs), 5.1b (Entity Views), 12 (Pre-Extraction Checklist)
> **Implementation Notes**: Organization module extracted to [`src/Core/Organization/`](src/Core/Organization/) with 7 models (Company, Branch, Department, Division, BusinessUnit, Location, Team), 7 migrations, navigation config, routes, and seeders. The `ModelConfigRepository` was extended to scan `src/Core/` for Data configs. See [`docs/pre-phase-4-remediation-plan.md`](docs/pre-phase-4-remediation-plan.md) for the remediation steps completed before extraction.

---

## 1. Overview

### 1.1 What This Phase Accomplishes

Phase 4.1 moves the **Organization module** from the HR application (`app/Modules/Organization/`) into the UI library as a first-class Core module at [`src/Core/Organization/`](src/Core/Organization/). Organization owns the foundational entity graph that every business module depends on:

```
Company ──┬── Branch
          ├── Department ── Division
          ├── Business Unit
          ├── Location
          └── Team
```

### 1.2 Why This Matters

Organization is the **single most shared dependency** across all business applications. Per the architecture blueprint:

> "Everything depends on Organization. Nothing depends on HR except HR."

Currently, HR, Payroll, Time, and Leave all import `App\Modules\Organization\Models\*`. This creates a hard dependency on the HR app that violates the library's architectural invariant: **the library never imports from `App\Modules\*`**.

By extracting Organization into the library:

- Consuming apps reference `QuickerFaster\UILibrary\Core\Organization\Models\Company` instead of `App\Modules\Organization\Models\Company`
- The library owns the canonical Organization schema, migrations, and seeders
- Business modules (HR, Payroll, Time, Leave) become consumers of a shared foundation rather than owners of it
- Multi-tenancy via `company_id` is standardized at the library level

### 1.3 Architectural Context

This extraction follows the **Phase 3 contract-driven pattern** established by Workflow, Documents, Notifications, and Reports engines. However, Organization differs from those services in one critical way: **Organization entities are Eloquent models with concrete database tables**, not abstract engines. They do not need a `Contract` + `Engine` pattern — they follow the simpler **Core module pattern** established by [`src/Core/System/`](src/Core/System/) and [`src/Core/Admin/`](src/Core/Admin/).

**CRITICAL AMENDMENT (2026-08-09)**: The existing Core modules (Admin, System) are **incomplete skeletons** — they lack `Data/` configs, entity views, and functional Livewire components. See [`docs/architecture-discrepancy-analysis.md`](docs/architecture-discrepancy-analysis.md) for the full gap inventory. This amended spec ensures Organization does NOT repeat those omissions. Organization will be the **first fully functional Core module** with complete Data configs, entity views, and CRUD capability, establishing the pattern for retrofitting Admin and System.

---

## 2. Target Directory Structure

### 2.1 Complete Structure

Following the [`src/Core/System/`](src/Core/System/) pattern exactly, with additions from [`src/Core/Admin/`](src/Core/Admin/) where relevant, **plus the Data config and entity view additions mandated by this amendment**:

```
src/Core/Organization/
├── Config/
│   └── navigation.php                  # Navigation: context_groups, contexts, layout
├── Data/                               # ← NEW: Config-driven data definitions
│   ├── company.php                     # DataTable/Form/Detail config for Company
│   ├── branch.php                      # DataTable/Form/Detail config for Branch
│   ├── department.php                  # DataTable/Form/Detail config for Department
│   ├── division.php                    # DataTable/Form/Detail config for Division
│   ├── business_unit.php               # DataTable/Form/Detail config for BusinessUnit
│   ├── location.php                    # DataTable/Form/Detail config for Location
│   └── team.php                        # DataTable/Form/Detail config for Team
├── Database/
│   ├── Migrations/
│   │   ├── 2026_08_09_000001_create_companies_table.php
│   │   ├── 2026_08_09_000002_create_branches_table.php
│   │   ├── 2026_08_09_000003_create_departments_table.php
│   │   ├── 2026_08_09_000004_create_divisions_table.php
│   │   ├── 2026_08_09_000005_create_business_units_table.php
│   │   ├── 2026_08_09_000006_create_locations_table.php
│   │   └── 2026_08_09_000007_create_teams_table.php
│   └── Seeders/
│       └── OrganizationDemoSeeder.php  # Demo data for development
├── Models/
│   ├── Company.php                     # Top-level tenant/org entity
│   ├── Branch.php                      # Physical or logical branch
│   ├── Department.php                  # Organizational department
│   ├── Division.php                    # Sub-division of departments
│   ├── BusinessUnit.php                # Business unit grouping
│   ├── Location.php                    # Physical location/office
│   └── Team.php                        # Cross-cutting team structure
├── Resources/
│   └── views/
│       ├── dashboard.blade.php         # Organization dashboard
│       ├── companies.blade.php         # ← NEW: Company DataTable view
│       ├── branches.blade.php          # ← NEW: Branch DataTable view
│       ├── departments.blade.php       # ← NEW: Department DataTable view
│       ├── divisions.blade.php         # ← NEW: Division DataTable view
│       ├── business_units.blade.php    # ← NEW: BusinessUnit DataTable view
│       ├── locations.blade.php         # ← NEW: Location DataTable view
│       └── teams.blade.php             # ← NEW: Team DataTable view
└── Routes/
    └── web.php                         # Organization routes
```

**Comparison with existing Core modules:**

| Directory | System | Admin | Organization (AMENDED) |
|-----------|--------|-------|----------------------|
| `Config/navigation.php` | ✅ | ✅ | ✅ |
| `Data/` | ❌ | ❌ | ✅ (7 configs) |
| `Database/Migrations/` | ✅ (1) | — | ✅ (7) |
| `Database/Seeders/` | ✅ (1) | ✅ (2) | ✅ (1) |
| `Models/` | — (uses `src/Models/`) | — | ✅ (7) |
| `Resources/views/` | ✅ (1) | ✅ (1) | ✅ (8: dashboard + 7 entity) |
| `Routes/web.php` | ✅ | ✅ | ✅ |
| `Http/Controllers/` | — | — | — |
| `Http/Livewire/` | — | — | — |

**Key decision**: Organization models live in `src/Core/Organization/Models/`, NOT in `src/Models/`. This follows the Core module self-containment pattern. The `src/Models/` directory is reserved for cross-cutting library models (Document, Workflow, Notification, Export, etc.).

### 2.2 Views and Data Configs Handling (NEW SECTION)

#### 2.2.1 Why Data Configs Are Required

The library's architecture is **config-driven**. The [`DataTable`](src/Http/Livewire/DataTables/DataTable.php), [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php), and [`DataTableDetail`](src/Http/Livewire/DataTables/DataTableDetail.php) components render entirely from PHP config files. Without `Data/` configs, Organization entities will have:

- ✅ Database tables (migrations create them)
- ✅ Eloquent models (PHP classes exist)
- ✅ Routes (URLs resolve)
- ❌ **No CRUD UI** — DataTable has no config to render
- ❌ **No forms** — DataTableForm has no field definitions
- ❌ **No detail views** — DataTableDetail has no sections to display

#### 2.2.2 Data Config Resolution for Core Modules

**Current limitation**: The [`ModelConfigRepository`](src/Services/Config/ModelConfigRepository.php) resolves config keys to file paths using only `app/Modules/` as the base path:

```php
// Current: only scans business modules
$filePath = app_path('Modules') . '/' . $module . '/Data/' . $relativePath . '.php';
```

**Required change for Phase 4.1**: The `ModelConfigRepository` must be extended to also scan `src/Core/`. Resolution order:

1. Try `app/Modules/{Module}/Data/{file}.php` (business module — for backward compatibility)
2. Fall back to `src/Core/{Module}/Data/{file}.php` (core module)

This change is a **prerequisite** for Phase 4.1. Without it, `<livewire:qf.data-table config-key="organization.company" />` will throw `InvalidArgumentException` because the repository cannot find the config file.

**Implementation approach** (in [`ModelConfigRepository`](src/Services/Config/ModelConfigRepository.php)):

```php
protected function resolveConfigPath(string $module, string $relativePath): ?string
{
    // 1. Try business module path
    $businessPath = app_path("Modules/{$module}/Data/{$relativePath}.php");
    if (file_exists($businessPath)) {
        return $businessPath;
    }
    
    // 2. Try core module path
    $corePath = base_path("vendor/quicker-faster/ui-library/src/Core/{$module}/Data/{$relativePath}.php");
    if (file_exists($corePath)) {
        return $corePath;
    }
    
    return null;
}
```

#### 2.2.3 Entity View Pattern

Each entity gets a Blade view that embeds the DataTable component. Following the blueprint pattern ([`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:1796)):

```blade
{{-- src/Core/Organization/Resources/views/companies.blade.php --}}
<x-layout configKey="organization_company" moduleName="organization">
    <livewire:qf.data-table config-key="organization.company" />
</x-layout>
```

This pattern:
- Uses the `<x-layout>` component for consistent page shell
- Passes `configKey` for dashboard widget resolution
- Passes `moduleName` for navigation context
- Embeds `<livewire:qf.data-table>` with the dot-notation config key

#### 2.2.4 Data Config Schema

Each Data config follows the canonical schema defined in the blueprint ([`docs/ai-optimized-architecture-blueprint.md`](docs/ai-optimized-architecture-blueprint.md:1319-1440)). Example for Company:

```php
<?php

// src/Core/Organization/Data/company.php

return [
    'model' => \QuickerFaster\UILibrary\Core\Organization\Models\Company::class,

    'fieldDefinitions' => [
        'name' => [
            'field_type' => 'string',
            'label' => 'Company Name',
            'required' => true,
            'validation' => 'required|string|max:255',
            'sortable' => true,
            'searchable' => true,
        ],
        'code' => [
            'field_type' => 'string',
            'label' => 'Code',
            'validation' => 'nullable|string|max:50|unique:companies,code',
            'sortable' => true,
            'searchable' => true,
        ],
        'email' => [
            'field_type' => 'string',
            'label' => 'Email',
            'validation' => 'nullable|email|max:255',
            'sortable' => true,
        ],
        'currency' => [
            'field_type' => 'select',
            'label' => 'Currency',
            'validation' => 'nullable|string|max:3',
            'options' => ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'NGN' => 'NGN'],
        ],
        'is_active' => [
            'field_type' => 'boolcheckbox',
            'label' => 'Active',
            'default' => true,
        ],
        // ... additional fields as defined in Section 3.1
    ],

    'fieldGroups' => [
        [
            'key' => 'basic',
            'label' => 'Basic Information',
            'icon' => 'fa-building',
            'fields' => ['name', 'code', 'email', 'phone', 'website'],
        ],
        [
            'key' => 'address',
            'label' => 'Address',
            'icon' => 'fa-map-marker-alt',
            'fields' => ['address', 'city', 'state', 'country', 'postal_code'],
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'fa-cog',
            'fields' => ['currency', 'timezone', 'date_format', 'is_active'],
        ],
    ],

    'controls' => [
        'files' => [
            'export' => ['xls', 'csv', 'pdf'],
            'import' => ['xls', 'csv'],
            'print' => true,
        ],
        'bulkActions' => [
            'export' => ['xls', 'csv'],
            'delete' => true,
        ],
        'perPage' => [10, 25, 50, 100],
        'search' => true,
        'showHideColumns' => true,
        'filterColumns' => true,
        'addButton' => true,
        'editable' => true,
    ],

    'hiddenFields' => [
        'onTable' => ['metadata'],
        'onNewForm' => [],
        'onEditForm' => [],
        'onQuery' => [],
        'onDetail' => [],
    ],
];
```

### 2.3 Catch-All Routing Integration (NEW SECTION)

#### 2.3.1 How Organization Routes Interact with the Catch-All

The System module's catch-all route ([`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php:16-32)) handles `/{module}/{view}/{id?}` patterns. However, Organization defines **explicit named routes** in its own `Routes/web.php`, which take precedence because:

1. **Library routes** load first ([`src/Routes/web.php`](src/Routes/web.php))
2. **Core module routes** load next (via [`UILibraryServiceProvider::bootCoreModules()`](src/Providers/UILibraryServiceProvider.php:130-134))
3. **Business module routes** load next (via [`ModuleServiceProvider::discoverBusinessModules()`](src/Providers/ModuleServiceProvider.php:67-70))
4. **System catch-all** loads LAST (via [`ModuleServiceProvider::discoverBusinessModules()`](src/Providers/ModuleServiceProvider.php:88-92))

Since Organization routes are loaded in step 2 and the catch-all in step 4, explicit Organization routes always win.

#### 2.3.2 View Resolution Flow

For a request to `/organization/companies`:

```
1. Request: GET /organization/companies
2. Organization Routes/web.php matches:
   Route::get('/organization/companies', fn() => view('qf-core::organization.companies'))
   → Renders: src/Core/Organization/Resources/views/companies.blade.php
3. The catch-all route is never reached for this URL
```

For a request to `/organization/company/1` (detail view, if not explicitly routed):

```
1. Request: GET /organization/company/1
2. Organization Routes/web.php — no match for this pattern
3. System catch-all matches:
   → Tries: view('organization::company') — fails (Organization is a Core module, not business)
   → Falls back: view('qf-core::organization.company') — succeeds if company.blade.php exists
   → Renders with ['id' => 1]
```

#### 2.3.3 Route Design Decision

Organization uses **explicit routes** for entity index pages (e.g., `/organization/companies`) rather than relying on the catch-all. This is intentional:

- **Explicit routes** provide named routes (`organization.companies`) that navigation configs reference
- **Catch-all** handles ad-hoc view resolution for detail pages or future views
- This hybrid approach gives the best of both worlds: predictable named routes for navigation + flexible catch-all for extension

#### 2.3.4 View Namespace Registration

Organization views are registered with the `qf-core::organization` namespace by [`UILibraryServiceProvider::bootCoreModules()`](src/Providers/UILibraryServiceProvider.php:124):

```php
$this->loadViewsFrom($viewPath, "qf-core::{$moduleLower}");
// Organization → qf-core::organization
```

This means:
- `view('qf-core::organization.dashboard')` → `src/Core/Organization/Resources/views/dashboard.blade.php`
- `view('qf-core::organization.companies')` → `src/Core/Organization/Resources/views/companies.blade.php`
- Published overrides at `resources/views/vendor/ui-library/core/organization/` take precedence (Laravel's native `loadViewsFrom` behavior)

---

## 3. Entity Model Specifications

### 3.1 Company

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\Company`  
**Table**: `companies`  
**Contract needed**: No — Company is a concrete Eloquent model, not an abstract service  
**Service/Engine needed**: No — standard Eloquent CRUD via DataTable/Form configs

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK, auto-increment | |
| `name` | string(255) | required | Company display name |
| `code` | string(50) | nullable, unique | Short code (e.g., "ACME") |
| `subdomain` | string(100) | nullable, unique | For multi-tenant routing |
| `logo` | string(255) | nullable | Logo path/URL |
| `email` | string(255) | nullable | Company contact email |
| `phone` | string(50) | nullable | Company contact phone |
| `website` | string(255) | nullable | Company website URL |
| `address` | text | nullable | Registered address |
| `city` | string(100) | nullable | |
| `state` | string(100) | nullable | |
| `country` | string(100) | nullable | |
| `postal_code` | string(20) | nullable | |
| `tax_id` | string(100) | nullable | Tax identification number |
| `registration_number` | string(100) | nullable | Business registration number |
| `currency` | string(3) | nullable, default 'USD' | ISO 4217 currency code |
| `timezone` | string(50) | nullable, default 'UTC' | |
| `date_format` | string(20) | nullable, default 'Y-m-d' | |
| `is_active` | boolean | default true | Soft disable |
| `status` | string(50) | default 'active' | active, inactive, suspended |
| `metadata` | json | nullable | Arbitrary metadata |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function branches(): HasMany
public function departments(): HasMany
public function businessUnits(): HasMany
public function locations(): HasMany
public function teams(): HasMany
```

**Casts**:
```php
protected $casts = [
    'is_active' => 'boolean',
    'metadata' => 'array',
];
```

**Traits**: `HasSettings` (from [`src/Traits/HasSettings.php`](src/Traits/HasSettings.php)), `SoftDeletes`

### 3.2 Branch

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\Branch`  
**Table**: `branches`

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK | |
| `company_id` | bigint | FK → companies.id, required | Multi-tenant scope |
| `name` | string(255) | required | |
| `code` | string(50) | nullable | |
| `address` | text | nullable | |
| `city` | string(100) | nullable | |
| `state` | string(100) | nullable | |
| `country` | string(100) | nullable | |
| `postal_code` | string(20) | nullable | |
| `phone` | string(50) | nullable | |
| `email` | string(255) | nullable | |
| `is_headquarters` | boolean | default false | |
| `is_active` | boolean | default true | |
| `metadata` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function company(): BelongsTo
public function departments(): HasMany
```

### 3.3 Department

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\Department`  
**Table**: `departments`

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK | |
| `company_id` | bigint | FK → companies.id, required | |
| `branch_id` | bigint | FK → branches.id, nullable | |
| `parent_id` | bigint | FK → departments.id, nullable | Self-referential hierarchy |
| `name` | string(255) | required | |
| `code` | string(50) | nullable | |
| `description` | text | nullable | |
| `manager_id` | bigint | FK → users.id, nullable | Department head |
| `is_active` | boolean | default true | |
| `metadata` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function company(): BelongsTo
public function branch(): BelongsTo
public function parent(): BelongsTo  // self-referential
public function children(): HasMany  // self-referential
public function divisions(): HasMany
public function teams(): HasMany
// NOTE: Does NOT define employees() — HR owns that relationship
```

### 3.4 Division

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\Division`  
**Table**: `divisions`

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK | |
| `company_id` | bigint | FK → companies.id, required | |
| `department_id` | bigint | FK → departments.id, required | |
| `name` | string(255) | required | |
| `code` | string(50) | nullable | |
| `description` | text | nullable | |
| `manager_id` | bigint | FK → users.id, nullable | |
| `is_active` | boolean | default true | |
| `metadata` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function company(): BelongsTo
public function department(): BelongsTo
```

### 3.5 Business Unit

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\BusinessUnit`  
**Table**: `business_units`

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK | |
| `company_id` | bigint | FK → companies.id, required | |
| `name` | string(255) | required | |
| `code` | string(50) | nullable | |
| `description` | text | nullable | |
| `manager_id` | bigint | FK → users.id, nullable | |
| `is_active` | boolean | default true | |
| `metadata` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function company(): BelongsTo
```

### 3.6 Location

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\Location`  
**Table**: `locations`

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK | |
| `company_id` | bigint | FK → companies.id, required | |
| `name` | string(255) | required | |
| `code` | string(50) | nullable | |
| `type` | string(50) | default 'office' | office, warehouse, remote, etc. |
| `address` | text | nullable | |
| `city` | string(100) | nullable | |
| `state` | string(100) | nullable | |
| `country` | string(100) | nullable | |
| `postal_code` | string(20) | nullable | |
| `latitude` | decimal(10,7) | nullable | |
| `longitude` | decimal(10,7) | nullable | |
| `phone` | string(50) | nullable | |
| `email` | string(255) | nullable | |
| `timezone` | string(50) | nullable | |
| `is_headquarters` | boolean | default false | |
| `is_active` | boolean | default true | |
| `metadata` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function company(): BelongsTo
```

### 3.7 Team

**Namespace**: `QuickerFaster\UILibrary\Core\Organization\Models\Team`  
**Table**: `teams`

| Attribute | Type | Constraints | Notes |
|-----------|------|-------------|-------|
| `id` | bigint | PK | |
| `company_id` | bigint | FK → companies.id, required | |
| `department_id` | bigint | FK → departments.id, nullable | |
| `name` | string(255) | required | |
| `code` | string(50) | nullable | |
| `description` | text | nullable | |
| `team_lead_id` | bigint | FK → users.id, nullable | |
| `type` | string(50) | default 'permanent' | permanent, project, virtual |
| `is_active` | boolean | default true | |
| `metadata` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | Soft deletes |

**Relationships**:
```php
public function company(): BelongsTo
public function department(): BelongsTo
```

### 3.8 Contract/Service Decision Summary

| Entity | Contract? | Service/Engine? | Rationale |
|--------|-----------|-----------------|-----------|
| Company | No | No | Concrete Eloquent model; standard CRUD |
| Branch | No | No | Concrete Eloquent model; standard CRUD |
| Department | No | No | Concrete Eloquent model; standard CRUD |
| Division | No | No | Concrete Eloquent model; standard CRUD |
| Business Unit | No | No | Concrete Eloquent model; standard CRUD |
| Location | No | No | Concrete Eloquent model; standard CRUD |
| Team | No | No | Concrete Eloquent model; standard CRUD |

**Why no contracts?** Unlike Workflow, Documents, and Notifications — which are abstract engines that operate on any model — Organization entities are concrete database tables with fixed schemas. They are the *data*, not the *behavior*. The Phase 3 contract pattern is for polymorphic services; Organization follows the Core module pattern for shared data models.

---

## 4. Migration Plan

### 4.1 Migration Files

Seven new migration files following the timestamp convention established by [`src/Core/System/Database/Migrations/2026_07_17_000000_create_systems_table.php`](src/Core/System/Database/Migrations/2026_07_17_000000_create_systems_table.php):

| # | Migration File | Creates Table | Order |
|---|---------------|---------------|-------|
| 1 | `2026_08_09_000001_create_companies_table.php` | `companies` | First (no FKs to other org tables) |
| 2 | `2026_08_09_000002_create_branches_table.php` | `branches` | Second (FK → companies) |
| 3 | `2026_08_09_000003_create_departments_table.php` | `departments` | Third (FK → companies, branches, self) |
| 4 | `2026_08_09_000004_create_divisions_table.php` | `divisions` | Fourth (FK → companies, departments) |
| 5 | `2026_08_09_000005_create_business_units_table.php` | `business_units` | Fifth (FK → companies) |
| 6 | `2026_08_09_000006_create_locations_table.php` | `locations` | Sixth (FK → companies) |
| 7 | `2026_08_09_000007_create_teams_table.php` | `teams` | Seventh (FK → companies, departments) |

### 4.2 Migration Pattern

Each migration follows the pattern from [`2026_07_17_000000_create_systems_table.php`](src/Core/System/Database/Migrations/2026_07_17_000000_create_systems_table.php):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                // ... other columns ...
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

Key conventions:
- `Schema::hasTable()` guard for idempotent migrations
- `$table->softDeletes()` on all entity tables
- `$table->foreignId('company_id')->constrained()->cascadeOnDelete()` for FK relationships
- `$table->json('metadata')->nullable()` for extensibility
- Anonymous class (not named class) — consistent with existing library migrations

### 4.3 Existing Data Migration Strategy

**Critical decision**: The library migrations create fresh tables. If the HR app already has Organization tables with data, the consuming app is responsible for data migration.

**Strategy for consuming apps with existing data**:

1. **Option A (Recommended)**: The consuming app publishes the library migrations, then writes a **data migration** that copies data from old tables to new tables:
   ```php
   // In the consuming app's own migration:
   DB::statement('INSERT INTO companies (id, name, ...) SELECT id, name, ... FROM old_companies');
   ```
   Then drops the old tables.

2. **Option B**: The consuming app skips the library migrations entirely (by not running them) and instead renames its existing tables to match the library's expected table names. This requires the column structures to be compatible.

3. **Option C**: The consuming app publishes and customizes the library migrations to match its existing schema.

**Library responsibility**: The library provides clean, canonical migrations. Data migration from existing HR app tables is the consuming app's responsibility. The spec documents the expected schema so consuming apps can plan their data migration.

### 4.4 Migration Loading

Migrations are auto-loaded by [`UILibraryServiceProvider::bootCoreModules()`](src/Providers/UILibraryServiceProvider.php:113-145). When `Organization` is added to the `$modules` array in `bootCoreModules()`, its migrations are automatically discovered:

```php
// In UILibraryServiceProvider::bootCoreModules():
foreach (['Admin', 'System', 'Organization'] as $module) {
    // ...
    $migrationPath = "{$modulePath}/Database/Migrations";
    if (is_dir($migrationPath)) {
        $this->loadMigrationsFrom($migrationPath);
    }
}
```

---

## 5. Affected Files Inventory

### 5.1 Files to CREATE (New)

#### Models (7 files)

| # | File | Namespace |
|---|------|-----------|
| 1 | [`src/Core/Organization/Models/Company.php`](src/Core/Organization/Models/Company.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |
| 2 | [`src/Core/Organization/Models/Branch.php`](src/Core/Organization/Models/Branch.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |
| 3 | [`src/Core/Organization/Models/Department.php`](src/Core/Organization/Models/Department.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |
| 4 | [`src/Core/Organization/Models/Division.php`](src/Core/Organization/Models/Division.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |
| 5 | [`src/Core/Organization/Models/BusinessUnit.php`](src/Core/Organization/Models/BusinessUnit.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |
| 6 | [`src/Core/Organization/Models/Location.php`](src/Core/Organization/Models/Location.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |
| 7 | [`src/Core/Organization/Models/Team.php`](src/Core/Organization/Models/Team.php) | `QuickerFaster\UILibrary\Core\Organization\Models` |

#### Migrations (7 files)

| # | File |
|---|------|
| 1 | [`src/Core/Organization/Database/Migrations/2026_08_09_000001_create_companies_table.php`](src/Core/Organization/Database/Migrations/) |
| 2 | [`src/Core/Organization/Database/Migrations/2026_08_09_000002_create_branches_table.php`](src/Core/Organization/Database/Migrations/) |
| 3 | [`src/Core/Organization/Database/Migrations/2026_08_09_000003_create_departments_table.php`](src/Core/Organization/Database/Migrations/) |
| 4 | [`src/Core/Organization/Database/Migrations/2026_08_09_000004_create_divisions_table.php`](src/Core/Organization/Database/Migrations/) |
| 5 | [`src/Core/Organization/Database/Migrations/2026_08_09_000005_create_business_units_table.php`](src/Core/Organization/Database/Migrations/) |
| 6 | [`src/Core/Organization/Database/Migrations/2026_08_09_000006_create_locations_table.php`](src/Core/Organization/Database/Migrations/) |
| 7 | [`src/Core/Organization/Database/Migrations/2026_08_09_000007_create_teams_table.php`](src/Core/Organization/Database/Migrations/) |

#### Seeders (1 file)

| # | File |
|---|------|
| 1 | [`src/Core/Organization/Database/Seeders/OrganizationDemoSeeder.php`](src/Core/Organization/Database/Seeders/OrganizationDemoSeeder.php) |

#### Config (1 file)

| # | File |
|---|------|
| 1 | [`src/Core/Organization/Config/navigation.php`](src/Core/Organization/Config/navigation.php) |

#### Routes (1 file)

| # | File |
|---|------|
| 1 | [`src/Core/Organization/Routes/web.php`](src/Core/Organization/Routes/web.php) |

#### Views (8 files) ← AMENDED: was 1, now 8

| # | File | Purpose |
|---|------|---------|
| 1 | [`src/Core/Organization/Resources/views/dashboard.blade.php`](src/Core/Organization/Resources/views/dashboard.blade.php) | Organization dashboard |
| 2 | [`src/Core/Organization/Resources/views/companies.blade.php`](src/Core/Organization/Resources/views/companies.blade.php) | Company DataTable |
| 3 | [`src/Core/Organization/Resources/views/branches.blade.php`](src/Core/Organization/Resources/views/branches.blade.php) | Branch DataTable |
| 4 | [`src/Core/Organization/Resources/views/departments.blade.php`](src/Core/Organization/Resources/views/departments.blade.php) | Department DataTable |
| 5 | [`src/Core/Organization/Resources/views/divisions.blade.php`](src/Core/Organization/Resources/views/divisions.blade.php) | Division DataTable |
| 6 | [`src/Core/Organization/Resources/views/business_units.blade.php`](src/Core/Organization/Resources/views/business_units.blade.php) | BusinessUnit DataTable |
| 7 | [`src/Core/Organization/Resources/views/locations.blade.php`](src/Core/Organization/Resources/views/locations.blade.php) | Location DataTable |
| 8 | [`src/Core/Organization/Resources/views/teams.blade.php`](src/Core/Organization/Resources/views/teams.blade.php) | Team DataTable |

#### Data Configs (7 files) ← NEW

| # | File | Config Key |
|---|------|------------|
| 1 | [`src/Core/Organization/Data/company.php`](src/Core/Organization/Data/company.php) | `organization.company` |
| 2 | [`src/Core/Organization/Data/branch.php`](src/Core/Organization/Data/branch.php) | `organization.branch` |
| 3 | [`src/Core/Organization/Data/department.php`](src/Core/Organization/Data/department.php) | `organization.department` |
| 4 | [`src/Core/Organization/Data/division.php`](src/Core/Organization/Data/division.php) | `organization.division` |
| 5 | [`src/Core/Organization/Data/business_unit.php`](src/Core/Organization/Data/business_unit.php) | `organization.business_unit` |
| 6 | [`src/Core/Organization/Data/location.php`](src/Core/Organization/Data/location.php) | `organization.location` |
| 7 | [`src/Core/Organization/Data/team.php`](src/Core/Organization/Data/team.php) | `organization.team` |

**Total new files: 32** (was 18 in original spec; +7 Data configs, +7 entity views)

### 5.2 Files to MODIFY (Library)

#### Service Providers (2 files)

| # | File | Change |
|---|------|--------|
| 1 | [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | Add `'Organization'` to `bootCoreModules()` foreach loop (line 117); add `ModuleRegistered` event for `organization` (after line 96) |
| 2 | [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php) | No changes needed — Organization is a Core module, not a business module. Core modules are booted by `UILibraryServiceProvider`. |

#### Config (1 file)

| # | File | Change |
|---|------|--------|
| 1 | [`src/Config/ui-library.php`](src/Config/ui-library.php) | Add `'organization'` entry to `'modules'` array (after `'system'`, before closing `]`) |

#### ModelConfigRepository (1 file) ← NEW PREREQUISITE

| # | File | Change |
|---|------|--------|
| 1 | [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) | Extend `loadFromFile()` to scan `src/Core/{Module}/Data/` as fallback after `app/Modules/{Module}/Data/`. See Section 2.2.2 for implementation approach. |

#### Composer (1 file)

| # | File | Change |
|---|------|--------|
| 1 | `composer.json` | Verify PSR-4 autoloading covers `QuickerFaster\\UILibrary\\Core\\Organization\\` — already covered by existing `"QuickerFaster\\UILibrary\\Core\\": "src/Core/"` mapping |

### 5.3 Files to MODIFY (Consuming Apps — Reference Only)

These are files in the HR/Payroll/Time/Leave apps that reference `App\Modules\Organization\Models\*` and must be updated. This is documented for the consuming app teams.

#### HR App

| # | Pattern to Update | New Namespace |
|---|-------------------|---------------|
| 1 | `use App\Modules\Organization\Models\Company` | `use QuickerFaster\UILibrary\Core\Organization\Models\Company` |
| 2 | `use App\Modules\Organization\Models\Branch` | `use QuickerFaster\UILibrary\Core\Organization\Models\Branch` |
| 3 | `use App\Modules\Organization\Models\Department` | `use QuickerFaster\UILibrary\Core\Organization\Models\Department` |
| 4 | `use App\Modules\Organization\Models\Division` | `use QuickerFaster\UILibrary\Core\Organization\Models\Division` |
| 5 | `use App\Modules\Organization\Models\BusinessUnit` | `use QuickerFaster\UILibrary\Core\Organization\Models\BusinessUnit` |
| 6 | `use App\Modules\Organization\Models\Location` | `use QuickerFaster\UILibrary\Core\Organization\Models\Location` |
| 7 | `use App\Modules\Organization\Models\Team` | `use QuickerFaster\UILibrary\Core\Organization\Models\Team` |

#### Library Files with HR-specific Organization References

| # | File | Current Reference | Action |
|---|------|-------------------|--------|
| 1 | [`src/Http/Controllers/RegistrationController.php`](src/Http/Controllers/RegistrationController.php:5-11) | `use App\Modules\Admin\Models\Company`, `Location`, `Department` | Update to `QuickerFaster\UILibrary\Core\Organization\Models\*` |
| 2 | [`src/Concerns/ResolvesModels.php`](src/Concerns/ResolvesModels.php:132,148,156,160) | References `company_id` column dynamically | No namespace change needed — uses dynamic column checks |
| 3 | [`src/Http/Controllers/Exports/ExportController.php`](src/Http/Controllers/Exports/ExportController.php:197) | `auth()->user()?->company_id` | No change — uses user attribute, not model import |
| 4 | [`src/Jobs/GenerateExport.php`](src/Jobs/GenerateExport.php:250) | `$export->company_id` | No change — uses export model attribute |
| 5 | [`src/Jobs/ProcessImportChunk.php`](src/Jobs/ProcessImportChunk.php:323) | `$import->company_id` | No change — uses import model attribute |
| 6 | [`src/Jobs/ExportChunk.php`](src/Jobs/ExportChunk.php:297) | `$export->company_id` | No change — uses export model attribute |
| 7 | [`src/Http/Livewire/Wizards/WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php:171-174,325-326,348-349,391-395) | Dynamic `company_id` column checks | No change — uses `Schema::hasColumn()` |
| 8 | [`src/Http/Livewire/DataTables/DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php:161-164,684-685,765-766,925-929) | Dynamic `company_id` column checks | No change — uses `Schema::hasColumn()` |
| 9 | [`src/Http/Livewire/DataTables/ImportForm.php`](src/Http/Livewire/DataTables/ImportForm.php:201) | `auth()->user()?->company_id` | No change — uses user attribute |
| 10 | [`src/Http/Livewire/Layouts/Navs/TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php:78-90) | `Session::get('current_company_id')` | No change — uses session, not model import |
| 11 | [`src/Services/AccessControl/AccessControlPermissionService.php`](src/Services/AccessControl/AccessControlPermissionService.php:104) | `$model->team_id` | No change — dynamic attribute access |

**Key finding**: The library itself has only **one file** with hard-coded Organization model imports: [`RegistrationController.php`](src/Http/Controllers/RegistrationController.php). All other `company_id` references use dynamic column checks or session values. This dramatically reduces the library-side modification surface.

### 5.4 Files to DELETE

None. The Organization module is being **created** in the library, not moved from within the library. The old `app/Modules/Organization/` in the HR app is the consuming app's responsibility to remove after migration.

---

## 6. Namespace Mapping

### 6.1 Old → New Namespace Map

| Old Namespace | New Namespace |
|---------------|---------------|
| `App\Modules\Organization\Models\Company` | `QuickerFaster\UILibrary\Core\Organization\Models\Company` |
| `App\Modules\Organization\Models\Branch` | `QuickerFaster\UILibrary\Core\Organization\Models\Branch` |
| `App\Modules\Organization\Models\Department` | `QuickerFaster\UILibrary\Core\Organization\Models\Department` |
| `App\Modules\Organization\Models\Division` | `QuickerFaster\UILibrary\Core\Organization\Models\Division` |
| `App\Modules\Organization\Models\BusinessUnit` | `QuickerFaster\UILibrary\Core\Organization\Models\BusinessUnit` |
| `App\Modules\Organization\Models\Location` | `QuickerFaster\UILibrary\Core\Organization\Models\Location` |
| `App\Modules\Organization\Models\Team` | `QuickerFaster\UILibrary\Core\Organization\Models\Team` |
| `App\Modules\Admin\Models\Company` | `QuickerFaster\UILibrary\Core\Organization\Models\Company` |
| `App\Modules\Admin\Models\Location` | `QuickerFaster\UILibrary\Core\Organization\Models\Location` |
| `App\Modules\Admin\Models\Department` | `QuickerFaster\UILibrary\Core\Organization\Models\Department` |

**Note**: The RegistrationController currently imports from `App\Modules\Admin\Models\*` (not `App\Modules\Organization\Models\*`). This suggests the HR app may have Organization models under the Admin module. Both old namespaces map to the same new namespace.

### 6.2 Search/Replace Patterns for Consuming Apps

Consuming apps can use these regex patterns to find and replace:

```bash
# Find all references
grep -r "App\\Modules\\Organization\\Models" app/
grep -r "App\\Modules\\Admin\\Models\\Company\|App\\Modules\\Admin\\Models\\Location\|App\\Modules\\Admin\\Models\\Department" app/

# Replace (use with caution — review each match)
find app/ -name "*.php" -exec sed -i '' 's/App\\Modules\\Organization\\Models\\/QuickerFaster\\UILibrary\\Core\\Organization\\Models\\/g' {} +
```

---

## 7. Service Provider Updates

### 7.1 UILibraryServiceProvider Changes

**File**: [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php)

#### Change 1: Add Organization to bootCoreModules()

**Location**: Line 117 — the `foreach` loop in `bootCoreModules()`

**Current**:
```php
foreach (['Admin', 'System'] as $module) {
```

**New**:
```php
foreach (['Admin', 'System', 'Organization'] as $module) {
```

This single change auto-registers:
- Views: `qf-core::organization.*` from `src/Core/Organization/Resources/views/`
- Routes: `src/Core/Organization/Routes/web.php`
- Migrations: `src/Core/Organization/Database/Migrations/`
- Publishables: views published to `resources/views/vendor/ui-library/core/organization/`

#### Change 2: Fire ModuleRegistered event

**Location**: After line 96 (the existing `ModuleRegistered` events)

**Current**:
```php
event(new ModuleRegistered('admin', __DIR__ . '/../Core/Admin'));
event(new ModuleRegistered('system', __DIR__ . '/../Core/System'));
```

**New**:
```php
event(new ModuleRegistered('admin', __DIR__ . '/../Core/Admin'));
event(new ModuleRegistered('system', __DIR__ . '/../Core/System'));
event(new ModuleRegistered('organization', __DIR__ . '/../Core/Organization'));
```

### 7.2 ModuleServiceProvider

**No changes needed.** Organization is a Core module, not a business module. The `ModuleServiceProvider` handles `app/Modules/*` discovery only. Core modules are booted by `UILibraryServiceProvider`.

However, the `registerModuleConfigs()` method in `ModuleServiceProvider` (line 138) scans both `src/Core` and `app/Modules` for Data/Dashboards and Data/reports configs. If Organization later adds Data configs, they will be auto-discovered.

### 7.3 ModelConfigRepository Changes (PREREQUISITE)

**File**: [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php)

This change is a **hard prerequisite** for Phase 4.1. Without it, DataTable components cannot resolve Organization Data configs.

**Current `loadFromFile()` logic**:
```php
$filePath = $this->basePath . '/' . $module . '/Data/' . $relativePath . '.php';
// Where $this->basePath = app_path('Modules')
```

**Required change**: Add a fallback to scan `src/Core/` when the business module path doesn't exist:

```php
protected function resolveConfigPath(string $module, string $relativePath): ?string
{
    // 1. Try business module path (app/Modules/)
    $businessPath = app_path("Modules/{$module}/Data/{$relativePath}.php");
    if (file_exists($businessPath)) {
        return $businessPath;
    }
    
    // 2. Try core module path (src/Core/)
    $corePath = base_path("vendor/quicker-faster/ui-library/src/Core/{$module}/Data/{$relativePath}.php");
    if (file_exists($corePath)) {
        return $corePath;
    }
    
    return null;
}
```

---

## 8. Navigation Integration

### 8.1 Module Registry Entry

**File**: [`src/Config/ui-library.php`](src/Config/ui-library.php), in the `'modules'` array

**Add after the `'system'` entry** (line 29):

```php
'organization' => [
    'enabled' => true,
    'label' => 'Organization',
    'icon' => 'fa-building',
    'route' => 'organization.dashboard',
    'order' => 100,
    'roles' => ['super_admin', 'admin'],
    'core' => true,
],
```

**Placement rationale**: `order: 100` places Organization between Admin (900) and System (999) in the module switcher, making it the first user-facing module after the infrastructure modules.

### 8.2 Navigation Config

**File**: [`src/Core/Organization/Config/navigation.php`](src/Core/Organization/Config/navigation.php)

Following the pattern from [`src/Core/System/Config/navigation.php`](src/Core/System/Config/navigation.php:1-46) and [`src/Core/Admin/Config/navigation.php`](src/Core/Admin/Config/navigation.php:1-60):

```php
<?php

return [
    'context_groups' => [
        'companies' => [
            'label' => 'Companies',
            'icon' => 'fa-building',
            'route' => 'organization.companies',
            'order' => 10,
        ],
        'structure' => [
            'label' => 'Structure',
            'icon' => 'fa-sitemap',
            'route' => 'organization.departments',
            'order' => 20,
        ],
        'locations' => [
            'label' => 'Locations',
            'icon' => 'fa-map-marker-alt',
            'route' => 'organization.locations',
            'order' => 30,
        ],
        'teams' => [
            'label' => 'Teams',
            'icon' => 'fa-users',
            'route' => 'organization.teams',
            'order' => 40,
        ],
    ],

    'contexts' => [
        'companies' => [
            [
                'label' => 'All Companies',
                'route' => 'organization.companies',
                'icon' => 'fa-building',
                'order' => 10,
            ],
            [
                'label' => 'Branches',
                'route' => 'organization.branches',
                'icon' => 'fa-code-branch',
                'order' => 20,
            ],
            [
                'label' => 'Business Units',
                'route' => 'organization.business-units',
                'icon' => 'fa-briefcase',
                'order' => 30,
            ],
        ],
        'structure' => [
            [
                'label' => 'Departments',
                'route' => 'organization.departments',
                'icon' => 'fa-layer-group',
                'order' => 10,
            ],
            [
                'label' => 'Divisions',
                'route' => 'organization.divisions',
                'icon' => 'fa-diagram-project',
                'order' => 20,
            ],
        ],
        'locations' => [
            [
                'label' => 'All Locations',
                'route' => 'organization.locations',
                'icon' => 'fa-location-dot',
                'order' => 10,
            ],
        ],
        'teams' => [
            [
                'label' => 'All Teams',
                'route' => 'organization.teams',
                'icon' => 'fa-people-group',
                'order' => 10,
            ],
        ],
    ],

    'shared_items' => [
        'header' => [],
        'footer' => [],
    ],

    'shared_top_items' => [
        'left' => [],
        'right' => [],
    ],

    'layout' => [
        'top_bar' => ['enabled' => true],
        'context_menu' => ['type' => 'sidebar', 'position' => 'left', 'allow_switch' => false],
        'sidebar' => ['initial_state' => 'full'],
        'bottom_bar' => ['enabled' => true],
    ],
];
```

### 8.3 Routes

**File**: [`src/Core/Organization/Routes/web.php`](src/Core/Organization/Routes/web.php)

Following the pattern from [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php:1-21):

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/organization/dashboard', function () {
        return view('qf-core::organization.dashboard');
    })->name('organization.dashboard');

    Route::get('/organization/companies', function () {
        return view('qf-core::organization.companies');
    })->name('organization.companies');

    Route::get('/organization/branches', function () {
        return view('qf-core::organization.branches');
    })->name('organization.branches');

    Route::get('/organization/departments', function () {
        return view('qf-core::organization.departments');
    })->name('organization.departments');

    Route::get('/organization/divisions', function () {
        return view('qf-core::organization.divisions');
    })->name('organization.divisions');

    Route::get('/organization/business-units', function () {
        return view('qf-core::organization.business-units');
    })->name('organization.business-units');

    Route::get('/organization/locations', function () {
        return view('qf-core::organization.locations');
    })->name('organization.locations');

    Route::get('/organization/teams', function () {
        return view('qf-core::organization.teams');
    })->name('organization.teams');
});
```

### 8.4 Dashboard View

**File**: [`src/Core/Organization/Resources/views/dashboard.blade.php`](src/Core/Organization/Resources/views/dashboard.blade.php)

Following the pattern from [`src/Core/System/Resources/views/dashboard.blade.php`](src/Core/System/Resources/views/dashboard.blade.php:1-14):

```blade
<x-layout configKey="organization_dashboard" moduleName="organization">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Organization Dashboard</h5>
                </div>
                <div class="card-body">
                    <p>Welcome to the Organization module. Manage companies, departments, locations, and teams from here.</p>
                </div>
            </div>
        </div>
    </div>
</x-layout>
```

### 8.5 Entity Index Views (NEW)

Each entity view follows the DataTable embedding pattern:

```blade
{{-- src/Core/Organization/Resources/views/companies.blade.php --}}
<x-layout configKey="organization_company" moduleName="organization">
    <livewire:qf.data-table config-key="organization.company" />
</x-layout>
```

The `config-key` parameter uses dot-notation: `organization.company` resolves to `src/Core/Organization/Data/company.php` via the updated `ModelConfigRepository`.

### 8.6 ModuleSwitcher Integration

The [`ModuleSwitcher`](src/Http/Livewire/Layouts/Navs/ModuleSwitcher.php:31) reads from `config('ui-library.modules')`. Once the `'organization'` entry is added to the config, it automatically appears in the module switcher. No code changes needed in `ModuleSwitcher`.

---

## 9. Risk Assessment

### 9.1 Cascade Impact Analysis

```
Organization Extraction
        │
        ├── HR App ──────────────── HIGH IMPACT
        │   ├── Employee → company_id, branch_id, department_id, division_id, location_id
        │   ├── Job History → department_id, division_id
        │   ├── Attendance Policy → company_id
        │   ├── Work Pattern → company_id
        │   ├── Shift → company_id
        │   └── All Data configs reference App\Modules\Organization\Models\*
        │
        ├── Payroll App ─────────── HIGH IMPACT
        │   ├── Salary Structure → company_id
        │   ├── Payroll Run → company_id
        │   ├── Bank Account → company_id
        │   └── All Data configs reference App\Modules\Organization\Models\*
        │
        ├── Time App ────────────── MEDIUM IMPACT
        │   ├── Timesheet → company_id
        │   ├── Clock Event → company_id
        │   └── Data configs reference App\Modules\Organization\Models\*
        │
        ├── Leave App ───────────── MEDIUM IMPACT
        │   ├── Leave Request → company_id
        │   ├── Leave Policy → company_id
        │   └── Data configs reference App\Modules\Organization\Models\*
        │
        └── Library ─────────────── LOW IMPACT
            └── Only RegistrationController has hard-coded imports
```

### 9.2 Breaking Change Surface

| Change | Breaking? | Mitigation |
|--------|-----------|------------|
| Namespace change: `App\Modules\Organization\Models\*` → `QuickerFaster\UILibrary\Core\Organization\Models\*` | **YES** | Consuming apps must update all `use` statements. Provide search/replace script. |
| Table names unchanged (`companies`, `branches`, etc.) | No | Same table names; existing data survives |
| Column names unchanged | No | Same column names; existing queries survive |
| Relationship method names unchanged | No | `$company->branches()`, `$department->company()` etc. remain identical |
| New migration files | **YES** (if tables already exist) | `Schema::hasTable()` guard prevents duplicate table errors. Consuming apps with existing tables skip these migrations. |
| Module registry entry | No | Additive change; existing modules unaffected |
| Route names (`organization.dashboard`, etc.) | No | New routes; no conflicts with existing |
| ModelConfigRepository change | **YES** (if not done first) | DataTable components cannot resolve Organization configs. This is a hard prerequisite. |

### 9.3 Rollback Strategy

If the extraction causes issues:

1. **Immediate**: Remove `'Organization'` from `bootCoreModules()` array in `UILibraryServiceProvider`
2. **Config**: Remove `'organization'` entry from `ui-library.php` modules array
3. **Consuming apps**: Revert `use` statements to `App\Modules\Organization\Models\*`
4. **Database**: No rollback needed — tables are unchanged
5. **Files**: The new `src/Core/Organization/` directory can remain (inert without service provider registration)

### 9.4 Specific Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| **HR app has Organization models under `App\Modules\Admin\Models\*` instead of `App\Modules\Organization\Models\*`** | Medium | High | The RegistrationController imports from `App\Modules\Admin\Models\*`. Before extraction, verify the actual location of Organization models in the HR app. The namespace mapping covers both locations. |
| **Existing HR app tables have different column names** | Medium | Medium | The spec defines the canonical schema. If HR app tables differ, consuming apps must either migrate their schema or publish and customize the library migrations. |
| **`company_id` foreign key constraint conflicts** | Low | High | The library migrations use `constrained()->cascadeOnDelete()`. If existing tables use different FK names or constraints, the `Schema::hasTable()` guard prevents conflicts, but the consuming app must reconcile constraints. |
| **RegistrationController references HR-specific models (Shift, AttendancePolicy, WorkPattern)** | High | Medium | The RegistrationController imports `App\Modules\Hr\Models\*` in addition to Organization models. This is a separate coupling issue (Phase 2.5 scope). For Phase 4.1, only the Organization imports are updated. |
| **Composer autoloading not covering new namespace** | Low | Low | The existing PSR-4 mapping `"QuickerFaster\\UILibrary\\Core\\": "src/Core/"` already covers `src/Core/Organization/`. No composer.json change needed. |
| **ModelConfigRepository not updated before Phase 4.1** | **High** | **Critical** | DataTable components will throw `InvalidArgumentException` for all Organization config keys. This is now documented as a hard prerequisite (Section 12). |

---

## 10. Implementation Sequence

The implementation must follow this exact order to minimize risk and enable incremental validation:

### Step 0: PREREQUISITE — Update ModelConfigRepository ← NEW

Update [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) to scan `src/Core/` as a fallback path for Data configs. See Section 7.3 for implementation details.

**Validation**: `ModelConfigRepository::get('system.dashboard')` should resolve (even if the file doesn't exist yet, the path resolution logic should work).

### Step 1: Create Directory Structure

Create all empty directories:
```bash
mkdir -p src/Core/Organization/{Config,Data,Database/Migrations,Database/Seeders,Models,Resources/views,Routes}
```

### Step 2: Create Migration Files

Create the 7 migration files in dependency order (companies first, then FK-dependent tables). Each migration uses `Schema::hasTable()` guard for idempotency.

**Validation**: Run `php artisan migrate` — migrations should create tables if they don't exist, skip if they do.

### Step 3: Create Model Files

Create all 7 Eloquent models with:
- Correct namespace (`QuickerFaster\UILibrary\Core\Organization\Models`)
- `$fillable` arrays matching migration columns
- `$casts` for boolean, json, and datetime fields
- Relationship methods
- `SoftDeletes` trait
- `HasSettings` trait on Company

**Validation**: `php artisan tinker` — `QuickerFaster\UILibrary\Core\Organization\Models\Company::class` should resolve.

### Step 4: Create Data Configs ← NEW

Create all 7 Data config files following the schema in Section 2.2.4. Each config must include:
- `model` pointing to the correct FQCN
- `fieldDefinitions` matching the model's columns
- `fieldGroups` organizing fields into logical sections
- `controls` enabling export/import/print

**Validation**: `app(ModelConfigRepository::class)->get('organization.company')` should return the config array.

### Step 5: Create Seeder

Create `OrganizationDemoSeeder.php` with demo data (one company, one branch, one department, etc.).

**Validation**: `php artisan db:seed --class=QuickerFaster\\UILibrary\\Core\\Organization\\Database\\Seeders\\OrganizationDemoSeeder`

### Step 6: Create Routes

Create `Routes/web.php` with named routes for dashboard and all entity index pages.

**Validation**: `php artisan route:list | grep organization`

### Step 7: Create Views

Create `dashboard.blade.php` and all 7 entity index views following the DataTable embedding pattern.

**Validation**: Visit `/organization/dashboard` and `/organization/companies` in browser (after service provider update).

### Step 8: Create Navigation Config

Create `Config/navigation.php` with context_groups and contexts.

### Step 9: Update UILibraryServiceProvider

1. Add `'Organization'` to `bootCoreModules()` foreach loop
2. Add `ModuleRegistered` event for `'organization'`

**Validation**: Views, routes, and migrations should auto-load. `php artisan route:list | grep organization` should show routes.

### Step 10: Update ui-library.php Config

Add `'organization'` entry to `'modules'` array.

**Validation**: Organization module should appear in `ModuleSwitcher`.

### Step 11: Update RegistrationController

Change imports from `App\Modules\Admin\Models\*` to `QuickerFaster\UILibrary\Core\Organization\Models\*`.

**Validation**: `grep -r "App\\Modules\\Admin\\Models" src/Http/Controllers/RegistrationController.php` returns zero.

### Step 12: Update Consuming Apps (HR, Payroll, Time, Leave)

This is the highest-risk step. For each consuming app:

1. Search for all `App\Modules\Organization\Models\*` references
2. Replace with `QuickerFaster\UILibrary\Core\Organization\Models\*`
3. Search for all `App\Modules\Admin\Models\Company`, `App\Modules\Admin\Models\Location`, `App\Modules\Admin\Models\Department`
4. Replace with corresponding `QuickerFaster\UILibrary\Core\Organization\Models\*`
5. Run all tests
6. Verify CRUD operations on all entities

### Step 13: Final Validation

Run the complete validation checklist (Section 11).

---

## 11. Testing/Validation Checklist

### 11.1 Library-Side Validation

- [ ] `grep -r "App\\\\Modules\\\\Organization" src/` returns zero results
- [ ] `grep -r "App\\\\Modules\\\\Admin\\\\Models\\\\Company" src/` returns zero results
- [ ] `grep -r "App\\\\Modules\\\\Admin\\\\Models\\\\Location" src/` returns zero results
- [ ] `grep -r "App\\\\Modules\\\\Admin\\\\Models\\\\Department" src/` returns zero results
- [ ] `php artisan route:list | grep organization` shows all 8 routes
- [ ] `php artisan migrate` runs without errors (tables created or skipped)
- [ ] `php artisan tinker` — all 7 models resolve correctly
- [ ] `app(ModelConfigRepository::class)->get('organization.company')` returns valid config ← NEW
- [ ] `app(ModelConfigRepository::class)->get('organization.branch')` returns valid config ← NEW
- [ ] All 7 Data configs resolve via ModelConfigRepository ← NEW
- [ ] Organization module appears in `ModuleSwitcher` component
- [ ] Navigation sidebar shows Organization context groups
- [ ] `/organization/dashboard` renders the dashboard view
- [ ] `/organization/companies` renders the DataTable with company data ← NEW
- [ ] `/organization/branches` renders the DataTable with branch data ← NEW
- [ ] DataTableForm opens for creating/editing companies ← NEW
- [ ] `config('ui-library.modules.organization')` returns the module config

### 11.2 Consuming App Validation (HR App)

- [ ] All `use App\Modules\Organization\Models\*` replaced with library namespace
- [ ] All `use App\Modules\Admin\Models\{Company,Location,Department}` replaced
- [ ] Employee CRUD works (references company, branch, department, division, location)
- [ ] Job History CRUD works (references department, division)
- [ ] Attendance Policy CRUD works (references company)
- [ ] Work Pattern CRUD works (references company)
- [ ] Shift CRUD works (references company)
- [ ] All DataTable/Form configs resolve model classes correctly
- [ ] All relationship queries return correct results
- [ ] `php artisan test` passes (if tests exist)

### 11.3 Consuming App Validation (Payroll)

- [ ] All `use App\Modules\Organization\Models\*` replaced
- [ ] Salary Structure CRUD works (references company)
- [ ] Payroll Run CRUD works (references company)
- [ ] Bank Account CRUD works (references company)

### 11.4 Consuming App Validation (Time)

- [ ] All `use App\Modules\Organization\Models\*` replaced
- [ ] Timesheet CRUD works (references company)
- [ ] Clock Event CRUD works (references company)

### 11.5 Consuming App Validation (Leave)

- [ ] All `use App\Modules\Organization\Models\*` replaced
- [ ] Leave Request CRUD works (references company)
- [ ] Leave Policy CRUD works (references company)

### 11.6 Integration Validation

- [ ] Multi-tenancy: switching companies in TopNav filters data correctly
- [ ] `company_id` injection in DataTableForm works with new namespace
- [ ] `company_id` injection in WizardForm works with new namespace
- [ ] Export with company scope works correctly
- [ ] Import with company scope works correctly
- [ ] Settings resolution (user → company → system) works with new Company model
- [ ] `CompanyProvider` contract resolution works (if consuming app binds it)

---

## 12. Pre-Extraction Checklist (NEW SECTION)

Before Phase 4.1 implementation begins, the following prerequisites must be satisfied:

### 12.1 Library Prerequisites

- [ ] **ModelConfigRepository updated**: [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) scans `src/Core/` as fallback for Data configs (see Section 7.3)
- [ ] **DataTable decoupled from HR services**: [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:10-12) no longer imports `App\Modules\Admin\Services\ActivityLogger` or `App\Modules\Admin\Services\AuthorizationService`
- [ ] **Core module pattern validated**: At least one existing Core module (Admin or System) has a working `Data/` config that successfully renders a DataTable — this proves the `ModelConfigRepository` change works end-to-end
- [ ] **View namespace `qf-core::organization` confirmed**: The `bootCoreModules()` pattern for view registration is verified working with existing Admin and System modules

### 12.2 HR App Prerequisites

- [ ] **Organization module location confirmed**: Verify whether Organization models live under `app/Modules/Organization/` or `app/Modules/Admin/` in the HR app
- [ ] **Existing table schema documented**: The HR app's current `companies`, `branches`, `departments`, etc. table schemas are compared against the canonical schema in Section 3
- [ ] **Data migration strategy chosen**: The consuming app team has selected Option A, B, or C from Section 4.3
- [ ] **All `App\Modules\Organization\Models\*` references catalogued**: A complete inventory of files that need namespace updates exists

### 12.3 Documentation Prerequisites

- [ ] **Discrepancy analysis reviewed**: [`docs/architecture-discrepancy-analysis.md`](docs/architecture-discrepancy-analysis.md) has been reviewed and acknowledged
- [ ] **Blueprint restructuring complete**: The architecture blueprint has been restructured into the `docs/architecture/` topic files (see [`docs/architecture/00-index.md`](docs/architecture/00-index.md))
- [ ] **Catch-all routing documented**: The view/config/catch-all interplay is clearly documented in the restructured blueprint

---

## Appendix A: Model Base Class Template

All Organization models follow this template:

```php
<?php

namespace QuickerFaster\UILibrary\Core\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'subdomain', 'logo', 'email', 'phone',
        'website', 'address', 'city', 'state', 'country',
        'postal_code', 'tax_id', 'registration_number',
        'currency', 'timezone', 'date_format',
        'is_active', 'status', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'status' => 'active',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function businessUnits(): HasMany
    {
        return $this->hasMany(BusinessUnit::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
```

## Appendix B: Migration Template

All Organization migrations follow this template:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->nullable()->unique();
                // ... remaining columns ...
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

## Appendix C: Seeder Template

```php
<?php

namespace QuickerFaster\UILibrary\Core\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Core\Organization\Models\Company;
use QuickerFaster\UILibrary\Core\Organization\Models\Branch;
use QuickerFaster\UILibrary\Core\Organization\Models\Department;
use QuickerFaster\UILibrary\Core\Organization\Models\Location;

class OrganizationDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if data already exists
        if (Company::count() > 0) {
            return;
        }

        $company = Company::create([
            'name' => 'Demo Company',
            'code' => 'DEMO',
            'email' => 'info@demo.com',
            'currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_headquarters' => true,
        ]);

        $department = Department::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'General',
            'code' => 'GEN',
        ]);

        Location::create([
            'company_id' => $company->id,
            'name' => 'Main Office',
            'code' => 'MAIN',
            'is_headquarters' => true,
        ]);
    }
}
```

## Appendix D: Data Config Template (NEW)

```php
<?php

// src/Core/Organization/Data/company.php

return [
    'model' => \QuickerFaster\UILibrary\Core\Organization\Models\Company::class,

    'fieldDefinitions' => [
        'name' => [
            'field_type' => 'string',
            'label' => 'Company Name',
            'required' => true,
            'validation' => 'required|string|max:255',
            'sortable' => true,
            'searchable' => true,
        ],
        'code' => [
            'field_type' => 'string',
            'label' => 'Code',
            'validation' => 'nullable|string|max:50',
            'sortable' => true,
            'searchable' => true,
        ],
        'email' => [
            'field_type' => 'string',
            'label' => 'Email',
            'validation' => 'nullable|email|max:255',
            'sortable' => true,
        ],
        'currency' => [
            'field_type' => 'select',
            'label' => 'Currency',
            'validation' => 'nullable|string|max:3',
            'options' => ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'NGN' => 'NGN'],
        ],
        'is_active' => [
            'field_type' => 'boolcheckbox',
            'label' => 'Active',
            'default' => true,
        ],
    ],

    'fieldGroups' => [
        [
            'key' => 'basic',
            'label' => 'Basic Information',
            'icon' => 'fa-building',
            'fields' => ['name', 'code', 'email', 'phone', 'website'],
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'fa-cog',
            'fields' => ['currency', 'timezone', 'date_format', 'is_active'],
        ],
    ],

    'controls' => [
        'files' => [
            'export' => ['xls', 'csv', 'pdf'],
            'import' => ['xls', 'csv'],
            'print' => true,
        ],
        'bulkActions' => [
            'export' => ['xls', 'csv'],
            'delete' => true,
        ],
        'perPage' => [10, 25, 50, 100],
        'search' => true,
        'showHideColumns' => true,
        'filterColumns' => true,
        'addButton' => true,
        'editable' => true,
    ],

    'hiddenFields' => [
        'onTable' => ['metadata'],
        'onNewForm' => [],
        'onEditForm' => [],
        'onQuery' => [],
        'onDetail' => [],
    ],
];