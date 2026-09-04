# QuickerFaster UI Library — Phase History & Decoupling Status

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](../README.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) · [`../implementation-plan.md`](../implementation-plan.md)

---

## Overview

This file records **what has been built and when** across the decoupling and feature phases. It is a status/history reference, not an implementation guide.

**Content policy (single source of truth)**: The full engine documentation for Phases 3.1–3.5 (§12–§16) lives in [`09-engines-and-services.md`](./09-engines-and-services.md). This file contains only the concise one-line phase-completion records, the Phase 2.5 decoupling detail, and the remaining-`App\Modules\*`-references tables.

---

## Completed Phases

| Phase | Status | Deliverable | Docs |
|-------|--------|-------------|------|
| **2.5** | ✅ | Decoupling — `CompanyProvider`, `ApprovalModelResolver`, migration relocation, HR-specific code removal | see §11 below |
| **3.1** | ✅ | Workflow Engine (`WorkflowEngine` + `Workflowable` contract) | [`09`](./09-engines-and-services.md) §12 |
| **3.2** | ✅ | Document Engine (`DocumentEngine` + `Documentable` contract) | [`09`](./09-engines-and-services.md) §13 |
| **3.3** | ✅ | Notification Engine (`NotificationService` + `Notifiable` + `NotificationChannel` contracts) | [`09`](./09-engines-and-services.md) §14 |
| **3.4** | ✅ | Scheduled Reports Engine (`ReportEngine` + `Reportable` + `ReportSchedule` + `GenerateReportJob` + `reports:generate-scheduled`) | [`09`](./09-engines-and-services.md) §15 |
| **3.5** | ✅ | Reference Data Engine (`ReferenceDataService` + `ReferenceDataProvider` + `ReferenceDataItem`) | [`09`](./09-engines-and-services.md) §16 |
| **4.1** | ✅ | Organization Extraction — extracted to [`src/Core/Organization/`](../../src/Core/) with routes, views, navigation config, and seeders | [`../phase-4.1-organization-extraction-spec.md`](../phase-4.1-organization-extraction-spec.md) |
| **4.2** | ✅ | Module Registry Enhancement — `user_facing` and `depends_on` flags added for infrastructure filtering | — |
| **4.3** | ✅ | Section-Based Sidebar — `Sidebar`/`MenuRenderer` support `sections` key; `section_label`, `collapsible`, `expanded_default` customization | [`06-navigation-system.md`](./06-navigation-system.md) |
| **4.4** | ✅ | Dropdown Application Switcher — replaced `ModuleSwitcher` Livewire component with inline Bootstrap 5 dropdown in `TopNav` | [`06-navigation-system.md`](./06-navigation-system.md) |
| **4.5** | ✅ | Config-Driven Navigation Metadata — `NavigationManager::getSections()` 5-tier priority chain, `SidebarComposer`, `WorkspaceResolver` + `WorkspaceFilter` | [`06-navigation-system.md`](./06-navigation-system.md) |
| **4.6** | 🔄 | Architecture Blueprint Restructure — topic-file split (this file set) | [`00-index.md`](../README.md) |

> The full engine sections (§12–§16) are **not duplicated here**. Read [`09-engines-and-services.md`](./09-engines-and-services.md) for the complete architecture, schemas, and usage examples.

---

## 11. Phase 2.5 Decoupling Status

### 11.1 Overview

Phase 2.5 targeted the most impactful hard-coded couplings between the UI library and consuming-business-layer code. The goal was to replace direct `\App\Modules\*` references with contracts and dependency injection, enabling the library to operate independently without requiring specific business modules to be present.

**5 targeted couplings addressed; ~50 broader references remain for later phases.**

### 11.2 Completed Items

| # | Gap Ref | File | Original Coupling | Resolution |
|---|---------|------|-------------------|------------|
| 1 | 2.4.2 | [`src/Resources/views/components/layouts/partials/company-title-suffix.blade.php`](../../src/Resources/views/components/layouts/partials/company-title-suffix.blade.php:9) | `\App\Modules\Hr\Models\Company::find($companyId)` | Replaced with [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php) contract via `app()` helper → `getCompanies(auth()->user())->firstWhere('id', $companyId)` |
| 2 | 2.4.2 | [`src/Http/Livewire/Settings/SettingsPanel.php`](../../src/Http/Livewire/Settings/SettingsPanel.php:118) | `\App\Modules\Hr\Models\Company::find($companyId)` in `getSettableModel()` | Injected [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php) via `boot()`, replaced with `$this->companyProvider->getCurrentCompanyId()` + `getCompanies()` |
| 3 | 2.4.4 | [`src/Http/Livewire/Custom/`](../../src/Http/Livewire/) | HR-specific Livewire components (`EmployeeDetail`, `SearchableEmployeeDropdown`, `TaxBandsRepeater`) | Directory deleted. Components belong in business modules, not the library. |
| 4 | 2.4.4 | [`src/Resources/views/livewire/custom/`](../../src/Resources/views/livewire/) | HR-specific Livewire views | Directory deleted. Views belong in business modules, not the library. |
| 5 | 2.1.2 | [`src/Services/Documents/EmployeeDocumentService.php`](../../src/Services/Documents/) | HR-specific document service (deleted in Phase 2.5) | ✅ Resolved — Phase 3.2 Document Engine ([`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) + [`Documentable`](../../src/Contracts/Documents/Documentable.php) contract) |

### 11.3 New Contracts & Implementations Added

| Layer | Name | Location | Purpose |
|-------|------|----------|---------|
| **Contract** | `CompanyProvider` | [`src/Contracts/Navigation/CompanyProvider.php`](../../src/Contracts/Navigation/CompanyProvider.php:8) | Abstracts company resolution for multi-tenant navigation. Methods: `getCompanies(?User)`, `getCurrentCompanyId(?User)`. |
| **Contract** | `ApprovalModelResolver` | [`src/Contracts/Approvals/ApprovalModelResolver.php`](../../src/Contracts/Approvals/ApprovalModelResolver.php) | Abstracts model class resolution for approval workflows. Config-driven implementation maps model keys to FQCNs. |
| **Implementation** | `ApprovalModelResolver` | [`src/Services/Approvals/ApprovalModelResolver.php`](../../src/Services/Approvals/ApprovalModelResolver.php) | Config-driven resolver: reads `ui-library.approvals.model_map` to resolve model keys to fully-qualified class names. |
| **Implementation** | `NullCompanyProvider` | [`src/Services/Navigation/NullCompanyProvider.php`](../../src/Services/Navigation/NullCompanyProvider.php:9) | Default no-op implementation: returns empty collection and null company ID. Used when no consuming app provider is configured. |

### 11.4 Migration Relocation

| Count | Source | Destination | Reason |
|-------|--------|-------------|--------|
| 8 migrations | `src/Database/Migrations/` (shared) | [`Database/Migrations/`](../../Database/Migrations/) at package root | Standard Laravel package convention; loaded via `loadMigrationsFrom()` in service provider |
| 1 migration | `src/Core/System/Database/Migrations/` | Retained at [`src/Core/System/Database/Migrations/2026_07_17_000000_create_systems_table.php`](../../src/Core/System/Database/Migrations/2026_07_17_000000_create_systems_table.php) | Core system module migration stays within the module structure |

### 11.5 New Events

| Event | Location | Purpose |
|-------|----------|---------|
| `ModuleRegistered` | [`src/Events/ModuleRegistered.php`](../../src/Events/ModuleRegistered.php) | Fired when a business module is auto-discovered and registered by [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php). Payload: module name, path. |
| `ModuleBooted` | [`src/Events/ModuleBooted.php`](../../src/Events/ModuleBooted.php) | Fired after a module has completed its boot sequence. Payload: module name. |
| `NavigationBuilding` | [`src/Events/NavigationBuilding.php`](../../src/Events/NavigationBuilding.php) | Fired during navigation construction, allowing listeners to modify nav items before rendering. Payload: navigation collection. |

### 11.6 Service Provider Updates

**UILibraryServiceProvider** ([`src/Providers/UILibraryServiceProvider.php`](../../src/Providers/UILibraryServiceProvider.php)):

| Change | Detail |
|--------|--------|
| **Contract bindings** | `CompanyProvider` → configurable implementation (default: `NullCompanyProvider`); `ApprovalModelResolver` → singleton binding |
| **Shared migration loading** | Added `loadMigrationsFrom(__DIR__.'/../../Database/Migrations/')` for 8 shared migrations (exports, imports, saved filters, etc.) |
| **Core namespace autoloading** | `composer.json` PSR-4 updated: `"QuickerFaster\\UILibrary\\Core\\": "src/Core/"` for Admin, Common, and System module namespaces within the library |
| **Event imports** | Added `use` statements for `ModuleRegistered` and `ModuleBooted` events |

### 11.7 Deleted Items

| Item | Type | Reason |
|------|------|--------|
| [`src/Services/Documents/EmployeeDocumentService.php`](../../src/Services/Documents/EmployeeDocumentService.php) | Service class | HR-specific business logic; belongs in the Hr module, not the library |
| [`src/Http/Livewire/Custom/`](../../src/Http/Livewire/) (directory) | Livewire components | Contained HR-specific components (`EmployeeDetail`, `SearchableEmployeeDropdown`, `TaxBandsRepeater`) |
| [`src/Resources/views/livewire/custom/`](../../src/Resources/views/livewire/) (directory) | Blade views | Contained HR-specific Livewire component views |

### 11.8 Remaining for Later Phases

Approximately **50 broader references** remain across the codebase, primarily in widget processors (HR KPI calculators like [`TurnoverRateWidgetProcessor`](../../src/Widgets/TurnoverRateWidgetProcessor.php), [`ENPSWidgetProcessor`](../../src/Widgets/ENPSWidgetProcessor.php), etc.) and bank file generators. These will be addressed in future phases as they require more extensive architectural refactoring (introducing data-source contracts, report-engine abstractions, etc.).

| Category | Estimated Count | Planned Phase |
|----------|----------------|---------------|
| HR-specific widget processors | 9 | Phase 3 |
| Bank file generators (country-specific) | 4 | Phase 3 |
| Conditional Livewire registrations in UILibraryServiceProvider | 6 | Phase 3 |
| Navigation/config references to HR models | ~15 | Phase 4 |
| View/Blade references to HR entities | ~16 | Phase 4 |

---

## Phase 4.x Status: Remaining `App\Modules\*` References

> This section was originally the blueprint's **§10.5** ("Known Gaps — Remaining `App\Modules\*` References"). It is renamed here to avoid colliding with the *other* §10.5 ("API vs Web Context Handling"), which lives in [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md#105-api-vs-web-context-handling).

### ✅ Resolution Complete (as of 2026-08-14)

As of 2026-08-14, **all executable `App\Modules\*` references have been resolved**. A full `grep` across `src/` returns **zero executable references**; only docblock/comment references remain (non-blocking). This was the final step of the Phase 4 decoupling work. See [`../implementation-plan.md`](../implementation-plan.md#11-appmodules-references--resolution-status) for the full audit table.

### Hardcoded Imports — Resolved

The 7 formerly hardcoded imports (previously "Block Functionality") are now resolved:

| File | Former Reference | Resolution |
|------|------------------|------------|
| [`src/Http/Livewire/Wizards/WizardForm.php:5`](../../src/Http/Livewire/Wizards/WizardForm.php:5) | `App\Modules\Admin\Services\ActivityLogger` | ✅ **Resolved** — import swapped to [`QuickerFaster\UILibrary\Services\ActivityLogger`](../../src/Services/ActivityLogger.php) |
| [`src/Http/Controllers/RegistrationController.php:8-11`](../../src/Http/Controllers/RegistrationController.php:8) | `App\Modules\Admin\Models\Shift`, `App\Modules\Hr\Models\*` | ✅ **Already decoupled** — no change required |
| [`src/Http/Controllers/Documents/DocumentController.php:7`](../../src/Http/Controllers/Documents/DocumentController.php:7) | `App\Modules\Hr\Models\Document` | ✅ **Already decoupled** — duck-typing via the `Documentable` contract |
| [`src/Services/BankFiles/BankFileGenerator.php:5`](../../src/Services/BankFiles/BankFileGenerator.php:5) | `App\Modules\Hr\Models\PayrollRun` | ✅ **Already decoupled** — duck-typed `$run` payload |
| [`src/Services/BankFiles/NIBSSGenerator.php:5`](../../src/Services/BankFiles/NIBSSGenerator.php:5) | `App\Modules\Hr\Models\PayrollRun` | ✅ **Already decoupled** — duck-typed `$run` payload |
| [`src/Services/BankFiles/NACHAGenerator.php:5`](../../src/Services/BankFiles/NACHAGenerator.php:5) | `App\Modules\Hr\Models\PayrollRun` | ✅ **Already decoupled** — duck-typed `$run` payload |
| [`src/Widgets/ActivityLogWidgetProcessor.php:5`](../../src/Widgets/ActivityLogWidgetProcessor.php:5) | `App\Modules\Admin\Models\ActivityLog` | ✅ **Resolved** — new [`ActivityLogs\ActivityLogModelResolver`](../../src/Contracts/ActivityLogs/ActivityLogModelResolver.php) contract resolves the model via config |

### Config References (By Design — Not Blocking)

These are intentional and remain for consuming apps to override:

| File | Reference | Notes |
|------|-----------|-------|
| [`src/Core/Admin/Data/user.php`](../../src/Core/Admin/Data/user.php:53) | `'model' => 'App\Modules\Hr\Models\Company'` | Reference config for the `company_id` select field. Consuming apps override. |
| [`src/Core/Admin/Data/user.php`](../../src/Core/Admin/Data/user.php:250) | `'model' => 'App\Modules\Hr\Models\Employee'` | Reference config for the `employee_id` field. Consuming apps override. |

### Hardcoded in Blade — Resolved

| File | Former Reference | Resolution |
|------|------------------|------------|
| [`src/Resources/views/components/dashboards/dashboard-control.blade.php:30`](../../src/Resources/views/components/dashboards/dashboard-control.blade.php:30) | `App\Modules\Production\Models\ProductionProcess::all()` | ✅ **Resolved** — dormant commented-out `<select>` block removed |

### Comment-Only (No Impact)

Seven files contain `App\Modules\*` only in docblocks/comments documenting migration history. No functional impact. Examples include [`src/Models/Role.php:15`](../../src/Models/Role.php:15), [`src/Models/ApprovalRequest.php:12`](../../src/Models/ApprovalRequest.php:12), [`src/Http/Livewire/DataTables/DataTableForm.php:888`](../../src/Http/Livewire/DataTables/DataTableForm.php:888), and [`src/Services/ActivityLogger.php:8`](../../src/Services/ActivityLogger.php:8).

---

**Related files**: [`00-index.md`](../README.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) · [`../implementation-plan.md`](../implementation-plan.md) · [`../architecture-discrepancy-analysis.md`](../architecture-discrepancy-analysis.md)
