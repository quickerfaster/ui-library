<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Socialite Configuration
    |--------------------------------------------------------------------------
    */
    'socialite' => [
        // Master switch for social login
        'enabled' => env('UI_LIBRARY_SOCIALITE_ENABLED', false),

        // List of enabled providers (must match driver names in Socialite)
        'providers' => [
            'google' => [
                'enabled'       => env('UI_LIBRARY_SOCIALITE_GOOGLE', false),
                'client_id'     => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect'      => url('/auth/google/callback'), // env('GOOGLE_REDIRECT_URI'),
            ],
        ],
    ],
];


