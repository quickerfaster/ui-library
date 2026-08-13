<?php

return [
    [
    'title' => 'Users',
    'icon' => 'fas fa-users-cog',
    'url' => 'admin/admin/users',
    'permission' => 'view_user',
    'order' => 1,
],
    [
    'title' => 'Roles',
    'icon' => 'fas fa-user-shield',
    'url' => 'admin/admin/roles',
    'permission' => 'view_role',
    'order' => 2,
],
    [
    'title' => 'Assign Permissions',
    'icon' => 'fas fa-user-lock',
    'url' => 'admin/admin/access-control-management',
    'permission' => 'view_permission',
    'order' => 3,
],
    [
    'title' => 'Assign Roles',
    'icon' => 'fas fa-user-tag',
    'url' => 'admin/admin/role-assignment',
    'permission' => 'view_assign_user_role',
    'order' => 4,
],
    [
    'itemType' => 'item-separator',
    'title' => '<h6 class="ps-3 mt-4 mb-2 text-uppercase text-xs font-weight-bolder opacity-6 group-title">Audit</h6>',
    'url' => null,
],
    [
    'title' => 'Activity Log',
    'icon' => 'fas fa-history',
    'url' => 'admin/admin/activity-logs',
    'permission' => 'view_activity_log',
    'groupTitle' => 'Audit',
    'order' => 10,
],
    [
    'title' => 'System Settings',
    'icon' => 'fas fa-cube',
    'url' => 'admin/system-settings',
    'permission' => 'view_system_setting',
],
];
