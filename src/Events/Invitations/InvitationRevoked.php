<?php

namespace QuickerFaster\UILibrary\Events\Invitations;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Models\Invitation;

/**
 * Fired when an invitation is revoked by an administrator.
 */
class InvitationRevoked
{
    use Dispatchable, SerializesModels;

    /**
     * @param Invitation $invitation The invitation that was revoked.
     */
    public function __construct(
        public Invitation $invitation,
    ) {}
}