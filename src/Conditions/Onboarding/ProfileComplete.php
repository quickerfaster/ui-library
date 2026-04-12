<?php 


namespace QuickerFaster\UILibrary\Conditions\Onboarding;

use QuickerFaster\UILibrary\Contracts\OnboardingCondition;

class ProfileComplete implements OnboardingCondition
{
    public function __invoke($user): bool
    {
        // Example: user must have filled in phone and address
        return !empty($user->phone) && !empty($user->address);
    }
}