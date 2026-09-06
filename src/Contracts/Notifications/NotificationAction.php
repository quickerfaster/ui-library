<?php

namespace QuickerFaster\UILibrary\Contracts\Notifications;

use QuickerFaster\UILibrary\Models\Notification;

interface NotificationAction
{
    /**
     * Handle the action for the given notification.
     */
    public function handle(Notification $notification, array $data): void;
}