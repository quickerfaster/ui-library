<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Prints;

use Illuminate\Http\Request;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use Illuminate\Routing\Controller;

class GenericTablePrintController extends Controller
{
    public function print(Request $request)
    {
        $configKey = $request->configKey;
        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $modelClass = $resolver->getModel();


        // Determine max rows to print
        $perPage = (int) $request->input('perPage', 50);
        $maxPrintRows = config('qf.max_print_rows', 500); // default 500. this needs to be set in the config file
        $limit = min($perPage, $maxPrintRows);


        // Start query
        $query = $modelClass::query();

        // 1. Apply search (same logic as DataTable)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $selectedColumns = json_decode($request->selectedSearchColumns, true) ?: [];
            $exactMatch = $request->boolean('exactMatch');

            $searchableFields = $this->getSearchableFields($resolver);
            $columns = !empty($selectedColumns) ? $selectedColumns : array_slice($searchableFields, 0, 2);

            $query->where(function ($q) use ($columns, $searchTerm, $exactMatch, $resolver) {
                foreach ($columns as $field) {
                    $fieldDef = $resolver->getFieldDefinitions()[$field] ?? [];
                    // Handle select options
                    if (isset($fieldDef['options']) && is_array($fieldDef['options'])) {
                        $keys = $this->getSearchKeysForSelectField($fieldDef['options'], $searchTerm);
                        if (!empty($keys)) {
                            $q->orWhereIn($field, $keys);
                        }
                        continue;
                    }
                    // Regular field
                    if ($exactMatch) {
                        $q->orWhere($field, '=', $searchTerm);
                    } else {
                        $q->orWhere($field, 'like', $searchTerm . '%');
                    }
                }
            });
        }

        // 2. Apply filters (activeFilters from filter panel)
        if ($request->filled('activeFilters')) {
            $activeFilters = json_decode($request->activeFilters, true);
            $this->applyActiveFilters($query, $activeFilters, $resolver);
        }

        // 3. Soft delete filter
        $trashedFilter = $request->input('trashedFilter', 'without');
        if ($this->usesSoftDeletes($modelClass)) {
            match ($trashedFilter) {
                'only' => $query->onlyTrashed(),
                'with' => $query->withTrashed(),
                default => $query->withoutTrashed(),
            };
        }

        // 4. Sorting (skip relationship fields)
        if ($request->filled('sort') && $request->filled('direction')) {
            $sortField = $request->sort;
            $direction = $request->direction;
            $fieldDefs = $resolver->getFieldDefinitions();
            if (!isset($fieldDefs[$sortField]['relationship'])) {
                $query->orderBy($sortField, $direction);
            }
        }




        // Apply limit
        $query->limit($limit);

        $records = $query->get();
        $recordCount = $records->count();

        // Check if truncated
        // $truncated = ($recordCount >= $limit && $query->count() > $limit);
        $truncated = ($recordCount >= $maxPrintRows);


        // 6. Determine columns to display (visible columns or all non-hidden)
        $hiddenOnTable = $resolver->getHiddenFields()['onTable'] ?? [];
        $allColumns = array_diff_key($resolver->getFieldDefinitions(), array_flip($hiddenOnTable));

        $visibleColumns = json_decode($request->columns, true);
        if (!empty($visibleColumns)) {
            $columns = array_intersect_key($allColumns, array_flip($visibleColumns));
        } else {
            $columns = $allColumns;
        }

        // 7. Render print view
        return view('qf::print.data-table', [
            'configKey' => $configKey,
            'records' => $records,
            'columns' => $columns,
            'title' => $resolver->getConfig()['pageTitle'] ?? 'Data Export',
            'truncated' => $truncated,
            'limit' => $limit,
        ]);
    }

    /**
     * Get searchable fields (direct columns, not relationships)
     */
    protected function getSearchableFields(ConfigResolver $resolver): array
    {
        $fields = [];
        foreach ($resolver->getFieldDefinitions() as $field => $def) {
            if (isset($def['relationship']))
                continue;
            if (($def['searchable'] ?? true) === false)
                continue;
            $fields[] = $field;
        }
        return $fields;
    }

    /**
     * Match search term against select options (keys or labels)
     */
    protected function getSearchKeysForSelectField(array $options, string $searchTerm): array
    {
        $lowerTerm = strtolower($searchTerm);
        $matched = [];
        foreach ($options as $key => $label) {
            if (str_contains(strtolower($key), $lowerTerm) || str_contains(strtolower($label), $lowerTerm)) {
                $matched[] = $key;
            }
        }
        return array_unique($matched);
    }

    /**
     * Apply active filters (same as DataTable's applyActiveFilters)
     */
    protected function applyActiveFilters($query, array $filters, ConfigResolver $resolver): void
    {
        foreach ($filters as $filter) {
            if (empty($filter['field']) || !isset($filter['value']))
                continue;
            $field = $filter['field'];
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'];

            if ($value === '' || $value === null)
                continue;

            // Determine field type
            $fieldDef = $resolver->getFieldDefinitions()[$field] ?? [];
            $type = $this->mapFieldTypeToFilterType($fieldDef['field_type'] ?? 'string');

            switch ($type) {
                case 'string':
                    $this->applyStringFilter($query, $field, $operator, $value);
                    break;
                case 'number':
                    $this->applyNumberFilter($query, $field, $operator, $value);
                    break;
                case 'date':
                    $this->applyDateFilter($query, $field, $operator, $value);
                    break;
                case 'boolean':
                    $this->applyBooleanFilter($query, $field, $operator, $value);
                    break;
                case 'select':
                    $this->applySelectFilter($query, $field, $operator, $value);
                    break;
                default:
                    $query->where($field, $operator, $value);
            }
        }
    }

    protected function mapFieldTypeToFilterType(string $fieldType): string
    {
        return match ($fieldType) {
            'string', 'textarea', 'text' => 'string',
            'number', 'integer', 'float' => 'number',
            'datepicker', 'datetimepicker' => 'date',
            'checkbox', 'boolcheckbox', 'radio' => 'boolean',
            'select' => 'select',
            default => 'string',
        };
    }

    protected function applyStringFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'contains':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value . '%');
                break;
            case 'ends_with':
                $query->where($field, 'like', '%' . $value);
                break;
            default:
                $query->where($field, $value);
        }
    }

    protected function applyNumberFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
            case 'less_than':
                $query->where($field, '<', $value);
                break;
            case 'greater_than_or_equals':
                $query->where($field, '>=', $value);
                break;
            case 'less_than_or_equals':
                $query->where($field, '<=', $value);
                break;
            case 'between':
                if (is_array($value) && isset($value['min'], $value['max'])) {
                    $query->whereBetween($field, [$value['min'], $value['max']]);
                }
                break;
        }
    }

    protected function applyDateFilter($query, $field, $operator, $value)
    {
        $now = now();
        switch ($operator) {
            case 'equals':
                $query->whereDate($field, $value);
                break;
            case 'not_equals':
                $query->whereDate($field, '!=', $value);
                break;
            case 'greater_than':
                $query->whereDate($field, '>', $value);
                break;
            case 'less_than':
                $query->whereDate($field, '<', $value);
                break;
            case 'between':
                if (isset($value['start'], $value['end'])) {
                    $query->whereBetween($field, [$value['start'], $value['end']]);
                }
                break;
            case 'today':
                $query->whereDate($field, $now->toDateString());
                break;
            case 'this_week':
                $query->whereBetween($field, [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth($field, $now->month)->whereYear($field, $now->year);
                break;
            case 'this_year':
                $query->whereYear($field, $now->year);
                break;
            // Add other presets as needed
        }
    }

    protected function applyBooleanFilter($query, $field, $operator, $value)
    {
        if ($value !== '') {
            $query->where($field, (bool) $value);
        }
    }

    protected function applySelectFilter($query, $field, $operator, $value)
    {
        if ($operator === 'in' || is_array($value)) {
            $query->whereIn($field, (array) $value);
        } else {
            $query->where($field, $value);
        }
    }

    protected function usesSoftDeletes(string $modelClass): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($modelClass));
    }
}