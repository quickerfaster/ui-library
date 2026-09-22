# Organization Module — Data Config Gap Analysis

> **Reference**: `app/Modules/Hr/Data/employee.php` (modern format)
> **Target**: `app/Modules/Organization/Data/*.php` (7 configs, legacy format)
> **Date**: 2026-09-20

---

## Summary

All 7 Organization configs use a **legacy dual-schema format** with `columns`, `fields`, and `filters` keys that duplicate `fieldDefinitions`. The HR `employee.php` uses the modern single-source `fieldDefinitions`-driven architecture. The legacy format causes missing features: no relationship display, no advanced search on FK fields, no bulk actions, no soft delete, and inconsistent controls.

---

## Gap Matrix (per config)

| # | Gap | company | department | branch | location | team | division | business_unit |
|---|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 | Duplicate `columns` key | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 2 | Duplicate `fields` key | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 3 | Legacy `filters` key | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| 4 | Legacy `controls` format | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| 5 | Missing `relationship` on FK fields | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 6 | Missing `searchable` on FK fields | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 7 | Missing `hiddenFields` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 8 | Missing `simpleActions` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 9 | Missing `tableDefaultFields` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 10 | Missing `bulkActions` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 11 | Legacy `per_page_options` | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| 12 | Legacy `default_sort` | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| 13 | Missing `searchable_fields` on relationships | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 14 | Legacy boolean `controls` | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| 15 | Missing soft delete support | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

✅ = gap present, ❌ = not applicable (config doesn't have FK fields / already has the feature)

---

## Detailed Gaps

### 1. Duplicate `columns` Key (ALL 7 configs)

The `columns` key defines column visibility, sortability, and searchability separately from `fieldDefinitions`. The library derives columns from `fieldDefinitions` — having both causes:
- Confusion about which definition is authoritative
- `searchable`/`sortable`/`visible` defined in `columns` but not in `fieldDefinitions`
- The `fieldDefinitions` values are what the library actually uses for search/filter

**Fix**: Remove `columns` key. Move `searchable`, `sortable`, `visible` into `fieldDefinitions`. Use `tableDefaultFields` for default visibility.

### 2. Duplicate `fields` Key (ALL 7 configs)

The `fields` key duplicates form field configuration. The library uses `fieldDefinitions` for form rendering.

**Fix**: Remove `fields` key. All form config already exists in `fieldDefinitions`.

### 3. Legacy `filters` Key (department, team, division, business_unit)

Uses old filter format instead of `'filterable' => true` on field definitions.

**Fix**: Remove `filters` key. Add `'filterable' => true` to relevant `fieldDefinitions`.

### 4. Legacy `controls` Format (department, team, division, business_unit)

Uses `'create' => true, 'edit' => true` instead of the modern format:

```php
// Legacy (current)
'controls' => [
    'create' => true,
    'edit' => true,
    'delete' => true,
    'view' => true,
    'export' => true,
    'import' => true,
    'print' => true,
    'softDelete' => true,
],

// Modern (should be)
'controls' => [
    'addButton' => true,
    'editable' => true,
    'files' => [
        'export' => ['xls', 'csv', 'pdf'],
        'print' => true,
    ],
    'perPage' => [10, 25, 50, 100],
    'search' => true,
    'showHideColumns' => true,
    'filterColumns' => true,
    'softDelete' => true,
    'restore' => true,
    'forceDelete' => true,
    'trashView' => true,
    'bulkActions' => [
        'export' => ['xls', 'csv', 'pdf'],
        'delete' => true,
        'restore' => true,
        'forceDelete' => true,
    ],
],
```

### 5. Missing `relationship` on FK Fields (ALL except company)

FK fields like `company_id`, `branch_id`, `department_id`, `parent_department_id` have no `relationship` definition. This means:
- Table shows raw IDs instead of related model names
- No eager loading optimization
- Advanced search can't search by related model data

**Fix**: Add `relationship` block to each FK field:

```php
'company_id' => [
    // ... existing ...
    'searchable' => true,
    'relationship' => [
        'model' => 'App\Modules\Organization\Models\Company',
        'type' => 'belongsTo',
        'display_field' => 'name',
        'searchable_fields' => ['name', 'code'],
        'dynamic_property' => 'company',
        'foreign_key' => 'company_id',
        'inlineAdd' => false,
    ],
    'options' => [
        'model' => 'App\Modules\Organization\Models\Company',
        'column' => 'name',
        'hintField' => 'code',
    ],
],
```

### 6. Missing `searchable` on FK Fields

FK fields don't have `'searchable' => true`. Combined with missing `relationship`, they can't be searched at all.

### 7. Missing `hiddenFields` (ALL 7 configs)

No `hiddenFields` to hide `created_at`, `updated_at` from table/forms. The HR config hides these:

```php
'hiddenFields' => [
    'onTable' => ['created_at', 'updated_at', 'deleted_at'],
    'onNewForm' => ['created_at', 'updated_at', 'deleted_at'],
    'onEditForm' => ['updated_at', 'deleted_at'],
    'onQuery' => ['deleted_at'],
],
```

### 8. Missing `simpleActions` (ALL 7 configs)

No explicit row actions. Should be:

```php
'simpleActions' => ['show', 'edit', 'delete'],
```

### 9. Missing `tableDefaultFields` (ALL 7 configs)

No default visible columns. The table shows all columns or falls back to first 6. Should define 5-7 key fields:

```php
'tableDefaultFields' => ['name', 'code', 'company_id', 'is_active', 'created_at'],
```

### 10. Missing `bulkActions` (ALL 7 configs)

No bulk delete/export/restore. Should be included in `controls`.

### 11-12. Legacy Pagination/Sort Keys

`per_page_options`, `default_per_page`, and `default_sort` should move into `controls.perPage` and be handled by the DataTable's built-in sort.

### 13. Missing `searchable_fields` on Relationships

Even after adding `relationship` definitions, need `searchable_fields` for multi-column advanced search.

### 14. Legacy Boolean `controls`

The `'create' => true` format doesn't map to the library's control system. Use the modern format.

### 15. Missing Soft Delete Support

No `softDelete`, `restore`, `forceDelete`, or `trashView` in controls. The HR config has all four.

---

## Migration Priority

| Priority | Configs | Effort | Impact |
|----------|---------|--------|--------|
| **P0** | All 7 | Remove `columns`/`fields`/`filters` keys | Eliminates confusion, single source of truth |
| **P0** | All 7 | Add `relationship` to FK fields | Enables name display, eager loading, advanced search |
| **P1** | All 7 | Modernize `controls` format | Enables bulk actions, soft delete, proper export |
| **P1** | All 7 | Add `hiddenFields`, `simpleActions`, `tableDefaultFields` | Consistent UX with HR module |
| **P2** | All 7 | Add `searchable_fields` to relationships | Multi-column advanced search on related models |