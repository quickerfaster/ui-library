<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;

/**
 * Asynchronously dispatch a single notification to a single notifiable.
 */
class SendNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Notifiable $notifiable,
        public string $type,
        public array $data = [],
    ) {
        $connection = config('ui-library.notifications.queue_connection');

        if ($connection) {
            $this->onConnection($connection);
        }
    }

    public function handle(NotificationService $service): void
    {
        $service->dispatch($this->notifiable, $this->type, $this->data);
    }
}
