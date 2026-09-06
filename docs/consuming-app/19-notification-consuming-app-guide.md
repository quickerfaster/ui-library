# QuickerFaster UI Library — Notification System: Consuming-App Guide

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Status**: Guidance (2026-08-16) — consuming-app integration guide for the notification engine
> **Scope**: What the library ships vs. what the consuming app must build for throttling/scheduling, audience segmentation, inline actions, and template variables — plus how to test each one.

**Related files**: [`09-engines-and-services.md`](../library/09-engines-and-services.md) (Notification Engine §14) · [`08-contracts-and-interfaces.md`](../library/08-contracts-and-interfaces.md) · [`18-workflow-approval-testing-checklist.md`](./18-workflow-approval-testing-checklist.md)

---

## 1. Purpose

The notification engine is **dispatch-only** and **per-recipient**. It deliberately leaves four concerns to the consuming app because they are business/operational decisions the library cannot make generically:

1. 🟢 **Throttling & scheduling** — how fast and when notifications leave the system.
2. 🟢 **Audience segmentation** — *who* should receive a notification.
3. 🟡 **Inline actions** — what buttons appear and what they do.
4. 🟡 **Template variables** — what placeholders exist and how templates are authored.

This document maps each concern to (a) the library primitive that supports it, and (b) the code the consuming app must write.

> ⚠️ **Accuracy note (2026-08-16)**: This guide was written against the current source. Two seams are flagged inline where the library primitive does not yet fully wire a concern end-to-end: [`dispatch()`](../../src/Services/Notifications/NotificationService.php:32) does not accept an `actions` argument (§3), and [`renderTemplate()`](../../src/Services/Notifications/NotificationService.php:133) is `protected` (§4).

---

## 2. Library Primitives (what the library already provides)

| Primitive | Location | Role |
|-----------|----------|------|
| `NotificationService::dispatch()` | [`NotificationService.php`](../../src/Services/Notifications/NotificationService.php:32) | Synchronous, per-recipient, multi-channel delivery |
| `NotificationService::dispatchAsync()` | [`NotificationService.php`](../../src/Services/Notifications/NotificationService.php:81) | Queues a single `SendNotification` job |
| `SendNotification` job (`ShouldQueue`) | [`SendNotification.php`](../../src/Jobs/SendNotification.php:16) | Async delivery unit; honors `queue_connection` config |
| `NotificationAction` contract | [`NotificationAction.php`](../../src/Contracts/Notifications/NotificationAction.php:7) | `handle(Notification $notification, array $data): void` |
| `NotificationActionRegistry` | [`NotificationActionRegistry.php`](../../src/Services/Notifications/NotificationActionRegistry.php:9) | Singleton; `register()`, `get()`, `handle()` |
| `TemplateVariableRegistry` contract | [`TemplateVariableRegistry.php`](../../src/Contracts/Notifications/TemplateVariableRegistry.php:5) | `variables(string $type): array` |
| `DefaultTemplateVariableRegistry` | [`DefaultTemplateVariableRegistry.php`](../../src/Services/Notifications/DefaultTemplateVariableRegistry.php:7) | Config-overridable default placeholder map |
| `Notification` model (`actions` JSON) | [`Notification.php`](../../src/Models/Notification.php:7) | `data` and `actions` cast to array; `markAsRead()` |
| Button rendering | [`notifications-index.blade.php`](../../src/Resources/views/livewire/notifications/notifications-index.blade.php:57), [`top-nav.blade.php`](../../src/Resources/views/livewire/navs/top-nav.blade.php:404) | Reads `$notification->actions` and renders buttons |
| `handleAction()` | [`NotificationsIndex.php`](../../src/Http/Livewire/Notifications/NotificationsIndex.php:132), [`TopNav.php`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:313) | Resolves handler from the registry and invokes it |

**Config keys** ([`ui-library.php`](../../src/Config/ui-library.php:649)):

| Key | Default | Meaning |
|-----|---------|---------|
| `notifications.default_channels` | `['database', 'mail']` | Channels resolved per dispatch (unless a preference disables one) |
| `notifications.queue_connection` | `null` (`UI_LIBRARY_NOTIFICATION_QUEUE`) | Queue connection used by `SendNotification` |
| `notifications.queue` | `false` (`UI_LIBRARY_NOTIFICATION_ASYNC`) | Future async-by-default toggle (informational) |
| `notifications.channels` | `database`, `mail`, `broadcast` | Channel FQCN registry bound in the service provider |

---

## 3. 🟢 Throttling & Scheduling

### 3.1 What the library provides

The async primitive is [`NotificationService::dispatchAsync()`](../../src/Services/Notifications/NotificationService.php:81), which wraps one [`SendNotification`](../../src/Jobs/SendNotification.php:16) job. The job implements `ShouldQueue` and, if `ui-library.notifications.queue_connection` is set, binds itself to that connection:

```php
// Library internals (shown for reference only — do not edit).
public function dispatchAsync(Notifiable $notifiable, string $type, array $data = []): void
{
    SendNotification::dispatch($notifiable, $type, $data);
}
```

The library does **not** throttle, rate-limit, or schedule. Those are consuming-app concerns because the correct policy is always application-specific.

### 3.2 What the consuming app must build

**Throttling** — pick one (or combine):

1. **Laravel's native `Redis::throttle`** in the code path that *enqueues* notifications.
2. **Queue worker limits** via `--max-jobs` / `--rate` in the process that *consumes* jobs.

**Scheduling** — pick one:

1. **Delayed dispatch** using the job's `delay()` method.
2. **A scheduled command** that computes and enqueues due notifications on a cron.

### 3.3 Example: throttling with `Redis::throttle`

```php
namespace App\Notifications\Throttling;

use Illuminate\Support\Facades\Redis;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;
use RuntimeException;

class ThrottledNotifier
{
    public function __construct(private NotificationService $service)
    {
    }

    /**
     * Enqueue one notification per recipient, throttled to 10 per minute
     * per recipient so a burst cannot flood their inbox.
     */
    public function enqueue(Notifiable $notifiable, string $type, array $data = []): void
    {
        $key = 'notifications:throttle:' . $notifiable->getNotifiableType()
             . ':' . $notifiable->getNotifiableId();

        Redis::throttle($key)
            ->allow(10)->every(60)
            ->then(
                fn () => $this->service->dispatchAsync($notifiable, $type, $data),
                fn () => throw new RuntimeException('Notification rate limit exceeded.')
            );
    }
}
```

> Use the **`cache`** driver instead of Redis by calling `Illuminate\Support\Facades\Cache::throttle($key)` if the app does not use Redis.

### 3.4 Example: queue worker limits

Run a dedicated notification queue with hard limits so an entire audience broadcast cannot consume all workers at once:

```bash
# Limit this worker to 20 jobs per run and 2 jobs/second.
php artisan queue:work redis --queue=notifications --max-jobs=20 --rate=2

# Daemonized variant with retry and sleep controls.
php artisan queue:work redis --queue=notifications --sleep=3 --tries=3 --max-jobs=50 --rate=5
```

Point [`SendNotification`](../../src/Jobs/SendNotification.php) at that queue by setting the env var so the job lands on `notifications`:

```
UI_LIBRARY_NOTIFICATION_QUEUE=notifications
```

### 3.5 Example: scheduling with delayed dispatch

```php
use QuickerFaster\UILibrary\Jobs\SendNotification;

// Send a reminder 30 minutes from now.
SendNotification::dispatch($user, 'payroll_reminder', ['run_id' => $run->id])
    ->delay(now()->addMinutes(30));

// Send on the default connection but on a specific named queue.
SendNotification::dispatch($user, 'payroll_reminder', ['run_id' => $run->id])
    ->onQueue('notifications')
    ->delay(now()->addMinutes(30));
```

### 3.6 Example: scheduling with a scheduled command

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

class SendPendingPayrollReminders extends Command
{
    protected $signature = 'payroll:send-reminders';

    public function handle(NotificationService $service): int
    {
        // Resolve recipients (see §4) and enqueue asynchronously.
        $this->line('Queued reminders for ' . $count . ' employees.');

        return self::SUCCESS;
    }
}
```

```php
// In App\Console\Kernel::schedule()
$schedule->command('payroll:send-reminders')->dailyAt('09:00');
```

---

## 4. 🟢 Audience Segmentation

### 4.1 What the library provides

[`NotificationService::dispatch()`](../../src/Services/Notifications/NotificationService.php:32) and [`dispatchAsync()`](../../src/Services/Notifications/NotificationService.php:81) both accept a **single** [`Notifiable`](../../src/Contracts/Notifications/Notifiable.php) recipient. The library intentionally has no "send to a group" API — segmenting an audience is the consuming app's responsibility.

### 4.2 What the consuming app must build

1. Query the target audience with business rules.
2. Loop the collection and call `dispatchAsync()` per recipient (async is recommended for anything beyond a handful of users).

### 4.3 Example: churn-risk audience broadcast

```php
namespace App\Notifications\Campaigns;

use App\Models\User;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

class InactiveUserCampaign
{
    public function __construct(private NotificationService $service)
    {
    }

    public function run(): int
    {
        // 1. Segment: users inactive for 30+ days.
        $audience = User::query()
            ->where('status', 'active')
            ->where('last_login_at', '<', now()->subDays(30))
            ->get();

        // 2. Loop and enqueue per recipient.
        foreach ($audience as $user) {
            $this->service->dispatchAsync($user, 'reengagement', [
                'user_name'    => $user->name,
                'last_login_at' => $user->last_login_at?->toDateString(),
            ]);
        }

        return $audience->count();
    }
}
```

### 4.4 Example: role- or company-scoped segmentation

```php
$audience = User::query()
    ->where('company_id', $companyId)
    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['manager', 'payroll_admin']))
    ->get();

foreach ($audience as $user) {
    $this->service->dispatchAsync($user, 'payroll_review_due', [
        'run_id'  => $run->id,
        'due_at'  => $run->due_at?->toDateTimeString(),
    ]);
}
```

### 4.5 Large audiences

For very large lists, chunk the query and dispatch a batch per chunk to avoid holding the whole result set in memory:

```php
User::query()
    ->where('status', 'active')
    ->chunkById(500, function ($users) {
        foreach ($users as $user) {
            $this->service->dispatchAsync($user, 'monthly_digest', []);
        }
    });
```

---

## 5. 🟡 Consuming-App Parts of Inline Actions

### 5.1 What the library provides

1. **`NotificationAction` contract** — a single method [`handle(Notification $notification, array $data): void`](../../src/Contracts/Notifications/NotificationAction.php:12).
2. **`NotificationActionRegistry`** — a singleton ([`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php:81)) with `register()`, `get()`, and `handle()`.
3. **`actions` JSON column** on the [`notifications`](../../Database/Migrations/2026_08_16_000001_add_actions_to_notifications_table.php) table, cast to array on [`Notification`](../../src/Models/Notification.php:16).
4. **Button rendering** — [`notifications-index.blade.php`](../../src/Resources/views/livewire/notifications/notifications-index.blade.php:57) and [`top-nav.blade.php`](../../src/Resources/views/livewire/navs/top-nav.blade.php:404) iterate `$notification->actions` and render a button per entry.
5. **`handleAction()`** — [`NotificationsIndex::handleAction()`](../../src/Http/Livewire/Notifications/NotificationsIndex.php:132) and [`TopNav::handleAction()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:313) load the notification, resolve the handler, invoke it, and mark the notification read.

Each `actions` entry is a plain array with these keys:

| Key | Required | Purpose |
|-----|----------|---------|
| `label` | ✅ | Button text (e.g., `Approve`) |
| `handler` | ✅ | Registry key resolved by `NotificationActionRegistry` |
| `style` | ❌ | Bootstrap button style: `primary`, `success`, `danger`, `warning`, `info`, `secondary`, `dark`, `light` |
| `data` | ❌ | Associative array passed to `handle()` |

### 5.2 What the consuming app must build

1. Implement `NotificationAction` for each business action (`Approve`, `Deny`, `View Invoice`, …).
2. Register each implementation with the registry.
3. Populate the `actions` JSON on the notification record.

### 5.3 Example: implementing a handler

```php
namespace App\Notifications\Actions;

use QuickerFaster\UILibrary\Contracts\Notifications\NotificationAction;
use QuickerFaster\UILibrary\Models\Notification;

class ApproveLeaveRequest implements NotificationAction
{
    public function handle(Notification $notification, array $data): void
    {
        $leaveRequest = LeaveRequest::findOrFail($data['leave_request_id'] ?? null);

        $leaveRequest->update(['status' => 'approved']);

        // Optional: dispatch a follow-up notification or log the action.
    }
}
```

```php
namespace App\Notifications\Actions;

use QuickerFaster\UILibrary\Contracts\Notifications\NotificationAction;
use QuickerFaster\UILibrary\Models\Notification;

class DenyLeaveRequest implements NotificationAction
{
    public function handle(Notification $notification, array $data): void
    {
        $leaveRequest = LeaveRequest::findOrFail($data['leave_request_id'] ?? null);

        $leaveRequest->update(['status' => 'denied']);
    }
}
```

### 5.4 Example: registering handlers

Register in a service provider so the singleton is populated at boot time:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use QuickerFaster\UILibrary\Services\Notifications\NotificationActionRegistry;
use App\Notifications\Actions\ApproveLeaveRequest;
use App\Notifications\Actions\DenyLeaveRequest;

class NotificationActionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving(NotificationActionRegistry::class, function (NotificationActionRegistry $registry) {
            $registry->register('leave.approve', new ApproveLeaveRequest());
            $registry->register('leave.deny', new DenyLeaveRequest());
        });
    }
}
```

> `register()` is an **instance** method on the singleton, not a static call. Resolve the registry with `app(NotificationActionRegistry::class)` (or use `resolving()` as above) rather than calling `NotificationActionRegistry::register(...)`.

### 5.5 Example: populating the `actions` JSON

The `actions` column is separate from `data`. Because [`NotificationService::dispatch()`](../../src/Services/Notifications/NotificationService.php:32) currently persists `$data` into the `data` column only (it does not accept an `$actions` argument), action-bearing notifications are created **directly on the model** today:

```php
use QuickerFaster\UILibrary\Models\Notification;

Notification::create([
    'notifiable_type' => $user->getNotifiableType(),
    'notifiable_id'   => $user->getNotifiableId(),
    'type'            => 'leave_request',
    'channel'         => 'database',
    'subject'         => 'Leave request awaiting approval',
    'body'            => 'Employee X requested 3 days off.',
    'data'            => ['leave_request_id' => 123],
    'actions'         => [
        [
            'label'   => 'Approve',
            'handler' => 'leave.approve',
            'style'   => 'success',
            'data'    => ['leave_request_id' => 123],
        ],
        [
            'label'   => 'Deny',
            'handler' => 'leave.deny',
            'style'   => 'danger',
            'data'    => ['leave_request_id' => 123],
        ],
    ],
]);
```

The buttons then render automatically from `$notification->actions` in the notification bell and the notifications index, and clicking one invokes the matching handler via [`handleAction()`](../../src/Http/Livewire/Notifications/NotificationsIndex.php:132).

> **Library seam**: A natural follow-up is to add an `array $actions = []` parameter to [`dispatch()`](../../src/Services/Notifications/NotificationService.php:32) / [`dispatchAsync()`](../../src/Services/Notifications/NotificationService.php:81) (and [`SendNotification`](../../src/Jobs/SendNotification.php:23)) so action-bearing notifications can use the same service method instead of direct model creation. Tracked in §8.

---

## 6. 🟡 Consuming-App Parts of Template Variables

### 6.1 What the library provides

1. **`TemplateVariableRegistry` contract** — [`variables(string $type): array`](../../src/Contracts/Notifications/TemplateVariableRegistry.php:15) returns `[placeholder => label]` pairs for a notification type.
2. **`DefaultTemplateVariableRegistry`** — bound to the contract in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php:85); checks `config('ui-library.notifications.template_variables.{type}')` first, then built-in defaults.
3. **Dot-notation placeholder rendering** — [`NotificationService::renderTemplate()`](../../src/Services/Notifications/NotificationService.php:133) resolves `{placeholder}` (including `{user.name}`, `{order.total}`) via `data_get()` **during** `dispatch()`.

> [`renderTemplate()`](../../src/Services/Notifications/NotificationService.php:133) is `protected`, not public API. Consuming apps do not call it directly — they supply `$data` to `dispatch()`/`dispatchAsync()` and the engine renders placeholders internally. For a preview (see §6.4), the consuming app needs its own small renderer.

### 6.2 What the consuming app must build

1. Register available placeholders per notification type (via config overrides or a custom `TemplateVariableRegistry` binding).
   > ✅ **Implemented (2026-09-01)**: The reference consuming app now ships `NotificationVariableRegistry` with 19 notification types. See the consuming app's `app/Services/NotificationVariableRegistry.php` for the canonical implementation.
2. Build a template CRUD UI (rich text editor) if the app wants non-developer template editing.
3. Provide a preview that renders a template against sample data.

### 6.3 Example: registering placeholders via config

Add to `config/ui-library.php` (or merge from a service provider):

```php
'notifications' => [
    'template_variables' => [
        'leave_request' => [
            'employee_name'    => 'Employee Name',
            'leave_type'       => 'Leave Type',
            'start_date'       => 'Start Date',
            'end_date'         => 'End Date',
            'approver.name'    => 'Approver Name',
        ],
    ],
],
```

The default [`DefaultTemplateVariableRegistry`](../../src/Services/Notifications/DefaultTemplateVariableRegistry.php:49) reads these overrides automatically. Alternatively, bind a custom implementation:

```php
$this->app->bind(
    \QuickerFaster\UILibrary\Contracts\Notifications\TemplateVariableRegistry::class,
    \App\Notifications\AppTemplateVariableRegistry::class
);
```

```php
namespace App\Notifications;

use QuickerFaster\UILibrary\Contracts\Notifications\TemplateVariableRegistry;

class AppTemplateVariableRegistry implements TemplateVariableRegistry
{
    public function variables(string $type): array
    {
        return match ($type) {
            'leave_request' => [
                'employee_name' => 'Employee Name',
                'leave_type'    => 'Leave Type',
                'start_date'    => 'Start Date',
                'end_date'      => 'End Date',
                'approver.name' => 'Approver Name',
            ],
            default => [],
        };
    }
}
```

### 6.4 Example: template CRUD + preview

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use QuickerFaster\UILibrary\Contracts\Notifications\TemplateVariableRegistry;
use QuickerFaster\UILibrary\Models\NotificationTemplate;

class NotificationTemplateController
{
    public function preview(Request $request, TemplateVariableRegistry $registry)
    {
        $type       = $request->input('type');
        $template   = $request->input('template');   // raw {placeholder} text
        $sample     = $request->input('sample', []); // preview values

        // The library renders placeholders internally during dispatch().
        // For preview, re-implement the same dot-notation substitution.
        $rendered = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_.]*)\}/',
            fn ($m) => (string) (data_get($sample, $m[1]) ?? $m[0]),
            $template
        );

        return response()->json([
            'rendered'    => $rendered,
            'placeholders' => $registry->variables($type),
        ]);
    }
}
```

Persist authored templates into [`NotificationTemplate`](../../src/Models/NotificationTemplate.php) rows (`type`, `channel`, `locale`, `subject`, `body`). [`dispatch()`](../../src/Services/Notifications/NotificationService.php:41) resolves the first template matching `type` + `channel` and renders its `subject`/`body_template`.

> **Schema note**: The engine reads `body_template` on [`NotificationTemplate`](../../src/Services/Notifications/NotificationService.php:43). Ensure any template CRUD writes that column name (not `body`).

---

## 7. Testing Guidance

### 7.1 Verify actual delivery (database rows, mail, broadcast)

```php
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\Notification;
use QuickerFaster\UILibrary\Models\NotificationLog;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

public function test_dispatch_persists_database_row(): void
{
    $user = User::factory()->create();

    $results = app(NotificationService::class)->dispatch($user, 'user_welcome', ['user_name' => 'Ada']);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => $user->getNotifiableType(),
        'notifiable_id'   => $user->getNotifiableId(),
        'type'            => 'user_welcome',
        'status'          => 'sent',
    ]);

    $this->assertDatabaseHas('notification_logs', [
        'type'   => 'user_welcome',
        'status' => 'sent',
    ]);
}
```

For **mail**, use `Mail::fake()` and assert the [`MailChannel`](../../src/Services/Notifications/Channels/MailChannel.php) delivered:

```php
use Illuminate\Support\Facades\Mail;
use QuickerFaster\UILibrary\Services\Notifications\Channels\MailChannel;

public function test_mail_channel_sends(): void
{
    Mail::fake();

    app(NotificationService::class)->dispatch($user, 'user_welcome', ['user_name' => 'Ada']);

    Mail::assertSent(/* expected mailable/raw mail */);
}
```

For **broadcast**, use `Event::fake()` (the [`NotificationDispatched`](../../src/Events/Notifications/NotificationDispatched.php) event fires after every dispatch) and/or assert the `BroadcastChannel` side effects in the broadcasting driver's log.

### 7.2 Test throttling/scheduling

```php
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

public function test_throttle_rejects_eleventh_dispatch(): void
{
    // Seed the limiter or call the notifier 10 times first.
    $this->expectException(\RuntimeException::class);

    // 11th call within the window should throw.
    app(ThrottledNotifier::class)->enqueue($user, 'reengagement', []);
}

public function test_delayed_dispatch_queues_with_delay(): void
{
    Queue::fake();

    SendNotification::dispatch($user, 'payroll_reminder', [])
        ->delay(now()->addMinutes(30));

    Queue::assertPushed(SendNotification::class, function ($job) {
        return $job->delay >= now()->addMinutes(29);
    });
}

public function test_scheduled_command_queues_due_recipients(): void
{
    Queue::fake();

    $this->artisan('payroll:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendNotification::class, 3); // 3 due recipients
}
```

### 7.3 Test audience segmentation

```php
public function test_campaign_targets_only_inactive_users(): void
{
    Queue::fake();

    $inactive = User::factory()->create(['last_login_at' => now()->subDays(45)]);
    $active   = User::factory()->create(['last_login_at' => now()]);

    app(InactiveUserCampaign::class)->run();

    Queue::assertPushed(SendNotification::class, 1);
    Queue::assertPushed(SendNotification::class, function ($job) use ($inactive) {
        return $job->notifiable->getNotifiableId() === $inactive->id;
    });
}
```

### 7.4 Test inline actions

```php
use QuickerFaster\UILibrary\Services\Notifications\NotificationActionRegistry;
use App\Notifications\Actions\ApproveLeaveRequest;

public function test_action_handler_is_registered_and_invoked(): void
{
    $registry = app(NotificationActionRegistry::class);
    $registry->register('leave.approve', new ApproveLeaveRequest());

    $notification = Notification::factory()->create(['type' => 'leave_request']);

    $registry->handle('leave.approve', $notification, ['leave_request_id' => 1]);

    $this->assertDatabaseHas('leave_requests', ['id' => 1, 'status' => 'approved']);
}

public function test_unregistered_handler_throws(): void
{
    $this->expectException(\InvalidArgumentException::class);

    app(NotificationActionRegistry::class)->handle('missing.handler', $notification, []);
}

public function test_actions_column_is_cast_to_array(): void
{
    $notification = Notification::factory()->create([
        'actions' => [
            ['label' => 'Approve', 'handler' => 'leave.approve', 'style' => 'success'],
        ],
    ]);

    $this->assertIsArray($notification->fresh()->actions);
    $this->assertSame('Approve', $notification->fresh()->actions[0]['label']);
}
```

### 7.5 Test template variables

```php
use QuickerFaster\UILibrary\Contracts\Notifications\TemplateVariableRegistry;

public function test_registry_returns_config_overrides(): void
{
    config()->set('ui-library.notifications.template_variables.leave_request', [
        'employee_name' => 'Employee Name',
    ]);

    $vars = app(TemplateVariableRegistry::class)->variables('leave_request');

    $this->assertSame('Employee Name', $vars['employee_name']);
}

public function test_rendering_substitutes_dot_notation(): void
{
    // Use a preview renderer (or call dispatch and assert the persisted body).
    $rendered = $this->preview('Hello {user.name}', ['user' => ['name' => 'Ada']]);

    $this->assertSame('Hello Ada', $rendered);
}

public function test_rendering_leaves_unknown_placeholders_unchanged(): void
{
    $rendered = $this->preview('Hello {missing}', []);

    $this->assertSame('Hello {missing}', $rendered);
}
```

---

## 8. Suggested Follow-Up Items (not yet implemented)

1. **Add an `actions` argument to dispatch** — extend [`dispatch()`](../../src/Services/Notifications/NotificationService.php:32), [`dispatchAsync()`](../../src/Services/Notifications/NotificationService.php:81), and [`SendNotification`](../../src/Jobs/SendNotification.php:23) with `array $actions = []` so action-bearing notifications flow through the service instead of direct model creation (§5.5).
2. **Expose a public render helper** — add a public `renderTemplate()` (or a small dedicated renderer) so consuming-app template previews reuse the exact engine substitution instead of duplicating it (§6.4).
3. **Wire the `queue` toggle** — currently `ui-library.notifications.queue` is informational; consider making `dispatch()` route to `dispatchAsync()` when `queue` is true.
4. **Document `BroadcastChannel` delivery** — verify and document the broadcast payload shape for consuming-app WebSocket/echo integration.

> **Update (2026-09-01)**: The consuming app now has a concrete `NotificationVariableRegistry` implementing the [`TemplateVariableRegistry`](../../src/Contracts/Notifications/TemplateVariableRegistry.php) contract with 19 notification types. This addresses the §6.2 requirement for registering placeholders per notification type. The remaining §8 items are still open.

---

## 9. Catch-All Route Security (Consuming-App Settings)

The library's catch-all route `/{module}/{view}/{id?}` (see [`17-view-config-routing-interplay.md`](../library/17-view-config-routing-interplay.md)) is now hardened with config-driven gates under `ui-library.catch_all`. Consuming apps may want to review and override these when publishing the config:

| Key | Default | Consuming-app consideration |
|-----|---------|-----------------------------|
| `catch_all.allowed_modules` | `['admin', 'system', 'organization', 'common']` | Business modules are appended automatically by [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php); extend only if a custom Core module needs catch-all resolution. |
| `catch_all.require_auth` | `true` | Keep `true` for authenticated-only views; set `false` only when exposing intentionally public views through the catch-all. |
| `catch_all.gate` | `null` | Set a Gate ability name to enforce a single uniform permission for all catch-all views. |
| `catch_all.authorization_callback` | `null` | Primary extension point — supply a callable `($user, $module, $view, $id)` for custom per-view/module authorization. Takes precedence over `gate`. |
| `catch_all.rate_limiting.enabled` | `true` | Disable only if a broader app-level limiter already covers these routes. |
| `catch_all.rate_limiting.max_attempts` | `60` | Tune per expected burst traffic (keyed by user id or IP). |
| `catch_all.rate_limiting.decay_minutes` | `1` | Decay window in minutes. |

The full schema is documented in [`10-settings-and-config.md`](../library/10-settings-and-config.md) under "Catch-All Route Security".

---

**Related files**: [`09-engines-and-services.md`](../library/09-engines-and-services.md) · [`08-contracts-and-interfaces.md`](../library/08-contracts-and-interfaces.md) · [`18-workflow-approval-testing-checklist.md`](./18-workflow-approval-testing-checklist.md) · [`00-index.md`](../README.md) · [`10-settings-and-config.md`](../library/10-settings-and-config.md) · [`17-view-config-routing-interplay.md`](../library/17-view-config-routing-interplay.md)
