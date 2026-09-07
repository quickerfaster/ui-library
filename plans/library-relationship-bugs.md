# Library Relationship Bugs

## Bug 1: `hasMany` uses `sync()` — will throw `BadMethodCallException`

- **Location**: [`src/Http/Livewire/DataTables/DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php:1356), `syncRelationships()` method, line ~1356
- **Root Cause**: The `in_array()` check on line 1356 includes `hasMany` alongside `belongsToMany`, `morphMany`, and `morphToMany`. However, `HasMany` relationships in Eloquent do **not** have a native `sync()` method — `sync()` is a `BelongsToMany`-only method. Calling `$record->$dynamicProp()->sync($ids)` on a `hasMany` relationship will throw `BadMethodCallException`.
- **Reproduction Steps**:
  1. Configure a DataTable with a field that has a `hasMany` relationship type
  2. Open the form and save/submit the record
  3. `syncRelationships()` will attempt to call `sync()` on the `hasMany` relation
  4. Eloquent throws `BadMethodCallException: Call to undefined method`
- **Impact**: Critical — any DataTable form that includes a `hasMany` relationship field will crash on save. This is a latent bug waiting to manifest when a consuming app configures a `hasMany` relationship in a form definition.
- **Recommended Fix**: Remove `hasMany` from the `in_array()` check on line 1356, or implement a custom sync for `hasMany` that handles the attach/detach pattern correctly (since `hasMany` uses `save()`/`delete()` semantics, not pivot table operations).

### Current code (line 1356):
```php
if (in_array($type, ['belongsToMany', 'hasMany', 'morphMany', 'morphToMany'])) {
```

### Recommended fix:
```php
if (in_array($type, ['belongsToMany', 'morphMany', 'morphToMany'])) {
```

---

## Bug 2: No pivot extra data support in `syncRelationships()`

- **Location**: [`src/Http/Livewire/DataTables/DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php:1359), `syncRelationships()` method, line ~1359
- **Root Cause**: `$record->$dynamicProp()->sync($ids)` passes only an array of IDs. Laravel's `sync()` method supports pivot data in the form `sync([1 => ['expires_at' => '...'], 2 => [...]])`. If a pivot table has additional columns (e.g., `quantity`, `notes`, `timestamps`, `is_primary`), there is currently no mechanism to capture or persist them.
- **Reproduction Steps**:
  1. Configure a `belongsToMany` relationship with a pivot table that has extra columns (e.g., `company_user` with `is_primary`, `role`, `joined_at`)
  2. The form saves the relationship but all pivot extra columns are left at their default values
  3. Data loss occurs silently — pivot records are created but extra columns are not populated
- **Impact**: High — any consuming app that uses pivot tables with extra data will silently lose that data on every save through `DataTableForm`. This creates data integrity issues that are difficult to detect.
- **Recommended Fix**: Extend the `relationship` config schema to support `pivot_fields` and pass them to `sync()`:

### Current code (line 1359):
```php
$record->$dynamicProp()->sync($ids);
```

### Recommended fix:
```php
$pivotData = [];
if (!empty($rel['pivot_fields'])) {
    foreach ($ids as $id) {
        $pivotData[$id] = [];
        foreach ($rel['pivot_fields'] as $pivotField) {
            $pivotData[$id][$pivotField] = $this->fields[$field . '_' . $pivotField][$id] ?? null;
        }
    }
    $record->$dynamicProp()->sync($pivotData);
} else {
    $record->$dynamicProp()->sync($ids);
}
```

This would require a corresponding config schema extension in the relationship definition:

```php
'relationship' => [
    'type' => 'belongsToMany',
    'model' => Company::class,
    'pivot_fields' => ['is_primary', 'role', 'joined_at'],  // NEW
],
```

---

## Bug 3: `company_id` field always visible on forms

- **Location**: [`src/Http/Livewire/DataTables/DataTableForm.php`](src/Http/Livewire/DataTables/DataTableForm.php:63), `loadConfiguration()` method; and [`src/Http/Livewire/Wizards/WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php:56), `loadConfiguration()` method
- **Root Cause**: The `loadConfiguration()` methods in both `DataTableForm` and `WizardForm` contained an `if` block that handled the "All Companies" case — when the session's `current_company_id` is `0` (meaning no company filter is active), the `company_id` field is shown so the user can choose which company the record belongs to. However, the corresponding `else` block was **missing**. When a specific company was selected (`current_company_id > 0`), the code should have added `company_id` to the `hiddenFields` array (with the selected company's ID as its value), but the `else` clause to do so did not exist. The same pattern was missing in both files' `validateFields()` methods.
- **Reproduction Steps**:
  1. Log in as a user with a specific company selected (not "All Companies")
  2. Open any DataTable form or Wizard form that has a `company_id` field
  3. The `company_id` field is visibly rendered as an editable field, even though the company is already determined by the session context
  4. Users can see and potentially modify the company assignment when they should not be able to
- **Impact**: Medium — the `company_id` field was always visible on all forms regardless of the current company context. When a specific company was selected in the switcher, the `company_id` field should have been hidden and auto-populated with that company's ID. Instead, it appeared as an editable field, creating confusion and a potential data integrity risk if users modified it.
- **Recommended Fix** (already applied): Added `else` blocks in both `loadConfiguration()` and `validateFields()` in both files. When `current_company_id > 0`, the `company_id` field is added to the `hiddenFields` array with the selected company's ID as its value. This ensures the field is not rendered in the UI but is still submitted with the form data.

### DataTableForm fix pattern:

```php
// loadConfiguration() — existing if block (kept):
if (session('current_company_id', -1) == 0) {
    $this->hiddenFields = array_diff($this->hiddenFields, ['company_id']);
}

// NEW else block (added):
else {
    $this->hiddenFields[] = 'company_id';
    $this->fields['company_id'] = session('current_company_id');
}
```

### WizardForm fix pattern:

Same structural fix — added `else` block in `loadConfiguration()` to hide `company_id` and set its value from the session, and corresponding `else` blocks in `validateFields()` in both files to skip validation rules for hidden `company_id` fields.

---

---

## Bug 4: Extra permissions not rendered in AccessControlManager UI

- **Location**: [`src/Services/AccessControl/AccessControlManager.php`](src/Services/AccessControl/AccessControlManager.php), `manageAccessControl()` method
- **Root Cause**: The `manageAccessControl()` method used `ModelDiscovery` to scan model directories for permission definitions, but it **never read the `extra` key** from `permissions.php` config files. Any permissions defined outside of model files (e.g., `manage_user_company_assignments`, custom module permissions) were silently ignored.
- **Reproduction Steps**:
  1. Define an `extra` permissions array in any module's `permissions.php` config file
  2. Navigate to the AccessControlManager UI (role permission management)
  3. The extra permissions do not appear in the permission list for any role
  4. Only model-discovered permissions are shown
- **Impact**: High — any permission not tied to a model file was invisible in the UI. This meant roles could not be granted these permissions through the admin interface, effectively making them unusable. The `manage_user_company_assignments` permission was affected by this bug.
- **Recommended Fix** (applied): Added logic in `manageAccessControl()` to read the `extra` key from each module's `permissions.php` config and merge the entries into the `$models` array under a synthetic `_extra` key. Each extra permission entry needs `id`, `label`, `description`, and `icon` fields to match the structure expected by the Blade template.

### Fix pattern:
```php
// In manageAccessControl(), after model discovery:
foreach ($modules as $module) {
    $permissionsConfig = config("{$module}.permissions");
    if (!empty($permissionsConfig['extra'])) {
        foreach ($permissionsConfig['extra'] as $extra) {
            $models['_extra']['permissions'][] = $extra;
        }
    }
}
```

---

## Bug 5: Extra permissions Blade section nested inside conditional

- **Location**: [`src/Services/AccessControl/resources/views/livewire/access-control-manager.blade.php`](src/Services/AccessControl/resources/views/livewire/access-control-manager.blade.php), extra permissions section
- **Root Cause**: The Blade template had the extra permissions rendering block nested inside a `@if (count($models) > 0)` conditional. When no models were discovered (e.g., a fresh install with no custom modules), `count($models)` was 0, and the entire extra permissions section was skipped — even if `extra` permissions were defined in config files.
- **Reproduction Steps**:
  1. Define extra permissions in `permissions.php` but have no model-discovered permissions
  2. Navigate to the AccessControlManager UI
  3. The extra permissions section does not render at all
  4. Fixing Bug 4 alone does not resolve this — the data is collected but the template never renders it
- **Impact**: Medium — compounded with Bug 4. Even after fixing the backend to collect extra permissions, they would not render if no model permissions existed. This affected fresh installs and modules with only extra permissions.
- **Recommended Fix** (applied): Moved the extra permissions Blade section **outside** the `@if (count($models) > 0)` conditional so it renders independently. The extra permissions section now has its own `@if (!empty($extraPermissions))` guard.

### Fix pattern:
```blade
{{-- Model-based permissions (inside conditional) --}}
@if (count($models) > 0)
    @foreach ($models as $modelKey => $modelData)
        {{-- render model permissions --}}
    @endforeach
@endif

{{-- Extra permissions (OUTSIDE conditional, independent) --}}
@if (!empty($extraPermissions))
    <div class="card mt-4">
        <div class="card-header">
            <h6>Additional Permissions</h6>
        </div>
        <div class="card-body">
            @foreach ($extraPermissions as $permission)
                {{-- render extra permission toggle --}}
            @endforeach
        </div>
    </div>
@endif
```

---

## Bug 6: Extra permissions toggle missing required attributes

- **Location**: [`src/Services/AccessControl/resources/views/livewire/access-control-manager.blade.php`](src/Services/AccessControl/resources/views/livewire/access-control-manager.blade.php), `ToggleButtonGroup` for extra permissions
- **Root Cause**: The `ToggleButtonGroup` Blade component for extra permissions was missing two critical attributes:
  1. **`stateSyncMethod="method"`** — The [`ToggleButtonListener`](src/Listeners/ToggleButtonListener.php) requires this attribute to know which Livewire method to call for state synchronization. Without it, the listener receives the toggle event but cannot persist the change.
  2. **`selectedScope` in `:data`** — The `:data` JSON payload must include a `selectedScope` key that identifies which permission is being toggled. Without it, the listener doesn't know which permission to update.
- **Reproduction Steps**:
  1. Fix Bugs 4 and 5 so extra permissions appear in the UI
  2. Click a toggle button for an extra permission
  3. The toggle visually changes in the UI (client-side)
  4. But the change is never persisted — refreshing the page reverts the toggle
  5. No error is thrown; the failure is silent
- **Impact**: High — even after extra permissions were visible (Bugs 4 and 5 fixed), toggling them had no effect. The UI appeared to work but changes were never saved to the database.
- **Recommended Fix** (applied): Added `stateSyncMethod="method"` attribute and included `selectedScope` in the `:data` JSON payload for extra permission toggles. Also ensured the full button structure (icon, label, description) matched the model-based permission pattern.

### Fix pattern:
```blade
{{-- BEFORE (broken) --}}
<x-qf::toggle-button-group
    :data="json_encode(['role_id' => $role->id])"
    ...
/>

{{-- AFTER (fixed) --}}
<x-qf::toggle-button-group
    stateSyncMethod="method"
    :data="json_encode(['selectedScope' => $permission['id'], 'role_id' => $role->id])"
    ...
/>
```

---

## Bug 7: Published vendor override masking library Blade fixes

- **Location**: `resources/views/vendor/qf/livewire/access-control-manager.blade.php` (consuming app)
- **Root Cause**: The consuming app had published the `access-control-manager.blade.php` view to `resources/views/vendor/qf/`. Laravel's view resolution gives published views **absolute priority** over library source views. After fixing Bugs 5 and 6 in the library source, the consuming app was still rendering the stale published override — making it appear as though the fixes had no effect.
- **Reproduction Steps**:
  1. Fix Bugs 5 and 6 in the library source Blade file
  2. Navigate to the AccessControlManager UI in the consuming app
  3. The old, broken template still renders
  4. Run `php artisan view:clear` — no change
  5. Check `resources/views/vendor/qf/` — the published override is masking the fix
- **Impact**: High — this masked all Blade-level fixes and caused significant debugging confusion. The library source was correct, but the consuming app never saw it.
- **Recommended Fix** (applied): Deleted the published override at `resources/views/vendor/qf/livewire/access-control-manager.blade.php` and re-published from the corrected library source using `php artisan vendor:publish --tag=qf-core-views --force`. Always check for published overrides when Blade changes don't take effect.

---

## Summary

| Bug | Severity | Status | Affected Method / File |
|-----|----------|--------|------------------------|
| Bug 1: `hasMany` uses `sync()` | Critical | Open | `syncRelationships()` |
| Bug 2: No pivot extra data support | High | Open | `syncRelationships()` |
| Bug 3: `company_id` always visible on forms | Medium | Fixed | `loadConfiguration()`, `validateFields()` |
| Bug 4: Extra permissions not rendered in UI | High | Fixed | `AccessControlManager::manageAccessControl()` |
| Bug 5: Extra permissions Blade nested in conditional | Medium | Fixed | `access-control-manager.blade.php` |
| Bug 6: Extra permissions toggle missing attributes | High | Fixed | `access-control-manager.blade.php` (ToggleButtonGroup) |
| Bug 7: Published vendor override masking fixes | High | Fixed | `resources/views/vendor/qf/` (consuming app) |

Bugs 1 and 2 are in the same method and should be addressed together to avoid introducing merge conflicts. Bug 1 will cause a hard crash; Bug 2 causes silent data loss. Bug 3 has been resolved in both `DataTableForm` and `WizardForm`.

Bugs 4, 5, and 6 were compounding issues in the AccessControlManager permission panel. All three had to be fixed for extra permissions to render and function correctly. Bug 7 was a deployment/publishing issue that masked the Blade-level fixes (Bugs 5 and 6) in the consuming app.