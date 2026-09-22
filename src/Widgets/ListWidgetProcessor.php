<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Services\Filters\FilterService;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class ListWidgetProcessor
{
    use ResolvesDateStrings;

    public function process(array $definition): array
    {
        $model = $definition['model'] ?? null;
        $limit = $definition['limit'] ?? 5;
        $sort = $definition['sort'] ?? ['created_at', 'desc'];
        $columns = $definition['columns'] ?? [];
        $conditions = $this->resolveConditions($definition['conditions'] ?? []);
        $idField = $definition['id_field'] ?? null;
        $rowActions = $definition['row_actions'] ?? [];

        $items = [];

        if ($model && class_exists($model)) {
            $query = $model::query();


            // ✅ Reuse the filter logic – dot notation works automatically
            $filterService = new FilterService();
            $filterService->applySimpleFilters($query, $conditions);


            // Apply sorting
            $query->orderBy($sort[0], $sort[1] ?? 'asc');

            // Load records with relationships if needed for dot notation fields
            $relations = $this->extractRelationsFromColumns($columns);
            // Also extract relations from row action {{ field }} placeholders
            if (!empty($rowActions)) {
                $relations = array_merge($relations, $this->extractRelationsFromRowActions($rowActions));
            }
            if (!empty($relations)) {
                $query->with(array_unique($relations));
            }

            $records = $query->limit($limit)->get();

            // Build items array with formatted values
            foreach ($records as $record) {
                $item = [];
                foreach ($columns as $col) {
                    $field = $col['field'] ?? null;
                    $value = $field ? data_get($record, $field) : null;
                    $label = $col['label'] ?? $field;

                    // Optional formatting (e.g., date, number, expiry_warning)
                    if (isset($col['format'])) {
                        $value = $this->formatValue($value, $col['format'], $record, $field);
                    }

                    $item[$label] = $value;
                }

                // Resolve row actions with per-record {{ field }} placeholders
                if (!empty($rowActions)) {
                    $item['actions'] = $this->resolveRowActions($rowActions, $record);
                    if ($idField) {
                        $item['id'] = data_get($record, $idField);
                    }
                }

                $items[] = $item;
            }
        }

        return [
            'type' => 'list',
            'title' => $definition['title'] ?? 'List',
            'description' => $definition['description'] ?? '',
            'icon' => $definition['icon'] ?? null,
            'color' => $definition['color'] ?? 'primary',
            'columns' => $columns,
            'items' => $items,
            'width' => $definition['width'] ?? 6,
            'showViewAll' => $definition['show_view_all'] ?? false,
            'viewAllLink' => $definition['view_all_link'] ?? null,
            'viewAllLinkTarget' => $definition['view_all_link_target'] ?? '_self',
            'id_field' => $idField,
            'row_actions' => $rowActions,
        ];
    }

    protected function extractRelationsFromColumns(array $columns): array
    {
        $relations = [];
        foreach ($columns as $col) {
            $field = $col['field'] ?? '';
            // Extract relationship parts before the first dot
            $parts = explode('.', $field);
            if (count($parts) > 1) {
                $relations[] = $parts[0];
            }
        }
        return array_unique($relations);
    }

    /**
     * Extract relationship names from row action {{ field }} placeholders.
     */
    protected function extractRelationsFromRowActions(array $actions): array
    {
        $relations = [];
        foreach ($actions as $action) {
            $this->collectRelationsFromValue($action['params'] ?? [], $relations);
        }
        return array_unique($relations);
    }

    /**
     * Recursively collect dot-notation relation names from a value.
     */
    protected function collectRelationsFromValue($value, array &$relations): void
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->collectRelationsFromValue($v, $relations);
            }
        } elseif (is_string($value)) {
            preg_match_all('/\{\{\s*(.+?)\s*\}\}/', $value, $matches);
            foreach ($matches[1] as $field) {
                $field = trim($field);
                $parts = explode('.', $field);
                if (count($parts) > 1) {
                    $relations[] = $parts[0];
                }
            }
        }
    }

    /**
     * Format a value based on the specified format.
     * 
     * @param mixed $value
     * @param string $format
     * @param object|null $record The full record (for formats that need more context)
     * @param string|null $field The field name (for formats that need the field name)
     * @return string
     */
    protected function formatValue($value, string $format, $record = null, $field = null): string
    {
        switch ($format) {
            case 'date':
                return $value ? date('Y-m-d', strtotime($value)) : '';
            case 'datetime':
                return $value ? date('Y-m-d H:i', strtotime($value)) : '';
            case 'currency':
                return number_format((float) $value, 2);
            case 'number':
                return number_format((float) $value);
            case 'expiry_warning':
                if (!$value)
                    return '';
                $daysLeft = now()->diffInDays($value, false);
                if ($daysLeft <= 0) {
                    return '<span class="badge bg-danger">Expired</span>';
                } elseif ($daysLeft <= 30) {
                    return '<span class="badge bg-warning text-dark">Expires in ' . $daysLeft . ' days</span>';
                }
                return '<span class="badge bg-success">Valid</span>';
            default:
                return (string) $value;
        }
    }

    /**
     * Resolve {{ field }} placeholders in row action params against a record.
     *
     * @param array $actions
     * @param object $record
     * @return array
     */
    protected function resolveRowActions(array $actions, $record): array
    {
        $resolved = [];
        foreach ($actions as $action) {
            // Skip actions that don't match their condition (if any)
            if (!empty($action['condition']) && !$this->evaluateCondition($record, $action['condition'])) {
                continue;
            }
            $action['params'] = $this->resolveValue($action['params'] ?? [], $record);
            $resolved[] = $action;
        }
        return $resolved;
    }

    /**
     * Evaluate a simple condition tuple against a record.
     *
     * @param object $record
     * @param array  $condition  [field, operator, value]
     * @return bool
     */
    protected function evaluateCondition($record, array $condition): bool
    {
        if (count($condition) < 3) {
            return true;
        }

        [$field, $operator, $expected] = $condition;
        $actual = data_get($record, $field);

        return match ($operator) {
            '='  => $actual == $expected,
            '!=' => $actual != $expected,
            '>'  => $actual > $expected,
            '<'  => $actual < $expected,
            '>=' => $actual >= $expected,
            '<=' => $actual <= $expected,
            'in' => in_array($actual, (array) $expected),
            'not_in' => !in_array($actual, (array) $expected),
            default => true,
        };
    }

    /**
     * Recursively resolve {{ field }} placeholders in a value using data_get.
     *
     * @param mixed $value
     * @param object $record
     * @return mixed
     */
    protected function resolveValue($value, $record)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->resolveValue($v, $record);
            }
            return $value;
        }

        if (is_string($value)) {
            return preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) use ($record) {
                $field = trim($matches[1]);
                $resolved = data_get($record, $field);
                return $resolved === null ? '' : (string) $resolved;
            }, $value);
        }

        return $value;
    }
}
