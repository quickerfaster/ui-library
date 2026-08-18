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
            'roles' => ['super_admin', 'company_admin'],
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
    | Module Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Toggles for the library's convention-based auto-discovery of business
    | modules under app/Modules.
    |
    | 'listeners' — When true (default), the library scans each business
    |               module's Listeners/ directory and auto-registers the
    |               discovered listeners.
    |
    | 'reports'   — When true (default), the library scans each business
    |               module for classes implementing the Reportable contract
    |               and auto-registers them into reports.report_types.
    |
    | 'workflows' — When true (default), the library merges each business
    |               module's Config/workflows.php into workflows.definitions.
    |
    | 'cache_ttl' — Finite cache lifetime (seconds) for production discovery
    |               caches. Cache keys are content-hashed from file paths and
    |               mtimes, so they self-invalidate on deploy; the TTL is a
    |               safety net (the library never uses cache()->forever()).
    |
    | Per-module opt-outs (each defaults to true and is set on the module
    | registry entry during discovery):
    |   'ui-library.modules.{module}.auto_register_listeners'
    |   'ui-library.modules.{module}.auto_register_reports'
    |   'ui-library.modules.{module}.auto_register_workflows'
    */
    'discovery' => [
        'listeners' => true,
        'reports' => true,
        'workflows' => true,
        'cache_ttl' => 86400,
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
        'company_provider' => \QuickerFaster\UILibrary\Services\Navigation\NullCompanyProvider::class,

        /*
        |------------------------------------------------------------------
        | Workspace Resolver
        |------------------------------------------------------------------
        |
        | The WorkspaceResolver contract is bound from this key. The library
        | ships with NullWorkspaceResolver (empty context — no filtering).
        | Consuming apps can publish this config and point the key at their
        | own resolver implementation.
        */
        'workspace_resolver' => \QuickerFaster\UILibrary\Services\Navigation\NullWorkspaceResolver::class,

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
        |       'role'            => 'finance_admin',
        |       'department_type' => 'engineering',
        |       'features'        => ['departments', 'time', 'inventory'],
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
        /*
        |------------------------------------------------------------------
        | Workflow Definitions
        |------------------------------------------------------------------
        |
        | Workflow definitions are resolved DB-first: the engine queries
        | the workflow_definitions table for an active row matching the
        | requested key. When no DB row exists, it falls back to the
        | config-driven definitions below.
        |
        | Use the Workflow Definition Wizard (Admin → Workflows) to
        | create and manage definitions through the UI. The examples
        | below serve as seed/defaults for consuming applications.
        |
        */
        'definitions' => [
            // Example: purchase_order workflow
            // 'purchase_order' => [
            //     'label' => 'Purchase Order Approval',
            //     'steps' => [
            //         ['name' => 'Manager Approval', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['manager', 'admin']],
            //         ['name' => 'Finance Review', 'step_type' => 'approval', 'approval_mode' => 'any', 'roles' => ['finance', 'super_admin']],
            //     ],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval Configuration
    |--------------------------------------------------------------------------
    | Default approver resolution for the workflow engine. Consuming apps can
    | override 'approver_resolver' and 'approver_label_resolver' with their own
    | implementations bound against the corresponding contracts. The
    | 'bypass_roles' list is used by ApprovalGuard to short-circuit role
    | resolution for privileged users.
    |
    | 'list_columns' drives the ApprovalRequestListView component. Each key
    | maps to a column definition with 'label' and 'enabled' flags. Consuming
    | apps can reorder, relabel, or disable columns by publishing this config.
    */
    'approvals' => [
        'approver_resolver' => \QuickerFaster\UILibrary\Services\Approvals\DefaultApproverResolver::class,
        'approver_label_resolver' => \QuickerFaster\UILibrary\Services\Approvals\DefaultApproverLabelResolver::class,
        'bypass_roles' => ['super_admin'],
        'list_columns' => [
            'workflow' => ['label' => 'Workflow', 'enabled' => true],
            'entity' => ['label' => 'Entity', 'enabled' => true],
            'current_step' => ['label' => 'Current Step', 'enabled' => true],
            'status' => ['label' => 'Status', 'enabled' => true],
            'submitted_at' => ['label' => 'Submitted', 'enabled' => true],
            'actions' => ['label' => 'Actions', 'enabled' => true],
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
    | Tenancy Configuration
    |--------------------------------------------------------------------------
    | Controls the tenant column and session key used by CompanyScope and the
    | HasCompanyScope trait. "company" is the library's domain-agnostic tenant
    | term (already used by CompanyProvider, the company switcher, and the
    | company_id convention across the library).
    */
    'tenancy' => [
        'column' => 'company_id',
        'session_key' => 'current_company_id',
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
        | modules (e.g. an "Admin Panel" link when in the Finance module, or a
        | "Back to Finance" link when in the Admin module).
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
            //     'label' => 'Back to Finance',
            //     'url' => '/finance/dashboard',
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
    | Notification Engine & Nav Bell Configuration
    |--------------------------------------------------------------------------
    | Unified configuration for the notification engine (dispatch, channels,
    | queue) and the top-nav notification bell (visibility, icon, badge).
    */
    'notifications' => [

        // ----------------------------------------------------------------
        //  Engine
        // ----------------------------------------------------------------
        'default_channels' => ['database', 'mail'],
        'queue_connection' => env('UI_LIBRARY_NOTIFICATION_QUEUE', null),
        'queue' => env('UI_LIBRARY_NOTIFICATION_ASYNC', false),

        // ----------------------------------------------------------------
        //  Nav bell
        // ----------------------------------------------------------------
        'enabled' => true,
        'roles' => ['super_admin', 'admin', 'user'],
        'icon' => 'fas fa-bell',
        'title' => 'Notifications',
        'badge_enabled' => false, // Future feature

        // ----------------------------------------------------------------
        //  Channel registry (config-driven, see Fix 6)
        // ----------------------------------------------------------------
        'channels' => [
            'database' => \QuickerFaster\UILibrary\Services\Notifications\Channels\DatabaseChannel::class,
            'mail' => \QuickerFaster\UILibrary\Services\Notifications\Channels\MailChannel::class,
            'broadcast' => \QuickerFaster\UILibrary\Services\Notifications\Channels\BroadcastChannel::class,
        ],
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
        'model' => env('UI_LIBRARY_USER_MODEL', config('auth.providers.users.model', 'App\Models\User')),

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

    /*
    |--------------------------------------------------------------------------
    | Catch-All Route Security
    |--------------------------------------------------------------------------
    |
    | Hardening for the centralized /{module}/{view}/{id?} route pattern
    | (see docs/library/15-gaps-and-recommendations.md §10.7).
    |
    | The catch-all route is loaded LAST by ModuleServiceProvider so that
    | module-specific routes take precedence. These settings constrain
    | what the catch-all route is allowed to resolve and render.
    |
    */
    'catch_all' => [

        /*
        |------------------------------------------------------------------
        | Module Allow-List
        |------------------------------------------------------------------
        |
        | Only modules listed here are resolvable via the catch-all route.
        | Requests for modules not in this list receive a 404, preventing
        | the route from being used to probe arbitrary namespaces.
        |
        | The default covers the library's core modules. Business modules
        | discovered under app/Modules/ are appended automatically by
        | ModuleServiceProvider, so consuming apps do not need to publish
        | this config just to enable their own modules.
        |
        */
        'allowed_modules' => ['admin', 'system', 'organization', 'common'],

        /*
        |------------------------------------------------------------------
        | Authorization
        |------------------------------------------------------------------
        |
        | 'require_auth' — When true (default), the catch-all route
        | requires an authenticated user. This is enforced by the 'auth'
        | middleware already applied to the route group and additionally
        | re-checked in the handler for defense in depth.
        |
        | 'gate' — Optional Laravel Gate ability to check before rendering.
        | When set, Gate::allows($gate, [$module, $view, $id]) is called.
        | Set to null to skip per-view Gate checks (default).
        |
        | 'authorization_callback' — Optional callable that receives
        | ($user, $module, $view, $id) and returns true to allow or false
        | to deny (403). This is the primary extension point for consuming
        | apps that need custom per-view/module authorization. It takes
        | precedence over 'gate' when both are configured.
        |
        */
        'require_auth' => true,
        'gate' => null,
        'authorization_callback' => null,

        /*
        |------------------------------------------------------------------
        | Rate Limiting
        |------------------------------------------------------------------
        |
        | 'enabled' — When true, applies Laravel's RateLimiter to the
        | catch-all route using the named limiter 'qf-catch-all'.
        |
        | 'max_attempts' — Maximum requests per decay window.
        | 'decay_minutes' — Decay window in minutes.
        |
        | Requests are keyed by authenticated user id when available,
        | falling back to the client IP address for guests.
        |
        */
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],
];