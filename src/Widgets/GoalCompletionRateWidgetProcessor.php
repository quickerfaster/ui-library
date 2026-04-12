<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;

class GoalCompletionRateWidgetProcessor
{
    public function process(array $definition): array
    {
        $period = $definition['period'] ?? 'year'; // year, quarter, month
        $year = $definition['year'] ?? date('Y');

        $query = DB::table('goals')
            ->whereYear('target_date', $year);

        if ($period === 'quarter') {
            $quarter = $definition['quarter'] ?? ceil(now()->month / 3);
            $query->whereQuarter('target_date', $quarter);
        } elseif ($period === 'month') {
            $month = $definition['month'] ?? now()->month;
            $query->whereMonth('target_date', $month);
        }

        $total = $query->count();
        $completed = $query->clone()->where('status', 'completed')->count();

        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Goal Completion Rate',
            'value' => $rate . '%',
            'icon'  => $definition['icon'] ?? 'fas fa-check-circle',
            'width' => $definition['width'] ?? 4,
        ];
    }
}