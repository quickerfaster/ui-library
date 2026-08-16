# QuickerFaster UI Library — Gaps & Recommendations

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-16

**Related files**: [`00-index.md`](./00-index.md) · [`13-adr.md`](./13-adr.md) · [`16-phase-history.md`](./16-phase-history.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md)

---

## Overview

This file documents the **10 known gaps** in the library as of the blueprint snapshot, each with a concrete recommendation. These are weaknesses to be aware of when implementing or extending the library — they are not blockers, but they represent areas where the architecture is incomplete or under-specified.

The "Known Gaps — Remaining `App\Modules\*` References" section (the *other* §10.5 in the blueprint) is **not** documented here; it lives in [`16-phase-history.md`](./16-phase-history.md) because it is a phase-completion status concern, not a forward-looking recommendation.

| # | Gap | Priority Context |
|---|-----|------------------|
| 10.1 | Missing Error Handling Strategy | Exception layer |
| 10.2 | Missing Testing Architecture | Quality assurance |
| 10.3 | Asset Compilation and Publishing Strategy | Build pipeline |
| 10.4 | State Management for Wizards and Multi-Step Flows | Component lifecycle |
| 10.5 | API vs Web Context Handling | Interface separation |
| 10.6 | Accessibility and Internationalization Standards | UX compliance |
| 10.7 | Security Hardening for Catch-All Routes | **Highest priority** — ✅ Resolved (2026-08-16) |
| 10.8 | Caching Strategy for Module Discovery | Performance |
| 10.9 | Missing Documentation for Bank File Generators | Docs |
| 10.10 | Missing Module Scaffold Command | Developer experience |

---

## 10.1 Missing Error Handling Strategy

**Gap**: No explicit strategy for missing config, missing views, invalid module names, or route authorization failures. The [`ConfigResolver`](../../src/Services/Config/ConfigResolver.php:25) throws `InvalidArgumentException` for missing configs, but there is no consistent exception layer.

**Recommendation**:
- Create a dedicated exception hierarchy under [`src/Exceptions/`](../../src/Exceptions/):
  - `ConfigNotFoundException` — extends `InvalidArgumentException`
  - `ModuleNotFoundException` — for invalid module names
  - `ViewResolutionException` — for missing views in catch-all route
  - `RecordNotAccessibleException` — already exists, expand usage
- Ensure all components fail predictably with a friendly fallback or clear error message
- Add a `render()` method to exceptions for consistent JSON/HTML error responses

> See also: [`ModelConfigRepository::loadFromFile()`](../../src/Services/Config/ModelConfigRepository.php:116) which currently throws `InvalidArgumentException` with the full searched-path list.

---

## 10.2 Missing Testing Architecture

**Gap**: No test strategy is described or implemented for components, config resolution, routes, and form validation.

**Recommendation**:
- Add PHPUnit tests under a `tests/` directory in the library:
  - **Unit tests**: [`ConfigResolver`](../../src/Services/Config/ConfigResolver.php), [`FieldFactory`](../../src/Factories/FieldTypes/FieldFactory.php), [`WidgetProcessor`](../../src/Services/Widgets/WidgetProcessor.php), [`SettingsManager`](../../src/Services/Settings/SettingsManager.php), [`DataTableFormValidationService`](../../src/Services/Validation/DataTableFormValidationService.php)
  - **Feature tests**: Livewire component tests for [`DataTable`](../../src/Http/Livewire/DataTables/DataTable.php), [`DataTableForm`](../../src/Http/Livewire/DataTables/DataTableForm.php), [`Wizard`](../../src/Http/Livewire/Wizards/Wizard.php)
  - **Integration tests**: Module route discovery, module view rendering, event listener auto-registration
- Add CI configuration (GitHub Actions) to run tests on PR

---

## 10.3 Asset Compilation and Publishing Strategy

**Gap**: The package publishes public assets via `qf-public-assets` tag, but no clear asset build pipeline is described. Bootstrap 5 theme (Soft UI Dashboard) and custom CSS/JS are manually managed.

**Recommendation**:
- Document the asset build flow:
  1. Bootstrap 5 (Soft UI Dashboard) at [`public/bootstrap/`](../../public/bootstrap/)
  2. Custom CSS at [`public/assets/css/quicker-faster.css`](../../public/assets/css/quicker-faster.css)
  3. Custom JS at [`public/assets/js/quicker-faster.js`](../../public/assets/js/quicker-faster.js)
- Consider adding a Laravel Mix or Vite build pipeline for the library's assets
- Version assets explicitly and document the publish command: `php artisan vendor:publish --tag=qf-public-assets`

---

## 10.4 State Management for Wizards and Multi-Step Flows

**Gap**: Wizards ([`Wizard`](../../src/Http/Livewire/Wizards/Wizard.php), [`SetupWizard`](../../src/Http/Livewire/Wizards/SetupWizard.php), [`WizardForm`](../../src/Http/Livewire/Wizards/WizardForm.php)) are mentioned but their lifecycle and persistence strategy is not formally defined.

**Recommendation**:
- Introduce a `WizardState` service or trait that:
  - Persists wizard state in the session (for short-lived wizards)
  - Persists wizard state in the database (for long-running wizards like payroll)
  - Supports step validation, back/forward navigation, and state serialization
- Document the wizard config schema in [`WizardConfigResolver`](../../src/Services/Config/Wizards/WizardConfigResolver.php)

---

## 10.5 API vs Web Context Handling

**Gap**: The architecture mixes web views, interactive Livewire components, and business modules without a clear separation for API contexts. Module API routes are loaded but there is no shared API response convention.

**Recommendation**:
- Define whether the same module config can drive API responses (e.g., API resource classes generated from field definitions)
- Introduce a separate API contract layer where needed:
  - `ApiResourceContract` — for transforming models to API responses
  - `ApiValidationContract` — for API-specific validation rules
- Document the API route convention: `app/Modules/{Module}/Routes/api.php` auto-loaded with `api` middleware and prefix

---

## 10.6 Accessibility and Internationalization Standards

**Gap**: No accessibility (a11y) or i18n standards are described. Translations exist for `en` and `es` in [`src/Resources/lang/`](../../src/Resources/lang/) but coverage is minimal.

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

---

## 10.7 Security Hardening for Catch-All Routes — ✅ RESOLVED (2026-08-16)

**Gap**: The centralized route pattern `/{module}/{view}/{id?}` in the System module can be abused if authorization and validation are weak.

**Resolution**: All five hardening recommendations have been implemented in [`src/Core/System/Routes/web.php`](../../src/Core/System/Routes/web.php) and [`src/Config/ui-library.php`](../../src/Config/ui-library.php):

1. **Module allow-list** — `config('ui-library.catch_all.allowed_modules')` (default `['admin', 'system', 'organization', 'common']`); business modules are appended automatically by [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php). Non-allow-listed modules receive `abort(404)`.
2. **Directory-traversal sanitization** — explicit null-byte, slash, backslash, `..`, and leading-dot checks (`abort(400)`) as defense in depth beyond the regex constraint.
3. **Per-view authorization** — `authorization_callback` callable (takes precedence) or `gate` Gate ability; `require_auth` re-checked in the handler.
4. **Rate limiting** — `qf-catch-all` named limiter registered in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php), keyed by user id or IP, applied via `throttle:qf-catch-all` middleware when `rate_limiting.enabled` is `true`.
5. **Consuming-app configurability** — all settings live under `ui-library.catch_all` and can be published/overridden.

> **Related**: [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) §2.1 documents the updated catch-all handler. [`04-routing-and-views.md`](./04-routing-and-views.md) §4.3 and §4.5 reflect the implementation.

---

## 10.8 Caching Strategy for Module Discovery

**Gap**: The [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php) scans modules dynamically on every request in non-production environments. Event listener discovery already has production caching, but view namespace and route registration do not.

**Recommendation**:
- Extend production caching to:
  - **View namespaces**: Cache the module→view-path map
  - **Route files**: Cache the list of module route files
  - **Migration paths**: Cache the list of module migration directories
- Use a single cache key pattern: `qf_module_registry` storing all discovered module metadata
- Invalidate the cache when modules are added/removed (via `php artisan qf:clear-cache` command)
- The [`ModelConfigRepository`](../../src/Services/Config/ModelConfigRepository.php) already uses `Cache::rememberForever()` — ensure this is documented

---

## 10.9 Missing Documentation for Bank File Generators

**Gap**: The [`src/Services/BankFiles/`](../../src/Services/BankFiles/) directory contains generators for BACS, NACHA, NIBSS, and SEPA formats, but there is no contract/interface defined and no documentation.

**Recommendation**:
- Extract a `BankFileGenerator` interface from the existing implementations
- Document the config schema for bank file generation
- Add a `BankFileGeneratorFactory` registration pattern for custom formats
- Document supported formats and their configuration options

---

## 10.10 Missing Module Scaffold Command

**Gap**: There is no Artisan command to scaffold a new business module with the correct directory structure.

**Recommendation**:
- Create a `php artisan qf:make-module {name}` command that:
  1. Creates the directory structure under `app/Modules/{Name}/`
  2. Generates a starter `Data/{entity}.php` config
  3. Generates a starter model
  4. Generates starter views (index, dashboard)
  5. Optionally generates a migration
- Publish stubs via `qf-modules` tag (already partially implemented in [`ModuleServiceProvider::registerPublishables()`](../../src/Providers/ModuleServiceProvider.php:84))

> **Related**: The extension guide [`11-extension-guide.md`](./11-extension-guide.md) documents the manual module-creation recipe that this command would automate.

---

---

## Resolved Items (not originally in the 10 gaps)

### Navigation Configs (Phase 6.1–6.3) — ✅ Complete (2026-08-16)

All three Core navigation configs have been expanded to their full context-group + context-item structure:

- [`System`](../../src/Core/System/Config/navigation.php) — 7 groups, 42 items
- [`Admin`](../../src/Core/Admin/Config/navigation.php) — 7 groups, 33 items
- [`Organization`](../../src/Core/Organization/Config/navigation.php) — 7 groups, 28 items

### Legacy Dead Code — ✅ Removed (2026-08-16)

Three legacy Artisan commands deleted: `QuickerFasterInstallUI`, `CleanExports`, `CleanImportErrors` — all superseded by [`InstallCommand`](../../src/Console/Commands/InstallCommand.php).

---

**Related files**: [`00-index.md`](./00-index.md) · [`13-adr.md`](./13-adr.md) · [`16-phase-history.md`](./16-phase-history.md) · [`17-view-config-routing-interplay.md`](./17-view-config-routing-interplay.md) · [`11-extension-guide.md`](./11-extension-guide.md)
