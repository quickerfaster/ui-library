<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use QuickerFaster\UILibrary\Traits\HasSettings;

class System extends Model
{
    use HasSettings;

    protected $fillable = ['id'];
    public $timestamps = false;

    // Ensure only one record exists
    protected static function booted()
    {
        static::creating(function ($model) {
            if (static::count() > 0) {
                throw new \Exception('Only one System record allowed');
            }
        });
    }
}