<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Modules\Hr\Models\Employee;
use Illuminate\Routing\Controller;

class EmployeePrintController extends Controller
{
    public function show(Employee $employee)
    {
        // Load the current position (hasOne) with its relationships
        $employee->load([
            'employeeProfile',
            'employeePosition.jobTitle',      // current position → job title
            'employeePosition.department',    // current position → department
            'employeePosition.manager',       // current position → manager
            'employeeWorkPatterns.workPattern',
        ]);

        // The current position is now directly accessible via the relationship
        $currentPosition = $employee->position;

        return view('hr::employees.print', compact('employee', 'currentPosition'));
    }
}