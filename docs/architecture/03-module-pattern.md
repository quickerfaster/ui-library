# QuickerFaster UI Library — Module Conventions & Registration Protocol

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`02-directory-map.md`](./02-directory-map.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`14-integration-map.md`](./14-integration-map.md)

---

## 6. Module Conventions & Registration Protocol

### 6.1 Mandatory Module Structure

Every business module under `app/Modules/{ModuleName}/` must include at minimum:

```
app/Modules/{ModuleName}/
├── Data/                         # REQUIRED: Config files for tables/forms/details
│   └── {Entity}.php              # At least one entity config
├── Models/                       # REQUIRED: Eloquent models
├── Resources/
│   └── views/                    # REQUIRED: Blade views (auto-registered)
└── Routes/
    └── web.php                   # RECOMMENDED: Module-specific routes
```

Optional but strongly recommended:
- `Database/Migrations/` — Auto-loaded by [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php)
- `Http/Controllers/` — Module controllers
- `Http/Livewire/` — Module-specific Livewire components
- `Http/Requests/` — Form request validation classes
- `Listeners/` — Auto-discovered event listeners
- `Services/` — Business logic services
- `Config/navigation.php` — Navigation items for the module
- `Data/Dashboards/` — Dashboard widget definitions
- `Data/reports/` — Report definitions

> **Note**: Core modules (Organization, Admin, System) live under `src/Core/{Module}/` and follow the **same registration protocol** as business modules — they contribute `Data/`, `Models/`, `Resources/views/`, `Routes/web.php`, `Config/navigation.php`, and `Database/` (migrations + seeders). See [`02-directory-map.md`](./02-directory-map.md) and [`04-routing-and-views.md`](./04-routing-and-views.md).

### 6.2 ModuleServiceProvider Registration Protocol

**Location**: [`src/Providers/ModuleServiceProvider.php`](../../src/Providers/ModuleServiceProvider.php)

The provider executes the following registration sequence on `boot()`:

```
boot()
├── discoverBusinessModules()        # Scan app/Modules/*, register views/routes/migrations/listeners
├── registerModuleConfigs()          # Global + dashboard + report configs (src/Core/* and app/Modules/*)
└── registerOnboardingConfig()       # Spatie Onboard steps from app_onboarding config
```

> **Blueprint note**: The original §6.2 documented the sequence as `registerPublishables()` → `registerModuleConfig()` → `setupModules()` (with `registerModuleViewAlias()`, `registerModuleRoutes()`, `registerModuleMigrations()`, `registerModuleEvents()`) → `registerAppOnboardingCnfig()`. These steps map to the current `discoverBusinessModules()`, `registerModuleConfigs()`, and `registerOnboardingConfig()` methods.

### 6.3 View Namespace Registration

Each module's `Resources/views/` directory is registered as a view namespace using the **lowercase module name**:

```php
// Business modules — ModuleServiceProvider::discoverBusinessModules()
$alias = strtolower($moduleName);  // 'Hr' → 'hr', 'Admin' → 'admin'
$this->loadViewsFrom($viewPath, $alias);
```

Usage: `view('hr::dashboard')`, `view('admin::users.index')`

> **Core modules** register their views under a single shared **`qf-core`** namespace instead of per-module lowercase aliases (see [`04-routing-and-views.md`](./04-routing-and-views.md)):
> ```php
> // UILibraryServiceProvider::bootCoreModules()
> $this->loadViewsFrom($viewPath, 'qf-core');            // Admin (first Core module)
> $this->app['view']->addNamespace('qf-core', $viewPath); // System, Organization
> ```
> So Core views resolve as `view('qf-core::admin.dashboard')`, `view('qf-core::system.dashboard')`, `view('qf-core::organization.companies')`.

### 6.4 Route Loading Order (Summary)

The route loading order is critical. Library routes load first, followed by Core module routes and non-system business-module routes, with the System catch-all route loaded LAST so explicit routes take precedence. See [`04-routing-and-views.md`](./04-routing-and-views.md) for the full 5-step detail.

### 6.5 Event Listener Auto-Discovery

`registerModuleEvents()` scans each module's `Listeners/` directory and:

1. Finds all PHP files
2. Resolves the FQCN: `App\Modules\{ModuleName}\Listeners\{ClassName}`
3. Uses reflection on the `handle()` method to detect the event type from the first parameter's type hint
4. Registers with `Event::listen($eventClass, $listenerClass)`
5. Caches the listener map in production (`cache()->forever('module_event_listeners_{$moduleName}', $map)`)

### 6.6 Global Config Merging

`registerModuleConfigs()` merges global configs from `src/Core/` and `app/Modules/`:

| File | Config Key |
|------|-----------|
| `src/Core/Common/Config/app_setup.php` | `app_setup` |
| `src/Core/Common/Config/app_tour.php` | `app_tour` |
| `src/Core/Common/Config/app_onboarding.php` | `app_onboarding` |
| `src/Core/Common/Config/app_general_settings.php` | `app_general_settings` |

Dashboard configs from `*/Data/Dashboards/*.php` (both `src/Core/*` and `app/Modules/*`) are merged with keys like `hr_employee_overview`.

Report configs from `*/Data/reports/*.php` are merged with keys like `hr_headcount` and registered in `config('reports.registered')`.

> **Blueprint note**: The original §6.6 listed the global config files as living in `app/Modules/` (e.g., `app/Modules/app_setup.php`). The current implementation loads them from `src/Core/Common/Config/`.

### 6.7 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Module directory | PascalCase | `Hr`, `Admin`, `System` |
| View namespace | lowercase | `hr`, `admin`, `system` |
| Config file | camelCase or snake_case `.php` | `employee.php`, `payroll_run.php` |
| Config key (dot notation) | `{lowercase_module}.{filename}` | `hr.employee`, `admin.user` |
| Dashboard config key | `{lowercase_module}_{filename}` | `hr_employee_overview` |
| Report config key | `{lowercase_module}_{filename}` | `hr_headcount` |
| Livewire component alias | `qf.{kebab-case}` | `qf.data-table`, `qf.payroll-run-wizard` |
| Blade component tag | `<x-qf::{kebab-case}>` | `<x-qf::text-field>` |
| Model namespace | `App\Modules\{ModuleName}\Models` | `App\Modules\Hr\Models\Employee` |
| Listener namespace | `App\Modules\{ModuleName}\Listeners` | `App\Modules\Hr\Listeners\SendWelcomeEmail` |

### 6.8 Catch-All Route Pattern (Summary)

The System module ([`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php)) contains the catch-all route `/{module}/{view}/{id?}`, which resolves views through a namespace fallback chain. It is loaded LAST so explicit module routes take precedence. See [`04-routing-and-views.md`](./04-routing-and-views.md) for the full detail.

---

**Related files**: [`00-index.md`](./00-index.md) · [`02-directory-map.md`](./02-directory-map.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`14-integration-map.md`](./14-integration-map.md)
