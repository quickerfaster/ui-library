<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    public $timestamps = false;

    protected $table = 'user_sessions';

    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);
        return $this->belongsTo($userModel);
    }
}