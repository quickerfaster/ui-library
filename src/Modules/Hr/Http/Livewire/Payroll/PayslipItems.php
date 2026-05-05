<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use App\Modules\Hr\Models\PayrollPayslip;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;

class PayslipItems extends Component
{
    use HasCurrencySymbol;

    public int $payslipId;
    public $items;
    public $currencySymbol;

    public function mount(int $recordId): void
    {
        $this->payslipId = $recordId;
        $payslip = PayrollPayslip::with('items', 'employee.employeePosition')->find($recordId);
        $this->items = $payslip ? $payslip->items : collect();
        $currencyCode = $payslip->employee->employeePosition->salary_currency ?? 'USD';
        $this->currencySymbol = $this->getCurrencySymbol($currencyCode);
    }

    public function render()
    {
        return view('hr::livewire.payroll.payslip-items');
    }
}