<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Notifications;

use Livewire\Component;
use Livewire\WithPagination;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\Notification;
use QuickerFaster\UILibrary\Models\NotificationTemplate;
use QuickerFaster\UILibrary\Services\Notifications\NotificationTypeRegistry;

/**
 * Lists every notification for the currently authenticated user.
 *
 * Supports filtering by notification type and read/unread status, plus
 * pagination via Livewire's WithPagination trait.
 */
class NotificationsIndex extends Component
{
    use WithPagination;

    public string $typeFilter = '';

    public string $readFilter = 'all';

    protected $queryString = [
        'typeFilter' => ['except' => ''],
        'readFilter' => ['except' => 'all'],
    ];

    /**
     * The paginated notifications for the current user.
     */
    public function getNotificationsProperty()
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        $query = Notification::query()
            ->where('notifiable_type', $user->getNotifiableType())
            ->where('notifiable_id', $user->getNotifiableId());

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->readFilter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($this->readFilter === 'unread') {
            $query->whereNull('read_at');
        }

        return $query->orderByDesc('created_at')->paginate(15);
    }

    /**
     * Available filter options derived from persisted templates + the registry.
     *
     * @return array<int, string>
     */
    public function getTypeOptionsProperty(): array
    {
        $templateTypes = NotificationTemplate::query()
            ->distinct()
            ->pluck('type')
            ->filter()
            ->all();

        return array_values(array_unique(array_merge(
            NotificationTypeRegistry::types(),
            $templateTypes,
        )));
    }

    /**
     * Mark a single notification as read (scoped to the current user).
     */
    public function markAsRead(int $notificationId): void
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            return;
        }

        Notification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', $user->getNotifiableType())
            ->where('notifiable_id', $user->getNotifiableId())
            ->first()
            ?->markAsRead();
    }

    /**
     * Navigate to the URL associated with a notification.
     *
     * Marks the notification as read (if not already) and dispatches
     * a Livewire navigate event to the URL stored in the notification's
     * data payload.
     */
    public function navigateToNotification(int $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if (! $notification) {
            return;
        }

        // Mark as read
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        // Navigate to the URL stored in data, if present
        $url = $notification->data['url'] ?? null;

        if ($url) {
            $this->redirect($url);
        }
    }

    /**
     * Mark every unread notification as read (scoped to the current user).
     */
    public function markAllAsRead(): void
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            return;
        }

        Notification::query()
            ->where('notifiable_type', $user->getNotifiableType())
            ->where('notifiable_id', $user->getNotifiableId())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedReadFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Handle an inline action button click for a notification.
     *
     * Loads the notification, resolves the handler from the
     * NotificationActionRegistry, calls handle(), and optionally
     * marks the notification as read.
     */
    public function handleAction(int $notificationId, string $handler, array $data = []): void
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            return;
        }

        $notification = Notification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', $user->getNotifiableType())
            ->where('notifiable_id', $user->getNotifiableId())
            ->first();

        if (! $notification) {
            return;
        }

        $registry = app(\QuickerFaster\UILibrary\Services\Notifications\NotificationActionRegistry::class);
        $registry->handle($handler, $notification, $data);

        // Optionally mark as read after handling the action.
        $notification->markAsRead();
    }

    public function render()
    {
        return view('qf::livewire.notifications.notifications-index');
    }
}
