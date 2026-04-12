<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;

class HeadcountVsBudgetWidgetProcessor
{
    public function process(array $definition): array
    {
        $actualHeadcount = DB::table('employees')->where('status', 'Active')->count();
        $budgetedHeadcount = $definition['budgeted_headcount'] ?? DB::table('budgets')->where('year', date('Y'))->value('headcount');

        $variance = $actualHeadcount - $budgetedHeadcount;
        $percentage = $budgetedHeadcount > 0 ? round(($actualHeadcount / $budgetedHeadcount) * 100, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Headcount vs Budget',
            'value' => "{$actualHeadcount} / {$budgetedHeadcount} ({$percentage}%)",
            'icon'  => $definition['icon'] ?? 'fas fa-users',
            'width' => $definition['width'] ?? 4,
            'description' => $variance >= 0 ? "{$variance} over budget" : (abs($variance) . " under budget"),
        ];
    }
}