# QuickerFaster UI Library — Component Catalog & Contract Definitions

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-15

**Related files**: [`00-index.md`](./00-index.md) · [`05-data-configs.md`](./05-data-configs.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`11-extension-guide.md`](./11-extension-guide.md)

---

## 4. Component Catalog & Contract Definitions

This file is the complete catalog of Livewire components, Blade components, services, models, traits, factories, processors, events, and listeners. The **contract interfaces** (formerly §4.4) live in [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md).

### 4.1 Livewire Components (Registered with `qf.` Prefix)

All components are registered in [`UILibraryServiceProvider::registerLivewireComponents()`](../../src/Providers/UILibraryServiceProvider.php:204).

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
| AccessControlManager | `qf.access-control-manager` | `AccessControlManager` | Consolidated access control UI with word-based model search, state-aware bulk toggle switches, and reactive permission state |
| ModuleSelector | `qf.module-selector` | `ModuleSelector` | Module selection for permission scoping |
| RoleAssignmentManager | `qf.role-assignment-manager` | `RoleAssignmentManager` | Role assignment interface |
| PermissionManager | `qf.permission-manager` | `PermissionManager` | Permission CRUD interface |

#### Buttons

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| ToggleButton | `qf.toggle-button` | `ToggleButton` | Single toggle button; listens for the `refresh-toggle-state` event |
| ToggleButtonGroup | `qf.toggle-button-group` | `ToggleButtonGroup` | Group of toggle buttons; listens for `refresh-toggle-state`, recomputes the description badge, and renders an expand/collapse chevron |

> **Access-control UX notes**: [`AccessControlManager::bulkToggle()`](../../src/Http/Livewire/AccessControls/AccessControlManager.php) dispatches the `refresh-toggle-state` event with the fresh permission names; [`ToggleButton::refreshState()`](../../src/Http/Livewire/Buttons/ToggleButton.php) and [`ToggleButtonGroup::refreshState()`](../../src/Http/Livewire/Buttons/ToggleButtonGroup.php) react to keep cards in sync. Bulk actions render as 7 Bootstrap toggle switches driven by [`AccessControlManager::getBulkToggleStatesProperty()`](../../src/Http/Livewire/AccessControls/AccessControlManager.php) (returns `'on'` / `'off'` / `'mixed'`). Permission cards expose a `fas fa-chevron-down collapse-chevron` indicator rotated by the `.collapse-chevron-trigger[aria-expanded="true"] .collapse-chevron` rule in [`quicker-faster.css`](../../public/assets/css/quicker-faster.css).

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

#### Conditional (HR Module — Registered Only If Files Exist)

These are registered conditionally in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php:299) by checking `app_path()` for file existence:

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
| ConfigResolver | [`src/Services/Config/ConfigResolver.php`](../../src/Services/Config/ConfigResolver.php:6) | Typed accessor for module config arrays |
| ModelConfigRepository | [`src/Services/Config/ModelConfigRepository.php`](../../src/Services/Config/ModelConfigRepository.php:8) | Cached config file loader with dot-notation keys |
| SettingsManager | [`src/Services/Settings/SettingsManager.php`](../../src/Services/Settings/SettingsManager.php:7) | 3-tier cascading settings resolver |
| WidgetProcessor | [`src/Services/Widgets/WidgetProcessor.php`](../../src/Services/Widgets/WidgetProcessor.php:25) | Maps widget type strings to processor classes |
| DataTableFormValidationService | [`src/Services/Validation/DataTableFormValidationService.php`](../../src/Services/Validation/DataTableFormValidationService.php:7) | Dynamic validation rule generation from field definitions |
| ImportProcessor | [`src/Services/Imports/ImportProcessor.php`](../../src/Services/Imports/ImportProcessor.php) | Import file processing (singleton) |
| DataTableExport | [`src/Services/Exports/DataTableExport.php`](../../src/Services/Exports/DataTableExport.php) | Excel/CSV export from DataTable config |
| TemplateExport | [`src/Services/Exports/TemplateExport.php`](../../src/Services/Exports/TemplateExport.php) | Import template generation with LookupSheet, OptionsReferenceSheet, TemplateDataSheet |
| **DocumentEngine** | [`src/Services/Documents/DocumentEngine.php`](../../src/Services/Documents/DocumentEngine.php:13) | **NEW** — Generic document engine. API: `upload()`, `generatePdf()`, `generateExcel()`, `getDocuments()`, `delete()`. Polymorphic, config-driven. |
| **WorkflowEngine** | [`src/Services/Workflow/WorkflowEngine.php`](../../src/Services/Workflow/WorkflowEngine.php:12) | **NEW** — Generic workflow engine. API: `start()`, `approve()`, `reject()`, `recall()`, `getDefinition()`, `hasActiveWorkflow()`. Supersedes ApprovalEngine. |
| ApprovalEngine | [`src/Services/Approvals/ApprovalEngine.php`](../../src/Services/Approvals/ApprovalEngine.php:11) | ⚠️ **DEPRECATED** — Legacy approval workflow engine. Maintained for backward compatibility. Prefer [`WorkflowEngine`](../../src/Services/Workflow/WorkflowEngine.php) for new workflow-enabled features. |
| ApprovalModelResolver | [`src/Services/Approvals/ApprovalModelResolver.php`](../../src/Services/Approvals/ApprovalModelResolver.php:7) | Config-driven approval model resolution (implements [`ApprovalModelResolver`](../../src/Contracts/Approvals/ApprovalModelResolver.php) contract) |
| NullCompanyProvider | [`src/Services/Navigation/NullCompanyProvider.php`](../../src/Services/Navigation/NullCompanyProvider.php:9) | Default no-op [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php) implementation — returns empty collection/null |
| **NavigationManager** | [`src/Services/Navigation/NavigationManager.php`](../../src/Services/Navigation/NavigationManager.php) | Config-driven navigation manager: `getSections()`, 5-tier priority chain, context_groups |
| **WorkspaceFilter** | [`src/Services/Navigation/WorkspaceFilter.php`](../../src/Services/Navigation/WorkspaceFilter.php) | **NEW** — Workspace-scoped navigation filtering: `filterContextGroups()` (feature gates), `filterContextItems()` (role/department constraints) |
| **NullWorkspaceResolver** | [`src/Services/Navigation/NullWorkspaceResolver.php`](../../src/Services/Navigation/NullWorkspaceResolver.php) | **NEW** — Default no-op [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php) — returns empty context |
| SearchEngine | [`src/Services/Search/SearchEngine.php`](../../src/Services/Search/SearchEngine.php) | Global search across modules |
| FilterService | [`src/Services/Filters/FilterService.php`](../../src/Services/Filters/FilterService.php) | Filter application logic |
| ValueGenerator | [`src/Services/ValueGenerator.php`](../../src/Services/ValueGenerator.php) | Auto-generates field values from patterns |
| ApplicationInfo | [`src/Services/System/ApplicationInfo.php`](../../src/Services/System/ApplicationInfo.php) | Application metadata |
| AccessControlPermissionService | [`src/Services/AccessControl/AccessControlPermissionService.php`](../../src/Services/AccessControl/AccessControlPermissionService.php) | Permission CRUD operations |
| **AuthorizationService** | [`src/Services/AccessControl/AuthorizationService.php`](../../src/Services/AccessControl/AuthorizationService.php:8) | Central authorization gate. `isBypassAllowed()` grants super admin / admin / `company_admin` bypass via Spatie roles, with an email fallback (`SUPER_ADMIN_EMAIL`) to protect against seed failures. `canAccessView()`, `authorizeView()`, `authorizeCreate()`, `authorizeUpdate()` (with self-edit bypass). Exposes `ADMIN_ROLES`, `ADMIN_ROLES_ARRAY`, `COMPANY_ADMIN_ROLES_ARRAY` constants. |
| **DefaultAuthorizationProvider** | [`src/Services/DataTables/DefaultAuthorizationProvider.php`](../../src/Services/DataTables/DefaultAuthorizationProvider.php:19) | Default [`DataTableAuthorizationProvider`](../../src/Contracts/DataTables/DataTableAuthorizationProvider.php) implementation. All 15 permission methods delegate to [`AuthorizationService::isBypassAllowed()`](../../src/Services/AccessControl/AuthorizationService.php) first, then fall back to Spatie `can()` checks (`view_*`, `create_*`, `edit_*`, `delete_*`, `print_*`, `export_*`, `import_*`, `restore_*`, `force_delete_*`, plus bulk variants). |
| **ActivityLogModelResolver** | [`src/Services/ActivityLogs/ActivityLogModelResolver.php`](../../src/Services/ActivityLogs/ActivityLogModelResolver.php:7) | Default [`ActivityLogModelResolver`](../../src/Contracts/ActivityLogs/ActivityLogModelResolver.php) contract implementation — resolves the ActivityLog model FQCN from `config('ui-library.activity_logs.model')`, returning `null` when unconfigured. |
| BankFileGeneratorFactory | [`src/Services/BankFiles/BankFileGeneratorFactory.php`](../../src/Services/BankFiles/BankFileGeneratorFactory.php) | Factory for BACS/NACHA/NIBSS/SEPA generators |
| **NotificationService** | [`src/Services/Notifications/NotificationService.php`](../../src/Services/Notifications/NotificationService.php) | **NEW** — Polymorphic notification dispatch engine. API: `dispatch()`, `getUnread()`, `registerChannel()`, `resolveTemplate()`. Supports `{placeholder}` replacement in templates. |
| **DatabaseChannel** | [`src/Services/Notifications/Channels/DatabaseChannel.php`](../../src/Services/Notifications/Channels/DatabaseChannel.php) | **NEW** — In-app notification channel (no-op; notifications already persisted by NotificationService) |
| **MailChannel** | [`src/Services/Notifications/Channels/MailChannel.php`](../../src/Services/Notifications/Channels/MailChannel.php) | **NEW** — Email notification channel via `Mail::raw()` |
| **ReportEngine** | [`src/Services/Reports/ReportEngine.php`](../../src/Services/Reports/ReportEngine.php) | **NEW** — Scheduled report engine. API: `process(ReportSchedule)`. Integrates [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) for PDF/Excel generation and [`NotificationService`](../../src/Services/Notifications/NotificationService.php) for recipient delivery. |

> **Deep-dive note**: Services marked **NEW** (DocumentEngine, WorkflowEngine, NotificationService, ReportEngine) are cataloged here; their full deep-dives (architecture, config, usage examples, schemas) live in [`09-engines-and-services.md`](./09-engines-and-services.md).

### 4.3a Models

| Model | Location | Purpose |
|-------|----------|---------|
| Export | [`src/Models/Export.php`](../../src/Models/Export.php) | Export job tracking record |
| ExportChunk | [`src/Models/ExportChunk.php`](../../src/Models/ExportChunk.php) | Export chunk file record |
| Import | [`src/Models/Import.php`](../../src/Models/Import.php) | Import job tracking record |
| ImportChunk | [`src/Models/ImportChunk.php`](../../src/Models/ImportChunk.php) | Import chunk record |
| SavedFilter | [`src/Models/SavedFilter.php`](../../src/Models/SavedFilter.php) | User-saved filter preset |
| SavedReport | [`src/Models/SavedReport.php`](../../src/Models/SavedReport.php) | User-saved report |
| System | [`src/Models/System.php`](../../src/Models/System.php) | System singleton model (id=1) |
| SystemSetting | [`src/Models/SystemSetting.php`](../../src/Models/SystemSetting.php) | Polymorphic settings (user/company/system) |
| Document | [`src/Models/Document.php`](../../src/Models/Document.php) | Polymorphic document with soft deletes |
| Workflow | [`src/Models/Workflow.php`](../../src/Models/Workflow.php) | Workflow instance (polymorphic, multi-step) |
| WorkflowStep | [`src/Models/WorkflowStep.php`](../../src/Models/WorkflowStep.php) | Workflow step (role-based, sequential) |
| WorkflowAction | [`src/Models/WorkflowAction.php`](../../src/Models/WorkflowAction.php) | Workflow action audit trail |
| **Notification** | [`src/Models/Notification.php`](../../src/Models/Notification.php) | **NEW** — Polymorphic notification (notifiable_type/id) |
| **NotificationTemplate** | [`src/Models/NotificationTemplate.php`](../../src/Models/NotificationTemplate.php) | **NEW** — Template model (type, channel, locale) |
| **NotificationPreference** | [`src/Models/NotificationPreference.php`](../../src/Models/NotificationPreference.php) | **NEW** — Per-user channel preference toggle |
| **NotificationLog** | [`src/Models/NotificationLog.php`](../../src/Models/NotificationLog.php) | **NEW** — Notification dispatch audit trail |
| **ReportSchedule** | [`src/Models/ReportSchedule.php`](../../src/Models/ReportSchedule.php) | **NEW** — Report schedule model (frequency, time, timezone, recipients, status, next_run_at) |

> **Deep-dive note**: Models marked **NEW** (Document, Workflow/WorkflowStep/WorkflowAction, Notification family, ReportSchedule) are cataloged here; their schema details and engine relationships live in [`09-engines-and-services.md`](./09-engines-and-services.md).

### 4.5 FieldFactory Mapping

**Location**: [`src/Factories/FieldTypes/FieldFactory.php`](../../src/Factories/FieldTypes/FieldFactory.php:23)

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

**Location**: [`src/Services/Widgets/WidgetProcessor.php`](../../src/Services/Widgets/WidgetProcessor.php:27)

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
| `HasSettings` | [`src/Traits/HasSettings.php`](../../src/Traits/HasSettings.php:8) | Polymorphic settings via `SystemSetting` model with caching |
| `HasNavItems` | [`src/Traits/HasNavItems.php`](../../src/Traits/HasNavItems.php:5) | Default navigation items (dashboard, profile, account, help, settings) |
| `HasColumnPreferences` | [`src/Traits/DataTables/HasColumnPreferences.php`](../../src/Traits/DataTables/HasColumnPreferences.php) | Session-based column visibility persistence |
| `HasBladeRendering` | [`src/Traits/FieldTypes/HasBladeRendering.php`](../../src/Traits/FieldTypes/HasBladeRendering.php) | Blade rendering with inline/modal/drawer fallback |
| `HasAutoGenerate` | [`src/Traits/FieldTypes/HasAutoGenerate.php`](../../src/Traits/FieldTypes/HasAutoGenerate.php) | Auto-generate button rendering for fields |
| `HasApproval` | [`src/Traits/Approvals/HasApproval.php`](../../src/Traits/Approvals/HasApproval.php) | Approval workflow trait for models |
| `HandlesToggleState` | [`src/Traits/Buttons/HandlesToggleState.php`](../../src/Traits/Buttons/HandlesToggleState.php) | Toggle button state management |
| `NavigationFilter` | [`src/Traits/NavigationFilter.php`](../../src/Traits/NavigationFilter.php) | Permission-based nav item filtering |
| `AppliesFilters` | [`src/Traits/AppliesFilters.php`](../../src/Traits/AppliesFilters.php) and [`src/Traits/Filters/AppliesFilters.php`](../../src/Traits/Filters/AppliesFilters.php) | Filter application logic |
| `HasCurrencySymbol` | [`src/Traits/HasCurrencySymbol.php`](../../src/Traits/HasCurrencySymbol.php) | Currency symbol resolution |
| `HasCacheInvalidator` | [`src/Traits/HasCacheInvalidator.php`](../../src/Traits/HasCacheInvalidator.php) | Cache invalidation helpers |
| `ResolvesExportValues` | [`src/Traits/ResolvesExportValues.php`](../../src/Traits/ResolvesExportValues.php) | Export value resolution |
| `HasHintField` | [`src/Traits/FieldTypes/HasHintField.php`](../../src/Traits/FieldTypes/HasHintField.php) | Hint/tooltip rendering for fields |
| `HandlesRelationshipGroupBy` | [`src/Traits/Widgets/HandlesRelationshipGroupBy.php`](../../src/Traits/Widgets/HandlesRelationshipGroupBy.php) | Relationship grouping for widgets |
| `ResolvesDateStrings` | [`src/Traits/Widgets/ResolvesDateStrings.php`](../../src/Traits/Widgets/ResolvesDateStrings.php) | Date string resolution for widgets |

### 4.8 Lifecycle Hooks & Extension Points

Business modules can tap into the following extension points:

1. **Event Listeners** — Auto-discovered from `app/Modules/{Module}/Listeners/`. The [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php:121) uses reflection on `handle()` method signatures to detect event types.

2. **Onboarding Conditions** — Implement [`OnboardingCondition`](../../src/Contracts/OnboardingCondition.php:5) and reference the class in `app_onboarding.php` config under `condition`.

3. **Navigation Config** — `app/Modules/{Module}/Config/navigation.php` is consumed by [`NavigationLayout`](../../src/Components/NavigationLayout.php) and filtered through [`NavigationFilter`](../../src/Traits/NavigationFilter.php).

4. **Settings Overrides** — The [`ConfigResolver::getSettingsOverrideFieldDefinition()`](../../src/Services/Config/ConfigResolver.php:56) method applies settings overrides for date formats, currency, and auto-generation patterns.

5. **Field Type Extension** — Add new field types by implementing [`FieldType`](../../src/Contracts/FieldTypes/FieldType.php:5) and registering in [`FieldFactory::$map`](../../src/Factories/FieldTypes/FieldFactory.php:25).

6. **Widget Extension** — Add new widget types by creating a processor class and registering in [`WidgetProcessor::$map`](../../src/Services/Widgets/WidgetProcessor.php:27).

### 4.9 Library Events

The library dispatches the following events that business modules can listen to:

| Event | Location | Fires When | Payload |
|-------|----------|------------|---------|
| `ModuleRegistered` | [`src/Events/ModuleRegistered.php`](../../src/Events/ModuleRegistered.php:7) | A business module is auto-discovered and registered by [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php) | `name` (string), `path` (string) |
| `ModuleBooted` | [`src/Events/ModuleBooted.php`](../../src/Events/ModuleBooted.php:7) | A module has completed its boot sequence | (no payload — use as signal) |
| `NavigationBuilding` | [`src/Events/NavigationBuilding.php`](../../src/Events/NavigationBuilding.php:7) | During navigation construction, before rendering | `modules` (array — modifiable by listeners) |
| **`NotificationDispatched`** | [`src/Events/Notifications/NotificationDispatched.php`](../../src/Events/Notifications/NotificationDispatched.php) | **NEW** — After each notification is dispatched | `notification` (Notification model) |

**Usage**: Business modules create listeners in `app/Modules/{Module}/Listeners/` with a `handle()` method type-hinted to the event class. Listeners are auto-discovered via reflection — no manual registration needed.

### 4.9a Library Listeners

| Listener | Location | Purpose |
|----------|----------|---------|
| **NotificationEventSubscriber** | [`src/Listeners/NotificationEventSubscriber.php`](../../src/Listeners/NotificationEventSubscriber.php) | **NEW** — Listens for [`NotificationDispatched`](../../src/Events/Notifications/NotificationDispatched.php) and logs each dispatch to [`NotificationLog`](../../src/Models/NotificationLog.php) |

---

**Related files**: [`00-index.md`](./00-index.md) · [`05-data-configs.md`](./05-data-configs.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`11-extension-guide.md`](./11-extension-guide.md)
