# Debug Checklist — Navigation & UI Bugs

> **Purpose**: Quick-reference guide for diagnosing common navigation, event, and UI bugs in the QuickerFaster UI Library. Each entry maps symptoms → likely causes → fix.
> **Last Updated**: 2026-09-22 (added: ESS DataTable action leakage, invitation company assignment gap, cross-company data leak audit, card/list view checkbox fix, onboarding pre-linked employee handling, employee number collision retry, JobTitle title-vs-name column fix, EmployeeProfile CompanyScope fix, PayrollWizard CompanyScope relationship eager-load fix)

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
| 1 | **Path comparison vs URL comparison** | `trim(request()->path(), '/')` may fail during Livewire re-renders | Use `url()->previous() === url($routePath)` — `request()->url()` returns `/livewire/update` during re-renders (see §10) |
| 2 | **PHP reference leak** from `&$item` foreach | Check if `$items` variable is used after a `&$items` loop without `unset()` | Add `unset($items)` after every `&$items` foreach loop |

**Correct pattern for `isItemActive()`**:
```php
// Use url()->previous() — request()->url() returns /livewire/update
// during Livewire component re-renders (see §10).
$currentUrl = url()->previous();
return $currentUrl === url($routePath);
```

**Correct pattern for PHP references**:
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

## 14. Bootstrap Dropdown Clipped Inside `table-responsive`

### Symptoms
- Dropdown menu is cut off at the bottom edge of a table or card
- `data-bs-boundary="viewport"` on the toggle button has no effect
- Dropdown opens but items below the table edge are invisible

### Root Cause
Bootstrap's `table-responsive` class applies `overflow-x: auto` (or `overflow: hidden`), creating a clipping context. Popper.js cannot escape a parent with `overflow: hidden`, even with `data-bs-boundary="viewport"`.

### Fix
Add `overflow: visible` to the `table-responsive` div:
```html
<div class="table-responsive" style="overflow: visible;">
```

**Trade-off:** This disables horizontal scrolling on very narrow screens. For dashboard widgets with few columns this is acceptable. For data tables with many columns, consider using list/card view modes instead.

### Affected Files
[`list.blade.php`](src/Resources/views/widgets/list.blade.php:8), [`data-table.blade.php`](src/Resources/views/livewire/data-tables/data-table.blade.php:328)

---

## 15. Eloquent `updateQuietly()` Skips Observers — Status Not Updating

### Symptoms
- A related model is saved but the parent model's computed status field doesn't update
- `EmployeeOnboardingObserver::saved()` never fires after position/company changes
- Status stays stuck at old value despite related data changing

### Root Cause
`Model::updateQuietly()` bypasses all Eloquent events (`saving`, `saved`, etc.). If a child model (e.g., `EmployeePosition`) uses `$parent->updateQuietly()` to sync data, the parent's observers never run.

### Fix
**Option A:** Use raw DB queries to update the status directly (bypasses observer chain entirely):
```php
\DB::table('employees')->where('id', $employeeId)->update([
    'onboarding_status' => $hasCompany ? 'complete' : 'position_pending',
]);
```

**Option B:** Use a `saved` event on the child model that always recomputes the parent's status:
```php
static::saved(function (self $position) {
    \DB::table('employees')->where('id', $position->employee_id)->update([...]);
});
```

### Affected Files
[`EmployeePosition.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Models/EmployeePosition.php:352), [`UserCompanyAssignment.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Organization/Http/Livewire/UserCompanyAssignment.php:97)

---

## 16. Computed Status Fields Get Out of Sync — Unreliable for Conditions

### Symptoms
- Row action conditions based on a computed status field don't match reality
- Employee has companies assigned but `onboarding_status` still shows `company_pending`
- Status field is updated by one code path but not another

### Root Cause
Computed fields like `onboarding_status` are set by specific observers/hooks. When related data changes through a different code path (e.g., pivot table sync, external module), the computed field isn't recalculated.

### Fix
**Option A:** Ensure ALL code paths that modify related data also recompute the status (see §15).

**Option B:** Remove conditions from row actions — show all relevant actions and let the target component handle the actual state:
```php
// Instead of conditional actions:
'condition' => ['onboarding_status', '=', 'company_pending'],

// Show all actions unconditionally:
// (no condition key)
```

### Affected Files
[`onboarding_overview.php`](../../LaravelProjects/hr-consuming-app/app/Modules/Hr/Data/dashboards/onboarding_overview.php:93)

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

---

## 14. Advanced Search — Relationship Fields Not Searchable

### Symptoms
- Advanced search panel only shows direct string columns (e.g., `work_email`)
- Relationship fields (e.g., `employee_id` → Employee model) are missing from search checkboxes
- Searching by related model data (employee name, employee number, email) returns no results
- Quick search works for direct columns but advanced search can't search relationships

### Root Cause (3 Layers)

| Layer | File | Issue |
|-------|------|-------|
| 1 | `src/Http/Livewire/SearchPanel.php` `loadColumns()` | `if (isset($def['relationship'])) { continue; }` — relationship fields are explicitly skipped |
| 2 | `src/Http/Livewire/DataTables/DataTable.php` `getRecordsProperty()` | Search loop only does `orWhere()` for direct columns; no `orWhereHas()` branch |
| 3 | `src/Services/Search/SearchEngine.php` `apply()` | Only direct `orWhere` — no relationship support |

### Fix (6 Changes)

1. **`SearchPanel::loadColumns()`**: Include relationship fields with `searchable: true`, using a descriptive label like `"Employee (employee_number, first_name, last_name, email)"`. The `searchable_fields` key in the relationship config controls which related-model columns are searched. Relationship fields require **explicit** `searchable: true` (opt-in); direct fields default to `searchable: true` (backward compatible).

2. **`DataTable::getRecordsProperty()`**: Add a `whereHas` branch before the select/direct field branches:
   ```php
   if (isset($fieldDef['relationship'])) {
       $relationMethod = $this->getRelationMethodFromField($field, $fieldDef);
       $searchableFields = $fieldDef['relationship']['searchable_fields']
           ?? [$this->getRelationDisplayColumn($fieldDef)];
       $q->orWhereHas($relationMethod, function ($subQ) use ($searchableFields, ...) {
           $subQ->where(function ($innerQ) use ($searchableFields, ...) {
               foreach ($searchableFields as $sf) {
                   $innerQ->orWhere($sf, 'like', $searchTerm . '%');
               }
           });
       });
   }
   ```

3. **`SearchEngine::apply()`**: Accept optional `$fieldDefs` parameter for relationship resolution via `whereHas`.

4. **Config `searchable_fields`**: Add `'searchable_fields' => ['employee_number', 'first_name', 'last_name', 'email']` to relationship definitions in the consuming app's data config.

5. **Default selection excludes relationships**: `SearchPanel` tracks `$directColumnNames` and `$relationColumns` separately. `mount()` and `resetSearch()` default to the first 2 **direct** columns only. Relationship checkboxes start **unchecked** — user must explicitly opt in.

6. **UI grouping with divider**: The Blade view renders direct columns first, then a `<hr>` divider with a link icon and italic note: *"Related records — selecting these may slow down search"*, followed by relationship checkboxes. Both the library view and any published vendor copy must be updated.

### `searchable` Flag Semantics

| Field Type | Default | Override |
|-----------|---------|----------|
| Direct (non-relationship) | `true` (searchable) | Set `'searchable' => false` to hide |
| Relationship | `false` (not searchable) | Set `'searchable' => true` to show |

### Performance Design Constraint
- **Quick search** (inline search bar): Direct columns only — `whereHas` is too heavy for default
- **Advanced search** (sliders icon → drawer): User explicitly opts into heavier queries, so `whereHas` is acceptable
- This separation is enforced by `$this->searchableFields` (direct) vs `$this->searchableRelations` (relationship)
- Quick search fallback uses `array_slice($this->searchableFields, 0, 2)` — never includes relationships
- Default selected columns in SearchPanel use `$this->directColumnNames` — never includes relationships

### Verification
```bash
php artisan optimize:clear
# Visit the datatable page
# 1. Quick search: type related data → should NOT filter (correct — quick search is direct only)
# 2. Click sliders icon → verify direct columns appear first, then <hr> divider, then relationship fields
# 3. Relationship checkboxes should be UNCHECKED by default (opt-in)
# 4. Select a relationship checkbox → search → table filters by related model data
# 5. Check published vendor views: resources/views/vendor/qf/livewire/search-panel.blade.php
```

---

## 15. Column Visibility Not Persisting Across Page Refresh

### Symptoms
- User checks additional columns via "View → Columns..." drawer
- Columns appear on the table during the current session
- After page refresh, only default columns (first 6 or `tableDefaultFields`) are visible
- Reopening the ColumnManager drawer shows the extra columns as **checked**, but they're not on the table

### Root Cause

**`HasColumnPreferences::loadVisibleColumns()`** intersects saved columns with the `$allColumns` parameter. Both DataTable call sites passed `$defaultColumns` (first 6 from config) instead of all available columns:

```php
// BEFORE (bug):
$this->visibleColumns = $this->loadVisibleColumns($this->configKey, $defaultColumns);
// Saved: [a, b, c, d, e, f, g, h]
// $defaultColumns: [a, b, c, d, e, f]
// Intersection: [a, b, c, d, e, f]  ← g, h STRIPPED!
```

**Secondary issue**: `ColumnManager` used a different session key (`visible_columns_*`) than `DataTable` (`datatable.columns.*`), causing the drawer to show stale/incorrect initial checkbox state.

### Fix (3 Changes)

1. **`HasColumnPreferences::loadVisibleColumns()`** ([`src/Traits/DataTables/HasColumnPreferences.php`](src/Traits/DataTables/HasColumnPreferences.php:14)): Added optional `$fallback` parameter. Session data is now intersected with `$allColumns` (all available columns), preserving user-added columns. When no session data exists, `$fallback` (config defaults) is used.

2. **Both DataTable call sites** ([`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:227) and line 938): Changed from `loadVisibleColumns($configKey, $defaultColumns)` to `loadVisibleColumns($configKey, array_keys($this->columns), $defaultColumns)`.

3. **`ColumnManager` session key** ([`ColumnManager.php`](src/Http/Livewire/ColumnManager.php:38)): Changed from `visible_columns_{configKey}` to `datatable.columns.{configKey}` — now reads/writes the same session key as DataTable.

### Verification
```bash
php artisan optimize:clear
# 1. Visit a datatable page
# 2. Open "View → Columns..." drawer
# 3. Check additional columns beyond defaults
# 4. Refresh the page → extra columns should still be visible
# 5. Reopen drawer → checkboxes should match table state
```

---

## 16. Bulk & Row Actions Visible Without Permission Checks

### Symptoms
- DataTable embedded in ESS/profile pages shows Delete, Restore, Permanently Delete buttons (both bulk toolbar and per-row)
- Non-admin users can see and click action buttons they shouldn't have access to
- Row action buttons (edit, delete) appear even when user lacks `edit_{entity}` or `delete_{entity}` permissions

### Root Cause

**Bulk actions**: [`parseBulkActions()`](src/Http/Livewire/DataTables/DataTable.php:1331) transforms config into action definitions but never checks permissions. `$this->bulkActions` is passed unfiltered to the Blade view.

**Row actions (simpleActions)**: The `simpleActions` array from config (e.g., `['show', 'edit', 'delete']`) was passed to every row without global permission filtering. While [`row-actions.blade.php`](src/Resources/views/livewire/data-tables/partials/row-actions.blade.php) did check permissions per-record, the array itself was never pre-filtered.

### Fix (2 Methods)

1. **`filterBulkActionsByPermission()`** ([line 1393](src/Http/Livewire/DataTables/DataTable.php:1393)) — called in `initializeFromConfig()` after `parseBulkActions()`:

| Action Type | Permission Check |
|------------|-----------------|
| `delete` | `canBulkDelete()` → `delete_{entity}` |
| `restore` | `canBulkRestore()` → `restore_{entity}` |
| `forceDelete` | `canBulkForceDelete()` → `force_delete_{entity}` |
| `export` | `canBulkExport()` → `export_{entity}` |
| `updateField` | `canBulkUpdate()` → `edit_{entity}` |

2. **`filterSimpleActionsByPermission()`** ([line 1428](src/Http/Livewire/DataTables/DataTable.php:1428)) — called in `render()` after resolving `simpleActions` from config:

| Action | Permission Check |
|--------|-----------------|
| `show` | `canAccessView()` → `view_{entity}` |
| `edit` | `isBypassAllowed() \|\| can('edit_{entity}')` |
| `delete` | `isBypassAllowed() \|\| can('delete_{entity}')` |
| `restore` | `isBypassAllowed() \|\| can('restore_{entity}')` |
| `forceDelete` | `isBypassAllowed() \|\| can('force_delete_{entity}')` |

> **⚠️ `isBypassAllowed()` is critical.** Without it, `company_admin` users lose access to row actions. `bulkActions` and `moreActions` use auth service methods that already include the bypass — only `simpleActions` needed the explicit check.

Both are **coarse-grained pre-filters**. Per-record checks in the Blade views remain as defense-in-depth. `moreActions` already had per-record filtering in `row-actions.blade.php`.

### Verification
```bash
php artisan optimize:clear
# 1. Log in as a non-admin user (e.g., employee without delete permissions)
# 2. Visit a page with an embedded DataTable (e.g., ESS profile → Payslips tab)
# 3. Bulk toolbar: Select checkboxes → should NOT show Delete/Restore/Force Delete
# 4. Row actions: Delete/edit buttons should NOT appear on any row
# 5. Log in as admin → same page → all actions should appear normally
```

---

## 17. ESS/Restricted DataTable Inherits Admin Actions from Config

### Symptoms
- DataTable embedded in ESS/profile page shows admin-only actions (edit, delete, Approve for Payroll, Mark as Resolved, Adjust Attendance)
- Bulk action toolbar shows Delete, Restore, Permanently Delete
- `moreActions` dropdown shows admin-only items despite `requiredRole` in config
- Only happens when the Blade view passes `configKey` but no `simpleActions`/`moreActions` overrides

### Root Cause

The DataTable merge logic in [`DataTable.php:2754-2755`](src/Http/Livewire/DataTables/DataTable.php:2754) uses null-coalescing:

```php
$controls = $this->controlsOverride ?? $resolver->getControls();
$simpleActions = $this->simpleActions ?? ($resolver->getConfig()['simpleActions'] ?? []);
```

When a Blade view passes a **partial** `controls` array (e.g., only `search`, `files`), it **completely replaces** the config controls — which may accidentally hide `bulkActions` but also drops `softDelete`/`restore`/`forceDelete` flags.

When **no** `simpleActions` or `moreActions` override is passed, the DataTable falls back to the **full admin config** values. The `filterSimpleActionsByPermission()` method only blocks actions if the user lacks the corresponding Spatie permission — it has no concept of "self-service mode."

Additionally, `moreActions` entries in configs often define `requiredRole` (e.g., `['payroll_officer', 'hr_admin']`), but [`canPerformAction()`](src/Services/DataTables/DefaultAuthorizationProvider.php:102) **never reads or enforces** `requiredRole`. It only checks `$user->can($action . '_' . $viewName)`.

### Fix (Consuming App — Override in Blade)

Always pass explicit `simpleActions`, `moreActions`, and complete `controls` when embedding a DataTable in a restricted context:

```blade
@livewire('qf.data-table', [
'configKey' => 'attendance.attendance',
'queryFilters' => [['employee_id', '=', $employee->id]],
'simpleActions' => ['show'],          // ← ESS: view-only
'moreActions' => [],                  // ← ESS: no admin actions
'controls' => [
    'search' => true,
    'filterColumns' => false,
    'addButton' => false,
    'editable' => false,
    'showHideColumns' => true,
    'files' => ['export' => ['csv', 'pdf'], 'print' => true],
    'softDelete' => false,            // ← explicitly disable
    'restore' => false,
    'forceDelete' => false,
    'trashView' => false,
    'bulkActions' => [],              // ← explicitly empty
],
])
```

### Diagnostic Logging

The `filterBulkActionsByPermission()` and `filterSimpleActionsByPermission()` methods in [`DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:1400) now emit `[DataTable]` debug logs showing:
- User ID, email, roles, and `isBypassAllowed` status
- Input actions before filtering
- Which actions were blocked and why
- Filtered output actions

Check `storage/logs/laravel.log` for these entries when debugging action visibility issues.

### Verification
```bash
php artisan optimize:clear
# 1. Log in as an employee (non-admin)
# 2. Visit /hr/my-profile?tab=attendance
# 3. Row actions: Only "Show" (view) should appear — no Edit, Delete
# 4. moreActions dropdown: Should be empty or absent — no Approve for Payroll, Mark as Resolved, Adjust Attendance
# 5. Bulk selection: No checkboxes or bulk action toolbar should appear
# 6. Log in as admin → /attendance/attendances → all actions should appear normally
```

---

## 18. CompanyScope on Eager-Loaded Relationships Returns Null

### Symptoms
- `Attempt to read property "first_name" on null` when accessing a relationship (e.g., `$emp->employee->first_name`)
- The parent model is loaded with `withoutCompanyScope()`, but the relationship still applies `CompanyScope`
- Occurs when the session company differs from the related model's `company_id`

### Root Cause
`withoutCompanyScope()` only bypasses the scope on the **parent** query. When a relationship (e.g., `belongsTo`) is lazy-loaded or eager-loaded without explicit scope bypass, `CompanyScope` still filters the related model. If the session company doesn't match, the relationship returns `null`.

### Fix Pattern
Always eager-load relationships **with** `withoutGlobalScopes()` when the parent query uses `withoutCompanyScope()`:

```php
// ❌ WRONG — relationship still scoped
$positions = EmployeePosition::withoutCompanyScope()->get();
// $positions[0]->employee → null (CompanyScope mismatch)

// ✅ CORRECT — relationship bypasses scope too
$positions = EmployeePosition::withoutCompanyScope()
    ->with(['employee' => fn ($q) => $q->withoutGlobalScopes()])
    ->get();
// $positions[0]->employee → loaded correctly
```

### Known Affected Files
| File | Fix |
|------|-----|
| [`PayrollWizardAdjustments.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardAdjustments.php:128) | Added `->with(['employee' => fn ($q) => $q->withoutGlobalScopes()])` to `getEmployeesProperty()` |
| [`EmployeeDetail.php`](app/Modules/Hr/Http/Livewire/EmployeeDetail.php:205) | Loads `employeeProfile` with `withoutGlobalScopes()` |
| [`EmployeeDetail.php`](app/Modules/Hr/Http/Livewire/EmployeeDetail.php:272) | Resolves FK display names with `withoutGlobalScopes()->withTrashed()` |
| [`my-portal.blade.php`](app/Modules/Hr/Resources/views/hr/my-portal.blade.php:17) | Dashboard card uses `withoutGlobalScopes()` for employee lookup |
| [`Step1EmployeeRecord.php`](app/Modules/Hr/Http/Livewire/Onboarding/Steps/Step1EmployeeRecord.php) | Uses `withoutCompanyScope()` for employee lookup |

### Audit Checklist
When using `withoutCompanyScope()` on a query, audit all relationships accessed from the results:
1. Check the Blade view for `$model->relationship->attribute` accesses
2. Check the component for lazy-loaded relationship accesses
3. If the related model uses `HasCompanyScope`, eager-load with `withoutGlobalScopes()`