<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AbsenteeismRateWidgetProcessor
{
    public function process(array $definition): array
    {
        $months = $definition['months'] ?? 1;
        $endDate = $definition['end_date'] ? Carbon::parse($definition['end_date']) : now();
        $startDate = $endDate->copy()->subMonths($months)->startOfMonth();

        $employeeTable = $definition['employee_table'] ?? config('ui-library.widgets.absenteeism_employee_table', 'employees');
        $leaveTable = $definition['leave_table'] ?? config('ui-library.widgets.absenteeism_leave_table', 'leave_requests');

        // Total scheduled work days in period (simplified: assume 22 working days per month)
        $workDaysPerMonth = 22;
        $totalScheduledDays = $months * $workDaysPerMonth * DB::table($employeeTable)->count();

        // Actual unplanned absence days
        $absentDays = DB::table($leaveTable)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->sum('workdays_count');

        $rate = $totalScheduledDays > 0 ? round(($absentDays / $totalScheduledDays) * 100, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Absenteeism Rate',
            'value' => $rate . '%',
            'icon'  => $definition['icon'] ?? 'fas fa-calendar-times',
            'width' => $definition['width'] ?? 4,
            'description' => "Last {$months} month(s)",
        ];
    }
}