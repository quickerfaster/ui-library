# QuickerFaster UI Library — Data Configs (Config-Driven Architecture)

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-17

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) · [`26-module-auto-discovery.md`](./26-module-auto-discovery.md)

> **Consuming-app developers**: For the complete DataTable/Form/Detail config schema, dashboard/wizard/report config schemas, and how-to recipes, see [../consuming-app/data-configs.md](../consuming-app/data-configs.md). The workflow, permissions, and notification config conventions are also covered in [../consuming-app/module-structure.md](../consuming-app/module-structure.md) and [../consuming-app/permissions-and-notifications.md](../consuming-app/permissions-and-notifications.md).

---

## 5. Config-Driven Architecture Deep Dive

This file covers the config-driven rendering pipeline internals: how the library resolves, caches, and processes config files. A single PHP config file (e.g., `app/Modules/{Module}/Data/{Entity}.php`) drives DataTable, DataTableForm, and DataTableDetail components.

> **⚠️ §5.7 is intentionally excluded here.** The library's own configuration (`config('ui-library')`) lives in [`10-settings-and-config.md`](./10-settings-and-config.md), not in this file.

### 5.1 Config File Resolution Flow

```
Component requests config
        │
        ▼
ConfigResolver('hr.employee')
        │
        ▼
ModelConfigRepository::get('hr.employee')
        │
        ├─ Check cache: 'model_config_hr_employee'
        │   └─ Cache hit → return cached array
        │
        └─ Cache miss → loadFromFile('hr.employee')
                │
                ├─ Split key: ['hr', 'employee']
                ├─ Module: ucfirst('hr') → 'Hr'
                ├─ Path: app/Modules/Hr/Data/employee.php
                │
                ├─ File exists → require $filePath → cache → return
                └─ File missing → throw InvalidArgumentException
```

### 5.2 ModelConfigRepository

**Location**: [`src/Services/Config/ModelConfigRepository.php`](../../src/Services/Config/ModelConfigRepository.php:8)

Key behaviors:

- **Dot-notation keys**: `'hr.employee'` → `app/Modules/Hr/Data/employee.php`
- **Nested paths**: `'hr.dashboards.overview'` → `app/Modules/Hr/Data/dashboards/overview.php`
- **Forever caching**: Configs are cached indefinitely using `Cache::rememberForever()`
- **Flush support**: `forget($key)` for single key, `flush()` for all keys via index tracking
- **Singleton binding**: Registered as singleton in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php:91)

> **Dual-location resolution**: `ModelConfigRepository` resolves configs from both locations — it tries `app/Modules/` first, then falls back to `src/Core/`. Core modules can therefore use `<livewire:qf.data-table config-key="organization.company" />` directly.

> **Query-string bridges**: DataTable also accepts runtime query-string parameters (`?filter[]`, `?hiddenFields[]`) and the `:page-title` prop — see [../consuming-app/data-configs.md](../consuming-app/data-configs.md) §"DataTable Query-String Bridges & Runtime Props".

### 5.3 ConfigResolver

**Location**: [`src/Services/Config/ConfigResolver.php`](../../src/Services/Config/ConfigResolver.php:6)

Typed accessor methods:

| Method | Config Key | Default | Purpose |
|--------|-----------|---------|---------|
| `getModel()` | `model` | `''` | Fully qualified model class name |
| `getModelName()` | (derived) | — | Short class name from model FQCN |
| `getModuleName()` | (derived) | — | Module name extracted from model namespace |
| `getFieldDefinitions()` | `fieldDefinitions` | `[]` | All field definitions keyed by field name |
| `getFieldGroups()` | `fieldGroups` | `[]` | Field grouping for forms (tabs/sections) |
| `getControls()` | `controls` | (all defaults) | Table controls: files, bulkActions, perPage, search, etc. |
| `getRelations()` | `relations` | `[]` | Relationship definitions |
| `getHiddenFields()` | `hiddenFields` | `[...]` | Fields hidden on table, newForm, editForm, query, detail |
| `getSwitchViews()` | `switchViews` | `[]` | Alternative view modes (card, list, etc.) |
| `getMoreActions()` | `moreActions` | `[]` | Additional row actions beyond defaults |
| `getReports()` | `reports` | `[]` | Report definitions keyed by report key |
| `getReport($key)` | `reports.$key` | `null` | Single report definition |

Settings override method:

```php
public function getSettingsOverrideFieldDefinition(string $field): array
```

Applies date format, currency, and auto-generation pattern overrides from [`SettingsManager`](../../src/Services/Settings/SettingsManager.php).

> **Consuming-app developers**: The complete DataTable/Form/Detail config schema with every known key and usage examples is in [../consuming-app/data-configs.md](../consuming-app/data-configs.md). The schema is reproduced here as a skeletal reference for understanding how `ConfigResolver` consumes it.

### 5.5 How Config Drives Validation

The [`DataTableFormValidationService`](../../src/Services/Validation/DataTableFormValidationService.php:9) generates validation rules dynamically:

1. Iterates over `fieldDefinitions`
2. For each field, calls `FieldFactory::make($name, $definition)` to get a `FieldType` instance
3. Calls `$fieldObj->getValidationRules()` for field-type-specific rules
4. Falls back to `definition['validation']` string if no field-type rules
5. Falls back to default file validation for `field_type === 'file'`
6. Adjusts `unique` rules for edit mode (appends record ID)
7. Skips hidden fields based on `hiddenFields.onNewForm` / `hiddenFields.onEditForm`
8. Always validates file fields if present in request
9. Validates password fields only when changed on edit or always on create

### 5.6 How Config Drives Import/Export

- **Export**: [`DataTableExport`](../../src/Services/Exports/DataTableExport.php) reads `fieldDefinitions` to determine columns, applies `hiddenFields.onQuery` exclusions, and uses `controls.files.export` for format options
- **Import Template**: [`TemplateExport`](../../src/Services/Exports/TemplateExport.php) generates Excel templates with [`LookupSheet`](../../src/Services/Exports/LookupSheet.php) (relationship options), [`OptionsReferenceSheet`](../../src/Services/Exports/OptionsReferenceSheet.php) (field options), and [`TemplateDataSheet`](../../src/Services/Exports/TemplateDataSheet.php) (data entry columns)
- **Import Processing**: [`ImportProcessor`](../../src/Services/Imports/ImportProcessor.php) (singleton) processes uploaded files, maps columns, and creates/updates records

### 5.7 Dashboard Config Schema

Dashboard configs (e.g., `app/Modules/{Module}/Data/Dashboards/{Name}.php`) drive the [`qf.dashboard`](../../src/Http/Livewire/Dashboards/Dashboard.php) Livewire component via [`DashboardResolver`](../../src/Services/Config/Dashboards/DashboardResolver.php). In addition to `title`, `description`, `layout`, and `widgets`, the resolver now exposes two optional visual sections:

```php
return [
    'title'       => 'People Overview',
    'description' => 'Workforce snapshot across the organization.',

    // Optional hero banner (title/description/icon)
    'hero' => [
        'title'       => 'Welcome back',
        'description' => 'Here is today\'s workforce summary.',
        'icon'        => 'fas fa-chart-pie',
    ],

    // Optional gradient stat row
    'stats' => [
        [
            'label' => 'Active Employees',
            'value' => 1284,
            'icon'  => 'fas fa-users',
            'color' => 'primary',
        ],
        [
            'label' => 'Open Positions',
            'value' => 32,
            'icon'  => 'fas fa-briefcase',
            'color' => 'info',
        ],
    ],

    // Widget grid
    'layout'  => ['columns' => 12, 'gutter' => 3],
    'widgets' => [
        [
            'type'  => 'stat',
            'title' => 'Headcount',
            'color' => 'success',   // Optional per-widget color
            // ... widget-specific config
        ],
        [
            'type'  => 'chart',
            'title' => 'Headcount Trend',
            'color' => 'primary',
            // ... widget-specific config
        ],
    ],
];
```

**Key additions**:

- **`hero`** — optional banner rendered at the top of the dashboard; resolved via [`DashboardResolver::getHero()`](../../src/Services/Config/Dashboards/DashboardResolver.php) and passed to the dashboard view by [`Dashboard.php`](../../src/Http/Livewire/Dashboards/Dashboard.php).
- **`stats`** — optional gradient stat row; resolved via [`DashboardResolver::getStats()`](../../src/Services/Config/Dashboards/DashboardResolver.php).
- **Per-widget `color`** — an optional key threaded through all 11 widget processors in [`src/Widgets/`](../../src/Widgets/) that controls accent color (icon shape, gradients, etc.).
- **Reusable card partial** — [`widgets/partials/card.blade.php`](../../src/Resources/views/widgets/partials/card.blade.php) wraps widget output in a polished card (header icon/title/description/actions, optional hover lift).

> The `qf.dashboard` component also accepts `$customWidgets` and `$parameters` mount arguments, which can supply `hero` and `stats` inline (overriding the config file).

### 5.8 Workflow Config Convention (`Config/workflows.php`)

Business modules declare workflow definitions in a dedicated config file, which the [`DiscoveryRegistrar`](../../src/Services/Discovery/DiscoveryRegistrar.php) deep-merges into `ui-library.workflows.definitions`:

```php
// app/Modules/Billing/Config/workflows.php
return [
    'invoice_approval' => [
        'label' => 'Invoice Approval',
        'steps' => [
            ['name' => 'Manager Approval', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['manager']],
            ['name' => 'Finance Review', 'step_type' => 'approval', 'approval_mode' => 'all', 'roles' => ['finance']],
        ],
    ],
];
```

Workflow resolution remains **DB-first**: the [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) queries the `workflow_definitions` table first and falls back to the merged config definitions.

### 5.9 Permissions Config Convention (`Config/permissions.php`)

The library auto-generates CRUD permission names from discovered models (`view_{entity}`, `create_{entity}`, `edit_{entity}`, `delete_{entity}`). A module can override or extend those names via:

```php
// app/Modules/Billing/Config/permissions.php
return [
    'custom' => ['approve_invoice', 'void_invoice'],
];
```

### 5.10 Notification Templates Convention (`Data/notifications.php`)

Notification templates and their channels are declared in a module data file and registered into the notification template/channel registry:

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

> **Cross-link**: The full auto-discovery contract (listeners, reports, workflows, permissions, notifications), caching strategy, and per-module/global opt-outs are documented in [`26-module-auto-discovery.md`](./26-module-auto-discovery.md).

---

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) · [`26-module-auto-discovery.md`](./26-module-auto-discovery.md)
