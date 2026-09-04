<?php

/**
 * System Module — Quick Actions Configuration
 *
 * Registers system-level actions for the command palette.
 * These actions appear when the user presses Cmd+K / Ctrl+K.
 */
return [
    'quick_actions' => [
        [
            'id'          => 'system.view_dashboard',
            'label'       => 'View System Dashboard',
            'description' => 'Go to the system dashboard overview',
            'icon'        => 'fas fa-tachometer-alt',
            'action'      => 'navigate',
            'module'      => 'system',
            'keywords'    => ['dashboard', 'home', 'overview', 'system'],
            'category'    => 'Dashboard',
            'route'       => 'system.dashboard',
            'roles'       => ['super_admin'],
        ],
        [
            'id'          => 'system.manage_settings',
            'label'       => 'Manage Settings',
            'description' => 'Configure global system settings and preferences',
            'icon'        => 'fas fa-cogs',
            'action'      => 'navigate',
            'module'      => 'system',
            'keywords'    => ['settings', 'configuration', 'system', 'preferences', 'general'],
            'category'    => 'Settings',
            'route'       => 'system.settings',
            'roles'       => ['super_admin'],
        ],
        [
            'id'          => 'system.setup_overview',
            'label'       => 'Setup Overview',
            'description' => 'View the system setup and onboarding dashboard',
            'icon'        => 'fas fa-magic',
            'action'      => 'navigate',
            'module'      => 'system',
            'keywords'    => ['setup', 'wizard', 'onboarding', 'configuration', 'getting started'],
            'category'    => 'Setup',
            'route'       => '/system/dashboard-setup-overview',
            'roles'       => ['super_admin'],
        ],
        [
            'id'          => 'system.notifications',
            'label'       => 'Notifications',
            'description' => 'View system notifications and alerts',
            'icon'        => 'fas fa-bell',
            'action'      => 'navigate',
            'module'      => 'system',
            'keywords'    => ['notifications', 'alerts', 'messages', 'inbox'],
            'category'    => 'Notifications',
            'route'       => '/system/notifications',
            'roles'       => ['super_admin', 'company_admin'],
        ],
        [
            'id'          => 'system.my_account',
            'label'       => 'My Account',
            'description' => 'Manage your account settings and profile',
            'icon'        => 'fas fa-user-circle',
            'action'      => 'navigate',
            'module'      => 'system',
            'keywords'    => ['account', 'profile', 'user', 'my', 'personal'],
            'category'    => 'Account',
            'route'       => '/system/accounts',
            'roles'       => ['super_admin', 'company_admin'],
        ],
    ],
];