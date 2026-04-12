<?php

namespace QuickerFaster\UILibrary\Widgets;

use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class ProgressWidgetProcessor
{

    use ResolvesDateStrings;
    public function process(array $definition): array
    {

        // Allow direct values (bypass queries)
        if (isset($definition['current_value']) && isset($definition['target_value'])) {
            $currentValue = $definition['current_value'];
            $targetValue = $definition['target_value'];
        } else {

            $model = $definition['model'] ?? null;
            $aggregate = $definition['aggregate'] ?? 'count';
            $field = $definition['field'] ?? '*';
            $conditions = $definition['conditions'] ?? [];
            $target = $definition['target'] ?? null;
            $targetField = $definition['target_field'] ?? null;
            $targetModel = $definition['target_model'] ?? null;
            $targetAggregate = $definition['target_aggregate'] ?? 'count';
            $targetConditions = $definition['target_conditions'] ?? [];

            $currentValue = 0;
            $targetValue = 100; // default target

            // Get current value
            if ($model && class_exists($model)) {
                $query = $model::query();
                foreach ($conditions as $condition) {
                    $query->where(...$condition);
                }
                $currentValue = $query->{$aggregate}($field);
            }

            // Determine target
            if ($target !== null) {
                // Static target
                $targetValue = (float) $target;
            } elseif ($targetModel && class_exists($targetModel)) {
                // Dynamic target from another model/query
                $query = $targetModel::query();
                foreach ($targetConditions as $condition) {
                    $query->where(...$condition);
                }
                $targetValue = $query->{$targetAggregate}($targetField ?? '*');
                $targetValue = (float) $targetValue;
            }
        }

        // Prevent division by zero
        $percentage = $targetValue > 0 ? ($currentValue / $targetValue) * 100 : 0;

        return [
            'type' => 'progress',
            'title' => $definition['title'] ?? 'Progress',
            'current_value' => $currentValue,
            'target_value' => $targetValue,
            'percentage' => round($percentage, 1),
            'icon' => $definition['icon'] ?? null,
            'width' => $definition['width'] ?? 4,
        ];
    }
}