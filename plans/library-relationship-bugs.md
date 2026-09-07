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

## Summary

| Bug | Severity | Status | Affected Method |
|-----|----------|--------|-----------------|
| Bug 1: `hasMany` uses `sync()` | Critical | Open | `syncRelationships()` |
| Bug 2: No pivot extra data support | High | Open | `syncRelationships()` |
| Bug 3: `company_id` always visible on forms | Medium | Fixed | `loadConfiguration()`, `validateFields()` |

Bugs 1 and 2 are in the same method and should be addressed together to avoid introducing merge conflicts. Bug 1 will cause a hard crash; Bug 2 causes silent data loss. Bug 3 has been resolved in both `DataTableForm` and `WizardForm`.