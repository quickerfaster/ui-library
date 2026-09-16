# AI Prompt: Fix ContextSheet Active Sub-Item Highlighting

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
│  TopNav: [📦 HR]              [🔔] [⌘] [⋯] [👤]              │  ← compact
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

## Bugs Already Fixed in This Session

1. **NavigationHub full-screen on desktop** — Added `d-md-none` to NavigationHub, split module/company switchers into responsive desktop dropdowns + mobile NavigationHub buttons
2. **"More" overflow dropdown clipped** — Removed `overflow-hidden` from parent container
3. **Config key mismatch** — NavigationHub used `ui-library.features.multi_company` but TopNav used `ui-library.navigation.show_company_switcher` — aligned to use same key
4. **Missing `$user` argument** — NavigationHub called `$provider->getCompanies()` without `auth()->user()` argument
5. **404 on module/company switch** — NavigationHub passed route names as raw URLs instead of resolving with `route()` helper; company switch redirected to `request()->url()` instead of module dashboard
6. **Active state visibility in NavigationHub** — Changed from `bg-opacity-10` to inline styles with `!important` for cross-browser compatibility
7. **Horizontal divider below "All Companies"** — Added in both desktop dropdown and mobile NavigationHub
8. **Company switcher removed from mobile** — Single module button → NavigationHub with both sections (Option 2 from comparison report)

## Current Bug: ContextSheet Active Sub-Item Not Visibly Highlighted

### What Works
- The `isItemActive()` PHP method in [`ContextSheet.php`](src/Http/Livewire/Layouts/Navs/ContextSheet.php) correctly detects the active sub-item
- Debug output confirmed: `<!-- DEBUG: path=hr/employees route=/hr/employees active=YES -->`
- The HTML source shows the correct conditional classes and inline styles are being rendered

### What Doesn't Work
- The active sub-item is NOT visually distinguishable from inactive items
- When `text-primary` is added to the `<a>` tag's class, ALL sub-links get highlighted — suggesting a **Bootstrap `list-group-item` CSS specificity issue**

### Current Code (context-sheet.blade.php)

```blade
@forelse ($items as $item)
    @php
        $itemUrl = $this->resolveItemUrl($item);
        $isActive = $this->isItemActive($item);
    @endphp
    <a href="{{ $itemUrl }}"
       wire:navigate
       @click="open = false"
       class="list-group-item list-group-item-action border-0 d-flex align-items-center px-3 py-3
              {{ $isActive ? 'fw-bold' : '' }}"
       @if ($isActive)
       style="background: rgba(13, 110, 253, 0.25) !important; border-left: 3px solid #0d6efd !important; color: #212529 !important;"
       @endif>
        @if (!empty($item['icon']))
            <i class="{{ $item['icon'] }} me-3 {{ $isActive ? 'text-primary' : 'text-muted' }}" style="width: 20px; text-align: center;"></i>
        @endif
        <span class="flex-grow-1">{{ $item['label'] }}</span>
        @if ($isActive)
            <i class="fas fa-check text-primary"></i>
        @else
            <i class="fas fa-chevron-right text-muted opacity-25"></i>
        @endif
    </a>
@empty
    ...
@endforelse
```

### Key Observation
The user reports: "when I add `text-primary` to the `<a>` class, ALL sub-links are highlighted." This means Bootstrap's `list-group-item` and `list-group-item-action` classes have CSS rules that override or interfere with the conditional inline styles. The `border-0` class uses `border: 0 !important` which may override `border-left`. The `list-group-item-action` class has its own color and background rules.

### What Needs to Happen

1. **Diagnose the CSS conflict**: Determine which Bootstrap `list-group-item` / `list-group-item-action` CSS rules are overriding the inline styles
2. **Fix the styling**: Ensure the active item is visually distinct with:
   - Visible blue left border accent (3px)
   - Tinted blue background (25% opacity)
   - Dark bold text
   - Colored icon (`text-primary`)
   - Checkmark instead of chevron
3. **Test on Safari**: The user is on Safari — ensure cross-browser compatibility

### Approach Suggestions

**Option A**: Remove `list-group-item` and `list-group-item-action` classes from the `<a>` tag and use custom styling instead. This eliminates Bootstrap's conflicting CSS rules.

**Option B**: Add a wrapper `<div>` with the list-group classes and keep the `<a>` tag with only custom classes.

**Option C**: Use more specific CSS selectors or `!important` on ALL style properties to override Bootstrap.

**Option D**: The `list-group-item` class may have `color` and `background-color` rules that need to be explicitly overridden. Check Bootstrap's compiled CSS for `.list-group-item-action` rules.

### Key Files

| File | Purpose |
|------|---------|
| [`src/Resources/views/livewire/navs/context-sheet.blade.php`](src/Resources/views/livewire/navs/context-sheet.blade.php) | ContextSheet Blade template — sub-item rendering |
| [`src/Http/Livewire/Layouts/Navs/ContextSheet.php`](src/Http/Livewire/Layouts/Navs/ContextSheet.php) | ContextSheet PHP class — `isItemActive()` method |
| [`src/Resources/views/livewire/navs/navigation-hub.blade.php`](src/Resources/views/livewire/navs/navigation-hub.blade.php) | NavigationHub — reference for working active state styling |
| [`src/Resources/views/livewire/navs/partials/sidebar-item.blade.php`](src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) | Desktop sidebar — reference for active detection logic |

### Consuming App Note

The consuming app pulls the library via local path:
```json
"repositories": [{"type": "path", "url": "/Users/mac/Projects/Libraries/ui-library"}],
"require": {"quicker-faster/ui-library": "*@dev"}
```

Changes to the library are immediately available (symlinked). After changes, run:
```bash
cd /Users/mac/Projects/LaravelProjects/hr-consuming-app
rm -f storage/framework/views/*.php
php artisan view:clear
```

Also check for stale published views at `resources/views/vendor/qf/` — delete any that override library files you've modified.

### Verification

After fixing:
1. Open the app on mobile viewport (or resize browser to <768px)
2. Navigate to a context group with sub-items (e.g., People → Employees)
3. Tap the handle bar to open the ContextSheet
4. The active sub-item (e.g., "Employees") should show:
   - Blue left border (3px)
   - Tinted blue background
   - Bold dark text
   - Blue icon
   - Checkmark (not chevron)
5. Other sub-items should show default styling with chevron