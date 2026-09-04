# Admin Placeholder Pages

> **Last updated:** 2026-08-19  
> **Context:** Audit of all Admin context group sidebar links — 404 remediation and placeholder documentation  
> **Verification:** All 34 navigation URLs return HTTP 200 (or 403 for permission-restricted pages)

---

## Overview

This document catalogs every placeholder page in the Admin module. A **placeholder page** is a Blade view that renders a minimal UI (typically a card with a heading and "will be implemented in the future" description) rather than full functionality. This ensures:

- No sidebar link returns a 404
- Every navigation item has a visible landing page
- Future implementation work is tracked and not forgotten

---

## Placeholder Pages — Newly Created (2026-08-19)

These 16 pages were created to resolve 404 errors discovered during the audit.

### Dashboard Context Group

| # | URL | View File | Type | Intended Future Functionality |
|---|-----|-----------|------|-------------------------------|
| 1 | `/admin/dashboard-overview` | [`src/Core/Admin/Resources/views/admin/dashboard-overview.blade.php`](src/Core/Admin/Resources/views/admin/dashboard-overview.blade.php) | Dashboard overview | Landing page for the Dashboard context group. Shows stat widgets (users, roles, permissions). Expand with more comprehensive system-wide metrics. |
| 2 | `/admin/dashboard/user-statistics` | [`src/Core/Admin/Resources/views/admin/dashboard/user-statistics.blade.php`](src/Core/Admin/Resources/views/admin/dashboard/user-statistics.blade.php) | Card placeholder | Detailed user statistics: growth trends, engagement metrics, activity breakdowns. |
| 3 | `/admin/dashboard/role-summary` | [`src/Core/Admin/Resources/views/admin/dashboard/role-summary.blade.php`](src/Core/Admin/Resources/views/admin/dashboard/role-summary.blade.php) | Card placeholder | Overview of all roles with permission counts and user assignment summaries. |
| 4 | `/admin/dashboard/recent-activity` | [`src/Core/Admin/Resources/views/admin/dashboard/recent-activity.blade.php`](src/Core/Admin/Resources/views/admin/dashboard/recent-activity.blade.php) | Card placeholder | Real-time activity feed showing recent actions across the system. |
| 5 | `/admin/dashboard/security-alerts` | [`src/Core/Admin/Resources/views/admin/dashboard/security-alerts.blade.php`](src/Core/Admin/Resources/views/admin/dashboard/security-alerts.blade.php) | Card placeholder | Security alert dashboard: failed logins, suspicious activities, policy violations. |

### Users Context Group

| # | URL | View File | Type | Intended Future Functionality |
|---|-----|-----------|------|-------------------------------|
| 6 | `/admin/invitations` | [`src/Core/Admin/Resources/views/admin/invitations.blade.php`](src/Core/Admin/Resources/views/admin/invitations.blade.php) | Card placeholder | User invitation management: send, track, resend, and revoke invitations. |
| 7 | `/admin/user-groups` | [`src/Core/Admin/Resources/views/admin/user-groups.blade.php`](src/Core/Admin/Resources/views/admin/user-groups.blade.php) | Card placeholder | Create and manage user groups for streamlined permission assignment. |
| 8 | `/admin/user-preferences` | [`src/Core/Admin/Resources/views/admin/user-preferences.blade.php`](src/Core/Admin/Resources/views/admin/user-preferences.blade.php) | Card placeholder | Configure default user preferences and system-wide user settings. |

### Security Context Group

| # | URL | View File | Type | Intended Future Functionality |
|---|-----|-----------|------|-------------------------------|
| 9 | `/admin/dashboard-security-overview` | [`src/Core/Admin/Resources/views/admin/dashboard-security-overview.blade.php`](src/Core/Admin/Resources/views/admin/dashboard-security-overview.blade.php) | Dashboard overview | Landing page for the Security context group. Shows active sessions and role counts. Expand with security posture metrics. |
| 10 | `/admin/security/authentication` | [`src/Core/Admin/Resources/views/admin/security/authentication.blade.php`](src/Core/Admin/Resources/views/admin/security/authentication.blade.php) | Card placeholder | Authentication method configuration: SSO providers, OAuth, SAML, login settings. |
| 11 | `/admin/security/password-policies` | [`src/Core/Admin/Resources/views/admin/security/password-policies.blade.php`](src/Core/Admin/Resources/views/admin/security/password-policies.blade.php) | Card placeholder | Password complexity requirements, expiration rules, and history enforcement. |
| 12 | `/admin/security/multi-factor-authentication` | [`src/Core/Admin/Resources/views/admin/security/multi-factor-authentication.blade.php`](src/Core/Admin/Resources/views/admin/security/multi-factor-authentication.blade.php) | Card placeholder | MFA configuration: required methods, enforcement policies, recovery codes. |
| 13 | `/admin/security/api-tokens` | [`src/Core/Admin/Resources/views/admin/security/api-tokens.blade.php`](src/Core/Admin/Resources/views/admin/security/api-tokens.blade.php) | Card placeholder | API token management: issue, revoke, set expiration, scope permissions. |
| 14 | `/admin/security/login-restrictions` | [`src/Core/Admin/Resources/views/admin/security/login-restrictions.blade.php`](src/Core/Admin/Resources/views/admin/security/login-restrictions.blade.php) | Card placeholder | IP whitelisting, time-based access restrictions, login attempt limits. |
| 15 | `/admin/sessions` | [`src/Core/Admin/Resources/views/admin/sessions.blade.php`](src/Core/Admin/Resources/views/admin/sessions.blade.php) | Card placeholder | View and manage active user sessions across the system. |

### General Settings Context Group

| # | URL | View File | Type | Intended Future Functionality |
|---|-----|-----------|------|-------------------------------|
| 16 | `/system-settings` | [`src/Core/Admin/Resources/views/admin/system-settings.blade.php`](src/Core/Admin/Resources/views/admin/system-settings.blade.php) | Card placeholder | Global system settings: application name, timezone, localization, maintenance mode. |

---

## Placeholder Pages — Pre-Existing

These placeholder pages existed before the audit and follow the same card-placeholder pattern.

### Audit Context Group

| # | URL | View File | Type | Intended Future Functionality |
|---|-----|-----------|------|-------------------------------|
| 17 | `/admin/activity-logs` | [`src/Core/Admin/Resources/views/admin/activity-logs.blade.php`](src/Core/Admin/Resources/views/admin/activity-logs.blade.php) | Card placeholder | Full datatable of all user activities with filtering and export. Requires `Data/activity_log.php` config and Livewire component. |
| 18 | `/admin/login-history` | [`src/Core/Admin/Resources/views/admin/login-history.blade.php`](src/Core/Admin/Resources/views/admin/login-history.blade.php) | Card placeholder | Login history datatable with success/failure tracking, IP addresses, and user agents. |
| 19 | `/admin/system-events` | [`src/Core/Admin/Resources/views/admin/system-events.blade.php`](src/Core/Admin/Resources/views/admin/system-events.blade.php) | Card placeholder | System-level event log: configuration changes, service starts/stops, errors. |
| 20 | `/admin/audit-exports` | [`src/Core/Admin/Resources/views/admin/audit-exports.blade.php`](src/Core/Admin/Resources/views/admin/audit-exports.blade.php) | Card placeholder | Export audit data in various formats (CSV, PDF, Excel) with scheduling. |

### Access Context Group

| # | URL | View File | Type | Intended Future Functionality |
|---|-----|-----------|------|-------------------------------|
| 21 | `/admin/access-control-management` | [`src/Core/Admin/Resources/views/admin/access-control-management.blade.php`](src/Core/Admin/Resources/views/admin/access-control-management.blade.php) | Card placeholder | Comprehensive access control management interface for permissions and policies. |

---

## New Routes Added

These explicit routes were added to [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php) because the catch-all route (`/{module}/{view}/{id?}`) only handles 2-segment URLs with an optional numeric ID. Three-segment and single-segment URLs require explicit route definitions.

| Route URL | Route Name | View |
|-----------|------------|------|
| `/admin/dashboard/user-statistics` | `admin.dashboard.user-statistics` | `qf-core::admin.dashboard.user-statistics` |
| `/admin/dashboard/role-summary` | `admin.dashboard.role-summary` | `qf-core::admin.dashboard.role-summary` |
| `/admin/dashboard/recent-activity` | `admin.dashboard.recent-activity` | `qf-core::admin.dashboard.recent-activity` |
| `/admin/dashboard/security-alerts` | `admin.dashboard.security-alerts` | `qf-core::admin.dashboard.security-alerts` |
| `/admin/security/authentication` | `admin.security.authentication` | `qf-core::admin.security.authentication` |
| `/admin/security/password-policies` | `admin.security.password-policies` | `qf-core::admin.security.password-policies` |
| `/admin/security/multi-factor-authentication` | `admin.security.multi-factor-authentication` | `qf-core::admin.security.multi-factor-authentication` |
| `/admin/security/api-tokens` | `admin.security.api-tokens` | `qf-core::admin.security.api-tokens` |
| `/admin/security/login-restrictions` | `admin.security.login-restrictions` | `qf-core::admin.security.login-restrictions` |
| `/system-settings` | `admin.system-settings` | `qf-core::admin.system-settings` |
| `/admin/dashboard-overview` | `admin.dashboard-overview` | `qf-core::admin.dashboard-overview` |
| `/admin/dashboard-security-overview` | `admin.dashboard-security-overview` | `qf-core::admin.dashboard-security-overview` |

> **Task AC (2026-08-20)**: `admin/dashboard-overview` and `admin/dashboard-security-overview` had placeholder views but were still 404ing because no explicit named routes existed. The two routes above were added — both now return HTTP 200.

---

## New Dashboard Configs Added

These config files were created to support the dashboard overview pages that use `<livewire:qf.dashboard>`.

| Config Key | File |
|------------|------|
| `admin.dashboards.dashboard_overview` | [`src/Core/Admin/Data/dashboards/dashboard_overview.php`](src/Core/Admin/Data/dashboards/dashboard_overview.php) |
| `admin.dashboards.dashboard_security_overview` | [`src/Core/Admin/Data/dashboards/dashboard_security_overview.php`](src/Core/Admin/Data/dashboards/dashboard_security_overview.php) |

---

## Verification Results (2026-08-19)

All 34 navigation URLs were tested against `http://localhost:8899`:

- **30 URLs** return HTTP 200 ✅
- **4 URLs** return HTTP 403 (permission-restricted, not 404s): `/admin/users`, `/admin/roles`, `/admin/workflow-definitions`, `/admin/notification-logs`
- **0 URLs** return HTTP 404 ✅

> **Task AC (2026-08-20)**: `admin/dashboard-overview` and `admin/dashboard-security-overview` were previously 404ing due to missing routes; they are now fixed and both return HTTP 200 via the new explicit named routes added above.

---

## Implementation Notes

### Placeholder View Pattern

Simple card placeholders follow this pattern:

```blade
<x-qf::navigation-layout configKey="admin.xxx" context="ContextName" moduleName="admin" :overrides="[]">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Page Title</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Description. Full functionality will be implemented in a future update.</p>
        </div>
    </div>
</x-qf::navigation-layout>
```

### Dashboard Overview Pattern

Dashboard overview pages use the Livewire dashboard component:

```blade
<x-qf::navigation-layout 
    configKey="admin.dashboards.dashboard_xxx_overview" 
    context="ContextName" 
    moduleName="admin" 
    :overrides="[...]"
>
    <livewire:qf.dashboard config-key="admin.dashboards.dashboard_xxx_overview" />
</x-qf::navigation-layout>
```

These require a corresponding data config in [`src/Core/Admin/Data/dashboards/`](src/Core/Admin/Data/dashboards/).

### Routing

- **2-segment URLs** (e.g., `/admin/invitations`): Handled by the catch-all route in [`src/Core/System/Routes/web.php`](src/Core/System/Routes/web.php). Just create the view file.
- **3-segment URLs** (e.g., `/admin/dashboard/user-statistics`): Require explicit routes in [`src/Core/Admin/Routes/web.php`](src/Core/Admin/Routes/web.php).
- **Single-segment URLs** (e.g., `/system-settings`): Require explicit routes.