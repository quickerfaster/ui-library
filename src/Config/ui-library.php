<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Registry
    |--------------------------------------------------------------------------
    | Defines all known modules. Core modules are shipped with the package.
    | Business modules register themselves by merging into this array.
    */
    'modules' => [
        'admin' => [
            'enabled' => true,
            'label' => 'Administration',
            'icon' => 'fa-shield-haltered',
            'route' => 'admin.dashboard',
            'order' => 900,
            'roles' => ['super_admin'],
            'core' => true,
        ],
        'system' => [
            'enabled' => true,
            'label' => 'System',
            'icon' => 'fa-cog',
            'route' => 'system.dashboard',
            'order' => 999,
            'roles' => ['super_admin'],
            'core' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Discovery Paths
    |--------------------------------------------------------------------------
    */
    'module_paths' => [
        'core' => null,     // Set by UILibraryServiceProvider at boot
        'business' => base_path('app/Modules'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Home Route
    |--------------------------------------------------------------------------
    */
    'home_route' => env('UI_LIBRARY_HOME_ROUTE', 'admin.dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Socialite Configuration
    |--------------------------------------------------------------------------
    */
    'socialite' => [
        'enabled' => env('UI_LIBRARY_SOCIALITE_ENABLED', false),
        'providers' => [
            'google' => [
                'enabled' => env('UI_LIBRARY_SOCIALITE_GOOGLE', false),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
            ],
            'github' => [
                'enabled' => env('UI_LIBRARY_SOCIALITE_GITHUB', false),
                'client_id' => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Resolution
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'resolvers' => [
            'user' => null,
            'company' => null,
            'system' => null,
        ],
        'cache_ttl' => 3600,
        'default_module' => 'system',
        'context_keys' => ['user_id', 'module'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'top_bar' => [
            'enabled' => true,
            'show_module_switcher' => true,
            'show_company_switcher' => false,
        ],
        'sidebar' => [
            'initial_state' => 'full',
        ],
        'bottom_bar' => [
            'enabled' => true,
        ],
        'company_provider' => \QuickerFaster\UILibrary\Services\Navigation\NullCompanyProvider::class,
        'show_company_switcher' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumb
    |--------------------------------------------------------------------------
    */
    'breadcrumb' => [
        'show_home' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Title
    |--------------------------------------------------------------------------
    */
    'title' => [
        'separator' => ' - ',
        'app_name' => env('APP_NAME', 'QuickerFaster'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Approvals Configuration
    |--------------------------------------------------------------------------
    | Model mappings for the approval engine. Override these in the consuming
    | app's published config to use custom models.
    */
    'approvals' => [
        'models' => [
            'request' => \QuickerFaster\UILibrary\Models\ApprovalRequest::class,
            'tier' => \QuickerFaster\UILibrary\Models\ApprovalTier::class,
            'log' => \QuickerFaster\UILibrary\Models\ApprovalLog::class,
            'tier_approval' => \QuickerFaster\UILibrary\Models\ApprovalTierApproval::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Engine Configuration
    |--------------------------------------------------------------------------
    | Define workflow definitions here. Business modules can merge their own
    | definitions into this array via their service providers.
    */
    'workflows' => [
        'definitions' => [
            // Example: leave_request workflow
            // 'leave_request' => [
            //     'label' => 'Leave Request Approval',
            //     'steps' => [
            //         ['name' => 'Manager Approval', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['manager', 'admin']],
            //         ['name' => 'HR Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['hr', 'super_admin']],
            //     ],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Engine Configuration
    |--------------------------------------------------------------------------
    */
    'documents' => [
        'disk' => env('UI_LIBRARY_DOCUMENT_DISK', 'public'),
        'max_file_size' => env('UI_LIBRARY_MAX_FILE_SIZE', 10240), // KB
        'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Engine Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'default_channels' => ['database', 'mail'],
        'queue_connection' => env('UI_LIBRARY_NOTIFICATION_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Reports Configuration
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'default_frequency' => 'daily',
        'available_frequencies' => ['daily', 'weekly', 'monthly', 'quarterly'],
        'report_types' => [
            // Business modules register their Reportable implementations here
        ],
        'notification_channels' => ['database', 'mail'],
        'queue_connection' => env('UI_LIBRARY_REPORT_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference Data Configuration
    |--------------------------------------------------------------------------
    */
    'reference_data' => [
        'cache_ttl' => 3600,
        'types' => [
            'countries' => ['label' => 'Countries', 'icon' => 'fa-globe'],
            'currencies' => ['label' => 'Currencies', 'icon' => 'fa-money-bill'],
            'languages' => ['label' => 'Languages', 'icon' => 'fa-language'],
            'timezones' => ['label' => 'Timezones', 'icon' => 'fa-clock'],
            'payment_methods' => ['label' => 'Payment Methods', 'icon' => 'fa-credit-card'],
            'document_types' => ['label' => 'Document Types', 'icon' => 'fa-file'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'exports' => true,
        'imports' => true,
        'reports' => true,
        'approvals' => true,
        'onboarding' => true,
        'tour' => true,
    ],
];