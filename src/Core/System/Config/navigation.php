<?php

return [
    'context_groups' => [
        'settings' => [
            'label' => 'System Settings',
            'icon' => 'fas fa-cog',
            'order' => 10,
            'route' => NULL,
            'url' => 'system/dashboard-settings-overview',
        ],
        'setup' => [
            'label' => 'Setup',
            'icon' => 'fas fa-magic',
            'order' => 20,
            'route' => NULL,
            'url' => 'system/dashboard-setup-overview',
        ],
    ],

    'contexts' => [
        'settings' => [
            [
                'key' => 'settings_overview',
                'label' => 'Overview',
                'icon' => 'fas fa-chart-bar',
                'route' => '/system/dashboard-settings-overview',
                'permission' => 'view_settings_overview',
                'order' => 1,
                'page_title' => NULL,
            ],
            [
                'key' => 'system_setting',
                'label' => 'General Settings',
                'route' => '/system/settings',
                'icon' => 'fas fa-sliders-h',
                'permission' => 'view_system_setting',
                'order' => 10,
                'page_title' => NULL,
            ],
        ],
        'setup' => [
            [
                'key' => 'setup_overview',
                'label' => 'Overview',
                'icon' => 'fas fa-chart-bar',
                'route' => '/system/dashboard-setup-overview',
                'permission' => 'view_setup_overview',
                'order' => 1,
                'page_title' => NULL,
            ],
            [
                'key' => 'setup_wizard',
                'label' => 'Setup Wizard',
                'route' => '/setup/wizard',
                'icon' => 'fas fa-magic',
                'permission' => 'view_setup_wizard',
                'order' => 20,
                'page_title' => NULL,
            ],
        ],
    ],

    'sidebar' => [
        'section_label' => null,
        'collapsible' => true,
        'expanded_default' => true,
    ],

    'layout' => [
        'top_bar' => [
            'enabled' => true,
        ],
        'context_menu' => [
            'type' => 'sidebar',
            'position' => 'left',
            'allow_switch' => true,
            'default_type' => 'sidebar',

            // Phase 1 — overflow
            'max_visible_items' => 6,
            'promote_active_item' => true,

            // Phase 2 — Cross-Context Dropdowns
            'show_all_contexts' => false,
            'hide_topnav_contexts' => false,
        ],
        'sidebar' => [
            'initial_state' => 'full',
        ],
        'bottom_bar' => [
            'enabled' => true,
        ],
        'breadcrumb' => [
            'enabled' => true,
        ],
        'title' => [
            'enabled' => true,
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
];
