<?php

namespace QuickerFaster\UILibrary\Contracts\Approvals;

interface ApproverLabelResolver
{
    /**
     * Get the display label for a user.
     */
    public function label($userId): string;

    /**
     * Get the avatar URL for a user.
     */
    public function avatar($userId): ?string;

    /**
     * Get the route to the user's profile, or null.
     */
    public function profileRoute($userId): ?string;
}
