# WorkspaceTabs

Browser-style workspace tabs that let users keep multiple pages open simultaneously. Tabs persist in the PHP session, survive page refreshes, and are managed through Livewire server state with vanilla JS client interactions.

> **Component class**: [`src/Http/Livewire/Layouts/Navs/WorkspaceTabs.php`](../../src/Http/Livewire/Layouts/Navs/WorkspaceTabs.php)
> **Blade view**: [`src/Resources/views/livewire/navs/workspace-tabs.blade.php`](../../src/Resources/views/livewire/navs/workspace-tabs.blade.php)
> **Client JS**: [`public/assets/js/quicker-faster.js`](../../public/assets/js/quicker-faster.js)
> **Alias**: `qf.workspace-tabs`

---

## Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$openTabs` | `array` | `[]` | Ordered list of open tab objects: `{ id, label, url, icon, context }`. |
| `$activeTabId` | `?string` | `null` | ID of the currently active tab. |
| `$recentlyClosed` | `array` | `[]` | Last 10 closed tabs, used by "Reopen closed tab". |
| `$maxTabs` | `int` | `15` | Maximum number of open tabs before the least-recently-used tab is evicted. |

---

## `#[On]` Handlers

The component reacts to browser events dispatched by [`quicker-faster.js`](../../public/assets/js/quicker-faster.js) via `Livewire.dispatch()`. Handlers are declared with Livewire's `#[On('event-name')]` attribute.

| Dispatched Event | Handler | Description |
|------------------|---------|-------------|
| `switch-tab` | `switchTab($tabId)` | Activates the given tab. |
| `close-tab` | `closeTab($tabId)` | Removes the tab and activates the adjacent (right-neighbour, else last) tab. |
| `close-active-tab` | `closeActiveTab()` | Closes the currently active tab. |
| `reopen-last-closed-tab` | `reopenLastClosed()` | Reopens the most recently closed tab (focuses it if already open). |
| `openWorkspaceTab` | `openTab($label, $url, $icon = null, $context = null)` | Opens a new tab, deduplicating by URL. |
| `close-others` | `closeOthers($keepTabId)` | Closes every tab except the given one. |
| `close-all-to-right` | `closeAllToRight($tabId)` | Closes all tabs to the right of the anchor tab. |
| `close-all` | `closeAll()` | Closes all tabs. |

---

## Session Persistence

On `mount()`, the component hydrates from three session keys and writes them back on every mutation via `persist()`:

| Session Key | Description |
|-------------|-------------|
| `workspace_tabs` | The ordered `$openTabs` array. |
| `workspace_active_tab` | The `$activeTabId`. |
| `workspace_recently_closed` | The `$recentlyClosed` array (capped at 10). |

Because state lives in the session rather than the DOM, tabs survive page refreshes and Livewire component re-mounts.

---

## Usage

```blade
<livewire:qf.workspace-tabs />
```

The component is rendered by the navigation layout between TopNav and the main content area. When `ui-library.navigation.open_in_tabs` is `true`, sidebar items carry `data-workspace-tab` attributes and dispatch `openWorkspaceTab` instead of navigating.

---

## Vanilla JS Behavior

All tab interactivity lives in [`public/assets/js/quicker-faster.js`](../../public/assets/js/quicker-faster.js) and is initialised through the `initWorkspaceTabs()` function (re-run after each `morph.updated` hook). Interactions are wired via **event delegation** on the `[data-tab-container]` element.

| Interaction | Trigger | Result |
|-------------|---------|--------|
| **Click** | click on `[data-tab-item]` | `Livewire.dispatch('switch-tab', { tabId })` |
| **Close** | click on `[data-tab-close]` | `Livewire.dispatch('close-tab', { tabId })` |
| **Middle-click** | `auxclick` with `button === 1` | `Livewire.dispatch('close-tab', { tabId })` |
| **Right-click** | `contextmenu` on `[data-tab-item]` | Shows the `[data-tab-context-menu]` at the cursor position, kept in-viewport |
| **Overflow** | `ResizeObserver` on `[data-tab-strip]` | Measures tabs, hides excess into `[data-tab-overflow-menu]` chevron |
| **Keyboard** | global `keydown` | Ctrl/Cmd+W → `close-active-tab`; Ctrl+Shift+T → `reopen-last-closed-tab` |

The context menu actions are declared with `data-tab-action` values: `close`, `close-others`, `close-all-to-right`, `close-all`. The menu closes on click-outside or `Escape`.

### Edge cases

| Edge case | Behavior |
|-----------|----------|
| Max tabs exceeded (15) | Evicts the least-recently-used non-active tab. |
| Tab URL already open | Focuses the existing tab instead of duplicating. |
| All tabs closed | Strip is hidden (`display: none`) until the next tab opens. |
| Browser refresh | Tabs restored from session on next `mount()`. |
| Long labels / narrow screens | CSS truncation + overflow chevron. |
