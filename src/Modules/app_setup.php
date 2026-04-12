<?php

return [
    'items' => [
        [
            'label'      => 'Location',
            'configKey'  => 'hr_location',        // must match your existing config key
            'model'      => App\Modules\Admin\Models\Location::class,
            'icon'       => 'fas fa-map-marker-alt', // optional
            'description' => 'Add your company’s physical locations',
            'groups'      => ['location_details'],   // only fields from this group

        ],
        [
            'label'      => 'Company',
            'configKey'  => 'hr_company',
            'model'      => App\Modules\Admin\Models\Company::class,
            'icon'       => 'fas fa-building',
            'description' => 'Enter your company details',
            'groups'      => ['basic_info'],

        ],
        
        [
            'label'      => 'Departments',
            'configKey'  => 'hr_department',
            'model'      => App\Modules\Admin\Models\Department::class,
            'icon'       => 'fas fa-sitemap',
            'description' => 'Create departments (e.g., Sales, IT)',
            'groups'      => ['department_info'],

        ],
        [
            'label'      => 'Job Titles',
            'configKey'  => 'hr_job_title',
            'model'      => App\Modules\Admin\Models\JobTitle::class,
            'icon'       => 'fas fa-briefcase',
            'description' => 'Define job titles for employees',
        ],
        [
            'label'      => 'Admin User',
            'configKey'  => 'hr_employee',        // assuming employee config handles user creation
            'model'      => App\Modules\Hr\Models\Employee::class,
            'icon'       => 'fas fa-user',
            'description' => 'Create the first administrator account',
        ],
    ],
];