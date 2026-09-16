# QuickerFaster UI Library — Navigation System

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-17

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`13-adr.md`](./13-adr.md) · [`phase-5-navigation-ux.md`](./phase-5-navigation-ux.md)

> **Consuming-app developers**: For the per-module `navigation.php` config schema, context groups, sidebar section configuration, and how-to recipes, see [../consuming-app/module-structure.md](../consuming-app/module-structure.md) §"Config/navigation.php".

---

## Overview

Navigation is a **cross-cutting concern** owned by the library. Per **ADR-005** (see [`13-adr.md`](./13-adr.md)), a single [`NavigationLayout`](../../src/Components/NavigationLayout.php) component composes all nav sub-components — [`TopNav`](../../src/Http/Livewire/Layouts/Navs/TopNav.php), [`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php), [`BottomBar`](../../src/Http/Livewire/Layouts/Navs/BottomBar.php), [`ContextSheet`](../../src/Http/Livewire/Layouts/Navs/ContextSheet.php), and [`NavigationHub`](../../src/Http/Livewire/Layouts/Navs/NavigationHub.php) — providing a consistent navigation architecture across all modules and viewport sizes.

**ADR-005 rationale**: one shared layout shell avoids per-module duplication, creates a predictable navigation contract, and centralizes the integration point. The trade-off is that `NavigationLayout` must stay simple while remaining flexible for varying modules and contexts.

### Desktop vs Mobile Architecture

| Surface | Desktop (≥768px) | Mobile (<768px) |
|---------|------------------|-----------------|
| **Context tabs** | TopNav horizontal tabs | BottomBar icon-only tabs + Handle Bar |
| **Sub-items** | Sidebar (left panel) | ContextSheet (slide-up, tap handle bar) |
| **Module/Company switch** | TopNav dropdowns | NavigationHub (slide-up, tap ⌘ button) |
| **Overflow contexts** | "More…" dropdown in TopNav | "More" tab → Overflow Sheet in BottomBar |

---

## Navigation Components

### Livewire Components (registered with `qf.` prefix)

| Component | Alias | Class | Purpose |
|-----------|-------|-------|---------|
| NavigationLayout | `qf.navigation-layout` | `NavigationLayout` | Main app shell |
| TopNav | `qf.top-nav` | `TopNav` | Top navigation bar (desktop + mobile) |
| Sidebar | `qf.sidebar` | `Sidebar` | Collapsible desktop sidebar |
| BottomBar | `qf.bottom-bar` | `BottomBar` | Mobile bottom tab bar + overflow sheet |
| ContextSheet | `qf.context-sheet` | `ContextSheet` | Mobile slide-up sub-item sheet |
| NavigationHub | `qf.navigation-hub` | `NavigationHub` | Mobile module + company switching sheet |
| HorizontalContextMenu | `qf.horizontal-context-menu` | `HorizontalContextMenu` | Context-sensitive horizontal menu |
| MenuRenderer | `qf.menu-renderer` | `MenuRenderer` | Dynamic menu renderer |

### Source Locations (§2.1 Directory Map)

- [`src/Components/NavigationLayout.php`](../../src/Components/NavigationLayout.php) — main app shell (Blade component)
- [`src/Http/Livewire/Layouts/NavigationLayout.php`](../../src/Http/Livewire/Layouts/NavigationLayout.php) — Livewire layout shell
- [`src/Http/Livewire/Layouts/Navs/TopNav.php`](../../src/Http/Livewire/Layouts/Navs/TopNav.php)
- [`src/Http/Livewire/Layouts/Navs/Sidebar.php`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php)
- [`src/Http/Livewire/Layouts/Navs/BottomBar.php`](../../src/Http/Livewire/Layouts/Navs/BottomBar.php)
- [`src/Http/Livewire/Layouts/Navs/ContextSheet.php`](../../src/Http/Livewire/Layouts/Navs/ContextSheet.php)
- [`src/Http/Livewire/Layouts/Navs/NavigationHub.php`](../../src/Http/Livewire/Layouts/Navs/NavigationHub.php)
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

## Per-Module `navigation.php` Config Schema

Each business module defines its navigation structure in `app/Modules/{Module}/Config/navigation.php`. The file returns an array with two top-level keys:

### `contexts` — Context Group Definitions

Context groups appear as **tabs in the TopNav bar**. Selecting a tab filters the sidebar to show only that group's items. Each context group is keyed by a unique slug:

```php
return [
    'contexts' => [
        'my-portal' => [
            'label'      => 'My Portal',           // Display label in TopNav tab
            'icon'       => 'fas fa-home',          // Font Awesome icon class
            'url'        => 'hr/my-portal',         // Default route when tab is clicked
            'permission' => 'view_my_portal',       // Spatie permission (optional)
            'roles'      => ['employee', 'manager'], // Spatie roles fallback (optional, '*' = all)
            'feature'    => 'ess',                   // Workspace feature gate (optional)
            'order'      => 10,                      // Sort order (lower = first)
            'sidebar'    => [                        // Sidebar rendering options (optional)
                'section_label'    => 'Self Service', // Custom header (null=use label, false=no header)
                'collapsible'      => true,           // Enable expand/collapse toggle
                'expanded_default' => true,           // Start expanded
            ],
            'items' => [                             // Sidebar navigation items
                [
                    'label'      => 'Dashboard',
                    'icon'       => 'fas fa-tachometer-alt',
                    'route'      => 'hr.dashboard-my-portal-overview',
                    'permission' => 'view_my_portal',
                ],
                [
                    'label'      => 'My Attendance',
                    'icon'       => 'fas fa-user-clock',
                    'url'        => '/hr/my-attendance',   // url takes precedence over route
                    'permission' => 'view_attendance',
                ],
                [
                    'label'      => 'External Link',
                    'icon'       => 'fas fa-external-link-alt',
                    'url'        => 'https://example.com',
                    'target'     => '_blank',               // Open in new tab
                ],
            ],
        ],
        'hr' => [ /* ... another context group ... */ ],
    ],

    // Legacy flat items (pre-context-groups, still supported)
    'items' => [
        ['label' => 'Dashboard', 'icon' => 'fas fa-home', 'route' => 'hr.dashboard'],
    ],
];
```

#### Context Group Keys

| Key | Type | Required | Purpose |
|-----|------|----------|---------|
| `label` | `string` | ✅ | Display text in the TopNav tab |
| `icon` | `string` | — | Font Awesome icon class for the tab |
| `url` | `string` | — | Default navigation target when tab is clicked |
| `route` | `string` | — | Named route (alternative to `url`) |
| `permission` | `string` | — | Spatie permission name; admin users bypass this check |
| `roles` | `array` | — | Spatie role names as fallback; `['*']` = all authenticated users |
| `feature` | `string` | — | Workspace feature flag — group hidden when feature not in workspace context |
| `order` | `int` | — | Sort position (lower = further left in TopNav) |
| `sidebar` | `array` | — | Sidebar rendering options (see table below) |
| `items` | `array` | — | Sidebar navigation items for this context group |

#### Sidebar Rendering Options (`sidebar` key)

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `section_label` | `string\|null\|false` | `null` | Custom header label (`null` = use group label, `false` = no header) |
| `collapsible` | `bool` | `true` | Enable expand/collapse toggle on the section header |
| `expanded_default` | `bool` | `true` | Start in expanded state (only when `collapsible`) |

#### Item Keys

| Key | Type | Required | Purpose |
|-----|------|----------|---------|
| `label` | `string` | ✅ | Display text |
| `icon` | `string` | — | Font Awesome icon class |
| `route` | `string` | — | Named Laravel route |
| `url` | `string` | — | Raw URL (takes precedence over `route`) |
| `target` | `string` | — | Link target (`'_blank'` for external links) |
| `permission` | `string` | — | Spatie permission gate |
| `roles` | `array` | — | Spatie role gate |
| `workspace` | `array` | — | Key-value constraints matched against workspace context |
| `badge` | `array` | — | Badge config: `{ 'text' => 'New', 'color' => 'danger' }` |

### `items` — Legacy Flat Items (Pre-Context-Groups)

When a module predates the context group system, it may define a flat `items` array at the top level. These items appear in the sidebar regardless of which context group is active. New modules should use `contexts` instead.

> **⚠️ Critical Contract**: The `context` prop in `<x-qf::navigation-layout context="my-portal">` MUST match a context group key in `navigation.php`. A mismatch causes the wrong sidebar links to appear or the sidebar to fall back to `NavigationManager`/legacy mode.

---

## TopNav Architecture

[`TopNav`](../../src/Http/Livewire/Layouts/Navs/TopNav.php) (1072 lines) is the most complex navigation component. It renders the top bar with context group tabs, module/company switchers, and integrated subsystems.

### Context Group Loading

[`TopNav::loadContextGroups()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php) resolves context groups from the active module's `navigation.php`:

1. Reads the module's `Config/navigation.php` via the 4-tier resolution chain
2. Extracts the `contexts` array (keyed by context slug)
3. Applies [`WorkspaceFilter::filterContextGroups()`](../../src/Services/Navigation/WorkspaceFilter.php:34) — groups with a `feature` key are kept only when that feature is in the workspace's `features` array
4. Applies permission/role filtering — groups with unmet `permission` or `roles` are excluded
5. Sorts by `order` (lower = further left)
6. Dispatches the [`NavigationBuilding`](../../src/Events/NavigationBuilding.php) event for last-mile customization

### Overflow with Active-Item Promotion

When context groups exceed `max_desktop` (default 5) or `max_mobile` (default 3), excess tabs move into a "More" dropdown. The algorithm in [`TopNav::getVisibleContextGroupsProperty()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php) ensures the **active context group is always promoted** to the visible set, even if it would otherwise fall into overflow.

### Integrated Subsystems

TopNav hosts several subsystems that share its role-checking pattern (`$config['roles'] ?? '*'` with wildcard support):

| Subsystem | Config Key | Purpose |
|-----------|-----------|---------|
| **Module Switcher** | `ui-library.module_switcher` | Dropdown to jump between business modules; reads `user_facing` modules, applies role filtering |
| **Company Switcher** | `ui-library.navigation.show_company_switcher` | Multitenancy-aware company selector; gated by `features.multi_company` |
| **Quick Actions** | `ui-library.quick_actions` | Cmd+K command palette, ⚡ dropdown with favorites ranking via [`RankingEngine`](../../src/Services/QuickActions/RankingEngine.php) |
| **Notifications** | `ui-library.notifications` | Bell icon with unread count, real-time Echo broadcasting, role-gated visibility |
| **Background Jobs** | `ui-library.background_jobs` | Status indicator for running queue jobs with configurable roles |

Each subsystem follows the same pattern: read config → check `enabled` → check `roles` (with `'*'` wildcard) → render or hide.

---

## HorizontalContextMenu & MenuRenderer

### HorizontalContextMenu

[`HorizontalContextMenu`](../../src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) (275 lines) renders context group items as a **horizontal button bar** instead of a vertical sidebar. It supports two modes:

**Mode 1 — Single Context (default):** Renders items from the active context group as inline buttons. Uses the same overflow/promotion algorithm as TopNav: the first `maxVisibleItems` items are shown inline, and if the active item falls into overflow, it is promoted to the visible set.

**Mode 2 — Cross-Context Dropdowns (`showAllContexts = true`):** Renders ALL context groups as dropdown triggers. Each dropdown contains that group's items. The active context's dropdown is highlighted. This mode is used when the sidebar is hidden and the user needs access to all context groups from the horizontal bar.

Key properties:
- `$maxVisibleItems` — resolved from `config('ui-library.navigation.context_menu.max_visible_items', 7)`. Set to `0` to disable overflow (show all items).
- `$allowTypeSwitch` — when `true`, shows a toggle button to switch between sidebar and horizontal menu.
- `$position` — `'left'` or `'right'` alignment within the content area.

### MenuRenderer

[`MenuRenderer`](../../src/Http/Livewire/Layouts/Navs/MenuRenderer.php) (72 lines) is a thin wrapper that delegates to either `Sidebar` or `HorizontalContextMenu` based on the `menuType` session value:

- `menuType = 'sidebar'` → renders [`Sidebar`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php)
- `menuType = 'horizontal'` → renders [`HorizontalContextMenu`](../../src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php)

[`MenuRenderer::switchMenuType()`](../../src/Http/Livewire/Layouts/Navs/MenuRenderer.php:56) persists the choice to `session('context_menu_type')` and dispatches `saveMenuType` + `menu-type-changed` events. The `$counter` property forces child component re-mounting on switch.

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

#### Context Group Permission Fallback

Context groups in `navigation.php` support a dual-key permission model:

1. **`permission`** — Checked first via `AuthorizationService::canAccessView()`. Admin users bypass this check.
2. **`roles`** — Checked as fallback when the `permission` check fails. Supports `['*']` wildcard for all authenticated users.

This fallback is implemented in [`top-nav-item.blade.php`](src/Resources/views/livewire/navs/partials/top-nav-item.blade.php) and the three inline blocks in [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php).

Example context group with both keys:
```php
'Organization' => [
    'label' => 'Organization',
    'permission' => 'view_organization_overview',  // admin bypass
    'roles' => ['*'],                               // fallback for all users
    'url' => 'hr/dashboard-organization-overview',
],
```

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

> **⚠️ Context Group Matching**: The `context` prop in every blade view's `<x-qf::navigation-layout>` MUST match the context group key in the module's `Config/navigation.php`. A mismatch causes the wrong sidebar links to appear. Example: blade has `context="my-portal"` → nav config must define a `'my-portal'` context group. See [Pre-Coding Checklist](../consuming-app/pre-coding-checklist.md) §E and [Sidebar Active State Pitfalls](./sidebar-active-state-pitfalls.md) §5 for the full diagnosis and fix pattern.

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
