# QuickerFaster UI Library — Architecture Documentation Index

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14
> **Status**: ✅ Authoritative Index — canonical reference for architecture documentation

---

## About This Restructuring (Phase 4.6)

The original [`docs/ai-optimized-architecture-blueprint.md`](../ai-optimized-architecture-blueprint.md) is a ~3,000-line monolithic document. While comprehensive, it is difficult for AI agents and human developers to consume efficiently. This restructuring splits the blueprint into **self-contained topic files** under `docs/architecture/`, each focused on a single architectural concern.

**Current status**: ✅ Complete — the blueprint has been split into 17 topic files under `docs/architecture/` (`01-` through `17-*`). The original [`docs/ai-optimized-architecture-blueprint.md`](../ai-optimized-architecture-blueprint.md) is now marked **SUPERSEDED** and retained as historical reference only.

### Design Principles

1. **Self-contained**: Each file can be read independently and provides enough context to be useful on its own
2. **Cross-referenced**: Files link to each other where concepts overlap, using relative markdown links
3. **AI-optimized**: Each file is under ~500 lines where possible, with clear section headers, tables, and code examples
4. **Single source of truth**: No duplication — each concept is documented in exactly one file
5. **Progressive disclosure**: The index provides the 10,000-foot view; topic files provide depth
6. **Vanilla JS policy**: All client-side interactivity uses vanilla JS via `data-*` attributes and `Livewire.dispatch()`. Alpine.js is used only internally by Livewire 3 for DOM diffing. No custom `x-data` directives are written.

---

## File Map

```
docs/architecture/
├── 00-index.md                          ← ✅ EXISTS (this file)
├── 01-core-concepts.md                  ← ✅ EXISTS
├── 02-directory-map.md                  ← ✅ EXISTS
├── 03-module-pattern.md                 ← ✅ EXISTS
├── 04-routing-and-views.md              ← ✅ EXISTS
├── 05-data-configs.md                   ← ✅ EXISTS
├── 06-navigation-system.md              ← ✅ EXISTS
├── 07-component-catalog.md              ← ✅ EXISTS
├── 08-contracts-and-interfaces.md       ← ✅ EXISTS
├── 09-engines-and-services.md           ← ✅ EXISTS
├── 10-settings-and-config.md            ← ✅ EXISTS
├── 11-extension-guide.md                ← ✅ EXISTS
├── 12-ai-quick-start.md                 ← ✅ EXISTS
├── 13-adr.md                            ← ✅ EXISTS
├── 14-integration-map.md                ← ✅ EXISTS
├── 15-gaps-and-recommendations.md       ← ✅ EXISTS
├── 16-phase-history.md                  ← ✅ EXISTS
├── 17-view-config-routing-interplay.md  ← ✅ EXISTS — NEW synthesis from multiple blueprint sections + discrepancy analysis
└── phase-5-navigation-ux.md             ← ✅ EXISTS — Phase 5 Navigation & UX polish (vanilla JS + Livewire 3)
```

**File status summary**:

| Status | Count | Files |
|--------|-------|-------|
| ✅ EXISTS | 19 | `00-index.md`, `01-` through `17-*` (17 topic files), `phase-5-navigation-ux.md` |
| ⏸️ DEFERRED | 0 | — |
| ❌ NOT NEEDED | 0 | — |


---

## Topic File Descriptions

### [`01-core-concepts.md`](01-core-concepts.md)

**What it covers**: The library's mission, separation of concerns (library layer vs business layer), design principles (convention over configuration, config-driven rendering, catch-all routing, scaffolded auth, reusable composition, three-tier settings), and the `qf` namespace convention.

**Source**: Extracted from Blueprint Section 1 → [`01-core-concepts.md`](01-core-concepts.md)

**Key takeaways for AI agents**:
- The library owns cross-cutting concerns; business modules own domain logic
- Everything uses the `qf` prefix (views, Blade, Livewire, translations)
- Config-driven means PHP files define structure; the library handles rendering

---

### [`02-directory-map.md`](02-directory-map.md)

**What it covers**: Complete directory tree of `src/` with every file and its purpose. Includes the canonical business module directory map (`app/Modules/{ModuleName}/`) and the component resolution map showing which library components consume which business module assets.

**Source**: Extracted from Blueprint Section 2 → [`02-directory-map.md`](02-directory-map.md)

**Key takeaways for AI agents**:
- Where to find any file in the library
- What each directory and file is responsible for
- How library components map to business module assets (e.g., `DataTable` → `app/Modules/{Module}/Data/{Entity}.php`)

---

### [`03-module-pattern.md`](03-module-pattern.md)

**What it covers**: Mandatory and optional module structure, `ModuleServiceProvider` registration protocol (view aliases, route loading order, migration loading, event listener auto-discovery), global config merging, naming conventions, and the catch-all route pattern overview.

**Source**: Extracted from Blueprint Section 6 → [`03-module-pattern.md`](03-module-pattern.md)

**Key takeaways for AI agents**:
- Every module needs at minimum: `Data/`, `Models/`, `Resources/views/`, `Routes/web.php`
- View namespace = lowercase module name (e.g., `Hr` → `hr`)
- Route loading order: library → non-system modules → system catch-all (LAST)
- Event listeners are auto-discovered via reflection on `handle()` method signatures

---

### [`04-routing-and-views.md`](04-routing-and-views.md)

**What it covers**: The complete routing architecture — library routes (`src/Routes/web.php`), Core module routes, business module routes, and the System catch-all route. View namespace registration for both Core modules (`qf-core::{module}`) and business modules (`{module}`). View resolution flow with fallback logic. Route loading order and why it matters.

**Source**: Extracted from Blueprint Sections 6.4, 6.8 → [`04-routing-and-views.md`](04-routing-and-views.md); source files [`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php), [`src/Routes/web.php`](../../src/Routes/web.php), [`src/Providers/ModuleServiceProvider.php`](../../src/Providers/ModuleServiceProvider.php)

**Key takeaways for AI agents**:
- Catch-all route: `/{module}/{view}/{id?}` resolves `{module}::{view}` then `qf-core::{module}.{view}`
- System routes load LAST so explicit routes take precedence
- Core module views use `qf-core::` namespace; business modules use lowercase module name

---

### [`05-data-configs.md`](05-data-configs.md)

**What it covers**: The config-driven architecture in depth. `ModelConfigRepository` resolution flow (dot-notation key → file path), `ConfigResolver` typed accessors, the complete DataTable/Form/Detail config schema with every known key, how config drives validation (`DataTableFormValidationService`), and how config drives import/export.

**Source**: Extracted from Blueprint Section 5 → [`05-data-configs.md`](05-data-configs.md)

**Key takeaways for AI agents**:
- Config keys use dot-notation: `{lowercase_module}.{filename}` → `app/Modules/{Module}/Data/{file}.php`
- A single config file drives DataTable, DataTableForm, AND DataTableDetail
- `fieldDefinitions` is the core array — every field has `field_type`, `label`, `validation`, etc.
- `ModelConfigRepository` currently only scans `app/Modules/` — needs extension for `src/Core/` (see [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md))

---

### [`06-navigation-system.md`](06-navigation-system.md)

**What it covers**: The navigation architecture — `NavigationLayout` (main shell), `TopNav` (top bar with module switcher), `Sidebar` (collapsible context menu), `BottomBar` (mobile), `HorizontalContextMenu`, `MenuRenderer` (dynamic menu type switching), and `ModuleSwitcher`. Navigation config schema (`context_groups`, `contexts`, `shared_items`, `layout`). How `NavigationFilter` applies permission-based filtering.

**Source**: Extracted from Blueprint Sections 4.1 (Layout & Navigation), 7.5 → [`06-navigation-system.md`](06-navigation-system.md); source files [`src/Http/Livewire/Layouts/Navs/Sidebar.php`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php), [`src/Http/Livewire/Layouts/Navs/MenuRenderer.php`](../../src/Http/Livewire/Layouts/Navs/MenuRenderer.php), [`src/Core/System/Config/navigation.php`](../../src/Core/System/Config/navigation.php)

**Key takeaways for AI agents**:
- Navigation config lives at `{Module}/Config/navigation.php`
- `NavigationLayout` resolves config path: Core → Business → Published override
- `ModuleSwitcher` reads from `config('ui-library.modules')`
- Sidebar state persists to session and localStorage

---

### [`07-component-catalog.md`](07-component-catalog.md)

**What it covers**: Complete catalog of all Livewire components (50+ registered with `qf.` prefix), Blade components, services, models, and traits. Each entry includes class location, alias, and purpose.

**Source**: Extracted from Blueprint Section 4 → [`07-component-catalog.md`](07-component-catalog.md)

**Key takeaways for AI agents**:
- Livewire components use `qf.{kebab-case}` aliases (e.g., `qf.data-table`)
- Blade components use `<x-qf::{kebab-case}>` tags
- All components registered in [`UILibraryServiceProvider::registerLivewireComponents()`](../../src/Providers/UILibraryServiceProvider.php:163)

---

### [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md)

**What it covers**: All contracts/interfaces with their full method signatures: `Workflowable`, `Documentable`, `Notifiable`, `NotificationChannel`, `Reportable`, `FieldType`, `Widget`, `ModuleContract`, `NavigationProvider`, `SettingsProvider`, `CompanyProvider`, `ApprovalModelResolver`, `OnboardingCondition`.

**Source**: Extracted from Blueprint Section 4.4 → [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md)

**Key takeaways for AI agents**:
- Contracts are the extension points for business modules
- `Workflowable`, `Documentable`, `Notifiable` are polymorphic — any Eloquent model can implement them
- `FieldType` has 10 required methods covering form, table, detail, inline edit, and validation

---

### [`09-engines-and-services.md`](09-engines-and-services.md)

**What it covers**: The five engine services in depth: `WorkflowEngine` (multi-step, role-based, sequential), `DocumentEngine` (polymorphic, upload/generatePdf/generateExcel), `NotificationService` (channel-based, template rendering, audit logging), `ReportEngine` (scheduled, integrates Document + Notification engines), and the legacy `ApprovalEngine`. Includes usage examples and configuration.

**Source**: Extracted from Blueprint Sections 12-15 → [`09-engines-and-services.md`](09-engines-and-services.md)

**Key takeaways for AI agents**:
- `WorkflowEngine` supersedes `ApprovalEngine` for new features
- `DocumentEngine` uses `barryvdh/laravel-dompdf` for PDF and `maatwebsite/excel` for Excel
- `NotificationService` supports `{placeholder}` replacement in templates
- `ReportEngine` chains: resolve Reportable → generate Document → notify recipients

---

### [`10-settings-and-config.md`](10-settings-and-config.md)

**What it covers**: The `SettingsManager` 3-tier cascading resolver (user → company → system), the `HasSettings` trait, `SystemSetting` polymorphic model, `ModelConfigRepository` caching strategy, and the complete `ui-library.php` configuration schema with all keys documented.

**Source**: Extracted from Blueprint Sections 5.7, 8.5 → [`10-settings-and-config.md`](10-settings-and-config.md); source file [`src/Config/ui-library.php`](../../src/Config/ui-library.php)

**Key takeaways for AI agents**:
- Settings cascade: user preferences override company defaults override system defaults
- Cache key includes context hash: `md5($userId . '_' . $module . '_' . $companyId)`
- `ModelConfigRepository` uses `Cache::rememberForever()` for config caching
- All `UI_LIBRARY_*` env vars are documented in the config schema

---

### [`11-extension-guide.md`](11-extension-guide.md)

**What it covers**: Step-by-step recipes for common extension tasks: add a new FieldType, create a new business module, override a library component, add a new widget type, extend NavigationLayout.

**Source**: Extracted from Blueprint Section 7 → [`11-extension-guide.md`](11-extension-guide.md)

**Key takeaways for AI agents**:
- New FieldType: implement `FieldType` contract → register in `FieldFactory::$map` → create Blade view
- New module: create directory structure → create Data config → create model → create views → auto-discovered
- Override component: extend library class → register in `AppServiceProvider` → use in views

---

### [`12-ai-quick-start.md`](12-ai-quick-start.md)

**What it covers**: Decision tree ("Given task X, which files do I touch?"), common task lookup table, and troubleshooting guide for config not found, component not rendering, route conflicts, validation issues, settings not resolving, and module not auto-discovered.

**Source**: Extracted from Blueprint Section 9 → [`12-ai-quick-start.md`](12-ai-quick-start.md)

**Key takeaways for AI agents**:
- This is the FIRST file an AI agent should read when given a task
- The decision tree covers 12 common task categories
- The troubleshooting guide covers 6 common failure modes with diagnostic steps

---

### [`13-adr.md`](13-adr.md)

**What it covers**: All six Architecture Decision Records: ADR-001 (catch-all routing), ADR-002 (config-driven DataTables/Forms/Details), ADR-003 (Livewire 3 for interactive, Blade for static), ADR-004 (FieldFactory with contracts), ADR-005 (single NavigationLayout), ADR-006 (three-tier settings resolution).

**Source**: Extracted from Blueprint Section 3 → [`13-adr.md`](13-adr.md)

**Key takeaways for AI agents**:
- Each ADR explains the decision, implementation, why, and trade-offs
- These are the "why" behind the architecture — essential for understanding design intent

---

### [`14-integration-map.md`](14-integration-map.md)

**What it covers**: Composer dependencies and their roles, service provider wiring diagram, inter-package communication patterns (service binding, event listeners, config merge, view namespace, Blade component, Livewire registration, Blade directive), database assumptions, and the settings architecture flow.

**Source**: Extracted from Blueprint Section 8 → [`14-integration-map.md`](14-integration-map.md)

**Key takeaways for AI agents**:
- 7 Composer dependencies: livewire, dompdf, excel, fortify, socialite, permission, onboard
- 3 service providers: UILibraryServiceProvider, ModuleServiceProvider, FortifyServiceProvider
- Database requires: users, system_settings, system, exports, imports, saved_filters, saved_reports, Spatie tables

---

### [`15-gaps-and-recommendations.md`](15-gaps-and-recommendations.md)

**What it covers**: All 10 identified gaps: missing error handling strategy, missing testing architecture, asset compilation strategy, wizard state management, API vs web context, accessibility/i18n, security hardening for catch-all routes, caching strategy for module discovery, bank file generator documentation, missing module scaffold command.

**Source**: Extracted from Blueprint Section 10 → [`15-gaps-and-recommendations.md`](15-gaps-and-recommendations.md)

**Key takeaways for AI agents**:
- These are known weaknesses — be aware of them when implementing
- Security hardening for catch-all routes is the highest-priority gap
- Module discovery caching is partially implemented (events) but not for views/routes

---

### [`16-phase-history.md`](16-phase-history.md)

**What it covers**: Completed phases: Phase 2.5 decoupling (5 couplings resolved, new contracts, migration relocation, deleted items), Phase 3.1 Workflow Engine, Phase 3.2 Document Engine, Phase 3.3 Notification Engine, Phase 3.4 Scheduled Reports Engine, Phase 3.5 Reference Data Engine, Phase 4.1 Organization Extraction, Phase 4.2 Module Registry (`user_facing`/`depends_on`), Phase 4.3 Section-Based Sidebar, Phase 4.4 Dropdown Application Switcher, Phase 4.5 Config-Driven Navigation Metadata. Remaining: 7 hardcoded `App\Modules\*` imports + 1 Blade hardcode + config references (by design) + comment-only references. See [`implementation-plan.md`](../implementation-plan.md#11-known-remaining-appmodules-references) for full details.

**Source**: Extracted from Blueprint Sections 11-15 → [`16-phase-history.md`](16-phase-history.md)

**Key takeaways for AI agents**:
- Phase 2.5 resolved the 5 most critical HR couplings
- Phase 3 built 4 new engines (Workflow, Document, Notification, Reports)
- ~50 broader references remain (HR widget processors, bank file generators, nav references)

---

### [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) ← CRITICAL

**What it covers**: **The most important cross-cutting documentation.** This file traces the complete flow from a URL request through route resolution, view rendering, and data config consumption. It covers:

1. **The complete request lifecycle**: URL → route match → view resolution → DataTable mount → config loading → model query → render
2. **View namespace resolution**: How `{module}::{view}` and `qf-core::{module}.{view}` namespaces are registered and resolved
3. **Data config resolution**: How `ModelConfigRepository` translates dot-notation keys to file paths, and the current limitation (only scans `app/Modules/`)
4. **The Core module gap**: Why Core modules (Admin, System, Organization) need special handling for Data configs
5. **Catch-all route interaction with explicit routes**: When the catch-all fires vs when explicit routes take precedence
6. **Visual flow diagram**: A Mermaid sequence diagram showing the complete interplay

**Source**: Synthesized from Blueprint Sections 5, 6.4, 6.8 → [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md); source files [`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php), [`src/Services/Config/ModelConfigRepository.php`](../../src/Services/Config/ModelConfigRepository.php), [`src/Providers/ModuleServiceProvider.php`](../../src/Providers/ModuleServiceProvider.php), [`src/Providers/UILibraryServiceProvider.php`](../../src/Providers/UILibraryServiceProvider.php), and the [`architecture-discrepancy-analysis.md`](../architecture-discrepancy-analysis.md)

**Key takeaways for AI agents**:
- This is the ROSETTA STONE for understanding how the library's three core systems interact
- Read this file before modifying routes, views, or data configs
- The `ModelConfigRepository` gap is the single most important architectural issue to understand

---

### [`phase-5-navigation-ux.md`](phase-5-navigation-ux.md) ← NEW

**What it covers**: Phase 5 Navigation & UX Polish — WorkspaceTabs, Breadcrumbs, Sidebar Filter, and Sidebar → Tabs Integration. Documents the vanilla JS architecture (IIFE, `data-*` attributes, `Livewire.dispatch()`, `morph.updated` hook, debounce utility), config keys, session keys, and keyboard shortcuts.

**Source**: Implemented Phase 5 components ([`WorkspaceTabs.php`](../../src/Http/Livewire/Layouts/Navs/WorkspaceTabs.php), [`Breadcrumbs.php`](../../src/Components/Breadcrumbs.php), [`quicker-faster.js`](../../public/assets/js/quicker-faster.js))

**Key takeaways for AI agents**:
- All client-side interactivity is vanilla JS via `data-*` attributes and `Livewire.dispatch()` — no custom `x-data` directives
- WorkspaceTabs persists state to the PHP session (`workspace_tabs`, `workspace_active_tab`, `workspace_recently_closed`)
- Component READMEs live in [`../components/`](../components/)

---

## Reading Order by Role

### For AI Agents Implementing a Task

1. [`12-ai-quick-start.md`](12-ai-quick-start.md) — Decision tree and task lookup
2. [`01-core-concepts.md`](01-core-concepts.md) — Understand the philosophy
3. The specific topic file for your task (see decision tree)
4. [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) — If your task touches views, routes, or data

### For New Human Developers

1. [`01-core-concepts.md`](01-core-concepts.md) — Philosophy and design principles
2. [`02-directory-map.md`](02-directory-map.md) — Where everything lives
3. [`03-module-pattern.md`](03-module-pattern.md) — How modules work
4. [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) — How it all connects
5. [`13-adr.md`](13-adr.md) — Why decisions were made
6. [`11-extension-guide.md`](11-extension-guide.md) — How to extend

### For Architects and Reviewers

1. [`13-adr.md`](13-adr.md) — Architecture decisions
2. [`15-gaps-and-recommendations.md`](15-gaps-and-recommendations.md) — Known weaknesses
3. [`14-integration-map.md`](14-integration-map.md) — Dependencies and wiring
4. [`16-phase-history.md`](16-phase-history.md) — What's been done
5. [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) — Critical cross-cutting concern

---

## Relationship Map: Views → Routing → Data Configs → Components

```
┌─────────────────────────────────────────────────────────────────────┐
│                        URL REQUEST                                   │
│                    GET /hr/employee/index                            │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ROUTE RESOLUTION                                                    │
│  ─────────────────                                                   │
│  1. Library routes (src/Routes/web.php) — no match                   │
│  2. Core module routes (src/Core/*/Routes/web.php) — no match        │
│  3. Business module routes (app/Modules/Hr/Routes/web.php) — match?  │
│  4. System catch-all (src/Core/System/Routes/web.php) — matches      │
│     /{module}/{view}/{id?} → module=hr, view=employee.index          │
│                                                                      │
│  See: [04-routing-and-views.md](04-routing-and-views.md)              │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  VIEW RESOLUTION                                                     │
│  ───────────────                                                     │
│  1. Try: view('hr::employee.index')                                  │
│     → Namespace 'hr' registered by ModuleServiceProvider             │
│     → Looks in: app/Modules/Hr/Resources/views/employee/index.blade  │
│  2. Fallback: view('qf-core::hr.employee.index')                     │
│     → Namespace 'qf-core::hr' registered by UILibraryServiceProvider │
│     → Looks in: src/Core/Hr/Resources/views/employee/index.blade     │
│                                                                      │
│  See: [04-routing-and-views.md](04-routing-and-views.md)              │
│  See: [17-view-config-routing-interplay.md](17-view-config-routing-interplay.md) │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  VIEW CONTENT (index.blade.php)                                      │
│  ─────────────────────────────                                       │
│  <x-layout configKey="hr_employee" moduleName="hr">                  │
│      <livewire:qf.data-table config-key="hr.employee" />             │
│  </x-layout>                                                         │
│                                                                      │
│  See: [05-data-configs.md](05-data-configs.md)                        │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  DATA CONFIG RESOLUTION                                              │
│  ───────────────────────                                             │
│  ModelConfigRepository::get('hr.employee')                           │
│  1. Split key: ['hr', 'employee']                                    │
│  2. Module: ucfirst('hr') → 'Hr'                                     │
│  3. Path: app/Modules/Hr/Data/employee.php                           │
│  4. Require file → cache → return array                              │
│                                                                      │
│  ⚠️ CURRENT LIMITATION: Only scans app/Modules/, not src/Core/       │
│  See: [17-view-config-routing-interplay.md](17-view-config-routing-interplay.md) │
│  See: [../architecture-discrepancy-analysis.md](../architecture-discrepancy-analysis.md) │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  COMPONENT RENDERING                                                 │
│  ───────────────────                                                 │
│  DataTable reads config via ConfigResolver:                          │
│  • fieldDefinitions → table columns                                  │
│  • fieldGroups → form sections                                       │
│  • controls → export/import/print buttons                            │
│  • model → Eloquent query builder                                    │
│                                                                      │
│  See: [05-data-configs.md](05-data-configs.md)                        │
│  See: [07-component-catalog.md](07-component-catalog.md)              │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Cross-Reference Quick Links

| Concept | Primary File | Also In |
|---------|-------------|---------|
| Catch-all routing | [`04-routing-and-views.md`](04-routing-and-views.md) | [`03-module-pattern.md`](03-module-pattern.md), [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) |
| Data configs | [`05-data-configs.md`](05-data-configs.md) | [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) |
| View namespaces | [`04-routing-and-views.md`](04-routing-and-views.md) | [`03-module-pattern.md`](03-module-pattern.md) |
| Navigation | [`06-navigation-system.md`](06-navigation-system.md) | [`03-module-pattern.md`](03-module-pattern.md) |
| Module registration | [`03-module-pattern.md`](03-module-pattern.md) | [`14-integration-map.md`](14-integration-map.md) |
| Settings resolution | [`10-settings-and-config.md`](10-settings-and-config.md) | [`14-integration-map.md`](14-integration-map.md) |
| Workflow engine | [`09-engines-and-services.md`](09-engines-and-services.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md) |
| Document engine | [`09-engines-and-services.md`](09-engines-and-services.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md) |
| Notification engine | [`09-engines-and-services.md`](09-engines-and-services.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md) |
| Report engine | [`09-engines-and-services.md`](09-engines-and-services.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md) |
| Field types | [`07-component-catalog.md`](07-component-catalog.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md), [`11-extension-guide.md`](11-extension-guide.md) |
| Widget processors | [`07-component-catalog.md`](07-component-catalog.md) | [`11-extension-guide.md`](11-extension-guide.md) |
| Reference Data engine | [`09-engines-and-services.md`](09-engines-and-services.md) | [`08-contracts-and-interfaces.md`](08-contracts-and-interfaces.md) |
| ModelConfigRepository gap | [`17-view-config-routing-interplay.md`](17-view-config-routing-interplay.md) | [`../architecture-discrepancy-analysis.md`](../architecture-discrepancy-analysis.md) |
| Core module incompleteness | [`../architecture-discrepancy-analysis.md`](../architecture-discrepancy-analysis.md) | [`../phase-4.1-organization-extraction-spec.md`](../phase-4.1-organization-extraction-spec.md) |
| Navigation & Workspace Architecture | [`../navigation-workspace-architecture.md`](../navigation-workspace-architecture.md) | [`06-navigation-system.md`](06-navigation-system.md), [`../context-groups-navigation-analysis.md`](../context-groups-navigation-analysis.md) |

---

## Related Documents (Outside architecture/)

| Document | Purpose |
|----------|---------|
| [`../decoupling-migration-plan.md`](../decoupling-migration-plan.md) | Original plan for decoupling from HR monolith |
| [`../implementation-plan.md`](../implementation-plan.md) | Phased implementation roadmap with gap tracking |
| [`../architecture-discrepancy-analysis.md`](../architecture-discrepancy-analysis.md) | Gap analysis between `app/Modules/*` and `src/Core/*` |
| [`../phase-4.1-organization-extraction-spec.md`](../phase-4.1-organization-extraction-spec.md) | Specification for extracting Organization into Core |
| [`../navigation-workspace-architecture.md`](../navigation-workspace-architecture.md) | Complete navigation config-to-UI mapping, workspace parameter analysis, and implementation |
| [`../context-groups-navigation-analysis.md`](../context-groups-navigation-analysis.md) | Context groups navigation pattern analysis (all gaps now resolved) |
| [`../gap-analysis.md`](../gap-analysis.md) | Original gap analysis |
| [`../input3-gap-supplement.md`](../input3-gap-supplement.md) | Supplemental gap analysis from input3.txt |

---

## Component Documentation

| Component | README |
|-----------|--------|
| WorkspaceTabs | [`../components/workspace-tabs.md`](../components/workspace-tabs.md) |
| Breadcrumbs | [`../components/breadcrumbs.md`](../components/breadcrumbs.md) |
| Sidebar Filter | [`../components/sidebar-filter.md`](../components/sidebar-filter.md) |

---

## Implementation Notes

### Current State (2026-08-14)

- **Phases 4.4 and 4.5 are now ✅ Complete** (as of 2026-08-11): Application Switcher replaced with inline Bootstrap dropdown (ModuleSwitcher component deleted). Context Groups → Sidebar linkage working with `$activeContext` on Sidebar. Sidebar `sidebar` config section supports `section_label`, `collapsible`, `expanded_default`. Icon mode complete. Workspace parameter support via [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php) contract + [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php).
- **Phase 4.6 is ✅ Complete**: The blueprint split is done — all 17 topic files (`01-` through `17-*`) now live under `docs/architecture/`, this index is the authoritative map, and the monolithic [`docs/ai-optimized-architecture-blueprint.md`](../ai-optimized-architecture-blueprint.md) is marked **SUPERSEDED** (retained as historical reference only).
- **17 topic files created**: Files `01-` through `17-*` are all ✅ EXISTS; no DEFERRED topic files remain.
- **Blueprint updated**: The monolithic blueprint has been updated with Phase 4.4/4.5 "Complete" status, new directory entries for [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php), [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php), and [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php). ModuleSwitcher entry removed from directory map.

### Extraction Process (for future execution)

1. **Copy the section verbatim** from the blueprint into the topic file
2. **Add cross-reference links** at the top and bottom of each file pointing to related topic files
3. **Update internal links** to use relative paths to the new file locations
4. **Add a header** with package name, namespace, and last-updated date
5. **File `17-view-config-routing-interplay.md`** requires NEW content synthesized from multiple blueprint sections and the discrepancy analysis — it does not exist as a single section in the original blueprint

### Files NOT to Create

- The original blueprint should be retained as a historical reference but marked as "SUPERSEDED" with a link to this index once all topic files are created

### Estimated File Sizes

| File | Approximate Lines | Source Blueprint Lines |
|------|------------------|----------------------|
| `01-core-concepts.md` | ~100 | 28-101 |
| `02-directory-map.md` | ~470 | 104-573 |
| `03-module-pattern.md` | ~200 | 1552-1674 |
| `04-routing-and-views.md` | ~150 | Synthesized |
| `05-data-configs.md` | ~250 | 1253-1460 |
| `06-navigation-system.md` | ~200 | Synthesized |
| `07-component-catalog.md` | ~350 | 682-1214 |
| `08-contracts-and-interfaces.md` | ~300 | 854-1130 |
| `09-engines-and-services.md` | ~450 | 2437-2888 |
| `10-settings-and-config.md` | ~200 | 1462-1548, 1996-2035 |
| `11-extension-guide.md` | ~250 | 1677-1903 |
| `12-ai-quick-start.md` | ~200 | 2038-2219 |
| `13-adr.md` | ~150 | 577-680 |
| `14-integration-map.md` | ~150 | 1906-2035 |
| `15-gaps-and-recommendations.md` | ~150 | 2222-2347 |
| `16-phase-history.md` | ~200 | 2437-2600 |
| `17-view-config-routing-interplay.md` | ~300 | NEW — synthesized |
| **TOTAL** | **~3,670** | (original: ~3,000) |

The total is slightly larger than the original because of added cross-references, headers, and the new synthesized file 17. However, each individual file is under ~500 lines, making them suitable for AI context windows.

### Split Completion (Phase 4.6) — ✅ Complete (2026-08-14)

The 17 topic-file split (previously deferred) is now complete:

1. All 17 topic files (`01-` through `17-*`) have been created under `docs/architecture/`
2. Organization-specific and navigation-specific content included in [`02-directory-map.md`](02-directory-map.md), [`03-module-pattern.md`](03-module-pattern.md), and [`06-navigation-system.md`](06-navigation-system.md)
3. The original blueprint is marked **SUPERSEDED** and retained as historical reference
4. Cross-references between topic files updated

See: [`docs/implementation-plan.md`](../implementation-plan.md) Phase 4.6