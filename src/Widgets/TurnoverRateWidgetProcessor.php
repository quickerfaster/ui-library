<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TurnoverRateWidgetProcessor
{
    public function process(array $definition): array
    {
        $type = $definition['turnover_type'] ?? 'voluntary'; // 'voluntary', 'involuntary', 'total'
        $months = $definition['months'] ?? 12;
        $endDate = $definition['end_date'] ? Carbon::parse($definition['end_date']) : now();
        $startDate = $endDate->copy()->subMonths($months)->startOfMonth();

        // Get terminations count of the specified type
        $terminationsQuery = DB::table('employees')
            ->whereNotNull('termination_date')
            ->whereBetween('termination_date', [$startDate, $endDate]);

        if ($type === 'voluntary') {
            $terminationsQuery->where('termination_reason', 'voluntary');
        } elseif ($type === 'involuntary') {
            $terminationsQuery->where('termination_reason', 'involuntary');
        }

        $terminations = $terminationsQuery->count();

        // Calculate average headcount over the period
        $avgHeadcount = DB::table('employees')
            ->where('status', 'Active')
            ->where('hire_date', '<=', $endDate)
            ->count(); // Simplified; more accurate would be monthly average

        // You can implement a more accurate monthly average query:
        /*
        $monthsCount = $startDate->diffInMonths($endDate) + 1;
        $totalHeadcount = 0;
        for ($i = 0; $i < $monthsCount; $i++) {
            $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $totalHeadcount += DB::table('employees')
                ->where('hire_date', '<=', $monthEnd)
                ->where(function($q) use ($monthEnd) {
                    $q->whereNull('termination_date')
                      ->orWhere('termination_date', '>=', $monthStart);
                })
                ->count();
        }
        $avgHeadcount = $totalHeadcount / $monthsCount;
        */

        $turnoverRate = $avgHeadcount > 0 ? round(($terminations / $avgHeadcount) * 100, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? ucfirst($type) . ' Turnover Rate',
            'value' => $turnoverRate . '%',
            'icon'  => $definition['icon'] ?? 'fas fa-chart-line',
            'width' => $definition['width'] ?? 4,
            'description' => "Last {$months} months",
        ];
    }
}