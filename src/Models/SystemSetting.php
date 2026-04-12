<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{

    protected $fillable = ['settable_type', 'settable_id', 'key', 'value', 'group', 'is_public'];

    protected $table = 'system_settings';
    protected $guarded = [];

    protected $casts = [
        'value' => 'json',
        'is_public' => 'boolean',
    ];




    public function settingable()
    {
        return $this->morphTo();
    }



}