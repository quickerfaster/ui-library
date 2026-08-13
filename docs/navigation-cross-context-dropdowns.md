# Navigation Cross-Context Dropdowns (Phases 1 & 2)

> **Date**: 2026-08-13
> **Status**: ✅ Both Phases Implemented + Nav Polish Complete
> **Phase 1**: Overflow "More" dropdown for TopNav + HorizontalContextMenu (`max_visible_items: 6`, active item promotion)
> **Phase 2**: Cross-context dropdowns — all context groups as Bootstrap dropdown triggers
> **Recent Additions**: User profile dropdown menu, notification bell icon, role-based module switcher config, role-based background jobs config
> **Design Doc**: [`plans/horizontal-bar-group-design.md`](plans/horizontal-bar-group-design.md)
> **Applies To**: [`HorizontalContextMenu`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php) + [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Configuration](#2-configuration)
3. [Usage Examples](#3-usage-examples)
4. [Behavior Description](#4-behavior-description)
5. [Accessibility Notes](#5-accessibility-notes)
6. [Edge Cases](#6-edge-cases)
7. [Migration Guide](#7-migration-guide)

---

## 1. Overview

Phase 2 adds an optional **cross-context dropdown** mode to the horizontal navigation bar. When a module sets `show_all_contexts: true` in its [`navigation.php`](src/Core/Admin/Config/navigation.php) config under `layout.context_menu`, every context group becomes a Bootstrap dropdown trigger in the horizontal bar.

### When to Use

| Scenario | Recommendation |
|---|---|
| Module has 3+ context groups, each with ≤5 items | Enable `show_all_contexts` — reduces clicks, all navigation visible at once |
| Module has 1–2 context groups | Keep default (`show_all_contexts: false`) — two-level TopNav + context bar is clearer |
| Module has many items per context (10+) | Enable `show_all_contexts` — Phase 1 overflow handles large groups within each dropdown |
| You want to remove the TopNav context tabs | Enable both `show_all_contexts` and `hide_topnav_contexts` |

### What It Changes

**Before (Phase 1 only)**:
```
TopNav context tabs (People | Payroll | Time | ...)
    ↓ user clicks a tab → activeContext changes
HorizontalContextMenu: flat inline links for active context + "More" overflow
```

**After (Phase 2 enabled)**:
```
TopNav context tabs hidden (if hide_topnav_contexts)
HorizontalContextMenu: [👥 People ▾] [💰 Payroll ▾] [⏰ Time ▾] [🏖️ Leave ▾] ...
                         ↓ click to open
                         Employees, Profiles, Teams, More → (sub-dropdown)
```

---

## 2. Configuration

All Phase 2 config keys live under `layout.context_menu` in each module's [`navigation.php`](src/Core/Admin/Config/navigation.php):

| Key | Type | Default | Description |
|---|---|---|---|
| `show_all_contexts` | `bool` | `false` | When `true`, renders all context groups as dropdown triggers instead of a flat item list. Each dropdown contains that group's child items with Phase 1 overflow applied internally. |
| `hide_topnav_contexts` | `bool` | `false` | When `true` **and** `show_all_contexts` is also `true`, hides the context group tabs section in TopNav (both desktop visible/overflow and mobile scrollable tabs). Other TopNav elements remain visible. Ignored when `show_all_contexts` is `false`. |

### Config Structure

```php
// In any module's Config/navigation.php
'layout' => [
    'context_menu' => [
        'type' => 'sidebar',             // or 'horizontal'
        'position' => 'left',
        'allow_switch' => true,

        // Phase 1
        'max_visible_items' => 6,
        'promote_active_item' => true,

        // Phase 2 — Cross-Context Dropdowns
        'show_all_contexts' => false,    // ← enable this for Phase 2
        'hide_topnav_contexts' => false, // ← hide TopNav context tabs when true
    ],
],
```

### Resolution Order

These keys are read in [`NavigationLayout::__construct()`](src/Components/NavigationLayout.php:108-109):

1. Module-specific `navigation.php` `layout.context_menu` config
2. Fall back to default `false` if key absent

---

## 3. Usage Examples

### 3.1 Basic Enable

Simplest setup — enable cross-context dropdowns while keeping TopNav context tabs visible:

```php
// app/Modules/Hr/Config/navigation.php
'layout' => [
    'context_menu' => [
        'type' => 'horizontal',
        'position' => 'left',
        'allow_switch' => true,
        'max_visible_items' => 6,
        'show_all_contexts' => true,   // ← Phase 2 enabled
    ],
],
```

**Result**: TopNav shows "People | Payroll | Time | ..." tabs. HorizontalContextMenu shows dropdown triggers for each group. Both work together — users can use either.

### 3.2 Full Mode (Both Flags)

Remove TopNav context tabs entirely — horizontal bar is the sole navigation:

```php
// app/Modules/Hr/Config/navigation.php
'layout' => [
    'context_menu' => [
        'type' => 'horizontal',
        'position' => 'left',
        'allow_switch' => false,       // optional: no sidebar switch needed
        'max_visible_items' => 6,
        'show_all_contexts' => true,
        'hide_topnav_contexts' => true, // ← hide TopNav tabs
    ],
],
```

**Result**: TopNav shows only module switcher, company switcher, and profile. HorizontalContextMenu is the only navigation — each context group is a dropdown.

### 3.3 With Custom `max_visible_items` Per Context Group

The `max_visible_items` setting applies uniformly. Phase 1 overflow within each dropdown uses this threshold:

```php
'layout' => [
    'context_menu' => [
        'type' => 'horizontal',
        'position' => 'left',
        'allow_switch' => true,
        'max_visible_items' => 4,      // show 4 items per dropdown, rest in "More"
        'show_all_contexts' => true,
    ],
],
```

**Result**: Each context group dropdown shows up to 4 visible items + "More" sub-dropdown for overflow. Active item promotion works — if the active page is in overflow, it's promoted to visible, bumping one item into overflow.

---

## 4. Behavior Description

### 4.1 How Context Groups Become Dropdowns

In [`horizontal-context-menu.blade.php`](src/Resources/views/livewire/navs/horizontal-context-menu.blade.php), when `$showAllContexts` is `true`:

```blade
@if ($showAllContexts)
    @foreach ($contextGroups as $contextKey => $group)
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle {{ $isActiveContext ? 'active fw-bold text-primary' : '' }}"
               href="#" role="button" data-bs-toggle="dropdown"
               aria-expanded="false" aria-haspopup="true">
                <i class="{{ $groupIcon }} me-1"></i>
                {{ $groupLabel }}
            </a>
            <ul class="dropdown-menu">
                {{-- Visible items --}}
                {{-- Overflow "More" sub-dropdown (if needed) --}}
            </ul>
        </li>
    @endforeach
@else
    {{-- Phase 1 layout (unchanged) --}}
@endif
```

### 4.2 Phase 1 Overflow Within Each Dropdown

Each context group dropdown independently applies Phase 1 overflow logic via two methods on [`HorizontalContextMenu`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php):

- [`getVisibleItemsForContext(string $contextKey)`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php:178) — Returns items shown directly in the dropdown menu
- [`getOverflowItemsForContext(string $contextKey)`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php:210) — Returns items placed in the "More" sub-dropdown

Active item promotion works per-context: if the active page is in a group's overflow, it is promoted to the visible list.

Overflow items appear in a nested Bootstrap dropdown (using `.dropstart` to open leftward, avoiding viewport edge clipping):

```blade
<li class="dropdown-submenu dropstart">
    <a class="dropdown-item dropdown-toggle" href="#"
       data-bs-toggle="dropdown">
        More
    </a>
    <ul class="dropdown-menu">
        {{-- Overflow items --}}
    </ul>
</li>
```

### 4.3 Active Context Highlighting

- The **active context group's dropdown trigger** receives `.active.fw-bold.text-primary` class
- Within a dropdown, the **active item** receives `.active.fw-bold.text-primary` class
- If the active item is in the **overflow "More"** sub-dropdown, the "More" trigger also gets the active styling + a dot badge indicator

### 4.4 "Switch to Sidebar"

The "Switch to Sidebar" button (visible when `allow_switch` is `true`) works identically in Phase 2 mode. It calls [`switchToSidebar()`](src/Http/Livewire/Layouts/Navs/HorizontalContextMenu.php:265) which sets `session(['context_menu_type' => 'sidebar'])` and triggers a page reload. The sidebar is completely independent — Phase 2 only affects horizontal mode.

---

## 5. Accessibility Notes

### 5.1 ARIA Attributes

Each context group dropdown trigger includes:

```html
<a class="nav-link dropdown-toggle"
   href="#"
   role="button"
   data-bs-toggle="dropdown"
   aria-expanded="false"
   aria-haspopup="true">
    People
</a>
```

Dropdown menus use:

```html
<ul class="dropdown-menu"
    role="menu"
    aria-label="{group label} navigation items">
    <li role="none">
        <a href="..." class="dropdown-item" role="menuitem" wire:navigate>
            <i class="fa fa-users me-2" aria-hidden="true"></i>
            <span>Employees</span>
        </a>
    </li>
</ul>
```

### 5.2 Keyboard Navigation

Standard Bootstrap 5 dropdown keyboard behavior applies:

| Key | Action |
|---|---|
| **Tab** | Focus moves through dropdown triggers sequentially |
| **Enter / Space** | Open focused dropdown |
| **↓ / ↑** | Navigate within open dropdown |
| **Escape** | Close dropdown, return focus to trigger |
| **Tab** (inside dropdown) | Close dropdown, move to next trigger |

### 5.3 Screen Reader Behavior

- Each dropdown trigger announces its state via `aria-expanded`
- Dropdown menus are labeled by `aria-label` with the context group name
- Icons use `aria-hidden="true"` — they are decorative only
- Active state is conveyed through the visual `.active` class (screen readers receive the link text as normal)

---

## 6. Edge Cases

### 6.1 Empty Context Group

**Scenario**: A context group exists in `context_groups` but has no items in `contexts` (or all items were filtered out by permissions).

**Handling**: The dropdown renders with an empty `<ul class="dropdown-menu">`. The trigger is still clickable but shows nothing. This is acceptable — the trigger still communicates the group's presence.

### 6.2 Single-Item Group

**Scenario**: A context group has exactly one child item.

**Handling**: The dropdown renders with a single item. No "More" sub-dropdown appears (since `max_visible_items ≥ 1`). The user sees a dropdown with one clickable link. For better UX, consider setting the context group's `route` to the single item's route so the TopNav tab also navigates directly.

### 6.3 All Items Hidden by Permissions

**Scenario**: After workspace/permission filtering, a context group has zero visible items.

**Handling**: The dropdown trigger still renders (group is not hidden). The dropdown menu is empty. This preserves the group's discoverability for users who might gain permissions later. If a group should be hidden entirely, use the `WorkspaceFilter` contract.

### 6.4 Large Number of Context Groups (5+)

**Scenario**: A module has 8 context groups, all rendered as dropdown triggers.

**Handling**: All 8 dropdowns render inline in the horizontal bar. If this overflows the viewport, the navbar's native Bootstrap wrapping handles it (items wrap to the next line). The bar height increases — this is acceptable since `show_all_contexts` is opt-in.

### 6.5 Mobile (Viewport < 768px)

**Handling**: The `HorizontalContextMenu` is wrapped in `d-none d-md-block` in [`navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php:134). On mobile, it is hidden entirely. If `hide_topnav_contexts` is true, the TopNav mobile scrollable tabs are also hidden. Navigation falls through to the BottomBar on mobile.

---

## 7. Migration Guide

### Enabling Phase 2 for an Existing Module

**Step 1**: Verify your module is using horizontal context menu mode (`type: 'horizontal'` in `layout.context_menu`).

**Step 2**: Add `show_all_contexts: true` to your module's navigation config:

```php
// Before
'layout' => [
    'context_menu' => [
        'type' => 'horizontal',
        'position' => 'left',
        'allow_switch' => true,
        'max_visible_items' => 6,
    ],
],

// After
'layout' => [
    'context_menu' => [
        'type' => 'horizontal',
        'position' => 'left',
        'allow_switch' => true,
        'max_visible_items' => 6,
        'show_all_contexts' => true,    // ← added
    ],
],
```

**Step 3** (Optional): Hide TopNav context tabs to avoid visual duplication:

```php
'show_all_contexts' => true,
'hide_topnav_contexts' => true,  // ← added
```

**Step 4**: Test:
- Verify each context group appears as a dropdown trigger
- Verify active context highlighting works
- Verify Phase 1 overflow within each dropdown works (if any group has > `max_visible_items`)
- Verify "Switch to Sidebar" button still functions
- Verify mobile fallback still works

**Step 5**: If satisfied, consider setting `allow_switch: false` to prevent users from switching to sidebar mode (since cross-context dropdowns are the intended experience).

### Disabling Phase 2

Set `show_all_contexts: false` (or remove the key entirely). The component immediately reverts to Phase 1 behavior — no data loss, no migration needed.

---

## 8. Changelog

| Date | Change |
|---|---|
| **2026-08-13** | User profile dropdown menu: config-driven `user_menu` with "My Profile", "My Account", "My Preferences" rendered from user avatar in TopNav |
| **2026-08-13** | Notification bell icon: unread count badge with Livewire polling, configurable via `notifications.enabled`, `notifications.polling_interval`, `notifications.max_display` |
| **2026-08-13** | Module switcher role-based config: `roles` array per module entry filters which modules appear in the switcher dropdown |
| **2026-08-13** | Background jobs role-based config: `roles` and `visible_statuses` keys control dashboard widget visibility |
| **2026-08-13** | Company dropdown behavior fix: restored correct hide/show logic; fixed company list to show companies (not users) |
| **2026-08-12** | Phase 2 implemented: `show_all_contexts` + `hide_topnav_contexts` config keys, all context groups as dropdown triggers, per-group Phase 1 overflow |
| **2026-08-12** | Phase 1 verified: TopNav overflow with `max_visible_items: 6`, active item promotion, mobile hamburger menu |
| **2026-08-12** | HorizontalContextMenu overflow: `getVisibleItemsForContext()` and `getOverflowItemsForContext()` per-group splitting |
| **2026-08-12** | Section header rendering in horizontal context menu overflow dropdowns |
| **2026-08-12** | Config-doc alignment: 5 missing keys added to nav config stubs (`max_visible_items`, `promote_active_item`, `show_all_contexts`, `hide_topnav_contexts`, `breadcrumb.enabled`) |

---

*End of document.*
