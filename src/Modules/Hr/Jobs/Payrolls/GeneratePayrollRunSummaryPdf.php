<?php

namespace App\Modules\Hr\Jobs\Payrolls;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use QuickerFaster\UILibrary\Models\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\Hr\Models\PayrollRun;

class GeneratePayrollRunSummaryPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle()
    {
        $export = Export::find($this->exportId);
        if (!$export || $export->status !== 'pending') {
            return;
        }

        $export->update(['status' => 'processing']);

        try {
            $run = PayrollRun::with(['paySchedule', 'payslips.employee'])->find($export->options['run_id']);
            $currencySymbol = $export->options['currency_symbol'];
            $companyName = $export->options['company_name'];

            // Load payslips lazily to avoid memory exhaustion
            $payslips = $run->payslips()->with('employee')->cursor();

            $pdf = Pdf::loadView('hr::livewire.payroll.exports.payroll_run_summary_pdf', [
                'run' => $run,
                'payslips' => $payslips,
                'currencySymbol' => $currencySymbol,
                'companyName' => $companyName,
            ]);

            $relativePath = 'exports/' . uniqid() . '.pdf';
            Storage::disk('local')->put($relativePath, $pdf->output());

            $fileSize = Storage::disk('local')->size($relativePath);

            $export->update([
                'status' => 'completed',
                'file_path' => $relativePath,
                'file_size' => $fileSize,
                'download_token' => \Str::random(64),
                'expires_at' => now()->addHour(),
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            \Log::error('Payroll run summary PDF generation failed', ['export_id' => $export->id, 'error' => $e->getMessage()]);
        }
    }
}