<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Tour Configuration
    |--------------------------------------------------------------------------
    */
    'enabled' => true,
    'steps' => [
        [
            'element' => '#module-switcher',
            'title' => 'Module Switcher',
            'content' => 'Switch between different modules of the application.',
            'placement' => 'bottom',
        ],
        [
            'element' => '#sidebar',
            'title' => 'Navigation Sidebar',
            'content' => 'Access all features within the current module.',
            'placement' => 'right',
        ],
    ],
];
