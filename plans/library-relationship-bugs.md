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

## Summary

| Bug | Severity | Status | Affected Method |
|-----|----------|--------|-----------------|
| Bug 1: `hasMany` uses `sync()` | Critical | Open | `syncRelationships()` |
| Bug 2: No pivot extra data support | High | Open | `syncRelationships()` |

Both bugs are in the same method and should be addressed together to avoid introducing merge conflicts. Bug 1 will cause a hard crash; Bug 2 causes silent data loss.