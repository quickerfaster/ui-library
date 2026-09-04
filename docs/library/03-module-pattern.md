# QuickerFaster UI Library — Module Conventions & Registration Protocol

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-17

**Related files**: [`02-directory-map.md`](./02-directory-map.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`14-integration-map.md`](./14-integration-map.md)

> **Consuming-app developers**: For business module directory anatomy, naming conventions, and the mandatory/optional structure, see [../consuming-app/module-structure.md](../consuming-app/module-structure.md). For auto-discovery conventions and opt-outs, see [../consuming-app/module-structure.md](../consuming-app/module-structure.md) §"Auto-Discovery Conventions & Opt-Outs".

---

## 6. Module Conventions & Registration Protocol

> **Note**: §6.1 (Mandatory Module Structure) and §6.7 (Naming Conventions) have been moved to the consuming-app documentation at [../consuming-app/module-structure.md](../consuming-app/module-structure.md). This file now focuses on the library-internal registration protocol.

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

### 6.8 Catch-All Route Pattern (Summary)

The System module ([`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php)) contains the catch-all route `/{module}/{view}/{id?}`, which resolves views through a namespace fallback chain. It is loaded LAST so explicit module routes take precedence. See [`04-routing-and-views.md`](./04-routing-and-views.md) for the full detail.

---

**Related files**: [`02-directory-map.md`](./02-directory-map.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`14-integration-map.md`](./14-integration-map.md)
