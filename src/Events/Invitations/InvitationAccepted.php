<?php

namespace QuickerFaster\UILibrary\Events\Invitations;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Models\Invitation;

/**
 * Fired when an invitation is accepted and the user account is created/activated.
 */
class InvitationAccepted
{
    use Dispatchable, SerializesModels;

    /**
     * @param Invitation $invitation The invitation that was accepted.
     */
    public function __construct(
        public Invitation $invitation,
    ) {}
}