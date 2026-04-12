<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\PayrollRun;
use Illuminate\Support\Collection;
use QuickerFaster\UILibrary\Services\GUI\SweetAlertService;

class PayrollReportService
{
    /**
     * Generate structured report data for a payroll run
     */
    public function generateReportData(PayrollRun $payrollRun): array
    {
        // Ensure run is approved/paid
        if (!in_array($payrollRun->status, ['approved', 'paid'])) {
            //SweetAlertService::showError($this, "Error!", 'Report only available for approved/paid runs');
            echo ("<h3>Report only available for approved/paid runs!</h3> <br /> <a>Back to Run Pay</a>");
            exit;

            //throw new \InvalidArgumentException('Report only available for approved/paid runs');
        }

        $payslips = $payrollRun->payslips()->with('employee')->get();

        return [
            'header' => $this->buildHeader($payrollRun),
            'employees' => $this->buildEmployeeData($payslips),
            'summary' => $this->buildSummary($payslips)
        ];
    }

    private function buildHeader(PayrollRun $run): array
    {
        return [
            'title' => $run->title,
            'payroll_number' => $run->payroll_number,
            'pay_period_start' => $run->period_start,
            'pay_period_end' => $run->period_end,
            'status' => $run->status,
            'prepared_by' => $run->created_by,
            'approved_by' => $run->approved_by,
            'prepared_at' => $run->created_at,
            'approved_at' => $run->approved_at,
        ];
    }

    private function buildEmployeeData(Collection $payslips): array
    {
        return $payslips->map(function ($payslip) {
            return [
                'employee_name' => $payslip->employee->first_name . ' ' . $payslip->employee->last_name,
                'employee_number' => $payslip->employee->employee_number,
                'gross_pay' => $payslip->gross_pay,
                'total_deductions' => $payslip->total_deductions,
                'net_pay' => $payslip->net_pay,
                'payslip_number' => $payslip->payslip_number,
                'payslip_id' => $payslip->id,
            ];
        })->toArray();
    }

    private function buildSummary(Collection $payslips): array
    {
        return [
            'total_gross' => $payslips->sum('gross_pay'),
            'total_deductions' => $payslips->sum('total_deductions'),
            'total_net' => $payslips->sum('net_pay'),
        ];
    }
}
