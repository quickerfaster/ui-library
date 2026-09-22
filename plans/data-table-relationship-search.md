AI Prompt — Add Relationship Field Support to DataTable Advanced Search

## Context

You are working on the QuickerFaster UI Library (quicker-faster/ui-library), a Laravel + Livewire 3 + Bootstrap 5 package at `/Users/mac/Projects/Libraries/ui-library`. The consuming app is at `/Users/mac/Projects/LaravelProjects/hr-consuming-app`.

## Library Philosophy (Critical — Do Not Violate)

From `docs/library/pilosophy.txt`:
- The library must be completely decoupled from the consuming app. The consuming app can depend on the library, but never the reverse.
- Convention over configuration — modules follow predictable folder conventions.
- qf namespace convention — all library assets use the qf prefix (views: `qf::`, Blade: `<x-qf::>`, Livewire: `qf.`).

## The Problem

The employee position datatable (`hr.employee_position`) at `/hr/employee-positions` cannot search employees by name, email, or employee number. Only the `work_email` field appears in the advanced search panel. Searching by "EMPLOYEE-2026-00008", "Aminu", or "employee1@test.com" returns no results.

### Root Cause Analysis (3 Layers)

**Layer 1 — [`SearchPanel::loadColumns()`](src/Http/Livewire/SearchPanel.php:57) explicitly skips relationship fields:**

```php
// Line 57-58
if (isset($def['relationship'])) {
    continue;  // ← RELATIONSHIP FIELDS ARE SKIPPED
}
```

This means `employee_id` (which has `searchable: true` and a relationship to the Employee model) never appears in the advanced search checkboxes. Only direct string fields like `work_email` are shown.

**Layer 2 — [`DataTable::getRecordsProperty()`](src/Http/Livewire/DataTables/DataTable.php:1586) only searches direct columns:**

```php
// Lines 1586-1612
if (!empty($this->search)) {
    $query->where(function ($q) {
        $columns = !empty($this->selectedSearchColumns)
            ? $this->selectedSearchColumns
            : array_slice($this->searchableFields, 0, 2);

        foreach ($columns as $field) {
            // ... orWhere($field, 'like', ...) — direct column only
        }
    });
}
```

The `searchableRelations` array (built at line 1113) is never used for actual searching. It's populated but orphaned.

**Layer 3 — [`SearchEngine::apply()`](src/Services/Search/SearchEngine.php:14) only does direct `orWhere`:**

```php
foreach ($fields as $field) {
    $q->orWhere($field, 'like', $search . '%');
}
```

No `whereHas` support for relationship fields.

### The `employee_position` Config

File: `app/Modules/Hr/Data/employee_position.php`

The `employee_id` field has:
```php
'employee_id' => [
    'searchable' => true,
    'relationship' => [
        'model' => 'App\Modules\Hr\Models\Employee',
        'type' => 'belongsTo',
        'display_field' => 'employee_number',
        'dynamic_property' => 'employee',
        'foreign_key' => 'employee_id',
    ],
],
```

The Employee model has `first_name`, `last_name`, `email`, `employee_number` columns that users want to search by.

## Performance Design Constraint (Critical)

The data table is designed for speed. The quick search bar must remain lightweight:

| Search Mode | Trigger | Columns Searched | Relationship Support |
|-------------|---------|-----------------|---------------------|
| **Quick search** | Typing in the inline search input | First 2 `searchableFields` (direct columns only) | ❌ No — `whereHas` is too heavy for default |
| **Advanced search** | Clicking sliders icon → opens drawer | User-selected columns from `SearchPanel` | ✅ Yes — user explicitly opts into the heavier query |

**This is already enforced by the existing architecture:**
- `DataTable::$searchableFields` contains only direct columns (line 1121)
- `DataTable::$searchableRelations` contains relationship fields (line 1113) but is never used for quick search
- Quick search falls back to `array_slice($this->searchableFields, 0, 2)` when no columns are selected (line 1590)
- Advanced search uses `$this->selectedSearchColumns` which comes from the `SearchPanel`

**The fix must preserve this separation:** relationship fields should ONLY appear in the advanced search panel, never in the quick search fallback.

## The Task

Extend the **advanced search panel** to support relationship fields. The quick search bar remains unchanged (direct columns only).

### Step 1: Update `SearchPanel::loadColumns()` (Library)

File: [`src/Http/Livewire/SearchPanel.php`](src/Http/Livewire/SearchPanel.php:47)

Currently skips relationship fields at line 57. Instead, include them with a label that indicates the relationship:

```php
if (isset($def['relationship'])) {
    // Include relationship fields with a descriptive label
    $relationLabel = $def['label'] ?? ucfirst($field);
    $displayField = $def['relationship']['display_field'] ?? 'name';
    $this->allColumns[$field] = $relationLabel . ' (' . $displayField . ')';
    continue;  // keep the continue, but after adding to allColumns
}
```

Wait — actually, the `continue` should be removed or restructured. The field should be added to `allColumns` so it appears in the search checkboxes. The label should help users understand what they're searching (e.g., "Employee (employee_number)").

### Step 2: Update `DataTable::getRecordsProperty()` Search Logic (Library)

File: [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:1586)

The search loop at lines 1592-1610 needs to handle relationship fields. When a selected search column has a `relationship` definition, use `whereHas` instead of `orWhere`:

```php
foreach ($columns as $field) {
    $fieldDef = $this->columns[$field] ?? [];

    // Handle relationship fields via whereHas
    if (isset($fieldDef['relationship'])) {
        $relationMethod = $this->getRelationMethodFromField($field, $fieldDef);
        $displayColumn = $this->getRelationDisplayColumn($fieldDef);
        if ($relationMethod && $displayColumn) {
            $searchTerm = $this->search;
            $exact = $this->exactMatch;
            $q->orWhereHas($relationMethod, function ($subQ) use ($displayColumn, $searchTerm, $exact) {
                if ($exact) {
                    $subQ->where($displayColumn, '=', $searchTerm);
                } else {
                    $subQ->where($displayColumn, 'like', $searchTerm . '%');
                }
            });
        }
        continue;
    }

    // ... existing direct field logic ...
}
```

**Key:** The `getRelationMethodFromField()` and `getRelationDisplayColumn()` methods already exist at lines 1262 and 1279. They extract the relationship method name and display column from the field definition.

### Step 3: Update `SearchEngine::apply()` (Library)

File: [`src/Services/Search/SearchEngine.php`](src/Services/Search/SearchEngine.php:8)

This is used by `getCurrentPageIds()` at line 2505. It should also support relationship fields. However, `SearchEngine` doesn't have access to field definitions — it only receives field names and a query. 

**Option A:** Pass field definitions to `SearchEngine` so it can handle relationships.
**Option B:** Update `getCurrentPageIds()` to use the same logic as `getRecordsProperty()` instead of `SearchEngine`.

Recommend **Option B** — consolidate the search logic into one place (DataTable) rather than duplicating it in SearchEngine.

### Step 4: Ensure `employee_position` Config Has Proper Searchable Fields (Consuming App)

File: `app/Modules/Hr/Data/employee_position.php`

The `employee_id` field already has `'searchable' => true`. But users want to search by `first_name`, `last_name`, `email`, and `employee_number` — all fields on the related Employee model, not on EmployeePosition.

The current `display_field` is `'employee_number'`. This means `whereHas('employee', fn($q) => $q->where('employee_number', 'like', ...))` — which only searches by employee number.

**Option A:** Change `display_field` to search multiple columns. This requires extending the config format to support an array of display fields.

**Option B:** Add a `searchable_fields` key to the relationship config that lists which columns on the related model to search:

```php
'relationship' => [
    'model' => 'App\Modules\Hr\Models\Employee',
    'type' => 'belongsTo',
    'display_field' => 'employee_number',
    'searchable_fields' => ['employee_number', 'first_name', 'last_name', 'email'],
    // ...
],
```

Then in the search logic, use `orWhere` chains within the `whereHas`:

```php
$q->orWhereHas($relationMethod, function ($subQ) use ($searchableFields, $searchTerm, $exact) {
    $subQ->where(function ($innerQ) use ($searchableFields, $searchTerm, $exact) {
        foreach ($searchableFields as $sf) {
            if ($exact) {
                $innerQ->orWhere($sf, '=', $searchTerm);
            } else {
                $innerQ->orWhere($sf, 'like', $searchTerm . '%');
            }
        }
    });
});
```

**Recommend Option B** — it's more flexible and doesn't require changing the existing `display_field` behavior.

### Step 5: Update `DataTable::initializeFromConfig()` (Library)

File: [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php:1108)

The `searchableRelations` array at line 1113 should include the `searchable_fields` from the relationship config:

```php
$this->searchableRelations[] = [
    'field' => $field,
    'relation' => $relationMethod,
    'column' => $displayColumn,
    'searchable_fields' => $def['relationship']['searchable_fields'] ?? [$displayColumn],
];
```

## Key Files Reference

| File | Purpose |
|------|---------|
| [`src/Http/Livewire/SearchPanel.php`](src/Http/Livewire/SearchPanel.php) | Advanced search panel — `loadColumns()` skips relationships |
| [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) | Main data table — search logic, `searchableRelations`, `getRecordsProperty()` |
| [`src/Services/Search/SearchEngine.php`](src/Services/Search/SearchEngine.php) | Simple search engine — no relationship support |
| [`src/Resources/views/livewire/search-panel.blade.php`](src/Resources/views/livewire/search-panel.blade.php) | Advanced search panel UI |
| `app/Modules/Hr/Data/employee_position.php` | Employee position config — needs `searchable_fields` on relationship |

## Pre-Implementation Checklist

- [ ] Read all 5 key files above to understand current implementation
- [ ] Check for published/stale views in `resources/views/vendor/qf/` that may override library changes
- [ ] Verify `getRelationMethodFromField()` and `getRelationDisplayColumn()` work correctly for the `employee_id` field

## Post-Implementation Checklist

- [ ] Run `php -l` on all modified PHP files
- [ ] Run `composer dump-autoload` in the consuming app
- [ ] Run `php artisan optimize:clear` in the consuming app
- [ ] Check `resources/views/vendor/qf/` for stale published copies of modified views
- [ ] Visit `/hr/employee-positions` and verify:
  - Advanced search panel shows "Employee (employee_number)" checkbox
  - Searching "EMPLOYEE-2026-00008" finds the employee
  - Searching "Aminu" finds the employee
  - Searching "employee1@test.com" finds the employee
- [ ] Update [`docs/debug-checklist.md`](docs/debug-checklist.md) with any new lessons learned

## Verification

After implementation:
1. Clear views: `php artisan view:clear`
2. Clear caches: `php artisan optimize:clear`
3. Visit `/hr/employee-positions`

**Quick search (should NOT search relationships):**
4. Type "EMPLOYEE-2026-00008" in the quick search bar → should NOT filter (quick search only searches direct columns like `work_email`)
5. This is correct behavior — relationship search is opt-in via advanced panel

**Advanced search (should search relationships):**
6. Click the sliders icon to open the advanced search drawer
7. Verify "Employee (employee_number, first_name, last_name, email)" appears as a checkbox
8. Select "Employee" checkbox
9. Search "EMPLOYEE-2026-00008" → table filters to show the matching employee
10. Search "Aminu" → table filters to show the matching employee
11. Search "employee1@test.com" → table filters to show the matching employee
12. Verify other relationship fields (Company, Job Title, Department) also appear as searchable