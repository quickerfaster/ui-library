<?php

namespace QuickerFaster\UILibrary\Tests\Fixtures;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Traits\HasNotifications;

/**
 * Minimal Eloquent User model for tests.
 *
 * Implements both Authenticatable (so Auth::login() works) and Notifiable
 * (so the WorkflowEngine's resolveNotifiable() returns a valid recipient).
 * Uses the 'users' table created by WithLaravelMigrations.
 */
class User extends Model implements AuthenticatableContract, Notifiable
{
    use Authenticatable;
    use HasNotifications;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}