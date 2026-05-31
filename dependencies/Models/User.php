<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Onboard\Concerns\GetsOnboarded;
use Spatie\Onboard\Concerns\Onboardable;
use QuickerFaster\UILibrary\Traits\HasSettings;

class User extends Authenticatable implements MustVerifyEmail, Onboardable
{
    use HasApiTokens, Notifiable, HasRoles, GetsOnboarded, HasSettings, SoftDeletes, HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name', 
        'email', 
        'email_verified_at', 
        'password', 
        'status', 
        'has_seen_tour'
    ];

    protected $guard_name = 'web';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relationship with Employee.
     */
    public function employee()
    {
        return $this->hasOne(\App\Modules\Hr\Models\Employee::class, 'user_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \App\Modules\Admin\Database\Factories\UserFactory::new();
    }
}