<?php

namespace QuickerFaster\UILibrary\Events\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Models\Notification;

class NotificationDispatched implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Notification $notification) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('notifiable.' . $this->notification->notifiable_id);
    }

    public function broadcastAs(): string
    {
        return 'notification.dispatched';
    }
}