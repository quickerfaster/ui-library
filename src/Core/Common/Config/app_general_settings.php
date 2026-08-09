<?php

return [
    /*
    |--------------------------------------------------------------------------
    | General Settings Configuration
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'general' => [
            'label' => 'General',
            'icon' => 'fa-cog',
            'fields' => [
                'app_name' => [
                    'label' => 'Application Name',
                    'type' => 'text',
                    'default' => env('APP_NAME', 'QuickerFaster'),
                ],
                'date_format' => [
                    'label' => 'Date Format',
                    'type' => 'select',
                    'options' => [
                        'Y-m-d' => 'YYYY-MM-DD',
                        'd/m/Y' => 'DD/MM/YYYY',
                        'm/d/Y' => 'MM/DD/YYYY',
                    ],
                    'default' => 'Y-m-d',
                ],
                'timezone' => [
                    'label' => 'Timezone',
                    'type' => 'select',
                    'options' => [
                        'UTC' => 'UTC',
                        'Africa/Lagos' => 'Africa/Lagos',
                        'America/New_York' => 'America/New_York',
                        'Europe/London' => 'Europe/London',
                    ],
                    'default' => env('APP_TIMEZONE', 'UTC'),
                ],
            ],
        ],
    ],
];
