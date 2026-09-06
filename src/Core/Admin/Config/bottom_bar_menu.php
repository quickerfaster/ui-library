<?php

return [
    [
    'title' => 'Users',
    'icon' => 'fas fa-user',
    'url' => 'admin/users',
    'permission' => 'view_user',
    'key' => 'user',
],
    [
    'title' => 'Roles',
    'icon' => 'fas fa-user-shield',
    'url' => 'admin/roles',
    'permission' => 'view_role',
    'key' => 'role',
],
    [
    'title' => 'Permissions',
    'icon' => 'fas fa-lock',
    'url' => 'admin/permissions',
    'permission' => 'view_permission',
    'key' => 'permission',
],
    [
    'title' => 'Assign User Roles',
    'icon' => 'fas fa-cube',
    'url' => 'admin/assign-user-roles',
    'permission' => 'view_assign_user_role',
    'key' => 'assign_user_role',
],
    [
    'title' => 'Activity Logs',
    'icon' => 'fas fa-cube',
    'url' => 'admin/activity-logs',
    'permission' => 'view_activity_log',
    'key' => 'activity_log',
],
    [
    'title' => 'System Settings',
    'icon' => 'fas fa-cube',
    'url' => 'admin/system-settings',
    'permission' => 'view_system_setting',
    'key' => 'system_setting',
],
];
