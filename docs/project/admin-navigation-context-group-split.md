# Admin Navigation — Context Group Split Recommendation

> **Date**: 2026-08-19
> **Scope**: Analysis and recommendation for splitting the "Users & Permissions" context group in the Admin module
> **Status**: Proposal — no code changes made
> **Source config**: [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php)

---

## 1. Current State

### 1.1 Admin Top-Nav Context Groups

The Admin module currently defines **7 context groups** in the top navigation bar:

| # | Context Group | Order | Sidebar Items | URL (Overview) |
|---|---|---|---|---|
| 1 | Dashboard | 1 | 5 items | `admin/dashboard-overview` |
| 2 | **Users & Permissions** | 2 | **8 items** | `admin/dashboard-users-overview` |
| 3 | Workflows | 3 | 3 items | `admin/dashboard-workflows-overview` |
| 4 | Security | 4 | 6 items | `admin/dashboard-security-overview` |
| 5 | Audit | 10000 | 5 items | `admin/dashboard-audit-overview` |
| 6 | General Settings | 10000 | 2 items | `admin/dashboard-settings-overview` |
| 7 | Notifications | 10001 | 4 items | `admin/dashboard-notifications-overview` |

> **Note (2026-08-20, Task AC)**: The `Dashboard` context group key was normalized from capital-D `'Dashboard'` to lowercase `'dashboard'` in [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php). The table above lists the display label ("Dashboard"); the underlying array key is now lowercase.

### 1.2 "Users & Permissions" Sidebar Items

When a user clicks the "Users & Permissions" top-nav tab, the sidebar renders these 8 items:

| # | Key | Label | Route | Permission |
|---|---|---|---|---|
| 1 | `users_overview` | Overview | `/admin/dashboard-users-overview` | `view_users_overview` |
| 2 | `user` | Users | `/admin/users` | `view_user` |
| 3 | `invitation` | Invitations | `/admin/invitations` | `view_invitation` |
| 4 | `user_group` | User Groups | `/admin/user-groups` | `view_user_group` |
| 5 | `user_preferences` | User Preferences | `/admin/user-preferences` | `view_user_preferences` |
| 6 | `session` | Sessions | `/admin/sessions` | `view_session` |
| 7 | `role` | Roles | `/admin/roles` | `view_role` |
| 8 | `access_control` | Access Control | `/admin/access-control-management` | `view_permission` |

### 1.3 Structural Observations

- **No consuming-app override exists**: There is no `app/Modules/Admin/Config/navigation.php` in the consuming app. The library config at [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php) is used directly.
- **8 items** is at the upper boundary of the comfortable sidebar range (3–10 items per domain per the [navigation UX analysis](../project/navigation-ux-analysis.md#211-top-nav--sidebar-current-pattern)).
- The group name "Users **&** Permissions" is a compound label — the only compound context group name in the entire Admin module. All other groups use single-concept names: Dashboard, Workflows, Security, Audit, General Settings, Notifications.
- The `max_visible_items` for the top nav is set to **6**, meaning the 7th group (Notifications) overflows into the "More" dropdown. Adding more groups would increase overflow.

---

## 2. Established Patterns from Consuming App Modules

### 2.1 The Operational-vs-Configuration Split

The consuming app's business modules demonstrate a consistent pattern of splitting related entities into separate context groups based on **usage frequency and nature**:

| Module | Operational Groups | Configuration Groups | Pattern |
|---|---|---|---|
| **Organization** | `companies`, `structure`, `teams`, `locations` | `classification`, `reports` | Entity-type grouping with per-group overviews |
| **HR** | `people` (employees, profiles, positions, job history) | `Organization` (companies, departments, locations) | Domain separation |
| **Payroll** (recommended) | Processing (Runs, Payslips, Adjustments) | Configuration (Schedules, Policies, Profiles) | Frequency-of-use split |

Key principles observed:
1. **Each context group has its own Overview** dashboard as the first sidebar item
2. **Groups are named with single-concept nouns** (Companies, Structure, People, Leave) — never compound
3. **Operational items** (used daily) are separated from **configuration items** (used during setup)
4. **3–6 items per group** is the sweet spot; 8+ items signals a split is warranted

### 2.2 The Organization Module as Reference Template

The Organization module at [`app/Modules/Organization/Config/navigation.php`](../../../LaravelProjects/hr-consuming-app/app/Modules/Organization/Config/navigation.php) is the most mature example:

```
Companies (4 items)     → Overview, All Companies, Branches, Business Units
Structure (4 items)     → Overview, Departments, Divisions, Organization Chart
Teams (2 items)         → Overview, All Teams
Locations (2 items)     → Overview, All Locations
Classification (1 item) → Overview
Reports (5 items)       → Overview, Company Reports, Department Reports, ...
```

Each group is a self-contained domain with its own overview dashboard. This is the pattern to emulate.

### 2.3 Existing Admin Precedent: Security Is Already Separate

The Admin module already has a dedicated [`Security`](#13-admin-top-nav-context-groups) context group (order 4) handling:
- Authentication, Password Policies, MFA, API Tokens, Login Restrictions

This establishes that **security-related concerns belong in their own group**, not lumped under "Users & Permissions."

---

## 3. Item-by-Item Analysis

### 3.1 Nature Categorization

Each of the 8 items is analyzed by its **operational frequency** (how often an admin uses it day-to-day) and its **conceptual domain**:

| Item | Nature | Frequency | Domain | Notes |
|---|---|---|---|---|
| **Overview** | Dashboard | Daily | Cross-cutting | Summary stats for the entire Users & Permissions domain |
| **Users** | CRUD / Operational | Daily | User Management | Core entity: create, edit, disable, delete user accounts |
| **Invitations** | Operational | Weekly | User Management | Onboarding flow — tightly coupled to Users (invitations create users) |
| **User Groups** | Organizational | Weekly | User Organization | Grouping users for organizational purposes; bridges Users and Structure |
| **User Preferences** | Configuration | Rarely (setup) | User Settings | Default preferences, notification defaults, display settings |
| **Sessions** | Monitoring / Security | As-needed | Security | Active session monitoring; overlaps with Security's Login Restrictions and Audit's Login History |
| **Roles** | Configuration | Rarely (setup) | Access Control | Role definitions and hierarchies; configured once, rarely changed |
| **Access Control** | Configuration | Rarely (setup) | Access Control | Permission policies and model-level access rules; deeply coupled to Roles |

### 3.2 Affinity Mapping

Items that are **tightly coupled** and should stay together:

```
Users ←→ Invitations          (Invitations are how users enter the system)
Roles ←→ Access Control       (Roles define who; Access Control defines what — configured together)
Sessions → Security           (Sessions is a security monitoring concern, not user management)
User Groups → Users           (Groups organize users; but could also stand alone)
User Preferences → Users      (Per-user settings; but configuration-oriented)
```

### 3.3 Cross-Group Overlaps Identified

| Concern | Current Location | Better Location | Reason |
|---|---|---|---|
| **Sessions** | Users & Permissions | **Security** | Security already handles Authentication, Login Restrictions, MFA — session monitoring is a natural fit |
| **Roles + Access Control** | Users & Permissions | **New "Access" group** | These are configuration entities, not operational user management |
| **User Preferences** | Users & Permissions | **Users** (keep) or **General Settings** | Borderline — user-level preferences are closer to user management than system settings |

---

## 4. Proposed Split

### 4.1 Recommendation: Three-Way Split (Primary)

Replace the single "Users & Permissions" context group with **two new groups** and **relocate one item** to an existing group:

```
BEFORE (1 group, 8 items)              AFTER (2 new groups + 1 relocated item)
                                        
Users & Permissions                    Users (operational)
├── Overview                           ├── Overview (new dedicated dashboard)
├── Users                              ├── Users
├── Invitations                        ├── Invitations
├── User Groups                        ├── User Groups
├── User Preferences                   └── User Preferences
├── Sessions                 ──→       (moved to Security)
├── Roles                    ──→       Access (configuration)
└── Access Control           ──→       ├── Overview (new dedicated dashboard)
                                       ├── Roles
                                       └── Access Control
```

#### Group A: **Users** (operational, order 2)

| # | Key | Label | Route | Rationale |
|---|---|---|---|---|
| 1 | `users_overview` | Overview | `/admin/dashboard-users-overview` | Dedicated dashboard: user count, recent registrations, invitation status, group distribution |
| 2 | `user` | Users | `/admin/users` | Core CRUD — the primary entity admins interact with daily |
| 3 | `invitation` | Invitations | `/admin/invitations` | Onboarding flow; tightly coupled to user creation |
| 4 | `user_group` | User Groups | `/admin/user-groups` | Organizational grouping of users |
| 5 | `user_preferences` | User Preferences | `/admin/user-preferences` | User-level settings and defaults |

**Rationale**: Everything an admin needs for day-to-day user management. Creating users, sending invitations, organizing them into groups, and managing their preferences. This is the "operational" half — used frequently by HR admins and team managers.

**Overview dashboard would show**:
- Total user count with trend (new this week/month)
- Pending invitations count
- Users by group distribution (pie/bar chart)
- Recently created/updated users
- Users without assigned groups (orphaned users)

#### Group B: **Access** (configuration, order 3)

| # | Key | Label | Route | Rationale |
|---|---|---|---|---|
| 1 | `access_overview` | Overview | `/admin/dashboard-access-overview` | Dedicated dashboard: role distribution, permission coverage, unassigned users |
| 2 | `role` | Roles | `/admin/roles` | Role definitions, hierarchies, and permission assignments |
| 3 | `access_control` | Access Control | `/admin/access-control-management` | Model-level permission policies and access rules |

**Rationale**: Roles and Access Control are two sides of the same coin — they are always configured together. An admin defining a new role immediately needs to set its access control policies. These are "configuration" entities — set up during system implementation and rarely changed afterward. Giving them their own group with a dedicated overview reduces cognitive load in the Users group and makes the access control surface independently navigable.

**Overview dashboard would show**:
- Role count and distribution
- Users per role (highlighting over/under-privileged patterns)
- Permission coverage matrix summary
- Recently modified roles/access policies
- Users with no assigned role (security gap)

#### Relocation: **Sessions → Security** (existing group, order 4)

Move the `session` item from "Users & Permissions" into the existing **Security** context group:

| # | Key | Label | Route |
|---|---|---|---|
| (existing) | `security_overview` | Overview | `/admin/dashboard-security-overview` |
| (existing) | `authentication` | Authentication | `/admin/security/authentication` |
| (existing) | `password_policies` | Password Policies | `/admin/security/password-policies` |
| (existing) | `multi_factor_authentication` | Multi-Factor Authentication | `/admin/security/multi-factor-authentication` |
| (existing) | `api_tokens` | API Tokens | `/admin/security/api-tokens` |
| (existing) | `login_restrictions` | Login Restrictions | `/admin/security/login-restrictions` |
| **→ new** | `session` | **Sessions** | `/admin/sessions` |

**Rationale**: Sessions is a security monitoring concern. Security already handles Authentication, Login Restrictions, and MFA — session monitoring completes this picture. It also resolves the conceptual overlap where Sessions, Login History (Audit), and Login Restrictions (Security) are scattered across three different groups.

### 4.2 Resulting Top-Nav Structure

After the split, the Admin top nav would have **8 context groups** (up from 7):

| # | Context Group | Order | Items | Change |
|---|---|---|---|---|
| 1 | Dashboard | 1 | 5 | Unchanged |
| 2 | **Users** | 2 | 5 | **New** (was "Users & Permissions") |
| 3 | **Access** | 3 | 3 | **New** |
| 4 | Workflows | 4 | 3 | Unchanged (order shifted from 3→4) |
| 5 | Security | 5 | 7 | **+1 item** (Sessions added; order shifted from 4→5) |
| 6 | Audit | 10000 | 5 | Unchanged |
| 7 | General Settings | 10000 | 2 | Unchanged |
| 8 | Notifications | 10001 | 4 | Unchanged |

**Overflow impact**: With `max_visible_items: 6`, groups 7–8 (Audit, General Settings, Notifications) already overflow into "More." The split adds one more group (8 total vs 7), so one additional group lands in overflow. This is acceptable — Audit, General Settings, and Notifications are low-frequency groups that belong in overflow.

### 4.3 Alternative: Two-Way Split (Simpler)

If three groups is considered too granular, a simpler two-way split keeps Sessions in Users:

```
Users (operational, 6 items)           Access (configuration, 3 items)
├── Overview                           ├── Overview
├── Users                              ├── Roles
├── Invitations                        └── Access Control
├── User Groups
├── User Preferences
└── Sessions
```

**Trade-off**: Simpler to implement (only one new group), but keeps the conceptual muddiness of Sessions under "Users" and results in 6 items in Users — still at the upper comfortable range.

---

## 5. UX Benefits

### 5.1 Reduced Cognitive Load

| Metric | Before | After |
|---|---|---|
| Items in "Users & Permissions" sidebar | 8 | — |
| Items in "Users" sidebar | — | 5 |
| Items in "Access" sidebar | — | 3 |
| Compound group names | 1 ("Users & Permissions") | 0 |
| Cross-domain items in one group | 3 domains (users, security, access) | 1 domain per group |

### 5.2 Clearer Mental Model

- **"I need to create a user"** → Click **Users** tab → Click Users
- **"I need to define a new role"** → Click **Access** tab → Click Roles
- **"I need to check active sessions"** → Click **Security** tab → Click Sessions

Each intent maps to exactly one top-nav tab. No more "is Sessions under Users & Permissions or Security?"

### 5.3 Alignment with Consuming App Patterns

The split mirrors the Organization module's approach:
- Organization splits `companies`, `structure`, `teams`, `locations` into separate groups
- Admin would split `users` and `access` into separate groups

Both follow the principle: **one conceptual domain per context group, with its own overview dashboard.**

### 5.4 Independent Overview Dashboards

Each new group gets a dedicated overview dashboard, providing:
- **Users overview**: User growth, invitation status, group distribution
- **Access overview**: Role distribution, permission coverage, security gaps

Currently, the single "Users & Permissions" overview must cover all 8 concerns — inevitably shallow. Dedicated overviews allow deeper, more relevant summaries.

---

## 6. Implementation Steps

### 6.1 Config Changes (Library Core)

**File**: [`src/Core/Admin/Config/navigation.php`](../../src/Core/Admin/Config/navigation.php)

1. **Rename** `context_groups['Users & Permissions']` → `context_groups['Users']`
   - Update `label` to `'Users'`
   - Update `icon` to `'fas fa-users'` (currently `'fas fa-users-cog'` — the cog suggests configuration, which is moving to Access)
   - Keep `url` as `'admin/dashboard-users-overview'`

2. **Add** new `context_groups['Access']` entry:
   ```php
   'Access' => [
       'label' => 'Access',
       'icon' => 'fas fa-shield-alt',
       'order' => 3,
       'route' => NULL,
       'url' => 'admin/dashboard-access-overview',
   ],
   ```

3. **Rebuild** `contexts['Users']` (was `contexts['Users & Permissions']`):
   - Keep: `users_overview`, `user`, `invitation`, `user_group`, `user_preferences`
   - Remove: `session`, `role`, `access_control`

4. **Create** `contexts['Access']`:
   - Add `access_overview` (new overview item)
   - Move `role` and `access_control` from old group

5. **Move** `session` item from `contexts['Users & Permissions']` into `contexts['Security']`
   - Insert after `login_restrictions` (order 60) or at an appropriate position

6. **Rebalance** `order` values:
   - Shift Workflows from 3→4, Security from 4→5
   - Or use spaced ordering (10, 20, 30...) like the Organization module

### 6.2 New Routes Required

| Route | Purpose |
|---|---|
| `admin/dashboard-access-overview` | Access group overview dashboard |

### 6.3 New Permissions Required

| Permission | Purpose |
|---|---|
| `view_access_overview` | Gate for the Access overview dashboard |

### 6.4 New Blade View Required

| View | Purpose |
|---|---|
| Access overview dashboard | Widget-based dashboard showing role distribution, permission coverage, security gaps |

### 6.5 No Consuming-App Changes Required

Since there is no `app/Modules/Admin/Config/navigation.php` override in the consuming app, the library config change propagates automatically. The consuming app would only need to:
- Run migrations if new permissions are seeded
- Optionally publish and customize the new overview dashboard

---

## 7. Trade-offs and Risks

### 7.1 Risks

| Risk | Severity | Mitigation |
|---|---|---|
| **Top-nav tab overflow** increases from 7→8 groups | Low | Audit, General Settings, and Notifications already overflow; adding one more group to overflow is negligible |
| **Breaking existing bookmarks** to `/admin/dashboard-users-overview` | Low | The Users group keeps the same overview URL; only the Access overview is new |
| **Permission migration** — existing roles may lack `view_access_overview` | Medium | Seed the new permission for existing admin roles; fall back to `view_role` or `view_permission` for the Access overview |
| **User confusion** during transition | Low | The split is intuitive; the old "Users & Permissions" label was already hinting at two separate concerns |
| **Sessions moving to Security** may surprise users who look for it under Users | Low | Sessions is infrequently accessed; its new location under Security is more logically consistent |

### 7.2 Trade-offs

| Trade-off | Assessment |
|---|---|
| **More top-nav tabs** vs. **cleaner sidebar** | The top nav gains one tab, but each sidebar becomes significantly cleaner (5 and 3 items vs 8). The top-nav overflow already handles excess tabs gracefully. |
| **Two overview dashboards** vs. **one unified overview** | Two focused dashboards provide deeper, more relevant information than one shallow overview trying to cover 8 concerns. |
| **Implementation effort** vs. **UX improvement** | The config changes are minimal (~30 lines modified in one file). One new route, one new permission, one new view. The UX improvement is substantial for daily admin users. |

---

## 8. Summary

The "Users & Permissions" context group currently mixes three distinct domains — user management (operational), access control (configuration), and session monitoring (security) — into a single 8-item sidebar. This creates cognitive overhead and diverges from the consuming app's established pattern of single-concept context groups with dedicated overview dashboards.

**Recommendation**: Split into two new groups (**Users** and **Access**) and relocate **Sessions** to the existing **Security** group. This produces:

- **Users** (5 items): Overview, Users, Invitations, User Groups, User Preferences
- **Access** (3 items): Overview, Roles, Access Control
- **Security** (+1 item): Sessions joins Authentication, Password Policies, MFA, API Tokens, Login Restrictions

Each group maps to a single conceptual domain, each has its own overview dashboard, and the structure mirrors the Organization module's proven pattern of entity-type context groups.