<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use QuickerFaster\UILibrary\Models\ReportSchedule;
use QuickerFaster\UILibrary\Services\Reports\ReportEngine;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Queue and connection are driven by config so each consuming app
     * can choose sync (local dev), database (single worker.sh), Redis,
     * or any other queue driver without touching library source code.
     *
     * @see config('ui-library.features.queue_connection')
     * @see config('ui-library.features.queue_name')
     */
    public function __construct(public int $scheduleId)
    {
        $this->queue = config('ui-library.features.queue_name', 'default');
        $this->connection = config('ui-library.features.queue_connection', 'sync');
    }

    public function handle(ReportEngine $engine): void
    {
        $schedule = ReportSchedule::find($this->scheduleId);
        if (!$schedule || !$schedule->isDue()) {
            return;
        }

        $engine->process($schedule);
    }
}