# QuickerFaster UI Library — Contracts & Interfaces

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`14-integration-map.md`](./14-integration-map.md)

---

## 4.4 Contracts (Interfaces)

All contracts live in `src/Contracts/` with default implementations in `src/Services/`. Contracts are the **extension points** for business modules — most are polymorphic, meaning any Eloquent model can implement them to opt into library capabilities.

### Workflowable Contract

**Location**: [`src/Contracts/Workflow/Workflowable.php`](../../src/Contracts/Workflow/Workflowable.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Workflow;

interface Workflowable
{
    /** Get the unique identifier for this workflowable entity. */
    public function getWorkflowableId(): int|string;

    /** Get the workflow definition key (e.g., 'leave_request', 'expense_claim'). */
    public function getWorkflowDefinitionKey(): string;

    /** Get additional context data for workflow routing decisions. */
    public function getWorkflowContext(): array;
}
```

Any Eloquent model implements this contract to become workflow-enabled. The [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) uses these methods to start, advance, and complete workflows.

### ApprovalModelResolver Contract

**Location**: [`src/Contracts/Approvals/ApprovalModelResolver.php`](../../src/Contracts/Approvals/ApprovalModelResolver.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Approvals;

interface ApprovalModelResolver
{
    public function resolveRequestModel(): string;
    public function resolveTierModel(): string;
    public function resolveLogModel(): string;
    public function resolveTierApprovalModel(): string;
}
```

Abstracts model class resolution for approval workflows. The default implementation ([`src/Services/Approvals/ApprovalModelResolver.php`](../../src/Services/Approvals/ApprovalModelResolver.php)) reads from `config('ui-library.approvals.models')`.

### CompanyProvider Contract

**Location**: [`src/Contracts/Navigation/CompanyProvider.php`](../../src/Contracts/Navigation/CompanyProvider.php:8)

```php
namespace QuickerFaster\UILibrary\Contracts\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User;

interface CompanyProvider
{
    public function getCompanies(?User $user): Collection;
    public function getCurrentCompanyId(?User $user): ?int;
}
```

Abstracts company resolution for multi-tenant navigation. The default implementation is [`NullCompanyProvider`](../../src/Services/Navigation/NullCompanyProvider.php:9) (returns empty collection/null). Consuming apps bind their own implementation in `AppServiceProvider`.

### ModuleContract

**Location**: [`src/Contracts/Modules/ModuleContract.php`](../../src/Contracts/Modules/ModuleContract.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Modules;

interface ModuleContract
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getIcon(): string;
    public function getRoute(): string;
    public function getOrder(): int;
    public function getRoles(): array;
    public function isCore(): bool;
    public function getPath(): string;
}
```

Defines the contract for module metadata used by the module registry and navigation system.

### NavigationProvider Contract

**Location**: [`src/Contracts/Navigation/NavigationProvider.php`](../../src/Contracts/Navigation/NavigationProvider.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Navigation;

interface NavigationProvider
{
    public function getNavigationItems(string $module, ?string $context = null): array;
    public function getContextGroups(string $module): array;
    public function getSharedItems(string $module, string $section): array;
}
```

### SettingsProvider Contract

**Location**: [`src/Contracts/Settings/SettingsProvider.php`](../../src/Contracts/Settings/SettingsProvider.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Settings;

interface SettingsProvider
{
    public function resolve(string $key): mixed;
}
```

### FieldType Contract

**Location**: [`src/Contracts/FieldTypes/FieldType.php`](../../src/Contracts/FieldTypes/FieldType.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\FieldTypes;

interface FieldType
{
    public function __construct(string $name, array $definition);

    /** Render the field for a form (input, select, etc.) */
    public function renderForm($value = null): string;

    /** Render the field value for a table cell */
    public function renderTable($value, $record): string;

    /** Render the field value for a detail view */
    public function renderDetail($value, $record): string;

    /** Get validation rules for this field */
    public function getValidationRules(): array;

    /** Get options for select, radio, etc. */
    public function getOptions(): array;

    /** Whether this field represents a relationship */
    public function isRelationship(): bool;

    /** If relationship, return its configuration */
    public function getRelationshipConfig(): ?array;

    /** Get the field's label */
    public function getLabel(): string;

    /** Get the field's name */
    public function getName(): string;

    /** Render an inline editor for use inside a table cell */
    public function renderInlineEditor($value, $record, array $extra = []): string;
}
```

**Implementations** (all in [`src/Components/FieldTypes/`](../../src/Components/FieldTypes/)):

`TextField`, `TextareaField`, `SelectField`, `DatepickerField`, `DatetimepickerField`, `TimepickerField`, `CheckboxField`, `RadioField`, `FileField`, `ImageField`, `PasswordField`, `LivewireSearchableSelectField`, `MorphToSelectField`, `PolicyCalculationBuilderField`

### Widget Contract

**Location**: [`src/Contracts/Widgets/Widget.php`](../../src/Contracts/Widgets/Widget.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Widgets;

interface Widget
{
    public function __construct(array $definition);
    public function setData(): void;
    public function render(): string;
    public function getTitle(): ?string;
    public function getWidth(): int;
}
```

### Documentable Contract

**Location**: [`src/Contracts/Documents/Documentable.php`](../../src/Contracts/Documents/Documentable.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Documents;

interface Documentable
{
    /** Get the unique identifier for this documentable entity. */
    public function getDocumentableId(): int|string;

    /** Get the document type key (e.g., 'employee_contract', 'payslip'). */
    public function getDocumentType(): string;

    /** Get the storage folder path relative to the configured disk. */
    public function getDocumentStoragePath(): string;

    /** Get template data for document generation. */
    public function getDocumentTemplateData(): array;
}
```

Any Eloquent model implements this contract to become document-enabled. The [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) uses these methods to store, generate, and retrieve documents.

### OnboardingCondition Contract

**Location**: [`src/Contracts/OnboardingCondition.php`](../../src/Contracts/OnboardingCondition.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts;

interface OnboardingCondition
{
    /** Determine if the step is complete for the given user. */
    public function __invoke($user): bool;
}
```

### Notifiable Contract

**Location**: [`src/Contracts/Notifications/Notifiable.php`](../../src/Contracts/Notifications/Notifiable.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Notifications;

interface Notifiable
{
    /** Get the unique identifier for this notifiable entity. */
    public function getNotifiableId(): int|string;

    /** Get the email address for mail channel delivery. */
    public function getNotificationEmail(): ?string;

    /** Get additional context data for template variable replacement. */
    public function getNotificationContext(): array;
}
```

Any Eloquent model implements this contract to receive notifications. The [`NotificationService`](../../src/Services/Notifications/NotificationService.php) uses these methods to resolve recipients and template data.

### NotificationChannel Contract

**Location**: [`src/Contracts/Notifications/NotificationChannel.php`](../../src/Contracts/Notifications/NotificationChannel.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Notifications;

use QuickerFaster\UILibrary\Models\Notification;

interface NotificationChannel
{
    /** Send a notification through this channel. */
    public function send(Notification $notification): bool;

    /** Get the channel identifier (e.g., 'database', 'mail'). */
    public function getChannel(): string;
}
```

Channel implementations are registered with [`NotificationService::registerChannel()`](../../src/Services/Notifications/NotificationService.php). Built-in channels: [`DatabaseChannel`](../../src/Services/Notifications/Channels/DatabaseChannel.php) (in-app), [`MailChannel`](../../src/Services/Notifications/Channels/MailChannel.php) (email).

### Reportable Contract

**Location**: [`src/Contracts/Reports/Reportable.php`](../../src/Contracts/Reports/Reportable.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Reports;

interface Reportable
{
    /** Generate the report and return a Document. */
    public function generate(array $parameters = []): \QuickerFaster\UILibrary\Models\Document;

    /** Get the list of user IDs who should receive this report. */
    public function recipients(): array;

    /** Get the report type key (e.g., 'payroll', 'headcount'). */
    public function getReportType(): string;
}
```

Any class implementing this contract can be registered as a report type in `config('ui-library.reports.report_types')`. The [`ReportEngine`](../../src/Services/Reports/ReportEngine.php) resolves the implementation from config, calls [`generate()`](../../src/Contracts/Reports/Reportable.php:10) to produce a [`Document`](../../src/Models/Document.php), and delivers it to [`recipients()`](../../src/Contracts/Reports/Reportable.php:15) via [`NotificationService`](../../src/Services/Notifications/NotificationService.php).

---

## Additional Contracts (Not in Blueprint §4.4)

These contracts exist in the current codebase but are not yet documented in the original blueprint §4.4. Signatures below are pulled directly from source.

### ReferenceDataProvider Contract

**Location**: [`src/Contracts/ReferenceData/ReferenceDataProvider.php`](../../src/Contracts/ReferenceData/ReferenceDataProvider.php:7)

```php
namespace QuickerFaster\UILibrary\Contracts\ReferenceData;

use Illuminate\Support\Collection;

interface ReferenceDataProvider
{
    /** Get all items of a given type. */
    public function getAll(string $type): Collection;

    /** Get a single item by type and ID. */
    public function getById(string $type, int|string $id): ?array;

    /** Get all registered reference data types. */
    public function getTypes(): array;

    /** Create a new reference data item. */
    public function create(string $type, string $key, mixed $value, array $meta = []): array;

    /** Update an existing reference data item. */
    public function update(int|string $id, array $data): array;

    /** Delete a reference data item. */
    public function delete(int|string $id): bool;
}
```

Any service can implement this contract to provide reference data from an alternative source (API, external database, etc.). The default implementation is [`ReferenceDataService`](../../src/Services/ReferenceData/ReferenceDataService.php), backed by the `reference_data_items` table with cache-backed reads. See [`09-engines-and-services.md`](./09-engines-and-services.md) for the Reference Data Engine deep-dive.

### WorkspaceResolver Contract

**Location**: [`src/Contracts/Navigation/WorkspaceResolver.php`](../../src/Contracts/Navigation/WorkspaceResolver.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Navigation;

interface WorkspaceResolver
{
    /**
     * Get the current workspace context map.
     *
     * Returns key-value pairs representing the current workspace context,
     * for example:
     *   ['company_id' => 1, 'role' => 'payroll_admin', 'department_type' => 'engineering', 'features' => ['departments', 'time']]
     *
     * Navigation items can define `workspace` constraints that are matched
     * against this map. Context groups can define `feature` gates that are
     * checked against the `features` array.
     */
    public function resolve(): array;

    /**
     * Check if a specific feature is enabled in the current workspace.
     */
    public function hasFeature(string $feature): bool;
}
```

Abstracts workspace context resolution for multi-tenant/role-based navigation filtering. The default implementation is [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php) (returns an empty context). [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php) consumes the resolved context to apply feature gates and role/department constraints. See [`06-navigation-system.md`](./06-navigation-system.md) for the navigation system.

### ActivityLogModelResolver Contract

**Location**: [`src/Contracts/ActivityLogs/ActivityLogModelResolver.php`](../../src/Contracts/ActivityLogs/ActivityLogModelResolver.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\ActivityLogs;

interface ActivityLogModelResolver
{
    /**
     * Resolve the FQCN of the ActivityLog Eloquent model.
     * Return null when activity logging is not configured by the consuming app.
     */
    public function resolveModel(): ?string;
}
```

**Recently added** — resolves the `ActivityLog` Eloquent model FQCN. The default implementation ([`src/Services/ActivityLogs/ActivityLogModelResolver.php`](../../src/Services/ActivityLogs/ActivityLogModelResolver.php:7)) returns `config('ui-library.activity_logs.model')` (null when the consuming app has not configured activity logging). This contract decouples the library from the `App\Modules\Admin\Models\ActivityLog` hardcoded reference previously present in [`ActivityLogWidgetProcessor`](../../src/Widgets/ActivityLogWidgetProcessor.php).

---

**Related files**: [`00-index.md`](./00-index.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`14-integration-map.md`](./14-integration-map.md)
