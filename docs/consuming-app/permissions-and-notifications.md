# Permissions & Notifications

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-09-01

This document covers permission auto-generation and notification template registration for consuming applications. For the library's internal notification engine architecture, see [../library/09-engines-and-services.md](../library/09-engines-and-services.md).

---

## 1. Permission Auto-Generation

### 1.1 Config/permissions.php Schema

The library auto-generates CRUD permission names from discovered models. Module-specific overrides are declared in `Config/permissions.php`:

```php
// app/Modules/Billing/Config/permissions.php
return [
    'custom' => ['approve_invoice', 'void_invoice'],
];
```

### 1.2 Permission Naming Convention

Auto-generated permissions follow the `{entity}.{action}` pattern:

| Action | Permission |
|--------|-----------|
| View | `view_invoice` |
| Create | `create_invoice` |
| Edit | `edit_invoice` |
| Delete | `delete_invoice` |

Custom permissions use the module name as a prefix:

| Permission | Purpose |
|-----------|---------|
| `approve_invoice` | Approve an invoice in a workflow |
| `void_invoice` | Void/annul an invoice |

### 1.3 How DiscoveryRegistrar Processes Permissions

The `DiscoveryRegistrar` auto-generates CRUD permissions for each discovered model. The `Config/permissions.php` file's `custom` array is merged alongside the auto-generated names. See [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md) for the discovery internals.

### 1.4 Seeding Permissions

The installer's `RoleSeeder` creates default roles and assigns permissions:

```php
// Roles created by the installer:
// - super_admin: all permissions
// - admin: module-level access
// - user: basic access
```

The `SuperAdminSeeder` creates a super admin user with all permissions. Consuming apps can extend or replace these seeders by publishing and modifying them.

---

## 2. Notification Templates

### 2.1 Data/notifications.php Schema

Notification templates are declared in `Data/notifications.php`:

```php
// app/Modules/Billing/Data/notifications.php
return [
    'templates' => [
        'invoice_submitted' => [
            'channel' => 'mail',
            'subject' => 'Invoice {number} submitted',
            'body'    => 'Invoice {number} for {amount} has been submitted and is awaiting approval.',
        ],
        'invoice_approved' => [
            'channel' => 'database',
            'subject' => 'Invoice {number} approved',
            'body'    => 'Invoice {number} has been approved.',
        ],
        'invoice_rejected' => [
            'channel' => 'mail',
            'subject' => 'Invoice {number} rejected',
            'body'    => 'Invoice {number} has been rejected. Reason: {reason}',
        ],
    ],
];
```

### 2.2 Template Registration

Templates are auto-discovered by the `DiscoveryRegistrar` and registered into the notification template/channel registry. No manual registration is needed.

### 2.3 {placeholder} Variables

Templates use `{placeholder}` syntax for dynamic content. Available placeholders depend on the notification context:

| Placeholder | Source | Example |
|------------|--------|---------|
| `{number}` | Model attribute | `INV-2026-0001` |
| `{amount}` | Model attribute | `1,500.00` |
| `{reason}` | Passed in dispatch data | `Amount exceeds budget limit` |
| `{user_name}` | `Notifiable::getNotificationContext()` | `John Doe` |
| `{company_name}` | `Notifiable::getNotificationContext()` | `Acme Corp` |

### 2.4 Default Templates

The library ships with default templates seeded by `NotificationTemplateSeeder`:

| Template Type | Channel | Purpose |
|--------------|---------|---------|
| `workflow_submitted` | database | Workflow submitted for approval |
| `workflow_approved` | database | Workflow step approved |
| `workflow_rejected` | database | Workflow step rejected |
| `workflow_recalled` | database | Workflow recalled by submitter |
| `document_generated` | mail | Document generated and ready |
| `report_ready` | mail | Scheduled report ready for download |

### 2.5 Consuming-App Notification Template Seeders

The reference consuming app ships 4 additional seeders beyond the library defaults:

| Seeder | Module | Templates | Purpose |
|--------|--------|-----------|---------|
| `WorkflowNotificationTemplateSeeder` | Payroll | 8 (4 types × 2 channels) | Workflow event notifications for payroll runs |
| `EssNotificationTemplateSeeder` | HR | 12 (6 types × 2 channels) | Employee self-service events (payslip, leave, holiday, clock) |
| `LeaveWorkflowNotificationTemplateSeeder` | Leave | 8 (4 types × 2 channels) | Leave request workflow notifications |
| `NotificationTemplateSeeder` (library) | Common | 8 | Built-in document, report, and workflow templates |

**Total**: 25 consuming-app templates + 8 library defaults = 33 templates across all modules.

These seeders are invoked from the root [`DatabaseSeeder`](hr-consuming-app:database/seeders/DatabaseSeeder.php).

---

## 3. Notification Dispatch

### 3.1 Programmatic Dispatch

```php
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

$service = app(NotificationService::class);

// Dispatch using a template
$service->dispatch(
    $user,                    // Notifiable recipient
    'invoice_approved',       // Template type key
    [                         // Placeholder data
        'number' => $invoice->invoice_number,
        'amount' => number_format($invoice->amount, 2),
    ]
);
```

### 3.2 Channel Configuration

The library supports two channels out of the box:

| Channel | Class | Purpose |
|---------|-------|---------|
| `database` | `DatabaseChannel` | In-app notification (persisted to `notifications` table) |
| `mail` | `MailChannel` | Email notification via `Mail::raw()` |

Configure default channels:

```php
// config/ui-library.php
'notifications' => [
    'default_channels' => ['database', 'mail'],
    'queue_connection' => env('UI_LIBRARY_NOTIFICATION_QUEUE', 'sync'),
],
```

### 3.3 User Preferences

Users can manage their notification preferences per type and channel via the `NotificationPreference` model:

```php
// Enable mail notifications for workflow events
NotificationPreference::updateOrCreate(
    ['user_id' => $user->id, 'notification_type' => 'workflow_submitted', 'channel' => 'mail'],
    ['enabled' => true]
);

// Disable database notifications for report events
NotificationPreference::updateOrCreate(
    ['user_id' => $user->id, 'notification_type' => 'report_ready', 'channel' => 'database'],
    ['enabled' => false]
);
```

---

## 4. Consuming-App Notification Concerns

### 4.1 Throttling

To prevent notification floods, implement throttling in your listeners:

```php
use Illuminate\Support\Facades\Cache;

protected function handleCreated(DataTableRecordSaved $event): void
{
    $key = 'notification_throttle:' . $event->model->id . ':created';
    
    if (Cache::has($key)) {
        return;
    }
    
    Cache::put($key, true, now()->addMinutes(5));
    
    // Dispatch notification
}
```

### 4.2 Segmentation

Use the `Notifiable` contract's `getNotificationContext()` to control which users receive which notifications:

```php
public function getNotificationContext(): array
{
    return [
        'user_name'    => $this->name,
        'department'   => $this->department->name,
        'company_name' => config('app.name'),
    ];
}
```

### 4.3 Custom Actions

Add notification actions (e.g., "View Invoice") by including action data in the dispatch payload:

```php
$service->dispatch($user, 'invoice_submitted', [
    'number' => $invoice->invoice_number,
    'action_url' => route('invoices.show', $invoice),
    'action_label' => 'View Invoice',
]);
```

### 4.4 Template Variables

See [19-notification-consuming-app-guide.md](19-notification-consuming-app-guide.md) for a full deep-dive into notification template customization.

---

## 5. Testing

### 5.1 Asserting Permissions Are Seeded

```php
public function test_permissions_are_seeded(): void
{
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

    $this->assertDatabaseHas('permissions', [
        'name' => 'view_invoice',
    ]);
    $this->assertDatabaseHas('permissions', [
        'name' => 'approve_invoice',
    ]);
}
```

### 5.2 Testing Notification Dispatch

```php
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;
use QuickerFaster\UILibrary\Models\Notification;

public function test_notification_is_dispatched(): void
{
    $user = User::factory()->create();
    $service = app(NotificationService::class);

    $service->dispatch($user, 'invoice_approved', [
        'number' => 'INV-2026-0001',
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id'   => $user->id,
        'type'            => 'invoice_approved',
    ]);
}
```

---

## Cross-References

- [../library/09-engines-and-services.md](../library/09-engines-and-services.md) — Notification engine internals
- [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md) — DiscoveryRegistrar internals
- [module-structure.md](module-structure.md) — Permissions & notifications config conventions
- [contracts.md](contracts.md) — Notifiable contract implementation
- [19-notification-consuming-app-guide.md](19-notification-consuming-app-guide.md) — Notification deep-dive