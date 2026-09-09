<?php

namespace QuickerFaster\UILibrary\Conditions\Onboarding;

use QuickerFaster\UILibrary\Contracts\OnboardingCondition;
use QuickerFaster\UILibrary\Models\UserActionHistory;

/**
 * Checks if the user has explored the dashboard at least once.
 *
 * A user is considered to have explored the dashboard when they have
 * at least one recorded quick action in their action history.
 */
class DashboardExplored implements OnboardingCondition
{
    public function __invoke($user): bool
    {
        return UserActionHistory::where('user_id', $user->id)->exists();
    }
}