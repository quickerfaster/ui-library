<?php

use Illuminate\Support\Facades\Route;



use App\Modules\Hr\Http\Controllers\PayrollRunController;
use App\Modules\Hr\Http\Controllers\PayrollReportController;
use App\Modules\Hr\Http\Controllers\PayslipController;

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

use App\Modules\Hr\Http\Livewire\AdjustAttendanceMvp;
use App\Modules\Hr\Http\Controllers\EmployeePrintController;
use App\Modules\Hr\Http\Controllers\Payrolls\BankFileController;
use App\Modules\Hr\Http\Controllers\Payrolls\PayrollRunPdfController;
use App\Modules\Hr\Models\PayrollRun;


Route::middleware([
    'web',
    // InitializeTenancyByDomain::class,
    // PreventAccessFromCentralDomains::class,

])->group(function () {





    // In your web.php or hr module routes

    /*Route::get('/hr/attendance/{attendanceId}/adjust', function ($attendanceId) {
        return view('hr::adjust-attendance', ['attendanceId' => $attendanceId]);
    } )->name('attendance.adjust');*/





    // Preview modal
    Route::get('/hr/payroll-runs/{payrollRun}/preview', [PayrollRunController::class, 'preview'])
        ->name('payroll.runs.preview');

    // Approve action
    Route::post('/hr/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
        ->name('payroll.runs.approve');


    // Preview modal
    Route::get('/hr/payroll-runs/{payrollRun}/edit', [PayrollRunController::class, 'edit'])
        ->name('payroll.payroll-employees.edit');


    // Payroll Reports
    Route::get('/hr/payroll-runs/{payrollRun}/report', [PayrollReportController::class, 'show'])
        ->name('payroll.reports.show');

    Route::get('/hr/payroll-runs/{payrollRun}/report/download/pdf', [PayrollReportController::class, 'downloadPdf'])
        ->name('payroll.reports.download.pdf');

    Route::get('/hr/payroll-runs/{payrollRun}/report/download/excel', [PayrollReportController::class, 'downloadExcel'])
        ->name('payroll.reports.download.excel');



    // Employee payslips
    Route::get('/hr/payslips/{payslip}', [PayslipController::class, 'download'])
        ->name('payslips.download');
    //->middleware('auth');

    // HR admin payslips
    Route::get('/hr/payslips/{payslip}/view', [PayslipController::class, 'view'])
        ->name('payslips.view');
    //->middleware('can:manage-payroll');



    // Route::post('/payroll-runs/{payrollRun}/generate-payslips', [PayrollRunController::class, 'generatePayslips']);
    // Route::post('/payroll-runs/{payrollRun}/mark-as-paid', [PayrollRunController::class, 'markAsPaid']);





    Route::get('/employees/{employee}/print', [EmployeePrintController::class, 'show'])
        ->name('hr.employees.print')
        //->middleware(['auth', 'can:view,employee']);
    ;



    Route::get('/payroll-run/{run}/bank-file', [BankFileController::class, 'download'])
        ->name('payroll.bank-file');



    Route::get('/payroll-run/{run}/print-summary', function (PayrollRun $run) {
        $currencyCode = $run->paySchedule->currency_code ?? 'USD';
        $currencySymbol = '₦'; // or use a helper to get symbol from code
        $companyName = optional($run->paySchedule->company)->name ?? config('app.name');

        // Load payslips with employee – use chunking? For print we can load all but if too many, paginate?
        // For up to 5000 records, loading all is fine; beyond that, paginate in print view.
        $run->load('payslips.employee');

        return view('hr::livewire.payroll.print.payroll-run-summary', [
            'run' => $run,
            'currencySymbol' => $currencySymbol,
            'companyName' => $companyName,
        ]);
    })->name('payroll-run.print-summary');




    Route::get('/payroll-run/{run}/summary-grouped/{group_by}', function (PayrollRun $run, $group_by) {
        $validGroups = ['department', 'location', 'company'];
        if (!in_array($group_by, $validGroups)) {
            abort(404);
        }

        // Load payslips with all necessary relationships
        $run->load([
            'payslips' => function ($query) {
                $query->with([
                    'employee' => function ($q) {
                        $q->with('company');
                    },
                    'employee.employeePosition' // for department & location
                ]);
            }
        ]);

        // Group the payslips collection manually
        $groups = $run->payslips->groupBy(function ($payslip) use ($group_by) {
            switch ($group_by) {
                case 'department':
                    $dept = optional($payslip->employee->employeePosition?->department);
                    return $dept->name ?? 'No Department';
                case 'location':
                    $loc = optional($payslip->employee->employeePosition?->location);
                    return $loc->name ?? 'No Location';
                case 'company':
                    $company = optional($payslip->employee->company);
                    return $company->name ?? 'No Company';
                default:
                    return 'Unknown';
            }
        });

        return view('hr::livewire.payroll.print.payroll-run-summary-grouped', [
            'run' => $run,
            'groups' => $groups,
            'groupBy' => $group_by,
        ]);
    })->name('payroll-run.summary-grouped');



    Route::get('/payroll-run/{run}/executive-summary', function (PayrollRun $run) {
        return view('hr::livewire.payroll.payroll-executive-summary', ['run' => $run]);
    })->name('payroll-run.executive-summary');



})->middleware(['auth']);


