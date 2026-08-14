# QuickerFaster UI Library — Architecture Decision Records (ADR)

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`01-core-concepts.md`](./01-core-concepts.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`10-settings-and-config.md`](./10-settings-and-config.md)

---

## 3. Architecture Decision Records (ADR)

### ADR-001: Catch-All Routing Instead of Explicit Route Definitions

**Decision**: Use a centralized route pattern `/{module}/{view}/{id?}` in the System module for module view discovery, loaded LAST so explicit module routes take precedence.

**Implementation** ([`ModuleServiceProvider.php`](../../src/Providers/ModuleServiceProvider.php)):
- Library routes load first ([`src/Routes/web.php`](../../src/Routes/web.php))
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

**Implementation** ([`ConfigResolver.php`](../../src/Services/Config/ConfigResolver.php)):
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

**Decision**: Field rendering is resolved through [`FieldFactory`](../../src/Factories/FieldTypes/FieldFactory.php) backed by the [`FieldType`](../../src/Contracts/FieldTypes/FieldType.php) contract.

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

**Decision**: Provide one layout component ([`NavigationLayout`](../../src/Components/NavigationLayout.php)) that composes [`TopNav`](../../src/Http/Livewire/Layouts/Navs/TopNav.php), [`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php), and [`BottomBar`](../../src/Http/Livewire/Layouts/Navs/BottomBar.php).

**Why**:
- The layout is a shared cross-cutting concern
- Provides consistent navigation architecture across all modules
- Reduces duplication and creates a predictable navigation contract

**Trade-offs**:
- The component becomes a central integration point and should remain simple
- Must be flexible enough for varying modules and contexts

### ADR-006: Three-Tier Settings Resolution (User → Company → System)

**Decision**: Settings cascade through three resolvers with priority: user preferences → company settings → system defaults.

**Implementation** ([`UILibraryServiceProvider.php`](../../src/Providers/UILibraryServiceProvider.php)):
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

**Related files**: [`00-index.md`](./00-index.md) · [`04-routing-and-views.md`](./04-routing-and-views.md) · [`05-data-configs.md`](./05-data-configs.md) · [`10-settings-and-config.md`](./10-settings-and-config.md)
