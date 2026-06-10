<?php

namespace App\Modules\Hr\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Modules\Hr\Models\PayrollRun;

class PayrollRunSummaryExport implements ShouldQueue
{
    use SerializesModels;

    protected PayrollRun $run;
    protected string $currencySymbol;
    protected string $companyName;

    public function __construct(PayrollRun $run, string $currencySymbol, string $companyName)
    {
        $this->run = $run;
        $this->currencySymbol = $currencySymbol;
        $this->companyName = $companyName;
    }

    public function generate(): string
    {
        // Load payslips in chunks to avoid memory exhaustion
        $payslips = $this->run->payslips()->with('employee')->cursor(); // lazy collection

        // Pass cursor to view – but DomPDF still needs all data to render.
        // For very large runs, you may need to split into multiple PDFs.
        // We'll use chunking and concatenation if needed. For now, we assume
        // the total records fit within memory if we process lazily.

        $pdf = Pdf::loadView('hr::livewire.payroll.exports.payroll_run_summary_pdf', [
            'run' => $this->run,
            'payslips' => $payslips,
            'currencySymbol' => $this->currencySymbol,
            'companyName' => $this->companyName,
        ]);

        return $pdf->output();
    }
}