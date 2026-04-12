<?php
// app/Models/Export.php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    protected $fillable = [
        'user_id',
        'config_key',
        'filters',
        'columns',
        'format',
        'options',
        'status',
        'file_path',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'options' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
