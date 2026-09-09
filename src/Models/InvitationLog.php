<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationLog extends Model
{
    protected $fillable = [
        'invitation_id',
        'action',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The invitation this log entry belongs to.
     */
    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * The user who performed the action.
     */
    public function performer()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'performed_by');
    }
}