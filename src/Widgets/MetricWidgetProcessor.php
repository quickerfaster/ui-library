<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class MetricWidgetProcessor
{
    use ResolvesDateStrings;
    public function process(array $definition): array
    {
        $model = $definition['model'] ?? null;
        $value = 'N/A';
        $previousValue = null;
        $changePercentage = null;
        $trend = null;

        if ($model && class_exists($model)) {
            $aggregate = $definition['aggregate'] ?? 'count';
            $field = $definition['field'] ?? '*';
            $conditions = $this->resolveConditions($definition['conditions'] ?? []);
            $relationship = $definition['relationship'] ?? null;
            $relationValue = $definition['relation_value'] ?? null;
            $compareTo = $definition['compare_to'] ?? null;

            // Current value query
            $currentQuery = $model::query();
            $this->applyFilters($currentQuery, $conditions, $relationship, $relationValue);
            $value = $currentQuery->{$aggregate}($field);

            // Previous value (if compare_to is set)
            if ($compareTo) {
                $previousQuery = $model::query();
                $previousConditions = $this->getPreviousPeriodConditions($conditions, $compareTo);
                $this->applyFilters($previousQuery, $previousConditions, $relationship, $relationValue);
                $previousValue = $previousQuery->{$aggregate}($field);

                if ($previousValue && $previousValue != 0) {
                    $changePercentage = (($value - $previousValue) / $previousValue) * 100;
                    $trend = $changePercentage > 0 ? 'up' : ($changePercentage < 0 ? 'down' : 'flat');
                }
            }
        }

        return [
            'type'              => 'metric',
            'title'             => $definition['title'] ?? 'Metric',
            'value'             => $this->formatValue($value, $definition['format'] ?? null),
            'previous_value'    => $previousValue !== null ? $this->formatValue($previousValue, $definition['format'] ?? null) : null,
            'change_percentage' => $changePercentage !== null ? round(abs($changePercentage), 1) : null,
            'trend'             => $trend,
            'icon'              => $definition['icon'] ?? null,
            'width'             => $definition['width'] ?? 4,
            'description'       => $definition['description'] ?? null,
        ];
    }

    protected function applyFilters($query, array $conditions, ?string $relationship, ?string $relationValue): void
    {
        // Apply relationship filter (e.g., users with a specific role)
        if ($relationship && $relationValue) {
            $query->whereHas($relationship, function ($q) use ($relationValue) {
                $q->where('name', $relationValue)->orWhere('id', $relationValue);
            });
        }

        // Apply standard conditions
        foreach ($conditions as $condition) {
            $query->where(...$condition);
        }
    }

    protected function getPreviousPeriodConditions(array $conditions, string $compareTo): array
    {
        // Modify date conditions to previous period
        $modified = [];
        foreach ($conditions as $condition) {
            if (count($condition) >= 3 && in_array($condition[1], ['>', '>=', '<', '<=', 'between', '=', '>=', '<='])) {
                // This is a simplistic approach; for production, you'd want smarter date handling.
                // For now, we'll leave conditions unchanged; the previous period logic is handled separately.
                // You can extend this to shift date ranges by subtracting one period (e.g., month, year).
            }
            $modified[] = $condition;
        }
        return $modified; // For simplicity, returning same conditions; in real usage you'd adjust date filters.
    }

    protected function formatValue($value, ?string $format = null): string
    {
        if ($value === null) {
            return 'N/A';
        }

        switch ($format) {
            case 'currency':
                return '$' . number_format((float) $value, 2);
            case 'number':
                return number_format((float) $value);
            case 'percent':
                return number_format((float) $value, 1) . '%';
            default:
                return (string) $value;
        }
    }
}