# QuickerFaster UI Library — Data Configs (Config-Driven Architecture)

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)

---

## 5. Config-Driven Architecture Deep Dive

This file covers the config-driven rendering pipeline for DataTables, DataTableForms, and DataTableDetails. A single PHP config file (e.g., `app/Modules/Hr/Data/employee.php`) drives all three components.

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

> **⚠️ Important limitation**: `ModelConfigRepository` currently scans only `app/Modules/` (business modules). It does **not** resolve configs under `src/Core/` (Core modules like Admin, System, Organization). This is the single most important architectural issue to understand when touching config resolution — see [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) for the full analysis and the Core-module gap.

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

### 5.4 DataTable/Form/Detail Config Schema

A single config file (e.g., `app/Modules/Hr/Data/employee.php`) drives all three components. Below is the comprehensive schema with every known key:

```php
return [
    // ── MODEL ─────────────────────────────────────────────
    'model' => 'App\\Modules\\Hr\\Models\\Employee',  // Required: FQCN of the Eloquent model

    // ── FIELD DEFINITIONS ─────────────────────────────────
    // Keyed by field name. Each field drives form rendering, table column display,
    // detail view display, inline editing, and validation.
    'fieldDefinitions' => [
        'first_name' => [
            'field_type'    => 'string',        // Maps to FieldFactory: 'string'|'text'|'select'|'datepicker'|'timepicker'|'datetimepicker'|'checkbox'|'boolcheckbox'|'boolradio'|'file'|'image'|'photo'|'picture'|'textarea'|'livewire-searchable-select'|'morph_to_select'|'password'|'policy_calculation_builder'
            'label'         => 'First Name',     // Display label
            'required'      => true,             // Visual required indicator
            'validation'    => 'required|string|max:255',  // Laravel validation rules string
            'sortable'      => true,             // Column is sortable in table
            'searchable'    => true,             // Column is searchable in table
            'visible'       => true,             // Column visible by default in table
            'default'       => null,             // Default value for new records
            'placeholder'   => 'Enter first name',
            'help_text'     => 'Legal first name',
            'autoGenerate'  => false,            // Enable auto-generation button
            'generator'     => [                 // Auto-generation config (if autoGenerate=true)
                'pattern'   => 'EMP-{YYYY}-{####}',
            ],
            // For select/relationship fields:
            'options'       => [],               // Static options array [value => label]
            'options_model' => null,             // Model class for dynamic options
            'options_method' => 'pluck',         // Method to call on options_model
            'relationship'  => null,             // Relationship name on the model
            'multiple'      => false,            // Multi-select
            // For file fields:
            'fileTypes'     => ['jpg','jpeg','png','pdf','doc','docx'],
            'maxSizeMB'     => 1,                // Max file size in MB
            'disk'          => 'public',         // Storage disk
            'path'          => 'uploads',        // Storage path
        ],
        // ... more fields
    ],

    // ── FIELD GROUPS ──────────────────────────────────────
    // Organizes form fields into tabs/sections
    'fieldGroups' => [
        [
            'key'    => 'personal',
            'label'  => 'Personal Information',
            'icon'   => 'fa-user',
            'fields' => ['first_name', 'last_name', 'email', 'phone'],
        ],
        [
            'key'    => 'employment',
            'label'  => 'Employment Details',
            'icon'   => 'fa-briefcase',
            'fields' => ['employee_id', 'department_id', 'position', 'start_date'],
        ],
    ],

    // ── CONTROLS ──────────────────────────────────────────
    // Table-level controls. Use 'all' to enable everything.
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

    // ── HIDDEN FIELDS ─────────────────────────────────────
    'hiddenFields' => [
        'onTable'    => [],    // Fields hidden from table columns
        'onNewForm'  => [],    // Fields hidden on create form
        'onEditForm' => [],    // Fields hidden on edit form
        'onQuery'    => [],    // Fields excluded from queries
        'onDetail'   => [],    // Fields hidden from detail view
    ],

    // ── RELATIONS ─────────────────────────────────────────
    'relations' => [
        'department' => [
            'model'     => 'App\\Modules\\Hr\\Models\\Department',
            'foreign_key' => 'department_id',
            'display'   => 'name',
        ],
    ],

    // ── SWITCH VIEWS ──────────────────────────────────────
    // Alternative view modes for the table
    'switchViews' => [
        'card' => ['label' => 'Card View', 'icon' => 'fa-th-large'],
        'list'  => ['label' => 'List View', 'icon' => 'fa-list'],
    ],

    // ── ACTIONS ───────────────────────────────────────────
    'moreActions' => [
        [
            'label'  => 'Send Welcome Email',
            'action' => 'sendWelcomeEmail',
            'icon'   => 'fa-envelope',
            'permission' => 'hr.send-welcome-email',
        ],
    ],

    // ── REPORTS ───────────────────────────────────────────
    'reports' => [
        'headcount' => [
            'label' => 'Headcount Report',
            'type'  => 'tabular',  // 'tabular' or 'dashboard'
        ],
    ],
];
```

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

---

**Related files**: [`00-index.md`](./00-index.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)
