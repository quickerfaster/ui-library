<?php

namespace QuickerFaster\UILibrary\Events\Invitations;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Models\Invitation;

/**
 * Fired when an invitation is created and the email has been dispatched.
 */
class InvitationSent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Invitation $invitation The invitation that was sent.
     */
    public function __construct(
        public Invitation $invitation,
    ) {}
}