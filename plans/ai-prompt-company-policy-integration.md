# AI Prompt — Company Policy Integration Analysis & Recommendation

> Paste this into a new AI session (Architect mode) to analyze how company policies should be integrated into the HR app and Employee Self-Service (ESS), then recommend an implementation approach.

---

## Task

Analyze how leading HR software competitors integrate **company policies** into their HR management and Employee Self-Service (ESS) experiences. Then analyze the modules currently available in this codebase and recommend how to integrate our own company policy feature, considering best-practice UX, user expectations, and competitor approaches.

This is an **analysis and recommendation task** — do NOT write code yet. Produce a detailed plan that a Code-mode session can execute.

---

## Context — Codebase Layout

You are working on a Laravel/Livewire HR application at `/Users/mac/Projects/LaravelProjects/hr-consuming-app/` that consumes a domain-agnostic UI library at `/Users/mac/Projects/Libraries/ui-library/`.

### Must-Read Before Analyzing

1. [`docs/library/pilosophy.txt`](docs/library/pilosophy.txt) — Core: library must be decoupled from consuming app; consuming app modules must be self-contained.
2. [`docs/library/25-library-independence-safeguards.md`](docs/library/25-library-independence-safeguards.md) — Non-negotiable: no `App\Modules\*` references in library code.
3. [`docs/consuming-app/pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md) — **Must be reviewed before writing any code.** Covers: Blade views, Livewire components, library modifications, module file placement, navigation, row actions, boolean fields, workflow notifications, active-state logic, document upload patterns (§J).
4. [`docs/debug-checklist.md`](docs/debug-checklist.md) — **§8 is critical**: stale files (vendor/published/compiled), and §8 Location #4 (child component `<script>` tags not executing on tab switch).

### Current Module Structure

The consuming app has modules under `app/Modules/`:
- **Hr** — employees, onboarding, documents, dashboards
- **Leave** — leave requests, balances, wizard
- **Payroll** — payroll runs, payslips
- **Attendance** — clock in/out, geofencing
- **Organization** — companies, departments, positions
- **Invitations** — employee invitations
- **Notifications** — workflow notifications
- **Reports** — scheduled reports
- **Settings** — system settings

### Key Library Capabilities

- **DataTable** — config-driven tables with table/list/card views, `moreActions`, filters, bulk actions
- **Wizard** — multi-step forms (`Wizard` + `WizardForm` base classes)
- **Workflow** — approval workflows with `Workflowable` contract
- **Documents** — polymorphic `Document` model + `HasDocuments` trait + `DocumentEngine`
- **Navigation** — config-driven sidebar/topnav/bottom-bar with context groups
- **Widgets** — dashboard widgets (metric, chart, list, action card, etc.)
- **Notifications** — template-based notifications (mail, database, broadcast)

---

## Specific Analysis Required

### 1. Competitor Analysis

Research how these HR platforms integrate company policies (handbook, code of conduct, leave policy, expense policy, etc.):

- **BambooHR** — policy acknowledgment tracking, e-signatures, document versioning
- **Gusto** — policy documents in onboarding, acknowledgment workflows
- **Workday** — policy distribution + attestation, compliance tracking
- **HiBob** — policy hub, read receipts, acknowledgment reports
- **Zoho People** — policy documents, employee acknowledgment
- **Deel** — compliance documents, localized policies

For each, note:
- Where policies live (admin vs ESS)
- How employees discover and access policies
- How acknowledgment/attestation works
- Versioning and updates
- UX patterns (cards, lists, search, categories)

### 2. Current Module Analysis

Analyze the existing modules to identify:
- Which module should own the "Company Policy" feature (or should it be a new module?)
- What existing infrastructure can be reused (Documents, Workflows, Notifications, DataTable, Widgets)
- Gaps that need to be filled

### 3. Integration Recommendation

Recommend how to integrate company policies considering:
- **Admin experience**: create/edit/publish policies, versioning, categories, target audiences (all/department/role)
- **ESS experience**: policy hub, search, acknowledgment flow, read receipts
- **Onboarding**: policies as part of the onboarding wizard (Step 4 documents already exists)
- **Compliance**: acknowledgment tracking, reports, reminders
- **UX best practices**: discoverability, mobile responsiveness, accessibility
- **Library boundary**: what goes in the library vs consuming app

### 4. Library Boundary Check

Per the pre-coding checklist §C:
- ✅ Policy feature is domain-specific → belongs in consuming app (`app/Modules/`)
- ✅ Reuse library `Document`, `HasDocuments`, `Workflowable`, `DataTable`, `Widgets`
- ⚠️ If a new library component is needed, it must be domain-agnostic

---

## Output Format

1. Competitor analysis summary (table format)
2. Current module gap analysis
3. Recommended architecture (module structure, data model, components)
4. UX flow (admin + ESS + onboarding)
5. Implementation phases (prioritized)
6. Library vs consuming app boundary decisions
7. Risks and mitigations

Do NOT write code. Produce a plan that a Code-mode session can execute.