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

    public function __construct(public int $scheduleId) {}

    public function handle(ReportEngine $engine): void
    {
        $schedule = ReportSchedule::find($this->scheduleId);
        if (!$schedule || !$schedule->isDue()) {
            return;
        }

        $engine->process($schedule);
    }
}