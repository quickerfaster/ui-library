<?php

namespace QuickerFaster\UILibrary\Conditions\Onboarding;

use QuickerFaster\UILibrary\Contracts\OnboardingCondition;

/**
 * Checks if the user has completed their basic profile.
 *
 * A profile is considered complete when the user has set their name.
 * This is the first generic onboarding step, distinct from the HR-specific
 * employee profile creation step.
 */
class UserProfileComplete implements OnboardingCondition
{
    public function __invoke($user): bool
    {
        return ! empty($user->name);
    }
}