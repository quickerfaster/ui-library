<?php

namespace QuickerFaster\UILibrary\Listeners;

use QuickerFaster\UILibrary\Events\Notifications\NotificationDispatched;
use Illuminate\Support\Facades\Log;

class NotificationEventSubscriber
{
    public function handleDispatched(NotificationDispatched $event): void
    {
        Log::info('Notification dispatched', [
            'id' => $event->notification->id,
            'type' => $event->notification->type,
            'channel' => $event->notification->channel,
            'status' => $event->notification->status,
        ]);
    }

    public function subscribe($events): void
    {
        $events->listen(NotificationDispatched::class, [self::class, 'handleDispatched']);
    }
}