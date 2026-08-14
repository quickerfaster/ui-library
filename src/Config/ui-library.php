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
            'user_facing' => true,
            'depends_on' => ['organization'],
        ],
        'system' => [
            'enabled' => true,
            'label' => 'System',
            'icon' => 'fa-cog',
            'route' => 'system.dashboard',
            'order' => 999,
            'roles' => ['super_admin'],
            'core' => true,
            'user_facing' => false,
            'depends_on' => [],
        ],
        'organization' => [
            'enabled' => true,
            'label' => 'Organization',
            'icon' => 'fa-sitemap',
            'route' => 'organization.dashboard',
            'order' => 100,
            'roles' => ['*'],
            'core' => true,
            'user_facing' => true,
            'depends_on' => [],
        ],
        'common' => [
            'enabled' => true,
            'label' => 'Common',
            'icon' => 'fa-cubes',
            'route' => null,
            'order' => 50,
            'roles' => ['*'],
            'core' => true,
            'user_facing' => false,
            'depends_on' => [],
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
        'business_namespace' => 'App\\Modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Home Route
    |--------------------------------------------------------------------------
    */
    'home_route' => env('UI_LIBRARY_HOME_ROUTE', 'admin.dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Layout Configuration
    |--------------------------------------------------------------------------
    */
    'layout' => [
        'workspace_tabs' => [
            'enabled' => true,
        ],
    ],

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
    |
    | Controls the top bar, sidebar, and bottom bar behaviour. The sidebar
    | section/item definitions below drive the NavigationManager (Phase 4.5).
    |
    | When 'sidebar.sections' is empty or not published, NavigationManager
    | falls back to smart defaults: it reads the module registry and each
    | module's navigation.php config to build sections automatically.
    |
    | To customise the sidebar, publish this config and define your own
    | sections and items.
    |
    */
    'navigation' => [

        /*
        |------------------------------------------------------------------
        | Open Links In Workspace Tabs
        |------------------------------------------------------------------
        |
        | When true, sidebar navigation items open in a workspace tab via
        | Livewire's openWorkspaceTab event instead of performing a full
        | page navigation. When false, sidebar items retain their normal
        | link behaviour.
        |
        */
        'open_in_tabs' => false,

        'top_bar' => [
            'enabled' => true,
            'show_module_switcher' => true,
            'show_company_switcher' => true,

            /*
            |--------------------------------------------------------------
            | Overflow thresholds
            |--------------------------------------------------------------
            |
            | When the number of top-level items (context groups) exceeds
            | max_desktop (desktop) or max_mobile (mobile), the excess
            | items are moved into a "More" dropdown.
            |
            | The currently active context is always promoted to the
            | visible set, even if it would otherwise be in overflow.
            |
            */
            'max_desktop' => 5,
            'max_mobile'  => 3,
        ],

        /*
        |------------------------------------------------------------------
        | Sidebar Section & Item Definitions (Phase 4.5)
        |------------------------------------------------------------------
        |
        | sections: Top-level sidebar groups. Each section has:
        |   - slug:       Unique key for the section (e.g., 'organization')
        |   - label:      Display label
        |   - icon:       Font Awesome icon class (e.g., 'fa-sitemap')
        |   - order:      Sort order (lower = first)
        |   - gate:       Optional access gate string (see below)
        |   - permission: Optional Spatie permission name
        |   - enabled:    Set false to hide the section entirely
        |   - module:     Shorthand — load all nav items from this module
        |   - items:      Explicit item definitions (overrides 'module')
        |
        | items: Navigation links inside a section. Each item can be:
        |
        |   A) Module reference (loads all nav items from that module):
        |      ['module' => 'organization']
        |
        |   B) Module reference with overrides:
        |      ['module' => 'organization', 'label' => 'Structure', 'icon' => 'fa-sitemap']
        |
        |   C) Custom route:
        |      ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'fa-home', 'order' => 10]
        |
        |   D) Custom URL:
        |      ['label' => 'Help', 'url' => '/help', 'icon' => 'fa-question-circle']
        |
        | Gate strings support these formats:
        |   - 'role:super_admin'       User must have the given Spatie role
        |   - 'permission:view_users'  User must have the given Spatie permission
        |   - 'can:update,App\\Models\\Post'  Laravel Gate::allows() check
        |
        | If no sections are defined, NavigationManager auto-builds sections
        | from the module registry (user_facing modules) and their per-module
        | navigation.php configs — matching the pre-4.5 behaviour exactly.
        |
        */
        'sidebar' => [

            /*
            |--------------------------------------------------------------
            | Sidebar Sections
            |--------------------------------------------------------------
            |
            | Uncomment and customise to take control of sidebar rendering.
            | The defaults below mirror the current auto-built structure.
            |
            */
            'sections' => [
                // [
                //     'slug' => 'organization',
                //     'label' => 'Organization',
                //     'icon' => 'fa-sitemap',
                //     'order' => 100,
                //     'module' => 'organization',
                // ],
                // [
                //     'slug' => 'administration',
                //     'label' => 'Administration',
                //     'icon' => 'fa-shield-haltered',
                //     'order' => 900,
                //     'gate' => 'role:super_admin',
                //     'module' => 'admin',
                // ],
            ],

            /*
            |--------------------------------------------------------------
            | Sidebar Items (per-section)
            |--------------------------------------------------------------
            |
            | When a section uses 'items' instead of 'module', define each
            | link explicitly. Example:
            |
            | 'sections' => [
            |     [
            |         'slug' => 'custom',
            |         'label' => 'Custom Section',
            |         'icon' => 'fa-star',
            |         'order' => 50,
            |         'items' => [
            |             ['label' => 'Dashboard', 'route' => 'home', 'icon' => 'fa-home', 'order' => 10],
            |             ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'fa-chart-bar', 'order' => 20],
            |         ],
            |     ],
            | ],
            |
            */

            /*
            |--------------------------------------------------------------
            | Sidebar Initial State
            |--------------------------------------------------------------
            */
            'initial_state' => 'full',
        ],

        'bottom_bar' => [
            'enabled' => true,
        ],
        'company_provider' => \QuickerFaster\UILibrary\Services\Navigation\DefaultCompanyProvider::class,
        'show_company_switcher' => true,

        /*
        |------------------------------------------------------------------
        | Workspace Configuration
        |------------------------------------------------------------------
        |
        | The workspace context is resolved via the WorkspaceResolver contract.
        | Consuming applications should bind their own implementation that
        | returns the current workspace context (company, role, department,
        | features, etc.)
        |
        | The library ships with a NullWorkspaceResolver that returns an
        | empty context — no workspace filtering is applied by default.
        |
        | Example binding in your AppServiceProvider:
        |
        |   $this->app->singleton(
        |       \QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver::class,
        |       \App\Services\WorkspaceResolver::class
        |   );
        |
        | The resolve() method should return an array such as:
        |
        |   [
        |       'company_id'      => 1,
        |       'role'            => 'payroll_admin',
        |       'department_type' => 'engineering',
        |       'features'        => ['departments', 'time', 'payroll'],
        |   ]
        |
        | Context groups with a `feature` key are filtered against the
        | `features` array. Context items with a `workspace` constraint
        | map are matched against the workspace context key-by-key.
        |
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumb
    |--------------------------------------------------------------------------
    */
    'breadcrumb' => [
        'show_home' => true,
        'max_visible' => 4,
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
    | Activity Logs Configuration
    |--------------------------------------------------------------------------
    | Model resolution for the activity log widget. Override in the consuming
    | app's published config to use a custom ActivityLog model.
    */
    'activity_logs' => [
        'model' => env('UI_LIBRARY_ACTIVITY_LOG_MODEL', null),
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
    | DataTables Configuration
    |--------------------------------------------------------------------------
    | Authorization provider for DataTable view/edit/delete permission checks.
    | Override in the consuming app to use a custom authorization implementation.
    */
    'datatables' => [
        'authorization_provider' => \QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider::class,
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

        /*
        |------------------------------------------------------------------
        | Multi-Company Payroll
        |------------------------------------------------------------------
        | When enabled, super admins in "All Companies" mode can process
        | payroll for all companies at once. Each company's payslips are
        | generated independently within a single PayrollRun.
        |
        | Set in .env: FEATURE_MULTI_COMPANY_PAYROLL=true
        */
        'multi_company_payroll' => env('FEATURE_MULTI_COMPANY_PAYROLL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    | Controls company switcher visibility and access.
    */
    'multitenancy' => [

        /*
        |------------------------------------------------------------------
        | Company Switcher Roles
        |------------------------------------------------------------------
        | Roles that can see the company switcher dropdown in the top nav.
        | Use '*' to allow all authenticated users.
        */
        'switcher_roles' => '*',

        /*
        |------------------------------------------------------------------
        | All Companies Access
        |------------------------------------------------------------------
        | Roles that can select "All Companies" to see data across all
        | companies.
        */
        'all_companies_roles' => '*',

        /*
        |------------------------------------------------------------------
        | Default Company Mode
        |------------------------------------------------------------------
        | When a user has multiple available companies, which one to
        | default to:
        | - 'first'  : the first company in their list
        | - 'all'    : "All Companies" mode (0)
        | - 'none'   : no default (user must pick)
        */
        'default_mode' => 'first',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Switcher Configuration
    |--------------------------------------------------------------------------
    | Controls the module switcher dropdown in the top navigation bar.
    | Mirrors the multitenancy company-switcher pattern for consistency.
    */
    'module_switcher' => [

        /*
        |------------------------------------------------------------------
        | Enable Module Switcher
        |------------------------------------------------------------------
        | Toggle the entire module switcher dropdown on/off. When false,
        | the switcher is hidden for all users regardless of role.
        */
        'enabled' => true,

        /*
        |------------------------------------------------------------------
        | Module Switcher Roles
        |------------------------------------------------------------------
        | Roles that can see the module switcher dropdown in the top nav.
        | Use '*' to allow all authenticated users.
        | Use an array of role names (e.g. ['super_admin', 'admin']) to
        | restrict visibility to specific roles.
        */
        'roles' => '*',

        /*
        |------------------------------------------------------------------
        | Cross-Module Links
        |------------------------------------------------------------------
        | Navigation links rendered inside the top bar that point to other
        | modules (e.g. an "Admin Panel" link when in the HR module, or a
        | "Back to HR" link when in the Admin module).
        |
        | Each link has:
        |   - label : Display text
        |   - url   : Absolute or relative URL
        |   - icon  : Font Awesome icon class
        |   - roles : (optional) Roles that can see this link; defaults to
        |             module_switcher.roles when omitted.
        |
        | For backward compatibility, the old 'cross_module_links' config
        | key is still read as a fallback when 'module_switcher.links' is
        | empty.
        */
        'links' => [
            // 'admin' => [
            //     'label' => 'Admin Panel',
            //     'url' => '/admin/dashboard',
            //     'icon' => 'fas fa-cog',
            // ],
            // 'back' => [
            //     'label' => 'Back to HR',
            //     'url' => '/hr/dashboard',
            //     'icon' => 'fas fa-reply',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Jobs Configuration
    |--------------------------------------------------------------------------
    | Controls the background jobs launcher button in the top navigation bar.
    | Mirrors the module_switcher and multitenancy patterns for consistency.
    */
    'background_jobs' => [

        /*
        |------------------------------------------------------------------
        | Enable Background Jobs Launcher
        |------------------------------------------------------------------
        | Toggle the background jobs launcher button on/off. When false,
        | the button is hidden for all users regardless of role.
        */
        'enabled' => true,

        /*
        |------------------------------------------------------------------
        | Background Jobs Roles
        |------------------------------------------------------------------
        | Roles that can see the background jobs launcher button in the
        | top nav. Use '*' to allow all authenticated users.
        | Use an array of role names (e.g. ['super_admin', 'admin']) to
        | restrict visibility to specific roles.
        */
        'roles' => '*',

        /*
        |------------------------------------------------------------------
        | Icon
        |------------------------------------------------------------------
        | Font Awesome icon class for the background jobs button.
        */
        'icon' => 'fas fa-history',

        /*
        |------------------------------------------------------------------
        | Title
        |------------------------------------------------------------------
        | Tooltip / title attribute for the background jobs button.
        */
        'title' => 'Background Jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    | Controls the notifications icon button in the top navigation bar.
    | Mirrors the background_jobs, module_switcher, and multitenancy patterns
    | for consistency.
    */
    'notifications' => [

        /*
        |------------------------------------------------------------------
        | Enable Notifications
        |------------------------------------------------------------------
        | Toggle the notifications icon button on/off. When false,
        | the button is hidden for all users regardless of role.
        */
        'enabled' => true,

        /*
        |------------------------------------------------------------------
        | Notifications Roles
        |------------------------------------------------------------------
        | Roles that can see the notifications icon button in the
        | top nav. Use '*' to allow all authenticated users.
        | Use an array of role names (e.g. ['super_admin', 'admin']) to
        | restrict visibility to specific roles.
        */
        'roles' => '*',

        /*
        |------------------------------------------------------------------
        | Icon
        |------------------------------------------------------------------
        | Font Awesome icon class for the notifications button.
        */
        'icon' => 'fas fa-bell',

        /*
        |------------------------------------------------------------------
        | Title
        |------------------------------------------------------------------
        | Tooltip / title attribute for the notifications button.
        */
        'title' => 'Notifications',

        /*
        |------------------------------------------------------------------
        | Badge Enabled
        |------------------------------------------------------------------
        | When true, a badge showing the unread notification count is
        | displayed on the icon. (Future feature — not yet implemented.)
        */
        'badge_enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the user profile dropdown menu in the top navigation bar.
    | Each link has a label, icon, and optional url/route. When both url
    | and route are null, the link is hidden from the dropdown. Consuming
    | applications set url or route to make the link visible.
    |
    | The dropdown itself can be disabled entirely by setting 'enabled'
    | to false. The Sign Out button is always present regardless of
    | configuration.
    |
    | Design decision: When all links are hidden (null url/route), the
    | dropdown still renders with the user name header and Sign Out
    | button — it simply shows no configurable links between them.
    |
    */
    'user_menu' => [
        'enabled' => true,
        'links' => [
            /* This might be needed in a consuming app to give access to user profile
            [
                'label' => 'My Profile',
                'url' => null,
                'icon' => 'fas fa-user',
                'route' => 'profile',
            ],*/
            [
                'label' => 'Edit My Account',
                'url' => null,
                // 'icon' => 'fas fa-cog',
                'icon' => 'fas fa-edit',
                'route' => 'my-account',
            ],
            [
                'label' => 'My Preferences',
                'url' => null,
                'icon' => 'fas fa-sliders-h',
                'route' => 'my-preferences',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Configuration
    |--------------------------------------------------------------------------
    | Controls which User model the library resolves, and which traits the
    | install command injects into it.
    |
    | 'model' — The fully-qualified class name of the User model. Defaults
    |          to Laravel's auth provider configuration so it always matches
    |          your application's actual User model, regardless of namespace.
    |
    | 'required_traits' — Traits that the install command will attempt to add
    |                      to the User model. Consuming apps can customise
    |                      which traits are auto-injected.
    */
    'user' => [

        'required_traits' => [
            \QuickerFaster\UILibrary\Traits\HasUILibraryUser::class,
            \Spatie\Permission\Traits\HasRoles::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control Configuration
    |--------------------------------------------------------------------------
    |
    | Controls what roles, modules, and models appear in the
    | AccessControlManager permission assignment UI.
    |
    | - roles.include / roles.exclude: filter which roles are assignable
    | - modules.include / modules.exclude: filter which modules show permissions
    | - models.include / models.exclude: filter which models show permission cards
    |
    | Use '*' to include all, or an array of keys/names.
    */
    'access_control' => [
        'roles' => [
            'include' => '*',   // '*' or ['super_admin', 'admin', 'company_admin']
            'exclude' => [],    // ['super_admin'] to hide from assignment
        ],
        'modules' => [
            'include' => '*',
            'exclude' => ["system", "common", "admin"],    // ['system'] to hide infrastructure modules
        ],
        'models' => [
            'include' => '*',
            'exclude' => [],    // ['App\Models\User'] to hide specific models
        ],
    ],
];