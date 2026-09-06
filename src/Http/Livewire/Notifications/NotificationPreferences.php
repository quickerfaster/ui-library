<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Notifications;

use Livewire\Component;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\NotificationPreference;
use QuickerFaster\UILibrary\Models\NotificationTemplate;

/**
 * Renders a per-type × per-channel notification preference matrix.
 *
 * Rows are the notification types found in notification_templates; columns
 * are the configured notification channels. Checked cells represent enabled
 * delivery. Explicit rows are only stored when the user deviates from the
 * default (enabled) state, keeping the preference table minimal.
 */
class NotificationPreferences extends Component
{
    /** @var array<int, string> */
    public array $types = [];

    /** @var array<int, string> */
    public array $channels = [];

    /** @var array<string, array<string, bool>> */
    public array $preferences = [];

    public function mount(): void
    {
        $this->types = NotificationTemplate::query()
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values()
            ->all();

        $this->channels = array_keys(config('ui-library.notifications.channels', []));

        $this->loadPreferences();
    }

    /**
     * Load the current user's explicit preference rows.
     */
    protected function loadPreferences(): void
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            $this->preferences = [];

            return;
        }

        $rows = NotificationPreference::query()
            ->where('preferable_type', $user->getNotifiableType())
            ->where('preferable_id', $user->getNotifiableId())
            ->get();

        $this->preferences = [];

        foreach ($rows as $row) {
            $this->preferences[$row->type][$row->channel] = (bool) $row->enabled;
        }
    }

    /**
     * The default state of a preference cell is "enabled".
     */
    public function isEnabled(string $type, string $channel): bool
    {
        return $this->preferences[$type][$channel] ?? true;
    }

    /**
     * Toggle a single cell, creating/updating/deleting the backing row.
     */
    public function toggle(string $type, string $channel): void
    {
        $enabled = ! $this->isEnabled($type, $channel);

        $this->persist($type, $channel, $enabled);
    }

    /**
     * Persist a preference cell for the current user.
     */
    protected function persist(string $type, string $channel, bool $enabled): void
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            return;
        }

        $attributes = [
            'preferable_type' => $user->getNotifiableType(),
            'preferable_id' => $user->getNotifiableId(),
            'type' => $type,
            'channel' => $channel,
        ];

        $existing = NotificationPreference::query()->where($attributes)->first();

        if ($enabled) {
            // Enabled is the default — remove any explicit deviation.
            $existing?->delete();
            $this->preferences[$type][$channel] = true;
        } else {
            NotificationPreference::query()->updateOrCreate($attributes, ['enabled' => false]);
            $this->preferences[$type][$channel] = false;
        }
    }

    public function render()
    {
        return view('qf::livewire.notifications.notification-preferences');
    }
}
