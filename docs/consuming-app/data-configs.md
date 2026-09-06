# Data Configs

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

This document covers the config schemas for DataTable, DataTableForm, DataTableDetail, Dashboards, Wizards, and Reports. For the library's internal resolution pipeline (ModelConfigRepository, ConfigResolver), see [../library/05-data-configs.md](../library/05-data-configs.md).

---

## 1. Overview

The library uses a **config-driven architecture**: a single PHP config file defines the structure for DataTable, DataTableForm, and DataTableDetail components. Dashboard, wizard, and report configs follow the same pattern.

### Config Key Convention

- **Dot-notation key**: `{lowercase_module}.{filename}`
- **Resolution**: `app/Modules/{Module}/Data/{file}.php`
- **Example**: `'billing.invoice'` → `app/Modules/Billing/Data/invoice.php`

Nested paths use dot-notation for subdirectories: `'billing.dashboards.overview'` → `app/Modules/Billing/Data/dashboards/overview.php`.

---

## 2. DataTable Config Schema

### 2.1 Top-Level Keys

```php
return [
    'model'           => 'App\\Modules\\Billing\\Models\\Invoice',  // Required: FQCN
    'title'           => 'Invoices',                                 // Optional: page title
    'icon'            => 'fa-file-invoice',                          // Optional: header icon
    'fieldDefinitions' => [ /* ... */ ],                             // Required: field definitions
    'fieldGroups'     => [ /* ... */ ],                              // Optional: form section grouping
    'controls'        => 'all',                                      // Optional: table controls
    'hiddenFields'    => [ /* ... */ ],                              // Optional: field visibility
    'relations'       => [ /* ... */ ],                              // Optional: relationships
    'switchViews'     => [ /* ... */ ],                              // Optional: alternative view modes
    'moreActions'     => [ /* ... */ ],                              // Optional: custom row actions
    'reports'         => [ /* ... */ ],                              // Optional: report definitions
    'permissions'     => [ /* ... */ ],                              // Optional: permission overrides
    'crudType'        => 'modal',                                    // Optional: 'modal' (default), 'drawers', or 'pages'
    'simpleActions'   => ['show', 'edit', 'delete'],                 // Optional: row action buttons to display
    'view'            => [ /* ... */ ],                              // Optional: view configuration (route, card/list config)
];
```

### 2.2 fieldDefinitions

Each field drives form rendering, table column display, detail view display, inline editing, and validation. Keyed by field name:

```php
'fieldDefinitions' => [
    'invoice_number' => [
        'field_type'    => 'string',            // Maps to FieldFactory: 'string'|'text'|'select'|'datepicker'|'timepicker'|'datetimepicker'|'checkbox'|'boolcheckbox'|'boolradio'|'file'|'image'|'photo'|'picture'|'textarea'|'livewire-searchable-select'|'morph_to_select'|'password'|'policy_calculation_builder'
        'label'         => 'Invoice #',          // Display label
        'required'      => true,                 // Visual required indicator
        'validation'    => 'required|string|max:50', // Laravel validation rules
        'sortable'      => true,                 // Sortable column in table
        'searchable'    => true,                 // Searchable column in table
        'visible'       => true,                 // Visible by default in table
        'default'       => null,                 // Default value for new records
        'placeholder'   => 'Enter invoice number',
        'help_text'     => 'Auto-generated if left blank',
        'autoGenerate'  => true,                 // Enable auto-generation
        'generator'     => [                     // Auto-generation config
            'pattern'   => 'INV-{YYYY}-{####}',
        ],
        // For select/relationship fields:
        'options'       => [],                   // Static options [value => label]
        'options_model' => null,                 // Model for dynamic options
        'options_method' => 'pluck',             // Method on options_model
        'relationship'  => null,                 // Relationship name on the model
        'multiple'      => false,                // Multi-select
        // For file fields:
        'fileTypes'     => ['jpg','jpeg','png','pdf','doc','docx'],
        'maxSizeMB'     => 1,                    // Max file size in MB
        'disk'          => 'public',             // Storage disk
        'path'          => 'uploads',            // Storage path
    ],
    'amount' => [
        'field_type' => 'string',
        'label'      => 'Amount',
        'validation' => 'required|numeric|min:0',
        'sortable'   => true,
    ],
    // ... more fields
],
```

### 2.3 fieldGroups

Organizes form fields into tabs/sections:

```php
'fieldGroups' => [
    [
        'key'    => 'details',
        'label'  => 'Invoice Details',
        'icon'   => 'fa-file-invoice',
        'fields' => ['invoice_number', 'client_name', 'amount', 'due_date'],
    ],
    [
        'key'    => 'status',
        'label'  => 'Status',
        'icon'   => 'fa-info-circle',
        'fields' => ['status', 'notes'],
    ],
],
```

### 2.4 controls

Table-level controls. Use `'all'` to enable everything, or configure individually:

```php
'controls' => [
    'files' => [
        'export' => ['xls', 'csv', 'pdf'],   // Enabled export formats
        'import' => ['xls', 'csv'],           // Enabled import formats
        'print'  => true,                     // Print button
    ],
    'bulkActions' => [
        'export' => ['xls', 'csv', 'pdf'],    // Bulk export formats
        'delete' => true,                     // Bulk delete
    ],
    'perPage'          => [10, 25, 50, 100],  // Pagination options
    'search'           => true,               // Global search bar
    'showHideColumns'  => true,               // Column visibility toggling
    'filterColumns'    => true,               // Per-column filtering
    'addButton'        => true,               // "Add New" button
    'editable'         => true,               // Inline editing enabled
],
```

### 2.5 Relationships

```php
'relations' => [
    'client' => [
        'model'       => 'App\\Modules\\Crm\\Models\\Client',
        'foreign_key' => 'client_id',
        'display'     => 'name',
    ],
],
```

### 2.6 hiddenFields

Controls field visibility per context:

```php
'hiddenFields' => [
    'onTable'    => [],    // Fields hidden from table columns
    'onNewForm'  => [],    // Fields hidden on create form
    'onEditForm' => [],    // Fields hidden on edit form
    'onQuery'    => [],    // Fields excluded from queries
    'onDetail'   => [],    // Fields hidden from detail view
],
```

### 2.7 crudType

Controls how create, read, update, and delete operations are rendered. Three modes are supported:

```php
'crudType' => 'modal',   // Default: opens forms/details in Bootstrap modals
'crudType' => 'drawers', // Opens forms/details in slide-over drawers
'crudType' => 'pages',   // Navigates to dedicated pages (e.g., /resource/create, /resource/{id}/edit)
```

| crudType | Show (Detail) | Edit | Create | Row Click (List/Card View) |
|----------|--------------|------|--------|---------------------------|
| `modal` (default) | `wire:click="show()"` → Bootstrap modal | `wire:click="edit()"` → modal | `wire:click="create()"` → modal | `wire:click="show()"` |
| `drawers` | `openDrawer` event → slide-over drawer | `openDrawer` event → drawer | `openDrawer` event → drawer | `openDrawer` event → drawer |
| `pages` | `<a href>` → dedicated show page | `<a href>` → dedicated edit page | `<a href>` → dedicated create page | `window.location` → show page |

The `crudType` can be overridden per-instance via the `:crud-type` mount prop on `qf.data-table`:

```blade
@livewire('qf.data-table', ['configKey' => 'hr.employee', 'crudType' => 'drawers'])
```

### 2.8 simpleActions

Controls which action buttons appear on each table row. Defaults to `['show', 'edit', 'delete']`:

```php
'simpleActions' => ['show', 'edit', 'delete'],  // All three actions
'simpleActions' => ['show'],                     // View-only (no edit/delete)
'simpleActions' => ['show', 'edit'],             // No delete
'simpleActions' => [],                           // No row actions at all
```

Additional actions supported: `'restore'`, `'forceDelete'` (for soft-deleted records), and `'expand'` (for inline expandable detail panels).

Can be overridden per-instance via `:simple-actions` mount prop:

```blade
@livewire('qf.data-table', ['configKey' => 'hr.employee', 'simpleActions' => ['show']])
```

### 2.9 view (Route Configuration)

When `crudType` is `'pages'`, the `view` key configures the route prefix used for create/edit/show page navigation:

```php
'view' => [
    'route' => 'employees',  // Route name prefix (e.g., employees.show, employees.edit, employees.create)
],
```

This generates URLs like:
- Show: `route('employees.show', ['id' => $record->id])`
- Edit: `route('employees.edit', ['id' => $record->id])`
- Create: `route('employees.create')`

### 2.10 switchViews

Alternative view modes for the data table. Users can switch between views from a dropdown in the table header:

```php
'switchViews' => [
    'table'   => true,       // Default table view (always available)
    'list'    => [           // List view (compact rows)
        'titleFields'    => ['name', 'email'],
        'subtitleFields' => ['department', 'position'],
        'badgeField'     => 'status',
        'badgeColors'    => ['active' => 'success', 'inactive' => 'secondary'],
    ],
    'cards'   => [           // Card/grid view
        'titleFields'    => ['name'],
        'subtitleFields' => ['department', 'position'],
        'contentFields'  => ['email', 'phone'],
        'badgeField'     => 'status',
        'badgeColors'    => ['active' => 'success', 'inactive' => 'secondary'],
        'iconField'      => 'avatar',
        'defaultIconClass' => 'fas fa-user',
    ],
    'monthly' => [           // Monthly timeline view (for date-based records like leave)
        'dateField'      => 'start_date',
        'endDateField'   => 'end_date',
        'titleFields'    => ['employee.name', 'leaveType.name'],
        'subtitleFields' => ['start_date', 'end_date'],
        'badgeField'     => 'status',
        'badgeColors'    => ['Approved' => 'success', 'Pending' => 'warning', 'Draft' => 'secondary', 'Denied' => 'danger', 'Cancelled' => 'dark'],
    ],
],
```

Each view mode renders via its own Blade partial:
- [`table`](src/Resources/views/livewire/data-tables/data-table.blade.php:1) — standard paginated table
- [`list`](src/Resources/views/livewire/data-tables/partials/list-view.blade.php:1) — compact list rows with avatar, title, subtitle, badge
- [`cards`](src/Resources/views/livewire/data-tables/partials/card-view.blade.php:1) — card grid with image/icon header, body, footer actions
- [`monthly`](src/Resources/views/livewire/data-tables/partials/monthly-view.blade.php:1) — groups records by month with timeline cards

---

## 3. DataTableForm Config Schema

The DataTableForm uses the same config file as DataTable. Form-specific behavior is controlled by:

### 3.1 Form-Specific Keys

- **`fieldGroups`** — controls form section/tab layout
- **`hiddenFields.onNewForm`** — fields hidden on create
- **`hiddenFields.onEditForm`** — fields hidden on edit
- **`controls.addButton`** — shows/hides the "Add New" button
- **`controls.editable`** — enables/disables inline editing

### 3.2 Validation Rules

Validation is generated dynamically from `fieldDefinitions`:

1. Each field's `validation` string is used as the base
2. Field-type-specific rules are added by the `FieldType` implementation
3. `unique` rules are adjusted for edit mode (appends record ID)
4. Hidden fields (per `hiddenFields.onNewForm`/`onEditForm`) are skipped
5. File fields are always validated if present in the request

### 3.3 File Upload Fields

```php
'contract_file' => [
    'field_type' => 'file',
    'label'      => 'Contract',
    'fileTypes'  => ['pdf', 'doc', 'docx'],
    'maxSizeMB'  => 5,
    'disk'       => 'public',
    'path'       => 'contracts',
    'validation' => 'required|file|max:5120',
],
```

---

## 4. DataTableDetail Config Schema

The DataTableDetail uses the same config file. Detail-specific configuration:

- **`hiddenFields.onDetail`** — fields hidden from the detail view
- **`relations`** — related data is displayed in the detail view
- Field definitions control how each field renders in detail mode

---

## 5. Dashboard Config Schema

Dashboard configs live at `app/Modules/{Module}/Data/Dashboards/{Name}.php` and drive the `qf.dashboard` Livewire component:

```php
return [
    'title'       => 'Billing Overview',
    'description' => 'Revenue and invoice metrics across all clients.',

    // Optional hero banner
    'hero' => [
        'title'       => 'Welcome back',
        'description' => 'Here is today\'s billing summary.',
        'icon'        => 'fas fa-chart-pie',
    ],

    // Optional gradient stat row
    'stats' => [
        [
            'label' => 'Total Revenue',
            'value' => 128400,
            'icon'  => 'fas fa-dollar-sign',
            'color' => 'success',
        ],
        [
            'label' => 'Open Invoices',
            'value' => 32,
            'icon'  => 'fas fa-file-invoice',
            'color' => 'warning',
        ],
    ],

    // Widget grid
    'layout'  => ['columns' => 12, 'gutter' => 3],
    'widgets' => [
        [
            'type'  => 'stat',
            'title' => 'Revenue This Month',
            'color' => 'primary',
            'model' => 'App\\Modules\\Billing\\Models\\Invoice',
            'query' => fn($q) => $q->whereMonth('created_at', now()->month),
            'aggregate' => ['function' => 'sum', 'column' => 'amount'],
        ],
        [
            'type'  => 'chart',
            'title' => 'Revenue Trend',
            'color' => 'info',
            'chart_type' => 'line',
            // ... chart-specific config
        ],
    ],
];
```

### Available Widget Types

| Type | Processor | Purpose |
|------|-----------|---------|
| `stat` | `StatWidgetProcessor` | Single stat card (count, sum, avg) |
| `chart` | `ChartWidgetProcessor` | Chart (bar, line, pie, doughnut) |
| `list` | `ListWidgetProcessor` | Simple list |
| `metric` | `MetricWidgetProcessor` | Single metric display |
| `trend` | `TrendWidgetProcessor` | Trend indicator (up/down) |
| `progress` | `ProgressWidgetProcessor` | Progress bar |
| `action_card` | `ActionCardWidgetProcessor` | Action card with CTA |
| `activity_log` | `ActivityLogWidgetProcessor` | Activity log feed |

---

## 6. Wizard Config Schema

Wizard configs drive the `qf.wizard` component:

```php
// app/Modules/Billing/Data/wizards/invoice_creation.php
return [
    'title'       => 'Create Invoice',
    'description' => 'Step-by-step invoice creation wizard.',
    'steps' => [
        [
            'key'         => 'client',
            'label'       => 'Select Client',
            'description' => 'Choose the client for this invoice.',
            'fields'      => ['client_id'],
        ],
        [
            'key'         => 'items',
            'label'       => 'Line Items',
            'description' => 'Add invoice line items.',
            'fields'      => ['items'],
        ],
        [
            'key'         => 'review',
            'label'       => 'Review & Submit',
            'description' => 'Review and submit the invoice.',
            'fields'      => [],
        ],
    ],
];
```

### Step Validation

Each step can define validation rules that are applied before advancing to the next step.

---

## 7. Report Config Schema

Reports are registered via the `#[ReportType]` attribute or in `config('ui-library.reports.report_types')`:

```php
'report_types' => [
    'invoice_summary' => \App\Modules\Billing\Reports\InvoiceSummaryReport::class,
    'revenue_report'  => \App\Modules\Billing\Reports\RevenueReport::class,
],
```

Report schedules are managed via the `ReportSchedule` model:

```php
ReportSchedule::create([
    'name'        => 'Monthly Invoice Summary',
    'report_type' => 'invoice_summary',
    'frequency'   => 'monthly',   // daily, weekly, monthly, quarterly, yearly
    'time'        => '06:00',
    'timezone'    => 'UTC',
    'recipients'  => [1, 2, 3],
    'parameters'  => ['client_id' => 5],
    'status'      => 'active',
]);
```

---

## DataTable Query-String Bridges & Runtime Props

DataTable accepts query-string parameters and mount props that bridge external links (dashboard "View all" links, cross-module deep links) directly into the table's filter, visibility, and title state — no PHP wrapper required.

### Query-String Filters (`?filter[]`)

The `?filter[]` bridge applies filters to a DataTable on initial mount. Both syntaxes are supported:

- **Simple (backward compatible)** — `?filter[field]=value` implies the `=` operator:

  ```
  /employees?filter[status]=active
  ```

- **Nested operator** — `?filter[field][operator]=value`:

  ```
  /employees?filter[age][>]=30
  ```

**Supported operators**: `=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `not like`, `between`.

**`between`** takes two values via an array:

```
/employees?filter[created_at][between][]=2026-01-01&filter[created_at][between][]=2026-01-31
```

**Relative dates** — for `datepicker`/`datetimepicker` fields, the values `today`, `+N days`, and `-N days` are resolved through `strtotime()`:

```
/attendance?filter[clock_in][>=]=-7 days
/leave?filter[from_date][>=]=today
```

### Hiding Columns via Query String (`?hiddenFields[]`)

```
?hiddenFields[onTable][]=field_name
```

Any `hiddenFields` context is accepted (`onTable`, `onForm`, etc.), so the same bridge can hide columns from the table or fields from a form. Example:

```
/employees?hiddenFields[onTable][]=employee_id
```

### Dynamic Page Title (`:page-title`)

Pass `:page-title` to `qf.data-table` to set the document title and page heading dynamically:

```blade
<livewire:qf.data-table
    config-key="leave.leave_request"
    :page-title="'Leave Requests — ' . $employeeName" />
```

The DataTable blade sets `document.title` and renders the heading from this prop.

### List Widget `view_all_link_target`

The `view_all_link_target` config key in dashboard list-widget definitions controls how the "View all" link opens:

- `_self` (default) — same tab
- `_blank` — new tab; `rel="noopener noreferrer"` is added automatically

#### Per-Instance `crudType`, `simpleActions`, and `moreActions` Overrides

The `qf.data-table` component accepts optional mount params to override config values per instance:

- `:crud-type` — overrides the config's `crudType` (e.g., `'drawers'` for embedded tables)
- `:simple-actions` — overrides `simpleActions` (e.g., `['show']` to suppress Edit/Delete)
- `:more-actions` — overrides `moreActions` (e.g., custom row actions)

Pass `null` or omit to use the config default. The `render()` method resolves override first, config second. Properties are set before the blade renders.

Example:
```blade
@livewire('qf.data-table', ['configKey' => 'hr.employee', 'crudType' => 'drawers'])
```

### `between` Operator

`FilterService` now routes the `between` operator through `whereBetween()` for correct SQL generation (previously it produced invalid comparison SQL).

### Row Actions "Show" Link

The table row "Show" action now uses `getShowUrl()` — the same URL builder used by list/card views — so the pagination, filter, and sort context is carried through to the detail page.

#### Pre-filling Add Forms via `?prefill[]`

The `?prefill[field_name]=value` query parameter pre-fills the corresponding field in the "Add" form drawer. The page-header's Add button automatically merges `prefill[]` query params into the `prefilledData` passed to `DataTableForm`.

Example:
```
/employees?prefill[employee_id]=42
```
This pre-selects employee #42 in the form.

The table row "Show" action now uses `getShowUrl()` — the same URL builder used by list/card views — so the pagination, filter, and sort context is carried through to the detail page.

---

## Cross-References

- [../library/05-data-configs.md](../library/05-data-configs.md) — ModelConfigRepository & ConfigResolver internals
- [module-structure.md](module-structure.md) — Module directory structure for config files
- [contracts.md](contracts.md) — Reportable contract for scheduled reports
- [ui-primitives.md](ui-primitives.md) — DataTable, DataTableForm, DataTableDetail usage