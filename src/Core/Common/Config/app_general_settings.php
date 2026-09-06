<?php

return [
    'groups' => [
        'general' => [
            'label' => 'General',
            'icon' => 'fas fa-cog',
            'settings' => [
                [
                    'key' => 'timezone',
                    'type' => 'select',
                    'label' => 'Timezone',
                    'options' => 'timezones', // special resolver
                    'default' => 'UTC',
                ],
                [
                    'key' => 'date_format',
                    'type' => 'select',
                    'label' => 'Date Format',
                    'options' => ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY'],
                    'default' => 'Y-m-d',
                ],
                [
                    'key' => 'currency',
                    'type' => 'select',
                    'label' => 'Currency',
                    'options' => ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'NGN' => 'NGN'],
                    'default' => 'USD',
                ],
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'icon' => 'fas fa-palette',
            'settings' => [
                [
                    'key' => 'theme',
                    'type' => 'select',
                    'label' => 'Theme',
                    'options' => ['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto'],
                    'default' => 'auto',
                ],
            ],
        ],
        'pagination' => [
            'label' => 'Pagination',
            'icon' => 'fas fa-table',
            'settings' => [
                [
                    'key' => 'pagination.per_page',
                    'type' => 'number',
                    'label' => 'Items per page',
                    'default' => 15,
                    'min' => 5,
                    'max' => 100,
                ],
            ],
        ],
    ],
];
