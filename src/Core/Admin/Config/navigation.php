<?php

return [
    'context_groups' => [
        'users' => [
            'label' => 'User Management',
            'icon' => 'fa-users',
            'route' => 'admin.users',
            'order' => 10,
        ],
        'access' => [
            'label' => 'Access Control',
            'icon' => 'fa-shield-haltered',
            'route' => 'admin.roles',
            'order' => 20,
        ],
    ],

    'contexts' => [
        'users' => [
            [
                'label' => 'All Users',
                'route' => 'admin.users',
                'icon' => 'fa-user',
                'order' => 10,
            ],
        ],
        'access' => [
            [
                'label' => 'Roles',
                'route' => 'admin.roles',
                'icon' => 'fa-user-tag',
                'order' => 10,
            ],
            [
                'label' => 'Permissions',
                'route' => 'admin.permissions',
                'icon' => 'fa-key',
                'order' => 20,
            ],
        ],
    ],

    'shared_items' => [
        'header' => [],
        'footer' => [],
    ],

    'shared_top_items' => [
        'left' => [],
        'right' => [],
    ],

    'layout' => [
        'top_bar' => ['enabled' => true],
        'context_menu' => ['type' => 'sidebar', 'position' => 'left', 'allow_switch' => false],
        'sidebar' => ['initial_state' => 'full'],
        'bottom_bar' => ['enabled' => true],
    ],
];
