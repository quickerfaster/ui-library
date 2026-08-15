# Sidebar Filter

Real-time, client-side fuzzy search for the sidebar navigation. Filtering happens entirely in the browser — no server round-trip, no Livewire request per keystroke.

> **View**: [`src/Resources/views/livewire/navs/sidebar.blade.php`](../../src/Resources/views/livewire/navs/sidebar.blade.php)
> **Item partial**: [`src/Resources/views/livewire/navs/partials/sidebar-item.blade.php`](../../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php)
> **Section partial**: [`src/Resources/views/livewire/navs/partials/sidebar-section.blade.php`](../../src/Resources/views/livewire/navs/partials/sidebar-section.blade.php)
> **Client JS**: [`public/assets/js/quicker-faster.js`](../../public/assets/js/quicker-faster.js)

---

## Purpose

The sidebar can hold dozens of navigation items. The filter adds a search input at the top of the sidebar that hides non-matching items in real time. It is a pure client-side enhancement: it reads `data-filter-text` from the DOM and toggles `display` on `[data-filterable]` elements. No Alpine directives are used.

---

## Data Attributes

| Attribute | Element | Purpose |
|-----------|---------|---------|
| `data-sidebar-filter-wrap` | wrapper `<div>` | Scope marker that the JS initialiser keys off. |
| `data-sidebar-filter` | `<input>` | The search input. |
| `data-sidebar-filter-icon` | icon `<span>` | Search icon (styled on focus). |
| `data-sidebar-filter-clear` | `<button>` | Clear button (shown when there is a query). |
| `data-sidebar-filter-no-results` | `<div>` | "No results" message (shown when nothing matches). |
| `data-filterable` | sidebar items, section headers and section labels | Marks an element as filterable (nav items, collapsible section headers, and static section labels). |
| `data-filter-text` | same `[data-filterable]` elements | Lowercased searchable text (label + key + active context). |

The filter scope is resolved as the closest `.sidebar-container` ancestor (falling back to the wrapper's parent or `document`), so the filterable items outside the input wrapper are still included.

### Where `data-filterable` lives

The filterable attributes are placed on the correct partials:

- [`sidebar-item.blade.php`](../../src/Resources/views/livewire/navs/partials/sidebar-item.blade.php) — each navigation `<li>` item.
- [`sidebar-section.blade.php`](../../src/Resources/views/livewire/navs/partials/sidebar-section.blade.php) — both the collapsible section header and the static section label.
- [`sidebar.blade.php`](../../src/Resources/views/livewire/navs/sidebar.blade.php) — the inline static section label rendered when a context group has a non-collapsible header.

---

## Matching Algorithm

- **Word-based**: the query is split on whitespace; every word must `indexOf`-match the element's `data-filter-text`.
- **Case-insensitive**: both query and text are lowercased.
- **Debounced**: filtering applies 150ms after the last keystroke via the shared `debounce()` utility.

```
Query: "emp loc"
Matches: "employee locations"   (both words found)
No match: "employees"           (only one word found)
```

Matches are shown; non-matches get `display: none` and a `filter-hidden` class.

---

## Keyboard Navigation

| Key | Behavior |
|-----|----------|
| ArrowDown | Move the active highlight to the next visible item (`sidebar-filter-active`). |
| ArrowUp | Move the active highlight to the previous visible item. |
| Enter | Activate the highlighted item (click its link). |
| Escape | Clear the filter, restore all items, and blur the input. |
| Ctrl+K / Cmd+K | Global shortcut: focuses and selects the filter input (skipped when already in a form field). |

The arrow-key navigation wraps and scrolls the highlighted item into view.

---

## SPA Navigation (`wire:navigate`) Survival

The filter listeners (`input`, `click`, `keydown`) are registered with **document-level event delegation**, so they keep working after Livewire swaps the sidebar DOM during a `wire:navigate` SPA navigation (which does not re-fire `livewire:initialized`). A separate `livewire:navigated` listener re-runs `initSidebarFilter()`, re-applying the current query and hidden state to the freshly rendered sidebar DOM.

This means the filter — including any active query — survives SPA navigation.

---

## Config / Translation Keys

The filter is always rendered (no on/off config). Behavioural config relevant to the sidebar:

| Key | Default | Relevance |
|-----|---------|-----------|
| `ui-library.navigation.open_in_tabs` | `false` | When `true`, activating a filtered item opens a workspace tab instead of navigating. |

Translation keys in [`public/lang/en/nav.php`](../../public/lang/en/nav.php):

| Key | Default |
|-----|---------|
| `filter_modules` | `Search menu...` (Spanish: `Buscar menú...`) |
| `no_results` | `No matching items` (Spanish: `Sin resultados`) |
