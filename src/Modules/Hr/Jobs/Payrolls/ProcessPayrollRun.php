<?php

namespace App\Modules\Hr\Jobs\Payrolls;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Services\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\Log;



class ProcessPayrollRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum execution time (in seconds) for this job.
     * Set to 2 hours to allow processing of large payroll runs.
     */
    public $timeout = 7200;

    /**
     * Number of times to retry the job if it fails.
     * We set to 1 to prevent duplicate calculations. NO RETRYING
     */
    public $tries = 1;

    /**
     * The number of seconds to wait before retrying the job.
     * Not used because tries = 1, but included for completeness and as an example.
     */
    public $backoff = [60, 300, 600];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 1;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public $failOnTimeout = true;

    protected PayrollRun $payrollRun;

    public function __construct(PayrollRun $payrollRun)
    {
        $this->payrollRun = $payrollRun;
    }

    /**
     * Execute the job.
     */
    public function handle(PayrollCalculator $calculator): void
    {
        try {
            // Ensure the job is not processed if the run is already completed or failed
            if (in_array($this->payrollRun->calculation_status, ['completed', 'failed'])) {
                Log::warning("Payroll run {$this->payrollRun->id} already processed (status: {$this->payrollRun->calculation_status}). Skipping.");
                return;
            }

            Log::info("Starting payroll calculation for run {$this->payrollRun->id}");

            // Mark as processing
            $this->payrollRun->update([
                'calculation_status' => 'processing',
                'failed_at' => null,
                'failure_reason' => null,
            ]);

            // Run the heavy calculation (handles chunking, progress updates internally)
            $calculator->calculate($this->payrollRun);

            Log::info("Payroll calculation completed for run {$this->payrollRun->id}");
        } catch (\Exception $e) {
            // Mark as failed with details
            $this->payrollRun->update([
                'calculation_status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => substr($e->getMessage(), 0, 500),
            ]);

            Log::error("Payroll run {$this->payrollRun->id} failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw so the queue system knows it failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("Payroll job permanently failed for run {$this->payrollRun->id}", [
            'error' => $exception->getMessage(),
            'user_id' => $this->payrollRun->created_by ?? 'unknown',
        ]);

        // Optionally send notification to admin
        // Notification::route('mail', config('payroll.admin_email'))->notify(new PayrollCalculationFailed($this->payrollRun));
    }


    /********* IN PRODUCTION CONSIDER SUPERVISOR *********
        Running the Queue Worker for Long Jobs
        For production, use Supervisor with a long timeout:

        bash
        php artisan queue:work --timeout=7300 --sleep=3 --tries=1
        Or in your Supervisor configuration (/etc/supervisor/conf.d/laravel-worker.conf):

        ini
        [program:laravel-worker]
        process_name=%(program_name)s_%(process_num)02d
        command=php /path/to/artisan queue:work --timeout=7300 --sleep=3 --tries=1 --max-time=7200
        autostart=true
        autorestart=true
        stopasgroup=true
        killasgroup=true
        user=forge
        numprocs=4
        redirect_stderr=true
        stdout_logfile=/path/to/storage/logs/worker.log
        stopwaitsecs=7200
        This ensures the queue worker does not kill long-running payroll jobs.


        Pro-Tip: If your payroll is truly massive, consider using WithoutOverlapping middleware 
        in your job to prevent two workers from ever touching the same payroll ID simultaneously.
    */




}