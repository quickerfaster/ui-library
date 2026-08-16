<?php

namespace QuickerFaster\UILibrary\Services\Notifications;

use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Contracts\Notifications\NotificationChannel;
use QuickerFaster\UILibrary\Events\Notifications\NotificationDispatched;
use QuickerFaster\UILibrary\Models\Notification;
use QuickerFaster\UILibrary\Models\NotificationTemplate;
use QuickerFaster\UILibrary\Models\NotificationPreference;
use QuickerFaster\UILibrary\Models\NotificationLog;
use QuickerFaster\UILibrary\Jobs\SendNotification;
use Illuminate\Support\Str;

class NotificationService
{
    protected array $channels = [];

    public function registerChannel(string $name, NotificationChannel $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Dispatch a notification to a notifiable entity.
     */
    public function dispatch(Notifiable $notifiable, string $type, array $data = []): array
    {
        $results = [];
        $channels = $this->resolveChannels($notifiable, $type);

        foreach ($channels as $channelName) {
            $channel = $this->channels[$channelName] ?? null;
            if (!$channel) continue;

            $template = $this->resolveTemplate($type, $channelName);
            $subject = $this->renderTemplate($template?->subject ?? $type, $data);
            $body = $this->renderTemplate($template?->body_template ?? '', $data);

            $notification = Notification::create([
                'notifiable_type' => $notifiable->getNotifiableType(),
                'notifiable_id' => $notifiable->getNotifiableId(),
                'type' => $type,
                'channel' => $channelName,
                'subject' => $subject,
                'body' => $body,
                'data' => $data,
                'status' => 'pending',
            ]);

            $success = $channel->send($notification, $notifiable, $data);

            $notification->update(['status' => $success ? 'sent' : 'failed']);

            event(new NotificationDispatched($notification));

            NotificationLog::create([
                'notifiable_type' => $notifiable->getNotifiableType(),
                'notifiable_id' => $notifiable->getNotifiableId(),
                'notification_id' => $notification->id,
                'type' => $type,
                'channel' => $channelName,
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $success ? null : 'Channel delivery failed',
            ]);

            $results[$channelName] = $success;
        }

        return $results;
    }

    /**
     * Queue a notification for asynchronous delivery to a single notifiable.
     */
    public function dispatchAsync(Notifiable $notifiable, string $type, array $data = []): void
    {
        SendNotification::dispatch($notifiable, $type, $data);
    }

    /**
     * Get unread notifications for a notifiable.
     */
    public function getUnread(Notifiable $notifiable, int $limit = 20)
    {
        return Notification::where('notifiable_type', $notifiable->getNotifiableType())
            ->where('notifiable_id', $notifiable->getNotifiableId())
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Resolve which channels to use based on preferences and config.
     */
    protected function resolveChannels(Notifiable $notifiable, string $type): array
    {
        $defaultChannels = config('ui-library.notifications.default_channels', ['database']);
        $preferences = NotificationPreference::where('preferable_type', $notifiable->getNotifiableType())
            ->where('preferable_id', $notifiable->getNotifiableId())
            ->where('type', $type)
            ->pluck('enabled', 'channel')
            ->toArray();

        return array_filter($defaultChannels, function ($channel) use ($preferences) {
            return $preferences[$channel] ?? true;
        });
    }

    /**
     * Resolve a template for the given type and channel.
     */
    protected function resolveTemplate(string $type, string $channel): ?NotificationTemplate
    {
        return NotificationTemplate::where('type', $type)
            ->where('channel', $channel)
            ->first();
    }

    /**
     * Placeholder replacement in templates with dot-notation support.
     *
     * Supports both simple keys ({name}) and dot-notation ({user.name},
     * {order.total}) using Laravel's data_get() helper to resolve nested
     * array values.
     */
    protected function renderTemplate(string $template, array $data): string
    {
        // Match {placeholder} patterns, including dot-notation keys.
        return preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_.]*)\}/', function ($matches) use ($data) {
            $key = $matches[1];
            $value = data_get($data, $key);

            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                return (string) $value;
            }

            // Leave the placeholder unchanged if the value is not renderable.
            return $matches[0];
        }, $template);
    }
}