<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Services\PayrollRunProcessor;


use App\Modules\Hr\Models\EmployeePayrollProfile;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Models\Attendance;

use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollRunController extends Controller
{
    public function preview(PayrollRun $payrollRun)
    {
        // Ensure run is in draft status
        if (strtolower($payrollRun->status) !== 'draft') {
            abort(403, 'Only draft payroll runs can be previewed.');
        }

        // Load employees on this pay schedule
        $employeeProfiles = EmployeePayrollProfile::with([
            'employee' => function ($query) {
                $query->with('employeePosition');
            }
        ])
        ->where('pay_schedule_id', $payrollRun->pay_schedule_id)
        ->get();

        $employees = collect();
        $totalPayroll = 0;
        $warnings = collect();

        foreach ($employeeProfiles as $profile) {
            $employee = $profile->employee;
            $position = $employee->employeePosition;

            // Check for critical issues
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


            // Country labour law compliances
            if ($position->pay_type === 'salaried_daily' && in_array($position->jurisdiction, ['US', 'UK'])) {
                $warnings->push([
                    'type' => 'critical',
                    'message' => "Daily salary deductions not allowed for {$position->jurisdiction} salaried employees",
                    'impact' => 'Violates labor law - change to Hourly or Full Salaried',
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

            if ($position && $position->employment_type === 'Hourly') {

                // Ensure pay period dates exist
                if (!$payrollRun->period_start || !$payrollRun->period_end) {
                    \Log::warning('Pay period dates missing for payroll run', ['id' => $payrollRun->id]);
                    $grossPay = 0;
                } else {
                    $totalHours = Attendance::where('employee_id', $employee->employee_number)
                        ->whereBetween('date', [
                            $payrollRun->period_start,
                            $payrollRun->period_end
                        ])
                        ->where('is_approved', true)
                        ->sum('net_hours');

                    $grossPay = round($totalHours * ($position->hourly_rate ?? 0), 2);
                }
            }

            $employees->push([
                'employee' => $employee,
                'gross_pay' => $grossPay,
                'total_deductions' => $deductions,
                'net_pay' => $netPay,
                'has_critical_issue' => $hasCriticalIssue,
                'missing_bank_info' => $missingBankInfo,
            ]);
        }

        // Deduplicate warnings (group by type/message)
        $groupedWarnings = $warnings->groupBy(function ($item) {
            return $item['type'] . '|' . $item['message'];
        })->map(function ($group) {
            $first = $group->first();
            return [
                'type' => $first['type'],
                'message' => $first['message'],
                'impact' => $first['impact'],
                'fix_url' => $first['fix_url'],
                'count' => $group->count()
            ];
        })->values();

        // Update messages to show counts
        $finalWarnings = $groupedWarnings->map(function ($warning) {
            if ($warning['count'] > 1) {
                // Extract employee name from message to make generic
                $baseMessage = preg_replace('/ for .*$/', '', $warning['message']);
                $warning['message'] = "{$warning['count']} employees {$baseMessage}";
            }
            return $warning;
        });


        return view('hr::livewire.bootstrap.payroll.payroll-run-preview', [
            'run' => $payrollRun,
            'employees' => $employees,
            'totalPayroll' => $totalPayroll,
            'warnings' => $finalWarnings,
            'hasCriticalErrors' => $finalWarnings->where('type', 'critical')->isNotEmpty(),
            'criticalErrorCount' => $finalWarnings->where('type', 'critical')->sum('count'),
        ]);
    }

    public function approve(PayrollRun $payrollRun, Request $request)
    {
        // Re-validate in case data changed since preview
        $employeeProfiles = EmployeePayrollProfile::with('employee.employeePosition')
            ->where('pay_schedule_id', $payrollRun->pay_schedule_id)
            ->get();

        $hasCriticalErrors = false;
        foreach ($employeeProfiles as $profile) {
            $position = $profile->employee->employeePosition;
            if (!$position || (!$position->base_salary && !$position->hourly_rate)) {
                $hasCriticalErrors = true;
                break;
            }
        }

        if ($hasCriticalErrors) {
            return back()->withErrors(['critical_error' => 'Cannot approve: missing pay rates.']);
        }

        // Use your PayrollRunProcessor to generate payslips
        $processor = app(PayrollRunProcessor::class);
        $processor->generatePayslips($payrollRun);

        // Update run status
        $payrollRun->update([
            'status' => 'approved',
            'approved_by' => auth()->user()?->name,
            'approved_at' => now()
        ]);

        //return redirect(url("/hr/payroll-runs")) //->route('payroll.payroll-runs.index')
            //->with('success', 'Payroll run approved and payslips generated.');
    }
}
