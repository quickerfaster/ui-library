<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\PayrollPayslip;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Services\Payroll\PayrollCalculator;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;
use App\Modules\Hr\Jobs\Payroll\ProcessPayrollRun;




use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Department;
use App\Modules\Admin\Models\Location;

class PayrollWizardPreview extends Component
{
    use WithPagination;
    use HasCurrencySymbol;

    public int $stepIndex;
    public int $payrollRunId;
    public array $previewData = [];
    public ?int $expandedPayslipId = null;
    public array $lazyItemsCache = [];

    // Filters & Search
    public ?int $filterCompany = null;
    public ?int $filterDepartment = null;
    public ?int $filterLocation = null;
    public ?string $filterEmploymentStatus = 'Active';
    public string $search = '';

    // Progress tracking
    public string $calculationStatus = 'pending';
    public int $progress = 0;
    public int $totalEmployees = 0;
    public int $processedEmployees = 0;
    public bool $isPolling = false;
    public bool $processingStartedEventSent = false;

    // Sorting properties
    public string $sortField = 'employee_name';
    public string $sortDirection = 'asc';
    

    protected $listeners = [
        'refreshPreview' => 'loadPreviewData',
        'savePreview' => 'save',
    ];

    public function mount(int $stepIndex, int $payrollRunId): void
    {
        $this->stepIndex = $stepIndex;
        $this->payrollRunId = $payrollRunId;
        $this->loadPreviewData();
    }

    public function loadPreviewData(): void
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);
        $this->calculationStatus = $run->calculation_status;
        $this->totalEmployees = $run->total_employees ?? 0;
        $this->processedEmployees = $run->processed_employees ?? 0;

        if ($this->totalEmployees > 0) {
            $this->progress = round(($this->processedEmployees / $this->totalEmployees) * 100);
        }

        if ($this->calculationStatus === 'completed') {
            $this->loadCalculatedData();
        } elseif ($this->calculationStatus === 'failed') {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Payroll calculation failed. Please check logs or try again.'
            ]);
        } elseif ($this->calculationStatus === 'pending' || $this->calculationStatus === 'processing') {
            $this->startCalculation();
        }
    }



    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }



    protected function startCalculation(): void
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);

        if ($run->calculation_status === 'pending') {
            $run->update(['calculation_status' => 'processing']);
            ProcessPayrollRun::dispatch($run);
        }

        $this->calculationStatus = 'processing';
        $this->isPolling = true;
    }

    protected function loadCalculatedData(): void
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);

        $this->previewData = [
            'period_start' => $run->period_start->format('Y-m-d'),
            'period_end' => $run->period_end->format('Y-m-d'),
            'total_cash_required' => $run->total_cash_required,
        ];

        $this->isPolling = false;
        $this->dispatch('refreshPayslips');
    }

    public function checkCalculationStatus(): void
    {
        $progress = \App\Modules\Hr\Models\PayrollRunProgress::where('payroll_run_id', $this->payrollRunId)->first();

        if ($progress) {
            $this->calculationStatus = $progress->status;
            $this->totalEmployees = $progress->total_employees ?? 0;
            $this->processedEmployees = $progress->processed_employees ?? 0;

            if ($this->totalEmployees > 0) {
                $this->progress = round(($this->processedEmployees / $this->totalEmployees) * 100);
            }
        } else {
            // fallback: read from payroll_runs
            $run = PayrollRun::findOrFail($this->payrollRunId);
            $this->calculationStatus = $run->calculation_status;
            $this->totalEmployees = $run->total_employees ?? 0;
            $this->processedEmployees = $run->processed_employees ?? 0;
            $this->progress = $this->totalEmployees ? round(($this->processedEmployees / $this->totalEmployees) * 100) : 0;
        }

        if ($this->calculationStatus === 'completed') {
            $this->dispatch('processingFinished');
            $this->loadCalculatedData();
        } elseif ($this->calculationStatus === 'failed') {
            $this->isPolling = false;
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Calculation failed.']);
        }

        if ($this->progress && $this->progress > 0 && $this->calculationStatus != 'completed')
            $this->dispatch('processingStarted');

    }

    public function getPayslipsProperty()
    {
        if ($this->calculationStatus !== 'completed') {
            return collect();
        }

        $query = PayrollPayslip::with(['employee', 'employee.employeePosition'])
            ->where('payroll_run_id', $this->payrollRunId);

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



        // Apply sorting
        if ($this->sortField === 'employee_name') {
            $query->join('employees', 'payroll_payslips.employee_id', '=', 'employees.id')
                ->orderBy('employees.first_name', $this->sortDirection)
                ->orderBy('employees.last_name', $this->sortDirection)
                ->select('payroll_payslips.*');
        } elseif ($this->sortField === 'gross_pay') {
            $query->orderBy('gross_pay', $this->sortDirection);
        } elseif ($this->sortField === 'total_deductions') {
            $query->orderBy('total_deductions', $this->sortDirection);
        } elseif ($this->sortField === 'net_pay') {
            $query->orderBy('net_pay', $this->sortDirection);
        }





        return $query->paginate(50);
    }

    public function toggleDetails($payslipId): void
    {
        if ($this->calculationStatus !== 'completed')
            return;

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

    // Filter update methods (reset pagination and expanded state)
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

    public function save(): void
    {
        if ($this->calculationStatus !== 'completed') {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Please wait for calculation to complete.']);
            return;
        }
        $this->dispatch('previewComplete');
    }



    public function retryCalculation(): void
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);

        // Reset status to pending
        $run->update([
            'calculation_status' => 'pending',
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        // Reload the preview (which will start a new job)
        $this->loadPreviewData();
    }



    public function render()
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);

        // Prepare filter dropdown options (only showing entities that have employees in this run)
        $employeeIds = PayrollPayslip::where('payroll_run_id', $this->payrollRunId)
            ->pluck('employee_id')
            ->unique();

        $companies = collect();
        if ($employeeIds->isNotEmpty()) {
            $companyIds = \App\Modules\Hr\Models\Employee::whereIn('id', $employeeIds)
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
            $positions = \App\Modules\Hr\Models\Employee::whereIn('id', $employeeIds)
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

        return view('hr::livewire.payroll.wizard-preview', [
            'payslips' => $this->payslips,
            'companies' => $companies,
            'departments' => $departments,
            'locations' => $locations,
            'search' => $this->search,
            'calculationStatus' => $this->calculationStatus,
            'progress' => $this->progress,
            'totalEmployees' => $this->totalEmployees,
            'processedEmployees' => $this->processedEmployees,
            'isPolling' => $this->isPolling,
            'previewData' => $this->previewData,
        ]);
    }
}