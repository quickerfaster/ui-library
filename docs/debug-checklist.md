# Debug Checklist — Navigation & UI Bugs

> **Purpose**: Quick-reference guide for diagnosing common navigation, event, and UI bugs in the QuickerFaster UI Library. Each entry maps symptoms → likely causes → fix.
> **Last Updated**: 2026-09-18 (added: request()->url() during Livewire updates, wire:navigate + Bootstrap conflict, <style> in Livewire components, renderVersion bump)

---

## 1. Livewire `wire:click` Does Nothing (No Network Request)

### Symptoms
- Clicking a link/button with `wire:click` produces no reaction
- Browser DevTools Network tab shows **no POST to `/livewire/update`**
- No JavaScript errors in console
- `wire:click` attribute IS present in page source

### Likely Causes (check in order)

| # | Cause | How to Check | Fix |
|---|-------|-------------|-----|
| 1 | **Multiple root elements** in Blade template | View page source — if the component renders `<style>...<div>...` as siblings, Livewire 3 can't bind events | Wrap everything in a single root `<div>` |
| 2 | `wire:ignore.self` on the same element | Search page source for `wire:ignore.self` on the clicked element | Remove `wire:ignore.self` from the element |
| 3 | `wire:ignore` on a parent element | Check parent elements for `wire:ignore` | Remove or restructure |
| 4 | Component not mounted | Check if `<livewire:...>` tag is present and rendered | Verify component registration in ServiceProvider |

---

## 2. Livewire Event Dispatch Not Received

### Symptoms
- `$this->dispatch('eventName', ...)` is called (confirmed via log)
- Listener component's handler method never executes
- `dd()` in handler never fires

### Likely Causes

| # | Cause | How to Check | Fix |
|---|-------|-------------|-----|
| 1 | **Named parameter conflict** with `dispatch()` | PHP error: "Named parameter $params overwrites previous argument" | Use **positional** parameters: `$this->dispatch('event', $arg1, $arg2, $arg3)` |
| 2 | Listener not registered | Check `$listeners` array in target component | Add `'eventName' => 'handlerMethod'` |
| 3 | Components in different Livewire trees | Both components must be on the same page | Ensure both `<livewire:...>` tags are in the same layout |

**Correct dispatch pattern** (positional):
```php
$this->dispatch('openDrawer', 'qf.settings-panel', [
    'mode' => 'company',
    'context' => $contextKey,
], $title);
```

---

## 3. Active State / `isItemActive()` Returns `false` for All Items

### Symptoms
- Navigation item that should be highlighted is not
- Debug shows `$isActive = false` for every item
- Page URL is correct

### Likely Causes

| # | Cause | How to Check | Fix |
|---|-------|-------------|-----|
| 1 | **Path comparison vs URL comparison** | `trim(request()->path(), '/')` may fail during Livewire re-renders | Use `request()->url() === url($routePath)` (full URL comparison) |
| 2 | **PHP reference leak** from `&$item` foreach | Check if `$items` variable is used after a `&$items` loop without `unset()` | Add `unset($items)` after every `&$items` foreach loop |

**Correct pattern**:
```php
foreach ($this->contextItems as $group => &$items) {
    $items = $someFilter($items);
}
unset($items); // ← CRITICAL: break reference before next loop
```

---

## 4. PHP Reference Bug — Wrong Data in Array

### Symptoms
- Array contains data from a different key (e.g., `$contextItems['onboarding']` contains Manage items)
- Data corruption that depends on array key order
- Last element in array gets overwritten

### Root Cause
After a `foreach ($array as $key => &$value)` loop, `$value` remains a **reference** to the last element. A subsequent `foreach ($array as $key => $value)` (without `&`) **overwrites** the referenced element on each iteration.

### Fix
```php
foreach ($array as $key => &$value) {
    // ... modify $value
}
unset($value); // ← ALWAYS unset after &$value loops
```

---

## 5. Bootstrap `!important` Conflicts

### Symptoms
- Inline `display:none` doesn't hide element
- Element stays visible despite inline style
- Bootstrap class like `d-flex` is on the element

### Root Cause
Bootstrap's `d-flex` uses `display: flex !important`. Inline `style="display:none"` (without `!important`) cannot override a stylesheet `!important` rule.

### Fix
**Option A**: Use a CSS class without `!important`:
```css
.my-btn { display: flex; }  /* NO !important */
```
```html
<button class="my-btn" style="display:none">  <!-- inline beats stylesheet -->
```

**Option B**: If JS toggles visibility, ensure JS sets `display` without `!important` conflict:
```javascript
// JS removes inline style → CSS class takes over
el.style.display = '';  // show (CSS class applies)
el.style.display = 'none';  // hide (inline beats CSS)
```

---

## 6. UI Element Missing After Refactor

### Symptoms
- Feature that used to work is now broken
- Click handler exists but no UI appears
- Recent refactor/cleanup commit touched the file

### How to Diagnose
```bash
# Find when the feature was removed
git log --oneline -- path/to/file.blade.php

# View the file before the refactor
git show <commit>^:path/to/file.blade.php | grep -A 50 "feature-name"
```

### Common Scenario
A "simplify/refactor" commit removed UI elements (offcanvas, drawer, modal) while leaving the trigger button and PHP handler intact. The button still calls the method, but there's no UI to show.

---

## 7. Sidebar/Container Overflow (Content Clipped)

### Symptoms
- Search box, button, or text extends beyond container edge
- Right side of element is hidden/cut off

### Likely Causes

| # | Cause | Fix |
|---|-------|-----|
| 1 | Bootstrap `input-group` has intrinsic min-width | Replace with `d-flex` + `min-width: 0` on input |
| 2 | Container lacks `overflow: hidden` | Add `overflow: hidden` to parent |
| 3 | Flex child won't shrink | Add `min-width: 0` to the flex child |

---

## 8. Stale Published Views Override Library Fixes

### Symptoms
- Fix applied to library but not visible in consuming app
- Page source shows old HTML despite library changes

### Check
```bash
# Find published views that override library
find resources/views/vendor/qf -name "*.blade.php" | grep -i "component-name"

# If found, either:
# 1. Apply the same fix to the published copy
# 2. Delete the published copy if it's no longer needed
```

### Fix Commands
```bash
# Always clear after changes
php artisan optimize:clear
rm -rf storage/framework/views/*
composer dump-autoload  # if PHP classes changed
```

---

## 9. Offcanvas/Drawer Opens Without Animation (Instant)

### Symptoms
- Drawer/offcanvas appears instantly with no slide transition
- Other drawers on the same page animate correctly
- Drawer uses `@if ($showDrawer)` conditional rendering

### Root Cause
Livewire conditional rendering (`@if`) adds/removes elements from DOM instantly. Bootstrap Offcanvas CSS transitions only work when the element stays in the DOM and its visibility is toggled via JS.

### Fix
Use Bootstrap Offcanvas JS pattern (same as global Drawer):
1. Always render the offcanvas in DOM (remove `@if`)
2. Initialize with `new bootstrap.Offcanvas(el, { backdrop: true })`
3. Use `Livewire.on('event-name', () => bsOffcanvas.show())` to trigger
4. Use `data-bs-dismiss="offcanvas"` on close button

---

## 10. `request()->url()` Returns `/livewire/update` During Livewire Re-renders

### Symptoms
- `isItemActive()` always returns `false` for all items
- Active link highlighting never works in ContextSheet or BottomBar overflow
- Sidebar highlighting works fine (renders during initial page load)
- Log shows `currentUrl: "https://app.test/livewire/update"` instead of the page URL

### Root Cause
When a Livewire component re-renders (e.g., `open()` on ContextSheet, `openOverflow()` on BottomBar), the HTTP request goes to `/livewire/update`. `request()->url()` returns this endpoint URL, which will **never** match any page URL.

### Fix
Use `url()->previous()` instead of `request()->url()`:
```php
// Before (broken — returns /livewire/update during re-renders)
$currentUrl = request()->url();

// After (fixed — returns the actual page URL)
$currentUrl = url()->previous();
```

### Affected Files
[`ContextSheet::isItemActive()`](src/Http/Livewire/Layouts/Navs/ContextSheet.php:83), [`BottomBar::isItemActive()`](src/Http/Livewire/Layouts/Navs/BottomBar.php:71)

---

## 11. `wire:navigate` + Bootstrap 5 Dropdown Incompatibility

### Symptoms
- TopNav dropdowns alternately work and stop working after clicking BottomBar tabs
- Pattern is deterministic: work → broken → work → broken on each navigation
- `[TopNav] mount()` log shows new `component_id` on every navigation

### Root Cause
`wire:navigate` destroys and recreates the TopNav component. Bootstrap 5 stores dropdown instances in an internal `Map` — all state is lost. Five re-init strategies failed.

### Fix
Remove `wire:navigate` from links that coexist with Bootstrap dropdowns. Use standard `<a href>` with full page loads. See [`sidebar-active-state-pitfalls.md`](./sidebar-active-state-pitfalls.md) §7.

---

## 12. CSS in `<style>` Tags Lost During Livewire Morphing

### Symptoms
- Hover effects or custom styles stop working after a Livewire component re-renders
- Styles were defined in a `<style>` tag inside the Blade template
- Styles work on initial page load but disappear after component update

### Root Cause
`<style>` tags inside Livewire component templates are removed and re-injected during DOM morphing. The browser loses the CSS rules during the transition.

### Fix
Move all styles to the static CSS file ([`quicker-faster.css`](public/assets/css/quicker-faster.css)). Never put `<style>` tags inside Livewire Blade templates.

---

## 13. `renderVersion` Must Be Bumped After Blade Structural Changes

### Symptoms
- Changes to a Livewire component's Blade template have no effect
- Old DOM structure persists despite clearing views
- Component seems "stuck" on an old version

### Root Cause
Livewire caches component snapshots. Structural Blade changes (adding/removing elements, changing root structure) require a snapshot regeneration.

### Fix
Increment the `$renderVersion` property on the component:
```php
/** @var int Bump to force Livewire snapshot regeneration. */
public int $renderVersion = 4;  // was 3
```
This is used in [`ContextSheet`](src/Http/Livewire/Layouts/Navs/ContextSheet.php:36). Other components can add this property if needed.

---

## Quick Diagnostic Flow

```
Bug: Click does nothing
├─ Network request made? (DevTools → Network)
│  ├─ YES → Check Laravel log for errors
│  │  ├─ Method called? → Check dispatch syntax (named vs positional)
│  │  └─ Method not called? → Check $listeners array
│  └─ NO → wire:click not firing
│     ├─ Multiple root elements? → Wrap in single <div>
│     ├─ wire:ignore.self on element? → Remove it
│     └─ wire:ignore on parent? → Remove or restructure
│
Bug: Wrong data displayed
├─ Check for &$value foreach without unset()
├─ Check for path vs URL comparison in active detection
└─ Check for @props shadowing component properties