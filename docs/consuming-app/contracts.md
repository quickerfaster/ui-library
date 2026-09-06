
# Contracts & Engines

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

This document is a cookbook for implementing the library's contracts in your application. For the authoritative contract signatures and engine internals, see [../library/08-contracts-and-interfaces.md](../library/08-contracts-and-interfaces.md) and [../library/09-engines-and-services.md](../library/09-engines-and-services.md).

---

## 1. Contract Overview

### 1.1 The 8 Contracts Consuming Apps Implement

The library defines eight contracts that consuming apps implement to integrate with the library's engines:

| Contract | Purpose | Engine |
|----------|---------|--------|
| `Workflowable` | Enable workflow approvals on a model | `WorkflowEngine` |
| `Documentable` | Enable document upload/generation on a model | `DocumentEngine` |
| `Notifiable` | Enable notification delivery to a model | `NotificationService` |
| `Reportable` | Define a scheduled report | `ReportEngine` |
| `CompanyProvider` | Resolve available companies and current company | `NavigationManager` |
| `WorkspaceResolver` | Resolve workspace context for navigation filtering | `WorkspaceFilter` |
| `ApproverResolver` | Resolve approver user IDs from role assignments | `ApprovalEngine` |
| `ApproverLabelResolver` | Resolve display labels for approver user IDs | Approval UI components |

### 1.2 Contract vs. Engine Relationship

Each contract maps to a specific engine service. The contract defines *what* your application provides; the engine defines *how* the library processes it. All engines are registered as singletons in `UILibraryServiceProvider` and are fully decoupled from `App\Modules`.

---

## 2. Workflowable — Enabling Workflows

### 2.1 Implementing Workflowable

Any Eloquent model can become workflow-enabled by implementing the `Workflowable` contract:

```php
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;

class Invoice extends Model implements Workflowable
{
    public function getWorkflowableId(): int|string
    {
        return $this->id;
    }

    public function getWorkflowDefinitionKey(): string
    {
        return 'invoice_approval';
    }

    public function getWorkflowContext(): array
    {
        return [
            'department_id' => $this->department_id,
            'amount'        => $this->amount,
        ];
    }
}
```

### 2.2 Using HasWorkflow Trait

The optional `HasWorkflow` trait adds convenience accessors:

```php
use QuickerFaster\UILibrary\Contracts\Workflow\Workflowable;
use QuickerFaster\UILibrary\Traits\Workflows\HasWorkflow;

class Invoice extends Model implements Workflowable
{
    use HasWorkflow;

    public function getWorkflowDefinitionKey(): string
    {
        return 'invoice_approval';
    }

    public function getWorkflowableId(): string
    {
        return (string) $this->getKey();
    }
}
```

The trait provides `workflow()`, `workflows()`, `activeWorkflow()`, `isUnderApproval()`, and `getWorkflowableId()`.

### 2.3 WorkflowEngine Usage

```php
use QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine;

$engine = app(WorkflowEngine::class);

// Start a workflow
$workflow = $engine->start($invoice);

// Approve the current step
$engine->approve($workflow, 'Approved by manager');

// Reject
$engine->reject($workflow, 'Amount exceeds budget');

// Recall (by submitter)
$engine->recall($workflow);

// Check for active workflows
if ($engine->hasActiveWorkflow($invoice)) {
    // Already has a pending workflow
}
```

### 2.4 Workflow Definition Registration

Define workflows in `app/Modules/{Module}/Config/workflows.php`:

```php
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

See [module-structure.md](module-structure.md) §"Config/workflows.php" for the full schema.

---

## 3. Documentable — Enabling Documents

### 3.1 Implementing Documentable

```php
use QuickerFaster\UILibrary\Contracts\Documents\Documentable;

class Invoice extends Model implements Documentable
{
    public function getDocumentableId(): int|string
    {
        return $this->id;
    }

    public function getDocumentType(): string
    {
        return 'invoice_document';
    }

    public function getDocumentStoragePath(): string
    {
        return 'invoices/' . $this->id;
    }

    public function getDocumentTemplateData(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'client_name'    => $this->client_name,
            'company_name'   => config('app.name'),
        ];
    }
}
```

### 3.2 DocumentEngine Usage

```php
use QuickerFaster\UILibrary\Services\Documents\DocumentEngine;

$engine = app(DocumentEngine::class);

// Upload a file
$engine->upload($invoice, $request->file('document'));

// Generate a PDF from a Blade template
$engine->generatePdf($invoice, 'billing::documents.invoice', 'invoice.pdf', $data);

// Generate an Excel document
$engine->generateExcel($invoice, new InvoiceExport($invoice), 'invoice.xlsx');

// Get all documents for an entity
$documents = $engine->getDocuments($invoice);

// Delete a document (also removes the file from storage)
$engine->delete($document);
```

### 3.3 Using the HasDocuments Trait

Models implementing [`Documentable`](../../src/Contracts/Documents/Documentable.php) can use the [`HasDocuments`](../../src/Traits/Documents/HasDocuments.php) trait for convenience. The trait wraps [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) calls directly on the model, providing a cleaner API:

```php
use QuickerFaster\UILibrary\Contracts\Documents\Documentable;
use QuickerFaster\UILibrary\Traits\Documents\HasDocuments;

class Invoice extends Model implements Documentable
{
    use HasDocuments;

    public function getDocumentableId(): int|string
    {
        return $this->id;
    }

    public function getDocumentType(): string
    {
        return 'invoice_document';
    }

    public function getDocumentStoragePath(): string
    {
        return 'invoices/' . $this->id;
    }

    public function getDocumentTemplateData(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'client_name'    => $this->client_name,
        ];
    }
}
```

The trait provides four methods:

| Method | Signature | Description |
|--------|-----------|-------------|
| `documents()` | `MorphMany` | Polymorphic relationship to the `Document` model |
| `uploadDocument()` | `(UploadedFile $file, ?string $name = null): Document` | Upload and attach a file |
| `getDocuments()` | `Collection` | Retrieve all documents via DocumentEngine |
| `deleteDocument()` | `(Document $document): void` | Delete a document and its file from storage |

**Note**: If your model already defines a `documents()` relationship manually, remove it when using the trait — the trait provides it automatically.

---

## 4. Notifiable — Enabling Notifications

### 4.1 Implementing Notifiable

```php
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;

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
            'user_name'    => $this->name,
            'company_name' => config('app.name'),
        ];
    }
}
```

### 4.2 NotificationService Usage

```php
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

$service = app(NotificationService::class);

// Dispatch a notification using a template
$service->dispatch($user, 'invoice_approved', [
    'number' => $invoice->invoice_number,
    'amount' => $invoice->amount,
]);

// Get unread notifications
$unread = $service->getUnread($user);
```

### 4.3 NotificationChannel Implementation

The library ships with `DatabaseChannel` (in-app) and `MailChannel` (email). See [permissions-and-notifications.md](permissions-and-notifications.md) for template registration and dispatch details.

---

## 5. Reportable — Enabling Scheduled Reports

### 5.1 Implementing Reportable

```php
use QuickerFaster\UILibrary\Contracts\Reports\Reportable;
use QuickerFaster\UILibrary\Models\Document;
use QuickerFaster\UILibrary\Services\Documents\DocumentEngine;

class InvoiceSummaryReport implements Reportable
{
    public function generate(array $parameters = []): Document
    {
        return app(DocumentEngine::class)->generatePdf(
            $this,
            'billing::reports.invoice-summary',
            'invoice-summary.pdf',
            $parameters
        );
    }

    public function recipients(): array
    {
        return [1, 2, 3]; // User IDs
    }

    public function getReportType(): string
    {
        return 'invoice_summary';
    }
}
```

### 5.2 ReportEngine Usage

Register your report in `config('ui-library.reports.report_types')`:

```php
'report_types' => [
    'invoice_summary' => \App\Modules\Billing\Reports\InvoiceSummaryReport::class,
],
```

Or use the `#[ReportType]` attribute for auto-discovery (see [module-structure.md](module-structure.md) §"Auto-Discovery").

### 5.3 ReportSchedule Configuration

```php
use QuickerFaster\UILibrary\Models\ReportSchedule;

ReportSchedule::create([
    'name'        => 'Monthly Invoice Summary',
    'report_type' => 'invoice_summary',
    'frequency'   => 'monthly',
    'time'        => '06:00',
    'recipients'  => [1, 2],
]);
```

---

## 6. CompanyProvider — Multi-Company Resolution

> **Implementation guidance**: See the [`CompanyProvider` contract](../../src/Contracts/Navigation/CompanyProvider.php) and [multi-tenancy.md](multi-tenancy.md) for the full scoping mechanism. The pattern resolves a user's company chain through domain model relationships (e.g., `User → YourModel → Company`) — no `company_id` column on the `users` table. See [multi-tenancy-vs-multi-company.md](multi-tenancy-vs-multi-company.md) for the full distinction between database-level multi-tenancy and column-level multi-company.

### 6.1 Implementing CompanyProvider

```php
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

class YourModelCompanyProvider implements CompanyProvider
{
    public function getCompanies(?User $user): Collection
    {
        // Resolve companies through your domain model relationships.
        // The chain varies per app — e.g., User → YourModel → Company.
        $yourModel = \App\YourModule\Models\YourModel::where('user_id', $user->id)->first();

        if (! $yourModel) {
            return collect();
        }

        return collect([
            [
                'id'   => $yourModel->company_id,
                'name' => $yourModel->company->name,
                'logo' => $yourModel->company->logo_url,
            ],
        ]);
    }

    public function getCurrentCompanyId(?User $user): int|string|null
    {
        return session('current_company_id')
            ?? \App\YourModule\Models\YourModel::where('user_id', $user->id)
                ->value('company_id');
    }
}
```

### 6.2 Registration — Modular Binding Pattern

Bind in your **module's own ServiceProvider**, not in `AppServiceProvider`. This keeps the module self-contained:

```php
// app/Modules/{ModuleName}/Providers/{Module}ServiceProvider.php
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use App\Modules\{ModuleName}\Providers\{Module}CompanyProvider;

class {Module}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyProvider::class,
            {Module}CompanyProvider::class
        );
    }
}
```

This follows the modular binding pattern — each module owns its contract bindings.

Alternatively, publish the config and set `ui-library.navigation.company_provider` to your class.

---

## 7. WorkspaceResolver — Workspace Context

### 7.1 Implementing WorkspaceResolver

```php
use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;

class AppWorkspaceResolver implements WorkspaceResolver
{
    public function resolve(): array
    {
        return [
            'company_id'      => session('current_company_id'),
            'role'            => auth()->user()?->roles->first()?->name,
            'department_type' => auth()->user()?->department?->type,
            'features'        => ['documents', 'reports', 'workflows'],
        ];
    }
}
```

### 7.2 Registration

Publish the config and set `ui-library.navigation.workspace_resolver` to your class, or bind in `AppServiceProvider`.

### 7.3 How WorkspaceFilter Consumes the Context

- **Group filtering**: context groups with a `feature` key are kept only when that feature is in the workspace's `features` array.
- **Item filtering**: items with a `workspace` constraint map are kept only when **all** key/value pairs match the workspace context.

---

## 8. ApproverResolver — Role-to-User Resolution

> **Default**: The library now ships [`WorkspaceScopedApproverResolver`](../../src/Services/Approvals/WorkspaceScopedApproverResolver.php) as the default. Most consuming apps do **not** need a custom implementation — the default works when your User model has a `company_id` column.

### 8.1 The Contract

```php
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;

interface ApproverResolver
{
    /**
     * @param array<int|string> $roleIds Mixed user IDs (int) and role names (string).
     * @param string|null $workspaceId Optional workspace scope.
     * @return int[] Flat list of resolved user IDs.
     */
    public function resolve(array $roleIds, ?string $workspaceId = null): array;
}
```

### 8.2 When You Need a Custom Resolver

You only need a custom `ApproverResolver` when your User model does **not** have a direct workspace column (e.g., `company_id`). For example, when the user→workspace relationship goes through an intermediary model (`User → Employee → Company`).

### 8.3 Implementing a Custom ApproverResolver

```php
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverResolver;

class AppApproverResolver implements ApproverResolver
{
    public function resolve(array $roleIds, ?string $workspaceId = null): array
    {
        // $roleIds: int = already-resolved user ID (pass through);
        //           string = role name to resolve within workspace.
        // Return a flat int[] of user IDs.
    }
}
```

### 8.4 Registration

Publish the config and set `ui-library.approvals.approver_resolver` to your class. Or bind in a service provider that runs after `UILibraryServiceProvider`.

### 8.5 Reference

See [20-reference-workspace-scoped-approver-resolver.md](20-reference-workspace-scoped-approver-resolver.md) for the full default implementation, customization points, and testing guidance.

---

## 9. ApproverLabelResolver — User Display

### 9.1 Implementing ApproverLabelResolver

```php
use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;

class AppApproverLabelResolver implements ApproverLabelResolver
{
    public function resolveLabel(int $userId): array
    {
        $user = User::find($userId);
        return [
            'name'   => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }
}
```

### 9.2 Registration

Publish the config and set `ui-library.approvals.approver_label_resolver` to your class.

---

## 10. Engines at a Glance

| Engine | Contract | Key Methods | Config Keys |
|--------|----------|-------------|-------------|
| `WorkflowEngine` | `Workflowable` | `start()`, `approve()`, `reject()`, `recall()` | `ui-library.workflows.definitions` |
| `DocumentEngine` | `Documentable` | `upload()`, `generatePdf()`, `generateExcel()`, `getDocuments()`, `delete()` | `ui-library.documents` |
| `NotificationService` | `Notifiable` | `dispatch()`, `getUnread()`, `registerChannel()` | `ui-library.notifications` |
| `ReportEngine` | `Reportable` | (resolves Reportable → generates Document → notifies recipients) | `ui-library.reports` |
| `WorkflowEngine` | `ApproverResolver` | (resolves role assignees → user IDs) | `ui-library.approvals.approver_resolver` |
| `WorkflowEngine` | `ApproverLabelResolver` | (resolves user ID → display name/avatar) | `ui-library.approvals.approver_label_resolver` |
| `NavigationManager` | `CompanyProvider` | `getCompanies()`, `getCurrentCompanyId()` | `ui-library.navigation.company_provider` |
| `WorkspaceFilter` | `WorkspaceResolver` | `resolve()` → context array | `ui-library.navigation.workspace_resolver` |

---

## Cross-References

- [../library/08-contracts-and-interfaces.md](../library/08-contracts-and-interfaces.md) — Full contract method signatures
- [../library/09-engines-and-services.md](../library/09-engines-and-services.md) — Engine internals & architecture
- [module-structure.md](module-structure.md) — Workflow definitions, permissions, notification templates
- [multi-tenancy.md](multi-tenancy.md) — CompanyProvider & WorkspaceResolver in depth
- [20-reference-workspace-scoped-approver-resolver.md](20-reference-workspace-scoped-approver-resolver.md) — Reference implementation