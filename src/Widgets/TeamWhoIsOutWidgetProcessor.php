<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Carbon;
use QuickerFaster\UILibrary\Services\Filters\FilterService;
use QuickerFaster\UILibrary\Traits\Widgets\ResolvesDateStrings;

/**
 * TeamWhoIsOutWidgetProcessor — renders a list of team members who are
 * on leave today or within a configurable date range.
 *
 * This widget is domain-independent. Data can be provided in two ways:
 *
 * 1. **Pre-resolved data** via the `data` key (backward-compatible):
 *    The consuming app resolves members ahead of time and passes them in.
 *
 * 2. **Model-based query** via the `model` key (recommended):
 *    The processor queries the model directly, applying conditions and
 *    date-range filtering. The consuming app only needs to specify the
 *    model class and optional conditions.
 *
 * Widget definition (model-based):
 *   [
 *       'type'             => 'team_whos_out',
 *       'title'            => 'Team Who\'s Out',
 *       'icon'             => 'fas fa-users-slash',
 *       'color'            => 'warning',
 *       'date_range'       => 'today',        // 'today' | 'this_week' | 'next_7_days'
 *       'limit'            => 10,
 *       'width'            => 6,
 *       'model'            => 'App\\Modules\\Leave\\Models\\LeaveRequest',
 *       'conditions'       => [['status', '=', 'Approved']],
 *       'date_field'       => 'start_date',   // field checked for date range
 *       'end_date_field'   => 'end_date',     // end of the leave period
 *       'status_field'     => 'status',       // status column
 *       'approved_status'  => 'Approved',     // value that means "approved"
 *       'employee_relation'=> 'employee',     // relation name on the model
 *       'leave_type_relation' => 'leaveType', // relation for leave type name
 *   ]
 *
 * Widget definition (pre-resolved data):
 *   [
 *       'type'        => 'team_whos_out',
 *       'title'       => 'Team Who\'s Out',
 *       'data'        => [['name' => '...', 'leave_type' => '...', ...]],
 *   ]
 */
class TeamWhoIsOutWidgetProcessor
{
    use ResolvesDateStrings;

    public function process(array $definition): array
    {
        $dateRange = $definition['date_range'] ?? 'today';
        $limit = (int) ($definition['limit'] ?? 10);

        // Pre-resolved data takes priority (backward-compatible)
        $members = $definition['data'] ?? [];

        // If no pre-resolved data but a model is configured, query it
        if (empty($members) && !empty($definition['model'])) {
            $members = $this->queryModel($definition);
        }

        // If still no members, return empty state
        if (empty($members)) {
            return [
                'type'        => 'team_whos_out',
                'title'       => $definition['title'] ?? 'Team Who\'s Out',
                'description' => $definition['description'] ?? null,
                'icon'        => $definition['icon'] ?? 'fas fa-users-slash',
                'color'       => $definition['color'] ?? 'warning',
                'date_range'  => $dateRange,
                'members'     => [],
                'empty_state' => $definition['empty_state'] ?? 'Everyone is in today! 🎉',
                'width'       => $definition['width'] ?? 6,
            ];
        }

        // Slice to limit
        $members = array_slice($members, 0, $limit);

        return [
            'type'        => 'team_whos_out',
            'title'       => $definition['title'] ?? 'Team Who\'s Out',
            'description' => $definition['description'] ?? null,
            'icon'        => $definition['icon'] ?? 'fas fa-users-slash',
            'color'       => $definition['color'] ?? 'warning',
            'date_range'  => $dateRange,
            'members'     => $members,
            'empty_state' => $definition['empty_state'] ?? 'Everyone is in today! 🎉',
            'width'       => $definition['width'] ?? 6,
        ];
    }

    /**
     * Query the configured model for team members currently on leave.
     *
     * Applies configurable conditions, date-range filtering (start_date <= today
     * AND end_date >= today), eager-loads relationships, and transforms results
     * into the member array format expected by the blade template.
     */
    protected function queryModel(array $definition): array
    {
        $modelClass = $definition['model'];

        if (!class_exists($modelClass)) {
            return [];
        }

        $dateField = $definition['date_field'] ?? 'start_date';
        $endDateField = $definition['end_date_field'] ?? 'end_date';
        $statusField = $definition['status_field'] ?? 'status';
        $approvedStatus = $definition['approved_status'] ?? 'Approved';
        $employeeRelation = $definition['employee_relation'] ?? 'employee';
        $leaveTypeRelation = $definition['leave_type_relation'] ?? 'leaveType';
        $limit = (int) ($definition['limit'] ?? 10);

        $today = Carbon::today();

        $query = $modelClass::query();

        // Eager-load relationships
        $query->with([$employeeRelation, $leaveTypeRelation]);

        // Apply user-defined conditions (e.g., status = 'Approved')
        $conditions = $definition['conditions'] ?? [];
        if (!empty($conditions)) {
            $filterService = new FilterService();
            $filterService->applySimpleFilters($query, $conditions);
        }

        // Date range: the leave spans today (start_date <= today AND end_date >= today)
        $query->where($dateField, '<=', $today)
              ->where($endDateField, '>=', $today);

        // Order by end_date ascending (soonest return first)
        $query->orderBy($endDateField, 'asc')
              ->limit($limit);

        $records = $query->get();

        return $records->map(function ($record) use (
            $employeeRelation,
            $leaveTypeRelation,
            $dateField,
            $endDateField
        ) {
            $employee = $record->{$employeeRelation};
            $leaveType = $record->{$leaveTypeRelation};

            $startDate = $record->{$dateField};
            $endDate = $record->{$endDateField};

            // Build member array matching blade template expectations
            return [
                'name'       => $employee
                    ? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''))
                    : 'Unknown',
                'photo_url'  => $employee->photo_url ?? null,
                'leave_type' => $leaveType->name ?? null,
                'leave_color'=> $this->leaveTypeColor($leaveType->name ?? null),
                'dates'      => $this->formatDateRange($startDate, $endDate),
                'return_date'=> $this->formatReturnDate($endDate),
                'color'      => 'secondary',
            ];
        })->toArray();
    }

    /**
     * Format a date range like "Jan 5 – Jan 8".
     */
    protected function formatDateRange($startDate, $endDate): string
    {
        if (!$startDate || !$endDate) {
            return '';
        }

        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        if ($start->isSameDay($end)) {
            return $start->format('M j');
        }

        // Same month: "Jan 5 – 8"
        if ($start->format('M') === $end->format('M')) {
            return $start->format('M j') . ' – ' . $end->format('j');
        }

        // Different months: "Jan 5 – Feb 8"
        return $start->format('M j') . ' – ' . $end->format('M j');
    }

    /**
     * Format the return date as "Returns Jan 9".
     */
    protected function formatReturnDate($endDate): ?string
    {
        if (!$endDate) {
            return null;
        }

        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $returnDate = $end->copy()->addDay();

        return $returnDate->format('M j');
    }

    /**
     * Map leave type name to a Bootstrap color for the badge.
     */
    protected function leaveTypeColor(?string $leaveTypeName): string
    {
        if (!$leaveTypeName) {
            return 'info';
        }

        return match (true) {
            str_contains(strtolower($leaveTypeName), 'annual') => 'primary',
            str_contains(strtolower($leaveTypeName), 'sick') => 'danger',
            str_contains(strtolower($leaveTypeName), 'maternity') => 'pink',
            str_contains(strtolower($leaveTypeName), 'paternity') => 'info',
            str_contains(strtolower($leaveTypeName), 'bereavement') => 'dark',
            str_contains(strtolower($leaveTypeName), 'study') => 'success',
            str_contains(strtolower($leaveTypeName), 'unpaid') => 'secondary',
            str_contains(strtolower($leaveTypeName), 'casual') => 'warning',
            default => 'info',
        };
    }
}