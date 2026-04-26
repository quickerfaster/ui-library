<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Modules\Hr\Models\Employee;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class EmployeeProfileController extends Controller
{
    public function show()
    {
        // Find employee linked to the logged-in user
        $employee = Employee::where('user_id', Auth::id())->firstOrFail();
        
        $recordId = $employee->id;
        $returnParams = []; // no table state needed
        
        // Reuse the existing show.blade.php view
        return view('hr::employees.show', compact('recordId', 'returnParams'));
    }
}