<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = ['type', 'channel', 'subject', 'body_template', 'locale'];
    protected $table = 'notification_templates';
}