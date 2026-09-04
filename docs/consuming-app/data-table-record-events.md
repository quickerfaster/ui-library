# DataTable Record Events

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

This document covers the `DataTableRecordSaved` event and `DataTableRecordListener` abstract base class. For the library's event listener auto-discovery internals, see [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md).

---

## 1. Overview

The library dispatches a `DataTableRecordSaved` event whenever a record is created, updated, deleted, or restored through the DataTable/DataTableForm components. Consuming applications can react to these events by creating listeners that extend the `DataTableRecordListener` abstract base class.

---

## 2. DataTableRecordSaved Event

### 2.1 Event Payload

The `DataTableRecordSaved` event carries the following properties:

| Property | Type | Purpose |
|----------|------|---------|
| `model` | `Model` | The Eloquent model instance that was saved |
| `action` | `string` | The action performed: `created`, `updated`, `deleted`, `restored` |
| `original` | `array` | The original attributes before the change (for `updated` and `deleted`) |

### 2.2 When the Event Fires

The event is dispatched after the DataTable or DataTableForm component completes a create, update, delete, or restore operation. It fires after the database transaction is committed.

---

## 3. DataTableRecordListener

### 3.1 Abstract Base Class

The `DataTableRecordListener` abstract base class dispatches the `DataTableRecordSaved` event to four hook methods:

```php
namespace QuickerFaster\UILibrary\Listeners;

use QuickerFaster\UILibrary\Events\DataTableRecordSaved;

abstract class DataTableRecordListener
{
    public function handle(DataTableRecordSaved $event): void
    {
        match ($event->action) {
            'created'  => $this->handleCreated($event),
            'updated'  => $this->handleUpdated($event),
            'deleted'  => $this->handleDeleted($event),
            'restored' => $this->handleRestored($event),
            default    => null,
        };
    }

    protected function handleCreated(DataTableRecordSaved $event): void {}
    protected function handleUpdated(DataTableRecordSaved $event): void {}
    protected function handleDeleted(DataTableRecordSaved $event): void {}
    protected function handleRestored(DataTableRecordSaved $event): void {}
}
```

### 3.2 Creating a Listener

**Step 1**: Create a listener class in your module's `Listeners/` directory:

```php
// app/Modules/Billing/Listeners/InvoiceSavedListener.php
namespace App\Modules\Billing\Listeners;

use QuickerFaster\UILibrary\Listeners\DataTableRecordListener;
use QuickerFaster\UILibrary\Events\DataTableRecordSaved;

class InvoiceSavedListener extends DataTableRecordListener
{
    protected function handleCreated(DataTableRecordSaved $event): void
    {
        if ($event->model !== \App\Modules\Billing\Models\Invoice::class) {
            return;
        }

        // Dispatch a notification when an invoice is created
        \QuickerFaster\UILibrary\Services\Notifications\NotificationService::dispatch(
            $event->model->creator,
            'invoice_created',
            ['number' => $event->model->invoice_number]
        );
    }

    protected function handleUpdated(DataTableRecordSaved $event): void
    {
        if ($event->model !== \App\Modules\Billing\Models\Invoice::class) {
            return;
        }

        // Log status changes
        if ($event->model->status !== ($event->original['status'] ?? null)) {
            activity()
                ->performedOn($event->model)
                ->withProperties([
                    'old_status' => $event->original['status'] ?? null,
                    'new_status' => $event->model->status,
                ])
                ->log('invoice_status_changed');
        }
    }

    protected function handleDeleted(DataTableRecordSaved $event): void
    {
        // Cleanup related data
    }
}
```

**Step 2**: That's it. The listener is auto-discovered by the `DiscoveryRegistrar` — no manual service provider registration is needed.

### 3.3 Auto-Discovery

The library scans `app/Modules/{Module}/Listeners/` for classes with a `handle()` method and auto-registers them with the Laravel event dispatcher. The `handle()` method's first parameter type-hint determines which event the listener subscribes to.

See [module-structure.md](module-structure.md) §"Auto-Discovery Conventions & Opt-Outs" for the full discovery contract.

---

## 4. Common Use Cases

### 4.1 Audit Logging

```php
protected function handleUpdated(DataTableRecordSaved $event): void
{
    activity()
        ->performedOn($event->model)
        ->withProperties([
            'changes' => $event->model->getChanges(),
            'original' => $event->original,
        ])
        ->log('record_updated');
}
```

### 4.2 Triggering Workflows

```php
protected function handleCreated(DataTableRecordSaved $event): void
{
    if ($event->model instanceof Workflowable) {
        app(WorkflowEngine::class)->start($event->model);
    }
}
```

### 4.3 Sending Notifications

```php
protected function handleCreated(DataTableRecordSaved $event): void
{
    NotificationService::dispatch(
        $event->model->assigned_to,
        'record_assigned',
        ['title' => $event->model->title]
    );
}
```

### 4.4 Cache Invalidation

```php
protected function handleUpdated(DataTableRecordSaved $event): void
{
    Cache::tags(['dashboard', 'reports'])->flush();
}
```

---

## 5. Testing Record Events

### 5.1 Asserting Events Are Dispatched

```php
use QuickerFaster\UILibrary\Events\DataTableRecordSaved;
use Illuminate\Support\Facades\Event;

public function test_event_is_dispatched_on_create(): void
{
    Event::fake();

    $invoice = Invoice::factory()->create();

    Event::assertDispatched(DataTableRecordSaved::class, function ($event) use ($invoice) {
        return $event->model->is($invoice)
            && $event->action === 'created';
    });
}
```

### 5.2 Testing Listener Behavior

```php
public function test_listener_sends_notification_on_create(): void
{
    NotificationService::fake();

    $invoice = Invoice::factory()->create();

    $listener = new InvoiceSavedListener();
    $listener->handle(new DataTableRecordSaved($invoice, 'created', []));

    NotificationService::assertDispatched('invoice_created');
}
```

---

## Client-Side `formSaved` Event

The server-side `DataTableRecordSaved` event (dispatched through the Laravel event dispatcher) is distinct from the client-side `formSaved` Livewire event. `formSaved` is dispatched by `DataTableForm` after a save and is used to refresh a parent Livewire component after a drawer/modal save — see [ui-primitives.md](ui-primitives.md) §"Parent Refresh After Drawer Save".

---

## Cross-References

- [module-structure.md](module-structure.md) §"Auto-Discovery Conventions & Opt-Outs" — Listener auto-discovery
- [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md) — DiscoveryRegistrar internals
- [contracts.md](contracts.md) — Workflowable contract for triggering workflows
- [permissions-and-notifications.md](permissions-and-notifications.md) — Notification dispatch