# Navigation UX Backlog — Prioritized "To Be Implemented Later"

> **Source**: [`navigation-ux-analysis.md`](navigation-ux-analysis.md) (2026-08-19)
> **Modules**: Organization, HR, Attendance, Leave, Payroll, Holiday

---

## ✅ Recently Completed (2026-08-19)

| Category | Item |
|---|---|
| Dashboard views | Created missing overview-dashboard views for Attendance, Leave, Payroll, and Holiday |
| Named routes | Added named overview-dashboard routes across all six business modules |
| Dashboard pattern | Aligned all modules on per-context-group overview dashboards |
| Navigation | Holiday module gained a Dashboard/Overview entry as the first item in its `holidays` context group |
| Labels | Normalized HR labels (`Profiles` → `Employee Profiles`, `Current Jobs` → `Positions`) and Payroll `One-Time Adjustments` |
| Reordering | Fixed duplicate `order` values and rationalized Payroll ordering; reordered HR so `people` precedes `Organization` |
| HR People split | Split HR `people` into `people` (daily use) + `manage` (occasional/rare) context groups |

---

## Priority Matrix

| Priority | Category | Suggestion | Effort | Impact |
|---|---|---|---|---|
| **P1 (next)** | Config | Add missing `sidebar` config block to HR, Attendance, Leave, Payroll, Holiday (Organization already has it) | Small | Low |
| **P1 (next)** | Breadcrumbs | Populate `page_title` (currently `NULL` almost everywhere) to improve breadcrumbs and page titles | Small | Medium |
| **P1 (next)** | UX | Enable `open_in_tabs` for workspace tabs so sidebar links open in `WorkspaceTabs` | Small | High |
| **P2 (later)** | Structural | Remove structural entities from HR navigation (Companies, Departments, Locations) — Organization owns them | Medium | High |
| **P2 (later)** | Structural | Fix Attendance contexts: add context groups for orphaned `attendance_adjustment`, `clock_event`, `attendance_session`, or fold into `time`/`policies` | Medium | High |
| **P2 (later)** | Structural | Correct all Attendance routes to `/attendance/...` prefix (currently use `/hr/...`) | Medium | High |
| **P2 (later)** | Structural | Split Payroll into 2–3 context groups or grouped mega menu (Processing vs Configuration) | Medium | High |
| **P2 (later)** | Structural | Trim Organization from 7 to ~5 context groups by merging `classification` and moving `reports` off top nav | Medium | Medium |
| **P2 (later)** | Structural | Merge `shift_schedule` into `shift` in Attendance, or add missing `ShiftSchedule` model | Small | Medium |
| **P2 (later)** | Structural | Adopt Holiday module as golden reference; align every other module's `url`/`route` scheme to it | Large | High |
| **P2 (later)** | Views | Fix Leave: missing `leave-types` index view (referenced in navigation but may 404) | Small | Medium |
| **P2 (later)** | Views | Fix Organization: 3-segment dashboard paths (`/organization/dashboard/organization-summary`, `/growth`, `/recent-changes`) will 404 via catch-all route | Medium | Medium |
| **P2 (later)** | Views | Fix Organization: entities without models/routes (LegalEntity, Region, Country, Address, Tag, Category, Label, CustomField) — remove or stub | Medium | Medium |
| **P2 (later)** | Routes | Fix Attendance single-segment orphaned routes (`/attendance-adjustments`, `/clock-events`, `/attendance-sessions`) that 404 | Small | Medium |
| **P2 (later)** | Routes | Resolve duplicate route definitions between HR and Attendance (`attendance-policies.*`, `attendances.*`, `work-patterns.*`) | Medium | High |
| **P3 (nice-to-have)** | UX | Add global command palette (⌘K) — search records (employees, requests, runs) and nav items | Large | High |
| **P3 (nice-to-have)** | UX | Promote grouped mega menus for Payroll and Organization (extend `HorizontalContextMenu::showAllContexts` from flat list to grouped columns) | Large | Medium |
| **P3 (nice-to-have)** | UX | Add breadcrumbs with populated `page_title` values | Medium | Medium |
| **P3 (nice-to-have)** | Architecture | Unify three duplicated `resolveNavigationConfigPath()` implementations into one shared service/helper | Medium | Low |
| **P3 (nice-to-have)** | Architecture | Unify permission filtering — ensure `NavigationLayout` context-item rendering applies same `permission`/`gate` checks as `NavigationManager` | Medium | Medium |
| **P3 (nice-to-have)** | Architecture | Make context membership explicit in config (`context` key on items) to make orphaned contexts a runtime error rather than silent disappearance | Medium | Low |
| **P3 (nice-to-have)** | Architecture | Add module-registry audit — verify all 6 business modules are registered in consuming-app config (only 4 of 6 explicitly listed) | Small | Medium |
| **P3 (nice-to-have)** | UX | Add visual distinction between "module" and "context" — both are dropdown/tab-like controls in same bar | Medium | Low |
| **P3 (nice-to-have)** | UX | Collapse low-priority Payroll entities (`Payslip Items`, `Policy Assignments` at `order: 999`) behind progressive disclosure | Small | Low |

---

## Summary

| Priority | Count | Total Effort |
|---|---|---|
| P0 (now) | 0 (completed) | — |
| P1 (next) | 3 | Small–Trivial |
| P2 (later) | 12 | Small–Large |
| P3 (nice-to-have) | 9 | Small–Large |
| **Total** | **24** | |

### Quickest Wins (under 30 minutes total)

1. Add `sidebar` config blocks to 5 modules (copy-paste from Organization)
2. Enable `open_in_tabs` in config (1 line)
