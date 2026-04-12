<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class TrendWidgetProcessor
{

    use ResolvesDateStrings;
    public function process(array $definition): array
    {
        $model = $definition['model'] ?? null;
        $aggregate = $definition['aggregate'] ?? 'count';
        $field = $definition['field'] ?? '*';
        $groupBy = $definition['group_by'] ?? 'month';
        $period = $definition['period'] ?? 6;
        $conditions = $this->resolveConditions($definition['conditions'] ?? []);
        $dateField = $definition['date_field'] ?? 'created_at';
        $trendType = $definition['trend_type'] ?? 'line';

        $labels = [];
        $values = [];

        if ($model && class_exists($model)) {
            $query = $model::query();

            foreach ($conditions as $condition) {
                $query->where(...$condition);
            }

            $dateRange = $this->getDateRange($groupBy, $period);
            $query->whereBetween($dateField, [$dateRange['start'], $dateRange['end']]);

            $driver = DB::connection()->getDriverName();
            $groupExpression = $this->getGroupExpression($dateField, $groupBy, $driver);

            if ($groupExpression) {
                $select = DB::raw("$groupExpression as period_label, $aggregate($field) as value");
                $results = $query->select($select)
                    ->groupBy(DB::raw($groupExpression))
                    ->orderBy(DB::raw($groupExpression))
                    ->get();

                foreach ($results as $row) {
                    $labels[] = $row->period_label;
                    $values[] = (float) $row->value;
                }
            }
        }

        // Calculate trend change
        $change = null;
        $trendDirection = null;
        if (count($values) >= 2) {
            $current = $values[count($values) - 1];
            $previous = $values[count($values) - 2];
            if ($previous != 0) {
                $change = round((($current - $previous) / $previous) * 100, 1);
                $trendDirection = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat');
            }
        }

        return [
            'type'            => 'trend',
            'title'           => $definition['title'] ?? 'Trend',
            'icon'            => $definition['icon'] ?? null,
            'labels'          => $labels,
            'values'          => $values,
            'change'          => $change,
            'trendDirection'  => $trendDirection,
            'chart_type'      => $trendType,
            'width'           => $definition['width'] ?? 6,
        ];
    }

    protected function getDateRange(string $groupBy, int $period): array
    {
        $now = now();
        switch ($groupBy) {
            case 'month':
                $start = $now->copy()->subMonths($period)->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'week':
                $start = $now->copy()->subWeeks($period)->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'day':
                $start = $now->copy()->subDays($period)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            default:
                $start = $now->copy()->subMonths($period)->startOfMonth();
                $end = $now->copy()->endOfMonth();
        }
        return ['start' => $start, 'end' => $end];
    }

    protected function getGroupExpression(string $dateField, string $groupBy, string $driver): ?string
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
        }
        return null;
    }
}