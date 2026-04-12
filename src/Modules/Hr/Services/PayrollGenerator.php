<?php

namespace App\Modules\Hr\Services;


use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\EmployeeProfile;
use App\Modules\Hr\Models\PayrollEmployee;
use App\Modules\Hr\Models\EmployeeTax;
use App\Modules\Hr\Models\RoleTax;
use App\Modules\Hr\Models\EmployeeDeduction;
use App\Modules\Hr\Models\RoleDeduction;

use Spatie\Permission\Models\Role;
use App\Modules\Hr\Models\EmployeeAllowance;
use App\Modules\Hr\Models\EmployeeBonus;
use App\Modules\Hr\Models\RoleAllowance;
use App\Modules\Hr\Models\RoleBonus;
use App\Modules\Hr\Models\DailyEarning;


use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class PayrollGenerator
{
    public function generatePayroll(PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== 'draft') {
            throw new \Exception('Payroll can only be generated for draft runs');
        }

        // Get payroll components configuration
        $components = $this->parsePayrollComponents($payrollRun->payroll_components);

        // Get aggregated earnings for the period
        $earnings = $this->getEarningsForPeriod($payrollRun->from_date, $payrollRun->to_date);

        // Get all employee profiles with their roles
        $employeeProfiles = EmployeeProfile::with('user.roles')->get();

        // Preload all component data
        $allowances = [];
        $bonuses = [];
        $employeeTaxes = [];
        $roleTaxes = [];
        $employeeDeductions = [];
        $roleDeductions = [];
        $roleAllowances = [];
        $roleBonuses = [];


        if (in_array('allowances', $components)) {
            $allowances = $this->getAllowancesForPayroll($payrollRun->id);
            $roleAllowances = $this->getRoleAllowancesForPayroll($payrollRun->id);
        }

        if (in_array('bonuses', $components)) {
            $bonuses = $this->getBonusesForPayroll($payrollRun->id);
            $roleBonuses = $this->getRoleBonusesForPayroll($payrollRun->id);
        }

        if (in_array('taxes', $components)) {
            $employeeTaxes = $this->getEmployeeTaxesForPayroll($payrollRun->id);
            $roleTaxes = $this->getRoleTaxesForPayroll($payrollRun->id);
        }

        if (in_array('deductions', $components)) {
            $employeeDeductions = $this->getEmployeeDeductionsForPayroll($payrollRun->id);
            $roleDeductions = $this->getRoleDeductionsForPayroll($payrollRun->id);
        }

        DB::transaction(function () use (
            $payrollRun,
            $employeeProfiles,
            $earnings,
            $components,
            $allowances,
            $bonuses,
            $roleAllowances,
            $roleBonuses,
            $employeeTaxes,
            $roleTaxes,
            $employeeDeductions,
            $roleDeductions
        ) {
            foreach ($employeeProfiles as $employeeProfile) {
                $totalEarnings = $earnings[$employeeProfile->employee_id] ?? 0;

                $payrollData = [
                    'payroll_number' => $payrollRun->payroll_number,
                    'employee_id' => $employeeProfile->employee_id,
                    'base_salary' => $totalEarnings,
                    'gross_salary' => $totalEarnings,
                    'total_deductions' => 0,
                    'net_salary' => $totalEarnings,
                    'total_allowances' => 0,
                    'total_bonuses' => 0,
                    'total_taxes' => 0,
                    'total_other_deductions' => 0,
                ];

                // Get employee roles
                $roleIds = $employeeProfile->user->roles->pluck('id')->toArray();

                // Process allowances if enabled
                if (in_array('allowances', $components)) {
                    $allAllowances = $this->getAllComponents(
                        $employeeProfile->id,
                        $roleIds,
                        $allowances,
                        $roleAllowances
                    );

                    $allowanceTotal = $this->calculateComponentAmount(
                        $allAllowances,
                        $totalEarnings,
                        $payrollData['gross_salary'],
                        'addition_type'
                    );

                    $payrollData['total_allowances'] = $allowanceTotal;
                    $payrollData['gross_salary'] += $allowanceTotal;
                }

                // Process bonuses if enabled
                if (in_array('bonuses', $components)) {
                    $allBonuses = $this->getAllComponents(
                        $employeeProfile->id,
                        $roleIds,
                        $bonuses,
                        $roleBonuses,
                        true
                    );

                    $bonusTotal = $this->calculateComponentAmount(
                        $allBonuses,
                        $totalEarnings,
                        $payrollData['gross_salary'],
                        'addition_type'
                    );

                    $payrollData['total_bonuses'] = $bonusTotal;
                    $payrollData['gross_salary'] += $bonusTotal;
                }

                // Process taxes if enabled
                if (in_array('taxes', $components)) {
                    $allTaxes = $this->getAllComponents(
                        $employeeProfile->id,
                        $roleIds,
                        $employeeTaxes,
                        $roleTaxes
                    );

                    $taxTotal = $this->calculateComponentAmount(
                        $allTaxes,
                        $totalEarnings,
                        $payrollData['gross_salary'],
                        'subtraction_type'
                    );

                    $payrollData['total_taxes'] = $taxTotal;
                    $payrollData['total_deductions'] += $taxTotal;
                }

                // Process deductions if enabled
                if (in_array('deductions', $components)) {
                    $allDeductions = $this->getAllComponents(
                        $employeeProfile->id,
                        $roleIds,
                        $employeeDeductions,
                        $roleDeductions
                    );

                    $deductionTotal = $this->calculateComponentAmount(
                        $allDeductions,
                        $totalEarnings,
                        $payrollData['gross_salary'],
                        'subtraction_type'
                    );

                    $payrollData['total_other_deductions'] = $deductionTotal;
                    $payrollData['total_deductions'] += $deductionTotal;
                }

                // Final net salary calculation
                $payrollData['net_salary'] = $payrollData['gross_salary'] - $payrollData['total_deductions'];

                PayrollEmployee::updateOrCreate(
                    [
                        'payroll_number' => $payrollRun->payroll_number,
                        'employee_id' => $employeeProfile->employee_id,
                        'payroll_run_id' => $payrollRun->id,
                    ],
                    $payrollData
                );
            }
        });

        return true;
    }

    // ... (existing methods: getEarningsForPeriod, parsePayrollComponents, etc.) ...

    protected function getAllComponents(
        $employeeId,
        array $roleIds,
        array $employeeComponents,
        array $roleComponents,
        bool $isBonus = false
    ): array {
        $components = [];

        // Get employee-specific components
        if (isset($employeeComponents[$employeeId])) {
            $employeeItems = $employeeComponents[$employeeId];
            if ($employeeItems instanceof Collection) {
                $employeeItems = $employeeItems->toArray();
            }
            $components = array_merge($components, $employeeItems);
        }

        // Get role-based components
        foreach ($roleIds as $roleId) {
            if (!empty($roleComponents[$roleId])) {
                foreach ($roleComponents[$roleId] as $component) {
                    if ($isBonus) {
                        $components[] = [
                            'amount' => $component->amount,
                            'addition_type' => $component->addition_type,
                        ];
                    } else {
                        $components[] = [
                            'amount' => $component->amount,
                            'subtraction_type' => $component->subtraction_type,
                        ];
                    }
                }
            }
        }

        return $components;
    }

    protected function getEmployeeTaxesForPayroll($payrollRunId): array
    {
        return EmployeeTax::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('employee_profile_id')
            ->all();
    }

    protected function getRoleTaxesForPayroll($payrollRunId): array
    {
        return RoleTax::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('role_id')
            ->all();
    }

    protected function getEmployeeDeductionsForPayroll($payrollRunId): array
    {
        return EmployeeDeduction::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('employee_profile_id')
            ->all();
    }

    protected function getRoleDeductionsForPayroll($payrollRunId): array
    {
        return RoleDeduction::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('role_id')
            ->all();
    }

    protected function calculateComponentAmount(
        array $items,
        float $baseSalary,
        float $currentGross,
        string $typeField
    ): float {
        $total = 0;
        $grossDependentItems = [];

        // First pass: calculate non-gross-dependent items
        foreach ($items as $item) {
            $calcType = trim(strtolower($item[$typeField]));

            switch ($calcType) {
                case 'fixed_amount':
                    $total += $item['amount'];
                    break;

                case 'percentage_of_basic_salary':
                    $total += ($item['amount'] / 100) * $baseSalary;
                    break;

                case 'percentage_of_gross_pay':
                    $grossDependentItems[] = $item;
                    break;
            }
        }

        // Second pass: calculate gross-dependent items
        if (!empty($grossDependentItems)) {
            $currentBaseGross = $currentGross;

            foreach ($grossDependentItems as $item) {
                $total += ($item['amount'] / 100) * $currentBaseGross;
            }
        }

        return $total;
    }

    // ... (other existing methods) ...

    protected function parsePayrollComponents(string $components): array
    {
        // 1. Remove the outer square brackets and any surrounding quotes
        $cleanedString = trim($components, '[]"\' ');

        // 2. Split the string by the comma and a quote (",") or a quote and a comma (",')
        $explodedComponents = preg_split('/"\s*,\s*"/', $cleanedString);

        // Fallback for cases without inner quotes
        if (count($explodedComponents) === 1 && strpos($cleanedString, ',') !== false) {
             $explodedComponents = explode(',', $cleanedString);
        }

        // 3. Clean up each component
        $parsedComponents = array_map(function($component) {
            return strtolower(trim($component, '"\' '));
        }, $explodedComponents);

        return $parsedComponents;
    }


    protected function getEarningsForPeriod($startDate, $endDate): array
    {
        return DailyEarning::whereBetween('work_date', [$startDate, $endDate])
            ->select('employee_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id')
            ->all();
    }


    protected function getAllowancesForPayroll($payrollRunId): array
    {
        return EmployeeAllowance::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('employee_profile_id')
            ->all();
    }


    protected function getBonusesForPayroll($payrollRunId): array
    {
        return EmployeeBonus::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('employee_profile_id')
            ->all();
    }



    protected function getRoleAllowancesForPayroll($payrollRunId): array
    {
        return RoleAllowance::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('role_id')
            ->all();
    }

    protected function getRoleBonusesForPayroll($payrollRunId): array
    {
        return RoleBonus::where('payroll_run_id', $payrollRunId)
            ->get()
            ->groupBy('role_id')
            ->all();
    }





}
