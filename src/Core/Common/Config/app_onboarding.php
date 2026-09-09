<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Onboarding Configuration
    |--------------------------------------------------------------------------
    |
    | Default onboarding steps that apply to all users regardless of which
    | business modules are installed. Each step defines a condition class
    | (implementing OnboardingCondition) that determines when the step is
    | complete.
    |
    | Steps are executed in array order. The first incomplete step is
    | presented to the user after they accept their invitation.
    |
    | Consuming apps can override these steps by publishing the config:
    |   php artisan vendor:publish --tag=ui-library-config
    |
    */
    'steps' => [
        [
            'title' => 'Complete Your Profile',
            'link' => '/my-profile',
            'cta' => 'Update Profile',
            'model' => null,
            'condition' => \QuickerFaster\UILibrary\Conditions\Onboarding\UserProfileComplete::class,
        ],
        [
            'title' => 'Explore the Dashboard',
            'link' => '/home',
            'cta' => 'View Dashboard',
            'model' => null,
            'condition' => \QuickerFaster\UILibrary\Conditions\Onboarding\DashboardExplored::class,
        ],
    ],
];
