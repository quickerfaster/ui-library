# QuickerFaster UI Library — Settings & Library Configuration

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`05-data-configs.md`](./05-data-configs.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`13-adr.md`](./13-adr.md) · [`14-integration-map.md`](./14-integration-map.md)

---

## Settings & Library Configuration

This file is the **canonical home** for two distinct but related concerns:

1. **§5.7 Library Configuration** — the full `config('ui-library')` schema (the library's own configuration).
2. **§8.5 Settings Architecture** — the `SettingsManager` 3-tier cascading resolver and `HasSettings` trait (how consuming apps resolve user/company/system settings).

---

## 5.7 Library Configuration (`config('ui-library')`)

The library's own configuration is defined in [`src/Config/ui-library.php`](../../src/Config/ui-library.php) and accessed via `config('ui-library')`. Key sections:

### Module Paths

```php
'module_paths' => [
    'core'     => null,                    // Set by UILibraryServiceProvider at boot
    'business' => base_path('app/Modules'), // Business module discovery path
],
```

### Navigation

```php
'navigation' => [
    'company_provider'      => \QuickerFaster\UILibrary\Services\Navigation\NullCompanyProvider::class,
    'show_company_switcher' => false,
    'top_bar' => ['enabled' => true, 'show_module_switcher' => true, 'show_company_switcher' => false],
    'sidebar' => ['initial_state' => 'full'],
    'bottom_bar' => ['enabled' => true],
    // Sidebar grouping customization (per navigation.php):
    // 'sidebar' => [
    //     'section_label' => 'string',       // Custom section header label
    //     'collapsible'   => true|false,     // Enable collapse toggle on section headers
    //     'expanded_default' => true|false,  // Whether sections start expanded
    // ],
],
```

> **Cross-link**: The full navigation system (including how `company_provider` and the sidebar grouping keys are consumed) is documented in [`06-navigation-system.md`](./06-navigation-system.md). This section only keeps the canonical schema.

### Approvals

```php
'approvals' => [
    'models' => [
        'request'       => \QuickerFaster\UILibrary\Models\ApprovalRequest::class,
        'tier'          => \QuickerFaster\UILibrary\Models\ApprovalTier::class,
        'log'           => \QuickerFaster\UILibrary\Models\ApprovalLog::class,
        'tier_approval' => \QuickerFaster\UILibrary\Models\ApprovalTierApproval::class,
    ],
],
```

These are library-owned model defaults. Consuming apps can override them by publishing the config. The [`ApprovalModelResolver`](../../src/Services/Approvals/ApprovalModelResolver.php) reads from these keys.

### Workflows

```php
'workflows' => [
    'definitions' => [
        // Business modules merge their workflow definitions here
        // 'leave_request' => [
        //     'label' => 'Leave Request Approval',
        //     'steps' => [
        //         ['name' => 'Manager Approval', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['manager']],
        //         ['name' => 'HR Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['hr']],
        //     ],
        // ],
    ],
],
```

The [`WorkflowEngine::getDefinition()`](../../src/Services/Workflow/WorkflowEngine.php:136) reads from `config("ui-library.workflows.definitions.{$key}")`. Business modules merge their definitions via their service providers.

> **Cross-link**: The full workflow definition schema and usage examples live in [`09-engines-and-services.md`](./09-engines-and-services.md) (Workflow Engine deep-dive).

### Documents

```php
'documents' => [
    'disk'           => env('UI_LIBRARY_DOCUMENT_DISK', 'public'),
    'max_file_size'  => env('UI_LIBRARY_MAX_FILE_SIZE', 10240), // KB
    'allowed_types'  => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv'],
],
```

The [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php:13) reads `disk` from `config('ui-library.documents.disk')`. The `max_file_size` and `allowed_types` are used for upload validation. All values are environment-configurable via the `UI_LIBRARY_*` env prefix.

> **Cross-link**: Document engine deep-dive is in [`09-engines-and-services.md`](./09-engines-and-services.md).

### Reports

```php
'reports' => [
    'default_frequency'      => 'daily',
    'available_frequencies'  => ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],
    'report_types'           => [
        // Business modules register their report implementations here
        // 'payroll' => \App\Modules\Hr\Reports\PayrollReport::class,
    ],
    'notification_channels'  => ['database', 'mail'],
    'queue_connection'       => env('UI_LIBRARY_REPORT_QUEUE', 'database'),
],
```

The [`ReportEngine`](../../src/Services/Reports/ReportEngine.php) resolves report implementations from `config('ui-library.reports.report_types.{type}')`. The `notification_channels` key controls which channels are used for report delivery. The `queue_connection` key determines which queue connection [`GenerateReportJob`](../../src/Jobs/GenerateReportJob.php) dispatches to.

> **Cross-link**: Report engine deep-dive is in [`09-engines-and-services.md`](./09-engines-and-services.md).

---

## 8.5 Settings Architecture

The [`SettingsManager`](../../src/Services/Settings/SettingsManager.php:7) implements a 3-tier cascading resolver:

```
SettingsManager::get('date_format', 'Y-m-d')
        │
        ▼
    Check cache: 'setting_resolved.{context_hash}.date_format'
        │
        ▼ (cache miss)
    Resolver 1: user
        └─ auth()->user()?->getSetting('date_format')
        └─ Uses HasSettings trait → SystemSetting model
        └─ Returns value or null
        │
        ▼ (null → continue)
    Resolver 2: company
        └─ Company::find($companyId)?->getSetting('date_format')
        └─ Uses HasSettings trait → SystemSetting model
        └─ Returns value or null
        │
        ▼ (null → continue)
    Resolver 3: system
        └─ System::find(1)?->getSetting('date_format')
        └─ Uses HasSettings trait → SystemSetting model
        └─ Returns value or null
        │
        ▼ (null → return default)
    Return 'Y-m-d'
```

Cache key includes context hash: `md5($userId . '_' . $module . '_' . $companyId)`, cached for 3600 seconds.

The [`HasSettings`](../../src/Traits/HasSettings.php:8) trait provides:

- `settings()` — polymorphic `morphMany(SystemSetting::class, 'settingable')`
- `getSetting($key, $default)` — cached retrieval
- `setSetting($key, $value, $group)` — update or create with cache invalidation
- `forgetSetting($key)` — delete with cache invalidation

### ADR-006: Three-Tier Settings Resolution (User → Company → System)

**Decision**: Settings cascade through three resolvers with priority: user preferences → company settings → system defaults.

**Implementation** ([`src/Providers/UILibraryServiceProvider.php`](../../src/Providers/UILibraryServiceProvider.php:144)):

```php
$manager->addResolver('user', fn($key) => auth()->user()?->getSetting($key));
$manager->addResolver('company', fn($key) => $company?->getSetting($key));
$manager->addResolver('system', fn($key) => System::find(1)?->getSetting($key));
```

**Why**:

- Users can override company defaults; companies can override system defaults
- Each level uses the same `HasSettings` trait with polymorphic `SystemSetting` model
- Cached per context (user + module + company) for performance

### Reference Catalog Rows

These rows from the component catalog (`07-component-catalog.md`) are reproduced here for settings-focused readers:

| Entry | Type | Location | Purpose |
|-------|------|----------|---------|
| `SettingsManager` | Service | [`src/Services/Settings/SettingsManager.php`](../../src/Services/Settings/SettingsManager.php:7) | 3-tier cascading settings resolver |
| `SystemSetting` | Model | [`src/Models/SystemSetting.php`](../../src/Models/SystemSetting.php) | Polymorphic settings (user/company/system) |
| `HasSettings` | Trait | [`src/Traits/HasSettings.php`](../../src/Traits/HasSettings.php:8) | Polymorphic settings via `SystemSetting` model with caching |
| Settings resolution (component map) | §2.3 | — | `SettingsManager` → User model → Company model → System model (3-tier cascading resolution) |
| `@setting` Blade directive | §8.3 | [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php) | `Blade::directive('setting', ...)` → `@setting('date_format', 'Y-m-d')` |

> **Troubleshooting tip**: If `@setting('key')` returns null or the wrong value, verify the `SystemSetting` records exist at the appropriate level, clear the settings cache with `app(SettingsManager::class)->flush('key')`, and confirm the resolver chain in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php:144).

---

**Related files**: [`00-index.md`](./00-index.md) · [`05-data-configs.md`](./05-data-configs.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`13-adr.md`](./13-adr.md) · [`14-integration-map.md`](./14-integration-map.md)
