<?php

namespace App\Modules\Hr\Http\Livewire;


use Livewire\Component;
use Illuminate\Support\Collection;
use Carbon\Carbon;

use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\EmployeePayrollProfile;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Models\Attendance;

use App\Modules\Hr\Services\PayrollRunProcessor;


class PayrollRunManager extends Component
{
    public $payrollRunId;
    public $showPreviewModal = false;
    public $showApproveConfirm = false;

    // Preview data
    public $run;
    public $employees;
    public $totalPayroll = 0;
    public $warnings;
    public $hasCriticalErrors = false;
    public $criticalErrorCount = 0;



    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->showApproveConfirm = false;
        $this->resetPreviewData();
    }

    private function loadPreviewData()
    {
        $this->run = PayrollRun::with('paySchedule')->findOrFail($this->payrollRunId);

        if ($this->run->status !== 'draft') {
            $this->dispatchBrowserEvent('show-error', ['message' => 'Only draft runs can be previewed']);
            return;
        }

        $employeeProfiles = EmployeePayrollProfile::with([
            'employee' => fn($q) => $q->with('employeePosition')
        ])->where('pay_schedule_id', $this->run->pay_schedule_id)->get();

        $this->employees = collect();
        $this->totalPayroll = 0;
        $warnings = collect();

        foreach ($employeeProfiles as $profile) {
            $employee = $profile->employee;
            $position = $employee->employeePosition;

            $hasCriticalIssue = false;
            $missingBankInfo = false;

            // Critical: Missing pay rate
            if (!$position || (!$position->base_salary && !$position->hourly_rate)) {
                $hasCriticalIssue = true;
                $warnings->push([
                    'type' => 'critical',
                    'employee_id' => $employee->id,
                    'message' => "Missing pay rate for {$employee->first_name} {$employee->last_name}",
                    'impact' => 'Employee will not be paid',
                    'fix_url' => route('hr.employee-positions.edit', $employee->id)
                ]);
            }

            // Warning: Missing bank info
            if (empty($profile->bank_account)) {
                $missingBankInfo = true;
                $warnings->push([
                    'type' => 'warning',
                    'employee_id' => $employee->id,
                    'message' => "Missing bank details for {$employee->first_name} {$employee->last_name}",
                    'impact' => 'Payment may fail',
                    'fix_url' => route('payroll.payroll-employees.edit', $profile->id)
                ]);
            }

            // Calculate pay
            $grossPay = 0;
            $deductions = 0;
            $netPay = 0;

            if ($position) {
                if ($position->employment_type === 'Hourly') {
                    $totalHours = Attendance::where('employee_id', $employee->employee_number)
                        ->where('date', '>=', $this->run->pay_period_start)
                        ->where('date', '<=', $this->run->pay_period_end)
                        ->where('is_approved', true)
                        ->sum('net_hours');
                    $grossPay = round($totalHours * ($position->hourly_rate ?? 0), 2);
                } else {
                    $grossPay = $position->base_salary ?? 0;
                }
                $netPay = $grossPay - $deductions;
                $this->totalPayroll += $netPay;
            }

            $this->employees->push([
                'employee' => $employee,
                'gross_pay' => $grossPay,
                'total_deductions' => $deductions,
                'net_pay' => $netPay,
                'has_critical_issue' => $hasCriticalIssue,
                'missing_bank_info' => $missingBankInfo,
            ]);
        }

        // Group and deduplicate warnings
        $groupedWarnings = $warnings->groupBy(function ($item) {
            return $item['type'] . '|' . preg_replace('/ for .*$/', '', $item['message']);
        })->map(function ($group) {
            $first = $group->first();
            $baseMessage = preg_replace('/ for .*$/', '', $first['message']);
            return [
                'type' => $first['type'],
                'message' => $group->count() > 1
                    ? "{$group->count()} employees {$baseMessage}"
                    : $first['message'],
                'impact' => $first['impact'],
                'fix_url' => $first['fix_url'],
                'count' => $group->count()
            ];
        })->values();

        $this->warnings = $groupedWarnings;
        $this->hasCriticalErrors = $groupedWarnings->where('type', 'critical')->isNotEmpty();
        $this->criticalErrorCount = $groupedWarnings->where('type', 'critical')->sum('count');
    }

    private function resetPreviewData()
    {
        $this->run = null;
        $this->employees = null;
        $this->totalPayroll = 0;
        $this->warnings = null;
        $this->hasCriticalErrors = false;
        $this->criticalErrorCount = 0;
    }

    public function approvePayroll()
    {
        // Re-validate
        $employeeProfiles = EmployeePayrollProfile::with('employee.employeePosition')
            ->where('pay_schedule_id', $this->payrollRunId)
            ->get();

        $hasCriticalErrors = $employeeProfiles->contains(function ($profile) {
            $position = $profile->employee->employeePosition;
            return !$position || (!$position->base_salary && !$position->hourly_rate);
        });

        if ($hasCriticalErrors) {
            $this->dispatchBrowserEvent('show-error', [
                'message' => 'Cannot approve: missing pay rates for some employees.'
            ]);
            return;
        }

        // Generate payslips
        $processor = app(PayrollRunProcessor::class);
        $processor->generatePayslips(PayrollRun::findOrFail($this->payrollRunId));

        // Update status
        PayrollRun::where('id', $this->payrollRunId)->update([
            'status' => 'approved',
            'approved_by' => auth()->user()?->name,
            'approved_at' => now()
        ]);

        $this->closePreview();
        $this->dispatchBrowserEvent('show-success', [
            'message' => 'Payroll run approved and payslips generated.'
        ]);
        $this->dispatch('refreshDataTable'); // Refresh the list
    }

    public function render()
    {
        return view('hr::livewire.bootstrap.pages.payroll-run-manager');
    }
}
