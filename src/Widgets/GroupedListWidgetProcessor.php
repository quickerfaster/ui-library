<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Services\Filters\FilterService;
use QuickerFaster\UILibrary\Traits\Widgets\HandlesRelationshipGroupBy;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class GroupedListWidgetProcessor
{
    use HandlesRelationshipGroupBy, ResolvesDateStrings;

    public function process(array $definition): array
    {
        $model = $definition['model'] ?? null;
        $groupBy = $definition['group_by'] ?? null;
        $aggregates = $definition['aggregates'] ?? [];
        $columns = $definition['columns'] ?? [];
        $conditions = $this->resolveConditions($definition['conditions'] ?? []);
        $sort = $definition['sort'] ?? ['group_label', 'asc'];

        $items = [];

        if ($model && class_exists($model) && $groupBy && !empty($aggregates)) {
            $query = $model::query();

            // Apply conditions
            $filterService = new FilterService();
            $filterService->applySimpleFilters($query, $conditions);

            // Use the trait to build the group by expression
            $groupExpression = $this->applyGroupByWithRelations($query, $groupBy, 'group_label');

            $mainTable = $query->getModel()->getTable();
            $select = [DB::raw($groupExpression)];

            foreach ($aggregates as $field => $func) {
                // Prefix with main table if no dot (avoid ambiguous column errors)
                $qualifiedField = str_contains($field, '.') ? $field : "$mainTable.$field";
                $select[] = DB::raw("{$func}({$qualifiedField}) as {$field}_{$func}");
            }
            $query->select($select);

            // Apply sorting
            $sortField = $sort[0] === 'group_label' ? 'group_label' : $sort[0];
            $query->orderBy($sortField, $sort[1] ?? 'asc');

            $results = $query->get();

            // Build items array with formatted values
            foreach ($results as $row) {
                $item = [];
                foreach ($columns as $col) {
                    $field = $col['field'] ?? '';
                    $label = $col['label'] ?? $field;
                    $format = $col['format'] ?? null;
                    $value = data_get($row, $field);
                    if ($format) {
                        $value = $this->formatValue($value, $format);
                    }
                    $item[$label] = $value;
                }
                $items[] = $item;
            }
        }

        return [
            'type' => 'list',
            'title' => $definition['title'] ?? 'Grouped List',
            'description' => $definition['description'] ?? '',
            'icon' => $definition['icon'] ?? null,
            'color' => $definition['color'] ?? 'primary',
            'columns' => $columns,
            'items' => $items,
            'width' => $definition['width'] ?? 6,
            'showViewAll' => $definition['show_view_all'] ?? false,
            'viewAllLink' => $definition['view_all_link'] ?? null,
        ];
    }

    protected function formatValue($value, string $format): string
    {
        switch ($format) {
            case 'currency':
                return number_format((float) $value, 2);
            case 'number':
                return number_format((float) $value);
            case 'date':
                return $value ? date('Y-m-d', strtotime($value)) : '';
            default:
                return (string) $value;
        }
    }
}