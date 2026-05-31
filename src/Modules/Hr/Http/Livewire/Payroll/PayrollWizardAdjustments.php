<?php

namespace App\Modules\Hr\Http\Livewire\Payroll;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Hr\Models\PayrollRunAdjustment;
use App\Modules\Admin\Models\Department;
use App\Modules\Admin\Models\Location;
use App\Modules\Admin\Models\Company;
use App\Modules\Hr\Models\Employee;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;



class PayrollWizardAdjustments extends Component
{
    use WithPagination;
    use HasCurrencySymbol;

    public int $stepIndex;
    public int $payrollRunId;
    public array $tempAdjustments = [];
    protected array $existingAdjustmentsCache = [];

    // Filter properties
    public ?int $filterCompany = null;
    public ?int $filterDepartment = null;
    public ?int $filterLocation = null;
    public ?string $filterEmploymentStatus = 'Active';

    // Sorting properties
    public string $sortField = 'employee_name';
    public string $sortDirection = 'asc';

    // Search property
    public string $search = '';

    protected $listeners = [
        'saveAdjustments' => 'save',
        'refreshAdjustments' => '$refresh',
    ];

    public function mount(int $stepIndex, int $payrollRunId): void
    {
        $this->stepIndex = $stepIndex;
        $this->payrollRunId = $payrollRunId;
        $this->loadAdjustmentsCache();
        $this->initializeAllTempAdjustments();  // <-- add this line

    }


    protected function initializeAllTempAdjustments(): void
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);
        $allEmployeeIds = EmployeePosition::where('pay_schedule_id', $run->pay_schedule_id)
            ->pluck('employee_id');

        foreach ($allEmployeeIds as $employeeId) {
            $this->tempAdjustments[$employeeId] = [
                'Bonus' => $this->existingAdjustmentsCache[$employeeId]['Bonus'] ?? 0,
                'Commission' => $this->existingAdjustmentsCache[$employeeId]['Commission'] ?? 0,
                'Correction' => $this->existingAdjustmentsCache[$employeeId]['Correction'] ?? 0,
                'Reimbursement' => $this->existingAdjustmentsCache[$employeeId]['Reimbursement'] ?? 0,
                'Deduction' => $this->existingAdjustmentsCache[$employeeId]['Deduction'] ?? 0,
            ];
        }
    }


    protected function loadAdjustmentsCache(): void
    {
        $adjustments = PayrollRunAdjustment::where('payroll_run_id', $this->payrollRunId)
            ->get(['employee_id', 'type', 'amount']);

        foreach ($adjustments as $adj) {
            $this->existingAdjustmentsCache[$adj->employee_id][$adj->type] = $adj->amount;
        }
    }

    public function getEmployeesProperty()
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);

        $query = EmployeePosition::with('employee')
            ->where('pay_schedule_id', $run->pay_schedule_id);

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
        if ($this->filterEmploymentStatus === 'Active') {
            $query->where('employment_status', 'Active');
        } elseif ($this->filterEmploymentStatus === 'On Leave') {
            $query->where('employment_status', 'On Leave');
        } elseif ($this->filterEmploymentStatus === 'Terminated') {
            $query->where('employment_status', 'Terminated');
        }

        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }

        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
        }

        if ($this->filterCompany) {
            $query->whereHas('employee', function ($q) {
                $q->where('company_id', $this->filterCompany);
            });
        }

        // Apply sorting
        if ($this->sortField === 'employee_name') {
            $query->join('employees', 'employee_positions.employee_id', '=', 'employees.id')
                ->orderBy('employees.first_name', $this->sortDirection)
                ->orderBy('employees.last_name', $this->sortDirection)
                ->select('employee_positions.*');
        } elseif ($this->sortField === 'base_salary') {
            $query->orderBy('base_salary', $this->sortDirection);
        }

        $positions = $query->paginate(50);



        return $positions;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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














    public function updatedFilterCompany(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLocation(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEmploymentStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterCompany = null;
        $this->filterDepartment = null;
        $this->filterLocation = null;
        $this->filterEmploymentStatus = 'Active';
        $this->resetPage();
    }

    public function updatedTempAdjustments($value, $key): void
    {
        [$employeeId, $type] = explode('.', $key);
        $amount = (float) $value;
        $this->saveAdjustmentForEmployee($employeeId, $type, $amount);
    }

    protected function saveAdjustmentForEmployee($employeeId, $type, $amount): void
    {
        DB::transaction(function () use ($employeeId, $type, $amount) {
            $adjustment = PayrollRunAdjustment::firstOrNew([
                'payroll_run_id' => $this->payrollRunId,
                'employee_id' => $employeeId,
                'type' => $type,
            ]);

            if ($amount == 0 && $adjustment->exists) {
                $adjustment->delete();
                unset($this->existingAdjustmentsCache[$employeeId][$type]);
            } elseif ($amount != 0) {
                $adjustment->label = $type;
                $adjustment->amount = $amount;
                $adjustment->save();
                $this->existingAdjustmentsCache[$employeeId][$type] = $amount;
            }
        });
    }

    public function save(): void
    {
        /*foreach ($this->tempAdjustments as $employeeId => $types) {
            foreach ($types as $type => $amount) {
                $this->saveAdjustmentForEmployee($employeeId, $type, (float) $amount);
            }
        }*/
        // All adjustments are already saved individually via wire:model

        // Force recalculation when moving to preview
        $run = PayrollRun::find($this->payrollRunId);
        if ($run) {
            $run->update(['calculation_status' => 'pending']);
            // Delete old payslips to ensure clean slate (the job will recreate them)
            \App\Modules\Hr\Models\PayrollPayslip::where('payroll_run_id', $this->payrollRunId)->delete();
        }

        $this->dispatch('adjustmentsComplete');
    }



    public function render()
    {
        $run = PayrollRun::findOrFail($this->payrollRunId);

        // Prepare filter options (same as before)
        $employeePositions = EmployeePosition::where('pay_schedule_id', $run->pay_schedule_id)
            ->where('employment_status', 'Active')
            ->get(['employee_id', 'department_id', 'location_id']);

        $employeeIds = $employeePositions->pluck('employee_id')->unique();
        $departmentIds = $employeePositions->pluck('department_id')->unique()->filter();
        $locationIds = $employeePositions->pluck('location_id')->unique()->filter();

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

        $departments = Department::whereIn('id', $departmentIds)->get();
        $locations = Location::whereIn('id', $locationIds)->get();

        return view('hr::livewire.payroll.wizard-adjustments', [
            'employees' => $this->employees,
            'companies' => $companies,
            'departments' => $departments,
            'locations' => $locations,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'search' => $this->search,
        ]);
    }



}














