<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User; // adjust if your User model is elsewhere

class SavedFilter extends Model
{
    protected $table = 'saved_filters';

    protected $fillable = [
        'user_id',
        'config_key',
        'name',
        'filters',
        'is_global',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_global' => 'boolean',
    ];

    /**
     * Get the user who owns this saved filter.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include filters for a given config key.
     */
    public function scopeForConfig($query, string $configKey)
    {
        return $query->where('config_key', $configKey);
    }

    /**
     * Scope a query to only include global filters or user's own filters.
     */
    public function scopeAccessibleBy($query, ?int $userId = null)
    {
        if (!$userId) {
            $userId = auth()->id();
        }
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_global', true);
        });
    }
}