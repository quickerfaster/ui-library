# QuickerFaster UI Library — Navigation: Contract-to-Implementation Map

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-09-18
> **Status**: ✅ Complete — comprehensive map of every navigation contract, service, component, view, trait, event, and config key

**Related files**: [`06-navigation-system.md`](./06-navigation-system.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`sidebar-active-state-pitfalls.md`](./sidebar-active-state-pitfalls.md) · [`phase-5-navigation-ux.md`](./phase-5-navigation-ux.md)

---

## Purpose

This document consolidates every navigation-related artifact in the library — contracts, services, components, views, traits, events, config keys, and session keys — into a single reference. It maps each contract to its implementation(s) and consumer(s), traces the complete data flow from `navigation.php` config to rendered HTML, and identifies gaps (dead code, stale references, naming inconsistencies).

Use this as the **single source of truth** when investigating navigation bugs, adding navigation features, or onboarding new developers to the navigation architecture.

---

## 1. Contract → Implementation → Consumer Map

### 1.1 Navigation Contracts

| Contract | Location | Implementation(s) | Consumer(s) | Status |
|----------|----------|-------------------|-------------|--------|
| [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php) | `src/Contracts/Navigation/` | [`NullCompanyProvider`](../../src/Services/Navigation/NullCompanyProvider.php) (default, returns empty) | [`TopNav::loadCompanies()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:741), [`NavigationHub::loadCompanyContext()`](../../src/Http/Livewire/Layouts/Navs/NavigationHub.php:74), [`SidebarComposer`](../../src/Http/ViewComposers/SidebarComposer.php) | ✅ Active |
| [`WorkspaceResolver`](../../src/Contracts/Navigation/WorkspaceResolver.php) | `src/Contracts/Navigation/` | [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php) (default, empty context — no filtering) | [`NavigationLayout::loadNavigationConfig()`](../../src/Components/NavigationLayout.php:193), [`NavigationManager::loadModuleNavItems()`](../../src/Services/Navigation/NavigationManager.php:379) | ✅ Active |
| [`NavigationProvider`](../../src/Contracts/Navigation/NavigationProvider.php) | `src/Contracts/Navigation/` | **NONE** — no class implements this interface | **NONE** — no service or component calls it | ❌ Dead code |

### 1.2 Navigation Services

| Service | Location | Purpose | Consumed By |
|---------|----------|---------|-------------|
| [`NavigationManager`](../../src/Services/Navigation/NavigationManager.php) | `src/Services/Navigation/` | Config-driven sidebar section builder; reads `config('ui-library.navigation.sidebar.sections')` or falls back to module registry | [`Sidebar::buildModuleSections()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:156), [`SidebarComposer`](../../src/Http/ViewComposers/SidebarComposer.php), [`NavigationLayout::resolveCurrentSection()`](../../src/Components/NavigationLayout.php:331) |
| [`WorkspaceFilter`](../../src/Services/Navigation/WorkspaceFilter.php) | `src/Services/Navigation/` | Filters context groups by `feature` gates and context items by `workspace` constraint maps | [`NavigationLayout::loadNavigationConfig()`](../../src/Components/NavigationLayout.php:193), [`NavigationManager::loadModuleNavItems()`](../../src/Services/Navigation/NavigationManager.php:379) |
| [`NullCompanyProvider`](../../src/Services/Navigation/NullCompanyProvider.php) | `src/Services/Navigation/` | Default no-op `CompanyProvider` — returns empty collection/null | Bound via `config('ui-library.navigation.company_provider')` |
| [`NullWorkspaceResolver`](../../src/Services/Navigation/NullWorkspaceResolver.php) | `src/Services/Navigation/` | Default no-op `WorkspaceResolver` — returns empty context, all features enabled | Bound via `config('ui-library.navigation.workspace_resolver')` |

### 1.3 Navigation Traits

| Trait | Location | Purpose | Used By | Status |
|-------|----------|---------|---------|--------|
| [`NavigationFilter`](../../src/Traits/NavigationFilter.php) | `src/Traits/` | `filterVisibleItems()` + `checkVisibility()` — filters items by `visibility` rules (`any`, `auth`, `guest`, `role:X`, `permission:X`) | [`NavigationLayout`](../../src/Components/NavigationLayout.php), [`NavigationManager`](../../src/Services/Navigation/NavigationManager.php) | ✅ Active |
| [`HasNavItems`](../../src/Traits/HasNavItems.php) | `src/Traits/` | `defaultNavItems()` — returns dashboard, profile, account, help, settings items | **NONE** — no class uses this trait | ❌ Dead code |

### 1.4 Navigation Events

| Event | Location | Dispatched By | Listened By | Purpose |
|-------|----------|---------------|-------------|---------|
| [`NavigationBuilding`](../../src/Events/NavigationBuilding.php) | `src/Events/` | [`TopNav::loadModules()`](../../src/Http/Livewire/Layouts/Navs/TopNav.php:1062) | Consuming app listeners (optional) | Last-mile customization of the modules array before rendering |

### 1.5 Navigation View Composers

| Composer | Location | Injects | Registered In |
|----------|----------|---------|---------------|
| [`SidebarComposer`](../../src/Http/ViewComposers/SidebarComposer.php) | `src/Http/ViewComposers/` | `$currentOrganization`, `$userOrganizations`, `$sidebarSections` | [`UILibraryServiceProvider`](../../src/Providers/UILibraryServiceProvider.php) |

---

## 2. Complete Component Hierarchy

```
NavigationLayout (Blade Component — src/Components/NavigationLayout.php, 508 lines)
│  Orchestrator: loads navigation.php, determines active context, feeds sub-components
│
├── TopNav (Livewire — src/Http/Livewire/Layouts/Navs/TopNav.php, 1072 lines)
│   │  Top bar: context-group tabs + module switcher + company switcher +
│   │  notifications + quick actions + background jobs
│   │
│   ├── top-nav.blade.php (381 lines)
│   │   ├── partials/top-nav-item.blade.php — individual tab with permission check
│   │   └── Inline: module switcher dropdown, company switcher dropdown,
│   │       notifications bell, quick actions search/⚡, background jobs,
│   │       cross-module links, mobile NavigationHub trigger
│   │
│   └── Loads: modules (config), companies (CompanyProvider), notifications (DB),
│       quick actions (ActionRegistry + RankingEngine), favorites (UserFavoriteAction)
│
├── WorkspaceTabs (Livewire — src/Http/Livewire/Layouts/Navs/WorkspaceTabs.php)
│   │  Browser-style tab strip (Phase 5)
│   │
│   └── workspace-tabs.blade.php
│       └── Vanilla JS: quicker-faster.js (data-tab-* attributes, Livewire.dispatch)
│
├── MenuRenderer (Livewire — src/Http/Livewire/Layouts/Navs/MenuRenderer.php, 72 lines)
│   │  Thin switcher between Sidebar and HorizontalContextMenu
│   │
│   ├── Sidebar (Livewire — src/Http/Livewire/Layouts/Navs/Sidebar.php, 377 lines)
│   │   │  Left sidebar with 5-level priority chain
│   │   │
│   │   ├── sidebar.blade.php (333 lines)
│   │   │   ├── partials/sidebar-section.blade.php — collapsible section header + body
│   │   │   ├── partials/sidebar-item.blade.php — individual item with active-state +
│   │   │   │   permission logic + workspace-tab integration
│   │   │   ├── Sidebar filter (vanilla JS, data-sidebar-filter-*)
│   │   │   └── Application switcher (organization dropdown)
│   │   │
│   │   └── Falls back to: NavigationManager → SidebarComposer → flat items → debug
│   │
│   └── HorizontalContextMenu (Livewire — src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php, 275 lines)
│       │  Horizontal button bar alternative; supports single-context and
│       │  cross-context dropdown modes (showAllContexts)
│       │
│       └── horizontal-context-menu.blade.php
│
├── BottomBar (Livewire — src/Http/Livewire/Layouts/Navs/BottomBar.php, 148 lines)
│   │  Mobile bottom tab bar with overflow "More" sheet
│   │
│   └── bottom-bar.blade.php
│
├── ContextSheet (Livewire — src/Http/Livewire/Layouts/Navs/ContextSheet.php, 105 lines)
│   │  Mobile slide-up sub-item panel
│   │
│   └── context-sheet.blade.php
│
├── NavigationHub (Livewire — src/Http/Livewire/Layouts/Navs/NavigationHub.php, 167 lines)
│   │  Mobile module + company switching sheet (self-sufficient, loads own data)
│   │
│   └── navigation-hub.blade.php
│
└── Breadcrumbs (Blade component — src/Components/Breadcrumbs.php)
    │  Auto-generated trail: Home → Module → Context → Section → Page
    │
    └── breadcrumbs.blade.php
```

---

## 3. Data Flow: Config → Rendered HTML

```
┌──────────────────────────────────────────────────────────────────┐
│ navigation.php (PHP array in app/Modules/{Module}/Config/)       │
│                                                                  │
│  Top-level keys:                                                 │
│  ├── context_groups  → TopNav tabs (label, icon, url, order)     │
│  ├── contexts        → Sidebar items keyed by context slug       │
│  ├── layout          → UI chrome toggles (top_bar, sidebar,      │
│  │                      bottom_bar, context_menu)                │
│  ├── shared_items    → header/footer sidebar items               │
│  └── shared_top_items → left/right top-bar items                 │
└──────────────────────────┬───────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────────┐
│ NavigationLayout::loadNavigationConfig()                         │
│                                                                  │
│  1. resolveNavigationConfigPath(moduleName) — 4-tier resolution  │
│  2. require $configPath                                          │
│  3. $this->contextGroups = $config['context_groups']             │
│  4. $this->contextItems  = $config['contexts']                   │
│  5. $this->layoutConfig  = $config['layout']                     │
│  6. filterVisibleItems() via NavigationFilter trait              │
│  7. WorkspaceFilter on contextGroups (feature gates)             │
│  8. WorkspaceFilter on contextItems (workspace constraints)      │
│  9. Sort contextGroups by order                                  │
│ 10. Merge contextItems into contextGroups (for BottomBar)        │
└──────────────────────────┬───────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────────┐
│ NavigationLayout::setActiveContext()                             │
│                                                                  │
│  1. Explicit context prop → isset($this->contextGroups[$ctx])    │
│  2. Route/path matching → longest-match-wins prefix              │
│  3. First group key fallback                                     │
└──────────────────────────┬───────────────────────────────────────┘
                           │
           ┌───────────────┼───────────────┐
           ▼               ▼               ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   TopNav     │  │   Sidebar    │  │  BottomBar   │
│              │  │              │  │              │
│ :items =     │  │ :items =     │  │ :contextGroups│
│ contextGroups│  │ contextItems │  │ = contextGroups│
│ :activeContext│ │ [activeCtx]  │  │ :activeContext│
└──────────────┘  └──────────────┘  └──────────────┘
```

---

## 4. Config Resolution: 4-Tier Priority

All three of `NavigationLayout`, `NavigationManager`, and `Sidebar` implement the same resolution chain for a module's `navigation.php`:

| Priority | Path | Purpose |
|----------|------|---------|
| 1 (highest) | `resources/views/vendor/qf-core/{module}/Config/navigation.php` | Published override — consuming app's explicit customization |
| 2 | `app/Modules/{Module}/Config/navigation.php` | Business module — consuming app's own module |
| 3 | `config('ui-library.module_paths.core')/{Module}/Config/navigation.php` | Core module path — library default |
| 4 (lowest) | `vendor/quicker-faster/ui-library/src/Core/{Module}/Config/navigation.php` | Vendor fallback — shipped with package |

> **⚠️ Maintainability note**: This 4-tier resolution is duplicated in three classes. A future refactor should extract it into a single `NavigationConfigResolver` service.

---

## 5. Sidebar 5-Level Priority Chain

When rendering sidebar content, [`sidebar.blade.php`](../../src/Resources/views/livewire/navs/sidebar.blade.php:66) evaluates in this order:

| Priority | Condition | Source | When Used |
|----------|-----------|--------|-----------|
| 1 | `$activeContext` set AND `$items` non-empty | Context-specific items from `NavigationLayout` | Normal case — top-nav tab selected |
| 2 | `$moduleSections` non-empty | `NavigationManager::getSections()` or `Sidebar::buildModuleSectionsLegacy()` | Fallback when no context items |
| 3 | `$sidebarSections` non-empty | `SidebarComposer` (injected via view composer) | Config-driven sections |
| 4 | `$items` non-empty (flat) | Direct prop pass | Backward compatibility |
| 5 | Everything empty | Debug message | Development/debug mode only |

---

## 6. Active State Resolution (Three Independent Mechanisms)

The sidebar active state can be determined by three mechanisms that may conflict:

```
Page Loads
  │
  ├── 1. Explicit context prop (most reliable)
  │     context="my-portal" → isset($contextGroups['my-portal'])
  │
  ├── 2. URL path matching (fallback)
  │     setActiveContext(): longest-match-wins prefix matching
  │     sidebar-item.blade.php: route/URL comparison
  │
  └── 3. Model-name fallback (least reliable)
        configKey → $currentModelName → sidebar-item.blade.php model-name match
```

See [`sidebar-active-state-pitfalls.md`](./sidebar-active-state-pitfalls.md) for the 5 documented bug patterns and their fixes.

---

## 7. Permission Filtering Layers

Three independent filtering layers apply to navigation items. They are **not unified**:

| Layer | Where Applied | Mechanism |
|-------|---------------|-----------|
| **Visibility rules** | `NavigationLayout` (via `NavigationFilter` trait) | `visibility` key: `any`, `auth`, `guest`, `role:X`, `permission:X` |
| **Workspace constraints** | `NavigationLayout` + `NavigationManager` (via `WorkspaceFilter`) | `feature` key on groups; `workspace` key-value map on items |
| **Permission/gate/role** | `NavigationManager` + inline in `sidebar-item.blade.php` + `top-nav-item.blade.php` | `permission` key, `roles` key, `gate` strings (`role:X`, `permission:X`, `can:X`) |

> **⚠️ Gap**: The item `permission` field is enforced by `NavigationManager` in the sidebar fallback path (Priority 2–3) but **not** by `NavigationLayout` in the primary context-specific path (Priority 1). However, `sidebar-item.blade.php` and `top-nav-item.blade.php` both perform their own inline permission checks, providing defense in depth.

---

## 8. Config Keys Reference

All navigation-related keys under `config('ui-library')`:

| Key | Default | Source Location | Purpose |
|-----|---------|-----------------|---------|
| `navigation.top_bar.enabled` | `true` | [`ui-library.php`](../../src/Config/ui-library.php:183) | Show/hide top bar |
| `navigation.top_bar.max_desktop` | `5` | [`ui-library.php`](../../src/Config/ui-library.php:201) | Visible tabs before overflow |
| `navigation.top_bar.max_mobile` | `3` | [`ui-library.php`](../../src/Config/ui-library.php:202) | Mobile visible tabs |
| `navigation.sidebar.initial_state` | `'full'` | [`ui-library.php`](../../src/Config/ui-library.php:302) | Sidebar start state (`full`/`icon`) |
| `navigation.sidebar.sections` | `[]` | [`ui-library.php`](../../src/Config/ui-library.php:256) | Published section definitions |
| `navigation.bottom_bar.enabled` | `true` | [`ui-library.php`](../../src/Config/ui-library.php:306) | Show/hide mobile bottom bar |
| `navigation.open_in_tabs` | `false` | [`ui-library.php`](../../src/Config/ui-library.php:181) | Workspace tab mode |
| `navigation.show_company_switcher` | `true` | [`ui-library.php`](../../src/Config/ui-library.php:322) | Company switcher visibility |
| `navigation.company_provider` | `NullCompanyProvider` | [`ui-library.php`](../../src/Config/ui-library.php:308) | Company data provider |
| `navigation.workspace_resolver` | `NullWorkspaceResolver` | [`ui-library.php`](../../src/Config/ui-library.php:320) | Workspace context provider |
| `navigation.context_menu.max_visible_items` | `7` | (resolved in `NavigationLayout`) | Horizontal menu overflow threshold |
| `layout.workspace_tabs.enabled` | `true` | [`ui-library.php`](../../src/Config/ui-library.php:108) | Tab strip toggle |
| `breadcrumb.show_home` | `true` | [`ui-library.php`](../../src/Config/ui-library.php:366) | Home breadcrumb |
| `breadcrumb.max_visible` | `4` | [`ui-library.php`](../../src/Config/ui-library.php:367) | Before "..." collapse |
| `module_switcher.enabled` | `true` | (resolved in `TopNav`) | Module switcher toggle |
| `module_switcher.roles` | `'*'` | (resolved in `TopNav`) | Module switcher role gate |
| `multitenancy.switcher_roles` | `['*']` | [`ui-library.php`](../../src/Config/ui-library.php:587) | Company switcher role gate |
| `multitenancy.all_companies_roles` | `'*'` | [`ui-library.php`](../../src/Config/ui-library.php:596) | "All Companies" access roles |
| `features.multi_company` | `false` | [`ui-library.php`](../../src/Config/ui-library.php:568) | Multi-company feature flag |

---

## 9. Session Keys

| Key | Type | Set By | Read By | Purpose |
|-----|------|--------|---------|---------|
| `active_module` | `string` | `TopNav::switchModule()`, `NavigationHub::switchModule()` | `NavigationLayout::determineModuleName()`, `TopNav::loadModules()` | Currently selected business module |
| `current_company_id` | `int` | `TopNav::switchCompany()`, `NavigationHub::switchCompany()`, `OrganizationSwitchController` | `TopNav::loadCompanies()`, `SidebarComposer`, `NavigationHub` | Currently selected company |
| `sidebar_state` | `string` | `Sidebar::toggleState()` | `NavigationLayout::__construct()` | Sidebar width (`full`/`icon`) |
| `context_menu_type` | `string` | `MenuRenderer::switchMenuType()` | `MenuRenderer::mount()`, `NavigationLayout::__construct()` | Menu type (`sidebar`/`horizontal`) |
| `workspace_tabs` | `array` | `WorkspaceTabs` | `WorkspaceTabs` | Open workspace tab list |
| `workspace_active_tab` | `string\|null` | `WorkspaceTabs` | `WorkspaceTabs` | Active workspace tab ID |
| `workspace_recently_closed` | `array` | `WorkspaceTabs` | `WorkspaceTabs` | Last 10 closed tabs (FIFO) |

---

## 10. Translation Keys

Defined in [`src/Resources/lang/en/nav.php`](../../src/Resources/lang/en/nav.php) (also `es/nav.php`):

| Key | English | Spanish |
|-----|---------|---------|
| `dashboard` | Dashboard | Panel |
| `home` | Home | Inicio |
| `profile` | Profile | Perfil |
| `account` | Account | Cuenta |
| `settings` | Settings | Configuración |
| `help` | Help | Ayuda |
| `locale` | Language | Idioma |
| `logout` | Logout | Cerrar sesión |
| `more` | More | Más |
| `search_placeholder` | Type to search... | Escriba para buscar... |
| `more_tabs` | More tabs... | Más pestañas... |
| `close_tab` | Close tab | Cerrar pestaña |
| `close_others` | Close others | Cerrar otras |
| `close_all_to_right` | Close all to right | Cerrar todas a la derecha |
| `close_all` | Close all | Cerrar todas |
| `reopen_closed_tab` | Reopen closed tab | Reabrir pestaña cerrada |
| `filter_modules` | Search menu... | Buscar menú... |
| `no_results` | No matching items | Sin resultados |

---

## 11. Identified Gaps & Issues

### 11.1 Dead Code

| Artifact | Location | Issue |
|----------|----------|-------|
| `NavigationProvider` contract | [`src/Contracts/Navigation/NavigationProvider.php`](../../src/Contracts/Navigation/NavigationProvider.php) | Defined but never implemented or consumed. Navigation is resolved by `NavigationLayout` reading `navigation.php` config directly and `NavigationManager` doing the same. |
| `HasNavItems` trait | [`src/Traits/HasNavItems.php`](../../src/Traits/HasNavItems.php) | Defined but never used by any class. The `06-navigation-system.md` doc references it as providing "default nav items" but no code path calls `defaultNavItems()`. |

### 11.2 Stale Documentation References

| Doc | Stale Reference | Reality |
|-----|----------------|---------|
| `06-navigation-system.md` §"Source Locations" | Lists `src/Http/Livewire/Layouts/NavigationLayout.php` | File no longer exists — collapsed into Blade component |
| `02-directory-map.md` §2.1 | Lists `Http/Livewire/Layouts/NavigationLayout.php` | File no longer exists |
| `10-settings-and-config.md` §"Navigation" | `show_company_switcher => false` | Actual config default is `true` |
| `10-settings-and-config.md` §"Navigation" | `company_provider => DefaultCompanyProvider` | Actual config uses `NullCompanyProvider` |
| `10-settings-and-config.md` §"Approvals" | `approver_resolver => DefaultApproverResolver` | Actual config uses `WorkspaceScopedApproverResolver` |

### 11.3 Naming Inconsistency

The `06-navigation-system.md` config schema example uses `contexts` as the top-level key for context group definitions. However, the actual code in [`NavigationLayout::loadNavigationConfig()`](../../src/Components/NavigationLayout.php:145) reads:

```php
$this->contextGroups = $config['context_groups'] ?? [];  // Top-nav tabs
$this->contextItems  = $config['contexts'] ?? [];         // Sidebar items
```

The doc should use `context_groups` for groups and `contexts` for items to match the code.

### 11.4 Duplicated Config Resolution

The 4-tier `navigation.php` resolution is implemented identically in three classes:
- [`NavigationLayout::resolveNavigationConfigPath()`](../../src/Components/NavigationLayout.php:470)
- [`NavigationManager::resolveNavigationConfigPath()`](../../src/Services/Navigation/NavigationManager.php:565)
- [`Sidebar::resolveNavigationConfigPath()`](../../src/Http/Livewire/Layouts/Navs/Sidebar.php:302)

This is a maintainability risk — changes must be made in three places.

---

## 12. Quick Diagnostic Reference

When investigating navigation bugs, check these files in order:

1. **Config**: `app/Modules/{Module}/Config/navigation.php` — are `context_groups` keys matching `contexts` keys?
2. **Orchestrator**: [`NavigationLayout.php`](../../src/Components/NavigationLayout.php) — is `setActiveContext()` resolving correctly?
3. **Top bar**: [`TopNav.php`](../../src/Http/Livewire/Layouts/Navs/TopNav.php) — are context groups loading? Is overflow working?
4. **Sidebar**: [`sidebar.blade.php`](../../src/Resources/views/livewire/navs/sidebar.blade.php) — which priority level is rendering?
5. **Active state**: [`sidebar-item.blade.php`](../../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) — is the active check passing?
6. **Permissions**: [`top-nav-item.blade.php`](../../src/Resources/views/livewire/navs/partials/top-nav-item.blade.php) — is the permission check filtering items?
7. **Published views**: `resources/views/vendor/qf/` — are stale published views overriding library fixes?

---

**Related files**: [`06-navigation-system.md`](./06-navigation-system.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`sidebar-active-state-pitfalls.md`](./sidebar-active-state-pitfalls.md) · [`phase-5-navigation-ux.md`](./phase-5-navigation-ux.md) · [`../project/navigation-ux-analysis.md`](../project/navigation-ux-analysis.md) · [`../project/navigation-workspace-architecture.md`](../project/navigation-workspace-architecture.md)