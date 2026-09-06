# Navigation UX Backlog — Prioritized "To Be Implemented Later"

> **Source**: [`navigation-ux-analysis.md`](navigation-ux-analysis.md) (2026-08-19)
> **Modules**: Organization, HR, Attendance, Leave, Payroll, Holiday

---

## ✅ Recently Completed (2026-08-19 – 2026-08-20)

| Category | Item |
|---|---|
| Dashboard views | Created missing overview-dashboard views for Attendance, Leave, Payroll, and Holiday |
| Named routes | Added named overview-dashboard routes across all six business modules |
| Dashboard pattern | Aligned all modules on per-context-group overview dashboards |
| Navigation | Holiday module gained a Dashboard/Overview entry as the first item in its `holidays` context group |
| Labels | Normalized HR labels (`Profiles` → `Employee Profiles`, `Current Jobs` → `Positions`) and Payroll `One-Time Adjustments` |
| Reordering | Fixed duplicate `order` values and rationalized Payroll ordering; reordered HR so `people` precedes `Organization` |
| HR People split | Split HR `people` into `people` (daily use) + `manage` (occasional/rare) context groups |
| Organization dashboard | Enriched Organization general dashboard with 12 stat cards, 3 charts, 3 trends, 7 lists, 6 action cards (matches Payroll/Attendance/HR) |
| Duplicate Dashboard tab | Fixed duplicate "Dashboard" in top nav — guarded the hardcoded Dashboard tab in `top-nav.blade.php` with `@if (!isset($this->items['dashboard']))` |
| Organization reports 404s | Fixed 404s on `/organization/reports/{companies,departments,locations,growth}` — added 4 routes + 4 views in consuming app |
| Audit context 404s | Fixed 404s on Audit context group pages (Activity Log, Login History, System Events, Exports) — created 4 minimal views in library |
| Report views | Converted 4 Organization report Blade views to minimal placeholder pages matching the audit-view pattern |
| Mobile permission guards | Added `canAccessView()` permission checks to mobile/overflow inline rendering in `top-nav.blade.php` — mobile "More" dropdown now respects same permission model as desktop tabs |
| Admin case-sensitivity | Normalized `'Dashboard'` → `'dashboard'` across Admin `navigation.php` (context_groups + contexts), 6 Blade views, and `top-nav.blade.php` — guard simplified to single `!isset($this->items['dashboard'])` |
| Admin nav split | Split "Users & Permissions" (8 items) → "Users" (5) + "Access" (3); moved Sessions to Security |
| Admin sidebar 404 audit | Audited all Admin sidebar links; created 16 placeholder views + 10 routes; cataloged at `docs/project/admin-placeholder-pages.md` |
| Admin Dashboard link | Fixed Admin Dashboard link: `admin/dashboard-overview` → `admin/dashboard` |
| Admin Dashboard active-state | Normalized active-state to lowercase `context="dashboard"` (was `context="Dashboard"`) across Admin blade views |
| Dashboard titles | Standardized 4 module dashboard titles to `"{Module} Dashboard"` pattern; created naming standard doc |
| Drawer integration | Converted 23 dashboard action cards from `navigate` to `openDrawer` events; fixed `wire:click` → `$dispatch` in `action_card.blade.php` |
| Drawer integration (Task AL) | Converted 21 "Possible" action cards from `navigate` + `url` to `openDrawer` events; changed 13 data table configs from `pages`/`modals` to `drawers` crudType. ⚠️ Some converted cards still do not open the drawer — needs investigation. |
| Quick actions design | Created feature design doc at `docs/project/quick-actions-feature-design.md` |
| Command palette MVP | Built Phase 1 Cmd+K command palette: `ActionRegistry` discovery service, `QuickActionsPanel` Livewire component, modal UI with scoped CSS, vanilla JS with Cmd+K/Ctrl+K listener, client-side filtering, arrow key nav. 8 admin actions in library config; search button in top nav. |
| Quick actions — all modules | Registered 40 new actions across 7 consuming-app modules (System, Organization, HR, Attendance, Leave, Payroll, Holiday) — 48 total actions. |
| Quick actions — fixes | Fixed `array_map` → `foreach` in `normalizeActions()`, single-root blade wrapper, `all()` vs `authorizedFor()` in `loadActions()`, URL path fallback for non-named routes. |
| Quick actions — Phase 2 | Implemented tracking + ranking (Task AE): `user_action_histories` migration + `UserActionHistory` model, `ActionTracker` service (records palette executions), `RankingEngine` (score = 0.6 × recency + 0.4 × frequency, half-life 7 days, saturation 5). Panel orders actions by score; never-executed actions fall to bottom but still appear. |
| Quick actions — Phase 3 | Implemented ⚡ button + dashboard widget (Task AG): `QuickActionsWidgetProcessor` + `quick_actions.blade.php` widget; `TopNav.php` ⚡ dropdown with `loadQuickActions()`/`executeQuickAction()`/`execute-quick-action` listener; `top-nav.blade.php` ⚡ button + "More actions…" link; `WidgetProcessor` registered `quick_actions` type; `ui-library.php` ⚡ button + command palette config defaults. |
| Quick actions — Phase 4 | Implemented favorites/pinning + shortcuts + analytics (Task AI): `user_favorite_actions` migration + `UserFavoriteAction` model, `action-item.blade.php` partial with star toggle + shortcut badge, `QuickActionsPanel.php` `toggleFavorite()` + pinned-at-top ordering, `TopNav.php` favorites-aware ⚡ dropdown, `top-nav.blade.php` first-visit pulse animation, `QuickActionsWidgetProcessor.php` pinned-first widget, `quicker-faster.js` pulse trigger, `ui-library.php` `favorites` config. |
| Quick actions — shortcut fixes | Attempted Cmd+K conflict fix + Cmd+1..9 browser conflict fix (Task AJ): changed sidebar filter to `Cmd+Shift+K` in `quicker-faster.js`, quick launch to `Cmd+Shift+1..9` in `quick-actions.js`, updated shortcut badges in `QuickActionsPanel.php` and footer hint in `quick-actions-panel.blade.php`. **⚠️ Not yet working** — likely stale JS cache in consuming app; tracked as P2 backlog items. |
| Admin dashboard 404s | Added explicit named routes for `admin/dashboard-overview` and `admin/dashboard-security-overview` in `src/Core/Admin/Routes/web.php` |
| System quick-actions | Confirmed System module quick-actions config already exists with 5 actions — no code change needed |
| ESS Phase 1 — My Portal | Implemented Employee Self Service foundation (Task AQ): `my-portal` context group in HR navigation (order 1, roles: `employee, manager`) with 6 sidebar items; ESS dashboard at `/hr/my-portal` with 11 widgets (profile_header, 4× stat, 4× action_card, activity_log, quick_actions); 7 "Self Service" quick actions. No library changes needed. Design doc: [`employee-self-service-design.md`](employee-self-service-design.md). |
| ESS Phase 2 — Employee Views | Implemented employee-scoped views (Task AS): `my-leave.blade.php` (Leave module), `my-attendance.blade.php` (Attendance module), `my-payslips.blade.php` (Payroll module) with routes and navigation sidebar links in `my-portal` context group. No library changes needed. |
| ESS Phase 3 — Interactive Features | Implemented interactive ESS features (Task AT): `ClockEventRecorder` contract + `ClockInOut` Livewire component + Blade view (library); `ClockEventRecorderService` + `AttendanceServiceProvider` binding (consuming); Clock In/Out opens `qf.clock-in-out` in drawer + renders above dashboard. |
| ESS Phase 4 — Notifications & Polish | Implemented notifications + polish (Task AT): `TeamWhoIsOutWidgetProcessor` + Blade view + `CompositeDashboardResolver` (library); `EssNotificationTemplateSeeder` with 12 templates + `team_whos_out` widget in My Portal (consuming). All 4 ESS phases complete. |
| Sidebar link 404 resolution | Fixed all 43 previously-404 sidebar links (Task AW): 30 placeholder views + 9 routes for System module (library), 4 placeholder views + 4 routes for Organization module (consuming). **0 remaining 404s** — 100×200, 52×403. See [`sidebar-link-audit.md`](sidebar-link-audit.md). |
| Data table config audit + optimization | Audited all 36 data table configs across 5 dimensions (Tasks AY–BD): added `switchViews` to 11 configs, corrected default views in 2 configs, fixed add button visibility in 3 configs, optimized default fields to 5–7 per config (7 configs), reviewed row actions (2 configs). |
| Component restoration (Tasks BE–BG) | Restored `qf.employee-detail` and `qf.searchable-employee-dropdown` Livewire components from backup; discovered and registered 6 missing Payroll components in `PayrollServiceProvider.php`; fixed `/employees/1` 404 via [`ResolvesModels.php`](src/Concerns/ResolvesModels.php) company-scoping guard and config key fix in `EmployeeDetail.php`. Total: 8 components restored (2 HR + 6 Payroll). |

---

## 🟢 Active Priority: Per-Module Physical UX Review

> ⚠️ **This is the current active priority.** All other P1/P2/P3 items below are paused until the physical review completes.
>
> The user will physically review each module by clicking through features, links, workflows, dashboards, and data tables. Issues found will be reported and fixed module-by-module before moving to the next.
>
> **Modules to review (in order):**
> - [ ] Admin
> - [ ] System
> - [ ] Organization
> - [ ] HR
> - [ ] Attendance
> - [ ] Leave
> - [ ] Payroll
> - [ ] Holiday
>
> **Status**: In Progress. This is an interactive review process — the user reports issues, the AI fixes them. One module at a time.

---

## Priority Matrix (Paused — see Active Priority above)

| Priority | Category | Suggestion | Effort | Impact |
|---|---|---|---|---|
| **P1 (next)** | Review | 🔄 **Per-Module Physical UX Review** — User clicks through all 8 modules (Admin, System, Organization, HR, Attendance, Leave, Payroll, Holiday), reports issues module-by-module. This takes priority over all other items. | Large | High |
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
| **P2 (later)** | Views | ~~Fix Leave: missing `leave-types` index view~~ ✅ fixed 2026-08-19 (index view + route added) | Small | Medium |
| **P2 (later)** | Views | ~~Fix Organization: 3-segment dashboard paths (`/organization/dashboard/organization-summary`, `/growth`, `/recent-changes`) will 404 via catch-all route~~ ✅ Fixed (Task AW, 2026-08-20) — placeholder views + routes added for all 4 Organization sub-pages | Medium | Medium |
| **P2 (later)** | Views | Fix Organization: entities without models/routes (LegalEntity, Region, Country, Address, Tag, Category, Label, CustomField) — remove or stub | Medium | Medium |
| **P2 (later)** | Routes | Fix Attendance single-segment orphaned routes (`/attendance-adjustments`, `/clock-events`, `/attendance-sessions`) that 404 | Small | Medium |
| **P2 (later)** | Routes | Resolve duplicate route definitions between HR and Attendance (`attendance-policies.*`, `attendances.*`, `work-patterns.*`) | Medium | High |
| **P2 (later)** | JS | Fix sidebar search keyboard shortcut (`Cmd+Shift+K`) — changed from `Cmd+K` to `Cmd+Shift+K` in [`quicker-faster.js`](public/assets/js/quicker-faster.js) but still opens quick actions instead of sidebar search. Likely stale JS asset cache in consuming app. | Small | Medium |
| **P2 (later)** | JS | Fix quick launch keyboard shortcuts (`Cmd+Shift+1..9`) — changed from `Cmd+1..9` to `Cmd+Shift+1..9` in [`quick-actions.js`](public/assets/js/quick-actions.js) but shortcuts don't trigger. Likely same stale JS cache issue or `e.shiftKey` check mismatch. | Small | Medium |
| **P2 (later)** | Drawers | Fix non-working drawer action cards from Task AL — some of the 21 converted "Possible" action cards still do not open the drawer. Likely causes: missing `crudType` on the data table config, incorrect `configKey`, or the entity's form doesn't support drawer rendering. Needs investigation per card. Follow-up to Task AL. | Medium | High |
| **P3 (nice-to-have)** | UX | ~~Add global command palette (⌘K)~~ — **All 4 phases implemented**: Cmd+K command palette with 48 actions across all 8 modules; actions ranked by score = 0.6 × recency + 0.4 × frequency (half-life 7 days, saturation 5); top-nav ⚡ dropdown button with ranked actions; `quick_actions` dashboard widget; user favorites/pinning with star toggle; keyboard shortcut badges; first-visit pulse animation. ⚠️ Two keyboard shortcut fixes (Cmd+Shift+K sidebar filter, Cmd+Shift+1..9 quick launch) attempted but not yet working — tracked as P2 items above. | Large | High |
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
| 🟢 Active | 1 (Per-Module Physical UX Review) | Large |
| P1 (next) | 3 | Small–Trivial |
| P2 (later) | 14 | Small–Large |
| P3 (nice-to-have) | 9 | Small–Large |
| **Total** | **27** | |

### Quickest Wins (under 30 minutes total)

1. Add `sidebar` config blocks to 5 modules (copy-paste from Organization)
2. Enable `open_in_tabs` in config (1 line)
