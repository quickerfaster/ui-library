<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
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

        $items = [];

        if ($model && class_exists($model)) {
            $query = $model::query();

            // Apply conditions
            foreach ($conditions as $condition) {
                $query->where(...$condition);
            }

            // Apply sorting
            $query->orderBy($sort[0], $sort[1] ?? 'asc');

            // Load records with relationships if needed for dot notation fields
            $relations = $this->extractRelationsFromColumns($columns);
            if (!empty($relations)) {
                $query->with($relations);
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
                $items[] = $item;
            }
        }

        return [
            'type'        => 'list',
            'title'       => $definition['title'] ?? 'List',
            'description' => $definition['description'] ?? '',
            'icon'        => $definition['icon'] ?? null,
            'columns'     => $columns,
            'items'       => $items,
            'width'       => $definition['width'] ?? 6,
            'showViewAll' => $definition['show_view_all'] ?? false,
            'viewAllLink' => $definition['view_all_link'] ?? null,
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
                if (!$value) return '';
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
}






