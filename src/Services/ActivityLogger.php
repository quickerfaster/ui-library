<?php

namespace QuickerFaster\UILibrary\Services;

use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Models\ActivityLog;

/**
 * ActivityLogger records user actions for audit trails and analytics.
 *
 * Called automatically from DataTableForm and WizardForm on every
 * create/update/delete. Consuming apps can also call log() directly
 * for custom events (login, logout, export, etc.).
 */
class ActivityLogger
{
    /**
     * Log a generic activity event.
     */
    public function log(string $action, string $model, $modelId, array $data = []): void
    {
        if (!config('ui-library.analytics.activity_logs.enabled', true)) {
            return;
        }

        $user = Auth::user();

        ActivityLog::create([
            'user_id'      => $user?->id,
            'company_id'   => $user?->current_company_id ?? null,
            'action'       => $action,
            'subject_type' => $model,
            'subject_id'   => $modelId,
            'properties'   => !empty($data) ? $data : null,
            'created_at'   => now(),
        ]);
    }

    /**
     * Log a record creation event.
     */
    public static function created(string $logName, $record, array $data): void
    {
        if (!config('ui-library.analytics.activity_logs.enabled', true)) {
            return;
        }

        $user = Auth::user();

        ActivityLog::create([
            'user_id'      => $user?->id,
            'company_id'   => $user?->current_company_id ?? null,
            'action'       => 'created',
            'subject_type' => $record ? get_class($record) : null,
            'subject_id'   => $record->id ?? null,
            'properties'   => [
                'log_name' => $logName,
                'data'     => $data,
            ],
            'created_at'   => now(),
        ]);
    }

    /**
     * Log a record update event.
     */
    public static function updated(string $logName, $record, array $old, array $new): void
    {
        if (!config('ui-library.analytics.activity_logs.enabled', true)) {
            return;
        }

        $user = Auth::user();

        ActivityLog::create([
            'user_id'      => $user?->id,
            'company_id'   => $user?->current_company_id ?? null,
            'action'       => 'updated',
            'subject_type' => $record ? get_class($record) : null,
            'subject_id'   => $record->id ?? null,
            'properties'   => [
                'log_name' => $logName,
                'old'      => $old,
                'new'      => $new,
            ],
            'created_at'   => now(),
        ]);
    }
}