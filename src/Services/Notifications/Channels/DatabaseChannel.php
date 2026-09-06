<?php

namespace QuickerFaster\UILibrary\Services\Notifications\Channels;

use QuickerFaster\UILibrary\Contracts\Notifications\NotificationChannel;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\Notification;

class DatabaseChannel implements NotificationChannel
{
    public function send(Notification $notification, Notifiable $notifiable, array $data): bool
    {
        return true; // Already persisted — no-op for database channel
    }

    public function getName(): string
    {
        return 'database';
    }
}