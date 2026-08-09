<?php

namespace QuickerFaster\UILibrary\Console\Commands;

use Illuminate\Console\Command;
use QuickerFaster\UILibrary\Models\ReportSchedule;
use QuickerFaster\UILibrary\Jobs\GenerateReportJob;

class GenerateScheduledReports extends Command
{
    protected $signature = 'reports:generate-scheduled';
    protected $description = 'Generate all due scheduled reports';

    public function handle(): int
    {
        $schedules = ReportSchedule::where('status', 'active')
            ->where('next_run_at', '<=', now())
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No scheduled reports due.');
            return self::SUCCESS;
        }

        foreach ($schedules as $schedule) {
            $this->info("Dispatching: {$schedule->name}");
            GenerateReportJob::dispatch($schedule->id);
        }

        $this->info("Dispatched {$schedules->count()} scheduled report(s).");
        return self::SUCCESS;
    }
}