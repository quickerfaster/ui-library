<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Services\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use Barryvdh\DomPDF\Facade\Pdf;



class PayrollRunDetail extends Component
{
    use HasCurrencySymbol;

    public int $recordId;
    public string $configKey;
    public PayrollRun $run;
    public array $returnParams = [];
    public array $tabs = [];
    public string $activeTab = 'overview';

    protected $listeners = [
        'refreshDetail' => '$refresh',
        'approveRun' => 'approve',
        'markPaid' => 'markPaid',
        'cancelRun' => 'cancel',
        'recalculate' => 'recalculate',
        'forceGenerateBankFile' => 'forceGenerateBankFile',
        'cancelBankFile' => 'cancelBankFile',
    ];

    public function mount(int $recordId, string $configKey, array $returnParams = []): void
    {
        $this->recordId = $recordId;
        $this->configKey = $configKey;
        $this->returnParams = $returnParams;
        $this->run = PayrollRun::with(['paySchedule'])->findOrFail($recordId);
        $this->tabs = $this->getTabs();
    }

    protected function getTabs(): array
    {
        return [
            'overview' => ['title' => 'Overview', 'icon' => 'fas fa-info-circle'],
            'payslips' => ['title' => 'Payslips', 'icon' => 'fas fa-receipt'],
            'adjustments' => ['title' => 'Adjustments', 'icon' => 'fas fa-edit'],
            'reconciliation' => ['title' => 'Reconciliation', 'icon' => 'fas fa-check-double'],
            'audit' => ['title' => 'Audit', 'icon' => 'fas fa-history'],
        ];
    }

    // Approval actions (unchanged)
    public function confirmApprove(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Approve Payroll Run?',
            'message' => 'This will lock all data and mark the run as approved. Are you sure?',
            'confirmEvent' => 'approveRun',
            'confirmParams' => [],
        ]);
    }

    public function approve(): void
    {
        if (!in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review'])) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Cannot approve this payroll run.']);
            return;
        }

        DB::transaction(function () {
            $this->run->update([
                'status' => 'approved',
                'approved_by' => auth()->user()->name ?? auth()->id(),
                'approved_at' => now(),
            ]);
        });

        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run approved.']);
        $this->run->refresh();
    }

    public function confirmMarkPaid(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Mark as Paid?',
            'message' => 'Confirm that payment has been processed externally?',
            'confirmEvent' => 'markPaid',
            'confirmParams' => [],
        ]);
    }

    public function markPaid(): void
    {
        if ($this->run->status !== 'approved') {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Only approved runs can be marked as paid.']);
            return;
        }

        $this->run->update([
            'status' => 'paid',
            'processed_at' => now(),
            'processed_by' => auth()->user()->name ?? auth()->id(),
        ]);

        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run marked as paid.']);
        $this->run->refresh();
    }

    public function confirmCancel(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Cancel Payroll Run?',
            'message' => 'This action cannot be undone. Are you sure?',
            'confirmEvent' => 'cancelRun',
            'confirmParams' => [],
        ]);
    }

    public function cancel(): void
    {
        if (!in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review', 'approved'])) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'This payroll run cannot be cancelled.']);
            return;
        }

        $this->run->update(['status' => 'cancelled']);
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run cancelled.']);
        $this->run->refresh();
    }

    public function confirmRecalculate(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Recalculate Payroll?',
            'message' => 'This will overwrite existing payslip calculations. Continue?',
            'confirmEvent' => 'recalculate',
            'confirmParams' => [],
        ]);
    }

    public function recalculate(): void
    {
        if ($this->run->status !== 'draft') {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Only draft runs can be recalculated.']);
            return;
        }
        app(PayrollCalculator::class)->calculate($this->run);
        $this->run->refresh();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Recalculation completed.']);
    }

    public function exportPayslips(): void
    {

        $configResolver = app(ConfigResolver::class, ['configKey' => 'hr.payroll_payslip']);
        $fieldDefinitions = $configResolver->getFieldDefinitions();

        $excludedColumns = [
            "payslip_number", // exclude from middle & add it to custom column first item
            "payroll_run_id",
            "employee_id",
            "paid_at",
            "payment_reference",
            "bank_account_snapshot",
            "notes",
            "created_by",
            "updated_by",
        ];


        $customColumns = [
            'payslip_number',
            'employee.employee_number',
            'employee.first_name',
            'employee.last_name',
        ];

        $columns = array_diff(array_keys($fieldDefinitions), $excludedColumns);
        $columns = array_merge($customColumns, $columns);


        // Build filters in the exact format the DataTable uses
        $filters = [
            [
                'field' => 'payroll_run_id',
                'type' => 'number',          // or 'select' – both work
                'operator' => 'equals',
                'value' => $this->run->id,
                'multi' => false,
                // 'displayValue' => $this->run->id, // optional
                // 'label'        => 'Payroll Run',  // optional
            ]
        ];

        $options = [
            'orientation' => 'landscape',
            'paper' => 'a4',
        ];

        $params = [
            'configKey' => 'hr.payroll_payslip',
            'format' => 'xls',
            'columns' => implode(',', $columns),
            'filters' => json_encode($filters),
            'options' => json_encode($options),
        ];

        $this->dispatch('openExportModal', [
            'configKey' => 'hr.payroll_payslip',
            'params' => $params,
        ]);
    }



public function generateBankFile(): void
{
    $this->run->load(['payslips.employee.employeePayrollProfile', 'paySchedule']);

    // Group missing details by field combination
    $missingGroups = [];
    $employeeNames = [];

    foreach ($this->run->payslips as $payslip) {
        $employee = $payslip->employee;
        $profile = $employee->payrollProfile;
        $country = $this->run->paySchedule->country_code ?? 'US';

        $missing = [];
        if ($country === 'US' && empty($profile->bank_routing_number)) {
            $missing[] = 'Routing Number';
        }
        if (in_array($country, ['US', 'UK', 'NG']) && empty($profile->bank_account_number)) {
            $missing[] = 'Account Number';
        }
        if ($country === 'UK' && empty($profile->bank_sort_code)) {
            $missing[] = 'Sort Code';
        }
        if ($country === 'NG' && empty($profile->bank_code)) {
            $missing[] = 'Bank Code';
        }
        if (!empty($missing)) {
            $key = implode(', ', $missing);
            $missingGroups[$key][] = $employee;
        }
    }

    if (empty($missingGroups)) {
        // No missing details – proceed
        $this->forceGenerateBankFile();
        return;
    }

    // Build a human‑readable message
    $totalMissing = array_sum(array_map('count', $missingGroups));
    $fieldList = implode(' & ', array_keys($missingGroups));
    $message = "{$totalMissing} employee(s) are missing {$fieldList}. ";

    if ($totalMissing <= 5) {
        // Show all names
        $names = [];
        foreach ($missingGroups as $employees) {
            foreach ($employees as $emp) {
                $names[] = $emp->full_name ?? "{$emp->first_name} {$emp->last_name}";
            }
        }
        $message .= "Affected: " . implode(', ', $names) . ". ";
    } else {
        // Show first 5
        $sample = [];
        foreach ($missingGroups as $employees) {
            foreach (array_slice($employees, 0, 5) as $emp) {
                $sample[] = $emp->full_name ?? "{$emp->first_name} {$emp->last_name}";
            }
            if (count($sample) >= 5) break;
        }
        $message .= "Example: " . implode(', ', array_slice($sample, 0, 5)) . " and " . ($totalMissing - 5) . " more. ";
    }

    $message .= "The bank file will skip these employees. Please update their payroll profiles and try again.";

    $this->dispatch('showAlert', [
        'type' => 'confirm',
        'title' => 'Missing Bank Details',
        'message' => $message,
        'confirmText' => 'Continue Anyway (Skip Missing)',
        'cancelText' => 'Cancel & Fix Profiles',
        'confirmEvent' => 'forceGenerateBankFile',
        'cancelEvent' => 'cancelBankFile',
    ]);
}

public function forceGenerateBankFile(): void
{
    $this->dispatch('open-url-new-tab', route('payroll.bank-file', $this->run->id));
}

public function cancelBankFile(): void
{
    // Do nothing – user cancelled
}



public function exportSummaryPdf()
{
    $this->dispatch('open-url-new-tab', route('payroll-run.summary-pdf', $this->run->id));
}


public function queueSummaryPdf()
{
    $currencyCode = $this->run->paySchedule->currency_code ?? 'USD';
    $currencySymbol = $this->getCurrencySymbol($currencyCode);
    $companyName = optional($this->run->paySchedule->company)->name ?? config('app.name');

    // Create an export record (similar to ExportController::queueExport)
    $export = \QuickerFaster\UILibrary\Models\Export::create([
        'user_id' => auth()->id(),
        'config_key' => 'hr.payroll_payslip', // dummy, not used for this custom export
        'filters' => ['payroll_run_id' => $this->run->id],
        'columns' => [],
        'format' => 'pdf',
        'options' => [
            'custom_view' => 'hr::livewire.payroll.exports.payroll_run_summary_pdf',
            'run_id' => $this->run->id,
            'currency_symbol' => $currencySymbol,
            'company_name' => $companyName,
        ],
        'status' => 'pending',
    ]);

    // Dispatch a custom job that uses the export ID
    \App\Modules\Hr\Jobs\Payrolls\GeneratePayrollRunSummaryPdf::dispatch($export->id);

    // Open the export modal (same as data table exports)
    $this->dispatch('openExportModal', [
        'configKey' => 'hr.payroll_payslip',
        'params' => [
            'export_id' => $export->id,
        ],
    ]);
}





public function markAsReconciled(): void
{
    if ($this->run->status !== 'paid') {
        $this->dispatch('showAlert', [
            'type' => 'warning',
            'message' => 'Only paid runs can be marked as reconciled.',
        ]);
        return;
    }

    $this->run->update([
        'reconciliation_status' => 'reconciled',
        'reconciled_at' => now(),
    ]);

    $this->run->refresh();
    $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Payroll run marked as reconciled.']);
}




    public function render()
    {
        $canApprove = in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review']);
        $canMarkPaid = $this->run->status === 'approved';
        $canCancel = in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review', 'approved']);
        $canRecalculate = $this->run->status === 'draft';

        return view('hr::livewire.payroll.payroll-run-detail', [
            'canApprove' => $canApprove,
            'canMarkPaid' => $canMarkPaid,
            'canCancel' => $canCancel,
            'canRecalculate' => $canRecalculate,
        ]);
    }
}
