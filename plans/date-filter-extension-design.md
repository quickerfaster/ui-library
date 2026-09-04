# Date-Based Filter Extension — Design Document

**Scope:** Analyze and design only. No code modified.

## 1. Current Filter Infrastructure (Findings)

### 1.1 `queryFilters` shape

`queryFilters` is a **list of `[field, operator, value]` triplets**, not keyed objects. Confirmed in
[`DataTable::mount()`](src/Http/Livewire/DataTables/DataTable.php:214) and consumed in
[`getRecordsProperty()`](src/Http/Livewire/DataTables/DataTable.php:1499):

```php
// mount() — the ?filter[] bridge
$filterParam = request()->query('filter', []);
if (is_array($filterParam)) {
    foreach ($filterParam as $field => $value) {
        $this->queryFilters[] = [$field, '=', $value];   // always '='
    }
}

// getRecordsProperty()
$this->applyFilters($query, $this->queryFilters);
$this->applyFilters($query, $this->pageQueryFilters, true);
$this->applyActiveFilters($query);
```

There are **two parallel filter paths**:

| Path | Data shape | Driver | Operator vocabulary | Handling |
|------|-----------|--------|---------------------|----------|
| **`queryFilters`** (URL/page-driven) | `[field, op, value]` | [`FilterService::applySimpleFilters()`](src/Services/Filters/FilterService.php:22) | **Raw SQL symbols** (`=`, `>`, `<`, …) | `$query->where($field, $operator, $value)` |
| **`activeFilters`** (UI/session-driven) | `['field','type','operator','value']` | [`AppliesFilters::applyActiveFilters()`](src/Traits/Filters/AppliesFilters.php:34) | **Named operators** (`greater_than`, `between`, `today`, `last_7_days`, …) | Type-aware switch → `applyDateFilter()` etc. |

The `?filter[]` bridge only ever feeds `queryFilters` (path 1). It **hardcodes the `=` operator**.

### 1.2 Operators currently supported

**`FilterService::applySimpleFilters()`** (the `queryFilters` path) does **no operator whitelisting** — it
passes the operator string straight into Eloquent's `where()`:

```php
[$field, $operator, $value] = $filter;
...
$query->where($field, $operator, $value);
```

Consequences:
- **Works today with no change:** `=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `not like`, `in` (with array value).
- **`between` is BROKEN** in this path. Laravel's `Builder::where()` has this coercion:
  `if (is_array($value)) { return $this->whereIn($column, $value, $boolean, $operator !== 'in'); }`.
  Passing `where('expiry_date', 'between', ['a','b'])` therefore produces a **`whereNotIn`**, not a
  `whereBetween`. `between` requires explicit handling via `whereBetween()`.
- **Relative date values are NOT resolved.** Passing `today`, `+30 days`, or `-7 days` as a value goes
  verbatim into the SQL string. Nothing in this path calls the date resolver.

**`AppliesFilters::applyActiveFilters()`** (the UI path) already supports a rich named-operator set:
- string: `equals`, `contains`, `starts_with`, `ends_with`
- number: `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equals`, `less_than_or_equals`, `between`
- date (in [`applyDateFilter()`](src/Traits/Filters/AppliesFilters.php:211)): `equals`, `not_equals`, `greater_than`,
  `less_than`, `between` (with `start`/`end`), `today`, `this_week`, `this_month`, `this_year`,
  `last_week`, `last_month`, `last_year`, `last_7_days`, `next_30_days`, `this_quarter`, `last_quarter`.

### 1.3 How the FilterPanel constructs filter entries

[`FilterPanel::getOperatorsForType()`](src/Http/Livewire/FilterPanel.php:352) returns a map of
**named operators** per field type (e.g. date → `after`/`before`/`between`/`today`/`last_7_days`/`next_30_days`).
[`FilterPanel::emitFilters()`](src/Http/Livewire/FilterPanel.php:476) emits entries shaped
`['field' => …, 'type' => …, 'operator' => …, 'value' => …]`, which `DataTable::updateFilters()` stores into
`activeFilters`. These are consumed by `applyActiveFilters()`, **not** by `applySimpleFilters()`.

> **Key takeaway:** the UI already knows how to express "last 7 days", "next 30 days", "before/after", and
> "between" using named operators, but that machinery lives in the `activeFilters` path and is never reachable
> from a URL.

### 1.4 Dashboard "View all" links and placeholders

Dashboard widget configs (e.g. `hr.dashboards.dashboard_employee_overview`) define `view_all_link` strings that
already use the `?filter[field]=value` syntax with `{{ placeholders }}`:

```php
'view_all_link' => '/leave/leave-requests?filter[employee_id]={{ employee_id }}&hiddenFields[onTable][]=employee_id',
```

Placeholders are replaced by simple `str_replace` in
[`DashboardResolver::replacePlaceholders()`](src/Services/Config/Dashboards/DashboardResolver.php:30) using the
`parameters` passed into the dashboard. The list widget emits the link verbatim via
[`ListWidgetProcessor`](src/Widgets/ListWidgetProcessor.php:71) and renders it in
[`list.blade.php`](src/Resources/views/widgets/list.blade.php:35).

**Important:** the widget body already applies date conditions (e.g. `expiry_date >= today` and
`expiry_date <= +30 days`) using [`ResolvesDateStrings::resolveConditions()`](src/Traits/Widgets/ResolvesDateStrings.php:85),
but the "View all" link drops those date conditions entirely — so the table shows a superset of what the widget
promised. This is the exact gap the date-filter extension closes.

---

## 2. Query-String Syntax Recommendation

### Options evaluated

| Option | Example | Pros | Cons |
|--------|---------|------|------|
| **A. Nested array** | `?filter[end_date][>]=2026-01-01` | Natural `$_GET` parsing; consistent with existing `filter[]` and `hiddenFields[onTable][]`; backward compatible | Operator keys need URL-encoding (`>`/`<`) |
| **B. Dot-notation key** | `?filter[end_date:>]=…` | Compact | `:` + `>` in keys; ugly encoding; splitting is custom and error-prone |
| **C. Separate params** | `?filter[field]=v&filter_op[field]=>` | Isolates operator | Two arrays must stay in sync; verbose; easy to desync |
| **D. JSON-encoded** | `?filter=[{…}]` | Max expressiveness | Opaque, unreadable, painful to hand-author in a dashboard config string; overkill |

### Recommendation: **Option A — nested array syntax**

`?filter[field][operator]=value`, with the existing `?filter[field]=value` remaining valid (implying `=`).

Rationale:
1. **Backward compatible.** `?filter[field]=value` stays a scalar in `$_GET['filter']['field']` and still maps to
   `[$field, '=', $value]`.
2. **PHP parsing.** `$_GET` natively turns `filter[field][op]=v` into `['filter' => ['field' => ['op' => 'v']]]`.
   The bridge distinguishes scalar vs array to detect the "implied `=`" case.
3. **Consistent with the codebase.** The same URLs already use `hiddenFields[onTable][]=employee_id`, so nested
   `[]` is an established convention.
4. **Readable / hand-authorable.** Easier to write in a `view_all_link` string than JSON or parallel arrays.

`between` (two values) in Option A takes the nested-list form:

```
?filter[expiry_date][between][]=today&filter[expiry_date][between][]=+30 days
```

However, for date ranges the **simpler and recommended form is two separate operators** (`>=` + `<=`), which
mirrors the existing widget `conditions` style and avoids the `between`/array-value coercion entirely:

```
?filter[expiry_date][>=]=today&filter[expiry_date][<=]=+30 days
```

---

## 3. Implementation Design

### 3a. Extend the `?filter[]` bridge in `DataTable::mount()`

Replace the hardcoded loop at [`DataTable::mount()`](src/Http/Livewire/DataTables/DataTable.php:214). Pseudocode:

```php
$filterParam = request()->query('filter', []);

// 1. Whitelist of SQL operators (security + correctness).
$allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'like', 'not like', 'between'];

foreach ((array) $filterParam as $field => $spec) {

    if (!is_array($spec)) {
        // Backward compatible: ?filter[field]=value  =>  [field, '=', value]
        $this->queryFilters[] = [$field, '=', $this->resolveValue($field, $spec)];
        continue;
    }

    // New syntax: ?filter[field][operator]=value  =>  one entry per operator
    foreach ($spec as $operator => $value) {
        if (!in_array($operator, $allowedOperators, true)) {
            continue;                       // ignore unknown/unsafe operator
        }

        if ($operator === 'between') {
            // value is a numerically-indexed list: [min, max]
            $values = array_values((array) $value);
            if (count($values) !== 2) {
                continue;
            }
            $this->queryFilters[] = [$field, 'between', [
                $this->resolveValue($field, $values[0]),
                $this->resolveValue($field, $values[1]),
            ]];
            continue;
        }

        $this->queryFilters[] = [$field, $operator, $this->resolveValue($field, $value)];
    }
}
```

Supporting helpers to add to the same class (or a small dedicated service):

```php
/**
 * Resolve relative date strings for date-typed fields, reusing the same
 * vocabulary already used by widgets (ResolvesDateStrings).
 */
protected function resolveValue(string $field, $value)
{
    if (!is_string($value) || !$this->isDateField($field)) {
        return $value;                      // non-date or non-string: pass through
    }
    return $this->resolveDateString($value); // today, -7 days, +30 days, etc.
}

protected function isDateField(string $field): bool
{
    $type = $this->columns[$field]['field_type'] ?? '';
    return in_array($type, ['datepicker', 'datetimepicker'], true);
}
```

`resolveDateString()` can be obtained by `use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;` (it is
already trait-based and field-agnostic) — this guarantees **URL filters and widget filters resolve dates identically**.

**Optional sugar — named date operators.** To avoid URL-encoding/space issues and reuse vocabulary the UI already
knows, the bridge may also accept the FilterPanel's named date operators and translate them into concrete SQL:

| Named operator (URL) | Expansion (before storing in `queryFilters`) |
|----------------------|----------------------------------------------|
| `today` | `[$field, '=', today]` |
| `tomorrow` | `[$field, '=', tomorrow]` |
| `yesterday` | `[$field, '=', yesterday]` |
| `greater_than` | `[$field, '>', value]` |
| `less_than` | `[$field, '<', value]` |
| `greater_than_or_equals` | `[$field, '>=', value]` |
| `less_than_or_equals` | `[$field, '<=', value]` |
| `last_7_days` | `[$field, '>=', now - 7 days]` |
| `next_30_days` | `[$field, 'between', [now, now + 30 days]]` |

This is a thin, additive mapping and is **not required** for the three dashboard use cases (raw `>=`/`<=` +
relative values already cover them). Recommend shipping it only if a future dashboard config needs a "no spaces in
URL" shorthand.

### 3b. `FilterService` compatibility

**`>`, `<`, `>=`, `<=`, `!=`, `like`, `=` already work** — [`applySimpleFilters()`](src/Services/Filters/FilterService.php:22)
passes operators raw to `where()`. **No change needed** for these.

**`between` needs a small, explicit extension.** Because Laravel coerces array-valued `where()` into `whereIn`
(actually `whereNotIn` for `between`), add a branch before the generic `where()` call:

```php
// In FilterService::applySimpleFilters(), before the where() dispatch:
if ($operator === 'between' && is_array($value) && count($value) === 2) {
    $query->whereBetween($field, array_values($value));   // note: plain, not relation branch
    continue;
}
```

For the dot-notation (relation) branch, mirror the same check inside the `whereHas` closure:

```php
$query->whereHas($relation, function ($q) use ($column, $operator, $value) {
    if ($operator === 'between' && is_array($value) && count($value) === 2) {
        $q->whereBetween($column, array_values($value));
    } else {
        $q->where($column, $operator, $value);
    }
});
```

Optionally also guard `in` / `not in` with `whereIn`/`whereNotIn` for completeness, but it is not needed for dates.

**Relative date resolution happens in the bridge (3a), not here** — keeping `FilterService` a pure, dumb
triplet-applier. This avoids duplicating date math in two services and keeps the widget/table behavior identical.

### 3c. Dashboard config usage examples

Target file: `hr-consuming-app/app/Modules/Hr/Data/dashboards/dashboard_employee_overview.php`.
(Field names verified: `Document.expiry_date` is `datepicker`; `LeaveRequest.start_date` is `datepicker`;
`Attendance.date` is `datepicker`.)

**Expiring Documents (expiry_date before today + 30 days)** — currently only `filter[employee_id]`:

```php
'view_all_link' => '/hr/documents'
    . '?filter[employee_id]={{ employee_id }}'
    . '&filter[expiry_date][>=]=today'
    . '&filter[expiry_date][<=]=+30 days'
    . '&hiddenFields[onTable][]=employee_id',
```

**Upcoming Time Off (start_date in the future, already Approved)** — currently `filter[employee_id]` only:

```php
'view_all_link' => '/leave/leave-requests'
    . '?filter[employee_id]={{ employee_id }}'
    . '&filter[status]=Approved'
    . '&filter[start_date][>=]=today'
    . '&hiddenFields[onTable][]=employee_id',
```

**Recent Attendance (date within the last 7 days)** — currently `filter[employee_id]` only:

```php
'view_all_link' => '/attendance/attendances'
    . '?filter[employee_id]={{ employee_id }}'
    . '&filter[date][>=]=-7 days'
    . '&filter[date][<=]=today'
    . '&hiddenFields[onTable][]=employee_id',
```

> `{{ employee_id }}` is substituted by `DashboardResolver::replacePlaceholders()`; the date values (`today`,
> `+30 days`, `-7 days`) are resolved by the bridge at request time, so the links stay "relative" and never go stale.

---

## 4. Edge Cases and Risks

1. **URL length limits.** Each `filter[field][op]=value` adds ~40–60 bytes. Dashboards with many conditions could
   approach the ~2 KB practical URL limit. Mitigation: keep per-widget links to 2–4 filters (the current pattern),
   and prefer `between` over two `>=`/`<=` clauses when length matters.
2. **PHP `parse_str()` / `$_GET` nested arrays with special characters.** `>`, `<`, `=`, `:` in keys must be
   percent-encoded. Laravel's `request()->query()` already decodes them; the risk is only at authoring time
   (an unencoded `>` in a config string). Using the optional named operators (3a) sidesteps this entirely.
   **Never** put raw spaces in `view_all_link` date values — rely on `today` / `+30 days`-style resolution in the
   bridge, or name-only operators like `next_30_days`.
3. **Backward compatibility.** The scalar branch preserves `?filter[field]=value` ⇒ `=` exactly. Existing
   dashboard links continue to behave identically. `hiddenFields[onTable][]` parsing is untouched.
4. **FilterPanel UI interaction.** `queryFilters` (URL) and `activeFilters` (UI/session) are applied independently
   and **ANDed** together in `getRecordsProperty()`. A user arriving with date filters in the URL who then opens
   the FilterPanel will not see those URL filters reflected in the panel (the panel only reads `activeFilters`).
   This is a known, pre-existing separation. **Recommendation:** keep the URL extension query-string-only for now;
   extend the FilterPanel to render URL-derived date filters as a separate, read-only "From link" chip in a
   follow-up if needed.
5. **Security: SQL injection via operator.** Operator strings are interpolated raw into SQL by `where()`. The
   bridge MUST whitelist operators (`=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `not like`, `between`) and drop
   anything else. Values remain parameter-bound by Eloquent, so value injection is not a concern. Field names are
   additionally protected downstream by `applySimpleFilters()`'s `$fieldDefinitions` validation when config
   supplies definitions (see [`AppliesFilters::applyFilters()`](src/Traits/Filters/AppliesFilters.php:21)).
6. **Relative date resolution on non-date fields.** A string field whose value literally equals `today` could be
   accidentally resolved. Mitigation: gate resolution behind `isDateField()` (field_type `datepicker`/`datetimepicker`),
   as designed in 3a.
7. **`between` array coercion.** Explicitly handled in `FilterService` (3b); without it, `between` silently becomes
   `whereNotIn`. The design avoids relying on this path by recommending `>=` + `<=` for date ranges.

---

## 5. Recommendation Summary

- **Syntax:** Option A (nested array) `?filter[field][operator]=value`, backward compatible with
  `?filter[field]=value`.
- **Operators:** raw SQL symbols whitelisted (`=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `not like`, `between`), with
  optional named date aliases (`last_7_days`, `next_30_days`, `today`, …) as additive sugar.
- **Where to implement:** as a **library feature** (in [`DataTable::mount()`](src/Http/Livewire/DataTables/DataTable.php:214)
  and [`FilterService::applySimpleFilters()`](src/Services/Filters/FilterService.php:22)), not consuming-app only —
  the bridge and the operator handling are library concerns and benefit every dashboard.
- **Effort:** **Low**. The bridge change is ~40 lines; the `FilterService` `between` branch is ~8 lines. Date
  resolution reuses the existing [`ResolvesDateStrings`](src/Traits/Widgets/ResolvesDateStrings.php:8) trait.
- **FilterPanel UI:** keep it **query-string-only for now**. The UI already supports these operators internally;
  surfacing URL-derived filters in the panel is a separate, optional enhancement.

### Concrete change list (for the implementing mode)

1. Add a private `resolveFilterParam(array $filterParam): array` (or inline) in `DataTable` that:
   - keeps scalar ⇒ `=` (backward compatible),
   - whitelists operators,
   - handles `between` as `[min, max]`,
   - resolves relative date values for date-typed fields (via `ResolvesDateStrings`).
2. Replace the existing `foreach ($filterParam as $field => $value) { … $this->queryFilters[] = [$field, '=', $value]; }`
   with the new parser.
3. Add a `between` branch (with `whereBetween`) in `FilterService::applySimpleFilters()` for both the direct and
   dot-notation relation paths.
4. Update the three `view_all_link` strings in `dashboard_employee_overview.php` (Section 3c).
5. Add unit tests covering: scalar backward compat, `>=`/`<=` pass-through, `between` (direct + relation), relative
   date resolution, and operator whitelist rejection.
