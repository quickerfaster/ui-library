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

### 1.5 Module Dashboard Access (`module_access`)

The `module_access` configuration in `config/ui-library.php` controls which roles can access each module's dashboard. It maps URL prefixes to allowed roles:

```php
'module_access' => [
    // Fine-grained HR prefixes — must come BEFORE the broad 'hr' prefix
    // because the middleware uses first-match-wins (str_starts_with).
    'hr/my-'       => ['employee', 'manager', 'hr_manager', 'hr_officer', 'admin', 'super_admin', 'company_admin'],
    'hr/leave-hub' => ['employee', 'manager', 'hr_manager', 'hr_officer', 'admin', 'super_admin', 'company_admin'],
    'hr/team-'     => ['employee', 'manager', 'hr_manager', 'hr_officer', 'admin', 'super_admin', 'company_admin'],

    'hr'           => ['hr_manager', 'hr_officer', 'admin', 'super_admin', 'company_admin'],
    'organization' => ['hr_manager', 'hr_officer', 'admin', 'super_admin', 'company_admin'],
    'admin'        => ['admin', 'super_admin', 'company_admin'],

    // Employees do NOT get direct module access — all self-service
    // functionality is available through hr/my-* ESS wrapper routes.
    'leave'        => ['hr_manager', 'hr_officer', 'manager', 'supervisor', 'admin', 'super_admin', 'company_admin'],
    'holiday'      => ['hr_manager', 'hr_officer', 'admin', 'super_admin', 'company_admin'],
    'attendance'   => ['hr_manager', 'hr_officer', 'manager', 'supervisor', 'admin', 'super_admin', 'company_admin'],

    'payroll'      => ['payroll_officer', 'hr_manager', 'accountant', 'admin', 'super_admin', 'company_admin'],
    'system'       => ['admin', 'super_admin', 'company_admin'],
],
```

**Fine-grained prefix pattern**: When a module serves both admin and self-service users under the same URL namespace (e.g., `/hr/`), use more specific prefixes BEFORE the broad prefix. The [`EnsureModuleDashboardAccess`](../../app/Http/Middleware/EnsureModuleDashboardAccess.php) middleware uses `str_starts_with` with **first-match-wins** semantics. In the example above:

- `/hr/my-portal` matches `hr/my-` → allows employees ✅
- `/hr/leave-hub` matches `hr/leave-hub` → allows employees ✅
- `/hr/employees` matches `hr` → only HR/admin roles ✅

Without the fine-grained prefixes, `/hr/my-portal` would match the broad `hr` prefix and block employees, causing a redirect loop (the middleware redirects employees to `/hr/my-portal`, which then gets blocked again).

**Employee module access**: Employees do not appear in `leave`, `holiday`, or `attendance` module access. They access all self-service functionality through `hr/my-*` ESS wrapper routes. This keeps the module switcher clean — employees never see it.

The `EnsureModuleDashboardAccess` middleware checks this config on every request. Users without the required role are redirected to their appropriate dashboard. `super_admin`, `admin`, and `company_admin` bypass all checks.

The middleware must be registered in the consuming app's `bootstrap/app.php`:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \QuickerFaster\UILibrary\Http\Middleware\ResolveCompanyContext::class,
    ]);
    
    $middleware->alias([
        'qf.resolve-company-context' => \QuickerFaster\UILibrary\Http\Middleware\ResolveCompanyContext::class,
    ]);
})
```

[`ResolveCompanyContext`](../../src/Http/Middleware/ResolveCompanyContext.php) (alias `qf.resolve-company-context`) reads the `current_company_id` from the session and binds it into the service container. This middleware is **required** for multi-tenant scoping — without it, [`CompanyScope`](../../src/Scopes/CompanyScope.php) cannot resolve the current tenant.

### 1.6 Module Switcher Visibility (`module_switcher.roles`)

The module switcher dropdown in the top navigation bar is controlled by `module_switcher.roles`:

```php
'module_switcher' => [
    'enabled' => true,
    'roles' => ['super_admin', 'admin', 'company_admin', 'hr_manager', 'hr_officer', 'payroll_officer', 'manager', 'supervisor', 'recruiter', 'accountant'],
    'links' => [
        // Cross-module links (e.g., "Admin Panel" button)
    ],
],
```

Users not in the configured roles do not see the module switcher at all. Employees are excluded — all self-service functionality is available through the HR module sidebar.

### 1.7 Background Jobs Visibility (`background_jobs.roles`)

The background jobs history icon (🕐) in the top navigation bar is controlled by `background_jobs.roles`:

```php
'background_jobs' => [
    'enabled' => true,
    'roles' => '*',  // All authenticated users
    'icon' => 'fas fa-history',
    'title' => 'Background Jobs',
],
```

**User-scoped filtering**: The [`RecentExports`](../../src/Http/Livewire/Exports/RecentExports.php) and [`RecentImports`](../../src/Http/Livewire/Imports/RecentImports.php) components scope queries to `Auth::id()` by default. Non-admin users only see their own exports and imports.

**Admin bypass**: When [`AuthorizationService::isBypassAllowed()`](../../src/Services/AccessControl/AuthorizationService.php) returns true (super_admin, admin, company_admin), the components show all users' jobs. This allows administrators to monitor and troubleshoot all export/import activity.

### 1.8 Top Navigation Context Group Visibility

Context groups (the tabs in the top navigation bar) are rendered through the [`top-nav-item`](../../src/Resources/views/livewire/navs/partials/top-nav-item.blade.php) partial, which applies permission-based filtering:

1. **Explicit `permission` key**: Checked via `AuthorizationService::canAccessView()`
2. **Explicit `roles` key**: Checked via `$user->hasAnyRole()`. `['*']` is a wildcard that passes for all authenticated users.
3. **URL derivation**: If neither `permission` nor `roles` is set, derives `view_{resource}` from the URL path.

**Best practice**: Use explicit `permission` keys on context groups. Avoid `'roles' => ['*']` unless the tab should be visible to every authenticated user (e.g., "My Portal"). The `roles` wildcard acts as a fallback that overrides the permission check — if a user lacks the permission but the group has `roles: ['*']`, the tab remains visible.

**Overflow "More" dropdown**: The overflow dropdown in [`top-nav.blade.php`](../../src/Resources/views/livewire/navs/top-nav.blade.php) applies the same permission filtering as visible tabs. Items the user cannot access are excluded, and the "More" button hides entirely when no accessible overflow items remain.

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

## 5. Workflow Notification Type Mapping

### 5.1 The `notifications.types` Config in `workflows.php`

Every workflow definition that enables notifications MUST include a `notifications.types` map. This map tells [`WorkflowEngine::notifyTransition()`](../../src/Services/Workflow/WorkflowEngine.php:651) which template type to use for each event:

```php
// app/Modules/Hr/Config/workflows.php
'leave_request_approval' => [
    'label' => 'Leave Request Approval',
    'steps' => [
        // ...
    ],
    'notifications' => [
        'enabled' => true,
        'types' => [
            'submitted'           => 'workflow_submitted',
            'submitted_initiator' => 'workflow_submitted_initiator',
            'approved'            => 'workflow_approved',
            'stage_advanced'      => 'workflow_stage_advanced',
            'workflow_completed'  => 'workflow_completed',
            'rejected'            => 'workflow_rejected',
            'recalled'            => 'workflow_recalled',
        ],
    ],
],
```

**All seven events must be mapped.** Missing an event causes a logged warning and the notification falls back to `workflow_{event}` — which may not match any seeded template.

### 5.2 Template Naming Convention

All workflow notification template types use the `workflow_` prefix:

| Template Type | Purpose | Recipient |
|---------------|---------|-----------|
| `workflow_submitted` | Workflow submitted for approval | Approvers |
| `workflow_submitted_initiator` | Submission confirmation | Submitter |
| `workflow_approved` | Step approved, next step ready | Next approvers |
| `workflow_stage_advanced` | Workflow advanced to next stage | Submitter |
| `workflow_completed` | Workflow fully approved | Submitter |
| `workflow_rejected` | Workflow rejected | Submitter |
| `workflow_recalled` | Workflow recalled | Pending approvers |

**Rule:** Template type values in `notifications.types` must always use the `workflow_` prefix. Never use bare event names (e.g., `'submitted' => 'submitted'`) — they won't match the seeded templates.

### 5.3 `NotificationTemplateIntegrityTest`

The consuming app should include a test that verifies every event in every workflow definition's `notifications.types` has a corresponding template in the `notification_templates` table:

```php
// tests/Feature/Notifications/NotificationTemplateIntegrityTest.php
public function test_all_workflow_notification_types_have_templates(): void
{
    $definitions = config('ui-library.workflows.definitions', []);
    
    foreach ($definitions as $key => $definition) {
        $types = $definition['notifications']['types'] ?? [];
        
        foreach ($types as $event => $templateType) {
            $this->assertDatabaseHas('notification_templates', [
                'type' => $templateType,
            ], "Template '{$templateType}' missing for workflow '{$key}' event '{$event}'");
        }
    }
}
```

This test catches the "added an event to the engine but forgot to map it in config" bug before it reaches production.

### 5.4 Seeding Workflow Templates

The library's [`NotificationTemplateSeeder`](../../src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php) seeds the four basic workflow templates (`workflow_submitted`, `workflow_approved`, `workflow_rejected`, `workflow_recalled`). The consuming app must seed the three additional initiator-feedback templates:

- `workflow_submitted_initiator`
- `workflow_stage_advanced`
- `workflow_completed`

These should be added to the consuming app's workflow-specific seeders (e.g., `LeaveWorkflowNotificationTemplateSeeder`, `WorkflowNotificationTemplateSeeder`).

---

## 6. Testing

### 6.1 Asserting Permissions Are Seeded

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

### 6.2 Testing Notification Dispatch

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

### 1.9 Role Assignment Hierarchy (`role_assignment`)

The `role_assignment` configuration controls which roles each role is allowed to assign to other users. This prevents privilege escalation in invitation forms, employee creation, and the access control manager.

```php
// config/ui-library.php
'role_assignment' => [
    'hierarchy' => [
        'super_admin'    => ['*'],  // can assign any role
        'admin'          => ['admin', 'company_admin', 'hr_manager', 'hr_officer',
                             'payroll_officer', 'accountant', 'manager', 'supervisor',
                             'recruiter', 'employee'],
        'company_admin'  => ['company_admin', 'hr_manager', 'hr_officer', 'payroll_officer',
                             'accountant', 'manager', 'supervisor', 'recruiter', 'employee'],
        'hr_manager'     => ['hr_officer', 'manager', 'supervisor', 'recruiter', 'employee'],
        'hr_officer'     => ['manager', 'supervisor', 'employee'],
        'recruiter'      => ['employee'],
    ],
    'default_assignable' => ['employee'],
],
```

**How it works**:

1. [`AuthorizationService::getAssignableRoles()`](../../src/Services/AccessControl/AuthorizationService.php) reads the hierarchy config
2. It finds all roles the current user has that appear in the hierarchy
3. It collects the union of assignable roles from all matching entries
4. If the user has no matching hierarchy entry, it returns `default_assignable`
5. The `['*']` wildcard means "all roles" (for super_admin)

**UI integration**: All role dropdown components call `getAssignableRoles()` instead of `Role::all()`:
- [`BulkInvite`](../../src/Http/Livewire/Invitations/BulkInvite.php) — bulk invitation form
- [`AccessControlManager`](../../src/Http/Livewire/AccessControls/AccessControlManager.php) — permission assignment panel
- [`ModuleSelector`](../../src/Http/Livewire/AccessControls/ModuleSelector.php) — access control scope selector
- [`RoleAssignmentManager`](../../src/Http/Livewire/AccessControls/RoleAssignmentManager.php) — user role assignment
- `HrEmployeeForm` (consuming app) — employee creation with invitation

**Backend validation**: [`InvitationService::accept()`](../../src/Services/Invitations/InvitationService.php) validates the invited role against the inviter's hierarchy. If the role is outside the inviter's allowed set, it falls back to `default_assignable` (typically `employee`) and logs a warning. This provides defense-in-depth even if the UI filtering is bypassed.

### 1.10 SelectField Role Filtering

Data table forms render role selectors through [`SelectField::getOptions()`](../../src/Components/FieldTypes/SelectField.php). This method resolves options from either `relationship.model` or `options.model` in the field config.

**Security check**: `SelectField::getOptions()` now detects when either source references `Spatie\Permission\Models\Role` and returns [`getAssignableRoles()`](../../src/Services/AccessControl/AuthorizationService.php) directly. This is the single source of truth for role dropdown filtering:

```php
public function getOptions(): array
{
    $roleModel = \Spatie\Permission\Models\Role::class;
    $relationshipModel = $this->definition['relationship']['model'] ?? null;
    $optionsModel = $this->definition['options']['model'] ?? null;

    if (
        $relationshipModel === $roleModel
        || $optionsModel === $roleModel
    ) {
        return AuthorizationService::getAssignableRoles();
    }

    // ... normal relationship/options resolution
}
```

**Why this location matters**: `getOptions()` is called by every rendering path (form, table, detail), so any role selector anywhere in the system is filtered without requiring per-form overrides. The invitation config defines BOTH `relationship.model` and `options.model` — this check catches both.

### 1.11 Admin Gate Bypass

Laravel's native `can:` middleware and `Gate::allows()` check Spatie permissions directly. They do NOT know about [`AuthorizationService::isBypassAllowed()`](../../src/Services/AccessControl/AuthorizationService.php) — so admin roles (`super_admin`, `admin`, `company_admin`) get 403 errors on `can:permission` routes because those permissions aren't explicitly assigned to admin roles in the database.

The consuming app registers a `Gate::before` callback in `AppServiceProvider::boot()`:

```php
Gate::before(function ($user, $ability) {
    if (AuthorizationService::isBypassAllowed($user)) {
        return true;  // admins pass all checks
    }
    return null;     // fall through to normal Spatie checks
});
```

This makes all `can:` middleware, `Gate::allows()`, `$user->can()`, and `@can` directives respect the admin bypass universally.

## Cross-References

- [../library/09-engines-and-services.md](../library/09-engines-and-services.md) — Notification engine internals
- [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md) — DiscoveryRegistrar internals
- [module-structure.md](module-structure.md) — Permissions & notifications config conventions
- [contracts.md](contracts.md) — Notifiable contract implementation
- [19-notification-consuming-app-guide.md](19-notification-consuming-app-guide.md) — Notification deep-dive