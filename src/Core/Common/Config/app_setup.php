<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Setup Configuration
    |--------------------------------------------------------------------------
    */
    'steps' => [
        [
            'title' => 'Create Super Admin',
            'description' => 'Set up the primary administrator account',
            'route' => 'admin.users',
            'icon' => 'fa-user-shield',
        ],
        [
            'title' => 'Configure System Settings',
            'description' => 'Set application name, timezone, and defaults',
            'route' => 'system.settings',
            'icon' => 'fa-cog',
        ],
        [
            'title' => 'Define Roles & Permissions',
            'description' => 'Set up access control structure',
            'route' => 'admin.roles',
            'icon' => 'fa-shield-haltered',
        ],
    ],
];
