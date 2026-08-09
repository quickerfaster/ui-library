# Input3.txt Further Analysis — Gap Supplement

> Cross-referencing [`src/input3.txt`](src/input3.txt) (~5800 lines) against [`docs/gap-analysis.md`](docs/gap-analysis.md) and Phases 1 & 2 implementation  
> Date: 2026-08-07

---

## 1. Features Unique to input3.txt

These items appear in `input3.txt` but were NOT present in `input.txt` or `input2.txt`.

### 1.1 Architectural Philosophy

| # | New Concept | Description | Category |
|---|---|---|---|
| U1 | **Capability-based module organization** | Modules organized by business capability (Organization, Security, Workflow) not business domain (HR, Accounting) | Architecture |
| U2 | **Layered dependency model** | System → Security → Organization → Business Modules → Workflow → Notifications → Reporting | Architecture |
| U3 | **"Module" → "Application" terminology** | User-facing term should be "Applications" not "Modules". Developers think in modules, users think in applications. | Naming |
| U4 | **Infrastructure vs User-facing applications** | Hide Workflow, Notifications, Audit, Files from the module switcher. Only show Organization, HR, Payroll, Inventory, etc. | Architecture |
| U5 | **Config-driven navigation metadata** | Each module should declare `Module → workspaces()`, `Workspace → pages()`, `Page → actions()` as PHP config | Architecture |

### 1.2 Navigation Architecture Enhancements

| # | New Concept | Description | Category |
|---|---|---|---|
| U6 | **Section-based sidebar grouping** | Sidebar items grouped into sections with visual headers (Employment, Organization, Configuration) when workspace grows beyond 8-10 pages | UI Library |
| U7 | **Navigation hierarchy formalized** | Application → Workspace → Section → Page → Record (5 levels, up from 4 in input.txt) | UI Library |
| U8 | **Application switcher UX change** | Replace icon-based module switcher with dropdown showing application names (like Google Workspace app launcher) | UI Library |
| U9 | **Workspace as top-center tabs** | TopNav should show workspace names, not just context group labels | UI Library |

### 1.3 Detailed Application Navigation Trees (7 Applications)

These comprehensive navigation trees are entirely new — `input.txt` only had high-level workspace names.

| # | Application | Workspaces Designed | New Detail Level |
|---|---|---|---|
| U10 | **System** | 6 workspaces: Dashboard, Accounts, Subscriptions, Plans, Applications, Settings | Full sidebar for each workspace (~30 sidebar items) |
| U11 | **Administration** | 5 workspaces: Dashboard, Users, Access Control, Security, Audit | Full sidebar for each workspace (~25 sidebar items) |
| U12 | **Organization** | 6 workspaces: Dashboard, Companies, Structure, Locations, Classification, Reports | Full sidebar for each workspace (~30 sidebar items) |
| U13 | **HR** | 6 workspaces: Dashboard, People, Employment, Development, Documents, Reports | Full sidebar for each workspace (~35 sidebar items) |
| U14 | **Time** | 6 workspaces: Dashboard, Scheduling, Attendance, Time Tracking, Adjustments, Reports | Full sidebar for each workspace (~30 sidebar items) |
| U15 | **Leave** | 6 workspaces: Dashboard, Requests, Policies, Balances, Calendar, Reports | Full sidebar for each workspace (~30 sidebar items) |
| U16 | **Payroll** | 7 workspaces: Dashboard, Employees, Compensation, Processing, Compliance, Payments, Reports | Full sidebar for each workspace (~35 sidebar items) |

### 1.4 Domain Model Recommendations

| # | New Concept | Description | Category |
|---|---|---|---|
| U17 | **Department belongs to Organization, NOT HR** | Department exists even with zero employees. Accounting, Inventory, CRM all need Department. | Architecture |
| U18 | **Job Title belongs to HR only** | Only employees have job titles. Accounting, Inventory, CRM never need Job Title. | Architecture |
| U19 | **Shift belongs to HR/Time** | No reason Inventory should install Shift. Move to HR/Time module. | Architecture |
| U20 | **Company stays in Organization** | Almost every business application needs Company. Shared dependency. | Architecture |
| U21 | **Location belongs to Organization** | Company sites, warehouses, branches, offices, stores. Used across many domains. | Architecture |
| U22 | **Person/Employee model split** | A Person exists independently of employment. Employee is a role a Person has at a Company. Enables rehires, multi-employment. | Architecture |
| U23 | **Payroll Profile ≠ Employee** | Employee belongs to HR. Payroll Profile belongs to Payroll. Critical separation. | Architecture |
| U24 | **Clock Events as immutable source of truth** | Never edit attendance directly. Calculate attendance, overtime, payroll hours from immutable Clock Events. | Architecture |

### 1.5 Workflow & Engine Recommendations

| # | New Concept | Description | Category |
|---|---|---|---|
| U25 | **Leave approvals via Workflow Engine** | Don't build leave_approvals tables. Leave Requests become workflow-enabled documents. Same engine for Procurement, Expense, Travel, Recruitment. | Architecture |
| U26 | **Payroll calculation-first design** | Make Payroll Calculation the heart, not Payroll Run. Run becomes orchestration. Enables preview, simulation, recalculation. | Architecture |
| U27 | **Config-driven navigation generation** | Application switcher, top nav, sidebar, breadcrumbs, permissions, and global search all generated from same metadata | UI Library |

### 1.6 Capability Metadata Concept

| # | New Concept | Description | Category |
|---|---|---|---|
| U28 | **Capability page metadata** | Each sidebar page should declare: `capability`, `page_type` (CRUD/bulk/approval/dashboard), `actions`, `permissions`, `data_source` | Configuration |
| U29 | **Configurable organizational nodes** | Organization module should let users define their own structure types (Company, Division, Department, Unit) via config rather than hardcoded tables | Configuration |
| U30 | **Module dependency declaration** | Each module should declare its dependencies. HR depends on Organization. Inventory depends on Organization. Payroll depends on HR, Time, Leave. | Configuration |

---

## 2. HR Artifacts Still Present

`input3.txt` contains these HR/employee-specific references that may indicate remaining coupling or business-module scope.

| # | Reference | Location in input3.txt | Classification | Decoupling Status |
|---|---|---|---|---|
| H1 | Employee model referenced throughout HR, Time, Leave, Payroll designs | HR Application design | **HR Module** — belongs in `app/Modules/Hr/` | ✅ Already stays in HR app |
| H2 | Payroll Profile, Salary Structure, Bank Accounts | Payroll Employees workspace | **Payroll Module** — belongs in `app/Modules/Payroll/` | ✅ Business module, not library |
| H3 | Clock Events, Attendance, Overtime | Time Application design | **Time Module** — belongs in `app/Modules/Time/` | ✅ Business module, not library |
| H4 | Leave Types, Leave Policies, Leave Requests | Leave Application design | **Leave Module** — belongs in `app/Modules/Leave/` | ✅ Business module, not library |
| H5 | Person/Employee model split recommendation | HR design section | **Architecture** — affects HR module model design | ✅ Design guidance, not code coupling |
| H6 | Job Title, Employment Type, Shift belonging to HR | Module organization section | **Architecture** — confirms these stay in HR | ✅ Already in HR app |

**Assessment**: Unlike `input.txt` and `input2.txt`, `input3.txt` is a pure architectural design document with no code references to HR-specific paths, controllers, or services. All HR-related content is correctly scoped as business module design guidance. **No new decoupling actions required.**

---

## 3. UI Library Gaps Filled or Newly Identified

### 3.1 Gaps from `gap-analysis.md` Addressed by input3.txt

| Gap ID | Gap Description | input3.txt Contribution | Status |
|---|---|---|---|
| G-2.3.1 | Application → Workspace → Sidebar hierarchy not implemented | Full specification of the hierarchy with Section level added. Detailed navigation trees for all 7 applications. | ✅ **Designed** — Implementation still needed |
| G-2.1.1 | Generic Workflow Engine missing | Confirms Leave should use Workflow Engine for approvals. Specifies: Leave Request → Workflow Definition → Workflow Instance → Approvals → Completed | ✅ **Validated** — Strengthens case for Phase 3 |
| G-2.1.5 | Reference Data module not started | System module design includes Currency, Language, Country, State, Timezone as System-level data | ✅ **Partially addressed** — System could house reference data |
| C4 | Application Switcher vs Module Switcher mismatch | Explicitly recommends dropdown-style app switcher labeled with current app name. "I would NOT put every installed module there... hide framework internals." | ✅ **Validated** — UX change needed |
| C5 | Platform vs UI Library naming | Strongly reinforces "Application Platform" mindset. "That is much more than a UI library." Navigation philosophy reflects this. | ✅ **Validated** — Rename still deferred |

### 3.2 New UI Library Gaps Identified

| # | New Gap | Description | Priority |
|---|---|---|---|
| N1 | **Section-based sidebar rendering** | Current sidebar renders flat items. input3.txt calls for section headers (Employment, Organization, Configuration) when items exceed 8-10. | P2 |
| N2 | **Config-driven navigation metadata structure** | Current nav config has `context_groups` and `contexts`. input3.txt proposes a richer `Module → workspaces() → pages() → actions()` metadata model that the UI library reads to render all navigation. | P1 |
| N3 | **Application switcher dropdown UX** | Current ModuleSwitcher uses icon buttons. input3.txt wants a dropdown showing application names. | P2 |
| N4 | **Infrastructure module filtering** | ModuleSwitcher currently shows all modules. input3.txt says hide Workflow, Notifications, Audit, Files — only show user-facing applications. | P2 |
| N5 | **Workspace tabs in TopNav** | Current TopNav shows context items. input3.txt wants workspace names as top-center tabs. | P3 |
| N6 | **5-level breadcrumb support** | Current supports Application → Workspace → Page. input3.txt adds Section level. | P3 |

---

## 4. Cross-Cutting Concerns & Dependencies

### 4.1 Dependency Graph from input3.txt

```
             System
                │
         Security/Admin
                │
         Organization
                │
     ┌──────────┼─────────┐
     │          │         │
    HR     Inventory   Accounting
     │          │         │
     └──────────┼─────────┘
          Workflow Engine
                │
        Notifications
                │
           Reporting
```

### 4.2 Module Dependency Matrix

| Module | Depends On | Depended On By |
|---|---|---|
| System | — | Everything |
| Security/Admin | System | Everything |
| Organization | System, Security | HR, Inventory, Accounting, CRM, Payroll, Procurement, Projects |
| HR | Organization | Payroll, Time |
| Time | HR, Organization | Payroll, Leave |
| Leave | HR, Time, Organization | Payroll |
| Payroll | HR, Time, Leave, Organization | — |
| Workflow | — (cross-cutting) | Leave, Payroll, Procurement, Recruitment |
| Notifications | — (cross-cutting) | Everything |
| Reporting | — (cross-cutting) | Everything |

### 4.3 What the Current Library Provides vs What input3.txt Expects

| input3.txt Expectation | Current Library State | Gap |
|---|---|---|
| Module → Workspace → Section → Page hierarchy | Module → ContextGroup → ContextItem (3 levels) | Missing Section level |
| Config-driven nav generation from metadata | Manual nav config in `Config/navigation.php` | Missing metadata structure |
| Dropdown app switcher with labels | Icon-based button switcher | UX difference |
| Infrastructure module filtering | Shows all modules | Missing `user_facing` flag |
| Workflow engine for cross-module approvals | Only basic ApprovalEngine | Major gap |
| Organization as shared Core module | Organization lives in HR app | Extraction needed |

---

## 5. Decoupling Opportunities & Risks

### 5.1 Opportunities

| # | Opportunity | Rationale |
|---|---|---|
| O1 | **Organization module extraction into Core** | input3.txt confirms Organization is the most shared dependency across all business modules. Extracting it into `src/Core/Organization/` would be the highest-ROI Phase 3 task. |
| O2 | **Navigation metadata structure** | The `Module → workspaces() → pages()` config structure could become a new contract in the library, allowing any module to declare its navigation declaratively. |
| O3 | **Infrastructure vs User-facing module flag** | Add `user_facing` boolean to module registry. ModuleSwitcher filters by this flag. Solves the "hide framework internals" requirement. |
| O4 | **Workflow Engine as a Core service** | input3.txt confirms Workflow is cross-cutting. Building it as a library service (`src/Services/Workflow/`) rather than a business module makes it available everywhere. |

### 5.2 Risks

| # | Risk | Mitigation |
|---|---|---|
| R1 | **Navigation redesign is breaking** | The Section-based sidebar and 5-level breadcrumb would require changes to NavigationLayout, TopNav, Sidebar, and all navigation configs. Phase this gradually. |
| R2 | **Organization module extraction touches many files** | Organization models (Company, Department, Location) are referenced across HR, Time, Leave, Payroll. Namespace changes cascade. Do this before adding new business modules. |
| R3 | **Config-driven nav generation is complex** | The metadata model proposed is richer than current config. Start with a simple version that just declares workspaces and pages, add sections and actions later. |
| R4 | **"Application" vs "Module" terminology drift** | Renaming in code creates merge conflicts. Change only user-facing labels first; rename code later. |

---

## 6. Summary Table: New Items → Package Location

| ID | Item | Type | Package Location | Decoupling Status |
|---|---|---|---|---|
| U1 | Capability-based module organization | Architecture | Docs only | ✅ No code change |
| U2 | Layered dependency model | Architecture | Docs only | ✅ No code change |
| U3 | "Module" → "Application" terminology | Naming | User-facing labels | ⚠️ Label change only |
| U4 | Infrastructure vs User-facing modules | Feature | `ui-library.php` config | 🔴 Add `user_facing` flag |
| U5 | Config-driven navigation metadata | Configuration | `src/Contracts/Navigation/` | 🔴 New contract |
| U6 | Section-based sidebar grouping | UI Library | `Sidebar` + `NavigationLayout` | 🟡 P2 enhancement |
| U7 | 5-level nav hierarchy | UI Library | `NavigationLayout` | 🟡 P3 enhancement |
| U8 | Dropdown app switcher | UI Library | `ModuleSwitcher` | 🟡 P2 enhancement |
| U9 | Workspace tabs in TopNav | UI Library | `TopNav` | 🟢 P3 enhancement |
| U10-U16 | 7 application navigation trees | Feature Modules | `app/Modules/*/Config/navigation.php` | ✅ Business modules |
| U17-U21 | Domain model boundaries | Architecture | Docs only | ✅ No code change |
| U22 | Person/Employee model split | HR Module | `app/Modules/Hr/Models/` | 🟢 HR app change |
| U23 | Payroll Profile ≠ Employee | Payroll Module | `app/Modules/Payroll/` | 🟢 Payroll app change |
| U24 | Clock Events as immutable source | Time Module | `app/Modules/Time/` | 🟢 Time app change |
| U25 | Leave via Workflow Engine | Architecture | `src/Services/Workflow/` | 🔴 Phase 3 dependency |
| U26 | Payroll calculation-first | Payroll Module | `app/Modules/Payroll/` | 🟢 Payroll app change |
| U27 | Config-driven nav generation | UI Library | `NavigationLayout` + config | 🔴 Major enhancement |
| U28 | Capability page metadata | Configuration | `Config/navigation.php` schema | 🟡 Schema enhancement |
| U29 | Configurable organizational nodes | Feature | `Organization` module | 🟢 Business module |
| U30 | Module dependency declaration | Configuration | `ui-library.php` modules array | 🟡 Add `depends_on` key |

**Legend**: 🔴 = Not started, 🟡 = Planned but not implemented, 🟢 = Future/Low priority, ✅ = Complete, ⚠️ = Minor change

---

## 7. Priority Actions from input3.txt

### Immediate (Augment Phase 3 Planning)

| Priority | Action | Rationale |
|---|---|---|
| 🔴 P0 | Add `user_facing` boolean to `ui-library.php` module registry | Simple config change. Enables infrastructure module filtering. |
| 🔴 P0 | Add `depends_on` array to module registry schema | Documents dependency relationships. Validates module loading order. |
| 🟡 P1 | Design `NavigationMetadata` contract | Foundation for config-driven nav generation across all modules. |
| 🟡 P1 | Extract Organization into `src/Core/Organization/` | Highest-ROI extraction. Used by all business modules. |
| 🟡 P2 | Add Section level to sidebar rendering | Enables the "Employment / Organization / Configuration" grouping pattern. |
| 🟡 P2 | Redesign ModuleSwitcher UX to dropdown | Aligns with input3.txt's application switcher vision. |
| 🟢 P3 | 5-level breadcrumb support | Nice-to-have. Requires NavigationLayout changes. |

---

> **Key Insight**: `input3.txt` is primarily a design document, not a code audit. It contains zero code-level HR coupling references — unlike `input.txt` which had specific file paths and service references. Its value is in validating and extending the architectural vision, particularly around navigation hierarchy, module boundaries, and the config-driven metadata model. The most actionable items are the `user_facing` module flag and the Organization module extraction, both of which directly support the decoupling goals of Phases 1 & 2.