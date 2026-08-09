<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceDataItem extends Model
{
    protected $fillable = ['type', 'key', 'value', 'meta', 'is_active'];

    protected $casts = [
        'value' => 'json',
        'meta' => 'json',
        'is_active' => 'boolean',
    ];

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}