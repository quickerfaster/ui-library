<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'notifiable_type', 'notifiable_id', 'notification_id',
        'type', 'channel', 'status', 'error_message',
    ];
    protected $table = 'notification_logs';

    public function notifiable() { return $this->morphTo(); }
    public function notification() { return $this->belongsTo(Notification::class); }
}