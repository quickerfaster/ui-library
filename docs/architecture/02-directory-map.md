# QuickerFaster UI Library — Directory Map & File Purpose Index

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`01-core-concepts.md`](./01-core-concepts.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`04-routing-and-views.md`](./04-routing-and-views.md)

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
├── Console/                               # Console kernel + commands
│   ├── Kernel.php                         # Registers package commands
│   └── Commands/
│       ├── InstallCommand.php             # Single-command install: `php artisan ui-library:install`
│       └── GenerateScheduledReports.php   # Artisan command for scheduled reports
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
│   ├── Kernel.php                         # Registers package commands
│   └── Commands/
│       ├── InstallCommand.php             # `ui-library:install` — single-command setup (config, views, migrations, assets, vendors, seed, auth, storage, cache)
│       └── GenerateScheduledReports.php   # `reports:generate-scheduled` — cron command for scheduled report generation
│
├── Contracts/                             # Interfaces and contracts
│   ├── OnboardingCondition.php            # Contract for onboarding step completion checks
│   ├── Approvals/
│   │   └── ApprovalModelResolver.php      # Contract for approval model resolution
│   ├── FieldTypes/
│   │   └── FieldType.php                  # Contract for field type implementations
│   ├── Modules/
│   │   └── ModuleContract.php             # Contract for module metadata
│   ├── Navigation/
│   │   ├── CompanyProvider.php            # Contract for company resolution (multi-tenant)
│   │   ├── NavigationProvider.php         # Contract for navigation item provision
│   │   └── WorkspaceResolver.php          # Contract for workspace context resolution (multi-tenant/role-based nav filtering)
│   ├── Settings/
│   │   └── SettingsProvider.php           # Contract for settings resolution
│   ├── Widgets/
│   │   └── Widget.php                     # Contract for widget processors
│   ├── Documents/
│   │   └── Documentable.php               # Polymorphic document contract
│   ├── Notifications/
│   │   ├── Notifiable.php                  # Polymorphic notification receiver contract
│   │   └── NotificationChannel.php         # Channel abstraction contract (send method)
│   ├── Reports/
│   │   └── Reportable.php                 # Scheduled report contract
│   └── Workflow/
│       └── Workflowable.php               # Contract for workflow-enabled models
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
│   │   ├── OrganizationSwitchController.php  # Phase 4.4 — Organization switching (route: company.switch); CompanyProvider-based authorization
│   │   └── Prints/
│   │       ├── GenericTablePrintController.php    # Print-friendly table view
│   │       └── GenericDetailPagePrintController.php # Print-friendly detail view
│   │
│   ├── ViewComposers/                      # View composition
│   │   └── SidebarComposer.php            # ⚠️ Half done — Composes sidebar data for views; registered in UILibraryServiceProvider
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
│       │   ├── AccessControlManager.php   # Consolidated access control UI (search + bulk toggles)
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
│       │       ├── MenuRenderer.php       # Dynamic menu renderer
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
│   ├── ProcessImportChunk.php             # Processes one chunk of an import
│   └── GenerateReportJob.php              # Queueable report generation job
│
├── Models/                                # Library-owned Eloquent models
│   ├── Export.php                         # Export job record
│   ├── ExportChunk.php                    # Export chunk file record
│   ├── Import.php                         # Import job record
│   ├── ImportChunk.php                    # Import chunk record
│   ├── SavedFilter.php                    # User-saved filter preset
│   ├── SavedReport.php                    # User-saved report
│   ├── System.php                         # System singleton model
│   ├── SystemSetting.php                  # Polymorphic settings (user/company/system)
│   ├── Document.php                       # Polymorphic document model with soft deletes
│   ├── Workflow.php                       # Workflow instance (polymorphic, multi-step)
│   ├── WorkflowStep.php                   # Workflow step (role-based, sequential)
│   ├── WorkflowAction.php                 # Workflow action audit trail
│   ├── Notification.php                   # Polymorphic notification (notifiable_type/id)
│   ├── NotificationTemplate.php           # Template model (type, channel, locale)
│   ├── NotificationPreference.php         # Per-user channel preference toggle
│   ├── NotificationLog.php                # Notification dispatch audit trail
│   └── ReportSchedule.php                 # Report schedule model
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
│   │   ├── ApprovalEngine.php             # Legacy approval workflow engine (deprecated — prefer WorkflowEngine)
│   │   └── ApprovalModelResolver.php      # Config-driven approval model resolution
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
│   ├── Navigation/
│   │   ├── NavigationManager.php           # Config-driven navigation: getSections(), 5-tier priority chain, context_groups
│   │   ├── NullCompanyProvider.php        # Default no-op company provider (returns empty)
│   │   ├── NullWorkspaceResolver.php      # Default no-op workspace resolver (returns empty context)
│   │   └── WorkspaceFilter.php            # Workspace-scoped navigation item filtering (feature gates, role constraints)
│   ├── Search/
│   │   └── SearchEngine.php               # Global search across modules
│   ├── Settings/
│   │   └── SettingsManager.php            # 3-tier settings resolver with caching
│   ├── System/
│   │   └── ApplicationInfo.php            # Application metadata service
│   ├── Validation/
│   │   └── DataTableFormValidationService.php  # Dynamic validation rule generation
│   ├── Widgets/
│   │   └── WidgetProcessor.php            # Maps widget type strings to processor classes
│   ├── Documents/
│   │   └── DocumentEngine.php             # Generic document engine (upload, generatePdf, generateExcel)
│   ├── Notifications/
│   │   ├── NotificationService.php         # Dispatch, getUnread, channel registration, template resolution
│   │   └── Channels/
│   │       ├── DatabaseChannel.php         # In-app (database) notification channel
│   │       └── MailChannel.php             # Email notification channel
│   ├── Reports/
│   │   └── ReportEngine.php               # Scheduled report engine
│   └── Workflow/
│       └── WorkflowEngine.php             # Generic workflow engine (supersedes ApprovalEngine)
│
├── Events/                                # Library events
│   ├── ModuleBooted.php                   # Fires after module boot sequence completes
│   ├── ModuleRegistered.php               # Fires when a business module is auto-discovered
│   ├── NavigationBuilding.php             # Fires during navigation construction
│   └── Notifications/
│       └── NotificationDispatched.php     # Fires after each notification dispatch
│
├── Listeners/                             # Library event subscribers
│   └── NotificationEventSubscriber.php    # Logs dispatched notifications to NotificationLog
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

**Package-level Database/Migrations/** (loaded via `loadMigrationsFrom()` in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php)):

```
Database/Migrations/
├── 2026_1_create_exports_table.php                # Export job tracking
├── 2026_1_create_imports_table.php                # Import job tracking
├── 2026_2_create_export_chunks_table.php          # Export chunk file tracking
├── 2026_2_create_import_chunks_table.php          # Import chunk tracking
├── 2026_08_08_000001_create_workflow_tables.php   # Workflow, WorkflowStep, WorkflowAction
├── 2026_08_08_000002_create_documents_table.php   # Polymorphic documents table
├── 2026_08_08_000003_create_notification_tables.php # Notifications, templates, preferences, logs
├── add_create_jobs_table.php                      # Laravel jobs table
├── create_saved_filters_table.php                 # User-saved filter presets
├── create_saved_reports_table.php                 # User-saved reports
├── create_system_settings_table.php               # Polymorphic system settings
└── 2026_08_09_000001_create_report_schedules_table.php  # Report schedules table
```

**Package-level Core Seeders** (in [`src/Core/`](../../src/Core/)):

```
src/Core/
├── Admin/Database/Seeders/
│   ├── RoleSeeder.php                             # Default roles and permissions
│   └── SuperAdminSeeder.php                       # Super admin user creation
├── Common/Database/Seeders/
│   └── NotificationTemplateSeeder.php             # 5 default notification templates
└── System/Database/Seeders/
    └── SystemSettingsSeeder.php                   # Default system settings
```

### 2.1a Core Modules Directory Map (`src/Core/`)

The three Core modules — `Admin`, `System`, and `Organization` — follow the same directory structure as business modules but live inside the package. Each has `Config/`, `Data/`, `Database/` (migrations + seeders), `Models/`, `Resources/views/`, and `Routes/web.php`. Core module views are registered under the shared `qf-core` namespace (see [`04-routing-and-views.md`](./04-routing-and-views.md)).

**Organization module** ([`src/Core/Organization/`](../../src/Core/Organization/)) — extracted in Phase 4.1:

```
src/Core/Organization/
├── Config/
│   ├── navigation.php                     # Organization navigation items
│   ├── sidebar_menu.php                   # Sidebar section config
│   ├── top_bar_menu.php                   # Top bar menu config
│   ├── bottom_bar_menu.php                # Bottom bar menu config
│   └── settings.php                       # Module settings
├── Data/
│   ├── company.php                        # Entity config (table + form + detail)
│   ├── branch.php
│   ├── department.php
│   ├── division.php
│   ├── business_unit.php
│   ├── location.php
│   ├── team.php
│   ├── dashboard.php                      # Organization dashboard config
│   └── dashboards/
│       ├── dashboard_companies_overview.php
│       ├── dashboard_locations_overview.php
│       └── dashboard_structure_overview.php
├── Database/
│   ├── Migrations/
│   │   ├── 2026_08_09_000003_create_companies_table.php
│   │   ├── 2026_08_09_000004_create_branches_table.php
│   │   ├── 2026_08_09_000005_create_departments_table.php
│   │   ├── 2026_08_09_000006_create_divisions_table.php
│   │   ├── 2026_08_09_000007_create_business_units_table.php
│   │   ├── 2026_08_09_000008_create_locations_table.php
│   │   └── 2026_08_09_000009_create_teams_table.php
│   └── Seeders/
│       └── OrganizationSeeder.php         # Seed default organization structure
├── Models/
│   ├── Company.php
│   ├── Branch.php
│   ├── Department.php
│   ├── Division.php
│   ├── BusinessUnit.php
│   ├── Location.php
│   └── Team.php
├── Resources/
│   └── views/organization/
│       ├── dashboard.blade.php
│       ├── companies.blade.php
│       ├── branches.blade.php
│       ├── departments.blade.php
│       ├── divisions.blade.php
│       ├── business-units.blade.php
│       ├── locations.blade.php
│       ├── teams.blade.php
│       ├── dashboard-companies-overview.blade.php
│       ├── dashboard-locations-overview.blade.php
│       └── dashboard-structure-overview.blade.php
└── Routes/
    └── web.php                            # /organization/* routes → qf-core::organization.* views
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

## Phase 4.1–4.5 Corrections & Notes

- **Phase 4.1 (Organization Extraction)**: Organization module extracted to [`src/Core/Organization/`](../../src/Core/Organization/) with routes, views, navigation config, and seeders (see §2.1a above).
- **Phase 4.2 (Module Registry)**: `user_facing` and `depends_on` flags are set on each module entry in `ui-library.modules` during discovery ([`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php)). `user_facing` controls whether a module appears in the application switcher; `depends_on` declares module dependencies (validated at boot).
- **Phase 4.4 (Application Switcher)**: The `ModuleSwitcher` Livewire component was **deleted** and replaced with an inline Bootstrap 5 dropdown in [`TopNav`](../../src/Http/Livewire/Layouts/Navs/TopNav.php). It is intentionally **absent** from the `src/` directory map above.
- **Phase 4.5 (Workspace Navigation)**: [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php) contract, [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php), and [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php) are documented in the `Contracts/Navigation/` and `Services/Navigation/` sections above.

---

**Related files**: [`00-index.md`](./00-index.md) · [`01-core-concepts.md`](./01-core-concepts.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`04-routing-and-views.md`](./04-routing-and-views.md)
