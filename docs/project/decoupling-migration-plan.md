# QuickerFaster UI Library — Architectural Audit & Decoupling/Migration Plan

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **PSR-4 Root**: `src/`
> **View Namespace**: `qf`
> **Blade Component Alias**: `qf`
> **Livewire Prefix**: `qf.`
> **Target Version**: 2.0.0 (standalone, publishable)
> **Date**: 2026-08-07
> **Status**: ✅ IMPLEMENTED — All decoupling and migration steps completed as of 2026-08-09. Phases 2.5 through 4.5 are done. Remaining `App\Modules\*` references are documented as known gaps in [`docs/implementation-plan.md`](docs/implementation-plan.md#11-known-remaining-appmodules-references).

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Detailed File-by-File Migration Map](#2-detailed-file-by-file-migration-map)
3. [Module Relocation Strategy](#3-module-relocation-strategy)
4. [Package Bootstrapping & Installation Scaffold](#4-package-bootstrapping--installation-scaffold)
5. [Dynamic Module Switcher & Top Navigation](#5-dynamic-module-switcher--top-navigation)
6. [Publishable Views & Customization](#6-publishable-views--customization)
7. [Decoupling Contracts & Abstractions](#7-decoupling-contracts--abstractions)
8. [Service Provider & Lifecycle Hooks](#8-service-provider--lifecycle-hooks)
9. [Migration & Data Portability](#9-migration--data-portability)
10. [Testing & Independence Verification](#10-testing--independence-verification)
11. [Phased Implementation Roadmap](#11-phased-implementation-roadmap)
12. [Risk Assessment & Mitigation](#12-risk-assessment--mitigation)
13. [What NOT to Change](#13-what-not-to-change)

---

## 1. Executive Summary

### 1.1 Current State: Critical Coupling Problems

The QuickerFaster UI Library currently cannot function as a standalone Composer package. It is tightly coupled to a specific HR application (`/Users/mac/Projects/LaravelProjects/quick-hr/`) through **10 distinct coupling points**:

| # | Coupling Point | Location | Severity |
|---|---------------|----------|----------|
| 1 | HR model references in SettingsManager resolvers | [`src/Providers/UILibraryServiceProvider.php`](../src/Providers/UILibraryServiceProvider.php:144) | **Critical** |
| 2 | HR-specific Livewire component registration | [`src/Providers/UILibraryServiceProvider.php`](../src/Providers/UILibraryServiceProvider.php:300) | **Critical** |
| 3 | Hardcoded HR routes and view references | [`src/Routes/web.php`](../src/Routes/web.php:49) | **Critical** |
| 4 | `app_path("Modules/...")` hardcoding in NavigationLayout | [`src/Components/NavigationLayout.php`](../src/Components/NavigationLayout.php:105) | **High** |
| 5 | `App\Modules\Admin\Models\Role` reference in ModuleSelector | [`src/Http/Livewire/AccessControls/ModuleSelector.php`](../src/Http/Livewire/AccessControls/ModuleSelector.php:6) | **High** |
| 6 | System module catch-all route hardcoded to `app/Modules/System` | [`src/Providers/ModuleServiceProvider.php`](../src/Providers/ModuleServiceProvider.php:294) | **High** |
| 7 | `App\Models\User` type-hint in onboarding config | [`src/Providers/ModuleServiceProvider.php`](../src/Providers/ModuleServiceProvider.php:52) | **Medium** |
| 8 | HR-specific Custom Livewire components in library | [`src/Http/Livewire/Custom/`](../src/Http/Livewire/Custom/) | **Medium** |
| 9 | HR-specific dependencies/ scaffold folder | [`dependencies/`](dependencies/) | **Medium** |
| 10 | HR-specific config keys (multitenancy, multi_company_payroll) | [`src/Config/quicker-faster-ui.php`](../src/Config/quicker-faster-ui.php:29) | **Low** |

### 1.2 Target State

A standalone Composer package that:

- Installs via `composer require quicker-faster/ui-library:^2.0`
- Bootstraps a fully functional admin panel with `php artisan quicker-faster-ui:install`
- Ships with **Core modules** (Admin, System) inside `src/Core/`
- Discovers **Business modules** (HR, Finance, etc.) from the consuming app's `app/Modules/`
- Provides contracts/interfaces for business modules to implement
- Has zero hardcoded references to any specific business domain

### 1.3 Architecture Diagram (Target State)

```
┌──────────────────────────────────────────────────────────────────┐
│  COMPOSER PACKAGE: quicker-faster/ui-library                     │
│  ─────────────────────────────────────────────────────────────── │
│  src/                                                            │
│  ├── Core/                    ← NEW: Core modules (Admin, System)│
│  │   ├── Admin/               ← FROM: app/Modules/Admin/         │
│  │   │   ├── Config/navigation.php                               │
│  │   │   ├── Database/Migrations/                                │
│  │   │   ├── Database/Seeders/                                   │
│  │   │   ├── Http/Controllers/                                   │
│  │   │   ├── Http/Livewire/                                      │
│  │   │   ├── Models/                                             │
│  │   │   ├── Resources/views/                                    │
│  │   │   └── Routes/web.php                                      │
│  │   ├── System/              ← FROM: app/Modules/System/        │
│  │   │   ├── Config/                                             │
│  │   │   ├── Database/Migrations/                                │
│  │   │   ├── Database/Seeders/                                   │
│  │   │   ├── Http/Livewire/                                      │
│  │   │   ├── Models/                                             │
│  │   │   ├── Resources/views/                                    │
│  │   │   └── Routes/web.php   ← catch-all route moves here       │
│  │   └── Common/Config/       ← app_setup, app_tour, etc.        │
│  ├── Components/              ← UNCHANGED (minus HR refs)        │
│  ├── Contracts/               ← UNCHANGED + NEW contracts        │
│  ├── Config/ui-library.php    ← RENAMED, CLEANED                 │
│  ├── Events/                  ← NEW: ModuleRegistered, etc.      │
│  ├── Http/Livewire/           ← MINUS Custom/ HR components      │
│  ├── Providers/               ← REFACTORED                       │
│  ├── Services/                ← UNCHANGED (minus HR refs)        │
│  ├── Traits/                  ← UNCHANGED                        │
│  └── Widgets/                 ← UNCHANGED                        │
├──────────────────────────────────────────────────────────────────┤
│  CONSUMING APP (e.g., quick-hr)                                  │
│  ─────────────────────────────────────────────────────────────── │
│  app/Modules/                                                    │
│  ├── Hr/                      ← STAYS (business module)          │
│  │   ├── Config/navigation.php                                   │
│  │   ├── Data/employee.php                                       │
│  │   ├── Http/Controllers/                                       │
│  │   ├── Http/Livewire/       ← EmployeeDetail MOVED here        │
│  │   ├── Models/                                                 │
│  │   ├── Resources/views/                                        │
│  │   └── Routes/web.php                                          │
│  └── {OtherBusinessModules}/  ← STAYS                           │
│  config/ui-library.php        ← PUBLISHED, customized            │
│  resources/views/vendor/qf/ ← OPTIONAL overrides                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 2. Detailed File-by-File Migration Map

### 2.1 Files to MOVE (from HR app into library `src/Core/`)

| From (HR App) | To (Library) | Namespace Change |
|---|---|---|
| `app/Modules/Admin/` (entire directory) | `src/Core/Admin/` | `App\Modules\Admin` → `QuickerFaster\UILibrary\Core\Admin` |
| `app/Modules/System/` (entire directory) | `src/Core/System/` | `App\Modules\System` → `QuickerFaster\UILibrary\Core\System` |
| `app/Modules/app_setup.php` | `src/Core/Common/Config/app_setup.php` | N/A (config file) |
| `app/Modules/app_tour.php` | `src/Core/Common/Config/app_tour.php` | N/A (config file) |
| `app/Modules/app_onboarding.php` | `src/Core/Common/Config/app_onboarding.php` | N/A (config file) |
| `app/Modules/app_general_settings.php` | `src/Core/Common/Config/app_general_settings.php` | N/A (config file) |

### 2.2 Files to MOVE (from library into HR app)

| From (Library) | To (HR App) | Reason |
|---|---|---|
| `src/Http/Livewire/Custom/EmployeeDetail.php` | `app/Modules/Hr/Http/Livewire/EmployeeDetail.php` | HR-specific |
| `src/Http/Livewire/Custom/SearchableEmployeeDropdown.php` | `app/Modules/Hr/Http/Livewire/SearchableEmployeeDropdown.php` | HR-specific |
| `src/Http/Livewire/Custom/TaxBandsRepeater.php` | `app/Modules/Hr/Http/Livewire/TaxBandsRepeater.php` | HR-specific |
| `src/Resources/views/livewire/custom/employee-detail.blade.php` | `app/Modules/Hr/Resources/views/livewire/employee-detail.blade.php` | HR-specific |
| `src/Resources/views/livewire/custom/searchable-employee-dropdown.blade.php` | `app/Modules/Hr/Resources/views/livewire/searchable-employee-dropdown.blade.php` | HR-specific |
| `src/Resources/views/livewire/custom/tax-bands-repeater.blade.php` | `app/Modules/Hr/Resources/views/livewire/tax-bands-repeater.blade.php` | HR-specific |

### 2.3 Files to DELETE (from library)

| File | Reason |
|---|---|
| `dependencies/` (entire directory) | HR-specific scaffolds; replaced by proper migrations/seeders in `src/Core/` |
| `src/Http/Livewire/AccessControls/ModuleSelector.php` | References `App\Modules\Admin\Models\Role`; rewrite as generic component |
| `src/Resources/views/livewire/access-controls/module-selector.blade.php` | Tied to old ModuleSelector |

### 2.4 Files to CREATE (new in library)

| File | Purpose |
|---|---|
| `src/Core/Admin/` (directory tree) | Core Admin module |
| `src/Core/System/` (directory tree) | Core System module |
| `src/Core/Common/Config/` (directory) | Shared Core configs |
| `src/Config/ui-library.php` | New clean config (replaces `quicker-faster-ui.php`) |
| `src/Contracts/Modules/ModuleContract.php` | Module interface |
| `src/Contracts/Navigation/NavigationProvider.php` | Navigation provider interface |
| `src/Contracts/Settings/SettingsProvider.php` | Settings provider interface |
| `src/Events/ModuleRegistered.php` | Event: module discovered |
| `src/Events/ModuleBooted.php` | Event: all modules loaded |
| `src/Events/NavigationBuilding.php` | Event: before nav render |
| `src/Http/Livewire/AccessControls/ModuleSelector.php` | Rewritten generic version |
| `src/Http/Livewire/Layouts/Navs/ModuleSwitcher.php` | New: dynamic module switcher |
| `src/Resources/views/livewire/layouts/navs/module-switcher.blade.php` | Module switcher view |
| `src/Resources/views/livewire/access-controls/module-selector.blade.php` | Rewritten view |
| `src/Core/Admin/Database/Migrations/` | Admin module migrations |
| `src/Core/Admin/Database/Seeders/` | Admin module seeders |
| `src/Core/System/Database/Migrations/` | System module migrations |
| `src/Core/System/Database/Seeders/` | System module seeders |

### 2.5 Files to MODIFY (existing library files)

| File | Changes Required |
|---|---|
| [`src/Providers/UILibraryServiceProvider.php`](../src/Providers/UILibraryServiceProvider.php) | Remove HR model refs, remove HR Livewire registrations, add Core module bootstrapping, add event dispatching, update config merge path |
| [`src/Providers/ModuleServiceProvider.php`](../src/Providers/ModuleServiceProvider.php) | Accept module paths as parameter, remove hardcoded `app/Modules/System`, remove `App\Models\User` type-hint, scan both `src/Core/` and `app/Modules/` |
| [`src/Routes/web.php`](../src/Routes/web.php) | Remove `hr::dashboard`, remove `App\Modules\Hr\Http\Controllers\EmployeeProfileController`, remove `/hr/dashboard` redirect, make `/home` configurable |
| [`src/Components/NavigationLayout.php`](../src/Components/NavigationLayout.php) | Replace `app_path("Modules/{$moduleName}/Config/navigation.php")` with configurable path resolution |
| [`src/Services/Settings/SettingsManager.php`](../src/Services/Settings/SettingsManager.php) | Make `getContextHash()` configurable, remove HR-specific session keys |
| [`src/Commands/QuickerFasterInstallUI.php`](../src/Commands/QuickerFasterInstallUI.php) | Remove `dependencies/` copying, add Core module publishing, add super-admin seeding, add proper publishable tags |
| [`src/Config/quicker-faster-ui.php`](../src/Config/quicker-faster-ui.php) | Rename to `ui-library.php`, remove HR-specific sections, add `modules` array, add navigation config |
| [`composer.json`](composer.json) | Add `extra.laravel` for auto-discovery, add `extra.laravel.publishable` tags |

---

## 3. Module Relocation Strategy

### 3.1 Core Modules (Packaged with Library)

These modules provide essential cross-cutting infrastructure that every SaaS application needs:

#### 3.1.1 Admin Module (`src/Core/Admin/`)

**Purpose**: User management, role management, permission assignment.

**Directory Structure**:
```
src/Core/Admin/
├── Config/
│   └── navigation.php          # Admin sidebar navigation items
├── Database/
│   ├── Migrations/
│   │   └── create_admin_tables.php
│   └── Seeders/
│       ├── RoleSeeder.php      # Seeds: super_admin, admin, user
│       └── SuperAdminSeeder.php # Creates initial super-admin user
├── Http/
│   ├── Controllers/
│   │   └── UserManagementController.php
│   └── Livewire/
│       ├── UserList.php
│       ├── UserForm.php
│       └── RoleManager.php
├── Models/
│   ├── User.php                # Base user model (extensible)
│   └── Role.php                # Role model (Spatie Permission)
├── Resources/
│   └── views/
│       ├── dashboard.blade.php
│       ├── users/
│       │   ├── index.blade.php
│       │   └── form.blade.php
│       └── roles/
│           └── index.blade.php
└── Routes/
    └── web.php                 # Admin routes
```

**Namespace**: `QuickerFaster\UILibrary\Core\Admin`

**View Namespace**: `qf-core::admin` (registered via `loadViewsFrom`)

#### 3.1.2 System Module (`src/Core/System/`)

**Purpose**: Catch-all routing, system settings, setup wizard.

**Directory Structure**:
```
src/Core/System/
├── Config/
│   └── navigation.php          # System sidebar navigation
├── Database/
│   ├── Migrations/
│   │   └── create_system_tables.php
│   └── Seeders/
│       └── SystemSettingsSeeder.php
├── Http/
│   └── Livewire/
│       ├── SystemSettings.php
│       └── SetupWizard.php
├── Models/
│   └── System.php              # System settings model
├── Resources/
│   └── views/
│       ├── dashboard.blade.php
│       └── settings/
│           └── index.blade.php
└── Routes/
    └── web.php                 # Catch-all route: /{module}/{view}/{id?}
```

**Namespace**: `QuickerFaster\UILibrary\Core\System`

**View Namespace**: `qf-core::system`

#### 3.1.3 Common Configs (`src/Core/Common/Config/`)

```
src/Core/Common/Config/
├── app_setup.php               # Setup wizard steps
├── app_tour.php                # Application tour configuration
├── app_onboarding.php          # Onboarding steps (uses OnboardingCondition contract)
└── app_general_settings.php    # General system settings schema
```

### 3.2 Business Modules (Stay in App)

Business modules remain in `app/Modules/` with their existing `App\Modules\*` namespace:

```
app/Modules/
├── Hr/
│   ├── Config/navigation.php
│   ├── Data/
│   │   ├── employee.php
│   │   ├── Dashboards/
│   │   └── reports/
│   ├── Database/Migrations/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Livewire/
│   │       ├── EmployeeDetail.php          ← MOVED from library
│   │       ├── SearchableEmployeeDropdown.php ← MOVED from library
│   │       ├── TaxBandsRepeater.php        ← MOVED from library
│   │       └── Payroll/
│   ├── Models/
│   ├── Resources/views/
│   └── Routes/web.php
└── {FutureModules}/
```

### 3.3 Namespace Mapping Reference

| Context | Old Namespace | New Namespace |
|---|---|---|
| Admin Models | `App\Modules\Admin\Models\*` | `QuickerFaster\UILibrary\Core\Admin\Models\*` |
| Admin Controllers | `App\Modules\Admin\Http\Controllers\*` | `QuickerFaster\UILibrary\Core\Admin\Http\Controllers\*` |
| Admin Livewire | `App\Modules\Admin\Http\Livewire\*` | `QuickerFaster\UILibrary\Core\Admin\Http\Livewire\*` |
| System Models | `App\Modules\System\Models\*` | `QuickerFaster\UILibrary\Core\System\Models\*` |
| System Livewire | `App\Modules\System\Http\Livewire\*` | `QuickerFaster\UILibrary\Core\System\Http\Livewire\*` |
| HR (unchanged) | `App\Modules\Hr\*` | `App\Modules\Hr\*` |

### 3.4 Service Provider Updates for Module Relocation

The refactored [`UILibraryServiceProvider`](../src/Providers/UILibraryServiceProvider.php) must:

1. Register Core module views from `src/Core/{Module}/Resources/views/` with namespace `qf-core::{module}`
2. Register Core module routes from `src/Core/{Module}/Routes/web.php`
3. Register Core module migrations from `src/Core/{Module}/Database/Migrations/`
4. Register Core module Livewire components
5. Fire `ModuleRegistered` event for each Core module

The refactored [`ModuleServiceProvider`](../src/Providers/ModuleServiceProvider.php) must:

1. Accept an array of module base paths: `['src/Core/', 'app/Modules/']`
2. Scan each path for modules
3. Register views, routes, migrations, and event listeners from all discovered modules
4. Load System catch-all route from `src/Core/System/Routes/web.php` (not `app/Modules/System/`)
5. No longer hardcode `app/Modules/System` path

---

## 4. Package Bootstrapping & Installation Scaffold

### 4.1 Updated `composer.json`

```json
{
    "name": "quicker-faster/ui-library",
    "description": "A config-driven Laravel UI library with Livewire 3, Fortify auth, and module auto-discovery",
    "type": "library",
    "license": "MIT",
    "version": "2.0.0",
    "autoload": {
        "psr-4": {
            "QuickerFaster\\UILibrary\\": "src/",
            "QuickerFaster\\UILibrary\\Core\\": "src/Core/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "QuickerFaster\\UILibrary\\Providers\\UILibraryServiceProvider",
                "QuickerFaster\\UILibrary\\Providers\\ModuleServiceProvider",
                "QuickerFaster\\UILibrary\\Providers\\FortifyServiceProvider"
            ]
        }
    },
    "require": {
        "php": "^8.1",
        "livewire/livewire": "^3",
        "barryvdh/laravel-dompdf": "^3.0",
        "maatwebsite/excel": "^3.1",
        "laravel/fortify": "^1.0",
        "laravel/socialite": "^5.0",
        "spatie/laravel-permission": "^6.21",
        "spatie/laravel-onboard": "^2.6"
    },
    "require-dev": {
        "orchestra/testbench": "^8.0",
        "phpunit/phpunit": "^10.0"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

### 4.2 New Config: `src/Config/ui-library.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Registry
    |--------------------------------------------------------------------------
    | Defines all known modules. Core modules are shipped with the package.
    | Business modules register themselves by merging into this array.
    */
    'modules' => [
        'admin' => [
            'enabled' => true,
            'label' => 'Administration',
            'icon' => 'fa-shield-haltered',
            'route' => 'admin.dashboard',
            'order' => 900,
            'roles' => ['super_admin'],
            'core' => true,
        ],
        'system' => [
            'enabled' => true,
            'label' => 'System',
            'icon' => 'fa-cog',
            'route' => 'system.dashboard',
            'order' => 999,
            'roles' => ['super_admin'],
            'core' => true,
        ],
        // Business modules are appended at runtime by ModuleServiceProvider
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Discovery Paths
    |--------------------------------------------------------------------------
    | Paths scanned for modules. Core modules live in the package; business
    | modules live in the consuming application.
    */
    'module_paths' => [
        'core' => base_path('vendor/quicker-faster/ui-library/src/Core'),
        'business' => base_path('app/Modules'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Home Route
    |--------------------------------------------------------------------------
    | Where to redirect after login. Set to a named route or URL.
    */
    'home_route' => env('UI_LIBRARY_HOME_ROUTE', 'admin.dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Socialite Configuration
    |--------------------------------------------------------------------------
    */
    'socialite' => [
        'enabled' => env('UI_LIBRARY_SOCIALITE_ENABLED', false),
        'providers' => [
            'google' => [
                'enabled' => env('UI_LIBRARY_SOCIALITE_GOOGLE', false),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
            ],
            'github' => [
                'enabled' => env('UI_LIBRARY_SOCIALITE_GITHUB', false),
                'client_id' => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Resolution
    |--------------------------------------------------------------------------
    | Configure how settings cascade. Each resolver is a callable or class
    | implementing SettingsProvider contract.
    */
    'settings' => [
        'resolvers' => [
            'user' => null,     // Set by UILibraryServiceProvider
            'company' => null,  // Set by consuming app if multi-tenant
            'system' => null,   // Set by UILibraryServiceProvider
        ],
        'cache_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'top_bar' => [
            'enabled' => true,
            'show_module_switcher' => true,
            'show_company_switcher' => false,  // Enable in multi-tenant apps
        ],
        'sidebar' => [
            'initial_state' => 'full',  // 'full' | 'collapsed' | 'hidden'
        ],
        'bottom_bar' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumb
    |--------------------------------------------------------------------------
    */
    'breadcrumb' => [
        'show_home' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Title
    |--------------------------------------------------------------------------
    */
    'title' => [
        'separator' => ' - ',
        'app_name' => env('APP_NAME', 'QuickerFaster'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'exports' => true,
        'imports' => true,
        'reports' => true,
        'approvals' => true,
        'onboarding' => true,
        'tour' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Publishable Tags
    |--------------------------------------------------------------------------
    | Registered automatically. Listed here for documentation.
    |
    | ui-library-config       → config/ui-library.php
    | ui-library-assets       → public/vendor/ui-library/
    | ui-library-views        → resources/views/vendor/qf/
    | ui-library-core-views   → resources/views/vendor/qf-core/
    | ui-library-migrations   → Core module migrations (published for customization)
    */
];
```

### 4.3 Post-Install Workflow

```bash
# Step 1: Require the package
composer require quicker-faster/ui-library:^2.0

# Step 2: Publish config (REQUIRED)
php artisan vendor:publish --tag=ui-library-config

# Step 3: Publish assets (REQUIRED)
php artisan vendor:publish --tag=ui-library-assets

# Step 4: Run the installer
php artisan quicker-faster-ui:install

# Optional: Publish views for customization
php artisan vendor:publish --tag=ui-library-views
php artisan vendor:publish --tag=ui-library-core-views
```

### 4.4 Refactored `quicker-faster-ui:install` Command

The install command must be rewritten to:

1. **Publish Core module migrations** to the app's `database/migrations/` directory
2. **Run migrations** (`php artisan migrate`)
3. **Seed default roles** via Spatie Permission:
   - `super_admin` — full access
   - `admin` — administrative access
   - `user` — basic authenticated access
4. **Prompt for super-admin credentials** (email, password) and create the user
5. **Create storage link** (`php artisan storage:link`)
6. **Generate app key** if missing (`php artisan key:generate`)
7. **Publish Core module views** (optional, with confirmation)
8. **Clear caches** (`config:clear`, `view:clear`, `route:clear`)

**Key changes from current install command**:
- **REMOVE**: All `dependencies/` folder copying (`overrideModelFiles`, `overrideDatabaseMigrationFiles`, `overrideDatabaseSeederFiles`, `overrideRouteFiles`, etc.)
- **REMOVE**: `vendor:publish --tag=qf-modules` (no longer copies `app/Modules`)
- **REMOVE**: `vendor:publish --tag=qf-public-assets` (replaced by `ui-library-assets`)
- **ADD**: Core module migration publishing
- **ADD**: Interactive super-admin creation
- **ADD**: Role seeding

### 4.5 What a Fresh Install Delivers

After running `quicker-faster-ui:install`, the consuming app has:

- ✅ Login page (Fortify-powered, `qf::auth.login`)
- ✅ Register page (`qf::auth.register`)
- ✅ Password reset flow (`qf::auth.forgot-password`, `qf::auth.reset-password`)
- ✅ Dashboard (generic, configurable via `ui-library.home_route`)
- ✅ Admin module (user CRUD, role assignment via Spatie Permission)
- ✅ System settings panel
- ✅ Catch-all routing for future business modules
- ✅ Top navigation bar with module switcher
- ✅ Sidebar with context-aware navigation
- ✅ Mobile bottom bar
- ✅ Super-admin user (created during install)
- ✅ Default roles (super_admin, admin, user)

---

## 5. Dynamic Module Switcher & Top Navigation

### 5.1 Module Registration

Modules are registered in the `modules` array of [`config/ui-library.php`](../src/Config/ui-library.php). Core modules are defined in the package config. Business modules are appended at runtime by [`ModuleServiceProvider`](../src/Providers/ModuleServiceProvider.php).

**How business modules register themselves**:

The `ModuleServiceProvider` scans `app/Modules/*` and for each directory found, checks if it has a `Config/navigation.php` or `Routes/web.php`. If so, it appends an entry to `config('ui-library.modules')`:

```php
// In ModuleServiceProvider::discoverBusinessModules()
$businessPath = base_path('app/Modules');
foreach (glob($businessPath . '/*', GLOB_ONLYDIR) as $dir) {
    $module = strtolower(basename($dir));
    
    // Skip if already registered (e.g., as a core module)
    if (config("ui-library.modules.{$module}")) {
        continue;
    }
    
    // Auto-discover module metadata
    config()->set("ui-library.modules.{$module}", [
        'enabled' => true,
        'label' => ucfirst($module),
        'icon' => 'fa-cube',
        'route' => "{$module}.dashboard",
        'order' => 100,
        'roles' => ['*'],
        'core' => false,
    ]);
    
    event(new ModuleRegistered($module, $dir));
}
```

### 5.2 Module Switcher Component

A new Livewire component `ModuleSwitcher` renders the top navigation module icons:

```php
// src/Http/Livewire/Layouts/Navs/ModuleSwitcher.php
namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

class ModuleSwitcher extends Component
{
    public array $modules = [];
    public ?string $activeModule = null;

    public function mount()
    {
        $this->modules = collect(config('ui-library.modules', []))
            ->filter(fn($m) => $m['enabled'] ?? false)
            ->filter(function ($m) {
                $roles = $m['roles'] ?? ['*'];
                if (in_array('*', $roles)) return true;
                return auth()->user()?->hasAnyRole($roles);
            })
            ->sortBy('order')
            ->toArray();
        
        $this->activeModule = session('active_module', 'admin');
    }

    public function switchModule(string $module)
    {
        session(['active_module' => $module]);
        $route = config("ui-library.modules.{$module}.route", 'admin.dashboard');
        return redirect()->route($route);
    }

    public function render()
    {
        return view('qf::livewire.layouts.navs.module-switcher');
    }
}
```

### 5.3 NavigationLayout Decoupling

The [`NavigationLayout`](../src/Components/NavigationLayout.php) component currently hardcodes:

```php
$configPath = app_path("Modules/{$moduleName}/Config/navigation.php");
```

This must be replaced with a configurable path resolver:

```php
protected function resolveNavigationConfigPath(string $moduleName): ?string
{
    // 1. Check if module is a Core module
    $corePath = base_path(
        "vendor/quicker-faster/ui-library/src/Core/" . ucfirst($moduleName) . "/Config/navigation.php"
    );
    if (file_exists($corePath)) {
        return $corePath;
    }
    
    // 2. Check if module is a Business module
    $businessPath = app_path("Modules/" . ucfirst($moduleName) . "/Config/navigation.php");
    if (file_exists($businessPath)) {
        return $businessPath;
    }
    
    // 3. Check published override
    $publishedPath = resource_path(
        "views/vendor/qf-core/" . strtolower($moduleName) . "/Config/navigation.php"
    );
    if (file_exists($publishedPath)) {
        return $publishedPath;
    }
    
    return null;
}
```

### 5.4 TopNav Component Updates

The [`TopNav`](../src/Http/Livewire/Layouts/Navs/TopNav.php) component must:

1. Read `config('ui-library.modules')` for module list
2. Render module icons filtered by user role
3. Highlight the active module based on `session('active_module')`
4. Include the `ModuleSwitcher` child component

---

## 6. Publishable Views & Customization

### 6.1 View Hierarchy

```
Package Views (read-only, fallback):
  src/Core/{Module}/Resources/views/     → namespace: qf-core::{module}
  src/Resources/views/                   → namespace: qf

Published Views (customizable):
  resources/views/vendor/qf-core/{module}/  → overrides Core module views
  resources/views/vendor/qf/                → overrides library views
```

### 6.2 View Namespace Registration

In [`UILibraryServiceProvider::bootCoreModules()`](../src/Providers/UILibraryServiceProvider.php):

```php
private function bootCoreModules(): void
{
    $corePath = __DIR__ . '/../Core';
    
    foreach (['Admin', 'System'] as $module) {
        $viewPath = "{$corePath}/{$module}/Resources/views";
        
        if (is_dir($viewPath)) {
            // Register package views
            $this->loadViewsFrom($viewPath, 'qf-core::' . strtolower($module));
            
            // Allow publishing for customization
            $this->publishes([
                $viewPath => resource_path('views/vendor/qf-core/' . strtolower($module)),
            ], 'ui-library-core-views');
        }
    }
}
```

### 6.3 Fallback Logic

Laravel's native `loadViewsFrom` handles the fallback automatically:

1. First checks `resources/views/vendor/qf-core/{module}/` (published override)
2. Falls back to `src/Core/{Module}/Resources/views/` (package default)

### 6.4 Overriding Individual Blade Files

```bash
# Publish all Core module views
php artisan vendor:publish --tag=ui-library-core-views

# Publish all library views (auth, layouts, components)
php artisan vendor:publish --tag=ui-library-views
```

Then edit files in:
- `resources/views/vendor/qf-core/admin/dashboard.blade.php`
- `resources/views/vendor/qf/auth/login.blade.php`
- etc.

### 6.5 Auth Views

The package provides Fortify-compatible auth views:

| View | Path | Publishable |
|---|---|---|
| Login | `qf::auth.login` | `--tag=ui-library-views` |
| Register | `qf::auth.register` | `--tag=ui-library-views` |
| Forgot Password | `qf::auth.forgot-password` | `--tag=ui-library-views` |
| Reset Password | `qf::auth.reset-password` | `--tag=ui-library-views` |

### 6.6 Publishable Tags Summary

| Tag | Publishes To | Description |
|---|---|---|
| `ui-library-config` | `config/ui-library.php` | Main configuration |
| `ui-library-assets` | `public/vendor/ui-library/` | CSS, JS, images, fonts |
| `ui-library-views` | `resources/views/vendor/qf/` | Auth, layout, component views |
| `ui-library-core-views` | `resources/views/vendor/qf-core/` | Admin & System module views |
| `ui-library-migrations` | `database/migrations/` | Core module migrations |

---

## 7. Decoupling Contracts & Abstractions

### 7.1 Existing Contracts (Preserved)

These contracts remain in [`src/Contracts/`](../src/Contracts/) unchanged:

| Contract | File | Methods |
|---|---|---|
| `FieldType` | [`src/Contracts/FieldTypes/FieldType.php`](../src/Contracts/FieldTypes/FieldType.php) | `renderForm()`, `renderTable()`, `renderDetail()`, `getValidationRules()`, `getOptions()`, `isRelationship()`, `getRelationshipConfig()`, `getLabel()`, `getName()`, `renderInlineEditor()` |
| `Widget` | [`src/Contracts/Widgets/Widget.php`](../src/Contracts/Widgets/Widget.php) | `setData()`, `render()`, `getTitle()`, `getWidth()` |
| `OnboardingCondition` | [`src/Contracts/OnboardingCondition.php`](../src/Contracts/OnboardingCondition.php) | `__invoke($user)` |

### 7.2 New Contracts

#### 7.2.1 `ModuleContract`

```php
// src/Contracts/Modules/ModuleContract.php
namespace QuickerFaster\UILibrary\Contracts\Modules;

interface ModuleContract
{
    /**
     * Unique module identifier (e.g., 'admin', 'hr').
     */
    public function getName(): string;

    /**
     * Human-readable label.
     */
    public function getLabel(): string;

    /**
     * Icon class for navigation.
     */
    public function getIcon(): string;

    /**
     * Named route to the module's dashboard.
     */
    public function getDashboardRoute(): string;

    /**
     * Sort order in navigation (lower = first).
     */
    public function getOrder(): int;

    /**
     * Roles that can access this module. ['*'] = all authenticated users.
     */
    public function getRequiredRoles(): array;

    /**
     * Whether this is a Core module (shipped with package).
     */
    public function isCore(): bool;

    /**
     * Absolute path to the module directory.
     */
    public function getPath(): string;
}
```

#### 7.2.2 `NavigationProvider`

```php
// src/Contracts/Navigation/NavigationProvider.php
namespace QuickerFaster\UILibrary\Contracts\Navigation;

interface NavigationProvider
{
    /**
     * Return the navigation configuration array for this module.
     * Must include: context_groups, contexts, shared_items, shared_top_items, layout
     */
    public function getNavigationConfig(): array;

    /**
     * Return the settings configuration array for this module (optional).
     */
    public function getSettingsConfig(): ?array;
}
```

#### 7.2.3 `SettingsProvider`

```php
// src/Contracts/Settings/SettingsProvider.php
namespace QuickerFaster\UILibrary\Contracts\Settings;

interface SettingsProvider
{
    /**
     * Resolve a setting value for the given key.
     * Return null if this provider cannot resolve the key.
     */
    public function resolve(string $key): mixed;

    /**
     * Priority of this resolver (lower = higher priority).
     */
    public function priority(): int;
}
```

### 7.3 How Business Modules Implement Contracts

**Example: HR module implements `Widget`**:

```php
// app/Modules/Hr/Widgets/HeadcountWidget.php
namespace App\Modules\Hr\Widgets;

use QuickerFaster\UILibrary\Contracts\Widgets\Widget;

class HeadcountWidget implements Widget
{
    public function __construct(array $definition) { /* ... */ }
    public function setData(): void { /* query HR data */ }
    public function render(): string { return view('hr::widgets.headcount', $this->data); }
    public function getTitle(): ?string { return 'Headcount'; }
    public function getWidth(): int { return 6; }
}
```

**Example: HR module implements `OnboardingCondition`**:

```php
// app/Modules/Hr/Conditions/HasEmployees.php
namespace App\Modules\Hr\Conditions;

use QuickerFaster\UILibrary\Contracts\OnboardingCondition;

class HasEmployees implements OnboardingCondition
{
    public function __invoke($user): bool
    {
        return \App\Modules\Hr\Models\Employee::exists();
    }
}
```

### 7.4 Removal of HR-Specific Code

| Action | Detail |
|---|---|
| Move `EmployeeDetail` | `src/Http/Livewire/Custom/EmployeeDetail.php` → `app/Modules/Hr/Http/Livewire/EmployeeDetail.php` |
| Move `SearchableEmployeeDropdown` | `src/Http/Livewire/Custom/SearchableEmployeeDropdown.php` → `app/Modules/Hr/Http/Livewire/SearchableEmployeeDropdown.php` |
| Move `TaxBandsRepeater` | `src/Http/Livewire/Custom/TaxBandsRepeater.php` → `app/Modules/Hr/Http/Livewire/TaxBandsRepeater.php` |
| Remove HR Livewire registrations | Delete lines 300-333 in [`UILibraryServiceProvider.php`](../src/Providers/UILibraryServiceProvider.php:300) |
| Remove HR route references | Delete lines 49, 81, 95-101 in [`src/Routes/web.php`](../src/Routes/web.php:49) |
| Remove HR model references | Delete lines 155-168 in [`UILibraryServiceProvider.php`](../src/Providers/UILibraryServiceProvider.php:155) |
| Remove `App\Models\User` type-hint | Change line 52 in [`ModuleServiceProvider.php`](../src/Providers/ModuleServiceProvider.php:52) to use `$user` parameter without type-hint |

---

## 8. Service Provider & Lifecycle Hooks

### 8.1 Refactored `UILibraryServiceProvider`

```php
<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Laravel\Fortify\Fortify;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;
use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;
use QuickerFaster\UILibrary\Events\ModuleRegistered;
use QuickerFaster\UILibrary\Events\ModuleBooted;
use QuickerFaster\UILibrary\Contracts\Settings\SettingsProvider;

class UILibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../Config/ui-library.php', 'ui-library');

        // Bind core services
        $this->app->singleton(SettingsManager::class, function ($app) {
            $manager = new SettingsManager();

            // Register resolvers from config
            $resolvers = config('ui-library.settings.resolvers', []);
            foreach ($resolvers as $name => $resolver) {
                if ($resolver && is_callable($resolver)) {
                    $manager->addResolver($name, $resolver);
                }
            }

            return $manager;
        });

        $this->app->singleton(ModelConfigRepository::class);
    }

    public function boot(): void
    {
        // 1. Boot Core modules (Admin, System)
        $this->bootCoreModules();

        // 2. Fire ModuleRegistered for each Core module
        event(new ModuleRegistered('admin', __DIR__ . '/../Core/Admin'));
        event(new ModuleRegistered('system', __DIR__ . '/../Core/System'));

        // 3. Fire ModuleBooted
        event(new ModuleBooted());

        // 4. Register views, components, Livewire, commands
        $this->registerViews();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();
        $this->registerCommands();
        $this->registerPublishables();
        $this->registerFortifyViews();
        $this->registerSocialiteProviders();
        $this->registerBladeDirectives();
        $this->registerTranslations();
    }

    private function bootCoreModules(): void
    {
        $corePath = __DIR__ . '/../Core';

        foreach (['Admin', 'System'] as $module) {
            $moduleLower = strtolower($module);
            $modulePath = "{$corePath}/{$module}";

            // Register views
            $viewPath = "{$modulePath}/Resources/views";
            if (is_dir($viewPath)) {
                $this->loadViewsFrom($viewPath, "qf-core::{$moduleLower}");
                $this->publishes([
                    $viewPath => resource_path("views/vendor/qf-core/{$moduleLower}"),
                ], 'ui-library-core-views');
            }

            // Register routes
            $routePath = "{$modulePath}/Routes/web.php";
            if (file_exists($routePath)) {
                $this->loadRoutesFrom($routePath);
            }

            // Register migrations
            $migrationPath = "{$modulePath}/Database/Migrations";
            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
                $this->publishes([
                    $migrationPath => database_path('migrations'),
                ], 'ui-library-migrations');
            }
        }
    }

    private function registerViews(): void
    {
        $viewPath = __DIR__ . '/../Resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'qf');
        }
    }

    private function registerBladeComponents(): void
    {
        Blade::component('qf::layouts.app', 'layout');
        Blade::component('qf::layouts.guest', 'guest-layout');
        Blade::component('qf::components.breadcrumb', 'breadcrumb');
        Blade::componentNamespace('QuickerFaster\\UILibrary\\Components', 'qf');
    }

    private function registerLivewireComponents(): void
    {
        // Layout
        Livewire::component('qf.top-nav', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\TopNav::class);
        Livewire::component('qf.sidebar', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\Sidebar::class);
        Livewire::component('qf.bottom-bar', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\BottomBar::class);
        Livewire::component('qf.navigation-layout', \QuickerFaster\UILibrary\Http\Livewire\Layouts\NavigationLayout::class);
        Livewire::component('qf.horizontal-context-menu', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\HorizontalContextMenu::class);
        Livewire::component('qf.menu-renderer', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\MenuRenderer::class);
        Livewire::component('qf.module-switcher', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\ModuleSwitcher::class);

        // DataTables
        Livewire::component('qf.data-table', \QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable::class);
        Livewire::component('qf.data-table-form', \QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTableForm::class);
        Livewire::component('qf.data-table-detail', \QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTableDetail::class);

        // Modals
        Livewire::component('qf.form-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\FormModal::class);
        Livewire::component('qf.detail-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\DetailModal::class);
        Livewire::component('qf.alert-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\AlertModal::class);
        Livewire::component('qf.import-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\ImportModal::class);
        Livewire::component('qf.export-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\ExportModal::class);
        Livewire::component('qf.export-progress', \QuickerFaster\UILibrary\Http\Livewire\Modals\ExportProgress::class);
        Livewire::component('qf.document-preview-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\DocumentPreviewModal::class);
        Livewire::component('qf.crop-image-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\CropImageModal::class);

        // Wizards
        Livewire::component('qf.wizard', \QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard::class);
        Livewire::component('qf.setup-wizard', \QuickerFaster\UILibrary\Http\Livewire\Wizards\SetupWizard::class);
        Livewire::component('qf.setup-checklist', \QuickerFaster\UILibrary\Http\Livewire\SetupChecklist::class);
        Livewire::component('qf.wizard-form', \QuickerFaster\UILibrary\Http\Livewire\Wizards\WizardForm::class);

        // Dashboard
        Livewire::component('qf.dashboard', \QuickerFaster\UILibrary\Http\Livewire\Dashboards\Dashboard::class);

        // Access Control
        Livewire::component('qf.access-control-manager', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\AccessControlManager::class);
        Livewire::component('qf.module-selector', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\ModuleSelector::class);
        Livewire::component('qf.role-assignment-manager', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\RoleAssignmentManager::class);
        Livewire::component('qf.permission-manager', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\PermissionManager::class);

        // Buttons
        Livewire::component('qf.toggle-button', \QuickerFaster\UILibrary\Http\Livewire\Buttons\ToggleButton::class);
        Livewire::component('qf.toggle-button-group', \QuickerFaster\UILibrary\Http\Livewire\Buttons\ToggleButtonGroup::class);

        // Documents
        Livewire::component('qf.document-preview', \QuickerFaster\UILibrary\Http\Livewire\DocumentPreview::class);

        // Reports
        Livewire::component('qf.report-index', \QuickerFaster\UILibrary\Http\Livewire\Reports\ReportIndex::class);
        Livewire::component('qf.report-viewer', \QuickerFaster\UILibrary\Http\Livewire\Reports\ReportViewer::class);
        Livewire::component('qf.report-builder', \QuickerFaster\UILibrary\Http\Livewire\Reports\ReportBuilder::class);

        // Settings
        Livewire::component('qf.settings-panel', \QuickerFaster\UILibrary\Http\Livewire\Settings\SettingsPanel::class);

        // Misc
        Livewire::component('qf.drawer', \QuickerFaster\UILibrary\Http\Livewire\Drawer::class);
        Livewire::component('qf.filter-panel', \QuickerFaster\UILibrary\Http\Livewire\FilterPanel::class);
        Livewire::component('qf.search-panel', \QuickerFaster\UILibrary\Http\Livewire\SearchPanel::class);
        Livewire::component('qf.collapsible', \QuickerFaster\UILibrary\Http\Livewire\Collapsible::class);
        Livewire::component('qf.background-jobs-panel', \QuickerFaster\UILibrary\Http\Livewire\BackgroundJobsPanel::class);
        Livewire::component('qf.column-manager', \QuickerFaster\UILibrary\Http\Livewire\ColumnManager::class);
        Livewire::component('qf.import-form', \QuickerFaster\UILibrary\Http\Livewire\DataTables\ImportForm::class);
        Livewire::component('qf.recent-exports', \QuickerFaster\UILibrary\Http\Livewire\Exports\RecentExports::class);
        Livewire::component('qf.recent-imports', \QuickerFaster\UILibrary\Http\Livewire\Imports\RecentImports::class);

        // Approvals
        Livewire::component('qf.approval-actions', \QuickerFaster\UILibrary\Http\Livewire\Approvals\ApprovalActions::class);
        Livewire::component('qf.approval-history-timeline', \QuickerFaster\UILibrary\Http\Livewire\Approvals\ApprovalHistoryTimeline::class);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \QuickerFaster\UILibrary\Commands\QuickerFasterInstallUI::class,
                \QuickerFaster\UILibrary\Commands\CleanExports::class,
                \QuickerFaster\UILibrary\Commands\CleanImportErrors::class,
            ]);
        }
    }

    private function registerPublishables(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../Config/ui-library.php' => config_path('ui-library.php'),
        ], 'ui-library-config');

        // Assets
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/ui-library'),
        ], 'ui-library-assets');

        // Views
        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('views/vendor/qf'),
        ], 'ui-library-views');
    }

    private function registerFortifyViews(): void
    {
        Fortify::loginView(fn() => view('qf::auth.login'));
        Fortify::registerView(fn() => view('qf::auth.register'));
        Fortify::requestPasswordResetLinkView(fn() => view('qf::auth.forgot-password'));
        Fortify::resetPasswordView(fn() => view('qf::auth.reset-password'));
    }

    private function registerSocialiteProviders(): void
    {
        $providers = ['google', 'github'];
        foreach ($providers as $provider) {
            if (config("ui-library.socialite.providers.{$provider}.enabled")) {
                config([
                    "services.{$provider}" => [
                        'client_id' => env(strtoupper($provider) . '_CLIENT_ID'),
                        'client_secret' => env(strtoupper($provider) . '_CLIENT_SECRET'),
                        'redirect' => env(strtoupper($provider) . '_REDIRECT_URI', ''),
                    ],
                ]);
            }
        }
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('setting', function ($expression) {
            return "<?php echo app(\\QuickerFaster\\UILibrary\\Services\\Settings\\SettingsManager::class)->get({$expression}); ?>";
        });
    }

    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'qf');
    }
}
```

### 8.2 Refactored `ModuleServiceProvider`

```php
<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Event;
use QuickerFaster\UILibrary\Events\ModuleRegistered;
use QuickerFaster\UILibrary\Events\NavigationBuilding;
use Spatie\Onboard\Facades\Onboard;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->discoverBusinessModules();
        $this->registerModuleConfigs();
        $this->registerOnboardingConfig();
    }

    /**
     * Discover business modules in app/Modules/ and register them.
     */
    private function discoverBusinessModules(): void
    {
        $businessPath = base_path('app/Modules');

        if (!is_dir($businessPath)) {
            return;
        }

        $moduleDirectories = glob($businessPath . '/*', GLOB_ONLYDIR);

        foreach ($moduleDirectories as $directory) {
            $moduleName = strtolower(basename($directory));

            // Skip if already registered as Core module
            if (config("ui-library.modules.{$moduleName}")) {
                continue;
            }

            // Register module in config
            config()->set("ui-library.modules.{$moduleName}", [
                'enabled' => true,
                'label' => ucfirst($moduleName),
                'icon' => 'fa-cube',
                'route' => "{$moduleName}.dashboard",
                'order' => 100,
                'roles' => ['*'],
                'core' => false,
            ]);

            // Fire event
            event(new ModuleRegistered($moduleName, $directory));

            // Register views
            $viewPath = "{$directory}/Resources/views";
            if (is_dir($viewPath)) {
                $this->loadViewsFrom($viewPath, $moduleName);
            }

            // Register routes (web)
            $webRoutePath = "{$directory}/Routes/web.php";
            if (file_exists($webRoutePath)) {
                \Route::middleware('web')->group($webRoutePath);
            }

            // Register routes (api)
            $apiRoutePath = "{$directory}/Routes/api.php";
            if (file_exists($apiRoutePath)) {
                \Route::prefix('api')->middleware('api')->group($apiRoutePath);
            }

            // Register migrations
            $migrationPath = "{$directory}/Database/Migrations";
            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }

            // Register event listeners
            $this->registerModuleEvents($directory, $moduleName);
        }

        // Load System catch-all route LAST (from Core, not app/Modules)
        $systemCatchAll = base_path('vendor/quicker-faster/ui-library/src/Core/System/Routes/web.php');
        if (file_exists($systemCatchAll)) {
            \Route::middleware('web')->group($systemCatchAll);
        }
    }

    /**
     * Register event listeners from a module's Listeners directory.
     */
    private function registerModuleEvents(string $modulePath, string $moduleName): void
    {
        $listenersPath = "{$modulePath}/Listeners";

        if (!is_dir($listenersPath)) {
            return;
        }

        $cacheKey = "module_event_listeners_{$moduleName}";

        if (app()->environment('production') && cache()->has($cacheKey)) {
            foreach (cache()->get($cacheKey) as $eventClass => $listenerClass) {
                Event::listen($eventClass, $listenerClass);
            }
            return;
        }

        $listenersMap = [];
        foreach (File::allFiles($listenersPath) as $file) {
            $listenerClass = $this->getClassFromFile($moduleName, 'Listeners', $file->getPathname());

            if (!class_exists($listenerClass)) {
                continue;
            }

            $eventClass = $this->getEventFromListener($listenerClass);
            if ($eventClass && class_exists($eventClass)) {
                Event::listen($eventClass, $listenerClass);
                $listenersMap[$eventClass] = $listenerClass;
            }
        }

        if (app()->environment('production')) {
            cache()->forever($cacheKey, $listenersMap);
        }
    }

    /**
     * Register module configs (dashboard, report, global).
     */
    private function registerModuleConfigs(): void
    {
        $modulePaths = [
            base_path('vendor/quicker-faster/ui-library/src/Core'),
            base_path('app/Modules'),
        ];

        foreach ($modulePaths as $basePath) {
            if (!is_dir($basePath)) continue;

            // Dashboard configs
            $dashboardFiles = glob($basePath . '/*/Data/Dashboards/*.php');
            foreach ($dashboardFiles as $path) {
                $module = strtolower(basename(dirname(dirname(dirname($path)))));
                $file = pathinfo($path, PATHINFO_FILENAME);
                $this->mergeConfigFrom($path, "{$module}_{$file}");
            }

            // Report configs
            $reportFiles = glob($basePath . '/*/Data/reports/*.php');
            $reportKeys = [];
            foreach ($reportFiles as $path) {
                $module = strtolower(basename(dirname(dirname(dirname($path)))));
                $file = pathinfo($path, PATHINFO_FILENAME);
                $key = "{$module}_{$file}";
                $this->mergeConfigFrom($path, $key);
                $reportKeys[] = $key;
            }
            if (!empty($reportKeys)) {
                $existing = config('reports.registered', []);
                config(['reports.registered' => array_merge($existing, $reportKeys)]);
            }
        }

        // Global configs from Core Common
        $commonConfigPath = base_path('vendor/quicker-faster/ui-library/src/Core/Common/Config');
        if (is_dir($commonConfigPath)) {
            foreach (['app_setup', 'app_tour', 'app_onboarding', 'app_general_settings'] as $config) {
                $path = "{$commonConfigPath}/{$config}.php";
                if (file_exists($path)) {
                    $this->mergeConfigFrom($path, $config);
                }
            }
        }
    }

    /**
     * Register onboarding steps from config.
     */
    private function registerOnboardingConfig(): void
    {
        $steps = config('app_onboarding.steps', []);

        foreach ($steps as $step) {
            Onboard::addStep($step['title'])
                ->link($step['link'])
                ->cta($step['cta'])
                ->completeIf(function ($user) use ($step) {
                    if (isset($step['model'])) {
                        return $step['model']::exists();
                    }

                    if (isset($step['condition'])) {
                        $condition = app($step['condition']);
                        return $condition($user);
                    }

                    return false;
                });
        }
    }

    private function getClassFromFile(string $moduleName, string $directory, string $filePath): string
    {
        $className = str_replace('.php', '', basename($filePath));
        return "App\\Modules\\{$moduleName}\\{$directory}\\{$className}";
    }

    private function getEventFromListener(string $class): ?string
    {
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->hasMethod('handle')) {
                $method = $reflection->getMethod('handle');
                $parameters = $method->getParameters();
                if (!empty($parameters)) {
                    $parameterType = $parameters[0]->getType();
                    return $parameterType ? $parameterType->getName() : null;
                }
            }
        } catch (\ReflectionException $e) {
            // Silently skip
        }
        return null;
    }
}
```

### 8.3 New Events

```php
// src/Events/ModuleRegistered.php
namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ModuleRegistered
{
    use Dispatchable;

    public function __construct(
        public readonly string $name,
        public readonly string $path,
    ) {}
}
```

```php
// src/Events/ModuleBooted.php
namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ModuleBooted
{
    use Dispatchable;
}
```

```php
// src/Events/NavigationBuilding.php
namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NavigationBuilding
{
    use Dispatchable;

    public function __construct(
        public array $modules,
    ) {}
}
```

### 8.4 SettingsManager Decoupling

The [`SettingsManager`](../src/Services/Settings/SettingsManager.php) `getContextHash()` method currently uses HR-specific session keys:

```php
// CURRENT (coupled):
protected function getContextHash(): string
{
    $userId = auth()->id() ?? 'guest';
    $module = request()->route('module') ?? session('active_module') ?? 'system';
    $companyId = \Illuminate\Support\Facades\Session::get('current_company_id', '0');
    return md5($userId . '_' . $module . '_' . $companyId);
}
```

**Refactored version**:

```php
protected function getContextHash(): string
{
    $userId = auth()->id() ?? 'guest';
    $module = request()->route('module') 
        ?? session('active_module') 
        ?? config('ui-library.settings.default_module', 'system');
    
    // Allow consuming app to define custom context keys
    $contextKeys = config('ui-library.settings.context_keys', ['user_id', 'module']);
    $parts = [];
    
    foreach ($contextKeys as $key) {
        $parts[] = match ($key) {
            'user_id' => $userId,
            'module' => $module,
            default => session($key, request()->get($key, '0')),
        };
    }
    
    return md5(implode('_', $parts));
}
```

---

## 9. Migration & Data Portability

### 9.1 Strategy for Existing HR App Migration

The migration from the current coupled state to the standalone package happens in two phases:

#### Phase 1: Library Preparation (in `ui-library` repo)

1. Create `src/Core/Admin/` and `src/Core/System/` directory structures
2. Copy Admin and System module files from HR app, updating namespaces
3. Remove all HR-specific references from library code
4. Create new contracts, events, and config
5. Refactor service providers
6. Tag as `v2.0.0` and publish to Composer

#### Phase 2: HR App Adaptation (in `quick-hr` repo)

1. Update `composer.json` to require `quicker-faster/ui-library:^2.0`
2. Remove `app/Modules/Admin/` and `app/Modules/System/` directories
3. Update namespace references in HR code:
   - `App\Modules\Admin\Models\User` → `QuickerFaster\UILibrary\Core\Admin\Models\User`
   - `App\Modules\Admin\Models\Role` → `QuickerFaster\UILibrary\Core\Admin\Models\Role`
   - `App\Modules\System\Models\System` → `QuickerFaster\UILibrary\Core\System\Models\System`
4. Move HR-specific Livewire components from library `Custom/` to `app/Modules/Hr/Http/Livewire/`
5. Update `config/app.php` providers if needed (auto-discovery should handle this)
6. Publish and customize `config/ui-library.php`
7. Register HR-specific settings resolvers in `AppServiceProvider`:

```php
// In HR app's AppServiceProvider::boot()
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;

$settingsManager = app(SettingsManager::class);

$settingsManager->addResolver('company', function ($key) {
    $companyId = session('current_company_id') ?? auth()->user()?->company_id;
    if ($companyId) {
        $company = \App\Modules\Hr\Models\Company::find($companyId);
        return $company?->getSetting($key);
    }
    return null;
});
```

### 9.2 Data Preservation

- Core module migrations are registered via `loadMigrationsFrom()` pointing to `src/Core/{Module}/Database/Migrations/`
- Existing database tables remain intact — migrations use timestamps to ensure they only run once
- The `quicker-faster-ui:install` command only runs migrations; it does not reset the database

### 9.3 Backward Compatibility

During the transition period, provide an alias for the Admin User model:

```php
// In HR app's config/app.php 'aliases'
'AdminUser' => \QuickerFaster\UILibrary\Core\Admin\Models\User::class,
```

Or use Laravel's morph map:

```php
// In AppServiceProvider::boot()
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'admin_user' => \QuickerFaster\UILibrary\Core\Admin\Models\User::class,
]);
```

### 9.4 Migration Checklist for HR App

- [ ] Update `composer.json`: change `quicker-faster/ui-library` constraint to `^2.0`
- [ ] Run `composer update quicker-faster/ui-library`
- [ ] Delete `app/Modules/Admin/` directory
- [ ] Delete `app/Modules/System/` directory
- [ ] Delete `app/Modules/app_setup.php`, `app_tour.php`, `app_onboarding.php`, `app_general_settings.php`
- [ ] Move `EmployeeDetail.php`, `SearchableEmployeeDropdown.php`, `TaxBandsRepeater.php` to `app/Modules/Hr/Http/Livewire/`
- [ ] Move corresponding Blade views to `app/Modules/Hr/Resources/views/livewire/`
- [ ] Update all `use App\Modules\Admin\*` to `use QuickerFaster\UILibrary\Core\Admin\*`
- [ ] Update all `use App\Modules\System\*` to `use QuickerFaster\UILibrary\Core\System\*`
- [ ] Register HR-specific settings resolvers in `AppServiceProvider`
- [ ] Register HR-specific Livewire components in `AppServiceProvider`
- [ ] Publish config: `php artisan vendor:publish --tag=ui-library-config`
- [ ] Customize `config/ui-library.php` with HR module entry
- [ ] Run `php artisan config:clear && php artisan view:clear && php artisan route:clear`
- [ ] Test all HR functionality

---

## 10. Testing & Independence Verification

### 10.1 Validation Procedure

#### Step 1: Fresh Laravel Install Test

```bash
# Create a fresh Laravel project
laravel new test-ui-library
cd test-ui-library

# Configure database in .env
# DB_CONNECTION=mysql
# DB_DATABASE=test_ui_library

# Require the package (from local path during development)
composer require quicker-faster/ui-library:^2.0

# Publish and install
php artisan vendor:publish --tag=ui-library-config
php artisan vendor:publish --tag=ui-library-assets
php artisan quicker-faster-ui:install
```

**Verification checklist**:
- [ ] Login page renders at `/login`
- [ ] Register page renders at `/register`
- [ ] Password reset flow works
- [ ] Dashboard renders after login
- [ ] Admin module accessible (user CRUD)
- [ ] System settings panel functional
- [ ] Top nav shows Admin and System modules
- [ ] Sidebar renders context-aware navigation
- [ ] Mobile bottom bar works
- [ ] Module switcher allows switching between Admin and System

#### Step 2: Add Business Module

```bash
# Create HR business module structure
mkdir -p app/Modules/Hr/Resources/views
mkdir -p app/Modules/Hr/Routes
mkdir -p app/Modules/Hr/Config

# Create a test dashboard view
cat > app/Modules/Hr/Resources/views/dashboard.blade.php << 'BLADE'
<x-layout configKey="hr_dashboard">
    <h1>HR Dashboard</h1>
    <p>This is a business module dashboard.</p>
</x-layout>
BLADE

# Create navigation config
cat > app/Modules/Hr/Config/navigation.php << 'PHP'
<?php
return [
    'context_groups' => [
        'hr' => [
            'label' => 'HR Management',
            'icon' => 'fa-users',
            'route' => 'hr.dashboard',
            'order' => 100,
        ],
    ],
    'contexts' => [
        'hr' => [
            ['label' => 'Dashboard', 'route' => 'hr.dashboard', 'icon' => 'fa-home', 'order' => 1],
        ],
    ],
    'shared_items' => ['header' => [], 'footer' => []],
    'shared_top_items' => ['left' => [], 'right' => []],
    'layout' => [
        'top_bar' => ['enabled' => true],
        'context_menu' => ['type' => 'sidebar', 'position' => 'left'],
        'sidebar' => ['initial_state' => 'full'],
        'bottom_bar' => ['enabled' => true],
    ],
];
PHP

# Create route
cat > app/Modules/Hr/Routes/web.php << 'PHP'
<?php
use Illuminate\Support\Facades\Route;

Route::get('/hr/dashboard', function () {
    return view('hr::dashboard');
})->name('hr.dashboard');
PHP
```

**Verification**:
- [ ] Navigating to `/hr/dashboard` renders the HR dashboard
- [ ] Top nav shows HR module alongside Admin and System
- [ ] Sidebar shows HR context with Dashboard link
- [ ] Module switcher includes HR module

#### Step 3: Add Business Module DataTable Config

```bash
mkdir -p app/Modules/Hr/Data

cat > app/Modules/Hr/Data/employee.php << 'PHP'
<?php
return [
    'model' => \App\Modules\Hr\Models\Employee::class,
    'title' => 'Employees',
    'fields' => [
        'first_name' => ['label' => 'First Name', 'field_type' => 'text', 'sortable' => true],
        'last_name' => ['label' => 'Last Name', 'field_type' => 'text', 'sortable' => true],
        'email' => ['label' => 'Email', 'field_type' => 'text', 'sortable' => true],
    ],
    'default_sort' => ['field' => 'first_name', 'direction' => 'asc'],
];
PHP
```

**Verification**:
- [ ] DataTable component renders with employee config
- [ ] Sorting, filtering, pagination work

### 10.2 Automated Test Suite (Future)

```php
// tests/Feature/InstallCommandTest.php
class InstallCommandTest extends TestCase
{
    /** @test */
    public function it_publishes_config()
    {
        $this->artisan('vendor:publish --tag=ui-library-config')
             ->assertExitCode(0);
        
        $this->assertFileExists(config_path('ui-library.php'));
    }

    /** @test */
    public function it_creates_super_admin()
    {
        $this->artisan('quicker-faster-ui:install')
             ->expectsQuestion('Super admin email', 'admin@test.com')
             ->expectsQuestion('Super admin password', 'password')
             ->assertExitCode(0);
        
        $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);
    }
}
```

---

## 11. Phased Implementation Roadmap

### Phase 1: Library Foundation (Weeks 1-2)

**Goal**: Create the decoupled package structure without breaking existing functionality.

| # | Task | Files | Dependencies |
|---|---|---|---|
| 1.1 | Create `src/Core/` directory structure | New dirs | None |
| 1.2 | Create new contracts | [`src/Contracts/Modules/ModuleContract.php`](../src/Contracts/Modules/ModuleContract.php), [`src/Contracts/Navigation/NavigationProvider.php`](../src/Contracts/Navigation/NavigationProvider.php), [`src/Contracts/Settings/SettingsProvider.php`](../src/Contracts/Settings/SettingsProvider.php) | None |
| 1.3 | Create new events | [`src/Events/ModuleRegistered.php`](../src/Events/ModuleRegistered.php), [`src/Events/ModuleBooted.php`](../src/Events/ModuleBooted.php), [`src/Events/NavigationBuilding.php`](../src/Events/NavigationBuilding.php) | None |
| 1.4 | Create new config | [`src/Config/ui-library.php`](../src/Config/ui-library.php) | None |
| 1.5 | Create ModuleSwitcher component | [`src/Http/Livewire/Layouts/Navs/ModuleSwitcher.php`](../src/Http/Livewire/Layouts/Navs/ModuleSwitcher.php) + view | None |
| 1.6 | Rewrite ModuleSelector (generic) | [`src/Http/Livewire/AccessControls/ModuleSelector.php`](../src/Http/Livewire/AccessControls/ModuleSelector.php) | None |
| 1.7 | Update `composer.json` | [`composer.json`](composer.json) | None |

### Phase 2: Core Module Extraction (Weeks 2-3)

**Goal**: Move Admin and System modules from HR app into the library.

| # | Task | Files | Dependencies |
|---|---|---|---|
| 2.1 | Copy Admin module from HR app | `app/Modules/Admin/` → `src/Core/Admin/` | Phase 1 |
| 2.2 | Update Admin namespaces | All files in `src/Core/Admin/` | 2.1 |
| 2.3 | Copy System module from HR app | `app/Modules/System/` → `src/Core/System/` | Phase 1 |
| 2.4 | Update System namespaces | All files in `src/Core/System/` | 2.3 |
| 2.5 | Copy Common configs | `app/Modules/app_*.php` → `src/Core/Common/Config/` | Phase 1 |
| 2.6 | Create Core seeders | `src/Core/Admin/Database/Seeders/` | 2.1 |

### Phase 3: Decoupling (Weeks 3-4)

**Goal**: Remove all HR-specific references from library code.

| # | Task | Files | Dependencies |
|---|---|---|---|
| 3.1 | Refactor UILibraryServiceProvider | [`src/Providers/UILibraryServiceProvider.php`](../src/Providers/UILibraryServiceProvider.php) | Phase 1, 2 |
| 3.2 | Refactor ModuleServiceProvider | [`src/Providers/ModuleServiceProvider.php`](../src/Providers/ModuleServiceProvider.php) | Phase 1, 2 |
| 3.3 | Clean library routes | [`src/Routes/web.php`](../src/Routes/web.php) | Phase 1 |
| 3.4 | Decouple NavigationLayout | [`src/Components/NavigationLayout.php`](../src/Components/NavigationLayout.php) | Phase 1 |
| 3.5 | Decouple SettingsManager | [`src/Services/Settings/SettingsManager.php`](../src/Services/Settings/SettingsManager.php) | Phase 1 |
| 3.6 | Move HR Custom components out | `src/Http/Livewire/Custom/` → HR app | Phase 1 |
| 3.7 | Delete `dependencies/` folder | `dependencies/` | Phase 1 |
| 3.8 | Rewrite install command | [`src/Commands/QuickerFasterInstallUI.php`](../src/Commands/QuickerFasterInstallUI.php) | Phase 2 |

### Phase 4: HR App Adaptation (Weeks 4-5)

**Goal**: Update the HR app to consume the decoupled package.

| # | Task | Files | Dependencies |
|---|---|---|---|
| 4.1 | Update composer.json | HR app `composer.json` | Phase 3 |
| 4.2 | Remove Admin/System from app | Delete `app/Modules/Admin/`, `app/Modules/System/` | 4.1 |
| 4.3 | Update namespace references | All HR files referencing Admin/System | 4.2 |
| 4.4 | Move HR Livewire components | Copy from library `Custom/` to `app/Modules/Hr/` | Phase 3 |
| 4.5 | Register HR settings resolvers | HR `AppServiceProvider` | 4.1 |
| 4.6 | Register HR Livewire components | HR `AppServiceProvider` | 4.4 |
| 4.7 | Publish and customize config | `config/ui-library.php` | 4.1 |

### Phase 5: Validation & Polish (Weeks 5-6)

**Goal**: Verify the package works standalone and with the HR app.

| # | Task | Files | Dependencies |
|---|---|---|---|
| 5.1 | Fresh Laravel install test | New Laravel project | Phase 3 |
| 5.2 | Business module addition test | Test HR-like module | 5.1 |
| 5.3 | HR app integration test | Existing HR app | Phase 4 |
| 5.4 | Documentation updates | `README.md`, `docs/` | Phase 3 |
| 5.5 | Tag and release v2.0.0 | Git tag | 5.1-5.3 |

---

## 12. Risk Assessment & Mitigation

### 12.1 Risk Matrix

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **Namespace changes break HR app** | High | High | Provide backward-compatible aliases; run comprehensive test suite before HR app migration |
| **Core module views not rendering** | Medium | High | Verify `loadViewsFrom` paths; test view resolution order (published → package) |
| **Catch-all route conflicts** | Medium | High | Load System catch-all route LAST; ensure explicit business module routes take precedence |
| **Settings resolution breaks** | Medium | Medium | Allow consuming app to register custom resolvers; provide default no-op resolvers |
| **Livewire component name collisions** | Low | Medium | Document naming convention (`qf.*` for library, module-specific prefix for business) |
| **Migration timestamp conflicts** | Low | Low | Use unique timestamps for Core migrations; document migration publishing process |
| **Composer auto-discovery fails** | Low | High | Test `extra.laravel.providers` in fresh Laravel install; provide manual registration fallback |
| **Existing HR data loss** | Low | Critical | Never drop tables in migrations; only add/modify; backup database before migration |
| **Fortify/Socialite config conflicts** | Medium | Medium | Only register if not already configured; check for existing Fortify config before overriding |

### 12.2 Rollback Plan

If the decoupled package causes critical issues in the HR app:

1. Revert `composer.json` to use the pre-2.0 version of the library
2. Restore `app/Modules/Admin/` and `app/Modules/System/` from backup
3. Restore HR-specific Custom Livewire components
4. Run `composer update`
5. Clear all caches

### 12.3 Key Dependencies to Monitor

| Dependency | Version | Risk |
|---|---|---|
| `livewire/livewire` | ^3 | Breaking changes in Livewire 4 could affect all components |
| `laravel/fortify` | ^1.0 | Fortify 2.x may change view registration API |
| `spatie/laravel-permission` | ^6.21 | Major version bumps may change Role/Permission model APIs |
| `spatie/laravel-onboard` | ^2.6 | Onboarding step API may change |

---

## 13. What NOT to Change

The following parts of the library are already well-architected and should **NOT** be modified during this decoupling:

### 13.1 Contracts (Preserved As-Is)

| Contract | File | Reason |
|---|---|---|
| `FieldType` | [`src/Contracts/FieldTypes/FieldType.php`](../src/Contracts/FieldTypes/FieldType.php) | Generic, well-defined, no HR coupling |
| `Widget` | [`src/Contracts/Widgets/Widget.php`](../src/Contracts/Widgets/Widget.php) | Generic, well-defined, no HR coupling |
| `OnboardingCondition` | [`src/Contracts/OnboardingCondition.php`](../src/Contracts/OnboardingCondition.php) | Generic, uses `$user` parameter without type-hint |

### 13.2 Traits (Preserved As-Is)

All 16+ traits in [`src/Traits/`](../src/Traits/) are generic and reusable:

- [`HasSettings`](../src/Traits/HasSettings.php)
- [`HasNavItems`](../src/Traits/HasNavItems.php)
- [`HasCurrencySymbol`](../src/Traits/HasCurrencySymbol.php)
- [`HasAutoGenerateFields`](../src/Traits/HasAutoGenerateFields.php)
- [`HasCacheInvalidator`](../src/Traits/HasCacheInvalidator.php)
- [`NavigationFilter`](../src/Traits/NavigationFilter.php)
- [`ResolvesExportValues`](../src/Traits/ResolvesExportValues.php)
- [`AppliesFilters`](../src/Traits/AppliesFilters.php)
- [`HasApproval`](../src/Traits/Approvals/HasApproval.php)
- [`HandlesToggleState`](../src/Traits/Buttons/HandlesToggleState.php)
- [`HasColumnPreferences`](../src/Traits/DataTables/HasColumnPreferences.php)
- [`HasAutoGenerate`](../src/Traits/FieldTypes/HasAutoGenerate.php)
- [`HasBladeRendering`](../src/Traits/FieldTypes/HasBladeRendering.php)
- [`HasHintField`](../src/Traits/FieldTypes/HasHintField.php)
- [`HandlesRelationshipGroupBy`](../src/Traits/Widgets/HandlesRelationshipGroupBy.php)
- [`ResolvesDateStrings`](../src/Traits/Widgets/ResolvesDateStrings.php)

### 13.3 Services (Preserved As-Is)

These services are business-agnostic and should not change:

- [`ConfigResolver`](../src/Services/Config/ConfigResolver.php)
- [`ModelConfigRepository`](../src/Services/Config/ModelConfigRepository.php)
- [`ApprovalConfigResolver`](../src/Services/Config/Approvals/ApprovalConfigResolver.php)
- [`DashboardResolver`](../src/Services/Config/Dashboards/DashboardResolver.php)
- [`WizardConfigResolver`](../src/Services/Config/Wizards/WizardConfigResolver.php)
- [`ApprovalEngine`](../src/Services/Approvals/ApprovalEngine.php)
- [`SearchEngine`](../src/Services/Search/SearchEngine.php)
- [`FilterService`](../src/Services/Filters/FilterService.php)
- [`WidgetProcessor`](../src/Services/Widgets/WidgetProcessor.php)
- [`DataTableExport`](../src/Services/Exports/DataTableExport.php)
- [`TemplateExport`](../src/Services/Exports/TemplateExport.php)
- [`ImportProcessor`](../src/Services/Imports/ImportProcessor.php)
- [`DataTableFormValidationService`](../src/Services/Validation/DataTableFormValidationService.php)
- [`AccessControlPermissionService`](../src/Services/AccessControl/AccessControlPermissionService.php)
- [`ApplicationInfo`](../src/Services/System/ApplicationInfo.php)
- [`ValueGenerator`](../src/Services/ValueGenerator.php)
- Bank file generators: [`BACSGenerator`](../src/Services/BankFiles/BACSGenerator.PHP), [`NACHAGenerator`](../src/Services/BankFiles/NACHAGenerator.php), [`NIBSSGenerator`](../src/Services/BankFiles/NIBSSGenerator.php), [`SEPAGenerator`](../src/Services/BankFiles/SEPAGenerator.PHP), [`BankFileGenerator`](../src/Services/BankFiles/BankFileGenerator.php), [`BankFileGeneratorFactory`](../src/Services/BankFiles/BankFileGeneratorFactory.php)

### 13.4 Widget Processors (Preserved As-Is)

All 19 widget processors are generic and should not change:

- `StatWidgetProcessor`, `ChartWidgetProcessor`, `ListWidgetProcessor`, `GroupedListWidgetProcessor`, `ProgressWidgetProcessor`, `MetricWidgetProcessor`, `TrendWidgetProcessor`, `OnboardingWidgetProcessor`, `ActionCardWidgetProcessor`, `ActivityLogWidgetProcessor`, `ProfileHeaderWidgetProcessor`, `TurnoverRateWidgetProcessor`, `ENPSWidgetProcessor`, `AbsenteeismRateWidgetProcessor`, `GoalCompletionRateWidgetProcessor`, `TrainingCompletionWidgetProcessor`, `HeadcountVsBudgetWidgetProcessor`, `DiversityIndexWidgetProcessor`, `OfferAcceptanceRateWidgetProcessor`

### 13.5 FieldTypes (Preserved As-Is)

All 14 field types in [`src/Components/FieldTypes/`](../src/Components/FieldTypes/) are generic:

- `TextField`, `TextareaField`, `SelectField`, `DatepickerField`, `DatetimepickerField`, `TimepickerField`, `CheckboxField`, `RadioField`, `FileField`, `ImageField`, `PasswordField`, `LivewireSearchableSelectField`, `MorphToSelectField`, `PolicyCalculationBuilderField`

### 13.6 Controllers (Preserved As-Is)

These controllers are generic and should not change:

- [`RegistrationController`](../src/Http/Controllers/RegistrationController.php)
- [`SocialiteController`](../src/Http/Controllers/SocialiteController.php)
- [`TempImageUploadController`](../src/Http/Controllers/TempImageUploadController.php)
- [`DocumentController`](../src/Http/Controllers/Documents/DocumentController.php)
- [`ExportController`](../src/Http/Controllers/Exports/ExportController.php)
- [`ImportController`](../src/Http/Controllers/Imports/ImportController.php)
- [`GenericTablePrintController`](../src/Http/Controllers/Prints/GenericTablePrintController.php)
- [`GenericDetailPagePrintController`](../src/Http/Controllers/Prints/GenericDetailPagePrintController.php)

### 13.7 Middleware (Preserved As-Is)

- [`CheckSetup`](../src/Http/Middleware/CheckSetup.php)

### 13.8 Models (Preserved As-Is)

These library models are generic infrastructure:

- `Export`, `ExportChunk`, `Import`, `ImportChunk`, `SavedFilter`, `SavedReport`

### 13.9 Jobs (Preserved As-Is)

- `ExportChunk`, `FinalizeExportZip`, `GenerateExport`, `ProcessImport`, `ProcessImportChunk`

### 13.10 Views (Preserved, Paths Updated)

All views in [`src/Resources/views/`](../src/Resources/views/) are preserved. Only paths referencing HR-specific content are updated:

- `src/Resources/views/livewire/custom/` → **MOVED** to HR app (not deleted, relocated)
- All other views remain in place

---

## Appendix A: Complete File Manifest

### A.1 New Files to Create

```
src/Core/
├── Admin/
│   ├── Config/navigation.php
│   ├── Database/Migrations/ (from HR app)
│   ├── Database/Seeders/RoleSeeder.php
│   ├── Database/Seeders/SuperAdminSeeder.php
│   ├── Http/Controllers/ (from HR app)
│   ├── Http/Livewire/ (from HR app)
│   ├── Models/ (from HR app, namespace updated)
│   ├── Resources/views/ (from HR app)
│   └── Routes/web.php (from HR app)
├── System/
│   ├── Config/navigation.php
│   ├── Database/Migrations/ (from HR app)
│   ├── Database/Seeders/SystemSettingsSeeder.php
│   ├── Http/Livewire/ (from HR app)
│   ├── Models/ (from HR app, namespace updated)
│   ├── Resources/views/ (from HR app)
│   └── Routes/web.php (from HR app, catch-all)
└── Common/
    └── Config/
        ├── app_setup.php
        ├── app_tour.php
        ├── app_onboarding.php
        └── app_general_settings.php

src/Config/
└── ui-library.php (NEW)

src/Contracts/
├── Modules/
│   └── ModuleContract.php (NEW)
├── Navigation/
│   └── NavigationProvider.php (NEW)
└── Settings/
    └── SettingsProvider.php (NEW)

src/Events/
├── ModuleRegistered.php (NEW)
├── ModuleBooted.php (NEW)
└── NavigationBuilding.php (NEW)

src/Http/Livewire/Layouts/Navs/
└── ModuleSwitcher.php (NEW)

src/Resources/views/livewire/layouts/navs/
└── module-switcher.blade.php (NEW)
```

### A.2 Files to Delete

```
dependencies/ (entire directory tree)
src/Http/Livewire/Custom/EmployeeDetail.php
src/Http/Livewire/Custom/SearchableEmployeeDropdown.php
src/Http/Livewire/Custom/TaxBandsRepeater.php
src/Resources/views/livewire/custom/employee-detail.blade.php
src/Resources/views/livewire/custom/searchable-employee-dropdown.blade.php
src/Resources/views/livewire/custom/tax-bands-repeater.blade.php
src/Config/quicker-faster-ui.php (replaced by ui-library.php)
```

### A.3 Files to Modify

```
composer.json
src/Providers/UILibraryServiceProvider.php
src/Providers/ModuleServiceProvider.php
src/Routes/web.php
src/Components/NavigationLayout.php
src/Services/Settings/SettingsManager.php
src/Commands/QuickerFasterInstallUI.php
src/Http/Livewire/AccessControls/ModuleSelector.php
src/Resources/views/livewire/access-controls/module-selector.blade.php
```

---

## Appendix B: Config Key Migration Reference

| Old Config Key | New Config Key |
|---|---|
| `quicker-faster-ui.socialite.*` | `ui-library.socialite.*` |
| `quicker-faster-ui.multitenancy.*` | **REMOVED** (app-specific) |
| `quicker-faster-ui.features.multi_company_payroll` | **REMOVED** (app-specific) |
| `quicker-faster-ui.breadcrumb.show_home` | `ui-library.breadcrumb.show_home` |
| `quicker-faster-ui.title.separator` | `ui-library.title.separator` |
| N/A | `ui-library.modules.*` (NEW) |
| N/A | `ui-library.module_paths.*` (NEW) |
| N/A | `ui-library.home_route` (NEW) |
| N/A | `ui-library.settings.resolvers.*` (NEW) |
| N/A | `ui-library.navigation.*` (NEW) |
| N/A | `ui-library.features.*` (NEW, generic) |

---

## Appendix C: Environment Variables Reference

| Variable | Purpose | Default |
|---|---|---|
| `UI_LIBRARY_SOCIALITE_ENABLED` | Master switch for social login | `false` |
| `UI_LIBRARY_SOCIALITE_GOOGLE` | Enable Google OAuth | `false` |
| `UI_LIBRARY_SOCIALITE_GITHUB` | Enable GitHub OAuth | `false` |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | — |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret | — |
| `GOOGLE_REDIRECT_URI` | Google OAuth redirect URI | — |
| `GITHUB_CLIENT_ID` | GitHub OAuth client ID | — |
| `GITHUB_CLIENT_SECRET` | GitHub OAuth client secret | — |
| `GITHUB_REDIRECT_URI` | GitHub OAuth redirect URI | — |
| `UI_LIBRARY_HOME_ROUTE` | Named route for post-login redirect | `admin.dashboard` |

---

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-07  
> **Next Review**: After Phase 1 completion