<?php

// To be moved to the app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Run daily at 2 AM
    $schedule->command('qf:clean-exports --days=1')->dailyAt('02:00');
}


// Optional  config/qf.php
/*return [
    'export_retention_days' => env('EXPORT_RETENTION_DAYS', 1),
];

$days = (int) $this->option('days') ?: config('qf.export_retention_days', 1);

.env EXPORT_RETENTION_DAYS=7 */
