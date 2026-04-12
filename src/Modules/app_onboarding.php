<?php

return [
    'steps' => [
        [
            'title' => 'Add an Employee',
            'link'  => '/hr/employees/create',
            'cta'   => 'Add Employee',
            'model' => \App\Modules\Hr\Models\Employee::class,  // simple global existence
        ],
        [
            'title' => 'Create a Payroll Run',
            'link'  => '/payroll/runs/create',
            'cta'   => 'Create Run',
            'model' => \App\Modules\Hr\Models\PayrollRun::class,
        ],

        // If you need a user‑scoped condition, use a condition class
        [
            'title'     => 'Complete Profile',
            'link'      => '/profile/edit',
            'cta'       => 'Complete',
            'condition' => QuickerFaster\UILibrary\Conditions\Onboarding\ProfileComplete::class,
        ],
    ],
];