<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $table = 'page_views';

    protected $fillable = [
        'user_id',
        'company_id',
        'route',
        'method',
        'referrer',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);
        return $this->belongsTo($userModel);
    }
}