# Mobile Company & Module Switching — Options Comparison

> **Date**: 2026-09-16
> **Context**: Desktop uses Bootstrap dropdowns for both switchers. Mobile currently has two buttons that both open the NavigationHub bottom sheet (scrolled to different sections). We're evaluating four options for the optimal mobile interaction pattern.

---

## Current Architecture Reference

### Drawer Component ([`src/Http/Livewire/Drawer.php`](../src/Http/Livewire/Drawer.php))

| Property | Detail |
|----------|--------|
| **Type** | Bootstrap Offcanvas (`offcanvas-end`) — slides in from the **right** |
| **Trigger** | `$dispatch('openDrawer', component, params, title)` |
| **Content** | Hosts any Livewire component dynamically |
| **Primary use** | CRUD forms, detail views, filters, search panel, column manager, background jobs, settings panel |
| **Design intent** | Contextual workspace for data operations — not navigation |

### NavigationHub ([`src/Http/Livewire/Layouts/Navs/NavigationHub.php`](../src/Http/Livewire/Layouts/Navs/NavigationHub.php))

| Property | Detail |
|----------|--------|
| **Type** | Custom bottom sheet — slides up from the **bottom** |
| **Animation** | Alpine.js `x-show` + `x-transition` (translate-y) |
| **Content** | Two sections: **Switch Module** + **Switch Company** (both role-gated) |
| **Data** | Self-sufficient — loads modules from `config('ui-library.modules')`, companies from `CompanyProvider` contract |
| **Design intent** | Mobile-first navigation switching — designed per the [mobile navigation UX analysis](../plans/mobile-navigation-ux-analysis.md) State D |

### Original Design Intent (from [`mobile-navigation-ux-analysis.md`](../plans/mobile-navigation-ux-analysis.md))

The NavigationHub was designed as a **single entry point** (🍔 hamburger icon) containing both module and company switching in one unified sheet:

```
┌──────────────────────────────────┐
│  ─── drag handle ───             │
│                                   │
│  SWITCH MODULE                   │
│  ────────────────────────────────│
│  👥 HR                      ✓   │
│  💰 Payroll                      │
│  📅 Leave                        │
│                                   │
│  SWITCH COMPANY                  │
│  ────────────────────────────────│
│  🏢 Agriwatts Demo          ✓   │
│  🏢 Quick HR Main                │
│  🌐 All Companies                │
└──────────────────────────────────┘
```

The current implementation deviates from this by having **two separate buttons** (module label + company icon) that both open the same NavigationHub but scroll to different sections.

---

## Option Comparison

### Option 1: Dropdowns on Mobile (Remove NavigationHub)

> Use the same Bootstrap dropdown behavior as desktop for both switchers on mobile. Remove the NavigationHub entirely.

| Dimension | Assessment |
|-----------|------------|
| **Implementation complexity** | 🟢 **Low**. Remove `d-md-none` from desktop dropdown wrappers, delete NavigationHub component and its registration. ~30 minutes of work. |
| **User intuitiveness** | 🔴 **Poor**. Bootstrap dropdowns render as small `<ul>` lists with ~40px touch targets. On a 375px-wide phone, the dropdown menu is cramped and hard to tap accurately. Users must precisely tap a small row — no thumb-friendly sizing. |
| **Alignment with best practices** | 🔴 **Violates mobile UX principles**. Material Design 3 explicitly recommends **bottom sheets** for selection lists on mobile, not dropdowns. Apple HIG recommends **action sheets** or **popovers** for iOS. Bootstrap dropdowns are a desktop pattern with no mobile optimization. |
| **User expectations** | 🔴 **Mismatch**. Mobile users expect large touch targets (minimum 44px per WCAG 2.1), swipe-to-dismiss, and bottom-anchored sheets. A cramped dropdown feels like a desktop site shoehorned into a phone. |
| **Standard UX principles** | 🔴 **Fails Fitts's Law** on mobile. Small targets at the top of the screen are the hardest to reach with one-handed use. The thumb zone (bottom 1/3 of screen) is completely unused. |
| **Consistency** | 🟡 **Mixed**. Consistent behavior across breakpoints (dropdowns everywhere), but inconsistent with every other mobile app the user has ever used. |
| **Other factors** | NavigationHub was built specifically for this purpose — removing it wastes the investment. Also, the NavigationHub has role-gated sections and active-state highlighting that dropdowns would need to replicate. |

**Verdict**: ❌ Not recommended. Solves the implementation question but creates a poor mobile UX.

---

### Option 2: Single Module Button → NavigationHub (Integrate Company)

> Keep only the module switcher button on mobile. The company switcher is accessed inside the NavigationHub (which already has both sections). This is the **original design intent** from the mobile UX analysis.

| Dimension | Assessment |
|-----------|------------|
| **Implementation complexity** | 🟢 **Low**. Remove the company switcher button from mobile (`d-md-none` on the company button, keep desktop dropdown). NavigationHub already has both sections — no changes needed there. ~15 minutes of work. |
| **User intuitiveness** | 🟢 **High**. Single entry point (module label button) opens a sheet with both sections clearly labeled. Users see "Switch Module" and "Switch Company" in one view — no need to hunt for a second button. The sheet is dismissible with swipe-down, tap-backdrop, or selecting an item. |
| **Alignment with best practices** | 🟢 **Strong**. Follows Material Design 3 bottom sheet pattern for selection lists. Large touch targets (~56px rows). Thumb-friendly (bottom-anchored). Progressive disclosure — infrequent actions (module/company switching) are one tap away but don't clutter the top bar. |
| **User expectations** | 🟢 **Matches**. This is how Google Admin, Shopify, and Notion handle workspace/module switching on mobile — a single avatar/workspace button that opens a sheet with all switching options. |
| **Standard UX principles** | 🟢 **Fitts's Law**: The module button is at the top-left (reachable with left thumb on most phones). The sheet opens from the bottom (thumb zone). **Hick's Law**: Two clearly separated sections reduce cognitive load vs. two separate buttons. **Jakob's Law**: Matches the pattern users know from other apps. |
| **Consistency** | 🟡 **Good with minor tradeoff**. Desktop has two separate dropdowns (module + company). Mobile has one button → one sheet with both. The mental model differs slightly but the content is identical. Users who switch between desktop and mobile will recognize both sections. |
| **Other factors** | This is exactly what the [mobile navigation UX analysis](../plans/mobile-navigation-ux-analysis.md) designed (State D). The NavigationHub was built for this exact purpose. The company section is already role-gated — it only appears when the user has multi-company access. No wasted work. |

**Verdict**: ✅ **Strongly recommended**. Lowest implementation cost, best mobile UX, aligns with original design intent, and follows established patterns.

---

### Option 3: Replace Desktop Dropdowns with Drawer (Everywhere)

> Replace both desktop Bootstrap dropdowns with the UI library's Drawer component (`offcanvas-end`). Same implementation works on mobile without changes.

| Dimension | Assessment |
|-----------|------------|
| **Implementation complexity** | 🟡 **Medium**. Replace `data-bs-toggle="dropdown"` with `$dispatch('openDrawer', ...)` for both switchers. Need to create a new Livewire component for the drawer content (module list + company list). The Drawer already exists and is registered. ~2-3 hours of work. |
| **User intuitiveness** | 🔴 **Poor on desktop**. Replacing a compact 200px dropdown with a full right-side offcanvas panel is massive overkill for a simple 5-item list. Desktop users expect dropdowns for context switching — an offcanvas feels like opening a form. |
| **Alignment with best practices** | 🔴 **Violates desktop patterns**. Offcanvas/ drawers are designed for **content creation and detail views** (forms, filters, settings), not simple navigation pickers. Bootstrap's own documentation recommends dropdowns for "contextual overlays for displaying lists of links." |
| **User expectations** | 🔴 **Jarring on desktop**. A right-side panel sliding in to show 5 module names feels broken. Users will think a form or settings panel opened by mistake. On mobile, a right-side drawer is less thumb-friendly than a bottom sheet. |
| **Standard UX principles** | 🔴 **Violates least surprise**. The Drawer component is used everywhere else in the app for forms (data-table-form, data-table-detail, filters, settings). Using it for navigation switching conflates two different interaction patterns. |
| **Other factors** | The Drawer component ([`Drawer.php`](../src/Http/Livewire/Drawer.php)) is designed to host **dynamic Livewire components** with complex state (forms, filters). Using it for a static list is like using a truck to carry a grocery bag. Also, the Drawer uses `offcanvas-end` (right side) — on mobile, right-side drawers are harder to reach than bottom sheets. |

**Verdict**: ❌ Not recommended. Misuses the Drawer component, degrades desktop UX, and doesn't improve mobile UX.

---

### Option 4: Stack Both in Drawer on Mobile (Keep Desktop Dropdowns)

> On mobile, both buttons open the Drawer with a combined module + company component. Desktop keeps Bootstrap dropdowns unchanged.

| Dimension | Assessment |
|-----------|------------|
| **Implementation complexity** | 🔴 **High**. Need to create a new Livewire component for the drawer content, wire up both buttons to dispatch `openDrawer` with different scroll targets, handle the responsive split (desktop dropdowns vs. mobile drawer), and ensure the drawer component loads the right data. ~4-5 hours of work. |
| **User intuitiveness** | 🟡 **Moderate**. The drawer slides from the right — users must reach across the screen (right-handed) or stretch (left-handed). Less intuitive than a bottom sheet. However, the combined view (both sections in one panel) is good. |
| **Alignment with best practices** | 🟡 **Mixed**. The combined view is good (matches Material Design bottom sheet pattern), but the right-side slide-in is wrong for mobile navigation. Bottom sheets are the standard for mobile selection lists. |
| **User expectations** | 🟡 **Mixed**. Users may expect a bottom sheet (like every other mobile app). A right-side drawer feels like a settings panel, not a navigation switcher. |
| **Standard UX principles** | 🔴 **Fails thumb-zone principle**. Right-side drawers require reaching to the far edge of the screen — the hardest zone for one-handed use. Bottom sheets place content in the natural thumb path. |
| **Other factors** | This option essentially rebuilds NavigationHub inside the Drawer component. NavigationHub already does exactly this (combined module + company view) but with a bottom sheet instead of a right-side drawer. The only difference is the animation direction — and bottom is better for mobile. |

**Verdict**: ❌ Not recommended. Rebuilds what NavigationHub already does, but with worse ergonomics (right-side vs. bottom).

---

## Summary Matrix

| Option | Complexity | Mobile UX | Desktop UX | Best Practices | Original Intent | Verdict |
|--------|-----------|-----------|------------|----------------|-----------------|---------|
| **1. Dropdowns everywhere** | 🟢 Low | 🔴 Poor | 🟢 Good | 🔴 Violates | ❌ Discards | ❌ No |
| **2. Single button → NavHub** | 🟢 Low | 🟢 Excellent | 🟢 Good | 🟢 Aligned | ✅ Matches | ✅ **YES** |
| **3. Drawer everywhere** | 🟡 Medium | 🟡 Fair | 🔴 Poor | 🔴 Violates | ❌ Discards | ❌ No |
| **4. Drawer on mobile only** | 🔴 High | 🟡 Fair | 🟢 Good | 🟡 Mixed | ❌ Rebuilds | ❌ No |

---

## Recommendation: Option 2

### What It Means

1. **Desktop** (unchanged): Two separate Bootstrap dropdowns — module switcher + company switcher
2. **Mobile**: Single module label button → opens NavigationHub bottom sheet with both "Switch Module" and "Switch Company" sections
3. **Remove** the separate company switcher button from mobile top-nav (keep it on desktop)

### Why This Wins

- **Already built**: NavigationHub has both sections, role-gated, with active highlighting
- **Matches original design**: The [mobile navigation UX analysis](../plans/mobile-navigation-ux-analysis.md) explicitly designed this (State D)
- **Best mobile UX**: Bottom sheet = thumb-friendly, large touch targets, swipe-to-dismiss
- **Lowest effort**: ~15 minutes — just hide the company button on mobile
- **Progressive disclosure**: Infrequent actions (switching) are one tap away, not cluttering the top bar
- **Industry standard**: Google Admin, Shopify, Notion all use this pattern

### Implementation Sketch

In [`top-nav.blade.php`](../src/Resources/views/livewire/navs/top-nav.blade.php), the company switcher section would change from:

```blade
{{-- Desktop: Bootstrap dropdown --}}
<div class="dropdown d-none d-md-block" id="company-switcher">...</div>

{{-- Mobile: NavigationHub button --}}
<button class="... d-md-none" @click="Livewire.dispatch('openNavigationHub', { scrollTo: 'company' })">...</button>
```

To:

```blade
{{-- Desktop + Mobile: Bootstrap dropdown (desktop only via d-none d-md-block) --}}
<div class="dropdown d-none d-md-block" id="company-switcher">...</div>
{{-- Mobile: No separate button — company switching is inside NavigationHub --}}
```

The module switcher button on mobile would open NavigationHub without a `scrollTo` parameter (or with `scrollTo: 'module'`), and users would see both sections naturally.

### Tradeoff Acknowledged

The only tradeoff: on desktop, company switching is one click (dropdown). On mobile, it's two taps (module button → see company section in sheet). This is acceptable because:
- Company switching is infrequent (set once per session)
- The two-tap cost is offset by the cleaner top bar and better touch targets
- Users who need frequent company switching are likely on desktop anyway