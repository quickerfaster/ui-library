<?php

namespace App\Modules\Admin\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToggleButtonEvent
{
    use Dispatchable;//, InteractsWithSockets, SerializesModels;

     public $data;

    /**
     * Create a new event instance.
     *
     * @param array $oldRecord
     * @param array $newRecord
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

   
}
