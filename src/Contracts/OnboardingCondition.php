<?php

namespace QuickerFaster\UILibrary\Contracts;

interface OnboardingCondition
{
    /**
     * Determine if the step is complete for the given user.
     */
    public function __invoke($user): bool;
}