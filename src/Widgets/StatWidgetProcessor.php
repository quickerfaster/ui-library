<?php

namespace QuickerFaster\UILibrary\Widgets;

use QuickerFaster\UILibrary\Contracts\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;
use QuickerFaster\UILibrary\Services\Filters\FilterService;

class StatWidgetProcessor
{
    use ResolvesDateStrings;



    public function process(array $definition): array
    {
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

                // ✅ Reuse the filter logic – dot notation works automatically
                $filterService = new FilterService();
                $filterService->applySimpleFilters($query, $conditions);

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