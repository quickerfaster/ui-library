<?php

namespace QuickerFaster\UILibrary\Contracts\Notifications;

use QuickerFaster\UILibrary\Models\Notification;

interface NotificationChannel
{
    public function send(Notification $notification, Notifiable $notifiable, array $data): bool;
    public function getName(): string;
}