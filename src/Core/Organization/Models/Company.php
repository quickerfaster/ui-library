<?php

namespace QuickerFaster\UILibrary\Core\Organization\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'code',
    ];

    protected $casts = [];
}