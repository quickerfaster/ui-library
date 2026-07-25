<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Socialite Configuration
    |--------------------------------------------------------------------------
    */
    'socialite' => [
        // Master switch for social login
        'enabled' => env('UI_LIBRARY_SOCIALITE_ENABLED', false),

        // List of enabled providers (must match driver names in Socialite)
        'providers' => [
            'google' => [
                'enabled'       => env('UI_LIBRARY_SOCIALITE_GOOGLE', false),
                'client_id'     => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect'      => url('/auth/google/callback'), // env('GOOGLE_REDIRECT_URI'),
            ],
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    | Controls company switcher visibility and access.
    */
    'multitenancy' => [

        /*
        |--------------------------------------------------------------------------
        | Company Switcher Roles
        |--------------------------------------------------------------------------
        | Roles that can see the company switcher dropdown in the top nav.
        | Use '*' to allow all authenticated users.
        */
        'switcher_roles' => ['super_admin', 'company_admin'],

        /*
        |--------------------------------------------------------------------------
        | All Companies Access
        |--------------------------------------------------------------------------
        | Roles that can select "All Companies" to see data across all companies.
        */
        'all_companies_roles' => ['super_admin', 'company_admin'],

        /*
        |--------------------------------------------------------------------------
        | Default Company Mode
        |--------------------------------------------------------------------------
        | When a user has multiple available companies, which one to default to:
        | - 'first'  : the first company in their list
        | - 'all'    : "All Companies" mode (0)
        | - 'none'   : no default (user must pick)
        */
        'default_mode' => 'first',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | Toggle in-development or gated features per environment.
    */
    'features' => [

        /*
        |--------------------------------------------------------------------------
        | Multi-Company Payroll
        |--------------------------------------------------------------------------
        | When enabled, super admins in "All Companies" mode can process payroll
        | for all companies at once. Each company's payslips are generated
        | independently within a single PayrollRun.
        |
        | Set in .env: FEATURE_MULTI_COMPANY_PAYROLL=true
        */
        'multi_company_payroll' => env('FEATURE_MULTI_COMPANY_PAYROLL', false),

    ],

];


