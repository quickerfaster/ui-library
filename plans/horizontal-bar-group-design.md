# Horizontal Bar Section Group Design

**Date:** 2026-08-12
**Scope:** QuickerFaster UI Library — [`HorizontalContextMenu`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) + [`top-nav`](src/Resources/views/livewire/navs/top-nav.blade.php)
**Status:** ✅ Phase 1 Implemented | ✅ Phase 2 Implemented (2026-08-12) | Phase 3 (Future)
**Builds on:** [`horizontal-bar-sections-ux-analysis.md`](plans/horizontal-bar-sections-ux-analysis.md)

---

## Table of Contents

1. [Section Structure Mapping](#1-section-structure-mapping)
2. [Recommended Strategy](#2-recommended-strategy)
3. [Answers to Core Questions](#3-answers-to-core-questions)
4. [Data Contract Changes](#4-data-contract-changes)
5. [Component Architecture](#5-component-architecture)
6. [Visual Mockups](#6-visual-mockups)
7. [Interaction Design](#7-interaction-design)
8. [Edge Case Handling](#8-edge-case-handling)
9. [Implementation Phases](#9-implementation-phases)
10. [Files Affected Summary](#10-files-affected-summary)

---

## 1. Section Structure Mapping

### 1.1 Current Two-Level Architecture

The navigation system operates at two distinct levels, handled by two separate Livewire components:

```
┌──────────────────────────────────────────────────────────────────────┐
│  LEVEL 1: TopNav (qf.top-nav)                                        │
│  Receives: $contextGroups (from navigation.php's context_groups key) │
│  Renders:  Tabs for each context group (People, Payroll, Time, etc.) │
│  Overflow: Phase 1 "More" dropdown (maxDesktop/maxMobile thresholds) │
└──────────────────────────────┬───────────────────────────────────────┘
                               │ user clicks a tab → activeContext changes
                               ▼
┌──────────────────────────────────────────────────────────────────────┐
│  LEVEL 2: HorizontalContextMenu (qf.horizontal-context-menu)         │
│  Receives: $contextItems[$activeContext] — flat array of child items │
│  Renders:  Inline nav links (currently flat, no overflow handling)   │
│  Problem:  10+ items overflow the bar with no mitigation             │
└──────────────────────────────────────────────────────────────────────┘
```

### 1.2 Sidebar-to-Horizontal Mapping

The sidebar renders context group items with section headers (`sidebar.blade.php` Priority 1 → `sidebar-section.blade.php`). The mapping to horizontal mode:

| Sidebar Concept | Current Horizontal Representation | Gap |
|---|---|---|
| Context group label as collapsible section header | TopNav tab (Level 1) | No gap — this IS the context group label |
| Items within a section, listed vertically | Flat inline links in HorizontalContextMenu (Level 2) | **No overflow handling; 10+ items break layout** |
| Expand/collapse toggle (Alpine.js) | Not applicable in horizontal bar | No equivalent needed in horizontal |
| Icon mode (60px sidebar) | Not applicable | N/A |
| Shared header/footer items | Not passed to HorizontalContextMenu | **Shared items are lost in horizontal mode** |

### 1.3 Key Insight: Groups ARE Context Groups

The "sidebar groups" referenced in the problem statement are the `context_groups` entries in [`navigation.php`](src/Core/Admin/Config/navigation.php). Each context group IS a sidebar section. The TopNav already renders context groups as tabs. Therefore:

- **A sidebar section header = a TopNav tab.** No new grouping layer is needed between TopNav and HorizontalContextMenu.
- **The design problem is entirely within the HorizontalContextMenu**: how to handle the flat list of child items when there are too many to display inline.

---

## 2. Recommended Strategy

### 2.1 Decision: Phase 1 Overflow (Priority-Based Adaptive) + Phase 2 Cross-Context Dropdowns (Optional)

This design adopts the hybrid recommendation from the [UX analysis](plans/horizontal-bar-sections-ux-analysis.md) with refined implementation details.

#### Phase 1 (Immediate): Overflow for HorizontalContextMenu

Add a "More" dropdown to the `HorizontalContextMenu` — mirroring the proven Phase 1 overflow mechanism already working in [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php). This requires **no data contract changes** and is fully backward-compatible.

**Rationale:**
- Pattern already proven in TopNav (same codebase, same developers)
- Solves the immediate problem without architectural restructuring
- Configurable threshold (`max_visible_items`) per module
- Active-item promotion ensures users never lose their current page
- Zero impact on sidebar — both modes remain independent

#### Phase 2 (Future): Cross-Context Dropdowns (`show_all_contexts: true`)

When a module wants a single-level navigation (no separate TopNav + context bar), the horizontal bar can optionally render ALL context groups as dropdowns, bypassing the two-level architecture.

**Rationale:**
- Opt-in, config-driven — doesn't break existing modules
- Reduces clicks for modules with few items per context
- Uses standard Bootstrap dropdowns (no custom mega menu component needed)
- Can coexist with Phase 1 overflow within each dropdown

### 2.2 What We Are NOT Doing

| Rejected Approach | Reason |
|---|---|
| **Flat list with separators (Strategy B)** | Mathematically cannot fit 10+ items per context |
| **Icon-only with tooltips (Strategy C)** | Violates discoverability; many items share icons |
| **Mega menu panel (Strategy D)** | High implementation cost; no Soft UI precedent; Phase 2 dropdowns achieve 80% of the value |
| **Scrollable tabs (Strategy F)** | UX anti-pattern on desktop; accessibility concerns |
| **Adding sub-group nesting within context items** | Would require data contract changes for a problem solvable without them |

---

## 3. Answers to Core Questions

### Q1: Should a sidebar group become its own dropdown in the horizontal bar?

**Answer: Not in Phase 1. Yes, optionally, in Phase 2.**

In the current two-level architecture, the TopNav tab IS the sidebar group's representation. Adding per-context-group dropdowns to the HorizontalContextMenu would duplicate the TopNav's function.

Phase 1 keeps the two-level separation: TopNav tabs switch context groups; the HorizontalContextMenu shows items for the active context with overflow handling.

Phase 2 optionally merges them via `show_all_contexts: true` — each context group becomes a dropdown trigger in the horizontal bar, containing its child items. When this mode is active, the TopNav context group tabs can be hidden (controlled by a companion config flag) to avoid visual duplication.

### Q2: If a group's items end up inside the "More" dropdown, how should grouping be visually indicated?

**Answer: Non-clickable group label → flat list of items below.**

The "More" dropdown will use a simple `<h6 class="dropdown-header">` for the context group label (non-clickable for navigation, serving as a visual section divider), followed by the overflow items as flat `<a class="dropdown-item">` links.

**Why not nested submenus?** Bootstrap dropdown submenus require custom JavaScript and have poor mobile UX. They add a hover/click target hierarchy that's fragile on touch devices.

**Why not accordion?** Accordions within dropdowns create excessive nesting and violate the simple "list of links" mental model of a dropdown menu.

The flat list with a header divider is the most robust, accessible, and Bootstrap-native approach.

Within the "More" dropdown:

```
┌──────────────────────────┐
│  ── People ──            │  ← dropdown-header (non-clickable label)
│  📋 Job History           │
│  🏷️ Tags                  │
│  💼 Job Titles            │
│  🏢 Employee Groups       │
└──────────────────────────┘
```

The header is rendered only when `$overflowItems` has 2+ items. If the overflow contains just 1 item, the header is omitted (the item speaks for itself as a descendant of the visible "More" trigger).

### Q3: Viewport resize transitions — how to preserve group identity when collapsing?

**Answer: Group identity is preserved at the TopNav level, not the HorizontalContextMenu level.**

When the viewport narrows enough to switch from desktop sidebar to horizontal bar + mobile TopNav:

1. **TopNav context group tabs are already responsive** via `maxDesktop`/`maxMobile` thresholds and the collapse into the "More" dropdown. The active context is promoted.

2. **The HorizontalContextMenu** operates within a single active context. Its Phase 1 overflow mechanism is independent of viewport width — it uses a fixed `max_visible_items` threshold. If responsive adaptation is desired, a future enhancement can add a `ResizeObserver` to adjust the threshold dynamically.

3. **The sidebar-to-horizontal switch** (controlled by `context_menu_type` in session/config) is a full page reload (triggered by `doReload`). Group identity is maintained because the `activeContext` is preserved across reloads — it's stored in the URL path and re-derived in [`NavigationLayout::setActiveContext()`](src/Components/NavigationLayout.php:170).

### Q4: Consistency with existing component library

All changes use only:
- **Bootstrap 5** dropdown classes (`.dropdown`, `.dropdown-toggle`, `.dropdown-menu`, `.dropdown-item`, `.dropdown-header`, `.dropdown-divider`)
- **Livewire** component properties and computed properties (no new JS dependencies)
- **Alpine.js** — only if dynamic threshold adjustment is added in a future iteration
- **Phase 1 overflow logic** — directly mirrors the proven pattern in [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php:153-192)

No new CSS framework, no new JavaScript library, no custom component system.

### Q5: Edge cases

See [Section 8](#8-edge-case-handling) below for comprehensive coverage.

---

## 4. Data Contract Changes

### 4.1 Navigation Config (`navigation.php`) — Phase 1

Add to the `layout.context_menu` section:

```php
'layout' => [
    'context_menu' => [
        'type' => 'sidebar',
        'position' => 'left',
        'allow_switch' => true,
        'default_type' => 'sidebar',

        // NEW: Phase 1 — horizontal bar item overflow
        'max_visible_items' => 6,       // items shown inline before "More" dropdown
        'promote_active_item' => true,  // always show the active item (default true)

        // NEW: Phase 2 (future)
        'show_all_contexts' => false,   // when true, render all context groups as dropdowns
        'hide_topnav_contexts' => false, // when true + show_all_contexts, hide context tabs in TopNav
    ],
],
```

**Defaults** (when keys are absent):
- `max_visible_items` defaults to `6`
- `promote_active_item` defaults to `true`
- `show_all_contexts` defaults to `false`

### 4.2 Navigation Config — No Changes to `context_groups` or `contexts`

The existing `context_groups` and `contexts` structures remain unchanged. The overflow mechanism works on the existing flat `$items` array. No new keys like `sub_groups`, `sections`, or `categories` are needed.

### 4.3 Component Property Contract — Phase 1

`HorizontalContextMenu` receives new optional properties:

| Property | Type | Default | Source |
|---|---|---|---|
| `maxVisibleItems` | `int` | `6` | `layout.context_menu.max_visible_items` config |
| `promoteActiveItem` | `bool` | `true` | `layout.context_menu.promote_active_item` config |
| `overflowLabel` | `string` | `'More'` | Translatable string |

No changes to the existing `$items`, `$position`, `$allowTypeSwitch`, or `$currentModelName` properties.

### 4.4 Component Property Contract — Phase 2 (Future)

`HorizontalContextMenu` receives additional optional properties:

| Property | Type | Default | Source |
|---|---|---|---|
| `contextGroups` | `array` | `[]` | `$contextGroups` from `NavigationLayout` |
| `contextItems` | `array` | `[]` | `$contextItems` from `NavigationLayout` |
| `activeContext` | `?string` | `null` | `$activeContext` from `NavigationLayout` |
| `showAllContexts` | `bool` | `false` | `layout.context_menu.show_all_contexts` config |

---

## 5. Component Architecture

### 5.1 Phase 1 Changes

#### File: [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php)

**New properties:**
```php
public int $maxVisibleItems = 6;
public bool $promoteActiveItem = true;
public string $overflowLabel = 'More';
```

**New computed properties** (mirroring TopNav's pattern):

| Method | Returns | Logic |
|---|---|---|
| `getVisibleItemsProperty()` | `array` | First `$maxVisibleItems` items; if active item is beyond threshold, promote it (keep `$maxVisibleItems - 1` + active item) |
| `getOverflowItemsProperty()` | `array` | All items NOT in `getVisibleItemsProperty()` |
| `getHasOverflowProperty()` | `bool` | `count($this->overflowItems) > 0` |
| `getActiveInOverflowProperty()` | `bool` | Active item is in overflow set |

**Active detection**: The component must determine which item is "active" to support promotion. Use the same logic that `horizontal-context-menu.blade.php` already implements (route matching + model name fallback at [lines 8-38](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php:8)). Extract this into a private `isItemActive(array $item): bool` method.

**`mount()` changes**: After receiving `$items`, compute visible/overflow split and store results.

#### File: [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php)

Restructure from a single flat loop to a two-part render:

```blade
<ul class="navbar-nav {{ $position === 'right' ? '' : 'me-auto' }}">
    {{-- Visible items (inline links) --}}
    @foreach ($this->visibleItems as $item)
        <li class="nav-item">
            <a href="{{ $itemUrl }}" class="nav-link {{ $isActive ? 'active fw-bold text-primary' : '' }}" wire:navigate>
                @if (!empty($item['icon']))
                    <i class="{{ $item['icon'] }} me-1"></i>
                @endif
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach

    {{-- "More" dropdown (when overflow exists) --}}
    @if ($this->hasOverflow)
        <li class="nav-item dropdown" wire:key="context-overflow-dropdown">
            <a class="nav-link dropdown-toggle {{ $this->activeInOverflow ? 'active fw-bold text-primary' : '' }}"
               href="#" role="button" data-bs-toggle="dropdown"
               aria-expanded="false" aria-haspopup="true">
                {{ $overflowLabel }}
                @if ($this->activeInOverflow)
                    <span class="badge rounded-pill bg-primary ms-1" style="font-size: 0.5rem;">●</span>
                @endif
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                @if (count($this->overflowItems) > 1)
                    {{-- Context group label as non-clickable header --}}
                    <li><h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">
                        {{ $contextGroupLabel ?? 'More Options' }}
                    </h6></li>
                @endif
                @foreach ($this->overflowItems as $item)
                    {{-- Same active / URL resolution logic as visible items --}}
                    <li wire:key="context-overflow-{{ $item['key'] ?? $loop->index }}">
                        <a href="{{ $itemUrl }}"
                           class="dropdown-item d-flex align-items-center {{ $isActive ? 'active fw-bold text-primary' : '' }}">
                            @if (!empty($item['icon']))
                                <i class="{{ $item['icon'] }} me-2" style="width: 20px;"></i>
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
    @endif
</ul>
```

The existing active-detection logic ([lines 8-38](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php:8)) should be extracted into a reusable Blade partial or PHP helper to avoid duplication between the visible and overflow loops.

#### File: [`NavigationLayout.php`](src/Components/NavigationLayout.php)

In `render()`, pass `maxVisibleItems` from config to the horizontal menu:

```php
'maxVisibleItems' => $this->layoutConfig['context_menu']['max_visible_items'] ?? 6,
'promoteActiveItem' => $this->layoutConfig['context_menu']['promote_active_item'] ?? true,
```

#### File: [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php)

Wire the new attributes to the `<livewire:qf.horizontal-context-menu>` tag at [lines 144-145](src/Resources/views/components/layouts/navigation-layout.blade.php:144):

```blade
<livewire:qf.horizontal-context-menu
    :currentModelName="$currentModelName"
    :items="$contextItems[$activeContext] ?? []"
    :position="$contextMenuPosition"
    :allowTypeSwitch="$allowMenuTypeSwitch"
    :maxVisibleItems="$maxVisibleItems"
    :promoteActiveItem="$promoteActiveItem"
    wire:key="horizontal-menu-{{ $moduleName }}-{{ $activeContext }}"
/>
```

### 5.2 Phase 2 Changes (Future)

When `show_all_contexts` is enabled, the `HorizontalContextMenu` receives `$contextGroups` and `$contextItems` and renders each context group as a dropdown trigger instead of a flat item list. The active context group's dropdown trigger is highlighted, and each dropdown internally applies the Phase 1 overflow pattern if the item count exceeds `max_visible_items`.

The `navigation-layout.blade.php` conditionally omits the TopNav's `<livewire:qf.top-nav>` context group rendering when `hide_topnav_contexts` is true alongside `show_all_contexts`.

### 5.3 New/Changed Files Summary

| File | Phase | Change Type |
|---|---|---|
| [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) | 1 | **MODIFY** — Add overflow split logic |
| [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) | 1 | **MODIFY** — Visible + overflow rendering |
| [`NavigationLayout.php`](src/Components/NavigationLayout.php) | 1 | **MODIFY** — Pass new config values |
| [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) | 1 | **MODIFY** — Wire new attributes |
| `*/Config/navigation.php` (all modules) | 1 | **MODIFY** — Add `max_visible_items` to layout |
| (Optional) `overflow-item.blade.php` | 1 | **NEW** — Shared partial for dropdown item rendering |
| [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) | 2 | **MODIFY** — Add `showAllContexts` mode |
| [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) | 2 | **MODIFY** — Context group dropdown rendering |
| [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) | 2 | **MODIFY** — Conditionally hide context tabs |

### 5.4 Files NOT Affected

| File | Reason |
|---|---|
| [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) | Unchanged — Phase 1 overflow is independent |
| [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) | Unchanged in Phase 1; minor in Phase 2 |
| [`sidebar.blade.php`](src/Resources/views/livewire/navs/sidebar.blade.php) | Unchanged — sidebar rendering is independent |
| [`sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) | Unchanged |
| [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) | Unchanged |
| [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php) | Unchanged |
| [`MenuRenderer.php`](src/Http/Livewire/Layouts/Navs/MenuRenderer.php) | Unchanged |

---

## 6. Visual Mockups

### 6.1 Phase 1: Context with Few Items (No Overflow)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🏢 QH  │  People  Payroll  Time  Leave  Organization  Policies  Reports   │ ← TopNav
└──────────────────────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────────────────┐
│  📊 Overview  │  👥 Employees  │  🪪 Profiles  │  👥 Teams  │  📁 Documents  │ ← HorizontalContextMenu
└──────────────────────────────────────────────────────────────────────────────┘
```

Five items, `max_visible_items = 6`, no overflow. Output is identical to current behavior.

### 6.2 Phase 1: Context with Many Items (Overflow Triggered)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🏢 QH  │  👥 People  Payroll  Time  Leave  Organization  ...  │  🔔  👤   │ ← TopNav (People is active)
└──────────────────────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────────────────┐
│  📊 Overview  │  👥 Employees  │  🪪 Profiles  │  👥 Teams  │  📁 Documents  │ [More ▾] │
└──────────────────────────────────────────────────────────────────────────────┘
                                                                   ┌──────────────────────┐
                                                                   │  ── People ──        │ ← non-clickable header
                                                                   │  📋 Job History       │
                                                                   │  🏷️ Tags              │
                                                                   │  💼 Job Titles        │
                                                                   │  🏢 Employee Groups   │
                                                                   │  📜 Current Jobs      │
                                                                   └──────────────────────┘
```

`max_visible_items = 5`, 10 items total. First 5 shown inline, remaining 5 in "More" dropdown. Context group label "People" appears as dropdown header.

### 6.3 Phase 1: Active Item Promoted from Overflow

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🏢 QH  │  👥 People  Payroll  Time  Leave  ...  │  🔔  👤                  │ ← TopNav
└──────────────────────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────────────────┐
│  📊 Overview  │  👥 Employees  │  🪪 Profiles  │  👥 Teams  │  💼 Job Titles  │ [More ▾ ●] │
└──────────────────────────────────────────────────────────────────────────────┘
                                                                   ┌──────────────────────┐
                                                                   │  ── People ──        │
                                                                   │  📁 Documents         │
                                                                   │  📋 Job History       │
                                                                   │  🏷️ Tags              │
                                                                   │  🏢 Employee Groups   │
                                                                   │  📜 Current Jobs      │
                                                                   └──────────────────────┘
```

User is on "Job Titles" page. It would normally be in overflow (position 8 of 10). Promotion moves it to the visible bar, bumping "Documents" into overflow. The "More" trigger shows a small dot indicator (`●`) to alert the user that the active item is in the overflow and was promoted.

### 6.4 Phase 2: All Contexts as Dropdowns

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🏢 QH  │  [👥 People ▾]  [💰 Payroll ▾]  [⏰ Time ▾]  [🏖️ Leave ▾]  ...  │ ← TopNav (context tabs hidden)
└──────────────────────────────────────────────────────────────────────────────┘
              ┌─────────────────────┐
              │ 👥 Employees        │
              │ 🪪 Profiles         │
              │ 👥 Teams            │
              │ 📁 Documents        │
              │ 📋 Job History      │
              │ ───────────────     │ ← divider
              │ More →              │ ← sub-overflow trigger (recursive Phase 1)
              └─────────────────────┘
```

Each context group is a dropdown trigger. The active context's trigger is highlighted. Each dropdown can internally apply the Phase 1 overflow for groups with many items. When `hide_topnav_contexts: true`, the TopNav no longer renders context group tabs (avoiding visual duplication).

### 6.5 Mobile: HorizontalContextMenu is Hidden

On mobile (`<768px`), the `HorizontalContextMenu` is wrapped in `d-none d-md-block` (same as current behavior at [line 134](src/Resources/views/components/layouts/navigation-layout.blade.php:134)). Navigation falls back to the TopNav (which already has mobile overflow handling + `visibleMobile`/`overflowMobile`) and the BottomBar.

---

## 7. Interaction Design

### 7.1 Click vs Hover

**Dropdown triggers: Click-only.** This follows Bootstrap 5's default behavior and is the accessible choice. Hover-to-open dropdowns:
- Interfere with touch devices (no hover state)
- Cause accidental opens when the cursor passes over the trigger en route to another target
- Require JavaScript workarounds that complicate the component

**"More" dropdown**: Standard Bootstrap `data-bs-toggle="dropdown"` with `aria-expanded` and `aria-haspopup="true"`.

**Dismissal**: Click outside, click the trigger again, or press Escape — all native Bootstrap behavior.

### 7.2 Keyboard Navigation

| Key | Action |
|---|---|
| **Tab** | Focus moves sequentially through visible items, then to "More" trigger |
| **Enter / Space** | Activate "More" trigger → open dropdown |
| **↓ (Down Arrow)** | When "More" is focused and dropdown is open: move focus to first dropdown item |
| **↑ (Up Arrow)** | Move to previous dropdown item |
| **Escape** | Close dropdown, return focus to "More" trigger |
| **Tab** (inside dropdown) | Close dropdown, move to next focusable element after "More" |

This is all native Bootstrap dropdown keyboard behavior. No custom keyboard handlers needed.

### 7.3 ARIA Attributes

```html
<!-- "More" trigger -->
<a class="nav-link dropdown-toggle"
   href="#"
   role="button"
   data-bs-toggle="dropdown"
   aria-expanded="false"
   aria-haspopup="true"
   aria-label="More navigation options">
    More
</a>

<!-- Dropdown menu -->
<ul class="dropdown-menu"
    role="menu"
    aria-label="Additional navigation items">

    <!-- Section header -->
    <li role="presentation">
        <h6 class="dropdown-header" role="separator">People</h6>
    </li>

    <!-- Dropdown items -->
    <li role="none">
        <a href="..." class="dropdown-item" role="menuitem">
            <i class="fa fa-history me-2" aria-hidden="true"></i>
            Job History
        </a>
    </li>
</ul>
```

### 7.4 Active State Indicators

| Scenario | Visual Indicator |
|---|---|
| Active item is a visible inline link | `.active.fw-bold.text-primary` class |
| Active item is in overflow (promoted) | Dot badge (`●`) on "More" trigger + `.active` class on trigger |
| Active item is in overflow (not promoted, `promoteActiveItem: false`) | `.active` class on "More" trigger only |
| "More" dropdown contains no active item | Normal (no special styling) |

### 7.5 No Animation or Transition (Phase 1)

The "More" dropdown uses Bootstrap's default instant show/hide. No custom CSS transitions. This keeps the implementation simple and avoids z-index or positioning conflicts with other overlays (modals, drawers, tooltips).

---

## 8. Edge Case Handling

### 8.1 Empty Context Group (No Child Items)

**Scenario**: A context group exists in `context_groups` but has no entries in `contexts` for it, or all items were filtered out by permissions/workspace filters.

**Handling**: The `HorizontalContextMenu` receives an empty `$items` array. The blade template renders an empty `<ul class="navbar-nav">` — the bar appears as a thin empty strip. This is the current behavior and is acceptable. The bar still shows the "Switch to Sidebar" button (if `allowTypeSwitch` is true), maintaining discoverability of the toggle.

### 8.2 Group with a Single Item

**Scenario**: A context group has exactly one child item (e.g., "Reports" → "Saved Reports").

**Handling**: With `max_visible_items ≥ 1`, the single item renders as an inline link. No "More" dropdown appears. This is identical to current behavior. The single item effectively acts as a direct link — users click the TopNav tab and are taken to the item's page (since clicking the tab already sets the active context and the single item is likely the first/default).

**Optimization**: When a context group has only 1 item, the TopNav tab click could navigate directly to that item instead of just setting the active context. This is a separate enhancement (the `route` key in `context_groups` already supports this — set `route` to the single item's route).

### 8.3 Group Where All Items Are in Overflow

**Scenario**: `max_visible_items = 3` but the context has 10 items, and the active item is the 6th item (promoted per `promoteActiveItem: true`).

**Handling**: The visible bar shows 2 regular items + the promoted active item = 3 total. The overflow contains 7 items. The "More" dropdown renders all 7 with the context group label header.

If `promoteActiveItem: false`: The visible bar shows the first 3 items. The overflow contains 7 items including the active one. The "More" trigger has the `.active` class but no dot badge (since promotion didn't happen — the dot is specifically for the "this was promoted" signal).

### 8.4 Nested Groups (Deep Hierarchy)

**Scenario**: A context group contains sub-groups (e.g., "People" → "Core" {Employees, Profiles} and "Organization" {Teams, Departments}).

**Handling**: The current navigation config has no sub-group structure — `contexts` values are flat arrays. Phase 1 does not introduce nesting. All items are treated as peers in the flat list.

**Future consideration**: If sub-grouping is later added to the config (e.g., a `groups` key within `contexts.People`), the Phase 1 overflow mechanism would still work — it operates on the flattened item list. The "More" dropdown could add additional `<h6 class="dropdown-header">` dividers for each sub-group. This is a non-breaking enhancement.

### 8.5 Mobile/Responsive Constraints

**Scenario**: User resizes viewport below 768px.

**Handling**: The entire `d-none d-md-block` wrapper at [line 134](src/Resources/views/components/layouts/navigation-layout.blade.php:134) hides the `HorizontalContextMenu` on mobile. Navigation falls through to the TopNav's mobile scrollable bar + BottomBar.

The TopNav already has `getVisibleMobileProperty()` with its own `maxMobile` threshold ([TopNav.php:203](src/Http/Livewire/Layouts/Navs/TopNav.php:203)). No changes needed for mobile.

### 8.6 Permission-Filtered Items

**Scenario**: After permission/workspace filtering, some items are removed. The remaining count is below `max_visible_items` but the unfiltered count was above it.

**Handling**: Filtering happens in [`NavigationLayout::loadNavigationConfig()`](src/Components/NavigationLayout.php:146-152) before data reaches `HorizontalContextMenu`. The component only ever sees the filtered array. If filtered count ≤ `max_visible_items`, no overflow occurs. This is correct — the user only sees items they can access, and overflow only triggers when the filtered set is still too large.

### 8.7 All Items Filtered Out

**Scenario**: User lacks permissions for every item in the active context group.

**Handling**: `HorizontalContextMenu` receives `[]`. Renders empty bar. The `setActiveContext()` method in `NavigationLayout` would have already fallen back to the first available context group. If all groups are empty, the layout still renders (empty navigation, content area remains).

### 8.8 Config Value is 0 or Negative

**Scenario**: `max_visible_items` is set to `0`.

**Handling**: Treat `max_visible_items ≤ 0` as "show all items" (no overflow). The `mount()` method clamps the value: `$this->maxVisibleItems = max(1, (int) ($maxVisibleItems ?? 6))`. A config value of `0` or `-1` means "unlimited" — all items are visible.

### 8.9 Duplicate active items (unlikely but defensive)

**Scenario**: Two items somehow resolve as "active" (e.g., route matching + model name matching both return true for different items).

**Handling**: The active-detection method returns the first match. In the promotion logic, if the active key is found in the overflow set, it's promoted once. If somehow two items are considered active, only the first one found is promoted.

---

## 9. Implementation Phases

### Phase 1 — HorizontalContextMenu Overflow (Recommended First)

**Goal**: Add overflow handling to the horizontal context menu, mirroring the proven TopNav pattern. Solves the immediate problem of 10+ items breaking layout.

**Steps**:

1. **Extract active-detection logic** from [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php:8-38) into a private method on `HorizontalContextMenu.php`: `isItemActive(array $item): bool`.

2. **Add overflow properties** to [`HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php):
   - `$maxVisibleItems`, `$promoteActiveItem`, `$overflowLabel`
   - Computed properties: `getVisibleItemsProperty()`, `getOverflowItemsProperty()`, `getHasOverflowProperty()`, `getActiveInOverflowProperty()`

3. **Implement `getVisibleItemsProperty()`** with active-item promotion logic (mirroring [`TopNav::getVisibleDesktopProperty()`](src/Http/Livewire/Layouts/Navs/TopNav.php:153)).

4. **Restructure the Blade template** to render visible items + "More" dropdown (as described in Section 5.1).

5. **Pass config values** from [`NavigationLayout`](src/Components/NavigationLayout.php) through [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) to the component.

6. **Update navigation configs** in the UI library core modules ([`Admin`](src/Core/Admin/Config/navigation.php), [`System`](src/Core/System/Config/navigation.php), [`Organization`](src/Core/Organization/Config/navigation.php)) to include `max_visible_items`.

7. **Test** with the consuming project's [`HR navigation config`](app/Modules/Hr/Config/navigation.php) — the "People" context with 10 items is the primary stress test.

### Phase 2 — Cross-Context Dropdowns (Future, Optional)

**Goal**: Allow modules to use a single-level navigation where all context groups appear as dropdowns in the horizontal bar, optionally hiding the TopNav context tabs.

**Steps**:

1. Add `showAllContexts` and `hideTopnavContexts` config keys.

2. Extend [`HorizontalContextMenu`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) to accept `$contextGroups`, `$contextItems`, `$activeContext`, and `$showAllContexts`.

3. Add a conditional branch in [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php): when `$showAllContexts` is true, iterate context groups as dropdown triggers.

4. Apply Phase 1 overflow within each dropdown (recursive "More" for large groups).

5. Conditionally hide context tabs in [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) when `hideTopnavContexts` is true.

6. Pass all context data through [`NavigationLayout`](src/Components/NavigationLayout.php) and [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php).

### Phase 3 — Responsive Threshold (Future Enhancement)

**Goal**: Dynamically adjust `maxVisibleItems` based on viewport width, so the bar always fills available space without overflowing.

**Approach**: Add a small Alpine.js `ResizeObserver` on the navbar element that calculates available width and adjusts visible count. This is optional because the static threshold handles the common case, and mobile falls back to the TopNav anyway.

---

## 10. Files Affected Summary

### Phase 1 (Immediate)

| File | Change | Impact |
|---|---|---|
| [`src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) | Add overflow split + active promotion logic | **Core change** |
| [`src/Resources/views/livewire/navs/horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) | Restructure from flat loop to visible + overflow | **Core change** |
| [`src/Components/NavigationLayout.php`](src/Components/NavigationLayout.php) | Pass new config values to view | Minor |
| [`src/Resources/views/components/layouts/navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) | Wire `:maxVisibleItems` to component | Minor |
| [`src/Core/Admin/Config/navigation.php`](src/Core/Admin/Config/navigation.php) | Add `max_visible_items` to layout config | Config only |
| [`src/Core/System/Config/navigation.php`](src/Core/System/Config/navigation.php) | Add `max_visible_items` to layout config | Config only |
| [`src/Core/Organization/Config/navigation.php`](src/Core/Organization/Config/navigation.php) | Add `max_visible_items` to layout config | Config only |
| (Consuming project) `app/Modules/Hr/Config/navigation.php` | Add `max_visible_items` after library update | Opt-in config |

### Phase 2 (Future)

| File | Change |
|---|---|
| [`src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) | Add `showAllContexts` mode |
| [`src/Resources/views/livewire/navs/horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) | Context group dropdown rendering |
| [`src/Resources/views/livewire/navs/top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) | Conditionally hide context tabs |
| [`src/Components/NavigationLayout.php`](src/Components/NavigationLayout.php) | Pass context groups/items to horizontal menu |
| [`src/Resources/views/components/layouts/navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) | Wire new attributes |

### NOT Affected

- [`TopNav.php`](src/Http/Livewire/Layouts/Navs/TopNav.php) — Phase 1 overflow is independent and untouched
- [`Sidebar.php`](src/Http/Livewire/Layouts/Navs/Sidebar.php) — No changes
- [`sidebar.blade.php`](src/Resources/views/livewire/navs/sidebar.blade.php) — No changes
- [`sidebar-section.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) — No changes
- [`sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) — No changes
- [`menu-renderer.blade.php`](src/Resources/views/livewire/navs/menu-renderer.blade.php) — No changes
- [`MenuRenderer.php`](src/Http/Livewire/Layouts/Navs/MenuRenderer.php) — No changes

---

*End of design recommendation.*
