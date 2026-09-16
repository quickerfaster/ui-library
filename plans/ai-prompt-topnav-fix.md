# AI Prompt: Continue Mobile Navigation Redesign — TopNav Fix

## Context

You are Zoo, an expert software developer working on the **QuickerFaster UI Library** — a domain-agnostic, decoupled Laravel + Livewire 3 foundation at `/Users/mac/Projects/Libraries/ui-library`. The consuming app is an HR system at `/Users/mac/Projects/LaravelProjects/hr-consuming-app`.

## Library Philosophy (MUST RESPECT)

From [`docs/library/pilosophy.txt`](../../docs/library/pilosophy.txt):
1. Library must be completely decoupled from the consuming app
2. Consuming app modules must be self-contained and plug-and-play

From [`docs/library/25-library-independence-safeguards.md`](../../docs/library/25-library-independence-safeguards.md):
- No `use App\Modules\...` in `src/`
- Contracts named by capability, never by domain
- Extension point first: Contract → Default impl → Config-key binding

## What We've Built So Far

We completed a major mobile navigation redesign. Here's the architecture:

### Desktop Pattern (unchanged)
```
┌──────────────────────────────────────────────────────────────┐
│  TopNav: [📦 HR] [Dashboard] [People] [Manage] [More…]       │  ← context group tabs
│           [🏢 Company] [🔔] [⚡] [👤]                          │  ← actions
├──────────┬───────────────────────────────────────────────────┤
│ Sidebar  │  Content Area                                      │
│ (context │                                                    │
│  items)  │                                                    │
└──────────┴───────────────────────────────────────────────────┘
```

### Mobile Pattern (new)
```
┌──────────────────────────────────────────────────────────────┐
│  TopNav: [📦 HR]              [🏢] [🔔] [⌘] [⋯] [👤]         │  ← compact
├──────────────────────────────────────────────────────────────┤
│  Content Area                                                  │
├──────────────────────────────────────────────────────────────┤
│         ▬▬ People ▲ ▬▬                                      │  ← Handle Bar (tap for sub-items)
├──────────────────────────────────────────────────────────────┤
│     🏠    │    👤    │    👥    │    ⚙️    │    ⋯             │  ← Icon-only context tabs
└──────────────────────────────────────────────────────────────┘
```

### Components Created/Modified

| Component | File | Purpose |
|-----------|------|---------|
| **BottomBar** (refactored) | [`src/Http/Livewire/Layouts/Navs/BottomBar.php`](src/Http/Livewire/Layouts/Navs/BottomBar.php) | Icon-only context group tabs + handle bar |
| **bottom-bar.blade.php** (rewritten) | [`src/Resources/views/livewire/navs/bottom-bar.blade.php`](src/Resources/views/livewire/navs/bottom-bar.blade.php) | Handle bar + icon tabs + overflow accordion sheet |
| **ContextSheet** (new) | [`src/Http/Livewire/Layouts/Navs/ContextSheet.php`](src/Http/Livewire/Layouts/Navs/ContextSheet.php) | Slide-up sub-items sheet, receives items as props from NavigationLayout |
| **context-sheet.blade.php** (new) | [`src/Resources/views/livewire/navs/context-sheet.blade.php`](src/Resources/views/livewire/navs/context-sheet.blade.php) | Drag handle, header, sub-item list |
| **NavigationHub** (new) | [`src/Http/Livewire/Layouts/Navs/NavigationHub.php`](src/Http/Livewire/Layouts/Navs/NavigationHub.php) | Self-sufficient module + company switching sheet |
| **navigation-hub.blade.php** (new) | [`src/Resources/views/livewire/navs/navigation-hub.blade.php`](src/Resources/views/livewire/navs/navigation-hub.blade.php) | Two-section sheet with active highlighting |
| **TopNav** (simplified) | [`src/Resources/views/livewire/navs/top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) | Removed hamburger, removed collapse wrapper, unified layout |
| **NavigationLayout** (updated) | [`src/Resources/views/components/layouts/navigation-layout.blade.php`](src/Resources/views/components/layouts/navigation-layout.blade.php) | Wires BottomBar, ContextSheet, NavigationHub; 128px bottom padding |
| **NavigationLayout.php** (updated) | [`src/Components/NavigationLayout.php`](src/Components/NavigationLayout.php) | Merges contextItems into contextGroups for BottomBar |
| **UILibraryServiceProvider** (updated) | [`src/Providers/UILibraryServiceProvider.php`](src/Providers/UILibraryServiceProvider.php) | Registered `qf.context-sheet` and `qf.navigation-hub` |

### Key Design Decisions

1. **Handle Bar**: Full-width bar above BottomBar showing active context label. Groups WITH sub-items show "Label ▲" (tap opens ContextSheet). Groups WITHOUT sub-items show "Label" (tap navigates).
2. **Icon-only tabs**: No text labels on BottomBar tabs — handle bar serves as the label. Follows Material Design 3 icon-only nav bar pattern.
3. **ContextSheet receives items as props**: Same data source as desktop sidebar (`$contextItems[$activeContext]` from NavigationLayout). No event-based data passing.
4. **NavigationHub is self-sufficient**: Loads modules from `config('ui-library.modules')`, companies from `CompanyProvider` contract. No props needed.
5. **Overflow sheet uses pure Alpine**: `x-show` + `x-transition` instead of Bootstrap Offcanvas (which had state issues).

### Docs Updated
- [`plans/mobile-navigation-ux-analysis.md`](plans/mobile-navigation-ux-analysis.md) — Full analysis with implementation status
- [`docs/library/06-navigation-system.md`](docs/library/06-navigation-system.md) — Config schema, TopNav architecture, HorizontalContextMenu
- [`docs/CHANGELOG.md`](docs/CHANGELOG.md) — All changes logged

## Current Bug to Fix

### Problem: NavigationHub Opens as Full-Screen Sheet on Desktop

The NavigationHub was designed as a **mobile slide-up sheet**. When we removed `d-md-none` to make it work on desktop, it opens as a full-screen bottom sheet covering the entire desktop viewport — terrible UX.

**Expected behavior:**
- **Desktop (≥768px)**: Module switcher and Company switcher should use **Bootstrap dropdowns** (compact, contextual, standard desktop pattern)
- **Mobile (<768px)**: Module switcher and Company switcher should open the **NavigationHub sheet** (large touch targets, thumb-friendly)

### Current State of [`top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php)

The module switcher button (line ~18):
```blade
<button @click="Livewire.dispatch('openNavigationHub', { scrollTo: 'module' })">
```

The company switcher button (line ~90):
```blade
<button @click="Livewire.dispatch('openNavigationHub', { scrollTo: 'company' })">
```

Both use `Livewire.dispatch` to open NavigationHub — this works but opens the mobile sheet on desktop.

### What Needs to Happen

1. **Restore `d-md-none` on NavigationHub** — it should only appear on mobile
2. **Module switcher**: Desktop = Bootstrap dropdown (restore original dropdown markup with `d-none d-md-block`), Mobile = button → NavigationHub (`d-md-none`)
3. **Company switcher**: Desktop = Bootstrap dropdown (`d-none d-md-block`), Mobile = button → NavigationHub (`d-md-none`)
4. The original dropdown markup for both was removed in commit `416d40a`. You'll need to restore it from git history or rebuild it.

### Reference: Original Dropdown Markup

The original module switcher dropdown (before simplification):
```blade
<div class="dropdown me-2" id="module-switcher">
    <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-th-large me-1"></i>
        <span class="d-none d-md-inline">{{ $this->currentModuleLabel }}</span>
    </button>
    <ul class="dropdown-menu shadow border-0">
        @foreach ($this->modules as $module)
            <li>
                <a class="dropdown-item" href="#" wire:click.prevent="switchModule('{{ $module['key'] }}')">
                    <i class="{{ $module['icon'] }} me-2"></i> {{ $module['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
```

The original company switcher dropdown was similar with company list and `switchCompany()` calls.

### Key Files to Modify

| File | Change |
|------|--------|
| [`src/Resources/views/livewire/navs/top-nav.blade.php`](src/Resources/views/livewire/navs/top-nav.blade.php) | Add responsive module/company switchers: desktop dropdowns + mobile NavigationHub buttons |
| [`src/Resources/views/livewire/navs/navigation-hub.blade.php`](src/Resources/views/livewire/navs/navigation-hub.blade.php) | Restore `d-md-none` (mobile only) |

### Verification

After fixing:
- Resize to **desktop width** → click "HR" module switcher → dropdown appears with module list
- Resize to **desktop width** → click company name → dropdown appears with company list
- Resize to **mobile width** → click "HR" → NavigationHub sheet slides up
- Resize to **mobile width** → click company icon → NavigationHub sheet slides up (scrolled to company section)

### Git History Reference

Key commits on `main` branch:
- `416d40a` — TopNav simplification (removed original dropdowns)
- `192b119` — Removed d-md-none from NavigationHub (NEEDS REVERT)
- `4dc3603` — Module switcher → NavigationHub button
- `4e97b80` — NavigationHub self-sufficient
- `5d559a2` — Icon-only bottom tabs
- `d2d1f53` — Initial mobile navigation redesign

### Consuming App Note

The consuming app pulls the library from GitHub via VCS:
```json
"repositories": [{"type": "vcs", "url": "https://github.com/quickerfaster/ui-library"}],
"require": {"quicker-faster/ui-library": "*@dev"}
```

After making changes to the library, run in the consuming app:
```bash
composer update quicker-faster/ui-library
php artisan view:clear
```

Also check for stale published views at `resources/views/vendor/qf/` — delete any that override library files you've modified.