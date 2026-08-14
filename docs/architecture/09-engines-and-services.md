# QuickerFaster UI Library — Engines & Services

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`14-integration-map.md`](./14-integration-map.md) · [`16-phase-history.md`](./16-phase-history.md)

---

## Overview

The library ships five engine services, each following a **Contract → Service → Model → Migration** pattern. Each is registered as a singleton in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php) and is fully decoupled (zero `App\Modules` references).

| § | Engine | Service | Contract |
|---|--------|---------|----------|
| 12 | Workflow Engine | [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) | [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php) |
| 13 | Document Engine | [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) | [`Documentable`](../../src/Contracts/Documents/Documentable.php) |
| 14 | Notification Engine | [`NotificationService`](../../src/Services/Notifications/NotificationService.php) | [`Notifiable`](../../src/Contracts/Notifications/Notifiable.php), [`NotificationChannel`](../../src/Contracts/Notifications/NotificationChannel.php) |
| 15 | Scheduled Reports Engine | [`ReportEngine`](../../src/Services/Reports/ReportEngine.php) | [`Reportable`](../../src/Contracts/Reports/Reportable.php) |
| 16 | Reference Data Engine | [`ReferenceDataService`](../../src/Services/ReferenceData/ReferenceDataService.php) | [`ReferenceDataProvider`](../../src/Contracts/ReferenceData/ReferenceDataProvider.php) |

> **Cross-links**: Full contract method signatures live in [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) (referenced, not duplicated here). Phase-status one-liners live in [`16-phase-history.md`](./16-phase-history.md).

---

## 12. Phase 3.1: Workflow Engine

### 12.1 Overview

Config-driven, contract-based generic workflow engine that supersedes the legacy [`ApprovalEngine`](../../src/Services/Approvals/ApprovalEngine.php). Any Eloquent model can become workflow-enabled by implementing the [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php) contract.

### 12.2 Architecture

- **Contract**: [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php:5) — any Eloquent model implements this to become workflow-enabled. Methods: `getWorkflowableId()`, `getWorkflowDefinitionKey()`, `getWorkflowContext()` (signatures in [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md)).
- **Engine**: [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php:12) — transactional, multi-step, sequential advancement. API: `start()`, `approve()`, `reject()`, `recall()`, `getDefinition()`, `hasActiveWorkflow()`.
- **Models**: [`Workflow`](../../src/Models/Workflow.php) (polymorphic via `workflowable_type`/`workflowable_id`), [`WorkflowStep`](../../src/Models/WorkflowStep.php) (role-based, sequential with `approval_mode`: `any` or `all`), [`WorkflowAction`](../../src/Models/WorkflowAction.php) (audit trail of all actions).
- **Config**: `ui-library.workflows.definitions` — business modules merge their workflow definitions via service providers.

### 12.3 Integration

- Registered as singleton in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php).
- Migration at [`Database/Migrations/2026_08_08_000001_create_workflow_tables.php`](../../Database/Migrations/2026_08_08_000001_create_workflow_tables.php).
- Zero `App\Modules` references — fully decoupled.
- Legacy [`ApprovalEngine`](../../src/Services/Approvals/ApprovalEngine.php) still exists for backward compatibility with existing approval workflows.
- **No dedicated Controller, Livewire component, or Blade views** — workflow is dispatched programmatically; UI is provided by the legacy [`ApprovalActions`](../../src/Http/Livewire/Approvals/ApprovalActions.php) and [`ApprovalHistoryTimeline`](../../src/Http/Livewire/Approvals/ApprovalHistoryTimeline.php) Livewire components.

### 12.4 Usage Example

```php
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;

class LeaveRequest extends Model implements Workflowable
{
    public function getWorkflowableId(): int|string
    {
        return $this->id;
    }

    public function getWorkflowDefinitionKey(): string
    {
        return 'leave_request';
    }

    public function getWorkflowContext(): array
    {
        return [
            'department_id' => $this->employee->department_id,
            'days' => $this->total_days,
        ];
    }
}

// Starting a workflow
$engine = app(WorkflowEngine::class);
$workflow = $engine->start($leaveRequest);

// Approving the current step
$engine->approve($workflow, 'Approved by manager');

// Rejecting
$engine->reject($workflow, 'Insufficient leave balance');

// Recalling (by submitter)
$engine->recall($workflow);

// Checking for active workflows
if ($engine->hasActiveWorkflow($leaveRequest)) {
    // Already has a pending workflow
}
```

### 12.5 Workflow Definition Schema

```php
// In config/ui-library.php or merged by a business module:
'workflows' => [
    'definitions' => [
        'leave_request' => [
            'label' => 'Leave Request Approval',
            'steps' => [
                [
                    'name'          => 'Manager Approval',
                    'step_type'     => 'approval',
                    'approval_mode' => 'any',       // 'any' or 'all'
                    'roles'         => ['manager', 'admin'],
                ],
                [
                    'name'          => 'HR Review',
                    'step_type'     => 'approval',
                    'approval_mode' => 'any',
                    'roles'         => ['hr', 'super_admin'],
                ],
            ],
        ],
    ],
],
```

### 12.6 Relationship to Legacy ApprovalEngine

| Aspect | WorkflowEngine (NEW) | ApprovalEngine (LEGACY) |
|--------|---------------------|------------------------|
| Contract | [`Workflowable`](../../src/Contracts/Workflow/Workflowable.php) | None (ad-hoc) |
| Models | [`Workflow`](../../src/Models/Workflow.php), [`WorkflowStep`](../../src/Models/WorkflowStep.php), [`WorkflowAction`](../../src/Models/WorkflowAction.php) | ApprovalRequest, ApprovalTier, ApprovalLog, ApprovalTierApproval |
| Config | `ui-library.workflows.definitions` | ApprovalConfigResolver |
| Decoupling | Zero `App\Modules` references | Uses ApprovalModelResolver contract |
| Status | **Preferred for new features** | Maintained for backward compatibility |

---

## 13. Phase 3.2: Document Engine

### 13.1 Overview

Polymorphic, config-driven document management engine. Replaces the deleted HR-specific `EmployeeDocumentService`. Any Eloquent model can become document-enabled by implementing the [`Documentable`](../../src/Contracts/Documents/Documentable.php) contract.

### 13.2 Architecture

- **Contract**: [`Documentable`](../../src/Contracts/Documents/Documentable.php:5) — models implement to receive documents. Methods: `getDocumentableId()`, `getDocumentType()`, `getDocumentStoragePath()`, `getDocumentTemplateData()`.
- **Engine**: [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php:13) — singleton service. API: `upload()`, `generatePdf()`, `generateExcel()`, `getDocuments()`, `delete()`.
- **Model**: [`Document`](../../src/Models/Document.php:9) — polymorphic (morphs `documentable`), soft deletes via `SoftDeletes` trait, auto file cleanup on delete via `deleteFile()`.
- **Config**: `ui-library.documents` — `disk` (default: `public`), `max_file_size` (default: 10240 KB), `allowed_types` (array of extensions). All values environment-configurable via the `UI_LIBRARY_*` env prefix.

### 13.3 Integration

- Registered as singleton in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php).
- Migration at [`Database/Migrations/2026_08_08_000002_create_documents_table.php`](../../Database/Migrations/2026_08_08_000002_create_documents_table.php).
- Zero `App\Modules` references — fully decoupled.
- Uses existing composer dependencies: `barryvdh/laravel-dompdf` (PDF generation via `generatePdf()`), `maatwebsite/excel` (Excel generation via `generateExcel()`).
- **Full stack**: Contract ([`Documentable`](../../src/Contracts/Documents/Documentable.php)) → Model ([`Document`](../../src/Models/Document.php)) → Service ([`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php)) → Controller ([`DocumentController`](../../src/Http/Controllers/Documents/DocumentController.php)) → Livewire ([`DocumentPreview`](../../src/Http/Livewire/DocumentPreview.php), [`DocumentPreviewModal`](../../src/Http/Livewire/Modals/DocumentPreviewModal.php)) → Views (5 preview partials) → Migration.
- **No dedicated Event or Listener** — document operations are synchronous; no event is dispatched on upload/generate/delete.

### 13.4 Usage Example

```php
use QuickerFaster\UILibrary\Contracts\Documents\Documentable;
use QuickerFaster\UILibrary\Services\Documents\DocumentEngine;

class Employee extends Model implements Documentable
{
    public function getDocumentableId(): int|string
    {
        return $this->id;
    }

    public function getDocumentType(): string
    {
        return 'employee_document';
    }

    public function getDocumentStoragePath(): string
    {
        return 'employees/' . $this->id;
    }

    public function getDocumentTemplateData(): array
    {
        return [
            'employee_name' => $this->full_name,
            'company_name'  => config('app.name'),
        ];
    }
}

// Upload a file
$engine = app(DocumentEngine::class);
$engine->upload($employee, $request->file('document'));

// Generate a PDF from a Blade template
$engine->generatePdf($employee, 'hr::documents.contract', 'contract.pdf', $data);

// Generate an Excel document
$engine->generateExcel($employee, new PayrollExport($employee), 'payslip.xlsx');

// Get all documents for an entity
$documents = $engine->getDocuments($employee);

// Delete a document (also removes the file from storage)
$engine->delete($document);
```

### 13.5 Document Model Schema

The [`documents`](../../Database/Migrations/2026_08_08_000002_create_documents_table.php) table:

| Column | Type | Purpose |
|--------|------|---------|
| `documentable_type` | string | Polymorphic morph type |
| `documentable_id` | bigint | Polymorphic morph ID |
| `name` | string | Display name |
| `file_path` | string | Storage path relative to disk |
| `file_name` | string | Original filename |
| `mime_type` | string | File MIME type |
| `size` | bigint | File size in bytes |
| `document_type` | string | Category key (e.g., `employee_contract`) |
| `disk` | string | Storage disk name |
| `metadata` | json | Arbitrary metadata |
| `deleted_at` | timestamp | Soft delete support |

---

## 14. Phase 3.3: Notification Engine

### 14.1 Overview

Polymorphic, channel-based notification engine. Supports database (in-app) and mail channels with template-based rendering, user preferences, and audit logging.

### 14.2 Architecture

- **Contracts**: [`Notifiable`](../../src/Contracts/Notifications/Notifiable.php) (any model can receive notifications), [`NotificationChannel`](../../src/Contracts/Notifications/NotificationChannel.php) (channel abstraction with `send()` and `getChannel()`).
- **Models**: [`Notification`](../../src/Models/Notification.php) (polymorphic via `notifiable_type`/`notifiable_id`), [`NotificationTemplate`](../../src/Models/NotificationTemplate.php) (type, channel, locale with `{placeholder}` support), [`NotificationPreference`](../../src/Models/NotificationPreference.php) (per-user channel toggle), [`NotificationLog`](../../src/Models/NotificationLog.php) (audit trail of all dispatches).
- **Service**: [`NotificationService`](../../src/Services/Notifications/NotificationService.php) — `dispatch()`, `getUnread()`, `registerChannel()`, template resolution with `{placeholder}` replacement.
- **Channels**: [`DatabaseChannel`](../../src/Services/Notifications/Channels/DatabaseChannel.php) (no-op; notification already persisted by NotificationService), [`MailChannel`](../../src/Services/Notifications/Channels/MailChannel.php) (`Mail::raw()` delivery).
- **Event**: [`NotificationDispatched`](../../src/Events/Notifications/NotificationDispatched.php) — fires after each dispatch with the Notification model as payload.
- **Listener**: [`NotificationEventSubscriber`](../../src/Listeners/NotificationEventSubscriber.php) — logs dispatched notifications to [`NotificationLog`](../../src/Models/NotificationLog.php).
- **Config**: `ui-library.notifications` — `default_channels`, `queue_connection`.
- **Migration**: [`2026_08_08_000003_create_notification_tables.php`](../../Database/Migrations/2026_08_08_000003_create_notification_tables.php) — 4 tables.
- **Seeder**: [`NotificationTemplateSeeder`](../../src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php) — 5 default templates for `document_generated`, `report_ready`, `workflow_stage_changed`, and more.

### 14.3 Integration

- Registered as singleton in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php) with database and mail channels pre-registered.
- Zero `App\Modules` references — fully decoupled.
- **No dedicated Controller or Livewire UI** — notifications are dispatched programmatically; consuming apps build their own notification UI.

### 14.4 Usage Example

```php
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

class User extends Model implements Notifiable
{
    public function getNotifiableId(): int|string
    {
        return $this->id;
    }

    public function getNotificationEmail(): ?string
    {
        return $this->email;
    }

    public function getNotificationContext(): array
    {
        return [
            'user_name' => $this->name,
            'company_name' => config('app.name'),
        ];
    }
}

$service = app(NotificationService::class);
$service->dispatch($user, 'document_generated', ['name' => 'Contract.pdf']);
$unread = $service->getUnread($user);
```

### 14.5 Notification Tables Schema

The [`notification_tables`](../../Database/Migrations/2026_08_08_000003_create_notification_tables.php) migration creates 4 tables:

**notifications**:

| Column | Type | Purpose |
|--------|------|---------|
| `notifiable_type` | string | Polymorphic morph type |
| `notifiable_id` | bigint | Polymorphic morph ID |
| `type` | string | Notification type key (e.g., `document_generated`) |
| `channel` | string | Delivery channel (`database`, `mail`) |
| `subject` | string | Notification subject line |
| `body` | text | Rendered notification body |
| `data` | json | Arbitrary payload data |
| `read_at` | timestamp | Nullable read timestamp |

**notification_templates**:

| Column | Type | Purpose |
|--------|------|---------|
| `type` | string | Notification type key |
| `channel` | string | Target channel |
| `locale` | string | Language locale |
| `subject` | string | Template subject with `{placeholders}` |
| `body` | text | Template body with `{placeholders}` |

**notification_preferences**:

| Column | Type | Purpose |
|--------|------|---------|
| `user_id` | bigint | User FK |
| `notification_type` | string | Type key |
| `channel` | string | Channel name |
| `enabled` | boolean | Whether this channel is enabled |

**notification_logs**:

| Column | Type | Purpose |
|--------|------|---------|
| `notification_id` | bigint | FK to notifications table |
| `channel` | string | Channel used |
| `status` | string | `sent`, `failed` |
| `error` | text | Nullable error message |

---

## 15. Phase 3.4: Scheduled Reports Engine

### 15.1 Overview

Config-driven scheduled report generation engine. Integrates with Document Engine for PDF/Excel generation and Notification Engine for recipient delivery.

### 15.2 Architecture

- **Contract**: [`Reportable`](../../src/Contracts/Reports/Reportable.php) — any report definition implements `generate()`, `recipients()`, `getReportType()`.
- **Model**: [`ReportSchedule`](../../src/Models/ReportSchedule.php) — frequency, time, timezone, recipients, status, next_run_at calculation.
- **Engine**: [`ReportEngine`](../../src/Services/Reports/ReportEngine.php) — resolves [`Reportable`](../../src/Contracts/Reports/Reportable.php) from config, generates via [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php), notifies via [`NotificationService`](../../src/Services/Notifications/NotificationService.php).
- **Job**: [`GenerateReportJob`](../../src/Jobs/GenerateReportJob.php) — queueable, processes a single schedule.
- **Command**: [`reports:generate-scheduled`](../../src/Console/Commands/GenerateScheduledReports.php) — dispatches jobs for all due schedules.
- **Config**: `ui-library.reports` — `report_types` registry, `notification_channels`, `queue_connection`.
- **Migration**: [`2026_08_09_000001_create_report_schedules_table.php`](../../Database/Migrations/2026_08_09_000001_create_report_schedules_table.php).

### 15.3 Integration

- [`ReportEngine`](../../src/Services/Reports/ReportEngine.php) injects [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) and [`NotificationService`](../../src/Services/Notifications/NotificationService.php).
- Report types registered via `config('ui-library.reports.report_types.{type}')`.
- Cron: `* * * * * php artisan reports:generate-scheduled`.
- Zero `App\Modules` references — fully decoupled.

### 15.4 Usage Example

```php
use QuickerFaster\UILibrary\Contracts\Reports\Reportable;
use QuickerFaster\UILibrary\Models\Document;
use QuickerFaster\UILibrary\Services\Documents\DocumentEngine;

class PayrollReport implements Reportable
{
    public function generate(array $parameters = []): Document
    {
        return app(DocumentEngine::class)->generatePdf(
            $this,
            'reports::payroll',
            'payroll.pdf',
            $parameters
        );
    }

    public function recipients(): array
    {
        return [1, 2, 3];
    }

    public function getReportType(): string
    {
        return 'payroll';
    }
}

// Register in config
config()->set('ui-library.reports.report_types.payroll', PayrollReport::class);

// Schedule it
ReportSchedule::create([
    'name' => 'Monthly Payroll',
    'report_type' => 'payroll',
    'frequency' => 'monthly',
    'time' => '06:00',
    'recipients' => [1, 2],
]);
```

### 15.5 Report Schedules Table Schema

The [`report_schedules`](../../Database/Migrations/2026_08_09_000001_create_report_schedules_table.php) table:

| Column | Type | Purpose |
|--------|------|---------|
| `name` | string | Display name for the schedule |
| `report_type` | string | Key matching `config('ui-library.reports.report_types.{type}')` |
| `frequency` | string | `daily`, `weekly`, `monthly`, `quarterly`, `yearly` |
| `time` | time | Scheduled execution time |
| `timezone` | string | Timezone for schedule calculation |
| `recipients` | json | Array of user IDs to receive the report |
| `parameters` | json | Optional parameters passed to `Reportable::generate()` |
| `status` | string | `active`, `paused`, `completed` |
| `last_run_at` | timestamp | Last successful execution |
| `next_run_at` | timestamp | Calculated next execution time |

---

## 16. Phase 3.5: Reference Data Engine

### 16.1 Overview

Polymorphic, cache-backed reference data engine. Provides a single unified store for slowly-changing lookup data (countries, currencies, languages, timezones, payment methods, document types). Any number of reference data types can be registered via config without additional migrations.

### 16.2 Architecture

- **Contract**: [`ReferenceDataProvider`](../../src/Contracts/ReferenceData/ReferenceDataProvider.php) — 6 methods: `getAll()`, `getById()`, `getTypes()`, `create()`, `update()`, `delete()`. Any service can implement this contract to provide reference data from an alternative source (API, external database, etc.).
- **Service**: [`ReferenceDataService`](../../src/Services/ReferenceData/ReferenceDataService.php) — default implementation using the `reference_data_items` table. Uses `Cache::remember()` with configurable TTL (`ui-library.reference_data.cache_ttl`, default: 3600s). Auto-flushes cache on create/update/delete.
- **Model**: [`ReferenceDataItem`](../../src/Models/ReferenceDataItem.php) — single polymorphic model. Columns: `type` (indexed string), `key` (string), `value` (JSON), `meta` (nullable JSON), `is_active` (boolean, default true). Unique constraint on `[type, key]`. Scopes: `ofType($type)`, `active()`.
- **Config**: `ui-library.reference_data` — `cache_ttl` (int), `types` (array of type keys with `label` and `icon`). Default types: `countries`, `currencies`, `languages`, `timezones`, `payment_methods`, `document_types`.
- **Migration**: [`2026_08_09_000002_create_reference_data_items_table.php`](../../Database/Migrations/2026_08_09_000002_create_reference_data_items_table.php).

### 16.3 Integration

- Registered as singleton in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php) — `ReferenceDataProvider` contract bound to `ReferenceDataService`.
- Zero `App\Modules` references — fully decoupled.
- **No dedicated Controller, Livewire component, or Blade views** — reference data is accessed programmatically; consuming apps build their own management UI if needed.
- **No seeders shipped** — seed data (ISO countries, currencies, etc.) is the consuming app's responsibility.

### 16.4 Usage Example

```php
use QuickerFaster\UILibrary\Contracts\ReferenceData\ReferenceDataProvider;

$refData = app(ReferenceDataProvider::class);

// Get all active countries
$countries = $refData->getAll('countries');

// Get a specific item
$item = $refData->getById('currencies', 1);

// Create a new reference data item
$refData->create('payment_methods', 'bank_transfer', 'Bank Transfer', [
    'requires_approval' => true,
]);

// Update an item
$refData->update(1, ['is_active' => false]);

// Delete an item
$refData->delete(1);

// Get all registered types
$types = $refData->getTypes(); // ['countries', 'currencies', 'languages', ...]

// Flush all reference data cache
$refData->flushCache();
```

### 16.5 Reference Data Items Table Schema

The [`reference_data_items`](../../Database/Migrations/2026_08_09_000002_create_reference_data_items_table.php) table:

| Column | Type | Purpose |
|--------|------|---------|
| `id` | bigint | Primary key |
| `type` | string | Reference data type (e.g., `countries`, `currencies`). Indexed. |
| `key` | string | Unique key within the type (e.g., `US`, `NG`) |
| `value` | json | The primary value — flexible JSON for simple strings or complex objects |
| `meta` | json | Optional metadata (e.g., `{ "iso3": "USA", "phone_code": "+1" }`) |
| `is_active` | boolean | Soft enable/disable. Default: `true`. |
| `timestamps` | timestamp | `created_at`, `updated_at` |

**Unique constraint**: `[type, key]` — ensures no duplicate keys within a type.

### 16.6 Design Rationale

**Why a single polymorphic table instead of separate tables per type?**

1. **Extensibility without migrations**: Adding a new reference data type (e.g., `tax_codes`) requires only a config entry — no migration needed.
2. **Consistent API**: All types use the same `getAll()`, `create()`, `update()`, `delete()` methods.
3. **Simpler caching**: Single cache key pattern (`reference_data:{type}`) with unified flush strategy.
4. **Flexible value storage**: JSON columns allow simple values (`"United States"`) or complex objects (`{"name": "United States", "iso3": "USA", "phone_code": "+1"}`) in the same structure.

**Trade-off**: Loses relational integrity (no foreign keys to reference data items). This is acceptable because reference data is slowly-changing and typically cached. For relational reference data, business modules should use their own tables with proper foreign keys.

---

## Legacy: ApprovalEngine (DEPRECATED)

From the §4.3 Services table:

| Service | Location | Purpose |
|---------|----------|---------|
| ApprovalEngine | [`src/Services/Approvals/ApprovalEngine.php`](../../src/Services/Approvals/ApprovalEngine.php:11) | ⚠️ **DEPRECATED** — Legacy approval workflow engine. Maintained for backward compatibility. Prefer [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) for new workflow-enabled features. |

See §12.6 above for the side-by-side comparison with `WorkflowEngine`.

---

**Related files**: [`00-index.md`](./00-index.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`14-integration-map.md`](./14-integration-map.md) · [`16-phase-history.md`](./16-phase-history.md)
