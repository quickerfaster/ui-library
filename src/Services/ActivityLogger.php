<?php

namespace QuickerFaster\UILibrary\Services;

use Illuminate\Support\Facades\Log;

/**
 * ActivityLogger stub for the UI Library.
 *
 * This replaces the App\Modules\Admin\Services\ActivityLogger reference
 * that was previously coupled to the QuickHR application.
 *
 * Consuming applications should bind their own implementation
 * in their AppServiceProvider:
 *
 *   $this->app->singleton(
 *       \QuickerFaster\UILibrary\Services\ActivityLogger::class,
 *       \App\Services\ActivityLogger::class
 *   );
 */
class ActivityLogger
{
    /**
     * Log an activity event.
     *
     * @param string $action
     * @param string $model
     * @param mixed  $modelId
     * @param array  $data
     * @return void
     */
    public function log(string $action, string $model, $modelId, array $data = []): void
    {
        // Stub implementation — consuming apps should override this.
    }

    /**
     * Log a record creation event.
     *
     * Called from DataTableForm and WizardForm after a new record is created.
     * If activity logging is not configured, this is a graceful no-op.
     *
     * @param string $logName The config key (e.g., 'module.resource').
     * @param mixed  $record  The Eloquent model that was created.
     * @param array  $data    The data that was saved.
     * @return void
     */
    public static function created(string $logName, $record, array $data): void
    {
        Log::debug('ActivityLogger: record created.', [
            'log_name' => $logName,
            'model'    => $record ? get_class($record) : null,
            'model_id' => $record->id ?? null,
            'data'     => $data,
        ]);
    }

    /**
     * Log a record update event.
     *
     * Called from DataTableForm and WizardForm after an existing record is updated.
     * If activity logging is not configured, this is a graceful no-op.
     *
     * @param string $logName The config key (e.g., 'module.resource').
     * @param mixed  $record  The Eloquent model that was updated.
     * @param array  $old     The original values for changed fields.
     * @param array  $new     The new values for changed fields.
     * @return void
     */
    public static function updated(string $logName, $record, array $old, array $new): void
    {
        Log::debug('ActivityLogger: record updated.', [
            'log_name' => $logName,
            'model'    => $record ? get_class($record) : null,
            'model_id' => $record->id ?? null,
            'old'      => $old,
            'new'      => $new,
        ]);
    }
}