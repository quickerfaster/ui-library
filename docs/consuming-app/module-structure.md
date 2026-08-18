# Module Structure & Conventions

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

This document covers the complete anatomy of a business module under `app/Modules/{Module}/`, naming conventions, auto-discovery, navigation config, and related conventions. For the library-internal registration protocol, see [../library/03-module-pattern.md](../library/03-module-pattern.md).

---

## 1. Business Module Directory Anatomy

Every business module under `app/Modules/{ModuleName}/` follows this structure:

```
app/Modules/{ModuleName}/
├── Config/                               # Module-specific configuration
│   ├── navigation.php                    # Navigation items for this module
│   ├── workflows.php                     # Workflow definitions (deep-merged)
│   └── permissions.php                   # Custom permission overrides
├── Data/                                 # Config-driven data definitions
│   ├── {Entity}.php                      # Shared config for table + form + detail
│   ├── notifications.php                 # Notification template definitions
│   ├── Dashboards/                       # Dashboard widget definitions
│   │   └── {DashboardName}.php
│   └── reports/                          # Report definitions
│       └── {ReportName}.php
├── Database/
│   ├── Migrations/                       # Module-specific migrations (auto-loaded)
│   └── Seeders/                          # Module-specific seeders
├── Http/
│   ├── Controllers/                      # Module controllers
│   ├── Livewire/                         # Module-specific Livewire components
│   └── Requests/                         # Form request validation
├── Listeners/                            # Event listeners (auto-discovered via reflection)
├── Models/                               # Eloquent models
├── Reports/                              # Reportable implementations
├── Resources/
│   └── views/                            # Blade views (auto-registered as lowercase module alias)
├── Routes/
│   ├── web.php                           # Web routes (auto-loaded)
│   └── api.php                           # API routes (auto-loaded with 'api' prefix)
├── Services/                             # Business logic services
└── Traits/                               # Module-specific traits
```

### 1.1 Required Directories

At minimum, every module must include:

| Directory | Purpose |
|-----------|---------|
| `Data/` | At least one entity config file (e.g., `invoice.php`) |
| `Models/` | Eloquent models for the module's entities |
| `Resources/views/` | Blade views (auto-registered as a view namespace) |
| `Routes/web.php` | Module routes (optional if using the catch-all route) |

### 1.2 Optional Directories

| Directory | Purpose | When to use |
|-----------|---------|-------------|
| `Config/` | `navigation.php`, `workflows.php`, `permissions.php` | When the module contributes to navigation, workflows, or permissions |
| `Database/Migrations/` | Module-specific migrations | When the module owns its own tables |
| `Database/Seeders/` | Module-specific seeders | When the module needs seed data |
| `Http/Controllers/` | Custom controllers | When the module needs custom route handlers |
| `Http/Livewire/` | Custom Livewire components | When the module needs custom UI components |
| `Http/Requests/` | Form request classes | When the module needs custom validation |
| `Listeners/` | Event listeners | When the module reacts to library or application events |
| `Reports/` | Reportable implementations | When the module provides scheduled reports |
| `Services/` | Business logic services | When the module has complex business logic |
| `Traits/` | Module-specific traits | When the module has reusable concerns |

### 1.3 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Module directory | PascalCase | `Billing`, `Inventory`, `Admin` |
| View namespace | lowercase | `billing`, `inventory`, `admin` |
| Config file | camelCase or snake_case `.php` | `invoice.php`, `purchase_order.php` |
| Config key (dot notation) | `{lowercase_module}.{filename}` | `billing.invoice`, `inventory.purchase_order` |
| Dashboard config key | `{lowercase_module}_{filename}` | `billing_revenue_overview` |
| Report config key | `{lowercase_module}_{filename}` | `billing_monthly_summary` |
| Livewire component alias | `qf.{kebab-case}` | `qf.data-table`, `qf.invoice-form` |
| Blade component tag | `<x-qf::{kebab-case}>` | `<x-qf::text-field>` |
| Model namespace | `App\Modules\{ModuleName}\Models` | `App\Modules\Billing\Models\Invoice` |
| Listener namespace | `App\Modules\{ModuleName}\Listeners` | `App\Modules\Billing\Listeners\InvoiceSavedListener` |

---

## 2. Module Registration

### 2.1 ModuleServiceProvider Protocol

The library's [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php) auto-discovers and registers all modules under `app/Modules/`. No manual service provider registration is needed.

The provider executes this sequence on `boot()`:

1. **`discoverBusinessModules()`** — scans `app/Modules/*`, registers view namespaces (lowercase module name), loads routes, migrations, and auto-discovers event listeners
2. **`registerModuleConfigs()`** — merges global configs, dashboard configs, and report configs from both `src/Core/*` and `app/Modules/*`
3. **`registerOnboardingConfig()`** — registers onboarding steps from config

### 2.2 Module Config Entry

Each module gets an entry in `config('ui-library.modules.{Module}')`:

| Key | Type | Purpose |
|-----|------|---------|
| `user_facing` | `bool` | Whether the module appears in the application switcher |
| `depends_on` | `array` | Module dependencies (validated at boot) |
| `order` | `int` | Sort order in navigation and switcher |
| `roles` | `array` | Role-based access (`['*']` for all roles) |
| `enabled` | `bool` | Whether the module is active |
| `auto_register_listeners` | `bool` | Auto-discover listeners (default: `true`) |
| `auto_register_reports` | `bool` | Auto-discover reports (default: `true`) |
| `auto_register_workflows` | `bool` | Auto-discover workflows (default: `true`) |
| `auto_register_permissions` | `bool` | Auto-generate CRUD permissions (default: `true`) |
| `auto_register_notifications` | `bool` | Auto-discover notification templates (default: `true`) |

---

## 3. Auto-Discovery Conventions & Opt-Outs

### 3.1 Discoverable Asset Types

The library scans `app/Modules/*` by convention and auto-registers five asset types. **No service-provider wiring is required** — dropping a file into the right directory is enough. For the internals, see [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md).

| Asset type | Convention | Registered into |
|------------|-----------|-----------------|
| **Event listeners** | `app/Modules/{Module}/Listeners/*.php` (classes with a `handle()` method) | Laravel event dispatcher |
| **Reports** | `app/Modules/{Module}/Reports/*.php` (classes implementing `Reportable` + `#[ReportType]` attribute) | `ui-library.reports.report_types` |
| **Workflows** | `app/Modules/{Module}/Config/workflows.php` | `ui-library.workflows.definitions` (deep-merged) |
| **Permissions** | auto-generated CRUD names from discovered models; `Config/permissions.php` overrides | permission registry |
| **Notifications** | `app/Modules/{Module}/Data/notifications.php` (templates + channels) | notification template/channel registry |

**Example — a listener that reacts to DataTable record saves**:

```php
// app/Modules/Billing/Listeners/InvoiceSavedListener.php
namespace App\Modules\Billing\Listeners;

use QuickerFaster\UILibrary\Listeners\DataTableRecordListener;
use QuickerFaster\UILibrary\Events\DataTableRecordSaved;

class InvoiceSavedListener extends DataTableRecordListener
{
    protected function handleCreated(DataTableRecordSaved $event): void
    {
        if ($event->model !== \App\Modules\Billing\Models\Invoice::class) {
            return;
        }

        // Domain-specific side effects (e.g., queue a document render)
    }
}
```

**Example — a report declared via the `#[ReportType]` attribute**:

```php
// app/Modules/Billing/Reports/OverdueInvoicesReport.php
namespace App\Modules\Billing\Reports;

use QuickerFaster\UILibrary\Attributes\ReportType;
use QuickerFaster\UILibrary\Contracts\Reports\Reportable;

#[ReportType('overdue_invoices')]
class OverdueInvoicesReport implements Reportable
{
    public function getReportType(): string { return 'overdue_invoices'; }
    // ...generate() and recipients()
}
```

### 3.2 Opt-Out Mechanisms

**Global toggles** in `config('ui-library.discovery')`:

```php
'discovery' => [
    'listeners' => true,  // Set to false to disable all listener auto-discovery
    'reports'   => true,  // Set to false to disable all report auto-discovery
    'workflows' => true,  // Set to false to disable all workflow auto-discovery
    'cache_ttl' => 86400,
],
```

**Per-module opt-outs** on the module registry entry (each defaults to `true`):

```php
'ui-library.modules.billing.auto_register_listeners'    => false,
'ui-library.modules.billing.auto_register_reports'      => false,
'ui-library.modules.billing.auto_register_workflows'    => false,
'ui-library.modules.billing.auto_register_permissions'  => false,
'ui-library.modules.billing.auto_register_notifications' => false,
```

The per-module flag overrides the global toggle for that module.

### 3.3 The `ui-library:discover` Command

Run the command to dump a debuggable summary of every registration:

```bash
php artisan ui-library:discover
```

Output includes:

- Discovered **modules**
- Discovered **listeners** and their event→listener mappings
- Discovered **reports** and their resolved type keys
- Discovered **workflows**
- Discovered **configs**
- Discovered **permissions**
- Discovered **notifications**

The command calls the same `DiscoveryRegistrar::discover()` used at boot, so it reflects exactly what the runtime registers. Cache is invalidated on deploy via content-hashed keys derived from file paths + mtimes.

---

## 4. Config/navigation.php

### 4.1 Navigation Config Schema

Navigation items are defined per module at `app/Modules/{Module}/Config/navigation.php`:

```php
// app/Modules/Billing/Config/navigation.php
return [
    // Top-level items available to the module
    'items' => [
        ['key' => 'invoices',  'label' => 'Invoices',  'route' => 'billing.invoices',  'icon' => 'fa-file-invoice'],
        ['key' => 'payments',  'label' => 'Payments',  'route' => 'billing.payments',  'icon' => 'fa-credit-card'],
        ['key' => 'subscriptions', 'label' => 'Subscriptions', 'route' => 'billing.subscriptions', 'icon' => 'fa-repeat'],
    ],

    // Context groups: each maps a context key to a list of item keys
    'contexts' => [
        'billing' => [
            'label'  => 'Billing',
            'items'  => ['invoices', 'payments', 'subscriptions'],
            'sidebar' => [
                'section_label'    => 'Billing',      // Custom header label
                'collapsible'      => true,            // Expand/collapse toggle
                'expanded_default' => true,            // Start expanded
            ],
        ],
    ],
];
```

### 4.2 Item Keys

| Key | Purpose |
|-----|---------|
| `key` | Unique item identifier (auto-slugged from `label` if omitted) |
| `label` | Display label |
| `route` / `url` | Named route or URL path |
| `icon` | Font Awesome class |
| `order` | Sort order (default `999`) |
| `permission` | Optional Spatie permission name |
| `gate` | Optional gate string (`role:`, `permission:`, `can:`) |
| `workspace` | Optional workspace constraint map |

### 4.3 Context Groups

Context groups organize related navigation items and control how they appear in the sidebar. When a user selects a top-nav tab, only that context group's items render in the sidebar.

### 4.4 Sidebar Section Configuration

Each context group can specify sidebar rendering behavior via the `sidebar` key:

| Key | Type | Purpose |
|-----|------|---------|
| `section_label` | `string\|null\|false` | Custom header label (`null` = use group label, `false` = no header) |
| `collapsible` | `bool` | Enable expand/collapse toggle on section headers |
| `expanded_default` | `bool` | Start expanded (only when `collapsible` is `true`) |

---

## 5. Config/workflows.php

### 5.1 Workflow Definition Schema

Business modules declare workflow definitions in `Config/workflows.php`, which the library deep-merges into `ui-library.workflows.definitions`:

```php
// app/Modules/Billing/Config/workflows.php
return [
    'invoice_approval' => [
        'label' => 'Invoice Approval',
        'steps' => [
            [
                'name'          => 'Manager Approval',
                'step_type'     => 'approval',
                'approval_mode' => 'any',        // 'any' or 'all'
                'roles'         => ['manager'],
            ],
            [
                'name'          => 'Finance Review',
                'step_type'     => 'approval',
                'approval_mode' => 'all',
                'roles'         => ['finance', 'super_admin'],
            ],
        ],
    ],
];
```

### 5.2 Deep-Merge Behavior

Two modules can both contribute top-level workflow keys without one overwriting the other. Resolution is **DB-first**: the `WorkflowEngine` checks the `workflow_definitions` table before falling back to the merged config definitions.

---

## 6. Config/permissions.php

### 6.1 Permission Auto-Generation

The library auto-generates CRUD permission names from discovered models: `view_{entity}`, `create_{entity}`, `edit_{entity}`, `delete_{entity}`.

### 6.2 Custom Permissions

A module can override or extend auto-generated names via `Config/permissions.php`:

```php
// app/Modules/Billing/Config/permissions.php
return [
    'custom' => ['approve_invoice', 'void_invoice'],
];
```

---

## 7. Data/notifications.php

### 7.1 Notification Template Registration

Notification templates and their channels are declared in `Data/notifications.php`:

```php
// app/Modules/Billing/Data/notifications.php
return [
    'templates' => [
        'invoice_submitted' => [
            'channel' => 'mail',
            'subject' => 'Invoice {number} submitted',
            'body'    => 'Invoice {number} is awaiting approval.',
        ],
    ],
];
```

---

## Cross-References

- [../library/03-module-pattern.md](../library/03-module-pattern.md) — ModuleServiceProvider registration protocol
- [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md) — DiscoveryRegistrar internals & caching
- [../library/06-navigation-system.md](../library/06-navigation-system.md) — Navigation components & services
- [contracts.md](contracts.md) — Implementing Workflowable, Documentable, Notifiable, Reportable
- [permissions-and-notifications.md](permissions-and-notifications.md) — Permission seeding & notification dispatch

---

## Organization Model Inheritance

The library provides domain-agnostic Organization models under `QuickerFaster\UILibrary\Core\Organization\Models\`:
- `Company`
- `Department`
- `Location`
- `Branch`
- `Team`
- `BusinessUnit`
- `Division`

To add domain-specific columns or behavior, your module models can extend these library classes:

```php
use QuickerFaster\UILibrary\Core\Organization\Models\Department as BaseDepartment;

class Department extends BaseDepartment
{
    protected $table = 'departments';
    // Add HR-specific columns, relations, methods here
}
```

The library migrations (loaded via `UILibraryServiceProvider::loadMigrationsFrom()`) handle the base table creation. Your module's migrations only need to add domain-specific columns via ALTER statements.

**Important:** Published copies of library migrations should NOT be kept in the consuming app's `database/migrations/`. The package auto-loads them.