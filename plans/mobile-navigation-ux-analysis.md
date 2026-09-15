# Mobile Navigation UX/UI Analysis — QuickerFaster UI Library

> **Date**: 2026-09-15
> **Status**: Proposal — awaiting review
> **Related**: [`docs/library/06-navigation-system.md`](../docs/library/06-navigation-system.md), [`src/Http/Livewire/Layouts/Navs/TopNav.php`](../src/Http/Livewire/Layouts/Navs/TopNav.php), [`src/Http/Livewire/Layouts/Navs/BottomBar.php`](../src/Http/Livewire/Layouts/Navs/BottomBar.php), [`src/Http/Livewire/Layouts/Navs/Sidebar.php`](../src/Http/Livewire/Layouts/Navs/Sidebar.php)

---

## 1. Current State Analysis

### 1.1 Desktop Pattern (Works Well)

```
┌──────────────────────────────────────────────────────────────┐
│  TopNav: [Dashboard] [My Portal] [People] [Manage] [More…]   │  ← context group tabs
│           [🔔] [⚡] [👤]                                       │  ← notifications, quick actions, profile
├──────────┬───────────────────────────────────────────────────┤
│ Sidebar  │  Content Area                                      │
│ (context │                                                    │
│  items)  │                                                    │
│          │                                                    │
├──────────┴───────────────────────────────────────────────────┤
│  BottomBar: hidden on desktop                                 │
└──────────────────────────────────────────────────────────────┘
```

- TopNav shows context group tabs → clicking one switches the sidebar to that group's items
- Sidebar shows sub-items for the active context group
- Clean separation: context selection (top) → sub-navigation (side)

### 1.2 Current Mobile Implementation

```
┌──────────────────────────────────────────────────────────────┐
│  TopNav: [Module▼] [←scrollable context pills→] [🔔][⚡][👤]  │  ← cramped
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  Content Area                                                  │
│  (sidebar hidden — d-none d-md-flex)                          │
│                                                                │
├──────────────────────────────────────────────────────────────┤
│  BottomBar: [Dashboard] [People] [Manage] [More…]             │  ← duplicates sidebar items
└──────────────────────────────────────────────────────────────┘
```

### 1.3 Problems Identified

| # | Problem | Severity | Impact |
|---|---------|----------|--------|
| **P1** | **BottomBar duplicates sidebar items** — [`navigation-layout.blade.php:200`](../src/Resources/views/components/layouts/navigation-layout.blade.php:200) passes `$contextItems[$activeContext]` to both Sidebar and BottomBar. Same data, two render targets. | 🔴 HIGH | Maintenance burden, inconsistent UX when one is updated but not the other |
| **P2** | **Context group tabs are hidden in a scrollable strip** — [`top-nav.blade.php:180`](../src/Resources/views/livewire/navs/top-nav.blade.php:180) renders context groups as small pill buttons in a horizontally scrollable `<div>`. Users must scroll horizontally to discover all groups. | 🔴 HIGH | Poor discoverability — users may not realize there are more tabs beyond the visible ones |
| **P3** | **No contextual sub-item reveal** — selecting a context group on mobile does NOT reveal its sub-items in a mobile-friendly way. The BottomBar shows ALL items regardless of which context pill is active. | 🔴 HIGH | Breaks the desktop mental model — users expect context switching to change what they see |
| **P4** | **Dual nav bars consume vertical space** — TopNav + BottomBar = ~110px of chrome on a ~700px screen (~16% of viewport). | 🟡 MEDIUM | Less content visible, especially problematic on smaller phones |
| **P5** | **Sidebar is completely hidden** — `d-none d-md-flex` on [`sidebar.blade.php:2`](../src/Resources/views/livewire/navs/sidebar.blade.php:2). No drawer, no offcanvas, no slide-in. The sidebar's contextual awareness is lost entirely. | 🟡 MEDIUM | Users lose the spatial model of "I'm in the People section" |
| **P6** | **Module switcher is a dropdown** — requires two taps (open dropdown → select module) and hides available modules. | 🟢 LOW | Acceptable for infrequent use, but could be more discoverable |

---

## 2. Mobile UX Pattern Research

### 2.1 Established Patterns for Multi-Module/Enterprise Apps

| Pattern | Examples | Best For | Drawbacks |
|---------|----------|----------|-----------|
| **Bottom Navigation Bar** (Material Design) | Google Admin, Shopify, Notion | 3-5 top-level destinations, high discoverability | Doesn't scale beyond 5 items; no sub-navigation |
| **Navigation Drawer** (Material Design) | Gmail, Google Drive, Slack | Many destinations, hierarchical nav, preserves screen space | Hidden by default — lower discoverability; requires hamburger tap |
| **Top App Bar + Bottom Bar hybrid** | GitHub Mobile, Jira | Primary actions top, navigation bottom | Can feel heavy with two bars |
| **Tab Bar + Sheet** (iOS HIG) | Apple Settings, App Store | Top-level tabs with contextual content below | Limited to flat or shallow hierarchies |
| **Collapsible Sidebar / Drawer** | Discord, Telegram | Rich hierarchy, always-visible on tablet, drawer on phone | Implementation complexity for responsive breakpoints |

### 2.2 Key UX Principles Applied

1. **One-handed reach** (thumb zone): Primary navigation should be in the bottom 1/3 of the screen. Secondary/overflow can be top or drawer.
2. **Progressive disclosure**: Show top-level destinations first; reveal sub-items on demand.
3. **Contextual awareness**: The user should always know "where they are" in the navigation hierarchy.
4. **Consistency with desktop**: The mental model should transfer — context groups select a "section," sub-items navigate within it.
5. **Discoverability**: Frequently-used destinations should be visible without interaction; less-used ones can be behind a menu.

### 2.3 What Works for This Class of Application

Enterprise HR/payroll apps have:
- **Multiple modules** (HR, Payroll, Leave, Attendance) — infrequent switching
- **Context groups per module** (Dashboard, People, Manage, Reports) — frequent switching
- **Sub-items per context** (Employee List, Profiles, Teams) — frequent navigation
- **Role-based visibility** — different users see different items

This maps well to: **Bottom Tab Bar (context groups) + Drawer/Sheet (sub-items)**.

---

## 3. Proposed Mobile Architecture

### 3.1 Core Concept: "Bottom Tabs + Slide-Up Context Sheet"

The proposal replaces the current dual-bar approach with a single **Bottom Tab Bar** that serves as the primary navigation surface, augmented by a **slide-up context sheet** for sub-item navigation.

```
┌──────────────────────────────────────────────────────────────┐
│  Top App Bar (compact):                                       │
│  [🍔 Module Drawer]  Page Title  [🔔] [⚡] [👤]               │  ← 48px, module switcher + actions
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  Content Area                                                  │
│  (full height — no sidebar, no second bar)                    │
│                                                                │
│                                                                │
├──────────────────────────────────────────────────────────────┤
│  Bottom Tab Bar:                                               │
│  [Dashboard] [My Portal] [People] [Manage] [More…]            │  ← 56px, context group tabs
└──────────────────────────────────────────────────────────────┘
```

**Key change**: The context group tabs move FROM the TopNav scrollable strip TO the Bottom Tab Bar. The BottomBar no longer duplicates sidebar items — it shows context groups instead.

### 3.2 Interaction Flow

```
User taps [People] in Bottom Tab Bar
  │
  ├─► Content navigates to People's default URL (e.g., /hr/dashboard-people-overview)
  │
  └─► A "sub-items" indicator (chevron/arrow) appears on the active tab
       │
       ├─► User taps the active tab AGAIN (or swipes up)
       │     │
       │     └─► Slide-up sheet reveals People's sub-items:
       │          ┌──────────────────────────────────────┐
       │          │  👥 People                           │
       │          │  ─────────────────────────────────── │
       │          │  📋 Overview                         │
       │          │  👤 Employee List                    │
       │          │  📄 Profiles                         │
       │          │  💼 Current Jobs                     │
       │          │  👥 Teams                            │
       │          │  📊 Employee Groups                  │
       │          └──────────────────────────────────────┘
       │
       └─► User taps a sub-item → sheet closes, content navigates
```

### 3.3 Component Breakdown

#### Top App Bar (48px, always visible)

| Element | Position | Behavior |
|---------|----------|----------|
| **Navigation Hub** (🍔 icon) | Left | Opens a half-sheet with two sections: **Switch Module** (all accessible modules, current highlighted) and **Switch Company** (all companies, current highlighted). Both sections are role-gated — they only appear when the user has access to multiple modules or companies. |
| **Page Title** | Center | Dynamically updates based on current context + sub-item |
| **Company Indicator** (🏢 icon) | Right (before profile) | Visible only when user has multi-company access. Shows current company name (truncated). Tapping opens the Navigation Hub scrolled to the company section. |
| **Notification Bell** (🔔) | Right | Same as desktop — badge count, opens drawer |
| **Quick Actions** (⚡) | Right | Same as desktop — command palette or dropdown |
| **Profile** (👤) | Right | Same as desktop — dropdown with profile, settings, logout |

#### Bottom Tab Bar (56px, always visible)

| Element | Position | Behavior |
|---------|----------|----------|
| **Context Group Tabs** | Horizontal row, 4-5 visible | Each tab = one context group from `navigation.php`. Icon + short label. Active tab is highlighted with a chevron (▲) indicating sub-items are available. |
| **"More" tab** (⋯) | Last position (if > 4-5 groups) | Opens the **Overflow Sheet** — an expandable accordion listing all context groups. See §3.6 for full behavior. |
| **Active tab double-tap** | Any active tab | Opens the **Context Sheet** showing that group's sub-items |

**Tab configuration** (from `navigation.php` context group):
```php
'People' => [
    'label'      => 'People',
    'icon'       => 'fas fa-users',
    'mobile_tab' => true,         // NEW: show in bottom tab bar (default: true)
    'url'        => 'hr/dashboard-people-overview',
    'items'      => [ /* sub-items for the context sheet */ ],
],
```

#### Context Sheet (slide-up, triggered by active tab double-tap or swipe)

| Element | Behavior |
|---------|----------|
| **Header** | Context group label + icon. Drag handle for dismissal. |
| **Sub-item list** | Vertical list of items from the context group's `items` array. Each row: icon + label. Active item highlighted. |
| **Dismissal** | Swipe down, tap backdrop, or tap a sub-item (auto-dismiss on navigation) |

### 3.4 Screen States (Wireframe-Level)

#### State A: Default View (HR Module, "Dashboard" context active)

```
┌──────────────────────────────────┐
│  🍔 HR          🔔 ⚡ 👤         │  ← Top App Bar (48px)
├──────────────────────────────────┤
│                                   │
│   Dashboard Content               │
│   (widgets, stats, charts)        │
│                                   │
│                                   │
│                                   │
├──────────────────────────────────┤
│  🏠        👤       👥       ⚙️    ⋯ │  ← Bottom Tab Bar (56px)
│ Dashboard  Portal  People  Manage More │
└──────────────────────────────────┘
```

#### State B: "People" Tab Active (first tap)

```
┌──────────────────────────────────┐
│  🍔 HR     People     🔔 ⚡ 👤   │  ← Title updates to "People"
├──────────────────────────────────┤
│                                   │
│   People Overview Content         │
│   (employee stats, org chart)     │
│                                   │
│                                   │
│                                   │
├──────────────────────────────────┤
│  🏠        👤       👥       ⚙️    ⋯ │
│ Dashboard  Portal [People] Manage More │  ← [People] highlighted
│                          ▲          │
│                     chevron indicates│
│                     sub-items avail. │
└──────────────────────────────────┘
```

#### State C: Context Sheet Open (double-tap "People" or swipe up)

```
┌──────────────────────────────────┐
│  🍔 HR     People     🔔 ⚡ 👤   │
├──────────────────────────────────┤
│  (content dimmed behind sheet)    │
│                                   │
├──────────────────────────────────┤
│  ─── drag handle ───             │  ← Context Sheet (half-screen)
│  👥 People                       │
│  ────────────────────────────────│
│  📋 Overview                 →   │
│  👤 Employee List            →   │
│  📄 Profiles                 →   │
│  💼 Current Jobs             →   │
│  👥 Teams                    →   │
│  📊 Employee Groups          →   │
│                                   │
├──────────────────────────────────┤
│  🏠        👤       👥       ⚙️    ⋯ │
│ Dashboard  Portal [People] Manage More │
└──────────────────────────────────┘
```

#### State D: Navigation Hub Open (tap 🍔)

```
┌──────────────────────────────────┐
│  ─── drag handle ───             │  ← Navigation Hub (half-screen)
│                                   │
│  SWITCH MODULE                   │  ← Section header
│  ────────────────────────────────│
│  👥 HR                      ✓   │  ← current module highlighted
│  💰 Payroll                      │
│  📅 Leave                        │
│  🕐 Attendance                   │
│  ⚙️ Admin                        │
│  🔧 System                       │
│                                   │
│  SWITCH COMPANY                  │  ← Section header (hidden if single company)
│  ────────────────────────────────│
│  🏢 Agriwatts Demo          ✓   │  ← current company highlighted
│  🏢 Quick HR Main                │
│  🌐 All Companies                │  ← admin-only option
└──────────────────────────────────┘
```

#### State E: Overflow Sheet Open (tap ⋯ in Bottom Tab Bar)

```
┌──────────────────────────────────┐
│  🍔 HR     People     🔔 ⚡ 👤   │
├──────────────────────────────────┤
│  (content dimmed behind sheet)    │
│                                   │
├──────────────────────────────────┤
│  ─── drag handle ───             │  ← Overflow Sheet (half-screen)
│  More Contexts                   │
│  ────────────────────────────────│
│  ▶ 📊 Reports                    │  ← Collapsed: tap to navigate + expand
│                                   │
│  ▼ ⚙️ Settings                   │  ← Expanded: shows sub-items inline
│     ─────────────────────────── │
│     📋 General Settings      →   │
│     🔐 Security              →   │
│     📧 Notifications         →   │
│                                   │
│  ▶ 📅 Schedules                   │  ← Collapsed
│                                   │
│  ▶ 📈 Analytics                   │  ← Collapsed
└──────────────────────────────────┘
```

**Overflow Sheet behavior** (see §3.6 for full details):
- Each overflow context group renders as an **expandable accordion row**
- **First tap on a collapsed group**: navigates to that group's default URL AND expands the row to reveal sub-items inline
- **Tap on an already-expanded group header**: navigates to the group's URL (no toggle)
- **Tap on a sub-item**: navigates to that item, sheet closes
- **Chevron (▶/▼)** toggles expand/collapse without navigation
- The currently active context group (if in overflow) is auto-expanded when the sheet opens

### 3.5 Overflow Behavior: The "More" Sheet

When context groups exceed the visible tab limit (default 4), excess groups move to an overflow sheet accessed via the ⋯ tab. This is the mobile equivalent of the desktop TopNav's "More" dropdown.

#### Accordion Pattern (Not Tab Promotion)

The overflow sheet uses an **expandable accordion** rather than promoting items to the visible tab bar. Rationale:

1. **Stable layout** — Tab positions never change. Users develop muscle memory for tab locations.
2. **No disorientation** — Promoting an overflow item into visible tabs would shift all other tabs, breaking spatial memory.
3. **Self-contained navigation** — The accordion provides both context switching AND sub-item access in one place.

#### Interaction Rules

| User Action | Result |
|-------------|--------|
| Tap a **collapsed** group header | Navigate to group's default URL **+** expand to show sub-items inline |
| Tap an **expanded** group header | Navigate to group's default URL (no toggle — already expanded) |
| Tap the **chevron** (▶/▼) | Toggle expand/collapse **without** navigation |
| Tap a **sub-item** | Navigate to that item, sheet closes |
| Swipe down / tap backdrop | Sheet closes, no navigation change |

#### Auto-Expand Active Context

When the overflow sheet opens, the **currently active context group** (if it's in overflow) is automatically expanded. This means:
- User taps "People" in the BottomBar → navigates to People overview
- User taps ⋯ → overflow sheet opens with "People" already expanded, showing its sub-items
- User can immediately navigate to a People sub-item without an extra tap

#### Why Not Tab Promotion?

Tab promotion (moving a tapped overflow item into the visible tab bar) is common in desktop apps (browser tabs, IDE tabs) but problematic on mobile:

- **Limited space**: Only 4-5 tabs fit. Promoting one means demoting another — which one?
- **Unpredictable layout**: Tabs shifting position breaks muscle memory
- **No clear "least recently used"**: Unlike browser tabs, context groups don't have a natural eviction order
- **The accordion solves the same problem**: Users get immediate access to sub-items without needing the tab to be visible

### 3.6 Responsive Breakpoints

| Breakpoint | Sidebar | BottomBar | Context Tabs Location |
|------------|---------|-----------|----------------------|
| **Mobile** (< 768px) | Hidden (drawer on demand) | ✅ Visible — shows context group tabs | Bottom Tab Bar |
| **Tablet** (768–1024px) | Collapsed (icons only, expandable) | ❌ Hidden | TopNav (like desktop) |
| **Desktop** (> 1024px) | ✅ Visible (full sidebar) | ❌ Hidden | TopNav (like desktop) |

---

## 4. Rationale

### 4.1 Why Bottom Tabs for Context Groups

1. **Thumb zone accessibility** — Material Design and iOS HIG both recommend primary navigation at the bottom of the screen for one-handed use. Context groups are the primary navigation surface (users switch between them frequently).

2. **Discoverability** — Bottom tabs are always visible and labeled. Unlike the current horizontal scroll strip (which hides overflow), a tab bar with a "More" item makes all destinations discoverable.

3. **Mental model transfer** — Desktop users click TopNav tabs to switch contexts. Mobile users tap BottomBar tabs. The spatial relationship is preserved: tabs at the "edge" of the screen switch the main content area.

4. **Eliminates duplication** — The BottomBar currently duplicates sidebar items. By making it show context groups instead, it serves a distinct purpose from the sidebar (which shows sub-items).

### 4.2 Why a Slide-Up Sheet for Sub-Items

1. **Progressive disclosure** — Sub-items are hidden until needed. The user's primary flow (tap context → see content) is uninterrupted.

2. **Contextual awareness** — The sheet only shows items for the active context group. This mirrors the desktop sidebar behavior exactly.

3. **Familiar pattern** — iOS Maps, Apple Music, and Google Maps all use slide-up sheets for secondary navigation. Users understand the drag handle and dismissal gestures.

4. **Space efficiency** — The sheet overlays content rather than pushing it aside. On small screens, every pixel of content width matters.

### 4.3 Why a Navigation Hub (Not Separate Drawers) for Module + Company Switching

1. **Frequency** — Both module switching and company switching are infrequent (once per session or less). Neither deserves permanent screen real estate.

2. **Shared access pattern** — Both are "switch current context" actions. Grouping them in one sheet reduces cognitive load: "I want to switch something → tap 🍔."

3. **Role-gated sections** — The Module section only appears when the user has access to multiple modules. The Company section only appears when the user has multi-company access (gated by `features.multi_company` and role checks). Single-module, single-company users see a minimal sheet or no sheet at all.

4. **Scale** — An app may have 6+ modules AND 10+ companies. A combined sheet with sections scales better than two separate drawers.

5. **Company indicator in Top Bar** — For users with multi-company access, a 🏢 icon in the Top App Bar shows the current company name. This provides ambient awareness without consuming tab space. Tapping it opens the Navigation Hub scrolled to the company section.

### 4.4 What This Replaces

| Current Element | Proposed Replacement | Rationale |
|-----------------|---------------------|-----------|
| TopNav scrollable context pills | Bottom Tab Bar context tabs | Better reach, discoverability, no horizontal scroll |
| BottomBar (sidebar item duplicates) | **Removed** — no longer needed | Context tabs serve the bottom bar role; sub-items are in the sheet |
| Hidden sidebar on mobile | Context Sheet (slide-up) | Same data, mobile-appropriate presentation |
| Module switcher dropdown | Navigation Hub — Module section | More space, better for 6+ modules |
| Company switcher dropdown | Navigation Hub — Company section + Top Bar indicator | Unified switching surface, ambient awareness via indicator |

---

## 5. Implementation Recommendations

### 5.1 Library Changes Required

| File | Change |
|------|--------|
| [`BottomBar.php`](../src/Http/Livewire/Layouts/Navs/BottomBar.php) | Refactor to accept `$contextGroups` instead of `$items`. Render context group tabs with icons + labels. Add "More" overflow tab. |
| [`bottom-bar.blade.php`](../src/Resources/views/livewire/navs/bottom-bar.blade.php) | Complete rewrite — tab bar layout with active state, chevron indicator for sub-items, overflow sheet for extra groups. |
| [`navigation-layout.blade.php`](../src/Resources/views/livewire/navs/navigation-layout.blade.php) | Pass `$contextGroups` to BottomBar instead of `$contextItems[$activeContext]`. Add Context Sheet component. |
| [`top-nav.blade.php`](../src/Resources/views/livewire/navs/top-nav.blade.php) | Remove the mobile scrollable context pills (`d-md-none mobile-scroll-wrapper`). Keep module switcher, actions, profile. |
| **New: `ContextSheet` component** | Livewire component + Blade view for the slide-up sub-item sheet. Receives `$contextGroup` and `$items`. |
| **New: `ModuleSheet` component** | Livewire component + Blade view for the module picker half-sheet. |
| [`navigation.php` config schema](../docs/library/06-navigation-system.md) | Add `mobile_tab` key to context group schema (default `true`). |

### 5.2 Backward Compatibility

- The `mobile_tab` key defaults to `true` — all existing context groups appear in the bottom tab bar automatically.
- The `maxVisible` property on BottomBar controls how many tabs show before overflow (default 4).
- Modules without context groups (legacy `items` array) fall back to showing those items directly in the BottomBar (current behavior preserved).
- The `bottom_bar.enabled` config key in `navigation.php` layout section continues to gate the entire BottomBar.

### 5.3 Phased Rollout

| Phase | Scope | Risk |
|-------|-------|------|
| **Phase 1** | Refactor BottomBar to show context groups. Keep existing Context Sheet as a simple list. Remove TopNav mobile pills. | Low — BottomBar is already mobile-only |
| **Phase 2** | Add Context Sheet with slide-up animation, drag handle, active item highlighting. | Medium — new component, needs gesture testing |
| **Phase 3** | Add Module Sheet for module switching. Replace dropdown. | Low — isolated component |
| **Phase 4** | Tablet breakpoint: collapsed sidebar (icons only) with expand-on-tap. | Medium — responsive complexity |

---

## 6. Open Questions

1. **Should the Context Sheet auto-open when switching contexts?** Pro: immediate access to sub-items. Con: may feel intrusive. Recommendation: no — require explicit double-tap or chevron tap. Power users learn the gesture; casual users aren't interrupted.

2. **Should the Bottom Tab Bar hide on scroll?** Pro: more content space. Con: less navigation discoverability. Recommendation: hide on scroll down, show on scroll up (standard Material Design pattern). Configurable via `bottom_bar.hide_on_scroll`.

3. **What about the Quick Actions ⚡ button on mobile?** Currently in TopNav. Could move to a floating action button (FAB) for better thumb reach. Recommendation: keep in TopNav for Phase 1; evaluate FAB in Phase 2.

4. **Should the Context Sheet support swipe-between-contexts?** iOS-style horizontal swipe to move between context sheets. Recommendation: nice-to-have for Phase 3+. Adds complexity; tab bar already provides context switching.

5. **Company switcher: Top Bar indicator vs Bottom Tab?** The proposal puts a 🏢 indicator in the Top App Bar (not a dedicated bottom tab). Rationale: company switching is infrequent and doesn't warrant permanent bottom tab space. However, if user testing shows users frequently switch companies, a dedicated tab may be warranted. Recommendation: start with Top Bar indicator; monitor usage data.

6. **Overflow accordion: should the active group auto-expand?** The proposal auto-expands the active context group when the overflow sheet opens. This could be surprising if the user just wanted to see what's in overflow. Recommendation: auto-expand is correct — the user's current context is the most relevant starting point. If they want to explore other groups, they can collapse it.