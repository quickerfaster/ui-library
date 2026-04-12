<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\ClockEvent;
use App\Modules\Hr\Models\Attendance;
use App\Modules\Hr\Http\Controllers\ClockEventController;
use Illuminate\Support\Facades\DB;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\PayrollPayslip;
use App\Modules\Hr\Models\EmployeePayrollProfile;
use App\Modules\Hr\Models\EmployeePosition;
use Carbon\Carbon;

class PayrollRunProcessor
{
    /**
     * Generate payslips for a payroll run
     */
    public function generatePayslips(PayrollRun $run): void
    {
        // Clear existing payslips (in case of reprocessing draft)
        $run->payslips()->delete();

        // Get all employees on this pay schedule
        $employees = EmployeePayrollProfile::with([
            'employee',
            'employee.employeePosition'
        ])
        ->where('pay_schedule_id', $run->pay_schedule_id)
        ->get();

        foreach ($employees as $profile) {
            $employee = $profile->employee;
            $position = $employee->employeePosition;

            if (!$position) {
                \Log::warning("No position found for employee {$employee->employee_number}");
                continue;
            }

            // Process based on pay_type (Nigeria-compliant)
            if ($position->pay_type === 'hourly') {
                $this->processHourlyEmployee($run, $employee, $position);
            } elseif ($position->pay_type === 'salaried_daily') {
                $this->processSalariedDailyEmployee($run, $employee, $position);
            } else {
                $this->processSalariedFullEmployee($run, $employee, $position);
            }
        }
    }

    /**
     * Process hourly employee: sum approved Attendance records
     */
    private function processHourlyEmployee(PayrollRun $run, $employee, $position): void
    {
        // Get approved attendance records in pay period
        $attendances = Attendance::where('employee_id', $employee->employee_number)
            ->where('date', '>=', $run->period_start)
            ->where('date', '<=', $run->period_end)
            ->where('is_approved', true)
            ->get();

        $totalHours = $attendances->sum('net_hours');
        $hourlyRate = $position->hourly_rate ?? 0;
        $grossPay = round($totalHours * $hourlyRate, 2);

        $this->createPayslip($run, $employee, [
            'base_salary' => $grossPay, // For hourly, base = gross
            'gross_pay' => $grossPay,
            'net_pay' => $grossPay, // MVP: no deductions
        ]);
    }

    /**
     * Process salaried employee with daily deductions (Nigeria standard)
     * Pay only for days with approved clock-in attendance
     */
private function processSalariedDailyEmployee(PayrollRun $run, $employee, $position): void
{
    $profile = EmployeePayrollProfile::where('employee_id', $employee->id)->first();
    $jurisdiction = $profile->jurisdiction ?? 'NG';

    // 🇺🇸 US/🇬🇧 UK/🇪🇺 EU: Block daily deductions for salaried employees
    if (in_array($jurisdiction, ['US', 'UK', 'DE', 'FR', 'ES', 'IT'])) {
        // For compliant jurisdictions, treat as full salaried
        $grossPay = $position->base_salary ?? 0;
    }
    // 🇳🇬 Nigeria & most African countries: Allow daily deductions
    else {
        $workedDays = Attendance::where('employee_id', $employee->employee_number)
            ->whereBetween('date', [$run->period_start, $run->period_end])
            ->where('is_approved', true)
            ->where('status', 'Present')
            ->count();

        $monthlySalary = $position->base_salary ?? 0;
        $dailyRate = $monthlySalary > 0 ? $monthlySalary / 26 : 0;
        $grossPay = round($workedDays * $dailyRate, 2);
    }

    $this->createPayslip($run, $employee, [
        'base_salary' => $position->base_salary ?? 0,
        'gross_pay' => $grossPay,
        'net_pay' => $grossPay,
    ]);
}

    /**
     * Process fully salaried employee (no deductions)
     */
    private function processSalariedFullEmployee(PayrollRun $run, $employee, $position): void
    {
        $baseSalary = $position->base_salary ?? 0;
        $grossPay = $baseSalary; // Full salary regardless of attendance

        $this->createPayslip($run, $employee, [
            'base_salary' => $baseSalary,
            'gross_pay' => $grossPay,
            'net_pay' => $grossPay, // MVP: no deductions
        ]);
    }

    /**
     * Create a payslip record
     */
    private function createPayslip(PayrollRun $run, $employee, array $data): void
    {
        PayrollPayslip::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'base_salary' => $data['base_salary'],
            'overtime_pay' => 0.00,
            'bonus_amount' => 0.00,
            'allowance_amount' => 0.00,
            'gross_pay' => $data['gross_pay'],
            'tax_deductions' => 0.00,
            'benefit_deductions' => 0.00,
            'other_deductions' => 0.00,
            'total_deductions' => 0.00,
            'net_pay' => $data['net_pay'],
            'payslip_number' => $this->generatePayslipNumber($run, $employee),
        ]);
    }

    /**
     * Generate unique payslip number
     */
    private function generatePayslipNumber(PayrollRun $run, $employee): string
    {
        return 'PSL-' . now()->format('Y') . '-' . str_pad($run->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mark payroll run as paid and update schedule
     */
    public function markAsPaid(PayrollRun $run): void
    {
        if ($run->status !== 'approved') {
            throw new \InvalidArgumentException('Only approved runs can be marked as paid');
        }

        $run->update([
            'status' => 'paid',
            'paid_at' => now(),
            'processed_by' => auth()->user()?->name ?? 'System'
        ]);

        // Update next pay date in schedule
        $schedule = $run->paySchedule;
        $nextDate = $this->calculateNextPayDate(
            $schedule->next_pay_date,
            $schedule->pay_frequency
        );
        $schedule->update(['next_pay_date' => $nextDate]);
    }

    /**
     * Calculate next pay date based on frequency
     */
    public function calculateNextPayDate(Carbon $currentDate, string $frequency): Carbon
    {
        return match (strtolower($frequency)) {
            'weekly' => $currentDate->copy()->addWeek(),
            'bi-weekly', 'biweekly' => $currentDate->copy()->addWeeks(2),
            'semi-monthly' => $currentDate->copy()->addDays(15),
            'monthly' => $currentDate->copy()->addMonth(),
            default => $currentDate->copy()->addWeeks(2), // fallback
        };
    }
}
