# QuickerFaster UI Library — AI-Optimized Architecture Blueprint

> **Package**: `quicker-faster/ui-library`  
> **Namespace**: `QuickerFaster\UILibrary\`  
> **PSR-4 Root**: `src/`  
> **View Namespace**: `qf`  
> **Blade Component Alias**: `qf` → `QuickerFaster\UILibrary\Components`  
> **Livewire Prefix**: `qf.`  
> **Last Updated**: 2026-08-07  

---

## Table of Contents

1. [System Overview & Philosophy](#1-system-overview--philosophy)
2. [Directory Map & File Purpose Index](#2-directory-map--file-purpose-index)
3. [Architecture Decision Records (ADR)](#3-architecture-decision-records-adr)
4. [Component Catalog & Contract Definitions](#4-component-catalog--contract-definitions)
5. [Config-Driven Architecture Deep Dive](#5-config-driven-architecture-deep-dive)
6. [Module Conventions & Registration Protocol](#6-module-conventions--registration-protocol)
7. [Extension & Customization Guide](#7-extension--customization-guide)
8. [Integration & Dependency Map](#8-integration--dependency-map)
9. [AI Agent Quick-Start Protocol](#9-ai-agent-quick-start-protocol)
10. [Identified Gaps & Recommendations](#10-identified-gaps--recommendations)

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

6. **Three-Tier Settings Resolution** — Settings cascade through user preferences → company settings → system defaults, with per-context caching.

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

## 2. Directory Map & File Purpose Index

### 2.1 UI Library Directory Map (`src/`)

```
src/
├── Commands/                              # Artisan console commands
│   ├── CleanExports.php                   # Cleans stale export files
│   ├── CleanImportErrors.php              # Cleans stale import error files
│   └── QuickerFasterInstallUI.php         # Scaffolds UI assets into consuming app
│
├── Components/                            # Standard Blade components (static/render-only)
│   ├── NavigationLayout.php               # Main app shell: top nav + sidebar + bottom bar
│   └── FieldTypes/                        # Field rendering components (one per type)
│       ├── TextField.php                  # <input type="text">
│       ├── TextareaField.php              # <textarea>
│       ├── SelectField.php                # <select> with options
│       ├── DatepickerField.php            # Date picker input
│       ├── DatetimepickerField.php        # Date+time picker input
│       ├── TimepickerField.php            # Time picker input
│       ├── CheckboxField.php              # Single checkbox
│       ├── RadioField.php                 # Radio button group
│       ├── FileField.php                  # File upload input
│       ├── ImageField.php                 # Image upload with preview
│       ├── PasswordField.php              # Password input with toggle visibility
│       ├── LivewireSearchableSelectField.php  # Searchable dropdown via Livewire
│       ├── MorphToSelectField.php         # Polymorphic relationship selector
│       └── PolicyCalculationBuilderField.php  # Payroll policy calculation builder
│
├── Concerns/                              # Reusable concerns
│   └── ResolvesModels.php                 # Model resolution helper
│
├── Conditions/                            # Condition classes for onboarding/permissions
│   └── Onboarding/
│       └── ProfileComplete.php            # Checks if user profile is complete
│
├── Config/                                # Library-level configuration
│   └── quicker-faster-ui.php              # Socialite, multitenancy, feature flags
│
├── Console/                               # Console kernel
│   └── Kernel.php                         # Registers package commands
│
├── Contracts/                             # Interfaces and contracts
│   ├── OnboardingCondition.php            # Contract for onboarding step completion checks
│   ├── FieldTypes/
│   │   └── FieldType.php                  # Contract for field type implementations
│   └── Widgets/
│       └── Widget.php                     # Contract for widget processors
│
├── Exceptions/                            # Library-specific exceptions
│   └── RecordNotAccessibleException.php   # Thrown when record access is denied
│
├── Factories/                             # Factory classes
│   └── FieldTypes/
│       └── FieldFactory.php               # Maps config field_type strings to FieldType classes
│
├── Http/                                  # HTTP layer
│   ├── Controllers/
│   │   ├── RegistrationController.php     # User registration handling
│   │   ├── SocialiteController.php        # OAuth redirect/callback (Google, GitHub)
│   │   ├── TempImageUploadController.php  # Temporary image upload for editors
│   │   ├── Documents/
│   │   │   └── DocumentController.php     # Document download/serve
│   │   ├── Exports/
│   │   │   └── ExportController.php       # Export queue, download, template, cancel
│   │   ├── Imports/
│   │   │   └── ImportController.php       # Import status, error download
│   │   └── Prints/
│   │       ├── GenericTablePrintController.php    # Print-friendly table view
│   │       └── GenericDetailPagePrintController.php # Print-friendly detail view
│   │
│   └── Livewire/                          # Livewire 3 components
│       ├── ColumnManager.php              # Column visibility toggling for data tables
│       ├── Collapsible.php                # Collapsible UI container
│       ├── Dashboard.php                  # Dashboard widget grid
│       ├── DocumentPreview.php            # Document preview component
│       ├── Drawer.php                     # Slide-over drawer panel
│       ├── FilterPanel.php                # Filter sidebar/panel
│       ├── SearchPanel.php                # Global search panel
│       ├── SetupChecklist.php             # Setup onboarding checklist UI
│       ├── BackgroundJobsPanel.php        # Background job status monitor
│       ├── AccessControls/                # Permission/role management
│       │   ├── AccessControlManager.php   # Full access control UI
│       │   ├── ModuleSelector.php         # Module selection for permissions
│       │   ├── PermissionManager.php      # Permission CRUD
│       │   ├── PermissionGroup.php        # Permission grouping
│       │   ├── PermissionToggle.php       # Individual permission toggle
│       │   └── RoleAssignmentManager.php  # Role assignment UI
│       ├── Approvals/                     # Approval workflow
│       │   ├── ApprovalActions.php        # Approve/reject action buttons
│       │   └── ApprovalHistoryTimeline.php # Approval history timeline
│       ├── Buttons/                       # Reusable button components
│       │   ├── ToggleButton.php           # Single toggle button
│       │   ├── ToggleButtonGroup.php      # Group of toggle buttons
│       │   └── Toggle.php                 # Toggle switch
│       ├── Custom/                        # Business-specific components
│       │   ├── EmployeeDetail.php         # Employee detail card
│       │   ├── SearchableEmployeeDropdown.php # Employee search/select
│       │   └── TaxBandsRepeater.php       # Tax bands repeater field
│       ├── DataTables/                    # Core data table components
│       │   ├── DataTable.php              # Paginated, searchable, sortable table
│       │   ├── DataTableForm.php          # Create/edit form from config
│       │   ├── DataTableDetail.php        # Read-only detail view from config
│       │   └── ImportForm.php             # Import workflow UI
│       ├── Exports/                       # Export UI
│       │   └── RecentExports.php          # Recent exports list
│       ├── Imports/                       # Import UI
│       │   └── RecentImports.php          # Recent imports list
│       ├── Layouts/                       # Layout components
│       │   ├── NavigationLayout.php       # Main navigation layout shell
│       │   └── Navs/
│       │       ├── TopNav.php             # Top navigation bar
│       │       ├── Sidebar.php            # Collapsible sidebar
│       │       ├── BottomBar.php          # Mobile bottom navigation
│       │       ├── HorizontalContextMenu.php # Context-sensitive horizontal menu
│       │       └── MenuRenderer.php       # Dynamic menu renderer
│       ├── Modals/                        # Modal components
│       │   ├── FormModal.php              # Modal wrapper for forms
│       │   ├── DetailModal.php            # Modal wrapper for detail views
│       │   ├── AlertModal.php             # Confirmation/alert dialog
│       │   ├── ImportModal.php            # Import file upload modal
│       │   ├── ExportModal.php            # Export options modal
│       │   ├── ExportProgress.php         # Export progress indicator
│       │   ├── DocumentPreviewModal.php   # Document preview in modal
│       │   └── CropImageModal.php         # Image cropping modal
│       ├── Reports/                       # Reporting components
│       │   ├── ReportIndex.php            # Report listing page
│       │   ├── ReportViewer.php           # Report display (tabular/dashboard)
│       │   └── ReportBuilder.php          # Report builder UI
│       ├── Settings/                      # Settings components
│       │   └── SettingsPanel.php          # Settings page panel
│       └── Wizards/                       # Multi-step wizard components
│           ├── Wizard.php                 # Generic multi-step wizard
│           ├── SetupWizard.php            # Application setup wizard
│           └── WizardForm.php             # Wizard with embedded forms
│
├── Jobs/                                  # Background/queued jobs
│   ├── ExportChunk.php                    # Processes one chunk of an export
│   ├── FinalizeExportZip.php              # Assembles export chunks into ZIP
│   ├── GenerateExport.php                 # Orchestrates full export generation
│   ├── ProcessImport.php                  # Orchestrates full import processing
│   └── ProcessImportChunk.php             # Processes one chunk of an import
│
├── Models/                                # Library-owned Eloquent models
│   ├── Export.php                         # Export job record
│   ├── ExportChunk.php                    # Export chunk file record
│   ├── Import.php                         # Import job record
│   ├── ImportChunk.php                    # Import chunk record
│   ├── SavedFilter.php                    # User-saved filter preset
│   ├── SavedReport.php                    # User-saved report
│   ├── System.php                         # System singleton model
│   └── SystemSetting.php                  # Polymorphic settings (user/company/system)
│
├── Providers/                             # Service providers
│   ├── UILibraryServiceProvider.php       # Main provider: registers everything
│   ├── ModuleServiceProvider.php          # Auto-discovers and registers modules
│   └── FortifyServiceProvider.php         # Wires Fortify actions and rate limiting
│
├── Resources/                             # Views, translations, assets
│   ├── lang/
│   │   ├── en/nav.php                     # English navigation labels
│   │   └── es/nav.php                     # Spanish navigation labels
│   └── views/
│       ├── home.blade.php                 # Default home view
│       ├── auth/                          # Fortify auth views
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── forgot-password.blade.php
│       │   └── reset-password.blade.php
│       ├── layouts/
│       │   ├── app.blade.php              # Authenticated layout shell
│       │   └── guest.blade.php            # Guest/unauthenticated layout
│       ├── components/
│       │   ├── active-filters.blade.php   # Active filter badges display
│       │   ├── breadcrumb.blade.php       # Breadcrumb navigation
│       │   ├── dashboards/                # Dashboard control views
│       │   ├── fields/                    # Field type Blade partials
│       │   │   ├── text.blade.php
│       │   │   ├── textarea.blade.php
│       │   │   ├── select.blade.php
│       │   │   ├── datepicker.blade.php
│       │   │   ├── datetimepicker.blade.php
│       │   │   ├── timepicker.blade.php
│       │   │   ├── checkbox.blade.php
│       │   │   ├── checkbox-group.blade.php
│       │   │   ├── radio.blade.php
│       │   │   ├── file.blade.php
│       │   │   ├── image.blade.php
│       │   │   ├── password.blade.php
│       │   │   ├── text-with-generate.blade.php
│       │   │   ├── generate-button.blade.php
│       │   │   ├── livewire-searchable-select.blade.php
│       │   │   ├── morph-to-select.blade.php
│       │   │   ├── policy-calculation-builder.blade.php
│       │   │   └── inline-editor/         # Inline editing field partials
│       │   │       ├── text.blade.php
│       │   │       ├── textarea.blade.php
│       │   │       ├── select.blade.php
│       │   │       ├── date.blade.php
│       │   │       ├── datetime.blade.php
│       │   │       ├── time.blade.php
│       │   │       ├── checkbox.blade.php
│       │   │       ├── radio.blade.php
│       │   │       └── password.blade.php
│       │   └── onboarding/
│       │       ├── app-onboarding-tasks.blade.php
│       │       └── app-onboarding-tour.blade.php
│       ├── livewire/                      # Livewire component views
│       │   ├── filter-panel.blade.php
│       │   ├── search-panel.blade.php
│       │   ├── column-manager.blade.php
│       │   ├── setup-checklist.blade.php
│       │   ├── background-jobs-panel.blade.php
│       │   ├── access-controls/
│       │   ├── approvals/
│       │   ├── buttons/
│       │   ├── custom/
│       │   ├── dashboards/
│       │   ├── data-tables/               # DataTable, DataTableForm, DataTableDetail views
│       │   │   └── partials/              # card-view, list-view, row-actions
│       │   ├── documents/partials/        # preview-image, preview-office, preview-unsupported
│       │   ├── exports/
│       │   ├── imports/
│       │   ├── modals/
│       │   ├── reports/
│       │   ├── settings/
│       │   └── wizards/                   # wizard, wizard-form, partials/wizard-review
│       ├── reports/                       # Report page views
│       ├── exports/                       # default-pdf.blade.php
│       ├── print/                         # data-table.blade.php, generic-detail.blade.php
│       └── widgets/                       # Widget Blade views
│           ├── stat.blade.php
│           ├── chart.blade.php
│           ├── list.blade.php
│           ├── metric.blade.php
│           ├── trend.blade.php
│           ├── progress.blade.php
│           ├── onboarding.blade.php
│           ├── action_card.blade.php
│           ├── activity_log.blade.php
│           └── profile_header.blade.php
│
├── Routes/
│   └── web.php                            # Library-level routes (export, print, socialite, setup)
│
├── Services/                              # Reusable service classes
│   ├── ValueGenerator.php                 # Auto-generates field values from patterns
│   ├── AccessControl/
│   │   └── AccessControlPermissionService.php  # Permission CRUD operations
│   ├── Approvals/
│   │   └── ApprovalEngine.php             # Approval workflow engine
│   ├── BankFiles/                         # Bank file generators
│   │   ├── BankFileGenerator.php          # Interface/contract for generators
│   │   ├── BankFileGeneratorFactory.php   # Factory for bank file generators
│   │   ├── BACSGenerator.PHP              # UK BACS format
│   │   ├── NACHAGenerator.php             # US NACHA format
│   │   ├── NIBSSGenerator.php             # Nigeria NIBSS format
│   │   └── SEPAGenerator.PHP              # EU SEPA format
│   ├── Config/                            # Configuration resolution
│   │   ├── ConfigResolver.php             # Resolves and normalizes module configs
│   │   ├── ModelConfigRepository.php      # Cached config file loader (dot-notation keys)
│   │   ├── Approvals/
│   │   │   └── ApprovalConfigResolver.php # Approval-specific config resolution
│   │   ├── Dashboards/
│   │   │   └── DashboardResolver.php      # Dashboard config resolution
│   │   └── Wizards/
│   │       └── WizardConfigResolver.php   # Wizard config resolution
│   ├── Documents/
│   │   └── EmployeeDocumentService.php    # Employee document management
│   ├── Exports/                           # Export infrastructure
│   │   ├── DataTableExport.php            # Excel/CSV export from DataTable config
│   │   ├── TemplateExport.php             # Import template generation
│   │   ├── LookupSheet.php                # Lookup values sheet in templates
│   │   ├── OptionsReferenceSheet.php      # Options reference sheet in templates
│   │   └── TemplateDataSheet.php          # Data entry sheet in templates
│   ├── Filters/
│   │   └── FilterService.php              # Filter application logic
│   ├── Imports/
│   │   └── ImportProcessor.php            # Import file processing (singleton)
│   ├── Search/
│   │   └── SearchEngine.php               # Global search across modules
│   ├── Settings/
│   │   └── SettingsManager.php            # 3-tier settings resolver with caching
│   ├── System/
│   │   └── ApplicationInfo.php            # Application metadata service
│   ├── Validation/
│   │   └── DataTableFormValidationService.php  # Dynamic validation rule generation
│   └── Widgets/
│       └── WidgetProcessor.php            # Maps widget type strings to processor classes
│
├── Traits/                                # Reusable traits
│   ├── AppliesFilters.php                 # Filter application (root level)
│   ├── HasAutoGenerateFields.php          # Auto-generate field support
│   ├── HasCacheInvalidator.php            # Cache invalidation helpers
│   ├── HasCurrencySymbol.php              # Currency symbol resolution
│   ├── HasNavItems.php                    # Default navigation items (dashboard, profile, etc.)
│   ├── HasSettings.php                    # Polymorphic settings via SystemSetting model
│   ├── NavigationFilter.php               # Permission-based nav item filtering
│   ├── ResolvesExportValues.php           # Export value resolution
│   ├── Approvals/
│   │   └── HasApproval.php                # Approval workflow trait for models
│   ├── Buttons/
│   │   └── HandlesToggleState.php         # Toggle button state management
│   ├── DataTables/
│   │   └── HasColumnPreferences.php       # Session-based column visibility persistence
│   ├── FieldTypes/
│   │   ├── HasAutoGenerate.php            # Auto-generate button rendering
│   │   ├── HasBladeRendering.php          # Blade rendering with inline/modal/drawer fallback
│   │   └── HasHintField.php               # Hint/tooltip rendering for fields
│   ├── Filters/
│   │   └── AppliesFilters.php             # Filter application (Filters sub-namespace)
│   └── Widgets/
│       ├── HandlesRelationshipGroupBy.php # Relationship grouping for widgets
│       └── ResolvesDateStrings.php        # Date string resolution for widgets
│
└── Widgets/                               # Widget processor implementations
    ├── StatWidgetProcessor.php            # Stat card (count, sum, avg)
    ├── ChartWidgetProcessor.php           # Chart (bar, line, pie, doughnut)
    ├── ListWidgetProcessor.php            # Simple list
    ├── GroupedListWidgetProcessor.php     # Grouped list
    ├── ProgressWidgetProcessor.php        # Progress bar
    ├── MetricWidgetProcessor.php          # Single metric display
    ├── TrendWidgetProcessor.php           # Trend indicator (up/down)
    ├── OnboardingWidgetProcessor.php      # Onboarding progress
    ├── ActionCardWidgetProcessor.php      # Action card with CTA
    ├── ActivityLogWidgetProcessor.php     # Activity log feed
    ├── ProfileHeaderWidgetProcessor.php   # User/employee profile header
    ├── TurnoverRateWidgetProcessor.php    # HR: turnover rate
    ├── ENPSWidgetProcessor.php            # HR: eNPS score
    ├── AbsenteeismRateWidgetProcessor.php # HR: absenteeism rate
    ├── GoalCompletionRateWidgetProcessor.php  # HR: goal completion
    ├── TrainingCompletionRateWidgetProcessor.php # HR: training completion
    ├── HeadcountVsBudgetWidgetProcessor.php    # HR: headcount vs budget
    ├── DiversityIndexWidgetProcessor.php       # HR: diversity index
    └── OfferAcceptanceRateWidgetProcessor.php  # HR: offer acceptance rate
```

### 2.2 Canonical Business Module Directory Map

Every business module under `app/Modules/{ModuleName}/` follows this structure:

```
app/Modules/{ModuleName}/
├── Config/                               # Module-specific configuration
│   └── navigation.php                    # Navigation items for this module
├── Data/                                 # Config-driven data definitions
│   ├── {Entity}.php                      # Shared config for table + form + detail
│   ├── Dashboards/                       # Dashboard widget definitions
│   │   └── {DashboardName}.php
│   └── reports/                          # Report definitions
│       └── {ReportName}.php
├── Database/
│   └── Migrations/                       # Module-specific migrations (auto-loaded)
├── Http/
│   ├── Controllers/                      # Module controllers
│   ├── Livewire/                         # Module-specific Livewire components
│   └── Requests/                         # Form request validation
├── Listeners/                            # Event listeners (auto-discovered via reflection)
├── Models/                               # Eloquent models
├── Resources/
│   └── views/                            # Blade views (auto-registered as lowercase module alias)
├── Routes/
│   ├── web.php                           # Web routes (auto-loaded, system loaded last)
│   └── api.php                           # API routes (auto-loaded with 'api' prefix)
├── Services/                             # Business logic services
└── Traits/                               # Module-specific traits
```

### 2.3 Component Resolution Map

```
Library Component                  →  Business Module Asset              Purpose
─────────────────────────────────────────────────────────────────────────────────────
NavigationLayout                   →  app/Modules/{Module}/Config/navigation.php
                                      shared items, contexts, sidebar state
DataTable                          →  app/Modules/{Module}/Data/{Entity}.php
                                      columns, actions, export/import settings
DataTableForm                      →  app/Modules/{Module}/Data/{Entity}.php
                                      fieldDefinitions, fieldGroups, controls
DataTableDetail                    →  app/Modules/{Module}/Data/{Entity}.php
                                      detail sections and display mappings
ModelConfigRepository              →  app/Modules/{Module}/Data/{path}.php
                                      dot-notation key → file path resolution
FieldFactory                       →  field_type string → FieldType class
                                      'string'→TextField, 'select'→SelectField, etc.
WidgetProcessor                    →  widget type string → WidgetProcessor class
                                      'stat'→StatWidgetProcessor, 'chart'→ChartWidgetProcessor
Catch-all route (System module)    →  app/Modules/{Module}/Resources/views/**
                                      /{module}/{view}/{id?} → view({module}::{view})
SettingsManager                    →  User model → Company model → System model
                                      3-tier cascading resolution
```

---

## 3. Architecture Decision Records (ADR)

### ADR-001: Catch-All Routing Instead of Explicit Route Definitions

**Decision**: Use a centralized route pattern `/{module}/{view}/{id?}` in the System module for module view discovery, loaded LAST so explicit module routes take precedence.

**Implementation** ([`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php:246)):
- Library routes load first ([`src/Routes/web.php`](src/Routes/web.php))
- Non-system module `Routes/web.php` files load next
- System module `Routes/web.php` loads LAST (contains the catch-all)

**Why**:
- Eliminates repetitive route boilerplate for CRUD-like screen modules
- Matches the convention that views are stored under module resource folders
- New modules require zero route configuration for basic view rendering

**Trade-offs**:
- Requires route validation and authorization checks in the catch-all handler
- Risk of accidental view exposure if authorization is weak
- Needs a clear module allow-list and view existence checks

### ADR-002: Config-Driven DataTables, Forms, and Details Share a Single Config Source

**Decision**: A single PHP config file (e.g., `Data/employee.php`) drives DataTable, DataTableForm, and DataTableDetail rendering.

**Implementation** ([`src/Services/Config/ConfigResolver.php`](src/Services/Config/ConfigResolver.php:1)):
- `ModelConfigRepository` loads the config file by dot-notation key (e.g., `'hr.employee'`)
- `ConfigResolver` provides typed accessors: `getFieldDefinitions()`, `getFieldGroups()`, `getControls()`, `getHiddenFields()`, `getRelations()`, `getReports()`
- All three components (table, form, detail) consume the same resolver

**Why**:
- The UI layer can be reused across entity types without rewriting component logic
- Business modules only need to express structure and rules
- Configuration can be reused by multiple UI components with different presentation modes

**Trade-offs**:
- Config files can become large; keep them modular
- Requires a clear schema and validation strategy

### ADR-003: Livewire 3 for Interactive Components, Blade for Static Elements

**Decision**: Use Livewire 3 for stateful, interactive, user-driven components; use standard Blade components for pure rendering.

**Implementation**:
- Livewire: DataTable, DataTableForm, DataTableDetail, modals, wizards, filters, search, navigation
- Blade: `<x-layout>`, `<x-guest-layout>`, `<x-breadcrumb>`, field type components

**Why**:
- Livewire 3 provides reactive state management without a separate frontend framework
- Blade components are simpler and faster for static rendering
- Aligns with the package's `livewire/livewire: ^3` dependency

**Trade-offs**:
- Livewire introduces component state lifecycle and hydration complexity
- Not all UI should be Livewire; static components should remain Blade

### ADR-004: FieldFactory with Contracts Instead of Inline Field Definitions

**Decision**: Field rendering is resolved through [`FieldFactory`](src/Factories/FieldTypes/FieldFactory.php:23) backed by the [`FieldType`](src/Contracts/FieldTypes/FieldType.php:5) contract.

**Implementation**:
- Config specifies `field_type` (e.g., `'string'`, `'select'`, `'datepicker'`)
- `FieldFactory::make($name, $definition)` maps the type string to a concrete class
- Each field type implements `FieldType` with `renderForm()`, `renderTable()`, `renderDetail()`, `renderInlineEditor()`, `getValidationRules()`, `getOptions()`, `isRelationship()`, `getRelationshipConfig()`, `getLabel()`, `getName()`

**Why**:
- Standardizes how field types are rendered across all contexts (form, table, detail, inline edit)
- New field types can be introduced without rewriting parent components
- Keeps business module config concise

**Trade-offs**:
- Requires a clear contract and rendering contract discipline
- Slightly more abstraction than inline field definitions

### ADR-005: Single NavigationLayout with Top, Side, and Bottom Navs

**Decision**: Provide one layout component ([`NavigationLayout`](src/Components/NavigationLayout.php)) that composes [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php), [`Sidebar`](src/Http/Livewire/Layouts/Navs/Sidebar.php), and [`BottomBar`](src/Http/Livewire/Layouts/Navs/BottomBar.php).

**Why**:
- The layout is a shared cross-cutting concern
- Provides consistent navigation architecture across all modules
- Reduces duplication and creates a predictable navigation contract

**Trade-offs**:
- The component becomes a central integration point and should remain simple
- Must be flexible enough for varying modules and contexts

### ADR-006: Three-Tier Settings Resolution (User → Company → System)

**Decision**: Settings cascade through three resolvers with priority: user preferences → company settings → system defaults.

**Implementation** ([`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php:144)):
```php
$manager->addResolver('user', fn($key) => auth()->user()?->getSetting($key));
$manager->addResolver('company', fn($key) => $company?->getSetting($key));
$manager->addResolver('system', fn($key) => System::find(1)?->getSetting($key));
```

**Why**:
- Users can override company defaults; companies can override system defaults
- Each level uses the same `HasSettings` trait with polymorphic `SystemSetting` model
- Cached per context (user + module + company) for performance

---

## 4. Component Catalog & Contract Definitions

### 4.1 Livewire Components (Registered with `qf.` Prefix)

All components are registered in [`UILibraryServiceProvider::registerLivewireComponents()`](src/Providers/UILibraryServiceProvider.php:204).

#### DataTables

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| DataTable | `qf.data-table` | `DataTable` | Paginated, searchable, sortable, filterable table with export/import controls |
| DataTableForm | `qf.data-table-form` | `DataTableForm` | Create/edit form rendered from field definitions |
| DataTableDetail | `qf.data-table-detail` | `DataTableDetail` | Read-only detail view with grouped sections |
| ImportForm | `qf.import-form` | `ImportForm` | File upload and column mapping for imports |

#### Modals

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| FormModal | `qf.form-modal` | `FormModal` | Modal wrapper for DataTableForm |
| DetailModal | `qf.detail-modal` | `DetailModal` | Modal wrapper for DataTableDetail |
| AlertModal | `qf.alert-modal` | `AlertModal` | Confirmation/alert dialog |
| ImportModal | `qf.import-modal` | `ImportModal` | Import file upload modal |
| ExportModal | `qf.export-modal` | `ExportModal` | Export format selection modal |
| ExportProgress | `qf.export-progress` | `ExportProgress` | Export job progress indicator |
| DocumentPreviewModal | `qf.document-preview-modal` | `DocumentPreviewModal` | Document preview in modal |
| CropImageModal | `qf.crop-image-modal` | `CropImageModal` | Image cropping interface |

#### Wizards

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| Wizard | `qf.wizard` | `Wizard` | Generic multi-step wizard |
| SetupWizard | `qf.setup-wizard` | `SetupWizard` | Application setup wizard |
| SetupChecklist | `qf.setup-checklist` | `SetupChecklist` | Setup onboarding checklist |
| WizardForm | `qf.wizard-form` | `WizardForm` | Wizard with embedded forms |

#### Layout & Navigation

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| NavigationLayout | `qf.navigation-layout` | `NavigationLayout` | Main app shell |
| TopNav | `qf.top-nav` | `TopNav` | Top navigation bar |
| Sidebar | `qf.sidebar` | `Sidebar` | Collapsible sidebar |
| BottomBar | `qf.bottom-bar` | `BottomBar` | Mobile bottom navigation |
| HorizontalContextMenu | `qf.horizontal-context-menu` | `HorizontalContextMenu` | Context-sensitive horizontal menu |
| MenuRenderer | `qf.menu-renderer` | `MenuRenderer` | Dynamic menu renderer |

#### Access Controls

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| AccessControlManager | `qf.access-control-manager` | `AccessControlManager` | Full access control management UI |
| ModuleSelector | `qf.module-selector` | `ModuleSelector` | Module selection for permission scoping |
| RoleAssignmentManager | `qf.role-assignment-manager` | `RoleAssignmentManager` | Role assignment interface |
| PermissionManager | `qf.permission-manager` | `PermissionManager` | Permission CRUD interface |

#### Buttons

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| ToggleButton | `qf.toggle-button` | `ToggleButton` | Single toggle button |
| ToggleButtonGroup | `qf.toggle-button-group` | `ToggleButtonGroup` | Group of toggle buttons |

#### Reports

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| ReportIndex | `qf.report-index` | `ReportIndex` | Report listing page |
| ReportViewer | `qf.report-viewer` | `ReportViewer` | Report display (tabular or dashboard) |
| ReportBuilder | `qf.report-builder` | `ReportBuilder` | Report builder UI |

#### Search, Filter & Settings

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| FilterPanel | `qf.filter-panel` | `FilterPanel` | Filter sidebar/panel |
| SearchPanel | `qf.search-panel` | `SearchPanel` | Global search panel |
| ColumnManager | `qf.column-manager` | `ColumnManager` | Column visibility toggling |
| SettingsPanel | `qf.settings-panel` | `SettingsPanel` | Settings page panel |

#### Approvals

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| ApprovalActions | `qf.approval-actions` | `ApprovalActions` | Approve/reject action buttons |
| ApprovalHistoryTimeline | `qf.approval-history-timeline` | `ApprovalHistoryTimeline` | Approval history timeline |

#### Other

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| Dashboard | `qf.dashboard` | `Dashboard` | Dashboard widget grid |
| Drawer | `qf.drawer` | `Drawer` | Slide-over drawer panel |
| Collapsible | `qf.collapsible` | `Collapsible` | Collapsible UI container |
| DocumentPreview | `qf.document-preview` | `DocumentPreview` | Document preview component |
| BackgroundJobsPanel | `qf.background-jobs-panel` | `BackgroundJobsPanel` | Background job status monitor |
| RecentExports | `qf.recent-exports` | `RecentExports` | Recent exports list |
| RecentImports | `qf.recent-imports` | `RecentImports` | Recent imports list |

#### Custom (Business-Specific)

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| EmployeeDetail | `qf.employee-detail` | `EmployeeDetail` | Employee detail card |
| SearchableEmployeeDropdown | `qf.searchable-employee-dropdown` | `SearchableEmployeeDropdown` | Employee search/select |
| TaxBandsRepeater | `qf.tax-bands-repeater` | `TaxBandsRepeater` | Tax bands repeater field |

#### Conditional (HR Module — Registered Only If Files Exist)

These are registered conditionally in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php:299) by checking `app_path()` for file existence:

| Component | Alias | Condition Path |
|-----------|-------|---------------|
| PayrollWizardAdjustments | `qf.payroll-wizard-adjustments` | `Modules/Hr/Http/Livewire/Payroll/PayrollWizardAdjustments.php` |
| PayrollWizardPreview | `qf.payroll-wizard-preview` | `Modules/Hr/Http/Livewire/Payroll/PayrollWizardPreview.php` |
| PayrollRunWizard | `qf.payroll-run-wizard` | `Modules/Hr/Http/Livewire/Payroll/PayrollRunWizard.php` |
| PayrollRunDetail | `qf.payroll-run-detail` | `Modules/Hr/Http/Livewire/Payroll/PayrollRunDetail.php` |
| PayslipItems | `qf.payslip-items` | `Modules/Hr/Http/Livewire/Payroll/PayslipItems.php` |
| PolicyCalculationBuilder | `qf.policy-calculation-builder` | `Modules/Hr/Http/Livewire/Payroll/PolicyCalculationBuilder.php` |

### 4.2 Blade Components

| Component Tag | Class | Purpose |
|---------------|-------|---------|
| `<x-layout>` | `qf::layouts.app` | Authenticated layout shell |
| `<x-guest-layout>` | `qf::layouts.guest` | Guest/unauthenticated layout |
| `<x-breadcrumb>` | `qf::components.breadcrumb` | Breadcrumb navigation |
| `<x-qf::*>` | `QuickerFaster\UILibrary\Components\*` | Namespaced Blade components |

### 4.3 Services

| Service | Location | Purpose |
|---------|----------|---------|
| ConfigResolver | [`src/Services/Config/ConfigResolver.php`](src/Services/Config/ConfigResolver.php:6) | Typed accessor for module config arrays |
| ModelConfigRepository | [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php:8) | Cached config file loader with dot-notation keys |
| SettingsManager | [`src/Services/Settings/SettingsManager.php`](src/Services/Settings/SettingsManager.php:7) | 3-tier cascading settings resolver |
| WidgetProcessor | [`src/Services/Widgets/WidgetProcessor.php`](src/Services/Widgets/WidgetProcessor.php:25) | Maps widget type strings to processor classes |
| DataTableFormValidationService | [`src/Services/Validation/DataTableFormValidationService.php`](src/Services/Validation/DataTableFormValidationService.php:7) | Dynamic validation rule generation from field definitions |
| ImportProcessor | [`src/Services/Imports/ImportProcessor.php`](src/Services/Imports/ImportProcessor.php) | Import file processing (singleton) |
| DataTableExport | [`src/Services/Exports/DataTableExport.php`](src/Services/Exports/DataTableExport.php) | Excel/CSV export from DataTable config |
| TemplateExport | [`src/Services/Exports/TemplateExport.php`](src/Services/Exports/TemplateExport.php) | Import template generation with LookupSheet, OptionsReferenceSheet, TemplateDataSheet |
| ApprovalEngine | [`src/Services/Approvals/ApprovalEngine.php`](src/Services/Approvals/ApprovalEngine.php) | Approval workflow engine |
| SearchEngine | [`src/Services/Search/SearchEngine.php`](src/Services/Search/SearchEngine.php) | Global search across modules |
| FilterService | [`src/Services/Filters/FilterService.php`](src/Services/Filters/FilterService.php) | Filter application logic |
| ValueGenerator | [`src/Services/ValueGenerator.php`](src/Services/ValueGenerator.php) | Auto-generates field values from patterns |
| ApplicationInfo | [`src/Services/System/ApplicationInfo.php`](src/Services/System/ApplicationInfo.php) | Application metadata |
| EmployeeDocumentService | [`src/Services/Documents/EmployeeDocumentService.php`](src/Services/Documents/EmployeeDocumentService.php) | Employee document management |
| AccessControlPermissionService | [`src/Services/AccessControl/AccessControlPermissionService.php`](src/Services/AccessControl/AccessControlPermissionService.php) | Permission CRUD operations |
| BankFileGeneratorFactory | [`src/Services/BankFiles/BankFileGeneratorFactory.php`](src/Services/BankFiles/BankFileGeneratorFactory.php) | Factory for BACS/NACHA/NIBSS/SEPA generators |

### 4.4 Contracts (Interfaces)

#### FieldType Contract

**Location**: [`src/Contracts/FieldTypes/FieldType.php`](src/Contracts/FieldTypes/FieldType.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\FieldTypes;

interface FieldType
{
    public function __construct(string $name, array $definition);

    /** Render the field for a form (input, select, etc.) */
    public function renderForm($value = null): string;

    /** Render the field value for a table cell */
    public function renderTable($value, $record): string;

    /** Render the field value for a detail view */
    public function renderDetail($value, $record): string;

    /** Get validation rules for this field */
    public function getValidationRules(): array;

    /** Get options for select, radio, etc. */
    public function getOptions(): array;

    /** Whether this field represents a relationship */
    public function isRelationship(): bool;

    /** If relationship, return its configuration */
    public function getRelationshipConfig(): ?array;

    /** Get the field's label */
    public function getLabel(): string;

    /** Get the field's name */
    public function getName(): string;

    /** Render an inline editor for use inside a table cell */
    public function renderInlineEditor($value, $record, array $extra = []): string;
}
```

**Implementations** (all in [`src/Components/FieldTypes/`](src/Components/FieldTypes/)):
`TextField`, `TextareaField`, `SelectField`, `DatepickerField`, `DatetimepickerField`, `TimepickerField`, `CheckboxField`, `RadioField`, `FileField`, `ImageField`, `PasswordField`, `LivewireSearchableSelectField`, `MorphToSelectField`, `PolicyCalculationBuilderField`

#### Widget Contract

**Location**: [`src/Contracts/Widgets/Widget.php`](src/Contracts/Widgets/Widget.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts\Widgets;

interface Widget
{
    public function __construct(array $definition);
    public function setData(): void;
    public function render(): string;
    public function getTitle(): ?string;
    public function getWidth(): int;
}
```

#### OnboardingCondition Contract

**Location**: [`src/Contracts/OnboardingCondition.php`](src/Contracts/OnboardingCondition.php:5)

```php
namespace QuickerFaster\UILibrary\Contracts;

interface OnboardingCondition
{
    /** Determine if the step is complete for the given user. */
    public function __invoke($user): bool;
}
```

### 4.5 FieldFactory Mapping

**Location**: [`src/Factories/FieldTypes/FieldFactory.php`](src/Factories/FieldTypes/FieldFactory.php:23)

The factory maps `field_type` config strings to concrete FieldType classes:

```php
protected array $map = [
    'string'       => TextField::class,
    'text'         => TextareaField::class,
    'select'       => SelectField::class,
    'datepicker'   => DatepickerField::class,
    'timepicker'   => TimepickerField::class,
    'datetimepicker'   => DatetimepickerField::class,
    'checkbox'     => CheckboxField::class,
    'boolcheckbox' => CheckboxField::class,
    'boolradio'    => RadioField::class,
    'file'         => FileField::class,
    'image'        => ImageField::class,
    'photo'        => ImageField::class,
    'picture'      => ImageField::class,
    'textarea'     => TextareaField::class,
    'livewire-searchable-select' => LivewireSearchableSelectField::class,
    'morph_to_select' => MorphToSelectField::class,
    'password'     => PasswordField::class,
    'policy_calculation_builder' => PolicyCalculationBuilderField::class,
];

public function make(string $name, array $definition): FieldType
{
    $type = $definition['field_type'] ?? 'string';
    $class = $this->map[$type] ?? TextField::class;
    return new $class($name, $definition);
}
```

### 4.6 WidgetProcessor Mapping

**Location**: [`src/Services/Widgets/WidgetProcessor.php`](src/Services/Widgets/WidgetProcessor.php:27)

```php
protected array $map = [
    'stat'        => StatWidgetProcessor::class,
    'chart'       => ChartWidgetProcessor::class,
    'list'        => ListWidgetProcessor::class,
    'grouped_list' => GroupedListWidgetProcessor::class,
    'progress'    => ProgressWidgetProcessor::class,
    'metric'      => MetricWidgetProcessor::class,
    'trend'       => TrendWidgetProcessor::class,
    'onboarding'  => OnboardingWidgetProcessor::class,
    'action_card' => ActionCardWidgetProcessor::class,
    'activity_log' => ActivityLogWidgetProcessor::class,
    'profile_header' => ProfileHeaderWidgetProcessor::class,
    'turnover_rate' => TurnoverRateWidgetProcessor::class,
    'enps'          => ENPSWidgetProcessor::class,
    'absenteeism_rate' => AbsenteeismRateWidgetProcessor::class,
    'goal_completion'  => GoalCompletionRateWidgetProcessor::class,
    'training_completion' => TrainingCompletionRateWidgetProcessor::class,
    'headcount_vs_budget' => HeadcountVsBudgetWidgetProcessor::class,
    'diversity_index' => DiversityIndexWidgetProcessor::class,
    'offer_acceptance' => OfferAcceptanceRateWidgetProcessor::class,
];
```

### 4.7 Key Traits

| Trait | Location | Purpose |
|-------|----------|---------|
| `HasSettings` | [`src/Traits/HasSettings.php`](src/Traits/HasSettings.php:8) | Polymorphic settings via `SystemSetting` model with caching |
| `HasNavItems` | [`src/Traits/HasNavItems.php`](src/Traits/HasNavItems.php:5) | Default navigation items (dashboard, profile, account, help, settings) |
| `HasColumnPreferences` | [`src/Traits/DataTables/HasColumnPreferences.php`](src/Traits/DataTables/HasColumnPreferences.php) | Session-based column visibility persistence |
| `HasBladeRendering` | [`src/Traits/FieldTypes/HasBladeRendering.php`](src/Traits/FieldTypes/HasBladeRendering.php) | Blade rendering with inline/modal/drawer fallback |
| `HasAutoGenerate` | [`src/Traits/FieldTypes/HasAutoGenerate.php`](src/Traits/FieldTypes/HasAutoGenerate.php) | Auto-generate button rendering for fields |
| `HasApproval` | [`src/Traits/Approvals/HasApproval.php`](src/Traits/Approvals/HasApproval.php) | Approval workflow trait for models |
| `HandlesToggleState` | [`src/Traits/Buttons/HandlesToggleState.php`](src/Traits/Buttons/HandlesToggleState.php) | Toggle button state management |
| `NavigationFilter` | [`src/Traits/NavigationFilter.php`](src/Traits/NavigationFilter.php) | Permission-based nav item filtering |
| `AppliesFilters` | [`src/Traits/AppliesFilters.php`](src/Traits/AppliesFilters.php) and [`src/Traits/Filters/AppliesFilters.php`](src/Traits/Filters/AppliesFilters.php) | Filter application logic |
| `HasCurrencySymbol` | [`src/Traits/HasCurrencySymbol.php`](src/Traits/HasCurrencySymbol.php) | Currency symbol resolution |
| `HasCacheInvalidator` | [`src/Traits/HasCacheInvalidator.php`](src/Traits/HasCacheInvalidator.php) | Cache invalidation helpers |
| `ResolvesExportValues` | [`src/Traits/ResolvesExportValues.php`](src/Traits/ResolvesExportValues.php) | Export value resolution |
| `HasHintField` | [`src/Traits/FieldTypes/HasHintField.php`](src/Traits/FieldTypes/HasHintField.php) | Hint/tooltip rendering for fields |
| `HandlesRelationshipGroupBy` | [`src/Traits/Widgets/HandlesRelationshipGroupBy.php`](src/Traits/Widgets/HandlesRelationshipGroupBy.php) | Relationship grouping for widgets |
| `ResolvesDateStrings` | [`src/Traits/Widgets/ResolvesDateStrings.php`](src/Traits/Widgets/ResolvesDateStrings.php) | Date string resolution for widgets |

### 4.8 Lifecycle Hooks & Extension Points

Business modules can tap into the following extension points:

1. **Event Listeners** — Auto-discovered from `app/Modules/{Module}/Listeners/`. The [`ModuleServiceProvider`](src/Providers/ModuleServiceProvider.php:121) uses reflection on `handle()` method signatures to detect event types.

2. **Onboarding Conditions** — Implement [`OnboardingCondition`](src/Contracts/OnboardingCondition.php:5) and reference the class in `app_onboarding.php` config under `condition`.

3. **Navigation Config** — `app/Modules/{Module}/Config/navigation.php` is consumed by [`NavigationLayout`](src/Components/NavigationLayout.php) and filtered through [`NavigationFilter`](src/Traits/NavigationFilter.php).

4. **Settings Overrides** — The [`ConfigResolver::getSettingsOverrideFieldDefinition()`](src/Services/Config/ConfigResolver.php:56) method applies settings overrides for date formats, currency, and auto-generation patterns.

5. **Field Type Extension** — Add new field types by implementing [`FieldType`](src/Contracts/FieldTypes/FieldType.php:5) and registering in [`FieldFactory::$map`](src/Factories/FieldTypes/FieldFactory.php:25).

6. **Widget Extension** — Add new widget types by creating a processor class and registering in [`WidgetProcessor::$map`](src/Services/Widgets/WidgetProcessor.php:27).

---

## 5. Config-Driven Architecture Deep Dive

### 5.1 Config File Resolution Flow

```
Component requests config
        │
        ▼
ConfigResolver('hr.employee')
        │
        ▼
ModelConfigRepository::get('hr.employee')
        │
        ├─ Check cache: 'model_config_hr_employee'
        │   └─ Cache hit → return cached array
        │
        └─ Cache miss → loadFromFile('hr.employee')
                │
                ├─ Split key: ['hr', 'employee']
                ├─ Module: ucfirst('hr') → 'Hr'
                ├─ Path: app/Modules/Hr/Data/employee.php
                │
                ├─ File exists → require $filePath → cache → return
                └─ File missing → throw InvalidArgumentException
```

### 5.2 ModelConfigRepository

**Location**: [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php:8)

Key behaviors:
- **Dot-notation keys**: `'hr.employee'` → `app/Modules/Hr/Data/employee.php`
- **Nested paths**: `'hr.dashboards.overview'` → `app/Modules/Hr/Data/dashboards/overview.php`
- **Forever caching**: Configs are cached indefinitely using `Cache::rememberForever()`
- **Flush support**: `forget($key)` for single key, `flush()` for all keys via index tracking
- **Singleton binding**: Registered as singleton in [`UILibraryServiceProvider`](src/Providers/UILibraryServiceProvider.php:91)

### 5.3 ConfigResolver

**Location**: [`src/Services/Config/ConfigResolver.php`](src/Services/Config/ConfigResolver.php:6)

Typed accessor methods:

| Method | Config Key | Default | Purpose |
|--------|-----------|---------|---------|
| `getModel()` | `model` | `''` | Fully qualified model class name |
| `getModelName()` | (derived) | — | Short class name from model FQCN |
| `getModuleName()` | (derived) | — | Module name extracted from model namespace |
| `getFieldDefinitions()` | `fieldDefinitions` | `[]` | All field definitions keyed by field name |
| `getFieldGroups()` | `fieldGroups` | `[]` | Field grouping for forms (tabs/sections) |
| `getControls()` | `controls` | (all defaults) | Table controls: files, bulkActions, perPage, search, etc. |
| `getRelations()` | `relations` | `[]` | Relationship definitions |
| `getHiddenFields()` | `hiddenFields` | `[...]` | Fields hidden on table, newForm, editForm, query, detail |
| `getSwitchViews()` | `switchViews` | `[]` | Alternative view modes (card, list, etc.) |
| `getMoreActions()` | `moreActions` | `[]` | Additional row actions beyond defaults |
| `getReports()` | `reports` | `[]` | Report definitions keyed by report key |
| `getReport($key)` | `reports.$key` | `null` | Single report definition |

Settings override method:
```php
public function getSettingsOverrideFieldDefinition(string $field): array
```
Applies date format, currency, and auto-generation pattern overrides from [`SettingsManager`](src/Services/Settings/SettingsManager.php).

### 5.4 DataTable/Form/Detail Config Schema

A single config file (e.g., `app/Modules/Hr/Data/employee.php`) drives all three components. Below is the comprehensive schema with every known key:

```php
return [
    // ── MODEL ─────────────────────────────────────────────
    'model' => 'App\\Modules\\Hr\\Models\\Employee',  // Required: FQCN of the Eloquent model

    // ── FIELD DEFINITIONS ─────────────────────────────────
    // Keyed by field name. Each field drives form rendering, table column display,
    // detail view display, inline editing, and validation.
    'fieldDefinitions' => [
        'first_name' => [
            'field_type'    => 'string',        // Maps to FieldFactory: 'string'|'text'|'select'|'datepicker'|'timepicker'|'datetimepicker'|'checkbox'|'boolcheckbox'|'boolradio'|'file'|'image'|'photo'|'picture'|'textarea'|'livewire-searchable-select'|'morph_to_select'|'password'|'policy_calculation_builder'
            'label'         => 'First Name',     // Display label
            'required'      => true,             // Visual required indicator
            'validation'    => 'required|string|max:255',  // Laravel validation rules string
            'sortable'      => true,             // Column is sortable in table
            'searchable'    => true,             // Column is searchable in table
            'visible'       => true,             // Column visible by default in table
            'default'       => null,             // Default value for new records
            'placeholder'   => 'Enter first name',
            'help_text'     => 'Legal first name',
            'autoGenerate'  => false,            // Enable auto-generation button
            'generator'     => [                 // Auto-generation config (if autoGenerate=true)
                'pattern'   => 'EMP-{YYYY}-{####}',
            ],
            // For select/relationship fields:
            'options'       => [],               // Static options array [value => label]
            'options_model' => null,             // Model class for dynamic options
            'options_method' => 'pluck',         // Method to call on options_model
            'relationship'  => null,             // Relationship name on the model
            'multiple'      => false,            // Multi-select
            // For file fields:
            'fileTypes'     => ['jpg','jpeg','png','pdf','doc','docx'],
            'maxSizeMB'     => 1,                // Max file size in MB
            'disk'          => 'public',         // Storage disk
            'path'          => 'uploads',        // Storage path
        ],
        // ... more fields
    ],

    // ── FIELD GROUPS ──────────────────────────────────────
    // Organizes form fields into tabs/sections
    'fieldGroups' => [
        [
            'key'    => 'personal',
            'label'  => 'Personal Information',
            'icon'   => 'fa-user',
            'fields' => ['first_name', 'last_name', 'email', 'phone'],
        ],
        [
            'key'    => 'employment',
            'label'  => 'Employment Details',
            'icon'   => 'fa-briefcase',
            'fields' => ['employee_id', 'department_id', 'position', 'start_date'],
        ],
    ],

    // ── CONTROLS ──────────────────────────────────────────
    // Table-level controls. Use 'all' to enable everything.
    'controls' => [
        'files' => [
            'export' => ['xls', 'csv', 'pdf'],   // Enabled export formats
            'import' => ['xls', 'csv'],           // Enabled import formats
            'print'  => true,                     // Print button
        ],
        'bulkActions' => [
            'export' => ['xls', 'csv', 'pdf'],    // Bulk export formats
            'delete' => true,                     // Bulk delete
        ],
        'perPage'          => [10, 25, 50, 100],  // Pagination options
        'search'           => true,               // Global search bar
        'showHideColumns'  => true,               // Column visibility toggling
        'filterColumns'    => true,               // Per-column filtering
        'addButton'        => true,               // "Add New" button
        'editable'         => true,               // Inline editing enabled
    ],

    // ── HIDDEN FIELDS ─────────────────────────────────────
    'hiddenFields' => [
        'onTable'    => [],    // Fields hidden from table columns
        'onNewForm'  => [],    // Fields hidden on create form
        'onEditForm' => [],    // Fields hidden on edit form
        'onQuery'    => [],    // Fields excluded from queries
        'onDetail'   => [],    // Fields hidden from detail view
    ],

    // ── RELATIONS ─────────────────────────────────────────
    'relations' => [
        'department' => [
            'model'     => 'App\\Modules\\Hr\\Models\\Department',
            'foreign_key' => 'department_id',
            'display'   => 'name',
        ],
    ],

    // ── SWITCH VIEWS ──────────────────────────────────────
    // Alternative view modes for the table
    'switchViews' => [
        'card' => ['label' => 'Card View', 'icon' => 'fa-th-large'],
        'list'  => ['label' => 'List View', 'icon' => 'fa-list'],
    ],

    // ── ACTIONS ───────────────────────────────────────────
    'moreActions' => [
        [
            'label'  => 'Send Welcome Email',
            'action' => 'sendWelcomeEmail',
            'icon'   => 'fa-envelope',
            'permission' => 'hr.send-welcome-email',
        ],
    ],

    // ── REPORTS ───────────────────────────────────────────
    'reports' => [
        'headcount' => [
            'label' => 'Headcount Report',
            'type'  => 'tabular',  // 'tabular' or 'dashboard'
        ],
    ],
];
```

### 5.5 How Config Drives Validation

The [`DataTableFormValidationService`](src/Services/Validation/DataTableFormValidationService.php:9) generates validation rules dynamically:

1. Iterates over `fieldDefinitions`
2. For each field, calls `FieldFactory::make($name, $definition)` to get a `FieldType` instance
3. Calls `$fieldObj->getValidationRules()` for field-type-specific rules
4. Falls back to `definition['validation']` string if no field-type rules
5. Falls back to default file validation for `field_type === 'file'`
6. Adjusts `unique` rules for edit mode (appends record ID)
7. Skips hidden fields based on `hiddenFields.onNewForm` / `hiddenFields.onEditForm`
8. Always validates file fields if present in request
9. Validates password fields only when changed on edit or always on create

### 5.6 How Config Drives Import/Export

- **Export**: [`DataTableExport`](src/Services/Exports/DataTableExport.php) reads `fieldDefinitions` to determine columns, applies `hiddenFields.onQuery` exclusions, and uses `controls.files.export` for format options
- **Import Template**: [`TemplateExport`](src/Services/Exports/TemplateExport.php) generates Excel templates with [`LookupSheet`](src/Services/Exports/LookupSheet.php) (relationship options), [`OptionsReferenceSheet`](src/Services/Exports/OptionsReferenceSheet.php) (field options), and [`TemplateDataSheet`](src/Services/Exports/TemplateDataSheet.php) (data entry columns)
- **Import Processing**: [`ImportProcessor`](src/Services/Imports/ImportProcessor.php) (singleton) processes uploaded files, maps columns, and creates/updates records

---

## 6. Module Conventions & Registration Protocol

### 6.1 Mandatory Module Structure

Every business module under `app/Modules/{ModuleName}/` must include at minimum:

```
app/Modules/{ModuleName}/
├── Data/                         # REQUIRED: Config files for tables/forms/details
│   └── {Entity}.php              # At least one entity config
├── Models/                       # REQUIRED: Eloquent models
├── Resources/
│   └── views/                    # REQUIRED: Blade views (auto-registered)
└── Routes/
    └── web.php                   # RECOMMENDED: Module-specific routes
```

Optional but strongly recommended:
- `Database/Migrations/` — Auto-loaded by [`ModuleServiceProvider`](src/Providers/ModuleServiceProvider.php:392)
- `Http/Controllers/` — Module controllers
- `Http/Livewire/` — Module-specific Livewire components
- `Http/Requests/` — Form request validation classes
- `Listeners/` — Auto-discovered event listeners
- `Services/` — Business logic services
- `Config/navigation.php` — Navigation items for the module
- `Data/Dashboards/` — Dashboard widget definitions
- `Data/reports/` — Report definitions

### 6.2 ModuleServiceProvider Registration Protocol

**Location**: [`src/Providers/ModuleServiceProvider.php`](src/Providers/ModuleServiceProvider.php:20)

The provider executes the following registration sequence on `boot()`:

```
boot()
├── registerPublishables()           # qf-public-assets, qf-modules
├── registerModuleConfig()           # Global configs: app_setup, app_tour, app_onboarding, app_general_settings
├── setupModules()
│   ├── registerModuleViewAlias()    # Each module's Resources/views → lowercase module alias
│   ├── registerModuleRoutes()       # Library routes → module web routes → module api routes → system routes
│   ├── registerModuleMigrations()   # Each module's Database/Migrations/
│   └── registerModuleEvents()       # Each module's Listeners/ (with production caching)
└── registerAppOnboardingCnfig()     # Spatie Onboard steps from app_onboarding config
```

### 6.3 View Namespace Registration

Each module's `Resources/views/` directory is registered as a view namespace using the **lowercase module name**:

```php
// ModuleServiceProvider::registerModuleViewAlias()
$alias = strtolower($moduleName);  // 'Hr' → 'hr', 'Admin' → 'admin'
$this->loadViewsFrom($viewPath, $alias);
```

Usage: `view('hr::dashboard')`, `view('admin::users.index')`

### 6.4 Route Loading Order

The route loading order in [`registerModuleRoutes()`](src/Providers/ModuleServiceProvider.php:246) is critical:

1. **Library routes** — [`src/Routes/web.php`](src/Routes/web.php) and `src/Routes/api.php`
2. **Non-system module web routes** — `app/Modules/*/Routes/web.php` (excluding 'system')
3. **Non-system module API routes** — `app/Modules/*/Routes/api.php` (excluding 'system')
4. **System module web routes** — `app/Modules/System/Routes/web.php` (catch-all, loaded LAST)
5. **System module API routes** — `app/Modules/System/Routes/api.php`

This ensures explicit module routes take precedence over the catch-all pattern.

### 6.5 Event Listener Auto-Discovery

[`registerModuleEvents()`](src/Providers/ModuleServiceProvider.php:121) scans each module's `Listeners/` directory and:

1. Finds all PHP files
2. Resolves the FQCN: `App\Modules\{ModuleName}\Listeners\{ClassName}`
3. Uses reflection on the `handle()` method to detect the event type from the first parameter's type hint
4. Registers with `Event::listen($eventClass, $listenerClass)`
5. Caches the listener map in production (`cache()->forever('module_event_listeners', $map)`)

### 6.6 Global Config Merging

[`registerModuleConfig()`](src/Providers/ModuleServiceProvider.php:307) merges global configs from `app/Modules/`:

| File | Config Key |
|------|-----------|
| `app/Modules/app_setup.php` | `app_setup` |
| `app/Modules/app_tour.php` | `app_tour` |
| `app/Modules/app_onboarding.php` | `app_onboarding` |
| `app/Modules/app_general_settings.php` | `app_general_settings` |

Dashboard configs from `app/Modules/*/Data/Dashboards/*.php` are merged with keys like `hr_employee_overview`.

Report configs from `app/Modules/*/Data/reports/*.php` are merged with keys like `hr_headcount` and registered in `config('reports.registered')`.

### 6.7 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Module directory | PascalCase | `Hr`, `Admin`, `System` |
| View namespace | lowercase | `hr`, `admin`, `system` |
| Config file | camelCase or snake_case `.php` | `employee.php`, `payroll_run.php` |
| Config key (dot notation) | `{lowercase_module}.{filename}` | `hr.employee`, `admin.user` |
| Dashboard config key | `{lowercase_module}_{filename}` | `hr_employee_overview` |
| Report config key | `{lowercase_module}_{filename}` | `hr_headcount` |
| Livewire component alias | `qf.{kebab-case}` | `qf.data-table`, `qf.payroll-run-wizard` |
| Blade component tag | `<x-qf::{kebab-case}>` | `<x-qf::text-field>` |
| Model namespace | `App\Modules\{ModuleName}\Models` | `App\Modules\Hr\Models\Employee` |
| Listener namespace | `App\Modules\{ModuleName}\Listeners` | `App\Modules\Hr\Listeners\SendWelcomeEmail` |

### 6.8 Catch-All Route Pattern

The System module at `app/Modules/System/Routes/web.php` contains the catch-all:

```php
Route::get('/{module}/{view}/{id?}', function ($module, $view, $id = null) {
    // Resolves view: {module}::{view}
    // Passes $id to the view
});
```

This is loaded LAST so explicit module routes take precedence.

---

## 7. Extension & Customization Guide

### 7.1 Recipe: Add a New FieldType

**Step 1**: Create the field type class in [`src/Components/FieldTypes/`](src/Components/FieldTypes/):

```php
// src/Components/FieldTypes/CurrencyField.php
namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;

class CurrencyField implements FieldType
{
    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function renderForm($value = null): string
    {
        return view('qf::components.fields.currency', [
            'name' => $this->name,
            'definition' => $this->definition,
            'value' => $value,
        ])->render();
    }

    public function renderTable($value, $record): string
    {
        $symbol = $this->definition['currency_symbol'] ?? '$';
        return $symbol . number_format($value, 2);
    }

    public function renderDetail($value, $record): string
    {
        return $this->renderTable($value, $record);
    }

    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        return view('qf::components.fields.inline-editor.text', [
            'name' => $this->name,
            'value' => $value,
            'extra' => $extra,
        ])->render();
    }

    public function getValidationRules(): array
    {
        return [$this->name => $this->definition['validation'] ?? 'numeric'];
    }

    public function getOptions(): array { return []; }
    public function isRelationship(): bool { return false; }
    public function getRelationshipConfig(): ?array { return null; }
    public function getLabel(): string { return $this->definition['label'] ?? $this->name; }
    public function getName(): string { return $this->name; }
}
```

**Step 2**: Register in [`FieldFactory`](src/Factories/FieldTypes/FieldFactory.php:25):

```php
protected array $map = [
    // ... existing mappings
    'currency' => CurrencyField::class,
];
```

**Step 3**: Create the Blade view at `src/Resources/views/components/fields/currency.blade.php`.

**Step 4**: Use in any module config:
```php
'fieldDefinitions' => [
    'salary' => [
        'field_type' => 'currency',
        'label' => 'Salary',
        'currency_symbol' => '₦',
        'validation' => 'required|numeric|min:0',
    ],
],
```

### 7.2 Recipe: Create a New Business Module

**Step 1**: Create the module directory structure:
```bash
mkdir -p app/Modules/Billing/{Data,Models,Resources/views,Routes,Database/Migrations,Listeners,Services}
```

**Step 2**: Create a model config at `app/Modules/Billing/Data/invoice.php`:
```php
return [
    'model' => 'App\\Modules\\Billing\\Models\\Invoice',
    'fieldDefinitions' => [
        'invoice_number' => [
            'field_type' => 'string',
            'label' => 'Invoice #',
            'validation' => 'required|string|max:50',
            'autoGenerate' => true,
            'generator' => ['pattern' => 'INV-{YYYY}-{####}'],
        ],
        // ... more fields
    ],
    'fieldGroups' => [
        ['key' => 'details', 'label' => 'Invoice Details', 'fields' => ['invoice_number', 'client_id', 'amount', 'due_date']],
    ],
    'controls' => 'all',
];
```

**Step 3**: Create the Eloquent model at `app/Modules/Billing/Models/Invoice.php`.

**Step 4**: Create views at `app/Modules/Billing/Resources/views/`:
- `index.blade.php` — Uses `<livewire:qf.data-table config-key="billing.invoice" />`
- `dashboard.blade.php` — Module dashboard

**Step 5**: Add routes at `app/Modules/Billing/Routes/web.php` (optional; catch-all handles basic view rendering).

**Step 6**: Add navigation at `app/Modules/Billing/Config/navigation.php` (optional).

The module is now auto-discovered. No service provider registration needed.

### 7.3 Recipe: Override a Library Component

**Step 1**: Create a module-specific component extending the library version:
```php
// app/Modules/Hr/Http/Livewire/DataTables/EmployeeTable.php
namespace App\Modules\Hr\Http\Livewire\DataTables;

use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable;

class EmployeeTable extends DataTable
{
    // Override methods as needed
    protected function getCustomActions(): array
    {
        return array_merge(parent::getCustomActions(), [
            'send_onboarding' => 'Send Onboarding Email',
        ]);
    }
}
```

**Step 2**: Register in the consuming app's `AppServiceProvider`:
```php
Livewire::component('hr.employee-table', EmployeeTable::class);
```

**Step 3**: Use the overridden component in views.

### 7.4 Recipe: Add a New Widget Type

**Step 1**: Create a widget processor in [`src/Widgets/`](src/Widgets/):
```php
// src/Widgets/GaugeWidgetProcessor.php
namespace QuickerFaster\UILibrary\Widgets;

class GaugeWidgetProcessor
{
    public function process(array $definition): array
    {
        return [
            'type' => 'gauge',
            'title' => $definition['title'] ?? 'Gauge',
            'value' => $this->calculateValue($definition),
            'min' => $definition['min'] ?? 0,
            'max' => $definition['max'] ?? 100,
            'width' => $definition['width'] ?? 3,
        ];
    }

    protected function calculateValue(array $definition): float
    {
        // Custom calculation logic
        return 75.5;
    }
}
```

**Step 2**: Register in [`WidgetProcessor`](src/Services/Widgets/WidgetProcessor.php:27):
```php
protected array $map = [
    // ... existing mappings
    'gauge' => GaugeWidgetProcessor::class,
];
```

**Step 3**: Create the Blade view at `src/Resources/views/widgets/gauge.blade.php`.

**Step 4**: Use in dashboard configs:
```php
'widgets' => [
    ['type' => 'gauge', 'title' => 'Revenue Target', 'min' => 0, 'max' => 100000],
],
```

### 7.5 NavigationLayout Extension Guide

To extend the shared layout:

1. **Add navigation items** — Create `app/Modules/{Module}/Config/navigation.php`:
```php
return [
    'items' => [
        ['key' => 'invoices', 'label' => 'Invoices', 'route' => 'billing.invoices', 'icon' => 'fa-file-invoice'],
    ],
    'contexts' => [
        'billing' => [
            'label' => 'Billing',
            'items' => ['invoices', 'payments', 'subscriptions'],
        ],
    ],
];
```

2. **Permission-based visibility** — The [`NavigationFilter`](src/Traits/NavigationFilter.php) trait filters items based on Spatie permissions.

3. **Default nav items** — [`HasNavItems`](src/Traits/HasNavItems.php:7) provides: dashboard, profile, account, help, settings.

4. **Sidebar state** — Controlled via `sidebar.initial_state` in navigation config.

---

## 8. Integration & Dependency Map

### 8.1 Composer Dependencies and Their Roles

| Package | Version | Role |
|---------|---------|------|
| `livewire/livewire` | `^3` | Interactive, stateful UI components (tables, forms, modals, wizards) |
| `barryvdh/laravel-dompdf` | `^3.0` | PDF export rendering for data tables and detail pages |
| `maatwebsite/excel` | `^3.1` | Excel/CSV import and export infrastructure |
| `laravel/fortify` | `^1.0` | Authentication scaffolds (login, register, password reset, 2FA) |
| `laravel/socialite` | `^5.0` | OAuth social authentication (Google, GitHub) |
| `spatie/laravel-permission` | `^6.21` | Role and permission management |
| `spatie/laravel-onboard` | `^2.6` | Onboarding checklist and step flows |

### 8.2 Service Provider Wiring

```
┌─────────────────────────────────────────────────────────────┐
│                    UILibraryServiceProvider                  │
│  register():                                                │
│    • ImportProcessor (singleton)                            │
│    • SettingsManager (singleton, 3-tier resolver chain)     │
│    • ModelConfigRepository (singleton)                      │
│    • path.public binding (shared hosting aware)             │
│  boot():                                                    │
│    • registerCommands()                                     │
│    • registerLivewireComponents() — 50+ components          │
│    • registerPublishables() — views, config                 │
│    • registerFortifyViews() — login, register, reset        │
│    • registerSocialiteProviders() — Google, GitHub          │
│    • loadViewsFrom(__DIR__.'/../Resources/views', 'qf')     │
│    • Blade::component('qf::layouts.app', 'layout')          │
│    • Blade::component('qf::layouts.guest', 'guest-layout')  │
│    • Blade::component('qf::components.breadcrumb', ...)     │
│    • Blade::componentNamespace('QuickerFaster\\...', 'qf')  │
│    • Blade::directive('setting', ...)                       │
│    • mergeConfigFrom(... 'quicker-faster-ui')               │
│    • loadTranslationsFrom(... 'qf')                         │
├─────────────────────────────────────────────────────────────┤
│                    ModuleServiceProvider                     │
│  boot():                                                    │
│    • registerPublishables() — qf-public-assets, qf-modules  │
│    • registerModuleConfig() — global + dashboard + report   │
│    • setupModules()                                         │
│      ├── registerModuleViewAlias()                          │
│      ├── registerModuleRoutes()                             │
│      ├── registerModuleMigrations()                         │
│      └── registerModuleEvents()                             │
│    • registerAppOnboardingCnfig() — Spatie Onboard steps    │
├─────────────────────────────────────────────────────────────┤
│                    FortifyServiceProvider                    │
│  boot():                                                    │
│    • Fortify::createUsersUsing(CreateNewUser::class)        │
│    • Fortify::updateUserProfileInformationUsing(...)        │
│    • Fortify::updateUserPasswordsUsing(...)                 │
│    • Fortify::resetUserPasswordsUsing(...)                  │
│    • RateLimiter: login (5/min), two-factor (5/min)         │
└─────────────────────────────────────────────────────────────┘
```

### 8.3 Inter-Package Communication Patterns

| Pattern | Implementation | Example |
|---------|---------------|---------|
| **Service binding** | Singleton bindings in service providers | `ImportProcessor`, `ModelConfigRepository`, `SettingsManager` |
| **Event listeners** | Auto-discovered via reflection on `handle()` signatures | `app/Modules/Hr/Listeners/SendWelcomeEmail.php` |
| **Config merge** | `mergeConfigFrom()` in service providers | Dashboard configs: `hr_employee_overview` |
| **View namespace** | `loadViewsFrom()` with module alias | `view('hr::dashboard')` |
| **Blade component** | `Blade::component()` and `Blade::componentNamespace()` | `<x-layout>`, `<x-qf::text-field>` |
| **Livewire registration** | `Livewire::component('qf.name', Class::class)` | `<livewire:qf.data-table>` |
| **Blade directive** | `Blade::directive('setting', ...)` | `@setting('date_format', 'Y-m-d')` |

### 8.4 Database Assumptions

The library's scaffolds assume the consuming app has:

| Requirement | Details |
|-------------|---------|
| `users` table | With columns: `has_seen_tour` (boolean), `company_id` (nullable FK) |
| `system_settings` table | Polymorphic: `settingable_type`, `settingable_id`, `key`, `value`, `group` |
| `system` table | Singleton system record (id=1) for system-level defaults |
| `exports` table | Export job tracking |
| `export_chunks` table | Export chunk file tracking |
| `imports` table | Import job tracking |
| `import_chunks` table | Import chunk tracking |
| `saved_filters` table | User-saved filter presets |
| `saved_reports` table | User-saved reports |
| `personal_access_tokens` table | With `expires_at` column |
| Spatie Permission tables | `roles`, `permissions`, `model_has_roles`, etc. |

### 8.5 Settings Architecture

The [`SettingsManager`](src/Services/Settings/SettingsManager.php:7) implements a 3-tier cascading resolver:

```
SettingsManager::get('date_format', 'Y-m-d')
        │
        ▼
    Check cache: 'setting_resolved.{context_hash}.date_format'
        │
        ▼ (cache miss)
    Resolver 1: user
        └─ auth()->user()?->getSetting('date_format')
        └─ Uses HasSettings trait → SystemSetting model
        └─ Returns value or null
        │
        ▼ (null → continue)
    Resolver 2: company
        └─ Company::find($companyId)?->getSetting('date_format')
        └─ Uses HasSettings trait → SystemSetting model
        └─ Returns value or null
        │
        ▼ (null → continue)
    Resolver 3: system
        └─ System::find(1)?->getSetting('date_format')
        └─ Uses HasSettings trait → SystemSetting model
        └─ Returns value or null
        │
        ▼ (null → return default)
    Return 'Y-m-d'
```

Cache key includes context hash: `md5($userId . '_' . $module . '_' . $companyId)`, cached for 3600 seconds.

The [`HasSettings`](src/Traits/HasSettings.php:8) trait provides:
- `settings()` — polymorphic `morphMany(SystemSetting::class, 'settingable')`
- `getSetting($key, $default)` — cached retrieval
- `setSetting($key, $value, $group)` — update or create with cache invalidation
- `forgetSetting($key)` — delete with cache invalidation

---

## 9. AI Agent Quick-Start Protocol

### 9.1 Decision Tree: "Given Task X, Which Files Do I Touch?"

```
TASK: "I need to..."
│
├─ "...add a new field to an existing form/table"
│   └─ Edit: app/Modules/{Module}/Data/{Entity}.php
│       └─ Add entry to 'fieldDefinitions' array
│       └─ Add field name to appropriate 'fieldGroups' group
│       └─ If hidden in some contexts, add to 'hiddenFields'
│
├─ "...create a new CRUD module for an entity"
│   └─ Create: app/Modules/{Module}/Data/{Entity}.php
│   └─ Create: app/Modules/{Module}/Models/{Entity}.php
│   └─ Create: app/Modules/{Module}/Resources/views/index.blade.php
│   │   └─ Use: <livewire:qf.data-table config-key="{module}.{entity}" />
│   └─ Create: app/Modules/{Module}/Resources/views/dashboard.blade.php (optional)
│   └─ Create: app/Modules/{Module}/Routes/web.php (optional)
│   └─ Create: app/Modules/{Module}/Database/Migrations/ (optional)
│
├─ "...add a new field type (e.g., color picker, rich text)"
│   └─ Create: src/Components/FieldTypes/{NewType}Field.php
│   │   └─ Implement: QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType
│   └─ Edit: src/Factories/FieldTypes/FieldFactory.php
│   │   └─ Add to $map: 'new_type' => {NewType}Field::class
│   └─ Create: src/Resources/views/components/fields/{new_type}.blade.php
│   └─ Create: src/Resources/views/components/fields/inline-editor/{new_type}.blade.php
│
├─ "...add a new widget type for dashboards"
│   └─ Create: src/Widgets/{NewWidget}WidgetProcessor.php
│   └─ Edit: src/Services/Widgets/WidgetProcessor.php
│   │   └─ Add to $map: 'new_widget' => {NewWidget}WidgetProcessor::class
│   └─ Create: src/Resources/views/widgets/{new_widget}.blade.php
│
├─ "...add navigation items for a module"
│   └─ Create/Edit: app/Modules/{Module}/Config/navigation.php
│   └─ Reference: src/Traits/HasNavItems.php (for default items)
│   └─ Reference: src/Traits/NavigationFilter.php (for permission filtering)
│
├─ "...add an event listener in a module"
│   └─ Create: app/Modules/{Module}/Listeners/{ListenerName}.php
│   │   └─ Must have: public function handle(EventType $event) method
│   │   └─ Event type auto-detected from handle() parameter type hint
│   └─ No registration needed — auto-discovered by ModuleServiceProvider
│
├─ "...add onboarding steps"
│   └─ Edit: app/Modules/app_onboarding.php
│   │   └─ Add step with: title, link, cta, model (or condition)
│   └─ Create condition class (optional):
│   │   └─ src/Conditions/Onboarding/{ConditionName}.php
│   │   └─ Implement: QuickerFaster\UILibrary\Contracts\OnboardingCondition
│
├─ "...add a social login provider"
│   └─ Edit: src/Config/quicker-faster-ui.php
│   │   └─ Add provider config under 'socialite.providers'
│   └─ Edit: src/Providers/UILibraryServiceProvider.php
│   │   └─ Add provider to $providers array in registerSocialiteProviders()
│   └─ Edit: src/Routes/web.php
│   │   └─ Add provider to where() clause in socialite routes
│
├─ "...change layout/navigation behavior"
│   └─ Edit: src/Components/NavigationLayout.php (main shell)
│   └─ Edit: src/Http/Livewire/Layouts/Navs/TopNav.php
│   └─ Edit: src/Http/Livewire/Layouts/Navs/Sidebar.php
│   └─ Edit: src/Http/Livewire/Layouts/Navs/BottomBar.php
│   └─ Edit: src/Resources/views/layouts/app.blade.php
│
├─ "...fix a validation issue on a form"
│   └─ Check: app/Modules/{Module}/Data/{Entity}.php → fieldDefinitions.{field}.validation
│   └─ Check: src/Services/Validation/DataTableFormValidationService.php
│   └─ Check: src/Components/FieldTypes/{Type}Field.php → getValidationRules()
│
├─ "...fix a config not loading"
│   └─ Check: src/Services/Config/ModelConfigRepository.php → loadFromFile()
│   │   └─ Verify dot-notation key matches file path
│   │   └─ Verify file exists at expected path
│   └─ Clear cache: Cache::forget('model_config_{key}')
│   └─ Or flush all: app(ModelConfigRepository::class)->flush()
│
├─ "...add a new Livewire component to the library"
│   └─ Create: src/Http/Livewire/{Category}/{ComponentName}.php
│   └─ Create: src/Resources/views/livewire/{category}/{component-name}.blade.php
│   └─ Edit: src/Providers/UILibraryServiceProvider.php
│   │   └─ Add: Livewire::component('qf.{alias}', {ComponentName}::class)
│
└─ "...debug route resolution issues"
    └─ Check load order: src/Providers/ModuleServiceProvider.php → registerModuleRoutes()
    └─ Check: src/Routes/web.php (library routes)
    └─ Check: app/Modules/{Module}/Routes/web.php (module routes)
    └─ Check: app/Modules/System/Routes/web.php (catch-all, loaded last)
```

### 9.2 Common Task Lookup Table

| Task | Primary Files | Secondary Files |
|------|--------------|-----------------|
| Add table column | `app/Modules/{Module}/Data/{Entity}.php` | `src/Components/FieldTypes/{Type}Field.php` |
| Add form field | `app/Modules/{Module}/Data/{Entity}.php` | `src/Services/Validation/DataTableFormValidationService.php` |
| Add detail section | `app/Modules/{Module}/Data/{Entity}.php` | `src/Http/Livewire/DataTables/DataTableDetail.php` |
| Add dashboard widget | `app/Modules/{Module}/Data/Dashboards/{Name}.php` | `src/Services/Widgets/WidgetProcessor.php`, `src/Widgets/*` |
| Add report | `app/Modules/{Module}/Data/reports/{Name}.php` | `src/Http/Livewire/Reports/ReportViewer.php` |
| Add navigation item | `app/Modules/{Module}/Config/navigation.php` | `src/Components/NavigationLayout.php` |
| Add module route | `app/Modules/{Module}/Routes/web.php` | `src/Providers/ModuleServiceProvider.php` |
| Add event listener | `app/Modules/{Module}/Listeners/{Name}.php` | `src/Providers/ModuleServiceProvider.php` (auto) |
| Add onboarding step | `app/Modules/app_onboarding.php` | `src/Providers/ModuleServiceProvider.php`, `src/Contracts/OnboardingCondition.php` |
| Add import/export format | `app/Modules/{Module}/Data/{Entity}.php` (controls) | `src/Services/Exports/DataTableExport.php` |
| Add bank file format | `src/Services/BankFiles/{Format}Generator.php` | `src/Services/BankFiles/BankFileGeneratorFactory.php` |
| Add settings page | `app/Modules/app_general_settings.php` | `src/Http/Livewire/Settings/SettingsPanel.php` |
| Add permission | Spatie migration/seeder | `src/Http/Livewire/AccessControls/PermissionManager.php` |
| Add approval workflow | `app/Modules/{Module}/Data/{Entity}.php` | `src/Services/Approvals/ApprovalEngine.php`, `src/Traits/Approvals/HasApproval.php` |
| Override library view | `resources/views/vendor/quicker-faster-ui/` | `src/Providers/UILibraryServiceProvider.php` (publish tag) |
| Add translation | `src/Resources/lang/{locale}/` | `src/Providers/UILibraryServiceProvider.php` |
| Change auth behavior | `src/Providers/FortifyServiceProvider.php` | `src/Resources/views/auth/` |
| Add social provider | `src/Config/quicker-faster-ui.php` | `src/Providers/UILibraryServiceProvider.php`, `src/Routes/web.php` |
| Add console command | `src/Commands/{Command}.php` | `src/Providers/UILibraryServiceProvider.php` |

### 9.3 Troubleshooting Guide

#### A. Config Not Found

**Symptoms**: Component renders with defaults only, unexpected empty state, or `InvalidArgumentException: Configuration not found for key: X`.

**Checks**:
1. Verify the config file exists at the expected path: `app/Modules/{Module}/Data/{file}.php`
2. Verify the dot-notation key matches: `{lowercase_module}.{filename}` (e.g., `hr.employee`)
3. Clear the config cache: `app(ModelConfigRepository::class)->forget('hr.employee')`
4. Check file permissions on the config file

#### B. Component Not Rendering

**Symptoms**: Blank output, missing section, or Livewire component not found.

**Checks**:
1. Verify the Livewire component is registered in [`UILibraryServiceProvider::registerLivewireComponents()`](src/Providers/UILibraryServiceProvider.php:204)
2. Verify the Blade view exists at the expected path
3. Verify the component alias uses the `qf.` prefix
4. For conditional components (HR payroll), verify the file exists at `app_path($config['path'])`

#### C. Route Conflict

**Symptoms**: Module view route does not resolve or hits the wrong route.

**Checks**:
1. Verify explicit module routes are in `app/Modules/{Module}/Routes/web.php`
2. Verify the System module catch-all is loaded LAST (check [`registerModuleRoutes()`](src/Providers/ModuleServiceProvider.php:246))
3. Verify the route pattern is not shadowed by a more specific route
4. Check `php artisan route:list` to see all registered routes

#### D. Validation Not Working

**Symptoms**: Form submits without validation or wrong rules applied.

**Checks**:
1. Verify `fieldDefinitions.{field}.validation` string is correct
2. Verify the field is not in `hiddenFields.onNewForm` or `hiddenFields.onEditForm`
3. Check [`DataTableFormValidationService::shouldValidateField()`](src/Services/Validation/DataTableFormValidationService.php:96)
4. For file fields, verify `field_type === 'file'` is set
5. Check the FieldType's `getValidationRules()` method

#### E. Settings Not Resolving

**Symptoms**: `@setting('key')` returns null or wrong value.

**Checks**:
1. Verify the `SystemSetting` model has records for the key at the appropriate level
2. Clear the settings cache: `app(SettingsManager::class)->flush('key')`
3. Verify the resolver chain in [`UILibraryServiceProvider::registerSettingsResolver()`](src/Providers/UILibraryServiceProvider.php:144)
4. Check that the user is authenticated (user resolver requires `auth()->user()`)

#### F. Module Not Auto-Discovered

**Symptoms**: Module views not found, routes not loaded, migrations not run.

**Checks**:
1. Verify the module directory exists at `app/Modules/{ModuleName}/`
2. Verify the directory name uses PascalCase
3. Verify `Resources/views/` exists for view namespace registration
4. Check that `ModuleServiceProvider` is registered in `config/app.php`
5. Clear the event listener cache: `Cache::forget('module_event_listeners')`

---

## 10. Identified Gaps & Recommendations

### 10.1 Missing Error Handling Strategy

**Gap**: No explicit strategy for missing config, missing views, invalid module names, or route authorization failures. The [`ConfigResolver`](src/Services/Config/ConfigResolver.php:25) throws `InvalidArgumentException` for missing configs, but there is no consistent exception layer.

**Recommendation**:
- Create a dedicated exception hierarchy under [`src/Exceptions/`](src/Exceptions/):
  - `ConfigNotFoundException` — extends `InvalidArgumentException`
  - `ModuleNotFoundException` — for invalid module names
  - `ViewResolutionException` — for missing views in catch-all route
  - `RecordNotAccessibleException` — already exists, expand usage
- Ensure all components fail predictably with a friendly fallback or clear error message
- Add a `render()` method to exceptions for consistent JSON/HTML error responses

### 10.2 Missing Testing Architecture

**Gap**: No test strategy is described or implemented for components, config resolution, routes, and form validation.

**Recommendation**:
- Add PHPUnit tests under a `tests/` directory in the library:
  - **Unit tests**: [`ConfigResolver`](src/Services/Config/ConfigResolver.php), [`FieldFactory`](src/Factories/FieldTypes/FieldFactory.php), [`WidgetProcessor`](src/Services/Widgets/WidgetProcessor.php), [`SettingsManager`](src/Services/Settings/SettingsManager.php), [`DataTableFormValidationService`](src/Services/Validation/DataTableFormValidationService.php)
  - **Feature tests**: Livewire component tests for [`DataTable`](src/Http/Livewire/DataTables/DataTable.php), [`DataTableForm`](src/Http/Livewire/DataTables/DataTableForm.php), [`Wizard`](src/Http/Livewire/Wizards/Wizard.php)
  - **Integration tests**: Module route discovery, module view rendering, event listener auto-registration
- Add CI configuration (GitHub Actions) to run tests on PR

### 10.3 Asset Compilation and Publishing Strategy

**Gap**: The package publishes public assets via `qf-public-assets` tag, but no clear asset build pipeline is described. Bootstrap 5 theme (Soft UI Dashboard) and custom CSS/JS are manually managed.

**Recommendation**:
- Document the asset build flow:
  1. Bootstrap 5 (Soft UI Dashboard) at [`public/bootstrap/`](public/bootstrap/)
  2. Custom CSS at [`public/assets/css/quicker-faster.css`](public/assets/css/quicker-faster.css)
  3. Custom JS at [`public/assets/js/quicker-faster.js`](public/assets/js/quicker-faster.js)
- Consider adding a Laravel Mix or Vite build pipeline for the library's assets
- Version assets explicitly and document the publish command: `php artisan vendor:publish --tag=qf-public-assets`

### 10.4 State Management for Wizards and Multi-Step Flows

**Gap**: Wizards ([`Wizard`](src/Http/Livewire/Wizards/Wizard.php), [`SetupWizard`](src/Http/Livewire/Wizards/SetupWizard.php), [`WizardForm`](src/Http/Livewire/Wizards/WizardForm.php)) are mentioned but their lifecycle and persistence strategy is not formally defined.

**Recommendation**:
- Introduce a `WizardState` service or trait that:
  - Persists wizard state in the session (for short-lived wizards)
  - Persists wizard state in the database (for long-running wizards like payroll)
  - Supports step validation, back/forward navigation, and state serialization
- Document the wizard config schema in [`WizardConfigResolver`](src/Services/Config/Wizards/WizardConfigResolver.php)

### 10.5 API vs Web Context Handling

**Gap**: The architecture mixes web views, interactive Livewire components, and business modules without a clear separation for API contexts. Module API routes are loaded but there is no shared API response convention.

**Recommendation**:
- Define whether the same module config can drive API responses (e.g., API resource classes generated from field definitions)
- Introduce a separate API contract layer where needed:
  - `ApiResourceContract` — for transforming models to API responses
  - `ApiValidationContract` — for API-specific validation rules
- Document the API route convention: `app/Modules/{Module}/Routes/api.php` auto-loaded with `api` middleware and prefix

### 10.6 Accessibility and Internationalization Standards

**Gap**: No accessibility (a11y) or i18n standards are described. Translations exist for `en` and `es` in [`src/Resources/lang/`](src/Resources/lang/) but coverage is minimal.

**Recommendation**:
- Define a minimum a11y standard:
  - All form fields must have associated `<label>` elements
  - All modals must trap focus and support Escape key
  - All data tables must have proper `aria` attributes
  - Color contrast must meet WCAG AA standards
- Expand translation coverage:
  - Add translation keys for all user-facing strings in Blade views
  - Use `__('qf::key')` consistently
  - Add `fr`, `de`, `ar` translations

### 10.7 Security Hardening for Catch-All Routes

**Gap**: The centralized route pattern `/{module}/{view}/{id?}` in the System module can be abused if authorization and validation are weak.

**Recommendation**:
- Enforce strict module allow-lists in the catch-all handler:
  ```php
  $allowedModules = ['hr', 'admin', 'billing', 'system'];
  if (!in_array($module, $allowedModules)) { abort(404); }
  ```
- Require explicit authorization checks per view using Laravel Gates or Policies
- Avoid exposing internal logic or model names through route parameters
- Add rate limiting to the catch-all route
- Sanitize `$module` and `$view` parameters to prevent directory traversal

### 10.8 Caching Strategy for Module Discovery

**Gap**: The [`ModuleServiceProvider`](src/Providers/ModuleServiceProvider.php) scans modules dynamically on every request in non-production environments. Event listener discovery already has production caching, but view namespace and route registration do not.

**Recommendation**:
- Extend production caching to:
  - **View namespaces**: Cache the module→view-path map
  - **Route files**: Cache the list of module route files
  - **Migration paths**: Cache the list of module migration directories
- Use a single cache key pattern: `qf_module_registry` storing all discovered module metadata
- Invalidate the cache when modules are added/removed (via `php artisan qf:clear-cache` command)
- The [`ModelConfigRepository`](src/Services/Config/ModelConfigRepository.php) already uses `Cache::rememberForever()` — ensure this is documented

### 10.9 Missing Documentation for Bank File Generators

**Gap**: The [`src/Services/BankFiles/`](src/Services/BankFiles/) directory contains generators for BACS, NACHA, NIBSS, and SEPA formats, but there is no contract/interface defined and no documentation.

**Recommendation**:
- Extract a `BankFileGenerator` interface from the existing implementations
- Document the config schema for bank file generation
- Add a `BankFileGeneratorFactory` registration pattern for custom formats
- Document supported formats and their configuration options

### 10.10 Missing Module Scaffold Command

**Gap**: There is no Artisan command to scaffold a new business module with the correct directory structure.

**Recommendation**:
- Create a `php artisan qf:make-module {name}` command that:
  1. Creates the directory structure under `app/Modules/{Name}/`
  2. Generates a starter `Data/{entity}.php` config
  3. Generates a starter model
  4. Generates starter views (index, dashboard)
  5. Optionally generates a migration
- Publish stubs via `qf-modules` tag (already partially implemented in [`ModuleServiceProvider::registerPublishables()`](src/Providers/ModuleServiceProvider.php:84))

---

## Final Architectural Summary

The QuickerFaster UI Library is a **layered architecture** with three core responsibilities:

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
└──────────────────────────────────────────────────────────────┘
```

**Key architectural invariants**:
- The library never imports from `App\Modules\*` (except in the service provider for conditional component registration)
- Business modules never modify library source files
- All UI behavior is configurable through PHP config files
- The `qf` prefix is used consistently across all namespaces (views, Blade, Livewire, translations)
- The System module catch-all route is always loaded last

If this blueprint is followed consistently, both human developers and AI agents will be able to understand where to add new functionality, how to extend the system, and how to preserve the intended separation of concerns.
