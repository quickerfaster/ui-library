# Breadcrumbs

Server-rendered Blade component supporting up to 5 levels: `Application → Workspace → Section → Page → Record`. When the trail exceeds `maxVisible`, the middle segments collapse into a "..." dropdown (vanilla JS, no Alpine).

> **Component class**: [`src/Components/Breadcrumbs.php`](../../src/Components/Breadcrumbs.php)
> **Blade view**: [`src/Resources/views/components/breadcrumbs.blade.php`](../../src/Resources/views/components/breadcrumbs.blade.php)
> **Tag**: `<x-breadcrumbs>`

---

## Constructor Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$segments` | `array` | `[]` | Ordered breadcrumb segments. Each is `['label' => string, 'url' => string|null]`. |
| `$maxVisible` | `int` | `4` | Maximum segments before collapsing the middle into "..." . |
| `$showHome` | `bool` | `true` | Whether to prepend a "Home" segment (also gated by config). |

The `$segments`, `$maxVisible`, and `$showHome` values are exposed as public properties and consumed by the helper methods below.

---

## Collapse Behavior

The class exposes helper methods that the Blade view calls directly:

| Method | Behavior |
|--------|----------|
| `allSegments()` | Returns `$segments`, prepending a "Home" segment when `$showHome` is `true` and `config('ui-library.breadcrumb.show_home', true)` is `true`. Prepending is idempotent — it won't duplicate an existing Home segment. |
| `shouldCollapse()` | `true` when `count(allSegments()) > $maxVisible`. |
| `visibleSegments()` | When collapsed, returns the **first** segment + the **last 2** segments. Otherwise returns all segments. |
| `hiddenSegments()` | When collapsed, returns the middle segments (`array_slice(allSegments(), 1, -2)`) for the "..." dropdown. |

When collapsing, the "..." toggle is rendered at position `1` (between the first segment and the trailing two). The dropdown lists the hidden middle segments.

---

## Usage

```blade
<x-breadcrumbs :segments="$breadcrumbItems" :maxVisible="4" />
```

- `$segments` is a list of `['label' => ..., 'url' => ...]` arrays.
- `url` may be `null` for the current (non-link) segment — the last segment is always rendered as plain text.
- `maxVisible` defaults to `4`; the config key `ui-library.breadcrumb.max_visible` can be read by the caller and passed through.
- `showHome` defaults to `true` and is also gated by `ui-library.breadcrumb.show_home`.

---

## How `NavigationLayout::getBreadcrumbItems()` Builds the 5 Levels

[`NavigationLayout::getBreadcrumbItems()`](../../src/Components/NavigationLayout.php) assembles up to five levels:

| Level | Source | URL |
|-------|--------|-----|
| 1. Home | `config('ui-library.breadcrumb.show_home')` | `url('/')` |
| 2. Application | `resolveModuleLabel()` from the module registry | `resolveModuleUrl()` (module `route` or `url`) |
| 3. Workspace | Active context group `$contextGroups[$activeContext]` | group `route` or `url` |
| 4. Section | `resolveCurrentSection()` via `NavigationManager::getSections()` | first section item's URL (skipped if its label equals the module label) |
| 5. Page / Record | `$currentContextItem` | `page_title` or `label`, with its `route` |

`NavigationLayout` stores the result in its `$breadcrumbItems` property, which the page header passes to `<x-breadcrumbs>`.

---

## Vanilla JS Behavior

The "..." dropdown is toggled by [`public/assets/js/quicker-faster.js`](../../public/assets/js/quicker-faster.js) via `initBreadcrumbDropdowns()`:

- Clicking `[data-breadcrumb-toggle]` toggles the `.show` class on the sibling `[data-breadcrumb-menu]` and updates `aria-expanded`.
- Only one breadcrumb dropdown is open at a time.
- Click-outside or `Escape` closes the dropdown.
- The initialiser is re-run after each Livewire `morph.updated` hook.
