<?php

namespace App\Modules\Hr\Http\Livewire;

use Livewire\Component;

use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\EmployeePayrollProfile;
use App\Modules\Hr\Models\Attendance;

use App\Modules\Hr\Services\PayrollRunProcessor;

class PayrollRunPreview extends Component
{
    public $payrollRunId;
    public $run;
    public $employees = [];
    public $totalPayroll = 0;
    public $warnings = [];
    public $hasCriticalErrors = false;
    public $individualWarnings = [];

    //protected $listeners = ['openPreviewModal' => 'loadPreview'];
    protected $listeners = [
        'openPayrollPreviewModalEvent' => 'openPayrollPreviewModal',
        'closePreviewModal' => 'closePreview',
    ];

    /*public function openPayrollPreview($params)
    {
dd($params);
        //$this->payrollRunId = $payrollRunId;
        //$this->loadPreviewData();
       // $this->showPreviewModal = true;
    }*/





    public function openPayrollPreviewModal($params)
    {

        $payrollRunId = $params["payroll_run_id"]?? null;
        if (!isset($payrollRunId))
            throw new \Exception("PayrollPreview data cannot be loaded [payroll_run_id] not provided");





        $this->payrollRunId = $payrollRunId;
       $this->run = PayrollRun::with('paySchedule')->findOrFail($payrollRunId);

        if (strtolower($this->run->status) !== 'draft') {
            $this->dispatch('swal:error', ['title' => 'Error', 'text' => 'Only draft runs can be previewed']);
            return;
        }

        $this->loadPreviewData();
        $this->dispatch('open-modal-event', ['modalId' => 'payrollRunPreview']);
    }

private function loadPreviewData()
{
    $employeeProfiles = EmployeePayrollProfile::with([
        'employee' => fn($q) => $q->with('employeePosition')
    ])->where('pay_schedule_id', $this->run->pay_schedule_id)->get();

    $this->employees = [];
    $this->totalPayroll = 0;
    $rawWarnings = []; // For individual fixes
    $groupableWarnings = []; // For UI grouping

    foreach ($employeeProfiles as $profile) {
        $employee = $profile->employee;
        $position = $employee->employeePosition;

        $hasCriticalIssue = false;
        $missingBankInfo = false;

        // Critical: Missing pay rate
        if (!$position || (!$position->base_salary && !$position->hourly_rate)) {
            $hasCriticalIssue = true;
            $warning = [
                'type' => 'critical',
                'message' => "Missing pay rate",
                'impact' => 'Employee will not be paid',
                'employee_name' => "{$employee->first_name} {$employee->last_name}",
                'employee_position_id' => $position?->id,
                'fix_model' => 'App\Modules\Hr\Models\EmployeePosition'
            ];
            $rawWarnings[] = $warning;
            $groupableWarnings[] = $warning;
        }

        // Warning: Missing bank info
        if (empty($profile->bank_account)) {
            $missingBankInfo = true;
            $warning = [
                'type' => 'warning',
                'message' => "Missing bank details",
                'impact' => 'Payment may fail',
                'employee_name' => "{$employee->first_name} {$employee->last_name}",
                'employee_payroll_profile_id' => $profile->id,
                'fix_model' => 'App\Modules\Hr\Models\EmployeePayrollProfile'
            ];
            $rawWarnings[] = $warning;
            $groupableWarnings[] = $warning;
        }

        // Calculate pay
        $grossPay = 0;
        if ($position) {
            if ($position->employment_type === 'Hourly') {
                $totalHours = Attendance::where('employee_id', $employee->employee_number)
                    ->whereBetween('date', [$this->run->pay_period_start, $this->run->pay_period_end])
                    ->where('is_approved', true)
                    ->sum('net_hours');
                $grossPay = round($totalHours * ($position->hourly_rate ?? 0), 2);
            } else {
                $grossPay = $position->base_salary ?? 0;
            }
        }

        $this->employees[] = [
            'employee' => $employee,
            'gross_pay' => $grossPay,
            'net_pay' => $grossPay,
            'has_critical_issue' => $hasCriticalIssue,
            'missing_bank_info' => $missingBankInfo,
        ];

        $this->totalPayroll += $grossPay;
    }

    // Store individual warnings for "Fix Now" actions
    $this->individualWarnings = $rawWarnings;

    // Group warnings for UI display
    $grouped = collect($groupableWarnings)->groupBy('message')->map(function ($group, $message) {
        return [
            'type' => $group[0]['type'],
            'message' => count($group) > 1
                ? count($group) . ' employees ' . $message
                : $group[0]['employee_name'] . ' ' . $message,
            'impact' => $group[0]['impact'],
        ];
    })->values();

    $this->warnings = $grouped->toArray();
    $this->hasCriticalErrors = $grouped->where('type', 'critical')->isNotEmpty();
}

    public function approvePayroll()
    {
        // Re-validate
        $hasErrors = collect($this->employees)->contains('has_critical_issue', true);
        if ($hasErrors) {
            $this->dispatch('swal:error', ['title' => 'Error', 'text' => 'Cannot approve: missing pay rates']);
            return;
        }

        // Call your processor
        $processor = app(PayrollRunProcessor::class);
        $processor->generatePayslips($this->run);

        $this->run->update([
            'status' => 'approved',
            'approved_by' => auth()->user()?->name,
            'approved_at' => now()
        ]);

        $this->dispatch('close-modal-event', ['modalId' => 'addEditModal']);
        $this->dispatch('swal:success', ['title' => 'Success', 'text' => 'Payroll approved!']);
        $this->dispatch('refresh-data-table'); // Assuming you have this
    }



/**
 * Open edit modal for any record (reusable for multiple models)
 *
 * @param int $recordId
 * @param string $modelClass Full model class name (e.g., 'App\Models\EmployeePayrollProfile')
 * @return void
 */
public function fixRecord($recordId, $modelClass)
{
    // Validate model
    $allowedModels = [
        'App\Modules\Hr\Models\EmployeePayrollProfile' => 'employee-payroll-profiles',
        'App\Modules\Hr\Models\EmployeePosition' => 'employee-positions',
        'App\Modules\Hr\Models\Employee' => 'employees',
    ];

    if (!isset($allowedModels[$modelClass])) {
        $this->dispatch('swal:error', ['title' => 'Error', 'text' => 'Invalid model']);
        return;
    }

    // Close preview modal
    $this->dispatch('close-modal-event', ['modalId' => 'payrollRunPreview']);

    // Redirect to the correct page with edit ID
    $page = $allowedModels[$modelClass];
    return redirect()->to("/hr/{$page}?edit={$recordId}");
}



public function generateReportData()
{
    $payslips = $this->payslips()->with('employee')->get();

    return [
        'header' => [
            'title' => $this->title,
            'payroll_number' => $this->payroll_number,
            'pay_period_start' => $this->pay_period_start,
            'pay_period_end' => $this->pay_period_end,
            'status' => $this->status,
            'prepared_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'prepared_at' => $this->created_at,
            'approved_at' => $this->approved_at,
        ],
        'employees' => $payslips->map(function ($payslip) {
            return [
                'employee_name' => $payslip->employee->first_name . ' ' . $payslip->employee->last_name,
                'employee_number' => $payslip->employee->employee_number,
                'gross_pay' => $payslip->gross_pay,
                'total_deductions' => $payslip->total_deductions,
                'net_pay' => $payslip->net_pay,
                'payslip_number' => $payslip->payslip_number,
            ];
        }),
        'summary' => [
            'total_gross' => $payslips->sum('gross_pay'),
            'total_deductions' => $payslips->sum('total_deductions'),
            'total_net' => $payslips->sum('net_pay'),
        ]
    ];
}





















    public function render()
    {
        return view('hr::components.livewire.bootstrap.payroll.payroll-run-preview');

    }

}





