# Company Policy Integration — Analysis & Recommendation

> **Status**: Analysis complete — ready for Code-mode execution
> **Date**: 2026-10-09
> **Mode**: Architect (analysis only, no code)

---

## Table of Contents

1. [Navigation Architecture Reference](#0-navigation-architecture-reference)
2. [Competitor Analysis Summary](#1-competitor-analysis-summary)
3. [Current Module Gap Analysis](#2-current-module-gap-analysis)
4. [Module Ownership Decision](#3-module-ownership-decision)
5. [Recommended Architecture](#4-recommended-architecture)
6. [UX Flow Design](#5-ux-flow-design)
7. [Implementation Phases](#6-implementation-phases)
8. [Library vs Consuming App Boundary](#7-library-vs-consuming-app-boundary)
9. [Risks and Mitigations](#8-risks-and-mitigations)

---

## 0. Navigation Architecture Reference

> **Critical**: All UX flows and navigation recommendations must align with the app's actual navigation architecture. This section documents how navigation works so the integration design is accurate.

### 0.1 The Navigation Hierarchy

```
┌──────────────────────────────────────────────────────────────────┐
│  TOP NAV BAR                                                      │
│  ┌──────────┐ ┌──────────────────────────────────────────────┐   │
│  │  Module   │ │  Context Groups (from active module's         │   │
│  │  Switcher │ │  Config/navigation.php → context_groups)      │   │
│  │  Dropdown │ │  [Dashboard] [Companies] [Structure] ...      │   │
│  └──────────┘ └──────────────────────────────────────────────┘   │
├──────────────────────────────────────────────────────────────────┤
│  SIDEBAR (left)                    │  MAIN CONTENT AREA           │
│  Context Items (from active        │  Rendered by catch-all route │
│  module's Config/navigation.php    │  /{module}/{view} →          │
│  → contexts.{selectedGroup})       │  Resources/views/{view}.blade│
│  ┌──────────────────┐              │  ┌────────────────────────┐ │
│  │ Overview          │              │  │ <x-qf::navigation-     │ │
│  │ All Companies     │              │  │   layout ...>          │ │
│  │ Branches          │              │  │   @livewire(...) or    │ │
│  │ Business Units    │              │  │   <livewire:qf.data-   │ │
│  │ Employee Alloc.   │              │  │   table ... />         │ │
│  └──────────────────┘              │  └────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

### 0.2 Step-by-Step Navigation Flow

| Step | User Action | What Happens |
|---|---|---|
| 1 | User logs in | `TopNav::loadModules()` reads `config('ui-library.modules')` — the module registry. Each entry has `label`, `icon`, `route`, `order`, `roles`. Modules are filtered by the user's roles. The first accessible module becomes the active module. |
| 2 | User opens Module Switcher dropdown | Dropdown lists all modules the user can access (e.g., "HR", "Organization", "Leave", "Payroll", "Attendance", "Admin"). |
| 3 | User selects a module (e.g., "Organization") | `TopNav::switchModule('organization')` sets `session('active_module', 'organization')` and redirects to the module's route (e.g., `organization.dashboard`). |
| 4 | Top nav renders **Context Groups** | The active module's [`Config/navigation.php`](src/../hr-consuming-app/app/Modules/Organization/Config/navigation.php) `context_groups` are rendered as top-nav tabs. For Organization: `dashboard`, `companies`, `structure`, `teams`, `locations`, `classification`, `reports`. |
| 5 | User clicks a context group (e.g., "Companies") | The sidebar renders the `contexts.companies` items from the same navigation config. The first item's route is loaded (or the context group's `url`). |
| 6 | Sidebar shows **Context Items** | For "Companies": Overview, All Companies, Branches, Business Units, Employee Allocations. Each has a `route` like `/organization/companies`. |
| 7 | User clicks a sidebar item (e.g., "All Companies") | The catch-all route `/{module}/{view}` resolves `/organization/companies` → [`app/Modules/Organization/Resources/views/organization/companies.blade.php`](src/../hr-consuming-app/app/Modules/Organization/Resources/views/organization/companies.blade.php). |
| 8 | View renders content | The Blade view is a thin wrapper using `<x-qf::navigation-layout>` and renders a Livewire component (e.g., `<livewire:qf.data-table config-key="organization.company" />`) or a tabbed interface. |

### 0.3 Key Architectural Rules

1. **Module = Top-level namespace in module switcher.** Each module has its own `Config/navigation.php` with `context_groups` and `contexts`.
2. **Context Group = Top-nav tab.** Groups related functionality (e.g., "Companies", "Structure").
3. **Context Items = Sidebar links.** Individual pages within a context group.
4. **Catch-all route** resolves `/{module}/{view}` → `app/Modules/{Module}/Resources/views/{view}.blade.php`. The `{view}` path can include subdirectories (e.g., `/organization/dashboard/growth` → `Resources/views/organization/dashboard/growth.blade.php`).
5. **Views are thin wrappers.** They use `<x-qf::navigation-layout>` with `context` matching the context group key, and render Livewire components.
6. **Library core modules can be overridden.** The consuming app's `app/Modules/Organization` overrides the library's [`src/Core/Organization`](src/Core/Organization). The consuming app version takes precedence.
7. **The HR module is the ESS aggregation hub.** Its `my-portal` context group aggregates employee self-service features: Overview, My Profile, Leave, Team Calendar. Other modules (Leave, Payroll, Attendance) have their own admin navigation but ESS access flows through HR's `my-portal`.

### 0.4 Existing Module Registry

From [`src/Config/ui-library.php`](src/Config/ui-library.php) and consuming app overrides:

| Module Key | Label | Roles | Purpose |
|---|---|---|---|
| `admin` | Administration | `super_admin`, `company_admin` | System admin (permissions, roles, settings) |
| `system` | System | `super_admin` | System-level config (not user-facing) |
| `common` | Common | `*` | Shared utilities (not user-facing) |
| `hr` | HR | `*` (my-portal), restricted (admin contexts) | **ESS hub** + HR management (employees, onboarding, people, manage) |
| `organization` | Organization | `*` | Company structure (companies, departments, teams, locations) |
| `leave` | Leave | (various) | Leave management (admin) |
| `payroll` | Payroll | (various) | Payroll processing |
| `attendance` | Attendance | (various) | Attendance tracking |

### 0.5 HR Module's Current Context Groups

From [`app/Modules/Hr/Config/navigation.php`](src/../hr-consuming-app/app/Modules/Hr/Config/navigation.php):

| Context Group | Purpose | Visible To |
|---|---|---|
| `dashboard` | HR executive dashboard | `super_admin`, `admin`, `company_admin` |
| `my-portal` | **ESS hub** — employee self-service | `*` (all roles) |
| `Organization` | HR's view of org structure | HR roles |
| `people` | Employee management | HR roles + managers |
| `manage` | Job titles, tags, documents, job history | HR roles |
| `onboarding` | Invitations, onboarding tracking | HR roles |

**Note**: There is a hardcoded "Policies" link in [`top-nav-links.blade.php`](src/../hr-consuming-app/app/Modules/Hr/Resources/views/components/layouts/navbars/auth/top-nav-links.blade.php) pointing to `/hr/dashboard-policies-overview`, but NO `policies` context group exists in the navigation config. The policies sidebar in [`policies/sidebar-links.blade.php`](src/../hr-consuming-app/app/Modules/Hr/Resources/views/components/layouts/navbars/auth/policies/sidebar-links.blade.php) is also hardcoded HTML (not config-driven). These are legacy artifacts from attendance/payroll policy management — a different concept from company handbook policies.

---

## 1. Competitor Analysis Summary

### 1.1 Feature Comparison Matrix

| Capability | BambooHR | Gusto | Workday | HiBob | Zoho People | Deel |
|---|---|---|---|---|---|---|
| **Policy document storage** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Versioning** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Categorization/tags** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Acknowledgment tracking** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **E-signature** | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Read receipts** | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Policy hub (ESS)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Onboarding integration** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Compliance dashboard** | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ |
| **Acknowledgment reminders** | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Targeted distribution** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Localized/multi-language** | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Mobile-responsive** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Search** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### 1.2 Where Policies Live (Navigation Placement)

| Competitor | Admin Access | ESS Access |
|---|---|---|
| **BambooHR** | Settings → Company Policies (within HR module) | Employee Profile → Company Policies tab |
| **Gusto** | Company → Documents (within HR/People module) | Employee Dashboard → Documents section |
| **Workday** | Organization → Policies (within HCM module) | Worker Profile → Policies & Procedures |
| **HiBob** | Settings → Policy Hub (within HR module) | Employee Home → Policy Hub |
| **Zoho People** | Settings → HR Policies (within HR module) | Self Service → Policy Documents |
| **Deel** | Organization → Compliance (within HR module) | Documents → Compliance section |

**Key insight**: All six competitors place company policies **within the HR/People module**, not as a separate top-level module. Employees access policies through their employee portal/self-service, not by switching to a different "module."

### 1.3 Common UX Patterns

1. **Policy Hub in ESS** — A dedicated, searchable, categorized view within the employee self-service portal.
2. **Card-based layout** — Policies displayed as cards with category badges, status indicators, and last-updated dates.
3. **"Pending" section** — Unacknowledged policies surfaced prominently at the top of the policy hub.
4. **Acknowledgment workflow** — Click-to-acknowledge with timestamp audit trail. Optional e-signature.
5. **Onboarding integration** — Policies presented as a task/step during employee onboarding.
6. **Admin dashboard** — Acknowledgment rates, compliance gaps, policy version management.
7. **Targeted distribution** — Policies assigned by department, role, location, or individual.
8. **Version history** — Track changes over time; employees must re-acknowledge on major updates.
9. **Search + filters** — Category, status (acknowledged/pending), effective date.
10. **Mobile-responsive** — Cards stack vertically on mobile; modals for policy reading.

---

## 2. Current Module Gap Analysis

### 2.1 What Already Exists

| Infrastructure | Location | Relevance to Company Policies |
|---|---|---|
| **Polymorphic `Document` model** | [`src/Models/Document.php`](src/Models/Document.php) | Can store policy PDFs attached to a `CompanyPolicy` model via `HasDocuments` |
| **`HasDocuments` trait** | [`src/Traits/Documents/HasDocuments.php`](src/Traits/Documents/HasDocuments.php) | Provides `documents()`, `uploadDocument()`, `getDocuments()`, `deleteDocument()` |
| **`DocumentEngine`** | [`src/Services/Documents/DocumentEngine.php`](src/Services/Documents/DocumentEngine.php) | Handles file storage, PDF generation, Excel generation |
| **`Workflowable` contract** | [`src/Contracts/Workflow/Workflowable.php`](src/Contracts/Workflow/Workflowable.php) | Can model policy acknowledgment as a workflow (submit → acknowledge → complete) |
| **`HasWorkflow` trait** | [`src/Traits/Workflows/HasWorkflow.php`](src/Traits/Workflows/HasWorkflow.php) | Provides `workflow()`, `workflows()`, `activeWorkflow()`, `isUnderApproval()` |
| **`WorkflowEngine`** | [`src/Services/Workflow/WorkflowEngine.php`](src/Services/Workflow/WorkflowEngine.php) | Handles workflow lifecycle, approvals, notifications |
| **`DataTable`** | Library core | Config-driven tables for admin policy list and ESS policy hub |
| **`Wizard` + `WizardForm`** | [`src/Http/Livewire/Wizards/Wizard.php`](src/Http/Livewire/Wizards/Wizard.php) | Can add a "Policies" step to the onboarding wizard |
| **`Notifications`** | Library core | Template-based notifications for policy updates, acknowledgment reminders |
| **`Widgets`** | Library core | Dashboard widgets for compliance metrics |
| **`Navigation`** | Library core | Config-driven sidebar/topnav via `Config/navigation.php` |
| **Onboarding wizard** | [`app/Modules/Hr/Http/Livewire/Onboarding/`](src/../hr-consuming-app/app/Modules/Hr/Http/Livewire/Onboarding/) | Existing 5-step wizard; can add Step 6 for policies |
| **Onboarding config** | [`app/Modules/Hr/Config/onboarding.php`](src/../hr-consuming-app/app/Modules/Hr/Config/onboarding.php) | Step definitions; can add a `company_policies` step |
| **HR module's `my-portal`** | [`app/Modules/Hr/Config/navigation.php`](src/../hr-consuming-app/app/Modules/Hr/Config/navigation.php) | ESS aggregation hub — where "Company Policies" sidebar item would live |
| **Hardcoded "Policies" top-nav link** | [`top-nav-links.blade.php`](src/../hr-consuming-app/app/Modules/Hr/Resources/views/components/layouts/navbars/auth/top-nav-links.blade.php) | Existing link to `/hr/dashboard-policies-overview` — can be replaced with proper context group |

### 2.2 What's Missing (Gaps)

| Gap | Severity | Description |
|---|---|---|
| **No `CompanyPolicy` model** | 🔴 Critical | No model to represent a company policy document (handbook, code of conduct, etc.) |
| **No `PolicyAcknowledgment` model** | 🔴 Critical | No way to track which employees have acknowledged which policies |
| **No `policies` context group in HR navigation** | 🔴 Critical | The hardcoded "Policies" top-nav link has no corresponding `context_groups.policies` entry in [`navigation.php`](src/../hr-consuming-app/app/Modules/Hr/Config/navigation.php) |
| **No policy data config** | 🔴 Critical | No `Data/company_policy.php` for DataTable-based admin CRUD |
| **No "Company Policies" in `my-portal`** | 🔴 Critical | No sidebar item for employees to access policies from their self-service portal |
| **No policy ESS view** | 🔴 Critical | No employee-facing policy hub (list, search, read, acknowledge) |
| **No policy admin views** | 🔴 Critical | No admin views for creating/editing/publishing policies |
| **No policy dashboard data config** | 🟡 High | `dashboard_policies_overview.php` data config file is missing (the view exists but references a non-existent config) |
| **No policy acknowledgment workflow** | 🟡 High | No workflow definition for policy acknowledgment |
| **No policy notification templates** | 🟡 High | No notification templates for policy published, acknowledgment reminders |
| **No onboarding policy step** | 🟡 Medium | Onboarding wizard has no step for reviewing/acknowledging company policies |
| **No policy versioning** | 🟡 Medium | No mechanism to track policy versions and require re-acknowledgment |
| **No targeted distribution** | 🟢 Low | No way to assign policies to specific departments, roles, or locations |
| **No compliance dashboard widgets** | 🟢 Low | No dashboard widgets showing acknowledgment rates |

---

## 3. Module Ownership Decision

### 3.1 The Core Question

Where should Company Policies live in the navigation architecture?

**Options evaluated:**

| Option | Description | Pros | Cons |
|---|---|---|---|
| **A: Standalone module in switcher** | `app/Modules/Policies/` with its own entry in the module registry and module switcher | Fully self-contained; portable; clean separation | Employees must switch modules to see policies (bad UX); no competitor does this; adds clutter to module switcher |
| **B: Context group in Organization module** | Add `policies` to Organization's `context_groups` | Organization module already has structural entities | Policies are not organizational structure; Organization is about companies/departments/teams/locations — not rules/compliance |
| **C: Context group in HR module** | Add `policies` to HR's `context_groups` + `my-portal` context item for ESS | Follows competitor patterns; HR is the natural home for policies; employees already use HR's my-portal; minimal navigation change | HR module gains more responsibility (but it's already the aggregation hub) |
| **D: Hybrid — Policies module for code, HR module for navigation** | Business logic in `app/Modules/Policies/`; navigation entries in HR module's config; thin wrapper views in HR module | Self-contained business logic; follows existing ESS aggregation pattern; portable Policies module | Thin wrapper views in HR module create minor coupling |

### 3.2 Recommendation: Option D (Hybrid)

**Business logic lives in `app/Modules/Policies/`** — a self-contained, portable module with models, migrations, services, and Livewire components.

**Navigation lives in the HR module** — because:
1. All six competitors place policy access within the HR/People module, not as a separate top-level module.
2. The HR module is already the **ESS aggregation hub** — its `my-portal` context groups employee-facing features (Overview, My Profile, Leave, Team Calendar). Company Policies belongs here.
3. The HR module already has a hardcoded "Policies" top-nav link — this would formalize it as a proper `policies` context group.
4. Employees should not need to switch modules to access company policies — they should find them in their self-service portal.
5. This follows the existing pattern: the Leave module has its own admin navigation, but ESS leave access flows through HR's `my-portal` → `leave-hub.blade.php`.

### 3.3 How the Hybrid Pattern Works

Following the existing Leave pattern:

| Layer | Leave (existing) | Policies (new) |
|---|---|---|
| **Business logic** | `app/Modules/Leave/` — models, migrations, admin components, admin views, admin navigation | `app/Modules/Policies/` — models, migrations, Livewire components (admin + ESS), services |
| **ESS presentation** | `app/Modules/Hr/Resources/views/leave-hub.blade.php` — thin wrapper | `app/Modules/Hr/Resources/views/my-policies.blade.php` — thin wrapper |
| **ESS component** | `app/Modules/Hr/Http/Livewire/LeaveHub.php` — registered in HR's service provider | `app/Modules/Policies/Http/Livewire/MyPoliciesHub.php` — registered in **Policies** module's service provider |
| **ESS navigation** | `my-portal` context item: `route => '/hr/leave-hub'` | `my-portal` context item: `route => '/hr/my-policies'` |
| **Admin navigation** | Leave module's own `Config/navigation.php` (separate module in switcher) | HR module's `policies` context group (within HR module, not a separate switcher entry) |

**Key difference from Leave**: Leave has its own module in the switcher for admin functions. Policies does NOT need a separate module in the switcher — admin policy management is accessed through the HR module's `policies` context group. This is because:

- Policy management is an HR function (HR managers create/edit/publish policies)
- Leave management can be done by managers outside HR (hence its own module)
- Competitors keep policy management within the HR module

---

## 4. Recommended Architecture

### 4.1 File Layout

```
app/Modules/Policies/                          ← Self-contained business logic
├── Config/
│   ├── permissions.php                        ← Policy-specific permissions
│   ├── workflows.php                          ← policy_acknowledgment workflow definition
│   └── quick-actions.php                      ← Quick actions (optional)
├── Data/
│   ├── company_policy.php                     ← DataTable config for admin CRUD
│   ├── policy_acknowledgment.php              ← DataTable config for acknowledgment report
│   ├── notifications.php                      ← Notification template types
│   └── dashboards/
│       └── dashboard_policies_overview.php    ← Admin compliance dashboard widgets
├── Database/
│   ├── Migrations/
│   │   ├── 2026_10_09_000001_create_company_policies_table.php
│   │   └── 2026_10_09_000002_create_policy_acknowledgments_table.php
│   └── Seeders/
│       ├── PoliciesRoleSeeder.php
│       └── PolicyNotificationTemplateSeeder.php
├── Http/
│   └── Livewire/
│       ├── MyPoliciesHub.php                  ← ESS policy hub (card-based listing)
│       ├── PolicyAcknowledgmentModal.php      ← Acknowledgment confirmation modal
│       └── Onboarding/
│           └── Step6CompanyPolicies.php        ← Onboarding wizard step
├── Models/
│   ├── CompanyPolicy.php                      ← Implements Documentable
│   └── PolicyAcknowledgment.php               ← Pivot model
├── Providers/
│   └── PoliciesServiceProvider.php            ← Registers Livewire components, loads routes/migrations
├── Resources/
│   └── views/
│       └── livewire/
│           ├── my-policies-hub.blade.php
│           ├── policy-acknowledgment-modal.blade.php
│           └── onboarding/
│               └── step6-company-policies.blade.php
├── Routes/
│   └── web.php                                ← Minimal routes (if any explicit routes needed)
└── Services/
    └── PolicyDistributionService.php          ← Resolves which policies apply to an employee

app/Modules/Hr/                                ← Existing HR module (navigation + thin wrappers)
├── Config/
│   └── navigation.php                         ← ADD: policies context group + my-portal item
├── Resources/views/
│   ├── company-policies.blade.php             ← NEW: Admin policy list (thin wrapper)
│   ├── my-policies.blade.php                  ← NEW: ESS policy hub (thin wrapper)
│   ├── policy-acknowledgments.blade.php       ← NEW: Admin acknowledgment report (thin wrapper)
│   └── dashboard-policies-overview.blade.php  ← UPDATE: Fix config key reference
└── (existing files unchanged)
```

### 4.2 Data Model

#### `company_policies` Table

| Column | Type | Description |
|---|---|---|
| `id` | bigint (PK) | Primary key |
| `company_id` | bigint (FK) | Owning company (multi-tenant scoping via `HasCompanyScope`) |
| `title` | varchar(255) | Policy title (e.g., "Code of Conduct") |
| `description` | text | Short description / summary |
| `category` | varchar(100) | `handbook`, `code_of_conduct`, `leave_policy`, `expense_policy`, `it_policy`, `health_safety`, `other` |
| `content` | longtext | Full policy content (HTML) — for in-app reading |
| `version` | varchar(50) | Semantic version (e.g., "1.0", "2.1") |
| `status` | varchar(50) | `draft`, `published`, `archived` |
| `effective_date` | date | When the policy takes effect |
| `review_date` | date | Scheduled review date (nullable) |
| `requires_acknowledgment` | boolean | Whether employees must acknowledge |
| `acknowledgment_required_by` | date | Deadline for acknowledgment (nullable) |
| `priority` | varchar(50) | `low`, `medium`, `high`, `critical` |
| `distribution_scope` | json | Target audience: `{"departments": [1,2], "roles": ["manager"], "locations": [3]}` — null = all |
| `created_by` | bigint (FK) | User who created the policy |
| `timestamps` | | created_at, updated_at |
| `soft_deletes` | | deleted_at |

#### `policy_acknowledgments` Table

| Column | Type | Description |
|---|---|---|
| `id` | bigint (PK) | Primary key |
| `company_policy_id` | bigint (FK) | Policy being acknowledged |
| `employee_id` | bigint (FK) | Employee who acknowledged |
| `user_id` | bigint (FK) | User account (denormalized) |
| `status` | varchar(50) | `pending`, `acknowledged`, `declined` |
| `acknowledged_at` | timestamp | When acknowledged |
| `ip_address` | varchar(45) | Audit trail |
| `user_agent` | text | Audit trail |
| `signature` | text | Optional e-signature (typed name) |
| `timestamps` | | created_at, updated_at |

**Unique constraint**: `(company_policy_id, employee_id)` — one acknowledgment per employee per policy version. On policy version update, old acknowledgments are preserved and new ones are required.

### 4.3 Model Contracts

#### `CompanyPolicy` implements:

- **`Documentable`** ([`src/Contracts/Documents/Documentable.php`](src/Contracts/Documents/Documentable.php)) — for attaching policy PDFs via `HasDocuments`
- **`HasCompanyScope`** — multi-tenant scoping

#### `PolicyAcknowledgment`:

- Standard Eloquent model with `belongsTo(CompanyPolicy::class)` and `belongsTo(Employee::class)`

### 4.4 Navigation Configuration

#### HR Module's `Config/navigation.php` — Additions

**New context group** (for admin access):

```php
// In context_groups:
'policies' => [
    'label' => 'Policies',
    'icon' => 'fas fa-gavel',
    'order' => 600,        // Between onboarding (500) and people (999)
    'route' => NULL,
    'url' => 'hr/dashboard-policies-overview',
    'permission' => 'view_company_policy',
    'roles' => ['hr_manager', 'hr_officer', 'super_admin', 'admin', 'company_admin'],
],

// In contexts:
'policies' => [
    [
        'key' => 'policies_overview',
        'label' => 'Overview',
        'icon' => 'fas fa-chart-bar',
        'route' => '/hr/dashboard-policies-overview',
        'permission' => 'view_company_policy',
        'order' => 1,
    ],
    [
        'key' => 'all_policies',
        'label' => 'All Policies',
        'icon' => 'fas fa-file-contract',
        'route' => '/hr/company-policies',
        'permission' => 'view_company_policy',
        'order' => 10,
    ],
    [
        'key' => 'acknowledgments',
        'label' => 'Acknowledgments',
        'icon' => 'fas fa-clipboard-check',
        'route' => '/hr/policy-acknowledgments',
        'permission' => 'view_company_policy',
        'order' => 20,
    ],
],
```

**New my-portal context item** (for ESS access):

```php
// In contexts -> 'my-portal':
[
    'key' => 'company_policies',
    'label' => 'Company Policies',
    'icon' => 'fas fa-gavel',
    'route' => '/hr/my-policies',
    'permission' => 'view_my_portal',   // Same as other my-portal items
    'order' => 5,                        // After Team Calendar (4)
],
```

### 4.5 Thin Wrapper Views (in HR Module)

#### `my-policies.blade.php` (ESS Policy Hub)

```blade
@php
    use App\Modules\Hr\Models\Employee;
    use Illuminate\Support\Facades\Auth;

    $employee = Employee::where('user_id', Auth::id())->first();
    if (!$employee) { abort(403); }
@endphp

<x-qf::navigation-layout
    context="my-portal"
    moduleName="hr"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    @livewire('qf.policies.my-hub', ['employeeId' => $employee->id])
</x-qf::navigation-layout>
```

#### `company-policies.blade.php` (Admin Policy List)

```blade
<x-qf::navigation-layout
    configKey="policies.company_policy"
    context="policies"
    moduleName="hr"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    <livewire:qf.data-table config-key="policies.company_policy" />
</x-qf::navigation-layout>
```

#### `policy-acknowledgments.blade.php` (Admin Acknowledgment Report)

```blade
<x-qf::navigation-layout
    configKey="policies.policy_acknowledgment"
    context="policies"
    moduleName="hr"
    :overrides="[...]"
>
    <livewire:qf.data-table config-key="policies.policy_acknowledgment" />
</x-qf::navigation-layout>
```

### 4.6 Livewire Component Registration

In [`PoliciesServiceProvider`](src/../hr-consuming-app/app/Modules/Policies/Providers/PoliciesServiceProvider.php):

```php
public function boot(): void
{
    // Register ESS component
    Livewire::component('qf.policies.my-hub', \App\Modules\Policies\Http\Livewire\MyPoliciesHub::class);

    // Register acknowledgment modal
    Livewire::component('qf.policies.acknowledgment-modal', \App\Modules\Policies\Http\Livewire\PolicyAcknowledgmentModal::class);

    // Register onboarding step
    Livewire::component('qf.onboarding.step6-company-policies', \App\Modules\Policies\Http\Livewire\Onboarding\Step6CompanyPolicies::class);

    // Load routes (if any explicit routes needed)
    $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

    // Load migrations
    $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

    // Merge permissions config
    $this->mergeConfigFrom(__DIR__ . '/../Config/permissions.php', 'policies.permissions');
}
```

**Important**: The `PoliciesServiceProvider` must be registered in the consuming app's `config/app.php` providers array, OR the library's auto-discovery must pick it up. Since the Policies module lives under `app/Modules/Policies/` and the library scans `app/Modules` for modules, it should be auto-discovered if it follows the convention.

---

## 5. UX Flow Design

### 5.1 Admin Experience

```
User: HR Manager (role: hr_manager)
Active Module: "HR" (selected from module switcher)

┌──────────────────────────────────────────────────────────────────┐
│  TOP NAV BAR                                                      │
│  ┌────────┐ ┌──────────────────────────────────────────────────┐ │
│  │   HR ▼ │ │ [Dashboard] [My Portal] [Organization] [People]  │ │
│  │        │ │ [Manage] [Onboarding] [Policies] ← NEW           │ │
│  └────────┘ └──────────────────────────────────────────────────┘ │
├────────────────────┬─────────────────────────────────────────────┤
│  SIDEBAR            │  MAIN CONTENT                               │
│  Context: policies  │                                             │
│  ┌────────────────┐ │  ┌───────────────────────────────────────┐ │
│  │ 📊 Overview     │ │  │ Company Policies (DataTable)          │ │
│  │ 📄 All Policies │ │  │ ┌───────────────────────────────────┐ │ │
│  │ ✅ Acknowledged │ │  │ │ 🔍 Search...  [Category▾][Status▾]│ │ │
│  └────────────────┘ │  │ ├───────────────────────────────────┤ │ │
│                      │  │ │ Title       │ Category │ Status   │ │ │
│                      │  │ │ Code of     │ Handbook │ Published│ │ │
│                      │  │ │ Conduct     │          │          │ │ │
│                      │  │ │ Leave       │ Leave    │ Draft    │ │ │
│                      │  │ │ Policy      │ Policy   │          │ │ │
│                      │  │ │ IT Security │ IT       │ Published│ │ │
│                      │  │ │ Policy      │          │          │ │ │
│                      │  │ └───────────────────────────────────┘ │ │
│                      │  │ [+ Create Policy]                      │ │
│                      │  └───────────────────────────────────────┘ │
└────────────────────┴─────────────────────────────────────────────┘
```

**Admin Flows:**

1. **View all policies**: HR Manager selects "HR" module → clicks "Policies" context group → clicks "All Policies" in sidebar → DataTable renders with create/edit/delete actions.
2. **Create policy**: Click "+ Create Policy" → Form with: title, category dropdown, content (rich text), version, effective date, requires acknowledgment toggle, distribution scope, PDF upload.
3. **Edit policy**: Click row → Edit form. On major content change → prompt "Notify employees to re-acknowledge?"
4. **Publish policy**: Change status from Draft → Published. Triggers notification to affected employees.
5. **Archive policy**: Published → Archived. Hidden from ESS but acknowledgments preserved.
6. **View acknowledgments**: Click "Acknowledgments" in sidebar → DataTable filtered by policy, showing employee name, department, status, date.
7. **Compliance dashboard**: Click "Overview" in sidebar → Widgets: acknowledgment rate by policy, pending count, department breakdown.

### 5.2 ESS Experience (Employee Self-Service)

```
User: Employee (role: employee)
Active Module: "HR" (default for employees)
Context Group: "My Portal" (default/active)

┌──────────────────────────────────────────────────────────────────┐
│  TOP NAV BAR                                                      │
│  ┌────────┐ ┌──────────────────────────────────────────────────┐ │
│  │   HR ▼ │ │ [My Portal] ← active                              │ │
│  └────────┘ └──────────────────────────────────────────────────┘ │
├────────────────────┬─────────────────────────────────────────────┤
│  SIDEBAR            │  MAIN CONTENT                               │
│  Context: my-portal │                                             │
│  ┌────────────────┐ │  ┌───────────────────────────────────────┐ │
│  │ 🏠 Overview     │ │  │ My Policies                           │ │
│  │ 👤 My Profile   │ │  │ ┌───────────────────────────────────┐ │ │
│  │ 📅 Leave        │ │  │ │ 🔍 Search policies...             │ │ │
│  │ 📅 Team Calendar│ │  │ │ [All Categories ▾]                │ │ │
│  │ 📋 Company      │ │  │ ├───────────────────────────────────┤ │ │
│  │    Policies  ◄──│──│  │ │                                   │ │ │
│  │ 📄 My Documents │ │  │ │ ⚠️ Pending Acknowledgment (2)     │ │ │
│  │ ⚙️ Preferences  │ │  │ │ ┌───────────────────────────────┐ │ │ │
│  └────────────────┘ │  │ │ │ 📄 Code of Conduct v2.1        │ │ │ │
│                      │  │ │ │ Handbook · High Priority       │ │ │ │
│                      │  │ │ │ Effective: Oct 1, 2026         │ │ │ │
│                      │  │ │ │ [Read & Acknowledge]           │ │ │ │
│                      │  │ │ └───────────────────────────────┘ │ │ │
│                      │  │ │ ┌───────────────────────────────┐ │ │ │
│                      │  │ │ │ 📄 IT Security Policy v1.0    │ │ │ │
│                      │  │ │ │ IT · Critical                 │ │ │ │
│                      │  │ │ │ [Read & Acknowledge]           │ │ │ │
│                      │  │ │ └───────────────────────────────┘ │ │ │
│                      │  │ │                                   │ │ │
│                      │  │ │ ✅ Acknowledged (5)               │ │ │
│                      │  │ │ ┌───────────────────────────────┐ │ │ │
│                      │  │ │ │ 📄 Leave Policy v3.0          │ │ │ │
│                      │  │ │ │ Acknowledged: Sep 15, 2026    │ │ │ │
│                      │  │ │ │ [View]                         │ │ │ │
│                      │  │ │ └───────────────────────────────┘ │ │ │
│                      │  │ │ ... more acknowledged policies     │ │ │
│                      │  │ └───────────────────────────────────┘ │ │
│                      │  └───────────────────────────────────────┘ │
└────────────────────┴─────────────────────────────────────────────┘
```

**ESS Flows:**

1. **Discover policies**: Employee is in "My Portal" → clicks "Company Policies" in sidebar → sees card-based policy hub.
2. **Pending section**: Unacknowledged policies appear at the top with a warning icon and "Read & Acknowledge" button.
3. **Read policy**: Click card → Modal opens with full policy content (HTML rendered from `content` field, or embedded PDF viewer).
4. **Acknowledge**: In modal, click "I have read and acknowledge this policy" → Optional typed-name signature → Confirm → Card moves to "Acknowledged" section.
5. **Search/Filter**: Search by title, filter by category, filter by status (pending/acknowledged).
6. **Policy update**: When a policy is updated (new version), previously acknowledged employees see it reappear in "Pending" with a "Re-acknowledge Required" badge.

### 5.3 Onboarding Integration

The existing onboarding wizard at [`EmployeeOnboardingWizard`](src/../hr-consuming-app/app/Modules/Hr/Http/Livewire/Onboarding/EmployeeOnboardingWizard.php) has 5 steps. Add **Step 6: Company Policies**.

**Navigation flow during onboarding:**
- The onboarding wizard is a standalone page at `/onboarding`, not part of the module/context-group navigation.
- It uses its own layout (`qf::layouts.app`), not `<x-qf::navigation-layout>`.
- Steps are defined in [`onboarding.php`](src/../hr-consuming-app/app/Modules/Hr/Config/onboarding.php) config.

**Step 6 Design:**
- Lists all **mandatory** policies (where `requires_acknowledgment = true` and distribution matches the employee's department/role/location).
- Each policy shown as a card with title, category badge, and "Read & Acknowledge" button.
- Employee reads policy inline (expandable card or modal) and clicks acknowledge.
- Step marked complete when all mandatory policies are acknowledged.
- Step is **skippable** if no mandatory policies exist for the employee.

**Config addition in [`onboarding.php`](src/../hr-consuming-app/app/Modules/Hr/Config/onboarding.php):**

```php
[
    'key'       => 'company_policies',
    'label'     => 'Company Policies',
    'description' => 'Review and acknowledge company policies',
    'model'     => \App\Modules\Policies\Models\CompanyPolicy::class,
    'condition' => \App\Modules\Policies\Conditions\PoliciesAcknowledged::class,
    'component' => 'qf.onboarding.step6-company-policies',
    'required'  => false,
    'order'     => 6,
],
```

---

## 6. Implementation Phases

### Phase 1: Foundation (Policies Module + Admin CRUD)

**Goal**: HR managers can create, edit, publish, and archive company policies from within the HR module.

| # | Task | Files | Est. |
|---|---|---|---|
| 1.1 | Create `app/Modules/Policies/` directory structure | All directories | S |
| 1.2 | Create `CompanyPolicy` model + migration | `Models/CompanyPolicy.php`, migration | M |
| 1.3 | Create `PolicyAcknowledgment` model + migration | `Models/PolicyAcknowledgment.php`, migration | M |
| 1.4 | Create `Data/company_policy.php` data config | DataTable config with fields: title, category, version, status, effective_date, requires_acknowledgment | M |
| 1.5 | Create `PoliciesServiceProvider` | Register Livewire components, load migrations, merge configs | M |
| 1.6 | Add `policies` context group to HR navigation | Modify [`navigation.php`](src/../hr-consuming-app/app/Modules/Hr/Config/navigation.php) — add `context_groups.policies` + `contexts.policies` | S |
| 1.7 | Create admin thin wrapper views in HR module | `company-policies.blade.php`, `policy-acknowledgments.blade.php` | S |
| 1.8 | Create permissions config + seed roles | `Config/permissions.php`, `Database/Seeders/PoliciesRoleSeeder.php` | S |
| 1.9 | Register `PoliciesServiceProvider` in consuming app | `config/app.php` providers or auto-discovery | S |
| 1.10 | Remove hardcoded "Policies" top-nav link | Clean up [`top-nav-links.blade.php`](src/../hr-consuming-app/app/Modules/Hr/Resources/views/components/layouts/navbars/auth/top-nav-links.blade.php) — the proper context group replaces it | S |

### Phase 2: ESS Policy Hub

**Goal**: Employees can discover, read, and acknowledge policies from My Portal.

| # | Task | Files | Est. |
|---|---|---|---|
| 2.1 | Create `MyPoliciesHub` Livewire component | `Http/Livewire/MyPoliciesHub.php` — loads policies scoped to employee, groups by status, handles search/filter | L |
| 2.2 | Create `MyPoliciesHub` Blade view | `Resources/views/livewire/my-policies-hub.blade.php` — card-based layout with pending/acknowledged sections | L |
| 2.3 | Create `PolicyAcknowledgmentModal` component | `Http/Livewire/PolicyAcknowledgmentModal.php` + Blade view | M |
| 2.4 | Create ESS thin wrapper view in HR module | `my-policies.blade.php` — employee scoping + renders `@livewire('qf.policies.my-hub')` | S |
| 2.5 | Add "Company Policies" to `my-portal` context | Modify [`navigation.php`](src/../hr-consuming-app/app/Modules/Hr/Config/navigation.php) — add item to `contexts.my-portal` | S |
| 2.6 | Register components in `PoliciesServiceProvider` | `Livewire::component('qf.policies.my-hub', ...)`, `Livewire::component('qf.policies.acknowledgment-modal', ...)` | S |

### Phase 3: Acknowledgment Tracking & Compliance

**Goal**: HR managers can track who has/hasn't acknowledged policies.

| # | Task | Files | Est. |
|---|---|---|---|
| 3.1 | Create `Data/policy_acknowledgment.php` data config | DataTable config for acknowledgment report with filters: policy, department, status, date range | M |
| 3.2 | Create `PolicyDistributionService` | `Services/PolicyDistributionService.php` — resolves which policies apply to an employee based on `distribution_scope` | M |
| 3.3 | Create compliance dashboard data config | `Data/dashboards/dashboard_policies_overview.php` — widgets: acknowledgment rate, pending count, by-department breakdown | M |
| 3.4 | Update `dashboard-policies-overview.blade.php` | Fix the config key reference (currently references non-existent config) | S |

### Phase 4: Onboarding Integration

**Goal**: New hires acknowledge policies during onboarding.

| # | Task | Files | Est. |
|---|---|---|---|
| 4.1 | Create `Step6CompanyPolicies` component | `Http/Livewire/Onboarding/Step6CompanyPolicies.php` | L |
| 4.2 | Create step Blade view | `Resources/views/livewire/onboarding/step6-company-policies.blade.php` | M |
| 4.3 | Register step in onboarding config | Modify [`onboarding.php`](src/../hr-consuming-app/app/Modules/Hr/Config/onboarding.php) — add step 6 | S |
| 4.4 | Register component in `PoliciesServiceProvider` | `Livewire::component('qf.onboarding.step6-company-policies', ...)` | S |
| 4.5 | Create onboarding condition | `Conditions/PoliciesAcknowledged.php` | S |

### Phase 5: Notifications & Reminders

**Goal**: Automated notifications for policy updates and acknowledgment reminders.

| # | Task | Files | Est. |
|---|---|---|---|
| 5.1 | Create notification templates seeder | `Database/Seeders/PolicyNotificationTemplateSeeder.php` | M |
| 5.2 | Create `Data/notifications.php` | Notification type definitions | S |
| 5.3 | Create console command for reminders | `Console/Commands/SendPolicyReminders.php` | M |
| 5.4 | Register command in `PoliciesServiceProvider` | `$this->commands([...])` | S |

### Phase 6: Advanced Features (Future)

| Feature | Description | Priority |
|---|---|---|
| E-signature capture | Canvas-based signature drawing in acknowledgment modal | Low |
| Policy version diff | Side-by-side comparison of policy versions | Low |
| Targeted distribution UI | Visual interface for assigning policies to departments/roles/locations | Low |
| Policy review workflow | Approval workflow for policy creation/updates before publishing | Low |
| Bulk acknowledgment | Admin marks policies as acknowledged for multiple employees | Low |
| Policy templates | Pre-built policy templates that admins can customize | Low |
| Multi-language policies | Localized policy content based on employee language preference | Low |

---

## 7. Library vs Consuming App Boundary

### 7.1 What Stays in the Consuming App

✅ **Everything** — the Company Policy feature is entirely domain-specific:

- `CompanyPolicy` and `PolicyAcknowledgment` models
- All migrations, seeders, factories
- All Livewire components (`MyPoliciesHub`, `PolicyAcknowledgmentModal`, `Step6CompanyPolicies`)
- All Blade views (both in Policies module and HR module thin wrappers)
- All data configs
- Navigation config additions (in HR module)
- Permissions config, workflows config
- Routes, service provider
- Notification templates

### 7.2 What the Library Already Provides (Reused, Not Modified)

✅ **Zero library changes needed.**

| Library Capability | Used By |
|---|---|
| `Document` model + `HasDocuments` trait + `DocumentEngine` | Attaching policy PDFs to `CompanyPolicy` |
| `DataTable` + config-driven CRUD | Admin policy list, acknowledgment report |
| `Wizard` + `WizardForm` | Onboarding step (consuming app creates subclass) |
| `Navigation` config-driven system | `context_groups` → `contexts` → catch-all route → views |
| `Widgets` + `WidgetProcessor` | Compliance dashboard widgets |
| `NotificationService` + templates | Policy notifications and reminders |
| `HasCompanyScope` | Multi-tenant policy scoping |
| Module auto-discovery | `PoliciesServiceProvider` auto-discovered from `app/Modules/Policies/` |

### 7.3 Library Boundary Check

Per [pre-coding-checklist §C](docs/consuming-app/pre-coding-checklist.md):

| Check | Status |
|---|---|
| Policy feature is domain-specific → belongs in consuming app | ✅ Pass |
| No `App\Modules\*` references in library code | ✅ Pass (no library changes) |
| Reuses library contracts/traits/models | ✅ Pass |
| Two-domain test: Would this work in a CRM? | ✅ Pass — policies are a generic business concept |
| New library component needed? | ❌ No — all existing library components suffice |

### 7.4 If a Library Enhancement Is Needed (Future)

If the acknowledgment modal pattern proves reusable across modules, a generic `AcknowledgmentModal` could be added to the library. This would:

- Accept `title`, `content` (HTML), and `onAcknowledge` callback
- Be domain-agnostic (no reference to policies, employees, or any business model)
- Pass the two-domain test (works for HR policy acknowledgment AND inventory safety acknowledgment)

**This is NOT needed for Phase 1-5.**

---

## 8. Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| **Navigation context mismatch** | The existing `dashboard-policies-overview.blade.php` uses `context="policies"` but no such context group exists. The hardcoded policies sidebar links are not config-driven. | Phase 1.6 creates a proper `policies` context group. The hardcoded top-nav link and sidebar are replaced by config-driven navigation. |
| **Stale vendor files** | Library changes (if any) won't reflect without copying to `vendor/`. | Per [debug-checklist §8](docs/debug-checklist.md), use the symlink approach. Since this plan requires zero library changes, this risk is minimal. |
| **Child component scripts not executing** | The onboarding Step 6 component's `<script>` tags may not execute on tab switch. | Per [debug-checklist §8 Location #4](docs/debug-checklist.md), put critical JS in the parent wizard blade with document-level event delegation. |
| **Onboarding step order conflict** | Adding Step 6 after Step 5 may conflict with existing step indices. | The onboarding config uses `order` keys, not positional indices. Set `order: 6`. |
| **Permission bloat** | Adding `view_company_policy`, `create_company_policy`, etc. requires updating role seeders. | Phase 1.8 creates a dedicated `PoliciesRoleSeeder` that adds permissions to existing roles. |
| **Multi-company data leak** | Policies from Company A visible to Company B employees. | `CompanyPolicy` uses `HasCompanyScope`. `MyPoliciesHub` scopes queries by `employee->company_id`. |
| **Large policy content** | Storing full HTML in `content` column could cause performance issues. | Use `longtext` column. For very large policies, rely on PDF attachment via `HasDocuments` instead of inline content. |
| **Acknowledgment race conditions** | Employee clicks "Acknowledge" multiple times. | Use `firstOrCreate` with unique constraint on `(company_policy_id, employee_id)`. Disable button after click. |
| **Module auto-discovery failure** | `PoliciesServiceProvider` not picked up if conventions aren't met. | Ensure the provider follows the standard path `app/Modules/Policies/Providers/PoliciesServiceProvider.php`. Register manually in `config/app.php` as fallback. |
| **HR module navigation config merge** | Adding `policies` context group could conflict with existing keys. | Use a unique key name (`policies`) that doesn't collide with existing groups (`dashboard`, `my-portal`, `Organization`, `people`, `manage`, `onboarding`). |

---

## Summary

**Company Policies** should be implemented as a **hybrid architecture**:

- **Business logic**: Self-contained `app/Modules/Policies/` module (models, migrations, Livewire components, services)
- **Navigation**: Integrated into the HR module as a `policies` context group (admin) and a `my-portal` context item (ESS)
- **Views**: Thin wrappers in the HR module that render Policies module's Livewire components

This follows:
- The **existing navigation architecture** (module switcher → context groups → sidebar contexts → catch-all views)
- The **existing ESS aggregation pattern** (HR's `my-portal` as the hub for employee self-service)
- **Competitor best practices** (policies accessed within HR/People module, not as a separate top-level module)
- The **library philosophy** (business logic is self-contained and portable; zero library changes needed)

**Zero library changes are required.** All existing infrastructure (Documents, DataTable, Wizard, Navigation, Notifications, Widgets) is sufficient.

Implementation follows a **6-phase approach** starting with foundation (models + admin CRUD) and progressing through ESS hub, compliance tracking, onboarding integration, notifications, and advanced features.