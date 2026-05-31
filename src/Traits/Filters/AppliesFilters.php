<?php

namespace QuickerFaster\UILibrary\Traits\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use QuickerFaster\UILibrary\Services\Filters\FilterService;

trait AppliesFilters
{
    /**
     * Apply a set of simple [field, operator, value] filters to a query.
     * Supports dot‑notation for relationships (e.g. 'profile.city').
     *
     * @param Builder $query
     * @param array $filters Array of [field, operator, value] triplets.
     * @param bool $mandatory Unused – kept for compatibility.
     */
    // Inside AppliesFilters trait
    protected function applyFilters($query, array $filters, bool $mandatory = false): void
    {
        $filterService = new FilterService();
        $fieldDefinitions = $this->getConfigResolver()->getFieldDefinitions();
        $filterService->applySimpleFilters($query, $filters, $fieldDefinitions);
    }

    /**
     * Apply the complex activeFilters (from UI) to the query.
     * Handles different field types, many‑to‑many relations, and custom operators.
     *
     * @param Builder $query
     */
    protected function applyActiveFilters(Builder $query): void
    {
        foreach ($this->activeFilters as $filter) {
            if (empty($filter['field']) || !isset($filter['value'])) {
                continue;
            }

            if ($filter['field'] === '_trashed') {
                continue; // handled separately in the main query
            }

            $field = $filter['field'];
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'];

            // Skip empty values (allow 0 and '0')
            if ($value === '' || $value === null || (is_array($value) && empty($value))) {
                continue;
            }

            if ($this->isManyToManyField($field)) {
                $this->applyManyToManyFilter($query, $field, $operator, $value);
                continue;
            }

            $fieldDef = $this->columns[$field] ?? [];
            $fieldType = $fieldDef['field_type'] ?? 'string';
            $type = $this->mapFieldTypeToFilterType($fieldType);

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

    /**
     * Validate a relationship chain (e.g. ['profile', 'address']) exists on the model.
     *
     * @param Model $model
     * @param array $relations
     * @return bool
     */
    private function validateRelationChain(Model $model, array $relations): bool
    {
        $currentModel = $model;

        foreach ($relations as $relation) {
            if (!method_exists($currentModel, $relation)) {
                return false;
            }

            try {
                $relationInstance = $currentModel->$relation();
                if (!$relationInstance instanceof Relation) {
                    return false;
                }
                $currentModel = $relationInstance->getRelated();
            } catch (\Throwable $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a field represents a many‑to‑many relationship.
     *
     * @param string $field
     * @return bool
     */
    protected function isManyToManyField(string $field): bool
    {
        $fieldDef = $this->columns[$field] ?? [];
        $rel = $fieldDef['relationship'] ?? null;
        if (!$rel) {
            return false;
        }
        $type = $rel['type'] ?? '';
        return in_array($type, ['belongsToMany', 'morphToMany']);
    }

    /**
     * Map a field_type from config to a filter category.
     *
     * @param string $fieldType
     * @return string
     */
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

    /**
     * Apply a string filter (equals, contains, starts_with, ends_with).
     */
    protected function applyStringFilter(Builder $query, string $field, string $operator, $value): void
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

    /**
     * Apply a numeric filter (equals, >, <, between, etc.).
     */
    protected function applyNumberFilter(Builder $query, string $field, string $operator, $value): void
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
                if (!empty($value['min'])) {
                    $query->where($field, '>=', $value['min']);
                }
                if (!empty($value['max'])) {
                    $query->where($field, '<=', $value['max']);
                }
                break;
        }
    }

    /**
     * Apply a date filter (equals, before, after, today, this_week, etc.).
     */
    protected function applyDateFilter(Builder $query, string $field, string $operator, $value): void
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
                if (!empty($value['start'])) {
                    $query->whereDate($field, '>=', $value['start']);
                }
                if (!empty($value['end'])) {
                    $query->whereDate($field, '<=', $value['end']);
                }
                break;
            case 'today':
                $query->whereDate($field, $now->toDateString());
                break;
            case 'this_week':
                $query->whereBetween($field, [
                    $now->copy()->startOfWeek()->toDateString(),
                    $now->copy()->endOfWeek()->toDateString()
                ]);
                break;
            case 'this_month':
                $query->whereMonth($field, $now->month)->whereYear($field, $now->year);
                break;
            case 'this_year':
                $query->whereYear($field, $now->year);
                break;
            case 'last_week':
                $lastWeek = $now->copy()->subWeek();
                $query->whereBetween($field, [
                    $lastWeek->copy()->startOfWeek()->toDateString(),
                    $lastWeek->copy()->endOfWeek()->toDateString()
                ]);
                break;
            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                $query->whereMonth($field, $lastMonth->month)->whereYear($field, $lastMonth->year);
                break;
            case 'last_year':
                $lastYear = $now->copy()->subYear();
                $query->whereYear($field, $lastYear->year);
                break;
            case 'last_7_days':
                $query->whereDate($field, '>=', $now->subDays(7));
                break;
            case 'next_30_days':
                $query->whereBetween($field, [$now, $now->addDays(30)]);
                break;
            case 'this_quarter':
                $query->whereBetween($field, [$now->startOfQuarter(), $now->endOfQuarter()]);
                break;
            case 'last_quarter':
                $lastQuarterStart = $now->subQuarter()->startOfQuarter();
                $query->whereBetween($field, [$lastQuarterStart, $lastQuarterStart->copy()->endOfQuarter()]);
                break;
        }
    }

    /**
     * Apply a boolean filter.
     */
    protected function applyBooleanFilter(Builder $query, string $field, string $operator, $value): void
    {
        if ($value !== '') {
            $query->where($field, $value);
        }
    }

    /**
     * Apply a select (dropdown) filter – supports single or multiple values.
     */
    protected function applySelectFilter(Builder $query, string $field, string $operator, $value): void
    {
        if ($value === '' || $value === null) {
            return;
        }
        if ($operator === 'in' || is_array($value)) {
            $query->whereIn($field, (array) $value);
        } else {
            $query->where($field, $value);
        }
    }

    /**
     * Apply a many‑to‑many relationship filter (e.g. tags, categories).
     * Expects the field definition to contain a 'relationship' array with:
     * - pivot_table
     * - foreign_pivot_key
     * - related_pivot_key
     * - morph_type (optional)
     */
    protected function applyManyToManyFilter(Builder $query, string $field, string $operator, $value): void
    {
        if (empty($value)) {
            return;
        }

        $fieldDef = $this->columns[$field] ?? [];
        $rel = $fieldDef['relationship'] ?? [];

        $pivotTable = $rel['pivot_table'] ?? null;
        $foreignPivotKey = $rel['foreign_pivot_key'] ?? null;
        $relatedPivotKey = $rel['related_pivot_key'] ?? null;
        $morphType = $rel['morph_type'] ?? null;

        if (!$pivotTable || !$foreignPivotKey || !$relatedPivotKey) {
            // Fallback to whereHas (less efficient but works without full config)
            $relationName = $rel['dynamic_property'] ?? $field;
            $query->whereHas($relationName, function ($q) use ($value) {
                $q->whereIn('id', (array) $value);
            });
            return;
        }

        $table = $query->getModel()->getTable();

        // Only 'in' operator is supported for many‑to‑many (any of the selected)
        $query->whereExists(function ($sub) use ($table, $pivotTable, $foreignPivotKey, $relatedPivotKey, $morphType, $value) {
            $sub->select(\DB::raw(1))
                ->from($pivotTable)
                ->whereColumn("{$pivotTable}.{$foreignPivotKey}", "{$table}.id")
                ->whereIn("{$pivotTable}.{$relatedPivotKey}", (array) $value);
            if ($morphType) {
                $modelClass = $this->getConfigResolver()->getModel();
                $sub->where("{$pivotTable}.{$morphType}", $modelClass);
            }
        });
    }
}