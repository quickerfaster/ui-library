# QuickerFaster UI Library — Core Concepts

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **PSR-4 Root**: `src/`
> **View Namespace**: `qf`
> **Blade Component Alias**: `qf` → `QuickerFaster\UILibrary\Components`
> **Livewire Prefix**: `qf.`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`02-directory-map.md`](./02-directory-map.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`13-adr.md`](./13-adr.md)

---

## 1. System Overview & Philosophy

### 1.1 Core Mission

The QuickerFaster UI Library is a **generic, config-driven foundation for any SaaS project** built on Laravel + Livewire 3 + Bootstrap 5. It is not a one-off HR system implementation. Its role is to own **cross-cutting concerns**:

- Layout and navigation (top nav, sidebar, mobile bottom bar)
- Reusable form and data-table rendering driven by PHP config files
- Permission-aware scaffolds via Spatie Permission
- Authentication and onboarding flows via Laravel Fortify + Spatie Onboard
- Module auto-discovery, route conventions, and view namespace registration
- Shared UI components (modals, wizards, drawers, collapsibles)
- Business-agnostic dashboard widgets (stat, chart, list, metric, trend, etc.)
- Import/export infrastructure (Excel, CSV, PDF)
- Bank file generation (BACS, NACH, NIBSS, SEPA)
- Approval workflows and search infrastructure

### 1.2 Separation of Concerns

```
┌─────────────────────────────────────────────────────────┐
│  LIBRARY LAYER                                          │
│  /Users/mac/Projects/Libraries/ui-library/src/          │
│  ─────────────────────────────────────────────────────  │
│  • Framework integration (Fortify, Socialite, Livewire) │
│  • Component rendering (DataTable, Form, Detail, Modal) │
│  • Config resolution (ModelConfigRepository)            │
│  • Route conventions (catch-all, export, print)         │
│  • Reusable layouts (app.blade.php, guest.blade.php)    │
│  • Scaffolded auth views (login, register, reset)       │
│  • Widget processors (stat, chart, metric, etc.)        │
│  • Import/export services                               │
│  • Settings resolution (user → company → system)        │
├─────────────────────────────────────────────────────────┤
│  BUSINESS LAYER                                         │
│  {consuming-app}/app/Modules/{ModuleName}/              │
│  ─────────────────────────────────────────────────────  │
│  • Domain models (Employee, Company, Payroll, etc.)     │
│  • Module-specific configs (Data/*.php)                 │
│  • Module-specific views (Resources/views/)             │
│  • Module-specific routes (Routes/web.php, api.php)     │
│  • Business rules and services                          │
│  • Event listeners (Listeners/)                         │
│  • Database migrations (Database/Migrations/)           │
└─────────────────────────────────────────────────────────┘
```

### 1.3 Design Principles

1. **Convention over Configuration** — Modules follow predictable folder conventions. Views, routes, migrations, and listeners are auto-discovered. Config files are expected in known locations (`Data/`, `Data/Dashboards/`, `Data/reports/`).

2. **Config-Driven Rendering** — DataTables, DataTableForms, DataTableDetails, and field rendering are driven by PHP config files. Business modules express structure and rules; the library handles rendering, validation, and state management.

3. **Catch-All Routing** — A central route pattern in the `System` module (`/{module}/{view}/{id?}`) handles view discovery for module views. This eliminates repetitive route boilerplate. The System module routes are loaded LAST so explicit module routes take precedence.

4. **Scaffolded Auth & Access Control** — The package provides ready-to-use scaffolds for login, register, forgot-password, reset-password, onboarding, and permissions using Laravel Fortify and Spatie packages.

5. **Reusable Component Composition** — Livewire 3 is used for interactive, stateful UI (tables, forms, modals, wizards). Standard Blade components are used for static or render-only UI (layouts, field types, widgets).

6. **Three-Tier Settings Resolution** — Settings cascade through user preferences → company settings → system defaults, with per-context caching. See [`13-adr.md`](./13-adr.md) — ADR-006.

### 1.4 The "qf" Namespace Convention

Everything in the library uses the `qf` prefix for discoverability:

| Context | Convention | Example |
|---------|-----------|---------|
| View namespace | `qf::` | `view('qf::layouts.app')` |
| Blade component tag | `<x-qf::...>` | `<x-qf::layout>` |
| Livewire component tag | `<livewire:qf....>` | `<livewire:qf.data-table>` |
| Translation namespace | `qf::` | `__('qf::nav.dashboard')` |
| Config key | `quicker-faster-ui` | `config('quicker-faster-ui.features.multi_company_payroll')` |
| Publishable tag | `quicker-faster-ui-*` | `php artisan vendor:publish --tag=quicker-faster-ui-views` |

---

## 2. Layer Summary (7-Layer Architecture)

The QuickerFaster UI Library is a **layered architecture** with seven core responsibilities:

```
┌──────────────────────────────────────────────────────────────┐
│  1. PRESENTATION FRAMEWORK (src/)                            │
│     • Base components (DataTable, Form, Detail, Modal,       │
│       Wizard, Dashboard, Navigation)                         │
│     • Route conventions (export, print, socialite, setup)    │
│     • Shared infrastructure (config resolution, validation,  │
│       settings, import/export, approvals, search)            │
│     • Scaffolded auth views and Fortify integration          │
├──────────────────────────────────────────────────────────────┤
│  2. BUSINESS MODULES (app/Modules/{Module}/)                 │
│     • Domain models and business logic                       │
│     • Module-specific configs (Data/*.php)                   │
│     • Module-specific views and routes                       │
│     • Event listeners (auto-discovered)                      │
├──────────────────────────────────────────────────────────────┤
│  3. CONFIG-DRIVEN CONNECTIVE TISSUE                          │
│     • ModelConfigRepository: dot-notation → file path        │
│     • ConfigResolver: typed access to config arrays          │
│     • FieldFactory: field_type string → FieldType class      │
│     • WidgetProcessor: widget type → processor class         │
│     • SettingsManager: user → company → system cascade       │
├──────────────────────────────────────────────────────────────┤
│  4. WORKFLOW ENGINE                                          │
│     • WorkflowEngine: generic, contract-based, multi-step    │
│     • Workflowable contract: any Eloquent model can opt in   │
│     • ApprovalEngine: legacy, maintained for backward compat │
│     • ApprovalModelResolver: config-driven model resolution  │
│     • CompanyProvider: multi-tenant navigation abstraction   │
├──────────────────────────────────────────────────────────────┤
│  5. DOCUMENT ENGINE                                          │
│     • DocumentEngine: polymorphic, config-driven             │
│     • Documentable contract: any Eloquent model can opt in   │
│     • Document model: polymorphic, soft deletes, auto cleanup│
│     • PDF + Excel generation via barryvdh/dompdf + maatwebsite/excel │
│     • Full stack: Contract → Model → Service → Controller →  │
│       Livewire → Views → Migration                           │
├──────────────────────────────────────────────────────────────┤
│  6. NOTIFICATION ENGINE                                      │
│     • NotificationService: polymorphic, channel-based        │
│     • Notifiable contract: any Eloquent model can opt in     │
│     • NotificationChannel contract: channel abstraction      │
│     • Channels: DatabaseChannel (in-app), MailChannel (email)│
│     • Template-based rendering with {placeholder} replacement│
│     • User preferences and audit logging                     │
├──────────────────────────────────────────────────────────────┤
│  7. SCHEDULED REPORTS ENGINE                                 │
│     • ReportEngine: config-driven, integrates DocumentEngine │
│       and NotificationService                                │
│     • Reportable contract: any class can define a report     │
│     • ReportSchedule model: frequency, time, recipients      │
│     • GenerateReportJob: queueable per-schedule processing   │
│     • reports:generate-scheduled: Artisan cron command       │
│     • Config: ui-library.reports — report_types registry     │
└──────────────────────────────────────────────────────────────┘
```

---

## 3. Architectural Invariants

- The library has 7 known `App\Modules\*` hardcoded imports + 2 config references + 1 Blade hardcode (see [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md)). Zero are in `DataTable.php` — all are documented with resolution paths.
- Business modules never modify library source files
- All UI behavior is configurable through PHP config files
- The `qf` prefix is used consistently across all namespaces (views, Blade, Livewire, translations)
- The System module catch-all route is always loaded last
- Workflow definitions are config-driven via `ui-library.workflows.definitions`
- Reference data types are config-driven via `ui-library.reference_data.types`
- Reference data is cache-backed with configurable TTL via `ui-library.reference_data.cache_ttl`
- Document storage is config-driven via `ui-library.documents`
- Notification channels and templates are config-driven via `ui-library.notifications`
- All cross-cutting contracts live in `src/Contracts/` with default implementations in `src/Services/`
- Notification dispatch is event-driven: `NotificationDispatched` fires after each dispatch, logged by `NotificationEventSubscriber`
- Report types are config-driven via `ui-library.reports.report_types`
- Report generation is queue-driven via `GenerateReportJob` dispatched by `reports:generate-scheduled`
- Report delivery integrates Document Engine and Notification Engine through `ReportEngine`

---

**Related files**: [`00-index.md`](./00-index.md) · [`02-directory-map.md`](./02-directory-map.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`13-adr.md`](./13-adr.md) · [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md)
