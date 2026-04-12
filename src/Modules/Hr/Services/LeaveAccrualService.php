<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\LeaveBalance;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\LeaveType;
use Carbon\Carbon;

class LeaveAccrualService
{
    /**
     * Calculate and update accruals for all employees
     */
    public function runMonthlyAccrual(): void
    {
        $employees = Employee::where('status', 'Active')->get();
        
        foreach ($employees as $employee) {
            $this->accrueForEmployee($employee);
        }
    }
    
    /**
     * Accrue leave for a single employee
     */
    public function accrueForEmployee(Employee $employee): void
    {
        // Get active leave types that accrue
        $leaveTypes = LeaveType::where('is_active', true)
            ->where('deducts_from_balance', true)
            ->get();
        
        foreach ($leaveTypes as $leaveType) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => Carbon::now()->year
                ],
                [
                    'balance' => 0.00,
                    'accrual_rate' => $leaveType->default_accrual_rate ?? 1.67, // 20 days/year ÷ 12 months
                    'accrual_frequency' => 'Monthly'
                ]
            );
            
            // Apply monthly accrual
            $balance->increment('balance', $balance->accrual_rate);
            
            // Cap at maximum if specified
            if ($leaveType->max_balance) {
                $balance->balance = min($balance->balance, $leaveType->max_balance);
                $balance->save();
            }
        }
    }
}