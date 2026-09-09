<?php

namespace QuickerFaster\UILibrary\Widgets;


use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;
use QuickerFaster\UILibrary\Traits\Widgets\HandlesRelationshipGroupBy;

class ChartWidgetProcessor
{
    use HandlesRelationshipGroupBy, ResolvesDateStrings;

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

                // Check if this is a date-based group_by (month, week, day, year)
                $dateGroupBys = ['month', 'week', 'day', 'year'];
                if (in_array($groupBy, $dateGroupBys)) {
                    $dateColumn = $definition['date_column'] ?? 'created_at';
                    $driver = DB::connection()->getDriverName();
                    $groupExpression = $this->getDateGroupExpression($dateColumn, $groupBy, $driver);

                    // Apply date range filter if months/period is specified
                    $months = $definition['months'] ?? ($definition['period'] ?? null);
                    if ($months) {
                        $query->where($dateColumn, '>=', now()->subMonths($months)->startOfMonth());
                    }

                    $query->select(DB::raw("$groupExpression as group_label"), DB::raw("$aggregate($field) as value"));
                    $query->groupBy(DB::raw($groupExpression));
                    $query->orderBy(DB::raw($groupExpression));
                } else {
                    // Use the trait method to build the group by expression
                    $groupExpression = $this->applyGroupByWithRelations($query, $groupBy, 'group_label');
                    $query->select(DB::raw($groupExpression), DB::raw("$aggregate($field) as value"));
                }

                $results = $query->get();

                foreach ($results as $row) {
                    $labels[] = $row->group_label;
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
            'icon' => $definition['icon'] ?? null,
            'color' => $definition['color'] ?? 'primary',
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

    /**
     * Generate a database-specific date grouping expression.
     * Mirrors TrendWidgetProcessor::getGroupExpression().
     *
     * @param string $dateField The date column name (e.g., 'created_at')
     * @param string $groupBy   One of: month, week, day, year
     * @param string $driver    Database driver name (mysql, sqlite, pgsql)
     * @return string|null      Raw SQL expression for the date grouping
     */
    protected function getDateGroupExpression(string $dateField, string $groupBy, string $driver): ?string
    {
        switch ($groupBy) {
            case 'month':
                if ($driver === 'mysql') {
                    return "CONCAT(YEAR($dateField), '-', LPAD(MONTH($dateField), 2, '0'))";
                } elseif ($driver === 'sqlite') {
                    return "strftime('%Y-%m', $dateField)";
                } elseif ($driver === 'pgsql') {
                    return "TO_CHAR($dateField, 'YYYY-MM')";
                }
                break;
            case 'week':
                if ($driver === 'mysql') {
                    return "CONCAT(YEAR($dateField), '-', LPAD(WEEK($dateField), 2, '0'))";
                } elseif ($driver === 'sqlite') {
                    return "strftime('%Y-%W', $dateField)";
                } elseif ($driver === 'pgsql') {
                    return "TO_CHAR($dateField, 'YYYY-WW')";
                }
                break;
            case 'day':
                if ($driver === 'mysql') {
                    return "DATE($dateField)";
                } elseif ($driver === 'sqlite') {
                    return "DATE($dateField)";
                } elseif ($driver === 'pgsql') {
                    return "DATE($dateField)";
                }
                break;
            case 'year':
                if ($driver === 'mysql') {
                    return "YEAR($dateField)";
                } elseif ($driver === 'sqlite') {
                    return "strftime('%Y', $dateField)";
                } elseif ($driver === 'pgsql') {
                    return "TO_CHAR($dateField, 'YYYY')";
                }
                break;
        }
        return null;
    }
}
