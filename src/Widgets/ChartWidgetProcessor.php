<?php

namespace QuickerFaster\UILibrary\Widgets;


use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class ChartWidgetProcessor
{
    use ResolvesDateStrings;

    public function process(array $definition): array
    {
        $model = $definition['model'] ?? null;
        $chartData = ['labels' => [], 'datasets' => []];

        if ($model && class_exists($model)) {
            $query = $model::query();

            // Apply common conditions first
            $conditions = $this->resolveConditions($definition['conditions'] ?? []);
            foreach ($conditions as $condition) {
                $query->where(...$condition);
            }

            $labels = [];
            $values = [];

            // CASE 1: Many-to-Many Relationship (e.g., Spatie Roles -> Users)
// Inside ChartWidgetProcessor.php

            if (isset($definition['relationship'])) {
                $rel = $definition['relationship'];

                // Get roles that actually have users attached to them
                $results = $query->withCount($rel)
                    ->has($rel) // Only show roles that have at least 1 user
                    ->get();

                foreach ($results as $row) {
                    // Spatie roles use the 'name' column (e.g., 'admin', 'editor')
                    $labels[] = ucfirst($row->name);
                    $values[] = $row->{"{$rel}_count"};
                }
            }

            // CASE 2: Standard Group By (e.g., status, type)
            elseif ($groupBy = ($definition['group_by'] ?? null)) {
                $aggregate = $definition['aggregate'] ?? 'count';
                $field = $definition['field'] ?? '*';

                $results = $query->select($groupBy, DB::raw("$aggregate($field) as value"))
                    ->groupBy($groupBy)
                    ->get();

                foreach ($results as $row) {
                    $labels[] = $row->$groupBy;
                    $values[] = $row->value;
                }
            }
            
            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => $definition['title'] ?? 'Chart',
                        'data' => $values,
                        'backgroundColor' => $this->getColors(count($values)),
                    ]
                ],
            ];
        }

        return [
            'type' => 'chart',
            'title' => $definition['title'] ?? 'Chart',
            'chart_id' => 'chart-' . uniqid(),
            'chart_data' => $chartData,
            'chart_type' => $definition['chart_type'] ?? 'bar',
            'width' => $definition['width'] ?? 6,
        ];
    }

    protected function getColors(int $count): array
    {
        $palette = ['#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236', '#166a8f', '#00a950', '#58595b', '#8549ba'];
        // Repeat palette if more items than colors
        return array_slice(array_merge(...array_fill(0, ceil($count / 9) ?: 1, $palette)), 0, $count);
    }
}
