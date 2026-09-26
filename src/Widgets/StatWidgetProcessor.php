<?php

namespace QuickerFaster\UILibrary\Widgets;

use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;
use QuickerFaster\UILibrary\Services\Filters\FilterService;

class StatWidgetProcessor
{
    use ResolvesDateStrings, HasCurrencySymbol;

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

                $filterService = new FilterService();
                $filterService->applySimpleFilters($query, $conditions);

                $value = $query->{$aggregate}($field);
            }
        }

        // Format: apply currency symbol if configured
        $format = $definition['format'] ?? null;
        if ($format === 'currency' && is_numeric($value)) {
            $currencyCode = $definition['currency_code'] ?? 'USD';
            $symbol = $this->getCurrencySymbol($currencyCode);
            $value = $symbol . number_format((float) $value, 2);
        }

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Statistic',
            'value' => $value,
            'icon'  => $definition['icon'] ?? null,
            'color' => $definition['color'] ?? 'primary',
            'width' => $definition['width'] ?? 4,
        ];
    }
}