<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['preferable_type', 'preferable_id', 'type', 'channel', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];
    protected $table = 'notification_preferences';

    public function preferable() { return $this->morphTo(); }
}