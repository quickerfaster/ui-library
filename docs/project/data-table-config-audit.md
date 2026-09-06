# Data Table Config Audit & UX Recommendations

> **Audit Date:** 2026-08-20  
> **Scope:** All `*/Data/*.php` configs across library (`src/Core/`) and consuming app (`app/Modules/`)  
> **Status:** Analysis only — no code modifications  

---

## 1. Config Structure Reference

Two distinct config formats coexist in the codebase:

### Format A — Legacy (Organization-style)

Used by: All Organization module configs, `system_setting.php`

| Key | Purpose | Example Values |
|-----|---------|---------------|
| `controls.create` | Add button visibility | `true` / `false` |
| `controls.edit` | Edit row action | `true` / `false` |
| `controls.delete` | Delete row action | `true` / `false` |
| `controls.view` | View row action | `true` / `false` |
| `columns.{field}.sortable` | Sortable flag per column | `true` / `false` |
| `columns.{field}.searchable` | Searchable flag per column | `true` / `false` |
| `columns.{field}.visible` | Default visibility per column | `true` / `false` |

**Missing:** No `switchViews`, no `tableDefaultFields`, no `simpleActions`, no `moreActions`.

### Format B — Modern (HR-style)

Used by: Admin, HR, Attendance, Leave, Payroll, Holiday modules

| Key | Purpose | Example Values |
|-----|---------|---------------|
| `controls.addButton` | Add button config | `true`, `false`, or array of button definitions |
| `simpleActions` | Row quick actions | `['show', 'edit', 'delete']` or `[]` |
| `moreActions` | Additional row actions (dropdown) | Array of action definitions |
| `switchViews.default` | Default view mode | `'table'`, `'list'`, `'card'` |
| `switchViews.table.enabled` | Table view toggle | `true` / `false` |
| `switchViews.list.enabled` | List view toggle + field mappings | `true` / `false` + `titleFields`, `subtitleFields`, `badgeField` |
| `switchViews.card.enabled` | Card view toggle + field mappings | `true` / `false` + `titleFields`, `subtitleFields`, `contentFields` |
| `tableDefaultFields` | Default visible columns (ordered) | `['name', 'email', 'status', ...]` |
| `fieldDefinitions.{field}.searchable` | Searchable flag per field | `true` / `false` |
| `fieldDefinitions.{field}.filterable` | Filterable flag per field | `true` / `false` |
| `fieldDefinitions.{field}.sortable` | Sortable flag per field | `true` / `false` |

---

## 2. Per-Module Audit Tables

### Legend

| Symbol | Meaning |
|--------|---------|
| ✅ | Already correct |
| ❌ | Needs change |
| ⚠️ | Partially correct / needs review |
| — | Not applicable / not present |

---

### 2.1 Admin Module (`src/Core/Admin/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 1 | User | [`user.php`](src/Core/Admin/Data/user.php) | ✅ `true` | ✅ `list` | ✅ show/edit/delete | ✅ 4 fields | ✅ per-field | **No** |
| 2 | Role | [`role.php`](src/Core/Admin/Data/role.php) | ✅ `create: true` | ❌ **none** | ✅ show/edit/delete | ⚠️ 2 fields (too few) | ✅ per-column | **Yes** — add switchViews, expand defaults |
| 3 | Permission | [`permission.php`](src/Core/Admin/Data/permission.php) | ❌ **none** | ❌ **none** | ❌ **none** | ❌ **none** | ❌ **none** | **Yes** — minimal stub, needs full config |
| 4 | Notification Log | [`notification_log.php`](src/Core/Admin/Data/notification_log.php) | ✅ `false` | ✅ `table` | ✅ none | ✅ 6 fields | ✅ per-field | **No** |
| 5 | Workflow Definition | [`workflow_definition.php`](src/Core/Admin/Data/workflow_definition.php) | ❌ `false` → **true** | ⚠️ `table` → **list** | ❌ none → **show** | ✅ 4 fields | ✅ per-field | **Yes** — add button, view, list default |

**Admin Summary:** 3 of 5 data-table configs need changes.

---

### 2.2 System Module (`src/Core/System/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 6 | System Setting | [`system_setting.php`](src/Core/System/Data/system_setting.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ✅ 5 visible columns | ✅ per-column | **Yes** — add switchViews |

**System Summary:** 1 of 1 data-table configs need changes.

---

### 2.3 Organization Module (`app/Modules/Organization/Data/`)

All Organization configs use **Format A (Legacy)**. They all lack `switchViews`, `tableDefaultFields`, `simpleActions`, and `moreActions`.

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 7 | Company | [`company.php`](app/Modules/Organization/Data/company.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ⚠️ 8 visible (too many) | ✅ per-column | **Yes** — add switchViews, reduce defaults |
| 8 | Department | [`department.php`](app/Modules/Organization/Data/department.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ✅ 7 visible | ✅ per-column | **Yes** — add switchViews |
| 9 | Branch | [`branch.php`](app/Modules/Organization/Data/branch.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ⚠️ 8 visible (too many) | ✅ per-column | **Yes** — add switchViews, reduce defaults |
| 10 | Business Unit | [`business_unit.php`](app/Modules/Organization/Data/business_unit.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ✅ 6 visible | ✅ per-column | **Yes** — add switchViews |
| 11 | Division | [`division.php`](app/Modules/Organization/Data/division.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ✅ 7 visible | ✅ per-column | **Yes** — add switchViews |
| 12 | Location | [`location.php`](app/Modules/Organization/Data/location.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ⚠️ 8+ visible (too many) | ✅ per-column | **Yes** — add switchViews, reduce defaults |
| 13 | Team | [`team.php`](app/Modules/Organization/Data/team.php) | ✅ `create: true` | ❌ **none** | ✅ view/edit/delete | ⚠️ 8 visible (too many) | ✅ per-column | **Yes** — add switchViews, reduce defaults |

**Organization Summary:** 7 of 7 configs need changes (all need `switchViews`; 4 need default field reduction).

---

### 2.4 HR Module (`app/Modules/Hr/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 14 | Employee | [`employee.php`](app/Modules/Hr/Data/employee.php) | ✅ wizard + quick_add | ❌ `table` → **list** | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — change default to list |
| 15 | Employee Group | [`employee_group.php`](app/Modules/Hr/Data/employee_group.php) | ✅ quick_add | ❌ **none** | ✅ show/edit/delete | ✅ 5 fields | ✅ per-field | **Yes** — add switchViews |
| 16 | Employee Job History | [`employee_job_history.php`](app/Modules/Hr/Data/employee_job_history.php) | ❌ **none** | ❌ **none** | ✅ show/edit/delete | ⚠️ needs review | ✅ per-field | **Yes** — add button, switchViews |
| 17 | Employee Position | [`employee_position.php`](app/Modules/Hr/Data/employee_position.php) | ⚠️ needs review | ⚠️ needs review | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — needs full review |
| 18 | Job Title | [`job_title.php`](app/Modules/Hr/Data/job_title.php) | ✅ `true` | ✅ `list` | ✅ show/edit/delete | ⚠️ 3 fields (too few) | ✅ per-field | **Yes** — expand defaults to 5-7 |
| 19 | Tag | [`tag.php`](app/Modules/Hr/Data/tag.php) | ✅ quick_add | ✅ `list` | ✅ show/edit/delete | ✅ 4 fields | ✅ per-field | **No** |
| 20 | Document | [`document.php`](app/Modules/Hr/Data/document.php) | ✅ `true` | ❌ **none** | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — add switchViews (card view ideal) |

**HR Summary:** 7 of 8 configs need changes.

---

### 2.5 Attendance Module (`app/Modules/Attendance/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 21 | Attendance | [`attendance.php`](app/Modules/Attendance/Data/attendance.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — system-generated, hide add; add switchViews |
| 22 | Attendance Policy | [`attendance_policy.php`](app/Modules/Attendance/Data/attendance_policy.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |
| 23 | Shift | [`shift.php`](app/Modules/Attendance/Data/shift.php) | ✅ `true` | ❌ **none** | ✅ show/edit/delete | ✅ 7 fields | ✅ per-field | **Yes** — add switchViews |
| 24 | Clock Event | [`clock_event.php`](app/Modules/Attendance/Data/clock_event.php) | ❌ **none** → **false** | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — system log, hide add; add switchViews |
| 25 | Work Pattern | [`work_pattern.php`](app/Modules/Attendance/Data/work_pattern.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |
| 26 | Attendance Adjustment | [`attendance_adjustment.php`](app/Modules/Attendance/Data/attendance_adjustment.php) | ✅ quick_add | ❌ **none** | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — add switchViews |

**Attendance Summary:** 6 of 6 configs need changes.

---

### 2.6 Leave Module (`app/Modules/Leave/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 27 | Leave Request | [`leave_request.php`](app/Modules/Leave/Data/leave_request.php) | ✅ wizard + quick_add | ❌ **none** | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — add switchViews |
| 28 | Leave Type | [`leave_type.php`](app/Modules/Leave/Data/leave_type.php) | ✅ `true` | ❌ **none** | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — add switchViews |
| 29 | Leave Balance | [`leave_balance.php`](app/Modules/Leave/Data/leave_balance.php) | ✅ `true` | ❌ **none** | ✅ show/edit/delete | ✅ 5 fields | ✅ per-field | **Yes** — add switchViews |
| 30 | Leave Approver | [`leave_approver.php`](app/Modules/Leave/Data/leave_approver.php) | ✅ `true` | ❌ **none** | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — add switchViews |

**Leave Summary:** 4 of 4 configs need changes.

---

### 2.7 Payroll Module (`app/Modules/Payroll/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 31 | Payroll Run | [`payroll_run.php`](app/Modules/Payroll/Data/payroll_run.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |
| 32 | Payroll Policy | [`payroll_policy.php`](app/Modules/Payroll/Data/payroll_policy.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |
| 33 | Pay Schedule | [`pay_schedule.php`](app/Modules/Payroll/Data/pay_schedule.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |
| 34 | Payroll Payslip | [`payroll_payslip.php`](app/Modules/Payroll/Data/payroll_payslip.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |

**Payroll Summary:** 4 of 4 configs need changes.

---

### 2.8 Holiday Module (`app/Modules/Holiday/Data/`)

| # | Entity | File | Add Button | Default View | Row Actions | Default Fields | Search/Sort | Needs Changes? |
|---|--------|------|------------|-------------|-------------|---------------|-------------|---------------|
| 35 | Holiday | [`holiday.php`](app/Modules/Holiday/Data/holiday.php) | ⚠️ needs review | ❌ **none** | ⚠️ needs review | ⚠️ needs review | ✅ per-field | **Yes** — add switchViews |
| 36 | Holiday Calendar | [`holiday_calendar.php`](app/Modules/Holiday/Data/holiday_calendar.php) | ✅ `true` | ❌ **none** (has calendarView) | ✅ show/edit/delete | ✅ 6 fields | ✅ per-field | **Yes** — add switchViews alongside calendarView |

**Holiday Summary:** 2 of 2 configs need changes.

---

## 3. Summary Statistics

### Total Configs Audited: **36** (excluding dashboards and wizards)

### Configs Needing Changes: **34 of 36** (94%)

### Breakdown by Dimension

| Dimension | Configs Needing Change | % of Total |
|-----------|----------------------|------------|
| **1. Add Button Visibility** | 5 | 14% |
| **2. Default View / switchViews** | 32 | 89% |
| **3. Row Quick Actions** | 4 | 11% |
| **4. Default Fields** | 8 | 22% |
| **5. Searchable / Sortable Flags** | 1 | 3% |

### Configs Already Correct (No Changes Needed)

| # | Entity | Module |
|---|--------|--------|
| 1 | User | Admin |
| 2 | Notification Log | Admin |
| 3 | Tag | HR |

### Key Finding

**The single biggest gap is `switchViews` (89% of configs).** Only 4 of 36 configs define `switchViews`:
- [`user.php`](src/Core/Admin/Data/user.php) — list default, table enabled, card disabled
- [`notification_log.php`](src/Core/Admin/Data/notification_log.php) — table default, list enabled
- [`workflow_definition.php`](src/Core/Admin/Data/workflow_definition.php) — table default, list enabled
- [`employee.php`](app/Modules/Hr/Data/employee.php) — table default, list + card enabled
- [`job_title.php`](app/Modules/Hr/Data/job_title.php) — list default, table + card enabled
- [`tag.php`](app/Modules/Hr/Data/tag.php) — list default, table + list enabled

---

## 4. Implementation Priority

### Priority 1 — Critical UX Gaps (add `switchViews` to all configs)

These configs have **no view switching at all**, forcing users into a single dense table view:

**Organization (7 configs):** company, department, branch, business_unit, division, location, team  
**HR (4 configs):** employee_group, employee_job_history, employee_position, document  
**Attendance (6 configs):** attendance, attendance_policy, shift, clock_event, work_pattern, attendance_adjustment  
**Leave (4 configs):** leave_request, leave_type, leave_balance, leave_approver  
**Payroll (4 configs):** payroll_run, payroll_policy, pay_schedule, payroll_payslip  
**Holiday (2 configs):** holiday, holiday_calendar  
**System (1 config):** system_setting  
**Admin (1 config):** role

**Total: 29 configs**

### Priority 2 — Default View Corrections

| Entity | Current | Recommended | Rationale |
|--------|---------|-------------|-----------|
| Employee | `table` | `list` | Photo-driven, high cognitive load in table |
| Workflow Definition | `table` | `list` | Configuration entity, benefits from scannable list |
| All Organization entities | none | `list` | Reference data, list reduces cognitive load |
| All HR entities | none | `list` | People-driven data |
| Attendance | none | `table` | Dense operational data |
| Clock Event | none | `table` | System log, dense data |
| Payroll Run | none | `table` | Financial data, needs precision |
| Payroll Payslip | none | `table` | Financial data, needs precision |
| Leave Request | none | `list` | Workflow-driven, status-focused |
| Holiday | none | `list` | Calendar-driven, date-focused |

### Priority 3 — Add Button Corrections

| Entity | Current | Recommended | Rationale |
|--------|---------|-------------|-----------|
| Workflow Definition | `false` | `true` | User-managed configuration |
| Permission | none | `true` | User-managed (Spatie) |
| Clock Event | none | `false` | System-generated log |
| Attendance | needs review | `false` | System-generated records |
| Employee Job History | none | `true` | User-managed history |

### Priority 4 — Default Field Corrections

| Entity | Current Count | Recommended Count | Action |
|--------|--------------|-------------------|--------|
| Role | 2 | 5-7 | Add: `guard_name`, `created_at`, `permissions_count` |
| Permission | 0 | 5-7 | Add: `name`, `guard_name`, `created_at` |
| Job Title | 3 | 5-7 | Add: `is_active`, `created_at` |
| Company | 8 | 5-7 | Remove: `phone`, `city`, `country` (keep in detail) |
| Branch | 8 | 5-7 | Remove: `city`, `country_code`, `is_headquarters` |
| Location | 8+ | 5-7 | Remove: `type`, `city`, `country_code` |
| Team | 8 | 5-7 | Remove: `type`, `department_id` |

### Priority 5 — Row Action Corrections

| Entity | Current | Recommended |
|--------|---------|-------------|
| Workflow Definition | none | `['show']` (edit via wizard in moreActions) |
| Permission | none | `['show', 'edit', 'delete']` |
| Clock Event | needs review | `['show']` only (system log) |

---

## 5. UX Rationale

### 5.1 Add New Button Visibility

**Principle: Progressive Disclosure**

- **Show** for entities users actively create and manage: employees, departments, leave types, job titles, shifts, policies, tags, documents, companies, branches, locations, teams, divisions, business units, roles, permissions, workflow definitions, payroll policies, pay schedules, holiday calendars, holidays, leave approvers
- **Hide** for system-generated or read-only entities: notification logs, clock events, attendance records (auto-generated from clock events), payroll runs (generated via wizard), payroll payslips (generated from runs), leave balances (auto-calculated), attendance adjustments (corrective, not additive)

### 5.2 Default Table View

**Principle: Cognitive Load Reduction**

| View | Best For | Why |
|------|----------|-----|
| **List** | Most entities | Reduces visual scanning effort; shows key info (title, subtitle, status badge) in a compact card-like row; ideal for browsing and quick identification |
| **Card** | Photo/icon-driven entities | Employees, users, documents — where a visual identifier (photo, icon) adds immediate recognition value |
| **Table** | Power users, dense data | Logs, audit trails, financial data, exports — where precision, sorting, and column comparison matter more than scannability |

**Default recommendations by entity type:**
- **People** (employees, users): `list` (with card available)
- **Reference data** (departments, companies, branches, job titles, tags, shifts, policies, leave types, holiday calendars): `list`
- **Transactional** (leave requests, attendance adjustments): `list`
- **Operational logs** (clock events, notification logs, attendance): `table`
- **Financial** (payroll runs, payslips): `table`
- **Configuration** (workflow definitions, system settings, roles, permissions): `list`

### 5.3 Row Quick Actions

**Principle: Contextual Appropriateness & Safety**

| Action | When to Enable | When to Disable |
|--------|---------------|-----------------|
| **View** | Always | Never — every entity benefits from a detail view |
| **Edit** | User-managed entities | System logs, auto-generated records, read-only snapshots |
| **Delete** | User-managed entities with no cascade risk | Entities with cascade dependencies, system-critical records, logs |
| **More** | When additional actions exist beyond view/edit/delete | When no extra actions are needed |

**Safety note:** Delete should be hidden for:
- `notification_log` — system log, no user deletion
- `clock_event` — audit trail, legal/compliance implications
- `attendance` — generated from clock events, should be adjusted not deleted
- `payroll_payslip` — financial record, legal retention requirements
- `leave_balance` — auto-calculated, should not be manually deleted

### 5.4 Default Fields

**Principle: Performance & Information Density**

- **5-7 fields** for table view — balances information density with load performance
- **3-4 fields** for list view — title, subtitle, status badge, date
- Prioritize: name/title → status → dates → key relationships
- De-prioritize: descriptions, notes, audit fields (created_by, updated_by), technical fields (IDs when names are shown)

### 5.5 Searchable & Sortable Flags

**Principle: Performance & Usability**

| Flag | Best For | Avoid For |
|------|----------|-----------|
| **searchable** | Text fields (name, email, description, notes), ID fields, code fields | Binary data, file paths, computed fields, JSON columns, encrypted data |
| **sortable** | Date fields, numeric fields, status fields, name fields | Large text/blobs, JSON columns, computed/virtual columns |
| **filterable** | Enum/status fields, foreign keys, boolean flags | Free-text descriptions (use searchable instead) |

---

## 6. Migration Strategy

### Phase 1: Add `switchViews` to All Configs (29 configs)

This is the highest-impact, lowest-risk change. Each config needs:

```php
'switchViews' => [
    'default' => 'list',  // or 'table' per recommendations above
    'table' => ['enabled' => true],
    'list' => [
        'enabled' => true,
        'titleFields' => ['name'],           // primary identifier
        'subtitleFields' => ['code', 'email'], // secondary info
        'badgeField' => 'is_active',          // status indicator
        'badgeColors' => [
            '1' => 'success',
            '0' => 'secondary',
        ],
    ],
    'card' => [
        'enabled' => false,  // true for photo-driven entities
    ],
],
```

### Phase 2: Correct Default Views (5 configs)

Change `switchViews.default` on: employee, workflow_definition, and any others where the current default doesn't match the recommendation.

### Phase 3: Fix Add Buttons (5 configs)

Add or correct `controls.addButton` for: workflow_definition, permission, clock_event, attendance, employee_job_history.

### Phase 4: Optimize Default Fields (8 configs)

Reduce visible columns for: role, permission, job_title, company, branch, location, team.

### Phase 5: Review Row Actions (3 configs)

Add missing actions for: workflow_definition, permission, clock_event.

---

## 7. Config Format Consistency Note

The codebase has two config formats (Legacy Format A and Modern Format B). The Organization module exclusively uses Format A, while all other modules use Format B. A future initiative could normalize all configs to Format B for consistency, but this is outside the scope of this audit.

---

*Audit completed by Architect mode. No code was modified.*