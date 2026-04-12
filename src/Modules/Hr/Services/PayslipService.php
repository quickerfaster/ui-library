<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\PayrollPayslip;
use PDF;

class PayslipService
{
    public function generatePdf(PayrollPayslip $payslip)
    {
        // Load employee and payroll run data
        $payslip->load(['employee', 'payrollRun']);

        $data = [
            'company' => [
                'name' => 'Agriwatts Nig. Ltd. ',//config('app.name'),
                'address' => '123 Business St, San Francisco, CA 94107',
                'phone' => '(555) 123-4567',
                'logo_path' => public_path('images/company-logo.png') // Optional
            ],
            'employee' => [
                'name' => $payslip->employee->first_name . ' ' . $payslip->employee->last_name,
                'id' => $payslip->employee->employee_number,
                'address' => $this->formatEmployeeAddress($payslip->employee),
            ],
            'payroll_run' => [
                'title' => $payslip->payrollRun->title,
                'period_start' => $payslip->payrollRun->pay_period_start,
                'period_end' => $payslip->payrollRun->pay_period_end,
                'prepared_by' => $payslip->payrollRun->created_by,
                'approved_by' => $payslip->payrollRun->approved_by,
            ],
            'payslip' => [
                'number' => $payslip->payslip_number,
                'base_salary' => $payslip->base_salary,
                'overtime_pay' => $payslip->overtime_pay,
                'bonus_amount' => $payslip->bonus_amount,
                'allowance_amount' => $payslip->allowance_amount,
                'gross_pay' => $payslip->gross_pay,
                'tax_deductions' => $payslip->tax_deductions,
                'benefit_deductions' => $payslip->benefit_deductions,
                'other_deductions' => $payslip->other_deductions,
                'total_deductions' => $payslip->total_deductions,
                'net_pay' => $payslip->net_pay,
                'paid_at' => $payslip->paid_at,
            ]
        ];


        return PDF::loadView('hr::components.livewire.bootstrap.payroll.payslips.payslip-pdf', $data);
    }

    private function formatEmployeeAddress($employee): string
    {
        $parts = [];
        if ($employee->address_street) $parts[] = $employee->address_street;
        if ($employee->address_city) $parts[] = $employee->address_city;
        if ($employee->address_state) $parts[] = $employee->address_state;
        if ($employee->address_postal_code) $parts[] = $employee->address_postal_code;
        if ($employee->address_country) $parts[] = $employee->address_country;

        return implode(', ', $parts);
    }
}
