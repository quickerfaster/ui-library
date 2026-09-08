<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'email',
        'token',
        'status',
        'role',
        'invitable_type',
        'invitable_id',
        'message',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * The invitable entity (polymorphic).
     */
    public function invitable()
    {
        return $this->morphTo();
    }

    /**
     * The user who created this invitation.
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * The Spatie Role assigned to this invitation.
     */
    public function roleRelation()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}