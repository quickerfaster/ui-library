<?php

namespace App\Modules\Hr\Services\Payroll;

use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\PayrollPayslip;
use App\Modules\Hr\Models\PayslipItem;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Models\EmployeeAdjustmentProfile;
use App\Modules\Hr\Models\PayrollRunAdjustment;
use App\Modules\Hr\Models\PayrollPolicy;
use App\Modules\Hr\Models\PayrollPolicyAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollCalculator
{
    protected PayrollRun $run;

    /**
     * Main calculation entry point – processes payroll run in chunks.
     */
    public function calculate(PayrollRun $run): void
    {
        $this->run = $run;

        // Get total employee count
        $totalEmployees = EmployeePosition::where('pay_schedule_id', $this->run->pay_schedule_id)
            ->where('employment_status', 'Active')
            ->count();

        // Create or reset progress record (outside transaction, so immediately visible)
        \App\Modules\Hr\Models\PayrollRunProgress::updateOrCreate(
            ['payroll_run_id' => $this->run->id],
            [
                'total_employees' => $totalEmployees,
                'processed_employees' => 0,
                'status' => 'processing',
            ]
        );

        // Delete previous payslips & items (inside a transaction – but this is quick)
        DB::transaction(function () {
            PayrollPayslip::where('payroll_run_id', $this->run->id)->delete();
        });

        // Process employees in chunks – each employee’s data saved in its own transaction
        EmployeePosition::where('pay_schedule_id', $this->run->pay_schedule_id)
            ->where('employment_status', 'Active')
            ->with(['employee', 'location', 'employee.employeeProfile', 'employee.user'])
            ->chunk(100, function ($positions) {
                foreach ($positions as $position) {
                    // Save payslip in a small transaction (for atomicity per employee)
                    DB::transaction(function () use ($position) {
                        $this->calculateForEmployee($position);
                    });

                    // Update progress (outside transaction, commits immediately)
                    \App\Modules\Hr\Models\PayrollRunProgress::where('payroll_run_id', $this->run->id)
                        ->increment('processed_employees');
                }
            });

        // Mark as completed
        \App\Modules\Hr\Models\PayrollRunProgress::where('payroll_run_id', $this->run->id)
            ->update(['status' => 'completed']);

        // Finally, update the run totals (inside a transaction, but done once at the end)
        $this->updateRunTotals();
    }

    /**
     * Calculate payslip for a single employee.
     */
    protected function calculateForEmployee(EmployeePosition $position): PayrollPayslip
    {
        $employeeId = $position->employee_id;
        $items = [];

        // 1. Base salary
        $baseSalary = $position->base_salary;
        $items[] = $this->makeItem(null, 'earning', 'Base Salary', $baseSalary);

        // 2. Recurring adjustments (EmployeeAdjustmentProfile)
        $recurring = EmployeeAdjustmentProfile::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where('effective_date', '<=', $this->run->period_end)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $this->run->period_start);
            })->get();


        foreach ($recurring as $adj) {
            $amount = strtolower($adj->calculation_type) === 'percentage'
                ? $baseSalary * ($adj->value / 100)
                : $adj->value;
            $type = strtolower($adj->type) === 'earning' ? 'earning' : 'deduction';
            $items[] = $this->makeItem(null, $type, $adj->label, $amount);
        }

        // 3. One‑time adjustments for this run (with sign handling)
        $oneTime = PayrollRunAdjustment::where('payroll_run_id', $this->run->id)
            ->where('employee_id', $employeeId)
            ->get();





        foreach ($oneTime as $adj) {
            $amount = $adj->amount;
            if ($amount == 0)
                continue;

            switch (strtolower($adj->type)) {
                case 'bonus':
                case 'commission':
                case 'reimbursement':
                    $type = 'earning';
                    $absAmount = abs($amount);
                    break;
                case 'deduction':
                    $type = 'deduction';
                    $absAmount = abs($amount);
                    break;
                case 'correction':
                    $type = $amount > 0 ? 'earning' : 'deduction';
                    $absAmount = abs($amount);
                    break;
                default:
                    continue 2;
            }
            $items[] = $this->makeItem(null, $type, ucfirst($adj->type) . ': ' . $adj->label, $absAmount, $adj->id);
        }

        // 4. Global policies (tax, pension, etc.)
        $policies = $this->resolvePoliciesForEmployee($position);
        foreach ($policies as $policy) {
            $amount = $this->applyPolicyLogic($policy, $items, $baseSalary);
            if ($amount != 0) {
                // Determine item type based on policy effect (addition or subtraction)
                $itemType = match ($policy->effect) {
                    'addition' => 'earning',
                    default => ($policy->type === 'tax' ? 'tax' : 'deduction'),
                };
                $items[] = $this->makeItem($policy->id, $itemType, $policy->name, $amount);
            }
        }

        // Calculate totals
        $grossPay = collect($items)->whereIn('type', ['earning'])->sum('amount');
        $totalDeductions = collect($items)->whereIn('type', ['deduction', 'tax'])->sum('amount');
        $netPay = $grossPay - $totalDeductions;

        // Create payslip
        $payslip = PayrollPayslip::create([
            'payslip_number' => $this->generatePayslipNumber($position->employee->employee_number),
            'payroll_run_id' => $this->run->id,
            'employee_id' => $employeeId,
            'base_salary' => $baseSalary,
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'payment_status' => 'pending',
        ]);

        // Create line items
        foreach ($items as $item) {
            PayslipItem::create([
                'payslip_id' => $payslip->id,
                'type' => $item['type'],
                'label' => $item['label'],
                'amount' => $item['amount'],
                'policy_id' => $item['policy_id'],
                'adjustment_id' => $item['adjustment_id'] ?? null,
            ]);
        }

        return $payslip;
    }



    /**
     * Resolve applicable policies for an employee based on assignments and global rules.
     */
protected function resolvePoliciesForEmployee(EmployeePosition $position): \Illuminate\Support\Collection
{
    $companyId = optional($position->employee->company)->id;
    $locationId = $position->location_id;
    $departmentId = $position->department_id;
    $shiftId = $position->shift_id;
    $employeeGroupId = $position->employee->employee_group_id;

    // Use the actual class names stored in the database
    $assignments = PayrollPolicyAssignment::with('payrollPolicy')
        ->whereIn('assignable_type', [
            'App\Modules\Admin\Models\Company',
            'App\Modules\Admin\Models\Location',
            'App\Modules\Admin\Models\Department',
            'App\Modules\Hr\Models\Shift',
            'App\Modules\Hr\Models\EmployeeGroup',
        ])
        ->where(function ($q) use ($companyId, $locationId, $departmentId, $shiftId, $employeeGroupId) {
            $q->where(function ($q2) use ($companyId) {
                $q2->where('assignable_type', 'App\Modules\Admin\Models\Company')
                   ->where('assignable_id', $companyId);
            })->orWhere(function ($q2) use ($locationId) {
                $q2->where('assignable_type', 'App\Modules\Admin\Models\Location')
                   ->where('assignable_id', $locationId);
            })->orWhere(function ($q2) use ($departmentId) {
                $q2->where('assignable_type', 'App\Modules\Admin\Models\Department')
                   ->where('assignable_id', $departmentId);
            })->orWhere(function ($q2) use ($shiftId) {
                $q2->where('assignable_type', 'App\Modules\Hr\Models\Shift')
                   ->where('assignable_id', $shiftId);
            })->orWhere(function ($q2) use ($employeeGroupId) {
                $q2->where('assignable_type', 'App\Modules\Hr\Models\EmployeeGroup')
                   ->where('assignable_id', $employeeGroupId);
            });
        })
        ->where('effective_date', '<=', $this->run->period_end)
        ->where(function ($q) {
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $this->run->period_start);
        })
        ->orderBy('priority', 'desc')
        ->get();

    // Global policies (by country/state, but allow null as "any")
    // Null means the global will be applied to any country/state
    // A default country_code eg. 'US', 'NG' means it will ony be applied to the employee from that country/state
    // use $countryCode = $position->location->country_code ?? 'US'; // 'NG' to effect the overide
    $countryCode = $position->location->country_code ?? null;
    $stateCode = $position->location->state_code ?? null;
    if ($position->employee->employee_number == "EMP0090")
        \Log::debug("Policies", [$countryCode, $stateCode]);

    $globalPolicies = PayrollPolicy::where('is_active', true)
        ->where('effective_date', '<=', $this->run->period_end)
        ->where(function ($q) {
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $this->run->period_start);
        })
        ->where(function ($q) use ($countryCode) {
            $q->where('country_code', $countryCode)->orWhereNull('country_code');
        })
        ->where(function ($q) use ($stateCode) {
            $q->where('state_code', $stateCode)->orWhereNull('state_code');
        })
        ->get();

    // Merge and deduplicate
    $policies = $assignments->pluck('payrollPolicy')->merge($globalPolicies)->unique('id');



    return $policies;
}

    /**
     * Apply policy logic to calculate amount.
     */
    protected function applyPolicyLogic(PayrollPolicy $policy, array $items, float $baseSalary): float
    {
        $logic = json_decode($policy->calculation_logic, true);
        if (!$logic)
            return 0;

        switch ($policy->type) {
            case 'tax':
                // Assume logic contains bands: [[limit, rate], ...]
                $annualTaxable = $baseSalary * 12;
                $tax = 0;
                $remaining = $annualTaxable;
                foreach ($logic['bands'] ?? [] as $band) {
                    $bandLimit = $band[0];
                    $rate = $band[1] / 100;
                    $taxable = min($remaining, $bandLimit);
                    $tax += $taxable * $rate;
                    $remaining -= $taxable;
                    if ($remaining <= 0)
                        break;
                }
                return $tax / 12; // monthly tax (positive)
            case 'pension':
                $rate = $logic['rate'] ?? 8;
                return $baseSalary * ($rate / 100);
            case 'benefit':
            case 'bonus':
                if ($logic['type'] === 'fixed')
                    return $logic['value'];
                if ($logic['type'] === 'percentage')
                    return $baseSalary * ($logic['value'] / 100);
                return 0;
            case 'insurance':
            case 'deduction':
                if ($logic['type'] === 'fixed')
                    return $logic['value'];
                if ($logic['type'] === 'percentage')
                    return $baseSalary * ($logic['value'] / 100);
                return 0;
            default:
                return 0;
        }
    }

    /**
     * Helper to create a line item array.
     */
    protected function makeItem(?int $policyId, string $type, string $label, float $amount, ?int $adjustmentId = null): array
    {
        return [
            'policy_id' => $policyId,
            'type' => $type,
            'label' => $label,
            'amount' => $amount,
            'adjustment_id' => $adjustmentId,
        ];
    }

    /**
     * Generate unique payslip number.
     */
    protected function generatePayslipNumber(string $employeeNumber): string
    {
        return 'PS-' . $employeeNumber . '-' . $this->run->id . '-' . now()->format('YmdHis');
    }

    /**
     * Update payroll run totals after all employees processed.
     */
    protected function updateRunTotals(): void
    {
        $totals = PayrollPayslip::where('payroll_run_id', $this->run->id)
            ->selectRaw('SUM(gross_pay) as total_gross, SUM(total_deductions) as total_deductions, SUM(net_pay) as total_net')
            ->first();

        $this->run->update([
            'total_gross_pay' => $totals->total_gross ?? 0,
            'total_deductions' => $totals->total_deductions ?? 0,
            'total_cash_required' => $totals->total_net ?? 0,
            'calculation_status' => 'completed',
        ]);

        // Log::info("Payroll run {$this->run->id} calculation completed. Total employees: {$this->run->total_employees}, Net total: {$totals->total_net}");
    }
}
