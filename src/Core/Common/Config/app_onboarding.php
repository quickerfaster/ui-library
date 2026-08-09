<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Onboarding Configuration
    |--------------------------------------------------------------------------
    */
    'steps' => [
        [
            'title' => 'Complete Your Profile',
            'link' => '/my-profile',
            'cta' => 'Update Profile',
            'model' => null,
            'condition' => null,
        ],
        [
            'title' => 'Explore the Dashboard',
            'link' => '/home',
            'cta' => 'View Dashboard',
            'model' => null,
            'condition' => null,
        ],
    ],
];
