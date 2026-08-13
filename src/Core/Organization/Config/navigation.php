<?php

return [
    'context_groups' => [
        'companies' => [
            'label' => 'Companies',
            'icon' => 'fas fa-building',
            'order' => 10,
            'route' => NULL,
            'url' => 'organization/dashboard-companies-overview',
        ],
        'structure' => [
            'label' => 'Structure',
            'icon' => 'fas fa-sitemap',
            'order' => 20,
            'route' => NULL,
            'url' => 'organization/dashboard-structure-overview',
        ],
        'locations' => [
            'label' => 'Locations',
            'icon' => 'fas fa-map-marker-alt',
            'order' => 30,
            'route' => NULL,
            'url' => 'organization/dashboard-locations-overview',
        ],
        'teams' => [
            'label' => 'Teams',
            'icon' => 'fas fa-users',
            'order' => 40,
            'route' => NULL,
            'url' => 'organization/teams',
        ],
    ],

    'contexts' => [
        'companies' => [
            [
                'key' => 'companies_overview',
                'label' => 'Overview',
                'icon' => 'fas fa-chart-bar',
                'route' => '/organization/dashboard-companies-overview',
                'permission' => 'view_companies_overview',
                'order' => 1,
                'page_title' => NULL,
            ],
            [
                'key' => 'company',
                'label' => 'All Companies',
                'route' => '/organization/companies',
                'icon' => 'fas fa-building',
                'permission' => 'view_company',
                'order' => 10,
                'page_title' => NULL,
            ],
            [
                'key' => 'branch',
                'label' => 'Branches',
                'route' => '/organization/branches',
                'icon' => 'fas fa-code-branch',
                'permission' => 'view_branch',
                'order' => 20,
                'page_title' => NULL,
            ],
            [
                'key' => 'business_unit',
                'label' => 'Business Units',
                'route' => '/organization/business-units',
                'icon' => 'fas fa-briefcase',
                'permission' => 'view_business_unit',
                'order' => 30,
                'page_title' => NULL,
            ],
        ],
        'structure' => [
            [
                'key' => 'structure_overview',
                'label' => 'Overview',
                'icon' => 'fas fa-chart-bar',
                'route' => '/organization/dashboard-structure-overview',
                'permission' => 'view_structure_overview',
                'order' => 1,
                'page_title' => NULL,
            ],
            [
                'key' => 'department',
                'label' => 'Departments',
                'route' => '/organization/departments',
                'icon' => 'fas fa-layer-group',
                'permission' => 'view_department',
                'order' => 10,
                'page_title' => NULL,
            ],
            [
                'key' => 'division',
                'label' => 'Divisions',
                'route' => '/organization/divisions',
                'icon' => 'fas fa-diagram-project',
                'permission' => 'view_division',
                'order' => 20,
                'page_title' => NULL,
            ],
        ],
        'locations' => [
            [
                'key' => 'locations_overview',
                'label' => 'Overview',
                'icon' => 'fas fa-chart-bar',
                'route' => '/organization/dashboard-locations-overview',
                'permission' => 'view_locations_overview',
                'order' => 1,
                'page_title' => NULL,
            ],
            [
                'key' => 'location',
                'label' => 'All Locations',
                'route' => '/organization/locations',
                'icon' => 'fas fa-location-dot',
                'permission' => 'view_location',
                'order' => 10,
                'page_title' => NULL,
            ],
        ],
        'teams' => [
            [
                'key' => 'team',
                'label' => 'All Teams',
                'route' => '/organization/teams',
                'icon' => 'fas fa-people-group',
                'permission' => 'view_team',
                'order' => 10,
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
