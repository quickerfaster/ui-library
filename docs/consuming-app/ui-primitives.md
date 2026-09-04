# UI Primitives

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

This document covers the library-provided UI components available to consuming applications. For the complete component catalog and internal architecture, see [../library/07-component-catalog.md](../library/07-component-catalog.md).

---

## 1. Overview

The library provides pre-built UI components registered as Livewire components (with the `qf.` prefix) and Blade components (with the `x-qf::` prefix). These components are available in any view within your application.

### Livewire Component Aliases

All library Livewire components use the `qf.{kebab-case}` alias:

```blade
<livewire:qf.data-table config-key="billing.invoice" />
```

### Blade Component Tags

Blade components use the `<x-qf::{kebab-case}>` tag:

```blade
<x-qf::text-field name="amount" label="Amount" />
```

---

## 2. DataTable

### 2.1 Basic Usage

```blade
<livewire:qf.data-table config-key="billing.invoice" />
```

The `config-key` follows the dot-notation convention: `{lowercase_module}.{filename}`.

### 2.2 Configuration

The component resolves its configuration from the config key. See [data-configs.md](data-configs.md) for the full schema.

### 2.3 Customization

Override the library's DataTable by extending it:

```php
// app/Modules/Billing/Http/Livewire/DataTables/InvoiceTable.php
namespace App\Modules\Billing\Http\Livewire\DataTables;

use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable;

class InvoiceTable extends DataTable
{
    protected function getCustomActions(): array
    {
        return array_merge(parent::getCustomActions(), [
            'mark_paid' => 'Mark as Paid',
        ]);
    }
}
```

Register in `AppServiceProvider`:

```php
Livewire::component('billing.invoice-table', InvoiceTable::class);
```

---

## 3. DataTableForm

### 3.1 Inline Forms

The DataTableForm is rendered inline within the DataTable for create/edit operations. It uses the same config file as DataTable.

### 3.2 Modal Forms

```blade
<livewire:qf.form-modal>
    <livewire:qf.data-table-form config-key="billing.invoice" />
</livewire:qf.form-modal>
```

### 3.3 Drawer Forms

```blade
<livewire:qf.drawer>
    <livewire:qf.data-table-form config-key="billing.invoice" />
</livewire:qf.drawer>
```

### Decoupled Drawer Integration

The Drawer listens for `openDrawer`/`closeDrawer`/`formSaved` events, so a button can open a form in the drawer without wrapping it in `<livewire:qf.drawer>`:

```blade
wire:click="$dispatch('openDrawer', {
    component: 'qf.data-table-form',
    params: { configKey: 'hr.employee_profile', recordId: {{ $id }}, inline: true },
    title: 'Edit Record'
})"
```

The Drawer itself is already included in the layout (`<livewire:qf.drawer />`), so only the button dispatch is needed.

### Parent Refresh After Drawer Save

`DataTableForm` dispatches `formSaved` after a save, and the Drawer auto-closes on `formSaved`. Parents should listen for `formSaved` to refresh their data:

```php
protected $listeners = [
    'formSaved' => 'refreshData',
];
```

### Self-Service Edit Suppression

Use the `@if($this->canEdit())` guard pattern to hide edit buttons in self-service mode, where a user should view but not modify a record:

```blade
@if($this->canEdit())
    <button wire:click="$dispatch('openDrawer', { ... })">Edit</button>
@endif
```

#### Drawer Close Behavior

When the Drawer closes (via Save, Discard Changes, or the × button):

- `Drawer::close()` dispatches `drawerClosed`, which triggers Bootstrap's offcanvas slide-out animation
- Content remains visible during the slide-out animation
- After the animation completes, `drawerHidden` triggers `cleanup()` to clear drawer content
- Inline forms (`inline: true`) do NOT redirect after save — the Drawer simply closes
- The success alert (`showAlert`) is suppressed for inline forms to avoid orphaned toasts
- The "Discard Changes" button dispatches `closeDrawer` to close the Drawer without navigating

---

## 4. Approval UI

### 4.1 ApprovalPanel (Combined Component)

The `qf.approval-panel` component combines actions + timeline into a single cohesive unit with three display modes:

```blade
{{-- Banner mode: colored alert with actions + drawer-accessible timeline --}}
<livewire:qf.approval-panel :workflow="$workflow" displayMode="banner" />

{{-- Card mode: full card wrapper with header --}}
<livewire:qf.approval-panel :workflow="$workflow" displayMode="card" />

{{-- Inline mode: flat row of buttons (default) --}}
<livewire:qf.approval-panel :workflow="$workflow" displayMode="inline" />
```

### 4.2 ApprovalActions Component

The `qf.approval-actions` component renders approve/reject/recall buttons for a workflow step. Supports `displayMode`:

```blade
{{-- Inline mode (default): flat d-flex row of buttons --}}
<livewire:qf.approval-actions :workflow="$workflow" />

{{-- Banner mode: colored alert banner with icon, message, and buttons --}}
<livewire:qf.approval-actions :workflow="$workflow" displayMode="banner" />

{{-- Card mode: full card wrapper with header and body --}}
<livewire:qf.approval-actions :workflow="$workflow" displayMode="card" />
```

In banner mode, buttons use `btn-light` with `text-success` (Approve), `text-danger` (Reject), and `text-dark` (Recall) for proper contrast on colored alert backgrounds. The Recall button opens a comment modal (`openCommentModal('recall')`) matching the Approve/Reject pattern.

### 4.3 ApprovalHistoryTimeline

The `qf.approval-history-timeline` component renders a visual timeline of all actions in a workflow. Supports `displayMode`:

```blade
{{-- Full mode (default): complete list-group with all details --}}
<livewire:qf.approval-history-timeline :workflow="$workflow" />

{{-- Compact mode: condensed list without comments, smaller avatars --}}
<livewire:qf.approval-history-timeline :workflow="$workflow" displayMode="compact" />

{{-- Steps-only mode: horizontal step progress indicator --}}
<livewire:qf.approval-history-timeline :workflow="$workflow" displayMode="steps-only" />
```

### 4.4 ApprovalRequestListView

Display pending and submitted approval requests with a status filter and reactive page title:

```blade
<livewire:qf.approval-request-list-view />

{{-- Filtered to a specific workflow definition --}}
<livewire:qf.approval-request-list-view definition-key="payroll_run" />
```

The status filter includes an "All statuses" option and the page title updates reactively based on the selected status (e.g., "Pending Approvals" vs "All Approval Requests").

---

## 5. Wizards

### 5.1 Wizard Component Usage

```blade
<livewire:qf.wizard config-key="billing.invoice_creation" />
```

### 5.2 Wizard Config Schema

See [data-configs.md](data-configs.md) §"Wizard Config Schema" for the full schema.

### 5.3 Example: WorkflowDefinitionWizard

The library ships a `WorkflowDefinitionWizard` for defining workflow definitions through a UI. See [../library/22-workflow-definition-wizard-ux.md](../library/22-workflow-definition-wizard-ux.md).

---

## 6. Widgets

### 6.1 Available Widget Types

| Type | Alias | Purpose |
|------|-------|---------|
| Stat | `stat` | Single stat card (count, sum, avg) |
| Chart | `chart` | Chart (bar, line, pie, doughnut) |
| List | `list` | Simple list |
| Metric | `metric` | Single metric display |
| Trend | `trend` | Trend indicator (up/down) |
| Progress | `progress` | Progress bar |
| Action Card | `action_card` | Action card with CTA |
| Activity Log | `activity_log` | Activity log feed |

### 6.2 Widget Configuration

Widgets are configured in dashboard configs:

```php
'widgets' => [
    [
        'type'  => 'stat',
        'title' => 'Revenue This Month',
        'color' => 'primary',
        'model' => 'App\\Modules\\Billing\\Models\\Invoice',
        'aggregate' => ['function' => 'sum', 'column' => 'amount'],
    ],
    [
        'type'       => 'chart',
        'title'      => 'Revenue Trend',
        'chart_type' => 'line',
        'color'      => 'info',
    ],
],
```

### 6.3 Creating Custom Widgets

To add a custom widget type to the library itself, see [../library/11-extension-guide.md](../library/11-extension-guide.md) §"Recipe: Add a New Widget Type".

---

## 7. Navigation Components

### 7.1 NavigationLayout

The main application shell:

```blade
<livewire:qf.navigation-layout>
    {{ $slot }}
</livewire:qf.navigation-layout>
```

### 7.2 TopNav, Sidebar, BottomBar

These are composed automatically by `NavigationLayout`. The top nav includes the module switcher and company switcher. The sidebar renders context-grouped navigation items. The bottom bar provides mobile navigation.

### 7.3 WorkspaceTabs

Tab-based navigation for workspace contexts:

```blade
<livewire:qf.workspace-tabs />
```

See [../components/workspace-tabs.md](../components/workspace-tabs.md) for full documentation.

### 7.4 Breadcrumbs

Auto-generated breadcrumb navigation:

```blade
<x-qf::breadcrumb />
```

See [../components/breadcrumbs.md](../components/breadcrumbs.md) for full documentation.

### 7.5 Sidebar Filter

Search/filter for sidebar navigation items:

```blade
<livewire:qf.sidebar-filter />
```

See [../components/sidebar-filter.md](../components/sidebar-filter.md) for full documentation.

---

## 8. Common Patterns

### 8.1 Layout Wrapping

```blade
{{-- resources/views/layouts/app.blade.php --}}
<livewire:qf.navigation-layout>
    @yield('content')
</livewire:qf.navigation-layout>
```

### 8.2 Permission-Based Visibility

Use Laravel's `@can` directive to conditionally show components:

```blade
@can('view_invoice')
    <livewire:qf.data-table config-key="billing.invoice" />
@endcan
```

Navigation items are automatically filtered by permissions via the `NavigationFilter` trait.

### 8.3 Event Dispatching Between Components

Livewire components communicate via events:

```blade
<!-- Dispatch from DataTableForm -->
<livewire:qf.data-table-form config-key="billing.invoice"
    @record-saved="$refresh" />

<!-- Listen in a parent component -->
<livewire:qf.data-table config-key="billing.invoice"
    @record-saved.window="$refresh" />
```

---

## Cross-References

- [../library/07-component-catalog.md](../library/07-component-catalog.md) — Complete component catalog
- [../library/11-extension-guide.md](../library/11-extension-guide.md) — Extending library components
- [data-configs.md](data-configs.md) — DataTable/Form/Detail config schemas
- [contracts.md](contracts.md) — Workflowable & workflow engine usage
- [../components/](../components/) — Component API reference (WorkspaceTabs, Breadcrumbs, Sidebar Filter)