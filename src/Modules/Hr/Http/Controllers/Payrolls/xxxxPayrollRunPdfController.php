<?php

namespace App\Modules\Hr\Http\Controllers\Payrolls;

use App\Modules\Hr\Models\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;

class PayrollRunPdfController extends Controller
{
    use HasCurrencySymbol;
    public function summary(PayrollRun $run)
    {
        $currencySymbol = $this->getCurrencySymbol($run->paySchedule->currency_code ?? 'USD');
        $companyName = optional($run->paySchedule->company)->name ?? config('app.name');

        $pdf = Pdf::loadView('hr::livewire.payroll.exports.payroll_run_summary_pdf', [
            'run' => $run,
            'currencySymbol' => $currencySymbol,
            'companyName' => $companyName,
        ]);

        return $pdf->download("payroll_run_{$run->id}_summary.pdf");
    }
}