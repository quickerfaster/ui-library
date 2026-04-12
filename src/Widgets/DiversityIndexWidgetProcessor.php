<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;

class DiversityIndexWidgetProcessor
{
    public function process(array $definition): array
    {
        $groups = $definition['groups'] ?? ['gender', 'ethnicity']; // fields to analyze
        $weights = $definition['weights'] ?? ['gender' => 0.5, 'ethnicity' => 0.5];

        $total = DB::table('employees')->where('status', 'Active')->count();
        if ($total === 0) {
            return [
                'type'  => 'stat',
                'title' => $definition['title'] ?? 'Diversity Index',
                'value' => 'N/A',
                'width' => $definition['width'] ?? 4,
            ];
        }

        $scores = [];
        foreach ($groups as $group) {
            // Calculate percentage of minority groups (non-majority)
            // For simplicity, assume majority is the most common value
            $distribution = DB::table('employees')
                ->where('status', 'Active')
                ->select($group, DB::raw('count(*) as count'))
                ->groupBy($group)
                ->get();

            $maxCount = $distribution->max('count');
            $majorityPercentage = ($maxCount / $total) * 100;
            // Diversity score = 100 - majority percentage
            $scores[$group] = 100 - $majorityPercentage;
        }

        // Weighted average
        $totalScore = 0;
        $totalWeight = 0;
        foreach ($groups as $group) {
            $weight = $weights[$group] ?? 1;
            $totalScore += ($scores[$group] ?? 0) * $weight;
            $totalWeight += $weight;
        }

        $index = $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Diversity Index',
            'value' => $index,
            'icon'  => $definition['icon'] ?? 'fas fa-chart-pie',
            'width' => $definition['width'] ?? 4,
            'description' => 'Higher score indicates greater diversity',
        ];
    }
}