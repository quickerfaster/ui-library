# QuickerFaster UI Library — AI Agent Quick-Start Protocol

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](../README.md) · [`01-core-concepts.md`](./01-core-concepts.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`05-data-configs.md`](./05-data-configs.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)

---

## Overview

**This is the FIRST file an AI agent should read when given a task.** It answers three questions:

1. **"Given task X, which files do I touch?"** → Decision tree (§9.1)
2. **"Where is the canonical reference for a common task?"** → Lookup table (§9.2)
3. **"Something is broken — where do I look?"** → Troubleshooting guide (§9.3)

For the philosophy and design intent, read [`01-core-concepts.md`](./01-core-concepts.md). For the cross-cutting flow of views/routes/configs, read [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md).

---

## 9.1 Decision Tree: "Given Task X, Which Files Do I Touch?"

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
    └─ Check load order: src/Providers/ModuleServiceProvider.php → discoverBusinessModules()
    └─ Check: src/Routes/web.php (library routes)
    └─ Check: app/Modules/{Module}/Routes/web.php (module routes)
    └─ Check: src/Core/System/Routes/web.php (catch-all, loaded last)
```

> The decision tree above reflects the **current** implementation: the catch-all lives in [`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php), not `app/Modules/System/Routes/web.php` as in the original blueprint. See [`04-routing-and-views.md`](./04-routing-and-views.md) and [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md).

---

## 9.2 Common Task Lookup Table

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

**Topic-file cross-reference for the above**:

| Task family | Read this file |
|-------------|----------------|
| Fields / configs / datatables | [`05-data-configs.md`](./05-data-configs.md) |
| Routes / views / catch-all | [`04-routing-and-views.md`](./04-routing-and-views.md), [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) |
| Navigation | [`06-navigation-system.md`](./06-navigation-system.md) |
| Components / Livewire / widgets | [`07-component-catalog.md`](./07-component-catalog.md) |
| Contracts / interfaces | [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) |
| Engines / services | [`09-engines-and-services.md`](./09-engines-and-services.md) |
| Settings / config | [`10-settings-and-config.md`](./10-settings-and-config.md) |
| Extension recipes | [`11-extension-guide.md`](./11-extension-guide.md) |

---

## 9.3 Troubleshooting Guide

### A. Config Not Found

**Symptoms**: Component renders with defaults only, unexpected empty state, or `InvalidArgumentException: Configuration not found for key: X`.

**Checks**:
1. Verify the config file exists at the expected path: `app/Modules/{Module}/Data/{file}.php` (business) or `src/Core/{Module}/Data/{file}.php` (core) — [`ModelConfigRepository`](../../src/Services/Config/ModelConfigRepository.php) now scans both
2. Verify the dot-notation key matches: `{lowercase_module}.{filename}` (e.g., `hr.employee`)
3. Clear the config cache: `app(ModelConfigRepository::class)->forget('hr.employee')`
4. Check file permissions on the config file

> See [`05-data-configs.md`](./05-data-configs.md) and [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) §3 for the full resolution flow.

### B. Component Not Rendering

**Symptoms**: Blank output, missing section, or Livewire component not found.

**Checks**:
1. Verify the Livewire component is registered in [`UILibraryServiceProvider::registerLivewireComponents()`](../../src/Providers/UILibraryServiceProvider.php:211)
2. Verify the Blade view exists at the expected path
3. Verify the component alias uses the `qf.` prefix
4. For conditional components, verify the file exists at `app_path($config['path'])`

### C. Route Conflict

**Symptoms**: Module view route does not resolve or hits the wrong route.

**Checks**:
1. Verify explicit module routes are in `app/Modules/{Module}/Routes/web.php`
2. Verify the System module catch-all is loaded LAST (check [`ModuleServiceProvider::discoverBusinessModules()`](../../src/Providers/ModuleServiceProvider.php:103))
3. Verify the route pattern is not shadowed by a more specific route
4. Check `php artisan route:list` to see all registered routes

### D. Validation Not Working

**Symptoms**: Form submits without validation or wrong rules applied.

**Checks**:
1. Verify `fieldDefinitions.{field}.validation` string is correct
2. Verify the field is not in `hiddenFields.onNewForm` or `hiddenFields.onEditForm`
3. Check [`DataTableFormValidationService::shouldValidateField()`](../../src/Services/Validation/DataTableFormValidationService.php:96)
4. For file fields, verify `field_type === 'file'` is set
5. Check the FieldType's `getValidationRules()` method

### E. Settings Not Resolving

**Symptoms**: `@setting('key')` returns null or wrong value.

**Checks**:
1. Verify the `SystemSetting` model has records for the key at the appropriate level
2. Clear the settings cache: `app(SettingsManager::class)->flush('key')`
3. Verify the resolver chain in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php) (settings resolvers registered in `register()`)
4. Check that the user is authenticated (user resolver requires `auth()->user()`)

> See [`10-settings-and-config.md`](./10-settings-and-config.md) for the 3-tier cascade.

### F. Module Not Auto-Discovered

**Symptoms**: Module views not found, routes not loaded, migrations not run.

**Checks**:
1. Verify the module directory exists at `app/Modules/{ModuleName}/`
2. Verify the directory name uses PascalCase
3. Verify `Resources/views/` exists for view namespace registration
4. Check that `ModuleServiceProvider` is registered in `config/app.php`
5. Clear the event listener cache: `Cache::forget('module_event_listeners_{moduleName}')`

> See [`03-module-pattern.md`](./03-module-pattern.md) for the full registration protocol.

---

**Related files**: [`00-index.md`](../README.md) · [`01-core-concepts.md`](./01-core-concepts.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`05-data-configs.md`](./05-data-configs.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)
