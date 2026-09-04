<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('qf-core::admin.dashboard');
    })->name('admin.dashboard');

    // -----------------------------------------------------------------
    // Dashboard context group — overview pages (2-segment URLs)
    // These are the landing pages for context group headers.
    // Explicit routes ensure they work even when the catch-all route
    // is not loaded (e.g., when no business modules exist).
    // -----------------------------------------------------------------
    Route::get('/admin/dashboard-overview', function () {
        return view('qf-core::admin.dashboard-overview');
    })->name('admin.dashboard-overview');

    Route::get('/admin/dashboard-security-overview', function () {
        return view('qf-core::admin.dashboard-security-overview');
    })->name('admin.dashboard-security-overview');

    Route::get('/admin/users', function () {
        return view('qf-core::admin.users');
    })->name('admin.users');

    Route::get('/admin/roles', function () {
        return view('qf-core::admin.roles');
    })->name('admin.roles');

    Route::get('/admin/permissions', function () {
        return view('qf-core::admin.permissions');
    })->name('admin.permissions');

    Route::get('/admin/access-control-management', function () {
        return view('qf-core::admin.access-control-management');
    })->name('admin.access-control-management');

    // -----------------------------------------------------------------
    // Dashboard context group — sub-pages (3-segment URLs)
    // -----------------------------------------------------------------
    Route::get('/admin/dashboard/user-statistics', function () {
        return view('qf-core::admin.dashboard.user-statistics');
    })->name('admin.dashboard.user-statistics');

    Route::get('/admin/dashboard/role-summary', function () {
        return view('qf-core::admin.dashboard.role-summary');
    })->name('admin.dashboard.role-summary');

    Route::get('/admin/dashboard/recent-activity', function () {
        return view('qf-core::admin.dashboard.recent-activity');
    })->name('admin.dashboard.recent-activity');

    Route::get('/admin/dashboard/security-alerts', function () {
        return view('qf-core::admin.dashboard.security-alerts');
    })->name('admin.dashboard.security-alerts');

    // -----------------------------------------------------------------
    // Security context group — sub-pages (3-segment URLs)
    // -----------------------------------------------------------------
    Route::get('/admin/security/authentication', function () {
        return view('qf-core::admin.security.authentication');
    })->name('admin.security.authentication');

    Route::get('/admin/security/password-policies', function () {
        return view('qf-core::admin.security.password-policies');
    })->name('admin.security.password-policies');

    Route::get('/admin/security/multi-factor-authentication', function () {
        return view('qf-core::admin.security.multi-factor-authentication');
    })->name('admin.security.multi-factor-authentication');

    Route::get('/admin/security/api-tokens', function () {
        return view('qf-core::admin.security.api-tokens');
    })->name('admin.security.api-tokens');

    Route::get('/admin/security/login-restrictions', function () {
        return view('qf-core::admin.security.login-restrictions');
    })->name('admin.security.login-restrictions');

    // -----------------------------------------------------------------
    // General Settings context group — top-level route
    // -----------------------------------------------------------------
    Route::get('/system-settings', function () {
        return view('qf-core::admin.system-settings');
    })->name('admin.system-settings');

    Route::get('/admin/invitations', function () {
        return view('qf-core::admin.invitations');
    })->name('admin.invitations');

    Route::get('/admin/user-groups', function () {
        return view('qf-core::admin.user-groups');
    })->name('admin.user-groups');

    Route::get('/admin/user-preferences', function () {
        return view('qf-core::admin.user-preferences');
    })->name('admin.user-preferences');

    Route::get('/admin/general-settings', function () {
        return view('qf-core::admin.general-settings');
    })->name('admin.general-settings');

    Route::get('/admin/onboarding', function () {
        return view('qf-core::admin.onboarding');
    })->name('admin.onboarding');

    // -----------------------------------------------------------------
    // Users & Access context group — overview pages
    // -----------------------------------------------------------------
    Route::get('/admin/dashboard-users-overview', function () {
        return view('qf-core::admin.dashboard-users-overview');
    })->name('admin.dashboard-users-overview');

    Route::get('/admin/dashboard-access-overview', function () {
        return view('qf-core::admin.dashboard-access-overview');
    })->name('admin.dashboard-access-overview');

    // -----------------------------------------------------------------
    // Workflows context group
    // -----------------------------------------------------------------
    Route::get('/admin/dashboard-workflows-overview', function () {
        return view('qf-core::admin.dashboard-workflows-overview');
    })->name('admin.dashboard-workflows-overview');

    Route::get('/admin/workflow-definitions', function () {
        return view('qf-core::admin.workflow-definitions');
    })->name('admin.workflow-definitions');

    Route::get('/admin/workflow-definition-wizard', function () {
        return view('qf-core::admin.workflow-definition-wizard');
    })->name('admin.workflow-definition-wizard');

    // -----------------------------------------------------------------
    // Sessions
    // -----------------------------------------------------------------
    Route::get('/admin/sessions', function () {
        return view('qf-core::admin.sessions');
    })->name('admin.sessions');

    // -----------------------------------------------------------------
    // Audit context group
    // -----------------------------------------------------------------
    Route::get('/admin/dashboard-audit-overview', function () {
        return view('qf-core::admin.dashboard-audit-overview');
    })->name('admin.dashboard-audit-overview');

    Route::get('/admin/activity-logs', function () {
        return view('qf-core::admin.activity-logs');
    })->name('admin.activity-logs');

    Route::get('/admin/login-history', function () {
        return view('qf-core::admin.login-history');
    })->name('admin.login-history');

    Route::get('/admin/system-events', function () {
        return view('qf-core::admin.system-events');
    })->name('admin.system-events');

    Route::get('/admin/audit-exports', function () {
        return view('qf-core::admin.audit-exports');
    })->name('admin.audit-exports');

    // -----------------------------------------------------------------
    // Settings & Notifications context groups
    // -----------------------------------------------------------------
    Route::get('/admin/dashboard-settings-overview', function () {
        return view('qf-core::admin.dashboard-settings-overview');
    })->name('admin.dashboard-settings-overview');

    Route::get('/admin/dashboard-notifications-overview', function () {
        return view('qf-core::admin.dashboard-notifications-overview');
    })->name('admin.dashboard-notifications-overview');

    Route::get('/admin/notifications', function () {
        return view('qf-core::admin.notifications');
    })->name('admin.notifications');

    Route::get('/admin/notification-preferences', function () {
        return view('qf-core::admin.notification-preferences');
    })->name('admin.notification-preferences');

    Route::get('/admin/notification-logs', function () {
        return view('qf-core::admin.notification-logs');
    })->name('admin.notification-logs');
});
