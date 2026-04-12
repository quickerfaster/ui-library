<?php

namespace QuickerFaster\UILibrary\Widgets;


use QuickerFaster\UILibrary\Contracts\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

class StatWidgetProcessor
{
    use ResolvesDateStrings;
    public function process(array $definition): array
    {
        // Support custom_value (bypass model query)
        if (isset($definition['custom_value'])) {
            $value = $definition['custom_value'];
        } else {
            $model = $definition['model'] ?? null;
            $value = 'N/A';

            if ($model && class_exists($model)) {
                $aggregate = $definition['aggregate'] ?? 'count';
                $field = $definition['field'] ?? '*';
                $conditions = $this->resolveConditions($definition['conditions'] ?? []);
                $relationship = $definition['relationship'] ?? null;
                $relationValue = $definition['relation_value'] ?? null;

                $query = $model::query();

                if ($relationship && $relationValue) {
                    $query->whereHas($relationship, function ($q) use ($relationValue) {
                        $q->where('name', $relationValue)->orWhere('id', $relationValue);
                    });
                }

                foreach ($conditions as $condition) {
                    $query->where(...$condition);
                }

                $value = $query->{$aggregate}($field);
            }
        }

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Statistic',
            'value' => $value,
            'icon'  => $definition['icon'] ?? null,
            'width' => $definition['width'] ?? 4,
        ];
    }
}
