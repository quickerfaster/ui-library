<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Modules\Hr\Models\PayrollRun;
use PDF;
use Illuminate\Http\Request;
use App\Modules\Hr\Services\PayrollReportService;
use App\Http\Controllers\Controller;



class PayrollReportController extends Controller
{


public function __construct(
        private PayrollReportService $reportService
    ) {}

    public function show(PayrollRun $payrollRun)
    {

        $data = $this->reportService->generateReportData($payrollRun);
        return view('hr::components.livewire.bootstrap.payroll.reports.run-report', [
            'reportData' => $data,
            'payrollRun' => $payrollRun
        ]);
    }

    public function downloadPdf(PayrollRun $payrollRun)
    {
        $data = $this->reportService->generateReportData($payrollRun);
        $pdf = PDF::loadView('hr::components.livewire.bootstrap.payroll.reports.run-report-pdf', $data);

        return $pdf->download("payroll-report-{$payrollRun->payroll_number}.pdf");
    }



    public function downloadExcel(PayrollRun $payrollRun)
    {
        // Implement Excel export using Maatwebsite/Excel
        // For now, return CSV
        $data = $this->reportService->generateReportData($payrollRun);

        $headers = ['Employee', 'Gross Pay', 'Deductions', 'Net Pay'];
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($data['employees'] as $emp) {
            fputcsv($output, [
                $emp['employee_name'] . ' (' . $emp['employee_number'] . ')',
                $emp['gross_pay'],
                $emp['total_deductions'],
                $emp['net_pay']
            ]);
        }

        $filename = "payroll-report-{$payrollRun->payroll_number}.csv";
        return response()->streamDownload(function () use ($output) {
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
