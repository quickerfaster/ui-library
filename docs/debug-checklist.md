# Debug Checklist — Navigation & UI Bugs

> **Purpose**: Quick-reference guide for diagnosing common navigation, event, and UI bugs in the QuickerFaster UI Library. Each entry maps symptoms → likely causes → fix.

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