<?php

namespace QuickerFaster\UILibrary\Services\Notifications\Channels;

use QuickerFaster\UILibrary\Contracts\Notifications\NotificationChannel;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\Notification;

/**
 * Real-time broadcast channel.
 *
 * Actual socket delivery is handled by the NotificationDispatched event
 * (which implements ShouldBroadcast), so this channel is a no-op success
 * marker within the channel pipeline.
 */
class BroadcastChannel implements NotificationChannel
{
    public function send(Notification $notification, Notifiable $notifiable, array $data): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'broadcast';
    }
}
