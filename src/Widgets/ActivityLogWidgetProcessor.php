<?php

namespace QuickerFaster\UILibrary\Widgets;

use App\Modules\Admin\Models\ActivityLog;

class ActivityLogWidgetProcessor
{
    public function process(array $definition): array
    {
        $limit = $definition['limit'] ?? 5;
        $logName = $definition['log_name'] ?? null;
        $actions = $definition['actions'] ?? null;

        $query = ActivityLog::with(['causer', 'subject'])->orderBy('created_at', 'desc');

        if ($logName) {
            $query->where('log_name', $logName);
        }
        if ($actions && is_array($actions)) {
            $query->whereIn('action', $actions);
        }

        $activities = $query->limit($limit)->get();

        $items = $activities->map(function ($log) {
            return [
                'timestamp'   => $log->created_at->diffForHumans(),
                'action'      => $log->action,
                'action_label'=> ucfirst($log->action),
                'description' => $log->description ?: $this->defaultDescription($log),
                'causer_name' => $log->causer?->name ?? 'System',
                'subject_type'=> class_basename($log->subject_type ?? ''),
                'subject_id'  => $log->subject_id,
                'changes'     => $this->formatChanges($log),
            ];
        });

        return [
            'type'          => 'activity_log',
            'title'         => $definition['title'] ?? 'Recent Activity',
            'icon'          => $definition['icon'] ?? 'fas fa-history',
            'items'         => $items,
            'width'         => $definition['width'] ?? 6,
            'show_view_all' => $definition['show_view_all'] ?? false,
            'view_all_link' => $definition['view_all_link'] ?? null,
        ];
    }

    protected function defaultDescription(ActivityLog $log): string
    {
        $subjectName = class_basename($log->subject_type);
        $subjectId = $log->subject_id;
        return match($log->action) {
            'created' => "Created a new {$subjectName} (ID: {$subjectId})",
            'updated' => "Updated {$subjectName} (ID: {$subjectId})",
            'deleted' => "Deleted {$subjectName} (ID: {$subjectId})",
            default   => ucfirst($log->action) . " on {$subjectName} (ID: {$subjectId})",
        };
    }

    protected function formatChanges(ActivityLog $log): ?string
    {
        if ($log->action !== 'updated' || empty($log->old_values)) {
            return null;
        }
        $changes = [];
        foreach ($log->new_values as $field => $newValue) {
            $oldValue = $log->old_values[$field] ?? null;
            if ($oldValue != $newValue) {
                $changes[] = "{$field}: {$oldValue} → {$newValue}";
            }
        }
        return implode(', ', $changes);
    }
}