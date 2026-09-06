<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'notifiable_type', 'notifiable_id', 'type', 'channel',
        'subject', 'body', 'data', 'actions', 'read_at', 'status',
    ];

    protected $casts = [
        'data' => 'array',
        'actions' => 'array',
        'read_at' => 'datetime',
    ];

    public function notifiable() { return $this->morphTo(); }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isRead(): bool { return $this->read_at !== null; }
}