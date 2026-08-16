<?php

namespace QuickerFaster\UILibrary\Services\Widgets;

use QuickerFaster\UILibrary\Widgets\StatWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ChartWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ActionCardWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ActivityLogWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ListWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\MetricWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\OnboardingWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ProfileHeaderWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ProgressWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\TrendWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\GroupedListWidgetProcessor;

class WidgetProcessor
{
    protected array $map = [
        'stat'        => StatWidgetProcessor::class,
        'chart'       => ChartWidgetProcessor::class,
        'list'        => ListWidgetProcessor::class,
        'grouped_list' => GroupedListWidgetProcessor::class,

        'progress'    => ProgressWidgetProcessor::class, 
        'metric'      => MetricWidgetProcessor::class,
        'trend'       => TrendWidgetProcessor::class,
        'onboarding'  => OnboardingWidgetProcessor::class,
        'action_card' => ActionCardWidgetProcessor::class,
        'activity_log' => ActivityLogWidgetProcessor::class,
        'profile_header' => ProfileHeaderWidgetProcessor::class,



    ];

    public function process(array $definition): array
    {
        $type = $definition['type'] ?? 'stat';
        $class = $this->map[$type] ?? StatWidgetProcessor::class;
        return (new $class())->process($definition);
    }

    public function processAll(array $definitions): array
    {
        $result = [];
        foreach ($definitions as $def) {
            $result[] = $this->process($def);
        }
        return $result;
    }
}