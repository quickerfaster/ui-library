<?php

return [
    'context_groups' => [
        'policies' => [
            'label' => 'Policies',
            'icon' => 'fas fa-gavel',
            'order' => 999,
            'route' => NULL,
            'url' => 'attendance-policies',
        ],
        'settings' => [
            'label' => 'Settings',
            'icon' => 'fas fa-cog',
            'order' => 999,
            'route' => NULL,
            'url' => 'departments',
        ],
        'people' => [
            'label' => 'People',
            'icon' => 'fas fa-user-friends',
            'order' => 999,
            'route' => NULL,
            'url' => 'employees',
        ],
        'payroll' => [
            'label' => 'Payroll',
            'icon' => 'fas fa-dollar-sign',
            'order' => 999,
            'route' => NULL,
            'url' => 'employee-payroll-profiles',
        ],
        'leave' => [
            'label' => 'Leave',
            'icon' => 'fas fa-user-check',
            'order' => 999,
            'route' => NULL,
            'url' => 'my-leave',
        ],
        'time' => [
            'label' => 'Time',
            'icon' => 'fas fa-user-clock',
            'order' => 999,
            'route' => NULL,
            'url' => 'attendances',
        ],
    ],
    'contexts' => [
        'policies' => [
            [
                'key' => 'attendance_policy',
                'label' => 'Attendance Policies',
                'icon' => 'fas fa-gavel',
                'route' => '/hr/attendance-policies',
                'permission' => 'view_attendance_policy',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'work_pattern',
                'label' => 'Work Patterns',
                'icon' => 'fas fa-calendar-week',
                'route' => '/hr/work-patterns',
                'permission' => 'view_work_pattern',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'time' => [
            [
                'key' => 'shift',
                'label' => 'Shifts',
                'icon' => 'fas fa-calendar-day',
                'route' => '/hr/shifts',
                'permission' => 'view_shift',
                'order' => 1000,
                'page_title' => 'Shift',
            ],
            [
                'key' => 'attendance',
                'label' => 'Attendance',
                'icon' => 'fas fa-user-clock',
                'route' => '/hr/attendances',
                'permission' => 'view_attendance',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'shift_schedule',
                'label' => 'Shift Schedules',
                'icon' => 'fas fa-calendar-alt',
                'route' => '/hr/shift-schedules',
                'permission' => 'view_shift_schedule',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'holiday_calendar',
                'label' => 'Holiday Calendars',
                'icon' => 'fas fa-calendar-alt',
                'route' => '/hr/holiday-calendars',
                'permission' => 'view_holiday_calendar',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'holiday',
                'label' => 'Holidays',
                'icon' => 'fas fa-gift',
                'route' => '/hr/holidays',
                'permission' => 'view_holiday',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'settings' => [
            [
                'key' => 'location',
                'label' => 'Locations',
                'icon' => 'fas fa-map-marker-alt',
                'route' => '/hr/locations',
                'permission' => 'view_location',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'company',
                'label' => 'Companies',
                'icon' => 'fas fa-building',
                'route' => '/hr/companies',
                'permission' => 'manage-system',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'department',
                'label' => 'Departments',
                'icon' => 'fas fa-sitemap',
                'route' => '/hr/departments',
                'permission' => 'view_department',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'job_title',
                'label' => 'Job Titles',
                'icon' => 'fas fa-briefcase',
                'route' => '/hr/job-titles',
                'permission' => 'view_job_title',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'people' => [
            [
                'key' => 'employee',
                'label' => 'Employees',
                'icon' => 'fas fa-user-friends',
                'route' => '/hr/employees',
                'permission' => 'view_employee',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'employee_position',
                'label' => 'Job Information',
                'icon' => 'fas fa-briefcase',
                'route' => '/hr/employee-positions',
                'permission' => 'view_employee_position',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'employee_profile',
                'label' => 'Profiles',
                'icon' => 'fas fa-user-circle',
                'route' => '/hr/employee-profiles',
                'permission' => 'view_employee_profile',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'document' => [
            [
                'key' => 'document',
                'label' => 'Documents',
                'icon' => 'fas fa-file-alt',
                'route' => '/hr/documents',
                'permission' => 'view_document',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'payroll' => [
            [
                'key' => 'pay_schedule',
                'label' => 'Pay Schedules',
                'icon' => 'fas fa-calendar-alt',
                'route' => '/hr/pay-schedules',
                'permission' => 'view_pay_schedule',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'employee_payroll_profile',
                'label' => 'Employees',
                'icon' => 'fas fa-user-tie',
                'route' => '/hr/employee-payroll-profiles',
                'permission' => 'view_employee_payroll_profile',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'payroll_run',
                'label' => 'Pay Runs',
                'icon' => 'fas fa-file-invoice-dollar',
                'route' => '/hr/payroll-runs',
                'permission' => 'view_payroll_run',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'payroll_payslip',
                'label' => 'Payslips',
                'icon' => 'fas fa-receipt',
                'route' => '/hr/payroll-payslips',
                'permission' => 'view_payroll_payslip',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'leave' => [
            [
                'key' => 'leave_type',
                'label' => 'Leave Types',
                'icon' => 'fas fa-tags',
                'route' => '/hr/leave-types',
                'permission' => 'view_leave_type',
                'order' => 999,
                'page_title' => NULL,
            ],
            [
                'key' => 'leave_request',
                'label' => 'Leave Requests',
                'icon' => 'fas fa-calendar-alt',
                'route' => '/hr/leave-requests',
                'permission' => 'view_leave_request',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'leave_balance' => [
            [
                'key' => 'leave_balance',
                'label' => 'Leave Balances',
                'icon' => 'fas fa-scale-balanced',
                'route' => '/hr/leave-balances',
                'permission' => 'view_leave_balance',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'leave_approver' => [
            [
                'key' => 'leave_approver',
                'label' => 'Leave Approvers',
                'icon' => 'fas fa-user-shield',
                'route' => '/hr/leave-approvers',
                'permission' => 'view_leave_approver',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'attendance_adjustment' => [
            [
                'key' => 'attendance_adjustment',
                'label' => 'Attendance Adjustments',
                'icon' => 'fas fa-edit',
                'route' => '/hr/attendance-adjustments',
                'permission' => 'view_attendance_adjustment',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'clock_event' => [
            [
                'key' => 'clock_event',
                'label' => 'Clock Events',
                'icon' => 'fas fa-clock',
                'route' => '/hr/clock-events',
                'permission' => 'view_clock_event',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
        'attendance_session' => [
            [
                'key' => 'attendance_session',
                'label' => 'Attendance Sessions',
                'icon' => 'fas fa-hourglass-half',
                'route' => '/hr/attendance-sessions',
                'permission' => 'view_attendance_session',
                'order' => 999,
                'page_title' => NULL,
            ],
        ],
    ],


        // Shared items appear in every context menu
    'shared_items' => [
        'header' => [
            [
                'key'   => 'dashboard',
                'label' => 'Dashboard',
                'icon'  => 'fas fa-tachometer-alt',
                'route' => 'dashboard',
                'order' => 10,
                'visibility' => 'any', // or 'auth', 'guest', 'role:admin', 'permission:view dashboard'
            ],
        ],
        'footer' => [
            [
                'key'   => 'settings',
                'label' => 'Settings',
                'icon'  => 'fas fa-cog',
                'route' => 'settings',
                'order' => 100,
                'visibility' => 'role:admin',
            ],
        ],
    ],

    'shared_top_items' => [
            'left' => [
                [
                    'key'   => 'admin_dashboard',
                    'label' => 'Admin',
                    'icon'  => 'fas fa-cog',
                    'route' => null, //'admin.dashboard',
                    'order' => 1,
                    'visibility' => 'role:admin',
                ],
            ],
            'right' => [
                [
                    'key'   => 'help',
                    'label' => 'Help',
                    'icon'  => 'fas fa-question',
                    'route' => null, //'help',
                    'order' => 10,
                    'visibility' => 'auth',
                ],
            ],
        ],






    'layout' => [
        'top_bar' => [
            'enabled' => true,
        ],
        'context_menu' => [
            'type' => 'sidebar',
            'position' => 'left',
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
];
