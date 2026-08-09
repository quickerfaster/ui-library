<?php

namespace QuickerFaster\UILibrary\Events\Notifications;

use Illuminate\Foundation\Events\Dispatchable;
use QuickerFaster\UILibrary\Models\Notification;

class NotificationDispatched
{
    use Dispatchable;

    public function __construct(public readonly Notification $notification) {}
}