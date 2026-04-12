<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Models\Employee;

class AuthorizationService
{
    /**
     * Check if user can perform action on a specific row
     */
    public function canPerformAction(User $user, array $action, $row): bool
    {


        
        if ($user->hasAnyRole(["super_admin"]))
            return true;


        // 1. Check required role
        if (isset($action['requiredRole'])) {
            $requiredRoles = (array) $action['requiredRole'];
            if (!$user->hasAnyRole($requiredRoles)) {
                return false;
            }
        }
        
        // 2. Check required permission
        if (isset($action['requiredPermission'])) {
            $requiredPermissions = (array) $action['requiredPermission'];
            if (!$user->hasAnyPermission($requiredPermissions)) {
                return false;
            }
        }
        
        // 3. Check business conditions (state-based)
        if (isset($action['condition'])) {
            if (!$this->checkBusinessConditions($row, $action['condition'])) {
                return false;
            }
        }
        
        // 4. Check data scope (can user access this specific employee's data?)
        if (!$this->isInUserScope($user, $row)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check business/state conditions
     */
    private function checkBusinessConditions($row, array $conditions): bool
    {
        foreach ($conditions as $field => $expectedValue) {
            $actualValue = data_get($row, $field);
            
            if ($actualValue != $expectedValue) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if row is within user's data scope
     */
    private function isInUserScope(User $user, $row): bool
    {
        // Get the employee ID from the row
        $employeeId = $row->employee_id ?? $row->id;
        
        // If user is an employee, they can only see their own data
        if ($user->hasRole('employee')) {
            return $user->employee_id == $employeeId;
        }
        
        // Managers can see their team's data
        if ($user->hasRole('manager')) {
            $managedEmployeeIds = $user->managedEmployees()->pluck('id')->toArray();
            return in_array($employeeId, $managedEmployeeIds);
        }
        
        // HR Admin and Payroll Admin can see all
        if ($user->hasAnyRole(['hr_admin', 'payroll_admin', 'system_admin'])) {
            return true;
        }
        
        return false;
    }
}