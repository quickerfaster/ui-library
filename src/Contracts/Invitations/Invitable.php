<?php

namespace QuickerFaster\UILibrary\Contracts\Invitations;

interface Invitable
{
    /**
     * Get the type key for polymorphic linking (e.g., 'employee', 'contact').
     */
    public function getInvitableType(): string;

    /**
     * Get the unique identifier for this invitable entity.
     */
    public function getInvitableId(): int|string;
}