<?php

namespace QuickerFaster\UILibrary\Services\Widgets;

use QuickerFaster\UILibrary\Widgets\AbsenteeismRateWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\StatWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ChartWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ActionCardWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ActivityLogWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\DiversityIndexWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ENPSWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\GoalCompletionRateWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\HeadcountVsBudgetWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ListWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\MetricWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\OfferAcceptanceRateWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\OnboardingWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\ProgressWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\TrainingCompletionRateWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\TrendWidgetProcessor;
use QuickerFaster\UILibrary\Widgets\TurnoverRateWidgetProcessor;

class WidgetProcessor
{
    protected array $map = [
        'stat'        => StatWidgetProcessor::class,
        'chart'       => ChartWidgetProcessor::class,
        'list'        => ListWidgetProcessor::class,
        'progress'    => ProgressWidgetProcessor::class, 
        'metric'      => MetricWidgetProcessor::class,
        'trend'       => TrendWidgetProcessor::class,
        'onboarding'  => OnboardingWidgetProcessor::class,
        'action_card' => ActionCardWidgetProcessor::class,
        'activity_log' => ActivityLogWidgetProcessor::class,



        // New custom processors
        'turnover_rate' => TurnoverRateWidgetProcessor::class,
        'enps'          => ENPSWidgetProcessor::class,
        'absenteeism_rate' => AbsenteeismRateWidgetProcessor::class,
        'goal_completion'  => GoalCompletionRateWidgetProcessor::class,
        'training_completion' => TrainingCompletionRateWidgetProcessor::class,
        'headcount_vs_budget' => HeadcountVsBudgetWidgetProcessor::class,
        'diversity_index' => DiversityIndexWidgetProcessor::class,
        'offer_acceptance' => OfferAcceptanceRateWidgetProcessor::class,
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