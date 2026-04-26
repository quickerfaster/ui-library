<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\PayrollRunAdjustment;
use App\Modules\Hr\Models\PayrollPayslip;
use App\Modules\Hr\Services\Payroll\PayrollCalculator;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;
use App\Modules\Admin\Models\Department;
use App\Modules\Admin\Models\Location;
use App\Modules\Admin\Models\Company;
use App\Modules\Hr\Models\Employee;
use Illuminate\Support\Facades\DB;

class PayrollRunDetail extends Component
{
    use WithPagination;
    use HasCurrencySymbol;

    public int $recordId;
    public string $configKey;
    public PayrollRun $run;
    public array $returnParams = [];
    public array $tabs = [];
    public string $activeTab = 'overview';
    public ?int $expandedPayslipId = null;
    public array $lazyItemsCache = [];

    // Filters for payslips tab
    public ?int $filterCompany = null;
    public ?int $filterDepartment = null;
    public ?int $filterLocation = null;
    public ?string $filterEmploymentStatus = 'Active';
    public string $search = '';


    // Adjustment tab filters
    public string $adjustmentSearch = '';
    public string $adjustmentTypeFilter = '';
    public ?int $adjustmentFilterCompany = null;
    public ?int $adjustmentFilterDepartment = null;
    public ?int $adjustmentFilterLocation = null;
    public ?string $adjustmentFilterEmploymentStatus = 'Active';



    protected $listeners = [
        'refreshDetail' => '$refresh',
        'approveRun' => 'approve',
        'markPaid' => 'markPaid',
        'cancelRun' => 'cancel',
        'recalculate' => 'recalculate',
    ];

    public function mount(int $recordId, string $configKey, array $returnParams = []): void
    {
        $this->recordId = $recordId;
        $this->configKey = $configKey;
        $this->returnParams = $returnParams;
        $this->run = PayrollRun::with(['paySchedule'])
            ->findOrFail($recordId);
        $this->tabs = $this->getTabs();
    }

    protected function getTabs(): array
    {
        return [
            'overview' => ['title' => 'Overview', 'icon' => 'fas fa-info-circle'],
            'payslips' => ['title' => 'Payslips', 'icon' => 'fas fa-receipt'],
            'adjustments' => ['title' => 'Adjustments', 'icon' => 'fas fa-edit'],
            'audit' => ['title' => 'Audit', 'icon' => 'fas fa-history'],
        ];
    }

    public function getPayslipsProperty()
    {
        $query = PayrollPayslip::with(['employee', 'employee.employeePosition'])
            ->where('payroll_run_id', $this->recordId);

        // Apply search
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('employee_number', 'like', $searchTerm);
            });
        }

        // Apply filters
        if ($this->filterCompany) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $this->filterCompany));
        }

        if ($this->filterDepartment) {
            $query->whereHas('employee.employeePosition', fn($q) => $q->where('department_id', $this->filterDepartment));
        }

        if ($this->filterLocation) {
            $query->whereHas('employee.employeePosition', fn($q) => $q->where('location_id', $this->filterLocation));
        }

        if ($this->filterEmploymentStatus === 'Active' || $this->filterEmploymentStatus === 'On Leave' || $this->filterEmploymentStatus === 'Terminated') {
            $query->whereHas('employee.employeePosition', fn($q) => $q->where('employment_status', $this->filterEmploymentStatus));
        }

        return $query->paginate(50);
    }

    public function togglePayslipDetails($payslipId): void
    {
        if ($this->expandedPayslipId === $payslipId) {
            $this->expandedPayslipId = null;
        } else {
            $this->expandedPayslipId = $payslipId;
            if (!isset($this->lazyItemsCache[$payslipId])) {
                $payslip = PayrollPayslip::with('items')->find($payslipId);
                $this->lazyItemsCache[$payslipId] = $payslip ? $payslip->items : collect();
            }
        }
    }

    public function updatedFilterCompany(): void
    {
        $this->resetPage();
        $this->expandedPayslipId = null;
    }

    public function updatedFilterDepartment(): void
    {
        $this->resetPage();
        $this->expandedPayslipId = null;
    }

    public function updatedFilterLocation(): void
    {
        $this->resetPage();
        $this->expandedPayslipId = null;
    }

    public function updatedFilterEmploymentStatus(): void
    {
        $this->resetPage();
        $this->expandedPayslipId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->expandedPayslipId = null;
    }

    public function resetFilters(): void
    {
        $this->filterCompany = null;
        $this->filterDepartment = null;
        $this->filterLocation = null;
        $this->filterEmploymentStatus = 'Active';
        $this->search = '';
        $this->resetPage();
        $this->expandedPayslipId = null;
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
            // app(PayrollCalculator::class)->calculate($this->run);
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

    public function exportPayslips(): mixed
    {
        return redirect()->route('payroll.payslips.export', ['payroll_run_id' => $this->run->id, 'format' => 'pdf']);
    }


public function getAdjustmentsProperty()
{
    $query = PayrollRunAdjustment::with('employee.employeePosition')
        ->where('payroll_run_id', $this->recordId);

    // Search by employee name or number
    if (!empty($this->adjustmentSearch)) {
        $searchTerm = '%' . $this->adjustmentSearch . '%';
        $query->whereHas('employee', function ($q) use ($searchTerm) {
            $q->where('first_name', 'like', $searchTerm)
                ->orWhere('last_name', 'like', $searchTerm)
                ->orWhere('employee_number', 'like', $searchTerm);
        });
    }

    // Filter by adjustment type
    if (!empty($this->adjustmentTypeFilter)) {
        $query->where('type', $this->adjustmentTypeFilter);
    }

    // Company filter
    if ($this->adjustmentFilterCompany) {
        $query->whereHas('employee', fn($q) => $q->where('company_id', $this->adjustmentFilterCompany));
    }

    // Department filter (via employee position)
    if ($this->adjustmentFilterDepartment) {
        $query->whereHas('employee.employeePosition', fn($q) => $q->where('department_id', $this->adjustmentFilterDepartment));
    }

    // Location filter
    if ($this->adjustmentFilterLocation) {
        $query->whereHas('employee.employeePosition', fn($q) => $q->where('location_id', $this->adjustmentFilterLocation));
    }

    // Employment status filter
    if ($this->adjustmentFilterEmploymentStatus && $this->adjustmentFilterEmploymentStatus !== 'All') {
        $query->whereHas('employee.employeePosition', fn($q) => $q->where('employment_status', $this->adjustmentFilterEmploymentStatus));
    }

    return $query->orderBy('id', 'desc')->paginate(50);
}


    public function updatedAdjustmentSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAdjustmentTypeFilter(): void
    {
        $this->resetPage();
    }

public function resetAdjustmentFilters(): void
{
    $this->adjustmentSearch = '';
    $this->adjustmentTypeFilter = '';
    $this->adjustmentFilterCompany = null;
    $this->adjustmentFilterDepartment = null;
    $this->adjustmentFilterLocation = null;
    $this->adjustmentFilterEmploymentStatus = 'Active';
    $this->resetPage();
}


public function updatedAdjustmentFilterCompany(): void { $this->resetPage(); }
public function updatedAdjustmentFilterDepartment(): void { $this->resetPage(); }
public function updatedAdjustmentFilterLocation(): void { $this->resetPage(); }
public function updatedAdjustmentFilterEmploymentStatus(): void { $this->resetPage(); }





    public function render()
    {
        $canApprove = in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review']);
        $canMarkPaid = $this->run->status === 'approved';
        $canCancel = in_array($this->run->status, ['draft', 'verification_complete', 'adjustments_pending', 'ready_for_review', 'approved']);
        $canRecalculate = $this->run->status === 'draft';
        //$adjustments = PayrollRunAdjustment::where('payroll_run_id', $this->run->id)->with('employee')->get();

        // Prepare filter dropdown options (companies, departments, locations from employees in this run)
        $employeeIds = PayrollPayslip::where('payroll_run_id', $this->recordId)
            ->pluck('employee_id')
            ->unique();

        $companies = collect();
        if ($employeeIds->isNotEmpty()) {
            $companyIds = Employee::whereIn('id', $employeeIds)
                ->whereNotNull('company_id')
                ->pluck('company_id')
                ->unique();
            if ($companyIds->isNotEmpty()) {
                $companies = Company::whereIn('id', $companyIds)->get();
            }
        }

        $departments = collect();
        $locations = collect();
        if ($employeeIds->isNotEmpty()) {
            $positions = Employee::whereIn('id', $employeeIds)
                ->with('employeePosition')
                ->get()
                ->pluck('employeePosition')
                ->filter();

            $departmentIds = $positions->pluck('department_id')->unique()->filter();
            $locationIds = $positions->pluck('location_id')->unique()->filter();

            if ($departmentIds->isNotEmpty()) {
                $departments = Department::whereIn('id', $departmentIds)->get();
            }
            if ($locationIds->isNotEmpty()) {
                $locations = Location::whereIn('id', $locationIds)->get();
            }
        }

        return view('hr::livewire.payroll.payroll-run-detail', [
            'canApprove' => $canApprove,
            'canMarkPaid' => $canMarkPaid,
            'canCancel' => $canCancel,
            'canRecalculate' => $canRecalculate,
            'adjustments' => $this->adjustments,
            'companies' => $companies,
            'departments' => $departments,
            'locations' => $locations,
            'payslips' => $this->payslips,
            'search' => $this->search,
        ]);
    }
}
