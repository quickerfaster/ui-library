<?php
// app/Models/Export.php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
        'download_token',
        'expires_at',
        'error_message',
        'completed_at',

        'file_size',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'options' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }



    public function generateDownloadUrl(): string
    {
        // Token expires in 1 hour (configurable)
        $this->update([
            'download_token' => Str::random(64),
            'expires_at' => now()->addHour(),
        ]);

        return route('export.download', ['token' => $this->download_token]);
    }

    public function isValid(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }



}
