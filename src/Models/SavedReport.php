<?php 

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;


class SavedReport extends Model
{
    protected $fillable = [
        'user_id', 'config_key', 'name', 'type', 'configuration', 'is_global'
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_global' => 'boolean',
    ];

    
}