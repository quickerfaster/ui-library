# QuickerFaster UI Library — Navigation System

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-17

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`13-adr.md`](./13-adr.md) · [`phase-5-navigation-ux.md`](./phase-5-navigation-ux.md)

> **Consuming-app developers**: For the per-module `navigation.php` config schema, context groups, sidebar section configuration, and how-to recipes, see [../consuming-app/module-structure.md](../consuming-app/module-structure.md) §"Config/navigation.php".

---

## Overview

Navigation is a **cross-cutting concern** owned by the library. Per **ADR-005** (see [`13-adr.md`](./13-adr.md)), a single [`NavigationLayout`](../../src/Components/NavigationLayout.php) component composes three nav sub-components — [`TopNav`](../../src/Http/Livewire/Layouts/Navs/TopNav.php), [`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php), and [`BottomBar`](../../src/Http/Livewire/Layouts/Navs/BottomBar.php) — providing a consistent navigation architecture across all modules.

**ADR-005 rationale**: one shared layout shell avoids per-module duplication, creates a predictable navigation contract, and centralizes the integration point. The trade-off is that `NavigationLayout` must stay simple while remaining flexible for varying modules and contexts.

---

## Navigation Components

### Livewire Components (registered with `qf.` prefix)

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| NavigationLayout | `qf.navigation-layout` | `NavigationLayout` | Main app shell |
| TopNav | `qf.top-nav` | `TopNav` | Top navigation bar |
| Sidebar | `qf.sidebar` | `Sidebar` | Collapsible sidebar |
| BottomBar | `qf.bottom-bar` | `BottomBar` | Mobile bottom navigation |
| HorizontalContextMenu | `qf.horizontal-context-menu` | `HorizontalContextMenu` | Context-sensitive horizontal menu |
| MenuRenderer | `qf.menu-renderer` | `MenuRenderer` | Dynamic menu renderer |

### Source Locations (§2.1 Directory Map)

- [`src/Components/NavigationLayout.php`](../../src/Components/NavigationLayout.php) — main app shell (Blade component)
- [`src/Http/Livewire/Layouts/NavigationLayout.php`](../../src/Http/Livewire/Layouts/NavigationLayout.php) — Livewire layout shell
- [`src/Http/Livewire/Layouts/Navs/TopNav.php`](../../src/Http/Livewire/Layouts/Navs/TopNav.php)
- [`src/Http/Livewire/Layouts/Navs/Sidebar.php`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php)
- [`src/Http/Livewire/Layouts/Navs/BottomBar.php`](../../src/Http/Livewire/Layouts/Navs/BottomBar.php)
- [`src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php`](../../src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php)
- [`src/Http/Livewire/Layouts/Navs/MenuRenderer.php`](../../src/Http/Livewire/Layouts/Navs/MenuRenderer.php)

### Navigation Services (§4.3)

| Service | Location | Purpose |
|---------|----------|---------|
| [`NavigationManager`](../../src/Services/Navigation/NavigationManager.php) | `src/Services/Navigation/NavigationManager.php` | Config-driven navigation: `getSections()`, priority chain, `context_groups` |
| [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php) | `src/Services/Navigation/WorkspaceFilter.php` | Workspace-scoped filtering: `filterContextGroups()` (feature gates), `filterContextItems()` (role/department constraints) |
| [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php) | `src/Services/Navigation/NullWorkspaceResolver.php` | Default no-op [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php) — returns empty context |

Related service contracts (full signatures in [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md)): [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php), [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php), [`NavigationProvider`](../../src/Contracts/Navigation/NavigationProvider.php).

---

## Library-level navigation keys

The library-level `config('ui-library.navigation')` keys (`top_bar`, `sidebar.sections`, `bottom_bar`, `company_provider`, `show_company_switcher`, `open_in_tabs`, workspace config) are documented canonically in [`10-settings-and-config.md`](./10-settings-and-config.md#navigation).

---

## Permission Filtering

- The [`NavigationFilter`](../../src/Traits/NavigationFilter.php) trait filters items based on Spatie permissions. It is consumed by [`NavigationManager`](../../src/Services/Navigation/NavigationManager.php) (`use NavigationFilter`).
- Gate strings support three formats (checked in [`NavigationManager::checkGate()`](../../src/Services/Navigation/NavigationManager.php:447)):
  - `role:super_admin` — user must have the given Spatie role
  - `permission:view_dashboard` — user must have the given Spatie permission
  - `can:update,App\Models\Post` — Laravel `Gate::allows()` check
- Default nav items are provided by the [`HasNavItems`](../../src/Traits/HasNavItems.php:5) trait: dashboard, profile, account, help, settings.

---

## Sidebar State

Sidebar state is controlled via the `sidebar.initial_state` config key (default `full`) and toggled at runtime by [`Sidebar::toggleState()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:341), which persists to both the session (`sidebar_state`) and localStorage (`saveSidebarState` event). Menu type (sidebar vs horizontal) is persisted via `context_menu_type` session key by [`MenuRenderer::switchMenuType()`](../../src/Http/Livewire/Layouts/Navs/MenuRenderer.php:56).

---

## Phase 4.3: Section-Based Sidebar

[`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php) and [`MenuRenderer`](../../src/Http/Livewire/Layouts/Navs/MenuRenderer.php) render the sidebar as **grouped sections**. Two grouping mechanisms coexist:

1. **Per-context-group `sidebar` config** (in `navigation.php`): the active context group's `sidebar` key controls how that group is rendered as an expandable section. Supported keys (see [`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:48) docblock):

   | Key | Type | Purpose |
   |-----|------|---------|
   | `section_label` | `string\|null\|false` | Custom header label (`null` = use group label, `false` = no header) |
   | `collapsible` | `bool` | Enable expand/collapse toggle on section headers |
   | `expanded_default` | `bool` | Start expanded (only when `collapsible`) |

2. **Module sections** (fallback/legacy): [`Sidebar::buildModuleSectionsLegacy()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:193) builds one section per `user_facing` module from `config('ui-library.modules')`, sorting by `order` and auto-expanding sections whose items match the active route.

Section state is held in [`Sidebar::$moduleSections`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:70) (keyed by section slug, each with `key`, `label`, `icon`, `items`, `has_active`) and [`Sidebar::$expandedSections`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:77). [`Sidebar::toggleSection()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:141) toggles a section's expanded/collapsed state.

---

## Phase 4.4: Dropdown Application Switcher

The dedicated `ModuleSwitcher` Livewire component was **deleted** and replaced with an **inline Bootstrap 5 dropdown** in [`TopNav`](../../src/Http/Livewire/Layouts/Navs/TopNav.php). This eliminated ~42 lines of custom JS.

- **Module resolution** ([`TopNav::loadModules()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:489)): reads `config('ui-library.modules')` and keeps only modules that are `enabled` **and** `user_facing: true`, then applies role filtering (`roles`, supporting `['*']` wildcard). Modules are sorted by `order` and dispatched through the [`NavigationBuilding`](../../src/Events/NavigationBuilding.php) event.
- **Active module** ([`TopNav::$activeModuleKey`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:29)) is read from the `active_module` session key, falling back to the first loaded module. The current label is derived via [`TopNav::getCurrentModuleLabelProperty()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:483).
- **Switching** ([`TopNav::switchModule()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:473)) persists the selection to session and redirects to the module's configured route.

> **Historical note**: the blueprint's Phase 4.4 status references a `TopNav::determineModuleName()` prop-overwrite bug fix. In the current source, active-module naming is consolidated into [`TopNav::loadModules()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:489) / [`TopNav::getCurrentModuleLabelProperty()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:483), which derive the name from the loaded module registry rather than a clobbered `moduleName` prop.

---

## Phase 4.5: Config-Driven Navigation Metadata

### `NavigationManager::getSections()`

[`NavigationManager::getSections()`](../../src/Services/Navigation/NavigationManager.php:44) returns sidebar sections (each with `key`, `label`, `icon`, `items`, `has_active`), resolving via a priority chain:

1. **Published section definitions** — if `config('ui-library.navigation.sidebar.sections')` is non-empty, [`buildFromConfig()`](../../src/Services/Navigation/NavigationManager.php:69) is used. Each section supports `slug`, `label`, `icon`, `order`, `gate`, `permission`, `enabled`, `module` (shorthand), or explicit `items` (module references, custom routes, or custom URLs).
2. **Module-registry fallback** — otherwise [`buildFromModuleRegistry()`](../../src/Services/Navigation/NavigationManager.php:138) auto-builds sections from `user_facing` modules and their per-module `navigation.php` configs (matching pre-4.5 behaviour).

Sections and items are filtered by dependency satisfaction (`depends_on`), module gates, item permissions, and visibility rules.

### Navigation config file resolution priority

Both [`NavigationManager::resolveNavigationConfigPath()`](../../src/Services/Navigation/NavigationManager.php:558) and [`Sidebar::resolveNavigationConfigPath()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:302) resolve a module's `navigation.php` in descending priority:

1. **Published override** — `resources/views/vendor/qf-core/{module}/Config/navigation.php`
2. **Business module** — `app/Modules/{Module}/Config/navigation.php`
3. **Core module path** — `config('ui-library.module_paths.core')/{Module}/Config/navigation.php`
4. **Vendor fallback** — `vendor/quicker-faster/ui-library/src/Core/{Module}/Config/navigation.php`

This ensures consuming apps can override library defaults.

### `SidebarComposer`

[`SidebarComposer`](../../src/Http/ViewComposers/SidebarComposer.php) (registered in [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php)) injects three variables into the sidebar view:

- `currentOrganization` — resolved by priority: session `current_company_id` → [`CompanyProvider::getCurrentCompanyId()`](../../src/Contracts/Navigation/CompanyProvider.php) → first available organization.
- `userOrganizations` — normalized list (each `id`, `name`, `logo`) from [`CompanyProvider::getCompanies()`](../../src/Contracts/Navigation/CompanyProvider.php), with a `user->companies` relationship fallback.
- `sidebarSections` — from [`NavigationManager::getSections()`](../../src/Services/Navigation/NavigationManager.php:44).

### Context Groups → Sidebar linkage

[`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php) receives an `$activeContext` (plus `contextGroupLabel`, `contextGroupIcon`, `contextGroupConfig`) from `NavigationLayout`. When context-specific items are present and `$activeContext` is set, [`Sidebar::buildModuleSections()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:156) renders **only that context group's items** — selecting a top-nav tab shows that context's items in the sidebar. Otherwise it falls back to `NavigationManager`, then to the Phase 4.3 legacy build.

> **⚠️ Context Group Matching**: The `context` prop in every blade view's `<x-qf::navigation-layout>` MUST match the context group key in the module's `Config/navigation.php`. A mismatch causes the wrong sidebar links to appear. Example: blade has `context="my-portal"` → nav config must define a `'my-portal'` context group. See [Pre-Coding Checklist](../consuming-app/pre-coding-checklist.md) §E.

---

## Workspace Parameter Support

The workspace context is resolved via the [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php) contract and applied by [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php). The library ships [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php) (empty context — no filtering by default); consuming apps bind their own implementation returning a context array (e.g., `company_id`, `role`, `department_type`, `features`).

- **Group filtering** ([`WorkspaceFilter::filterContextGroups()`](../../src/Services/Navigation/WorkspaceFilter.php:34)): context groups with a `feature` key are kept only when that feature is in the workspace's `features` array.
- **Item filtering** ([`WorkspaceFilter::filterContextItems()`](../../src/Services/Navigation/WorkspaceFilter.php:68)): items with a `workspace` constraint map are kept only when **all** key/value pairs match the workspace context. This is applied inside [`NavigationManager::loadModuleNavItems()`](../../src/Services/Navigation/NavigationManager.php:326).

See [`navigation-workspace-architecture.md`](../project/navigation-workspace-architecture.md) for the complete navigation config-to-UI mapping.

---

## Related Phase 5 UX

Phase 5 built additional navigation UX on top of this foundation — **WorkspaceTabs**, **Breadcrumbs**, and **Sidebar Filter**. All client-side interactivity is vanilla JS (IIFE, `data-*` attributes, `Livewire.dispatch()`). See [`phase-5-navigation-ux.md`](./phase-5-navigation-ux.md) and the component READMEs in [`../components/`](../components/).

### Sidebar Filter

The sidebar filter placeholder label is **"Search menu..."** (Spanish **"Buscar menú..."**), sourced from the `filter_modules` translation key. Its listeners are registered with document-level event delegation and re-initialised on the `livewire:navigated` event, so the filter survives Livewire `wire:navigate` SPA navigations (the sidebar DOM is swapped without re-firing `livewire:initialized`). Full details in [`../components/sidebar-filter.md`](../components/sidebar-filter.md).

---

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`13-adr.md`](./13-adr.md) · [`phase-5-navigation-ux.md`](./phase-5-navigation-ux.md)
