# Horizontal Bar Section Grouping — UX Strategy Analysis

**Date:** 2026-08-12  
**Scope:** QuickerFaster UI Library — Navigation System  
**Status:** Analysis & Recommendation

---

## Table of Contents

1. [Current State Analysis](#1-current-state-analysis)
2. [Data Flow & Architecture Overview](#2-data-flow--architecture-overview)
3. [Strategy Comparison](#3-strategy-comparison)
4. [Recommendation](#4-recommendation)
5. [Implementation Outline](#5-implementation-outline)
6. [Files Affected](#6-files-affected)

---

## 1. Current State Analysis

### 1.1 How the Sidebar Renders Sections

The sidebar in [`sidebar.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/sidebar.blade.php) renders menu items through a multi-tier priority system:

| Priority | Source | Mechanism |
|----------|--------|-----------|
| 1 | `$items` from [`NavigationLayout.php`](../../Libraries/ui-library/src/Components/NavigationLayout.php) when `$activeContext` is set | Context-specific items wrapped in a **collapsible or static section header** |
| 2 | `$moduleSections` from `NavigationManager::getSections()` (Phase 4.5) | Per-module collapsible groups |
| 3 | `$sidebarSections` from `SidebarComposer` (Phase 4.5) | Config-driven collapsible groups |
| 4 | Flat `$items` (backward-compatible fallback) | No section grouping — direct item rendering |
| 5 | Debug message | Shown when all sources are empty |

The section rendering is handled by the [`sidebar-section.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) partial, which supports two modes:

**Collapsible Mode (`collapsible: true`, default):**
- A clickable header row with the section icon, label text, and a chevron indicator
- Alpine.js manages expand/collapse state via `x-data="{ expandedSections: {...}, toggle(), isExpanded() }"`
- The chevron rotates 90° when expanded via CSS transition
- Child items are rendered inside a `<ul>` with `x-show` and `x-transition` for animated reveal
- The section body uses `x-cloak` to prevent flash of hidden content
- Section headers have hover background change and active-state highlighting (`active-section` class)

**Static Label Mode (`collapsible: false`):**
- A non-interactive label row with icon and uppercase small text
- No chevron, no click handler
- Child items rendered directly underneath in a standard `<ul>`

**Icon Mode (sidebar collapsed to 60px):**
- Section headers collapse to icon-only circles (36×36px) with a small expand indicator dot
- Labels are hidden; tooltips provide the section name on hover
- Child items also become icon-only with centered layout

**Key CSS classes and transitions:**
- `.sidebar-container` — flex column, sticky positioning, height calc
- `.sidebar-full` (220px) / `.sidebar-icon` (60px) — width states
- `.sidebar-section-header` — cursor pointer, hover bg, border-radius, active highlight
- `.sidebar-section-chevron` — rotate transform transition (0.25s ease)
- `.sidebar-section-body` — max-height/opacity transition (0.3s)

An example from the consuming project's [`navigation.php`](../../app/Modules/Hr/Config/navigation.php) shows the `contexts` structure:

```
'people' => [
    ['key' => 'people_overview', 'label' => 'Overview', ...],
    ['key' => 'employee_group', 'label' => 'Employee Groups', ...],
    ['key' => 'tag', 'label' => 'Tags', ...],
    ['key' => 'employee', 'label' => 'Employees', ...],
    // ... 6 more items (10 total)
]
```

With `context_groups` defining the section metadata:

```php
'people' => [
    'label' => 'People',
    'icon' => 'fas fa-user-tie',
    'order' => 999,
    'route' => NULL,
    'url' => 'hr/dashboard-people-overview',
],
```

### 1.2 How the Horizontal Bar Currently Renders Items

The horizontal bar in [`horizontal-context-menu.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) renders a **completely flat list**:

```blade
<ul class="navbar-nav">
    @foreach ($items as $item)
        <li class="nav-item">
            <a href="{{ $itemUrl }}" class="nav-link {{ $isActive ? 'active fw-bold text-primary' : '' }}">
                <i class="{{ $item['icon'] }} me-1"></i>
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
```

**What it receives:** The [`NavigationLayout.php`](../../Libraries/ui-library/src/Components/NavigationLayout.php) passes `$contextItems[$activeContext] ?? []` — the flat array of child items for the currently selected context group. **No section metadata, no grouping hierarchy, no context group info** is passed to the horizontal bar.

**What it lacks:**
- No awareness of section/context_group labels
- No dropdown or expandable grouping mechanism
- No visual separators between logical item groups
- No overflow handling for contexts with many items (e.g., "People" has 10 items, "Payroll" has 10)
- No "More" or overflow menu for items that don't fit

### 1.3 The Gap Between Sidebar and Horizontal Modes

The gap is structural, not cosmetic. When a user switches from sidebar to horizontal mode:

| Aspect | Sidebar | Horizontal Bar | Gap |
|--------|---------|----------------|-----|
| Section grouping | Collapsible labeled sections (e.g., "People ▾") | None — all items flat | **Complete loss of organizational context** |
| Item count handling | Vertical scroll accommodates any count | Horizontal space is severely limited | **Items overflow or wrap unpredictably** |
| Section metadata | Icon, label, active state, chevron | Not used | **Metadata is discarded at the wiring level** |
| Navigation scope | Items within active context + shared header/footer | Only items within active context | **Shared items (header/footer) are also lost** |
| Expand/collapse state | Persisted in Alpine.js reactive state | N/A | **No equivalent interaction** |

Concrete example: When the "People" context is active, the sidebar shows an expandable "People" section with 10 child items elegantly listed. The horizontal bar attempts to render all 10 items as inline links — which simply does not fit in a ~1100px wide bar. Some items are pushed off-screen or wrap to a second line, breaking the visual design.

---

## 2. Data Flow & Architecture Overview

Understanding the data flow is critical for evaluating implementation strategies:

```
┌──────────────────────────────────────────────────────────────┐
│ navigation.php config                                        │
│ ├── context_groups: { label, icon, order, route }            │
│ └── contexts: { group_key => [ { key, label, icon, route } ]}│
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│ NavigationLayout.php (Blade Component)                       │
│ ├── loadNavigationConfig() — reads config, filters, sorts    │
│ ├── setActiveContext() — determines current context group    │
│ └── render() — passes data to sub-views                      │
└──────────────────────┬───────────────────────────────────────┘
                       │
          ┌────────────┼────────────────┐
          ▼            ▼                 ▼
   ┌──────────┐ ┌──────────────┐ ┌──────────────┐
   │ top-nav  │ │ sidebar      │ │ horizontal-  │
   │ (receives│ │ (receives    │ │ context-menu │
   │ context  │ │ $items,      │ │ (receives    │
   │ _groups) │ │ $activeCtx,  │ │ $items ONLY) │
   │          │ │ $ctxGroup*)  │ │              │
   └──────────┘ └──────────────┘ └──────────────┘
```

**Critical observation:** The top bar (`qf.top-nav`) already receives `$contextGroups` and renders them as tabs. The horizontal context menu is scoped to items *within* a single selected context. This means:

- The horizontal bar functions as a **second-level navigation** (items within a context group)
- The top bar functions as the **first-level navigation** (switching between context groups)
- The sidebar collapses both levels into one vertical panel (expandable sections = context groups)

When switching to horizontal mode, the two-level architecture (top bar tabs + horizontal item bar) is fundamentally different from the sidebar's single-panel approach. This architectural difference must be respected in any solution.

---

## 3. Strategy Comparison

### Strategy A: Dropdown Menus

Each section's item group becomes a Bootstrap dropdown in the horizontal bar. The context group label is the dropdown trigger.

```
[People ▾]  [Payroll ▾]  [Time ▾]  [Leave ▾]  [Organization ▾]  [More ▾]
```

**Currently, the horizontal bar only shows items from ONE context. This strategy would require changing it to show ALL context groups as dropdowns — effectively merging the top bar and horizontal bar responsibilities.**

Alternatively, if kept scoped to the active context: items within a single context don't have sub-groups, so there's nothing to dropdown. This strategy only makes sense if we broaden the horizontal bar's scope.

| Criteria | Assessment |
|----------|------------|
| **Discoverability** | ★★★★☆ — Dropdowns are a well-established web pattern. Users expect "menu → arrow → submenu." The section label as trigger is self-documenting. |
| **Space efficiency** | ★★★★★ — Excellent. 7 context groups → 7 dropdown triggers, each ~80-120px wide = ~560-840px total. Fits comfortably. |
| **Interaction pattern** | Click-to-open (Bootstrap default). Multi-level possible but adds complexity. Hover-to-open is faster but worse for accessibility and mobile. |
| **Accessibility** | ★★★★☆ — Bootstrap dropdowns have built-in ARIA (`aria-expanded`, `aria-haspopup`, `role="menu"`). Keyboard navigation via Tab/Enter/Escape. Screen readers announce dropdown state. Requires `aria-label` on triggers. |
| **Implementation complexity** | ★★★☆☆ — Moderate. Requires restructuring the horizontal bar to receive `$contextGroups` + `$contextItems` (not just flat `$items`). Each trigger opens a `.dropdown-menu` with child `<a>` items. Bootstrap 5 classes handle most styling. |
| **Visual consistency** | ★★★★☆ — Consistent with Bootstrap design language already used. Soft UI theme supports dropdowns natively. |
| **Responsive behavior** | ★★★★☆ — Dropdowns work well on desktop. On tablet/mobile (<768px), the horizontal bar itself may need to collapse into a hamburger. Dropdowns remain functional within whatever container. |

**Verdict:** Strong candidate. The main architectural change is expanding the horizontal bar's data contract from `$items` (flat) to `$contextGroups + $contextItems` (hierarchical).

---

### Strategy B: Flat List with Visual Separators

All items are flattened into the horizontal bar with thin vertical dividers or colored badges indicating which section each item belongs to.

```
[Overview] [Employees] [Teams] │ [Overview] [Pay Runs] [Payslips] │ [Overview] [Attendance] [Shifts]
         ── People ──                  ── Payroll ──                    ── Time ──
```

| Criteria | Assessment |
|----------|------------|
| **Discoverability** | ★★★☆☆ — Separators are subtle. Users must learn the color/divider convention. No explicit section label is visible (or labels consume precious horizontal space). |
| **Space efficiency** | ★☆☆☆☆ — Terrible for the actual data. "People" alone has 10 items. With separators, the bar would need ~1500px minimum. Items will wrap, overflow, or be hidden. |
| **Interaction pattern** | No interaction needed — purely visual. But scrolling a horizontal list is poor UX. |
| **Accessibility** | ★★★☆☆ — Separators can use `<li role="separator">`. But screen reader users get no grouping context unless explicit `aria-labelledby` or hidden section headers are added. |
| **Implementation complexity** | ★★☆☆☆ — Simplest to implement: iterate all `$contextItems`, insert separator `<li>` elements between groups. But the solution breaks at scale. |
| **Visual consistency** | ★★★☆☆ — Separators fit the Soft UI aesthetic but clutter the bar when item count is high. |
| **Responsive behavior** | ★☆☆☆☆ — The worst strategy for responsiveness. Narrow screens make the already-overflowing bar completely unusable. |

**Verdict:** Not viable for the actual data volumes in this project. Quick-HR's "People" context has 10 items and "Payroll" has 10 items. A flat list simply cannot fit.

---

### Strategy C: Icon-Only with Tooltips

Each item rendered as a compact icon button (similar to the sidebar's icon mode). Section grouping indicated by icon color or a subtle gap.

```
[👥] [🏢] [👥] [🪪] [📋] [📁] [💼] [🏷️] [📜] [👔]    — all "People" items as icons
```

| Criteria | Assessment |
|----------|------------|
| **Discoverability** | ★★☆☆☆ — Icons alone are ambiguous. Users must hover to see tooltips, which is slow and frustrating. Icons like `fa-chart-bar` are used for "Overview" across multiple sections — impossible to distinguish without text. |
| **Space efficiency** | ★★★★★ — Each icon is ~40px. 10 items = 400px. Fits easily. |
| **Interaction pattern** | Hover-to-discover (tooltip). No click needed for identification, but tooltips have a ~300-500ms delay. Power users who know their icons benefit; new users struggle. |
| **Accessibility** | ★★☆☆☆ — Tooltips (`title` attribute) are not reliably announced by all screen readers. `aria-label` would be needed on each link. Icon-only interfaces violate WCAG 2.5.3 (Label in Name) unless visible text is somehow provided. |
| **Implementation complexity** | ★★☆☆☆ — Trivial: hide text labels with CSS, show on hover. Similar to the existing icon-mode sidebar code. |
| **Visual consistency** | ★★★★☆ — Consistent with sidebar icon mode behavior. Users who collapse the sidebar already see this pattern. |
| **Responsive behavior** | ★★★★☆ — Icons shrink well and maintain usability on smaller screens. |

**Verdict:** Too severe a usability regression. Icon-only navigation is appropriate as an *option* (the sidebar's icon mode) but not as the *only* mode. It violates discoverability principles for complex applications with many similarly-iconed items.

---

### Strategy D: Mega Menu / Panel

Context group labels as top-level tabs. Clicking a tab reveals a dropdown panel (mega menu) with all child items arranged in a grid or categorized list.

```
┌──────────┬──────────┬──────────┬──────────┐
│ People ▾ │ Payroll  │  Time    │  Leave   │  ← tab row (always visible)
└──────────┴──────────┴──────────┴──────────┘
┌─────────────────────────────────────────────┐
│  📊 Overview     👥 Employees    🏢 Teams   │  ← mega panel (shown on click)
│  🏷️ Tags         📋 Documents    💼 Jobs   │
│  📁 Profiles     📜 Job History  🪪 Groups │
└─────────────────────────────────────────────┘
```

| Criteria | Assessment |
|----------|------------|
| **Discoverability** | ★★★★★ — Excellent. Section labels always visible as tabs. Panel reveals all items at once — no hunting through multiple dropdowns. The pattern is familiar from e-commerce and enterprise apps. |
| **Space efficiency** | ★★★★☆ — Tab row: ~7 tabs × ~100px = ~700px (fits). The mega panel overlays content, so it doesn't consume permanent horizontal space. |
| **Interaction pattern** | Click-to-open panel, click-away or Escape to close. Could support hover for power users (optional enhancement). Single-level within panel (no sub-sub-menus). |
| **Accessibility** | ★★★★☆ — Tabs + panel pattern is well-supported: `role="tablist"`, `role="tab"`, `aria-selected`, `role="tabpanel"`. Keyboard: Left/Right arrows between tabs, Enter/Space to open, Escape to close, Tab into panel items. |
| **Implementation complexity** | ★★★★☆ — High. Requires a custom panel component (not native Bootstrap dropdown). Must handle: click-outside detection, positioning (panel should align with tab), animation, keyboard trap within panel, z-index stacking with other overlays. |
| **Visual consistency** | ★★★★☆ — The Soft UI theme doesn't have a mega menu component, but the panel can be styled to match cards/dropdowns. Tab styling already exists in the top bar. |
| **Responsive behavior** | ★★★☆☆ — Mega panels work on desktop but are problematic on mobile. On small screens, the panel must either become a full-width drawer/accordion or the tab row must collapse. |

**Verdict:** Excellent UX but high implementation cost. The mega menu conceptually merges the top bar (context group tabs) with the horizontal bar (context items). Worth considering as a long-term goal but may be too heavy for the current need.

---

### Strategy E: Priority-Based Adaptive

Only high-priority items appear directly in the horizontal bar. Lower-priority items move to a "More" dropdown at the end.

```
[Overview] [Employees] [Profiles] [Teams] [Documents] [More ▾]
                                              ┌──────────────────┐
                                              │ 📋 Job History   │
                                              │ 🏷️ Tags          │
                                              │ 💼 Job Titles    │
                                              │ 🏢 Employee Grps │
                                              └──────────────────┘
```

Priority derived from: explicit `priority` field in nav config, `order` value, or usage analytics.

| Criteria | Assessment |
|----------|------------|
| **Discoverability** | ★★★☆☆ — "More" is a recognized pattern. However, users may not know which items are hidden in "More" without opening it. Items in "More" get less visibility. |
| **Space efficiency** | ★★★★☆ — Excellent. Only N top items visible, rest in overflow. Configurable N (e.g., 4-6 items + "More"). |
| **Interaction pattern** | Click "More" to reveal dropdown. One extra click for secondary items. Straightforward. |
| **Accessibility** | ★★★★☆ — "More" dropdown uses standard Bootstrap dropdown ARIA. Visible items are standard nav links. |
| **Implementation complexity** | ★★★☆☆ — Moderate. Requires: priority/order sorting, split logic (visible vs overflow), a "More" dropdown component. No changes to data contract needed — just processing of existing `$items`. |
| **Visual consistency** | ★★★★★ — Fits perfectly with the existing Bootstrap navbar pattern. The "More" dropdown matches the design language of other dropdowns. |
| **Responsive behavior** | ★★★★★ — Naturally adaptive: as screen shrinks, the "priority threshold" can dynamically increase, moving more items into "More." This is essentially responsive navigation. |

**Verdict:** Strong practical candidate. Simple to implement, solves the immediate overflow problem, degrades gracefully. However, it doesn't address the *section grouping* problem — it just hides items rather than organizing them.

---

### Strategy F: Hybrid Scrollable Tabs with Filtering

Section labels appear as horizontally scrollable tabs. Clicking a tab filters the bar to show only that section's items.

```
[◀] [People] [Payroll] [Time] [Leave] [Organization] [Policies] [▶]
──────────────────────────────────────────────────────────────
[Overview] [Employees] [Profiles] [Teams] [Documents] [...]
──────────────────────────────────────────────────────────────
```

| Criteria | Assessment |
|----------|------------|
| **Discoverability** | ★★★☆☆ — Tab filtering pattern is known from app stores and dashboards. However, horizontal scrolling of tabs is a UX anti-pattern (users must hunt for hidden tabs). |
| **Space efficiency** | ★★★★☆ — Tabs scroll horizontally, so unlimited sections fit. Item row only shows one section's items at a time. |
| **Interaction pattern** | Two-step: click tab to filter, then click item to navigate. Extra cognitive load compared to seeing all items. Scroll arrows needed for tab overflow. |
| **Accessibility** | ★★★☆☆ — Tablist with arrow key navigation works. But scrollable tab lists are difficult to make fully accessible (focus management when tabs scroll out of view). |
| **Implementation complexity** | ★★★★☆ — High. Requires: horizontal scroll container with overflow detection, scroll arrow buttons (shown/hidden dynamically), tab click handlers to filter, smooth scroll animation, responsive behavior. |
| **Visual consistency** | ★★★☆☆ — Horizontal scrolling tabs are not part of the Soft UI design system. Would need custom styling. |
| **Responsive behavior** | ★★★☆☆ — On small screens, the tab row and item row compete for space. Tabs may need to collapse into a dropdown themselves, creating a nested navigation problem. |

**Verdict:** The tab-filtering pattern is common in mobile apps but awkward on desktop. It adds a layer of interaction (selecting a tab) that the sidebar doesn't require. The scrolling tab row is particularly problematic for accessibility and discoverability.

---

### Strategy Comparison Matrix

| Strategy | Discover-ability | Space | Interaction | Accessibility | Complexity | Consistency | Responsive | **Overall** |
|----------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| A: Dropdowns | ★★★★☆ | ★★★★★ | ★★★★☆ | ★★★★☆ | ★★★☆☆ | ★★★★☆ | ★★★★☆ | **4.0** |
| B: Flat + Separators | ★★★☆☆ | ★☆☆☆☆ | ★★★☆☆ | ★★★☆☆ | ★★☆☆☆ | ★★★☆☆ | ★☆☆☆☆ | **2.1** |
| C: Icon-Only | ★★☆☆☆ | ★★★★★ | ★★☆☆☆ | ★★☆☆☆ | ★★☆☆☆ | ★★★★☆ | ★★★★☆ | **2.7** |
| D: Mega Menu | ★★★★★ | ★★★★☆ | ★★★★☆ | ★★★★☆ | ★★★★☆ | ★★★★☆ | ★★★☆☆ | **4.1** |
| E: Priority + More | ★★★☆☆ | ★★★★☆ | ★★★★☆ | ★★★★☆ | ★★★☆☆ | ★★★★★ | ★★★★★ | **4.0** |
| F: Scrollable Tabs | ★★★☆☆ | ★★★★☆ | ★★★☆☆ | ★★★☆☆ | ★★★★☆ | ★★★☆☆ | ★★★☆☆ | **3.0** |

---

## 4. Recommendation

### Recommended Approach: **Strategy E (Priority-Based Adaptive) + Strategy A (Dropdowns) Hybrid**

The optimal solution combines two strategies into a phased approach:

### Phase 1 (Immediate): Priority-Based Adaptive with "More" Dropdown

This solves the immediate problem — items overflowing the horizontal bar — with minimal architectural change.

**How it works:**

1. Items within the active context are sorted by `order` (ascending).
2. A configurable threshold (default: `max_visible_items: 5`) determines how many items display as inline links.
3. The remaining items go into a "More" dropdown at the end of the bar.
4. If the active item is in the overflow group, it's promoted to visible and a badge/indicator shows on "More."
5. The threshold adapts to viewport width via a CSS media query or a simple JS observer.

**Data contract:** No change needed. `HorizontalContextMenu` already receives `$items`. Only the rendering logic and perhaps a new `maxVisible` property are needed.

```
[Overview] [Employees] [Profiles] [Teams] [Documents]  [More ▾ (5)]
                                                         ┌──────────────────┐
                                                         │ 📋 Job History   │
                                                         │ 🏷️ Tags          │
                                                         │ 💼 Job Titles    │
                                                         │ 🏢 Employee Grps │
                                                         │ 📜 Current Jobs  │
                                                         └──────────────────┘
```

### Phase 2 (Future Enhancement): Cross-Context Dropdowns

Once Phase 1 stabilizes, extend the horizontal bar to optionally show dropdowns for *all* context groups (not just the active one). This is controlled by a config flag:

```php
'context_menu' => [
    'type' => 'horizontal',
    'show_all_contexts' => true,  // new flag
    'max_visible_items' => 5,
],
```

When enabled, the horizontal bar renders:

```
[People ▾]  [Payroll ▾]  [Time ▾]  [Leave ▾]  [Organization ▾]  [Policies ▾]  [Reports ▾]
```

Each dropdown contains the items for that context group, with the active context's dropdown trigger highlighted. This effectively merges the top bar and horizontal bar into a single unified navigation — closer to the mega menu (Strategy D) but using simpler dropdown components.

**Why not Strategy D (Mega Menu) now?**
- Higher implementation cost (custom panel component, positioning, animations, keyboard management)
- The two-phase approach delivers value faster
- Phase 1 can ship independently and solve the immediate overflow problem
- Phase 2 builds on Phase 1's foundation and can be informed by real usage data

**Why not Strategy C (Icon-Only)?**
- Too severe a usability regression
- Violates discoverability principles
- Icons alone cannot distinguish between identically-iconed items across sections

**Why not Strategy B (Flat + Separators)?**
- Mathematically cannot fit the data volumes (10+ items per context)
- Wrapping items to a second line breaks the horizontal bar design

### Design Principles Guiding This Recommendation

1. **Progressive enhancement** — Phase 1 works with the current data contract; Phase 2 expands it.
2. **Config-driven** — All thresholds and behaviors are configurable in `navigation.php`'s `layout` section.
3. **Respects the two-level architecture** — The top bar handles context switching; the horizontal bar handles within-context navigation. Phase 2 optionally merges them.
4. **Accessibility-first** — Bootstrap dropdowns provide solid ARIA support out of the box.
5. **Graceful degradation** — If JavaScript fails, all items remain visible (no `x-cloak` hiding essential navigation).

---

## 5. Implementation Outline

### Phase 1: Priority-Based Adaptive with "More" Dropdown

#### Step 1: Add configuration schema

In [`navigation.php`](app/Modules/Hr/Config/navigation.php) layout section, add new keys:

```php
'layout' => [
    'context_menu' => [
        'type' => 'sidebar',
        'position' => 'left',
        'allow_switch' => true,
        'default_type' => 'sidebar',
        // NEW: horizontal bar specific
        'max_visible_items' => 6,        // items to show before "More"
        'show_all_contexts' => false,    // Phase 2 flag
    ],
    // ...
],
```

#### Step 2: Update `HorizontalContextMenu.php` Livewire Component

Add new properties and processing logic:

- `public int $maxVisibleItems = 6;` — from config or mount parameter
- `public array $visibleItems = [];` — first N items
- `public array $overflowItems = [];` — remaining items
- `public bool $hasOverflow = false;`
- `public bool $activeInOverflow = false;`

In `mount()`, split `$items` into visible and overflow groups. If the active item is in overflow, promote it to visible and add a visual indicator.

The `NavigationLayout.php` passes the config value to the component:

```php
<livewire:qf.horizontal-context-menu 
    :items="$contextItems[$activeContext] ?? []"
    :maxVisibleItems="$layoutConfig['context_menu']['max_visible_items'] ?? 6"
    ...
/>
```

#### Step 3: Update `horizontal-context-menu.blade.php`

Restructure the template:

```blade
<ul class="navbar-nav me-auto">
    {{-- Visible items --}}
    @foreach ($visibleItems as $item)
        <li class="nav-item">
            <a href="..." class="nav-link {{ $isActive ? 'active' : '' }}">
                <i class="{{ $item['icon'] }} me-1"></i> {{ $item['label'] }}
            </a>
        </li>
    @endforeach

    {{-- "More" dropdown (only if overflow exists) --}}
    @if ($hasOverflow)
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle {{ $activeInOverflow ? 'active' : '' }}"
               href="#" role="button" data-bs-toggle="dropdown"
               aria-expanded="false">
                More
                @if ($activeInOverflow)
                    <span class="badge bg-primary ms-1">●</span>
                @endif
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach ($overflowItems as $item)
                    <li>
                        <a href="..." class="dropdown-item {{ $isActive ? 'active' : '' }}">
                            <i class="{{ $item['icon'] }} me-2"></i> {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
    @endif
</ul>
```

#### Step 4: Add responsive threshold adjustment (optional JS)

A small Alpine.js or vanilla JS snippet that observes the navbar width and dynamically adjusts `maxVisibleItems`:

```js
// Conceptual: not full implementation
const observer = new ResizeObserver(entries => {
    const width = entries[0].contentRect.width;
    // Adjust visible count based on available width
    // Each item ~120px, "More" ~80px
    const maxFit = Math.floor((width - 200) / 120);
    // Dispatch to Livewire or update Alpine state
});
```

This is optional for Phase 1 — the static threshold handles the common case.

### Phase 2: Cross-Context Dropdowns

#### Step 1: Config flag

Set `show_all_contexts: true` in the layout config.

#### Step 2: Pass context groups to HorizontalContextMenu

In [`NavigationLayout.php`](../../Libraries/ui-library/src/Components/NavigationLayout.php) `render()`:

```php
<livewire:qf.horizontal-context-menu
    :contextGroups="$contextGroups"
    :contextItems="$contextItems"
    :activeContext="$activeContext"
    ...
/>
```

#### Step 3: Render context group dropdowns

Instead of a flat item list, iterate `$contextGroups` and render a dropdown for each:

```blade
@foreach ($contextGroups as $groupKey => $group)
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle {{ $groupKey === $activeContext ? 'active' : '' }}"
           href="#" data-bs-toggle="dropdown">
            <i class="{{ $group['icon'] }} me-1"></i> {{ $group['label'] }}
        </a>
        <ul class="dropdown-menu">
            @foreach ($contextItems[$groupKey] ?? [] as $item)
                <li>
                    <a href="..." class="dropdown-item {{ $isActive ? 'active' : '' }}">
                        <i class="{{ $item['icon'] }} me-2"></i> {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
            {{-- Apply max_visible_items + "More" within each dropdown if needed --}}
        </ul>
    </li>
@endforeach
```

#### Step 4: Handle dropdown overflow

If even the dropdowns for a context group have too many items, apply the Phase 1 "More" pattern recursively within each dropdown.

---

## 6. Files Affected

### Phase 1 (Priority-Based Adaptive)

| File | Change |
|------|--------|
| [`src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php`](../../Libraries/ui-library/src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) | Add `$maxVisibleItems`, `$visibleItems`, `$overflowItems`, `$hasOverflow`, `$activeInOverflow` properties; split logic in `mount()` |
| [`src/Resources/views/livewire/navs/horizontal-context-menu.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) | Restructure from flat loop to visible + overflow pattern; add "More" dropdown markup |
| [`src/Components/NavigationLayout.php`](../../Libraries/ui-library/src/Components/NavigationLayout.php) | Pass `maxVisibleItems` config to `horizontal-context-menu` Livewire component (line ~144) |
| [`src/Resources/views/components/layouts/navigation-layout.blade.php`](../../Libraries/ui-library/src/Resources/views/components/layouts/navigation-layout.blade.php) | Wire `:maxVisibleItems` attribute to the `<livewire:qf.horizontal-context-menu>` tag (line ~144) |
| `app/Modules/Hr/Config/navigation.php` (consuming project) | Add `max_visible_items` to layout config (example/opt-in) |

### Phase 2 (Cross-Context Dropdowns)

| File | Change |
|------|--------|
| [`src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php`](../../Libraries/ui-library/src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) | Add `$contextGroups`, `$contextItems`, `$activeContext`, `$showAllContexts` properties; extend `mount()` |
| [`src/Resources/views/livewire/navs/horizontal-context-menu.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/horizontal-context-menu.blade.php) | Add `$showAllContexts` conditional branch: if true, render context group dropdowns instead of flat items |
| [`src/Components/NavigationLayout.php`](../../Libraries/ui-library/src/Components/NavigationLayout.php) | Pass `$contextGroups`, `$contextItems`, `$activeContext` to horizontal menu component |
| [`src/Resources/views/components/layouts/navigation-layout.blade.php`](../../Libraries/ui-library/src/Resources/views/components/layouts/navigation-layout.blade.php) | Wire new attributes to `<livewire:qf.horizontal-context-menu>` |
| `app/Modules/Hr/Config/navigation.php` (consuming project) | Add `show_all_contexts` flag |

### Files NOT Affected

- [`sidebar.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/sidebar.blade.php) — No changes needed; sidebar behavior is independent
- [`sidebar-section.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) — Unchanged
- [`sidebar-item.blade.php`](../../Libraries/ui-library/src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) — Unchanged (could be extracted into a shared partial for both sidebar and dropdown items in Phase 2, but not required)
- [`Sidebar.php`](../../Libraries/ui-library/src/Http/Livewire/Layouts/Navs/Sidebar.php) — Unchanged

---

## Appendix: Visualization of Recommended Solution

### Phase 1 Result (Single Context, Adaptive)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🏢 QH  │  People  Payroll  Time  Leave  Organization  ...  │  🔔  👤  ⚙️  │  ← Top Bar
└──────────────────────────────────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────────────────────────────────┐
│  📊 Overview  │  👥 Employees  │  🪪 Profiles  │  👥 Teams  │  📁 Documents  │ [More ▾] │
└──────────────────────────────────────────────────────────────────────────────┘
                                                                    ┌──────────────────┐
                                                                    │ 📋 Job History   │
                                                                    │ 🏷️ Tags          │
                                                                    │ 💼 Job Titles    │
                                                                    │ 🏢 Emp. Groups   │
                                                                    │ 📜 Current Jobs  │
                                                                    └──────────────────┘
```

### Phase 2 Result (All Contexts, Dropdowns)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🏢 QH  │  [People ▾]  [Payroll ▾]  [Time ▾]  [Leave ▾]  [Org ▾]  ...  │ 🔔 👤 ⚙️ │
└──────────────────────────────────────────────────────────────────────────────┘
             ┌─────────────────────┐
             │ 👥 Employees        │
             │ 🪪 Profiles         │
             │ 👥 Teams            │
             │ 📁 Documents        │
             │ ... More →          │
             └─────────────────────┘
```

(The top bar's context group tabs become redundant in Phase 2 and could optionally be hidden when `show_all_contexts` is enabled, reducing visual duplication.)

---

*End of analysis.*
