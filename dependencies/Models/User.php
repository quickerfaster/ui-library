<?php

namespace App\Models;

use App\Modules\Admin\Models\User as BaseUser;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Onboard\Concerns\GetsOnboarded;
use Spatie\Onboard\Concerns\Onboardable;
use QuickerFaster\UILibrary\Traits\HasSettings;

class User extends BaseUser implements MustVerifyEmail, Onboardable
{
    use HasApiTokens, GetsOnboarded, HasSettings; 

    /**
     * Override $fillable to add application-specific fields.
     * Merge with base $fillable to avoid losing base fields.
     */
    protected $fillable = [
        'name',        // from base
        'email',       // from base
        'status',      // from base
        'password',    // from base
        'email_verified_at',
        'has_seen_tour',
    ];



    /**
     * Override $casts to add application-specific casts.
     * Again, merge with base casts.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',   // base uses 'datetime' for email_verified_at only
    ];

}