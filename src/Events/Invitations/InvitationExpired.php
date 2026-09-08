<?php

namespace QuickerFaster\UILibrary\Events\Invitations;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Models\Invitation;

/**
 * Fired when an invitation expires (via the scheduled expire command).
 */
class InvitationExpired
{
    use Dispatchable, SerializesModels;

    /**
     * @param Invitation $invitation The invitation that expired.
     */
    public function __construct(
        public Invitation $invitation,
    ) {}
}