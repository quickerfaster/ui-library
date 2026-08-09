<?php

return [
    'context_groups' => [
        'settings' => [
            'label' => 'System Settings',
            'icon' => 'fa-cog',
            'route' => 'system.settings',
            'order' => 10,
        ],
    ],

    'contexts' => [
        'settings' => [
            [
                'label' => 'General Settings',
                'route' => 'system.settings',
                'icon' => 'fa-sliders-h',
                'order' => 10,
            ],
            [
                'label' => 'Setup Wizard',
                'route' => 'setup.wizard',
                'icon' => 'fa-magic',
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
