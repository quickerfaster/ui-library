<?php
// config/tour.php

return [
    'workspace' => [
        [
            'element' => '.module-switcher',
            'popover' => [
                'title' => '👋 Welcome to QuickHR!',
                'description' => 'This is your primary workspace. As your business grows, you can use this menu to switch between <b>HR</b>, <b>Accounting</b>, and <b>Inventory</b> modules.<br><br>Let’s take 30 seconds to show you around!',
                'position' => 'bottom',
            ],
        ],


        [
            'element' => '#main-features-nav',
            'popover' => [
                'title' => 'Your HR Toolbox',
                'description' => 'Access all your core HR tools—like Payroll, Attendance, and Employee records—right here. <br><br><i><b>Pro Tip:</b> Clicking a tool opens a <b>Sidebar</b> with specific actions for that module!</i>',
                'position' => 'bottom',
            ],
        ],

        [
            'element' => '[wire\:key="overflow-dropdown"]', // Targets the "More" menu specifically
            'popover' => [
                'title' => 'Everything Else',
                'description' => 'Looking for something else? Additional tools like **Leave Requests** and **Reports** are tucked away here to keep your workspace clean.',
                'position' => 'bottom',
            ],
        ],


        [
            'element' => '.nav-item .btn-outline-primary', // Targets your Admin/HR toggle button
            'popover' => [
                'title' => 'Switch Context',
                'description' => 'Need to manage users or system settings? Use this button to jump to the **Admin Panel**. You can always click it again to come back to your work in the **HR Module**.',
                'position' => 'bottom',
            ],
        ],

        [
            'element' => '#language-switcher', // Targets the globe icon in the new switcher
            'popover' => [
                'title' => 'Language Settings',
                'description' => 'Switch your interface between English, French, and Spanish seamlessly.',
                'position' => 'bottom',
            ],
        ],
        [
            'element' => '#user-profile-menu',
            'popover' => [
                'title' => 'Your Account',
                'description' => 'Access your personal settings or securely log out of the platform here.<br><br><i><b>Note:</b> System-wide configurations are found in the <b>Admin Panel</b> we saw earlier!</i>',
                'position' => 'bottom',
            ],
        ],

    ],
];
