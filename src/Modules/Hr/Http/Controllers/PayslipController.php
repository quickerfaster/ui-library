<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Hr\Models\PayrollPayslip;
use App\Modules\Hr\Services\PayslipService;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function view(PayrollPayslip $payslip)
    {
        // Security check (HR admin or employee)
        // $this->authorizeAccess($payslip);

        // Stream PDF in browser (VIEW)
        return $this->getPdf($payslip)->stream("payslip-{$payslip->payslip_number}.pdf");
    }

    public function download(PayrollPayslip $payslip)
    {
        // Security check
        // $this->authorizeAccess($payslip);

        // Force download (DOWNLOAD)
        return $this->getPdf($payslip)->download("payslip-{$payslip->payslip_number}.pdf");
    }

    private function authorizeAccess(PayrollPayslip $payslip)
    {
        $user = auth()->user();
        if (!$user)
            abort(403);

        // Allow if: HR admin OR employee owns payslip
        if ($user->can('manage-payroll') || $payslip->employee_id === $user->employee_number) {
            return;
        }
        abort(403);
    }


    public function getPdf(PayrollPayslip $payslip)
    {
        $service = app(PayslipService::class);
        return $service->generatePdf($payslip);
    }


}
