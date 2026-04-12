<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $logName     e.g., 'hr.employee'
     * @param string $action      created, updated, deleted, custom_action
     * @param mixed  $subject     The model that was affected (optional)
     * @param array  $oldValues   (optional)
     * @param array  $newValues   (optional)
     * @param string|null $description
     * @param array|null $extra   extra properties (IP, user agent, route, etc.)
     * @return ActivityLog
     */
    public static function log(
        string $logName,
        string $action,
        $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        ?array $extra = null
    ): ActivityLog {
        $causer = Auth::user();

        $log = new ActivityLog([
            'log_name'    => $logName,
            'action'      => $action,
            'description' => $description,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'properties'  => $extra ?? [],
        ]);

        if ($causer) {
            $log->causer()->associate($causer);
        }

        if ($subject && is_object($subject)) {
            $log->subject()->associate($subject);
        }

        $log->save();

        return $log;
    }

    public static function created(string $logName, $subject, array $attributes, ?string $description = null): ActivityLog
    {
        return self::log($logName, 'created', $subject, [], $attributes, $description);
    }

    public static function updated(string $logName, $subject, array $old, array $new, ?string $description = null): ActivityLog
    {
        return self::log($logName, 'updated', $subject, $old, $new, $description);
    }

    public static function deleted(string $logName, $subject, array $old, ?string $description = null): ActivityLog
    {
        return self::log($logName, 'deleted', $subject, $old, [], $description);
    }
}