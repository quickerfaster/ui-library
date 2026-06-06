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
use Carbon\Carbon;

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

    $overridePolicies = [];
    $standaloneAdjustments = collect();

    foreach ($recurring as $adj) {
        if ($adj->policy_id) {
            $policy = $adj->policy;
            if (!$policy || !$policy->is_active) {
                continue;
            }
            $overridePolicy = clone $policy;
            // Override with adjustment's own calculation_type and value
            // For the new structure, we need to store employee_value and employer_value.
            // We'll assume the adjustment overrides both with the same value (typical use case).
            $overridePolicy->calculation_logic = json_encode([
                'calculation_type' => $adj->calculation_type,
                'employee_value' => (float) $adj->value,
                'employer_value' => (float) $adj->value, // or maybe zero? Let's keep same as employee.
            ]);
            $overridePolicy->name = $adj->label ?: $policy->name;
            $overridePolicies[$policy->id] = $overridePolicy;
        } else {
            $standaloneAdjustments->push($adj);
        }
    }

    // Process standalone adjustments (no policy link)
    foreach ($standaloneAdjustments as $adj) {
        $amount = strtolower($adj->calculation_type) === 'percentage'
            ? $baseSalary * ($adj->value / 100)
            : $adj->value;
        $type = strtolower($adj->type) === 'earning' ? 'earning' : 'deduction';
        $items[] = $this->makeItem(null, $type, $adj->label, $amount);
    }

    // 3. One‑time adjustments for this run
    $oneTime = PayrollRunAdjustment::where('payroll_run_id', $this->run->id)
        ->where('employee_id', $employeeId)
        ->get();

    foreach ($oneTime as $adj) {
        $amount = $adj->amount;
        if ($amount == 0) continue;

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

    // 4. Resolve normally assigned and global policies
    $normalPolicies = $this->resolvePoliciesForEmployee($position);

    // 5. Merge policies, giving precedence to overrides
    $allPolicies = [];
    foreach ($normalPolicies as $policy) {
        if (!$policy) continue;
        $allPolicies[$policy->id] = $policy;
    }
    foreach ($overridePolicies as $id => $overridePolicy) {
        $allPolicies[$id] = $overridePolicy;
    }

    // 6. Apply policies with proration
    $periodStart = $this->run->period_start;
    $periodEnd = $this->run->period_end;
    $totalDays = $periodStart->diffInDays($periodEnd) + 1;

    foreach ($allPolicies as $policy) {
        $effectivePolicy = $this->resolveEffectivePolicy($policy);

        $activeDays = $this->getActiveDaysInRun($effectivePolicy, $periodStart, $periodEnd);
        if ($activeDays <= 0) {
            continue;
        }
        $prorationFactor = $activeDays / $totalDays;

        // Get employee and employer amounts from the policy logic
        $amounts = $this->applyPolicyLogic($effectivePolicy, $items, $baseSalary, $prorationFactor);

        // Add employee share (affects net pay)
        if ($amounts['employee'] != 0) {
            $itemType = match ($effectivePolicy->effect) {
                'addition' => 'earning',
                default => ($effectivePolicy->type === 'tax' ? 'tax' : 'deduction'),
            };
            $items[] = $this->makeItem($policy->id, $itemType, $effectivePolicy->name . ' (Employee)', $amounts['employee']);
        }

        // Add employer share (informational / accounting line – does NOT affect net pay)
        if ($amounts['employer'] != 0) {
            $items[] = $this->makeItem($policy->id, 'employer_contribution', $effectivePolicy->name . ' (Employer)', $amounts['employer']);
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
     * Get number of days a policy is active within the payroll period.
     */
    protected function getActiveDaysInRun(PayrollPolicy $policy, Carbon $periodStart, Carbon $periodEnd): int
    {
        $policyStart = $policy->effective_date;
        $policyEnd = $policy->expiry_date ?? $periodEnd;

        // If policy starts after period end or ends before period start, zero days
        if ($policyStart > $periodEnd || ($policyEnd && $policyEnd < $periodStart)) {
            return 0;
        }

        $activeStart = $policyStart > $periodStart ? $policyStart : $periodStart;
        $activeEnd = $policyEnd < $periodEnd ? $policyEnd : $periodEnd;

        return $activeStart->diffInDays($activeEnd) + 1; // inclusive
    }

    /**
     * Resolve the effective policy by traversing parent chain and merging overrides.
     */
    protected function resolveEffectivePolicy(PayrollPolicy $policy): PayrollPolicy
    {
        if (!$policy->parent_policy_id) {
            return $policy;
        }

        // Load parent recursively
        $parent = $policy->parentPolicy;
        if (!$parent) {
            return $policy;
        }
        $effectiveParent = $this->resolveEffectivePolicy($parent);

        // Clone the child to avoid modifying database instance
        $effective = clone $policy;

        // Merge fields: child takes precedence, fallback to parent
        $fieldsToMerge = [
            'calculation_logic',
            'effect',
            'employer_ratio',
            'is_statutory',
            'country_code',
            'state_code',
            'type',
            'name'
        ];
        foreach ($fieldsToMerge as $field) {
            if (empty($effective->$field) && !is_null($effectiveParent->$field)) {
                $effective->$field = $effectiveParent->$field;
            }
        }

        // Special handling for dates: effective = max(child, parent), expiry = min(child, parent)
        $childStart = $policy->effective_date;
        $parentStart = $effectiveParent->effective_date;
        $effective->effective_date = $childStart > $parentStart ? $childStart : $parentStart;

        $childEnd = $policy->expiry_date;
        $parentEnd = $effectiveParent->expiry_date;
        if ($childEnd && $parentEnd) {
            $effective->expiry_date = $childEnd < $parentEnd ? $childEnd : $parentEnd;
        } elseif ($childEnd) {
            $effective->expiry_date = $childEnd;
        } else {
            $effective->expiry_date = $parentEnd;
        }

        return $effective;
    }

    /**
     * Resolve applicable policies for an employee based on assignments and global rules.
     */
    protected function resolvePoliciesForEmployee(EmployeePosition $position): \Illuminate\Support\Collection
    {
        // 1. Get assignments (with their policies)
        $assignments = PayrollPolicyAssignment::with('payrollPolicy')
            ->whereIn('assignable_type', [
                'App\Modules\Admin\Models\Company',
                'App\Modules\Admin\Models\Location',
                'App\Modules\Admin\Models\Department',
                'App\Modules\Admin\Models\Shift',
                'App\Modules\Hr\Models\EmployeeGroup',
            ])
            ->where(function ($q) use ($position) {
                $companyId = optional($position->employee->company)->id;
                $locationId = $position->location_id;
                $departmentId = $position->department_id;
                $shiftId = $position->shift_id;
                $employeeGroupId = $position->employee->employee_group_id;

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
                    $q2->where('assignable_type', 'App\Modules\Admin\Models\Shift')
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

        // 2. Get IDs of policies that have ANY assignment (not just those that match this employee)
        $assignedPolicyIds = PayrollPolicyAssignment::whereIn('assignable_type', [
            'App\Modules\Admin\Models\Company',
            'App\Modules\Admin\Models\Location',
            'App\Modules\Admin\Models\Department',
            'App\Modules\Admin\Models\Shift',
            'App\Modules\Hr\Models\EmployeeGroup',
        ])
            ->where('effective_date', '<=', $this->run->period_end)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $this->run->period_start);
            })
            ->pluck('payroll_policy_id')
            ->unique()
            ->toArray();

        // 3. Global policies: exclude those that have any assignment
        $countryCode = $position->location->country_code ?? null;
        $stateCode = $position->location->state_code ?? null;

        $globalPolicies = PayrollPolicy::where('is_active', true)
            ->where('effective_date', '<=', $this->run->period_end)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $this->run->period_start);
            })
            ->whereNotIn('id', $assignedPolicyIds)
            ->where(function ($q) use ($countryCode) {
                $q->where('country_code', $countryCode)->orWhereNull('country_code');
            })
            ->where(function ($q) use ($stateCode) {
                $q->where('state_code', $stateCode)->orWhereNull('state_code');
            })
            ->get();

        // 4. Merge (assignment policies take precedence)
        return $assignments->pluck('payrollPolicy')->merge($globalPolicies)->unique('id');
    }

    /**
     * Apply policy logic to calculate amount, with optional proration factor.
     */
protected function applyPolicyLogic(PayrollPolicy $policy, array $items, float $baseSalary, float $prorationFactor = 1.0): array
{
    // Return array with 'employee' and 'employer' amounts
    $logic = json_decode($policy->calculation_logic, true);
    if (!$logic) {
        return ['employee' => 0, 'employer' => 0];
    }

    if ($policy->type === 'tax') {
        $annualTaxable = $baseSalary * 12;
        $tax = 0;
        $remaining = $annualTaxable;
        foreach ($logic['bands'] ?? [] as $band) {
            $bandLimit = $band[0];
            $rate = $band[1] / 100;
            $taxable = min($remaining, $bandLimit);
            $tax += $taxable * $rate;
            $remaining -= $taxable;
            if ($remaining <= 0) break;
        }
        $amount = $tax / 12;
        return ['employee' => $amount, 'employer' => 0]; // taxes are usually employee-only
    }

    // Non-tax policies
    $calcType = $logic['calculation_type'] ?? 'percentage';
    $employeeValue = $logic['employee_value'] ?? 0;
    $employerValue = $logic['employer_value'] ?? 0;

    if ($calcType === 'fixed') {
        $employeeAmount = $employeeValue;
        $employerAmount = $employerValue;
    } else { // percentage
        $employeeAmount = $baseSalary * ($employeeValue / 100);
        $employerAmount = $baseSalary * ($employerValue / 100);
    }

    // Apply proration
    $employeeAmount *= $prorationFactor;
    $employerAmount *= $prorationFactor;

    return ['employee' => $employeeAmount, 'employer' => $employerAmount];
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

    $totalEmployerContributions = PayslipItem::whereHas('payslip', function($q) {
            $q->where('payroll_run_id', $this->run->id);
        })
        ->where('type', 'employer_contribution')
        ->sum('amount');

    $this->run->update([
        'total_gross_pay' => $totals->total_gross ?? 0,
        'total_deductions' => $totals->total_deductions ?? 0,
        'total_cash_required' => $totals->total_net ?? 0,
        'total_employer_contributions' => $totalEmployerContributions,
        'calculation_status' => 'completed',
    ]);
}
}
