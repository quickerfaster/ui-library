# Getting Started

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

This guide walks you through installing the QuickerFaster UI Library, publishing its configuration, and creating your first business module. For the library's internal architecture, see [../library/01-core-concepts.md](../library/01-core-concepts.md).

---

## 1. Installation

### 1.1 Composer Require

```bash
composer require quicker-faster/ui-library
```

This installs the package into your Laravel application and registers the `QuickerFaster\UILibrary\UILibraryServiceProvider` automatically via Laravel's package auto-discovery.

### 1.2 Path Repository (Local Development)

When developing the library alongside your application, use a Composer path repository:

```json
// composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/ui-library"
        }
    ],
    "require": {
        "quicker-faster/ui-library": "@dev"
    }
}
```

### 1.3 Running `ui-library:install`

The installer performs a single-command setup:

```bash
php artisan ui-library:install
```

**What the installer does**:

1. **Publishes the library config** — copies `src/Config/ui-library.php` to `config/ui-library.php`
2. **Publishes migrations** — library-owned migrations for exports, imports, workflows, documents, notifications, report schedules, and reference data
3. **Publishes assets** — CSS/JS files to `public/vendor/ui-library/`
4. **Publishes views** — all Blade views to `resources/views/vendor/qf/` for customization
5. **Runs migrations** — creates all library-owned tables
6. **Seeds default data** — roles, super admin user, notification templates, system settings
7. **Injects required traits** — adds `HasUILibraryUser` and `HasRoles` traits to your User model
8. **Configures auth** — sets up Fortify, Socialite, and scaffolded auth views
9. **Links storage** — creates the `public/storage` symlink
10. **Clears caches** — config, route, view, and application caches

**Post-install verification**:

```bash
php artisan ui-library:discover   # Verify module auto-discovery
php artisan route:list             # Confirm library routes are registered
```

---

## 2. Configuration & Publishing

### 2.1 Publishing Config

If you need to customize the library configuration after installation:

```bash
php artisan vendor:publish --tag=ui-library-config
```

This publishes `config/ui-library.php`. The full schema is documented in [../library/10-settings-and-config.md](../library/10-settings-and-config.md). Key sections:

| Section | Purpose |
|---------|---------|
| `modules` | Module registry: `user_facing`, `depends_on`, `order`, `roles` |
| `navigation` | Top bar, sidebar, bottom bar, company provider, workspace resolver |
| `discovery` | Auto-discovery toggles: listeners, reports, workflows, cache TTL |
| `tenancy` | Tenant column (`company_id`) and session key |
| `workflows` | Workflow definitions (merged from business modules) |
| `documents` | Document disk, max file size, allowed types |
| `notifications` | Default channels, queue connection |
| `reports` | Report types registry, frequencies, notification channels |
| `catch_all` | Catch-all route security: allowed modules, auth, gate, rate limiting |

### 2.2 Environment Variables

The library uses the `UI_LIBRARY_*` prefix convention for environment overrides:

| Variable | Default | Purpose |
|----------|---------|---------|
| `UI_LIBRARY_USER_MODEL` | `config('auth.providers.users.model')` | User model FQCN |
| `UI_LIBRARY_ACTIVITY_LOG_MODEL` | `null` | Activity log model for activity-log widget |
| `UI_LIBRARY_DOCUMENT_DISK` | `public` | Storage disk for documents |
| `UI_LIBRARY_MAX_FILE_SIZE` | `10240` | Max upload size in KB |
| `UI_LIBRARY_REPORT_QUEUE` | `database` | Queue connection for report generation |

### 2.3 Publishing Assets

```bash
php artisan vendor:publish --tag=ui-library-assets
```

CSS and JS assets are copied to `public/vendor/ui-library/`. The main stylesheet is `quicker-faster.css` and the main script is `quicker-faster.js`.

### 2.4 Publishing Views

```bash
php artisan vendor:publish --tag=ui-library-views
```

All Blade views are published to `resources/views/vendor/qf/`. From here you can customize any library view without modifying the package.

The `ui-library-core-views` tag publishes the core module (Admin/System) views to `resources/views/vendor/qf-core/{module}/`:

```bash
php artisan vendor:publish --tag=ui-library-core-views
```

This keeps core-module views (e.g., `admin/`, `system/`) separate from the shared `qf/` views so the two namespaces don't collide.

> **⚠️ Vendor override sync**: If you publish and customize [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) to `resources/views/vendor/qf/`, you must keep it in sync with the library version. The top-nav includes the Quick Actions UI (⚡ button with ranked dropdown + command palette entry point), and a stale override will silently drop these features.

---

## 3. Your First Module

### 3.1 Create the Directory Structure

```bash
mkdir -p app/Modules/Billing/{Data,Models,Resources/views,Routes,Database/Migrations}
```

### 3.2 Create a Data Config

Create `app/Modules/Billing/Data/invoice.php`:

```php
<?php

return [
    'model' => 'App\\Modules\\Billing\\Models\\Invoice',

    'fieldDefinitions' => [
        'invoice_number' => [
            'field_type' => 'string',
            'label'      => 'Invoice #',
            'validation' => 'required|string|max:50',
            'sortable'   => true,
            'searchable' => true,
            'autoGenerate' => true,
            'generator'  => ['pattern' => 'INV-{YYYY}-{####}'],
        ],
        'client_name' => [
            'field_type' => 'string',
            'label'      => 'Client',
            'validation' => 'required|string|max:255',
            'sortable'   => true,
            'searchable' => true,
        ],
        'amount' => [
            'field_type' => 'string',
            'label'      => 'Amount',
            'validation' => 'required|numeric|min:0',
            'sortable'   => true,
        ],
        'due_date' => [
            'field_type' => 'datepicker',
            'label'      => 'Due Date',
            'validation' => 'required|date',
            'sortable'   => true,
        ],
        'status' => [
            'field_type' => 'select',
            'label'      => 'Status',
            'options'    => [
                'draft'     => 'Draft',
                'sent'      => 'Sent',
                'paid'      => 'Paid',
                'overdue'   => 'Overdue',
            ],
            'validation' => 'required|string',
        ],
    ],

    'fieldGroups' => [
        [
            'key'    => 'details',
            'label'  => 'Invoice Details',
            'fields' => ['invoice_number', 'client_name', 'amount', 'due_date', 'status'],
        ],
    ],

    'controls' => 'all',
];
```

### 3.3 Create a Model

Create `app/Modules/Billing/Models/Invoice.php`:

```php
<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'client_name', 'amount', 'due_date', 'status',
    ];
}
```

### 3.4 Create a View

Create `app/Modules/Billing/Resources/views/index.blade.php`:

```blade
<div>
    <livewire:qf.data-table config-key="billing.invoice" />
</div>
```

### 3.5 Verify Auto-Discovery

Run the discovery command to confirm your module is registered:

```bash
php artisan ui-library:discover
```

The output lists your `Billing` module and the `Invoice` model with its auto-generated CRUD permissions.

### 3.6 Add Navigation

Create `app/Modules/Billing/Config/navigation.php`:

```php
<?php

return [
    'items' => [
        [
            'key'   => 'invoices',
            'label' => 'Invoices',
            'route' => 'billing.index',
            'icon'  => 'fa-file-invoice',
        ],
    ],
    'contexts' => [
        'billing' => [
            'label' => 'Billing',
            'items' => ['invoices'],
        ],
    ],
];
```

Your module is now fully functional. Visit `/{module}/index` (e.g., `/billing/index`) to see the DataTable, or use the navigation sidebar.

---

## 4. Next Steps

- [module-structure.md](module-structure.md) — Full module anatomy, auto-discovery conventions, and navigation config schema
- [data-configs.md](data-configs.md) — DataTable/Form/Detail config schema deep-dive
- [contracts.md](contracts.md) — Implementing contracts for workflows, documents, notifications, and reports
- [multi-tenancy.md](multi-tenancy.md) — Setting up company-scoped multi-tenancy