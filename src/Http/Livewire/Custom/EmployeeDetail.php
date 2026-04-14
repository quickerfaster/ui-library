<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Custom;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDetail extends Component
{
    public string $configKey;
    public int $recordId;

    public $employee;
    public $profile;
    public $position;
    public $payrollProfile;
    public $workPatterns;
    public string $modelName;
    public string $moduleName;

    public $fieldDefinitions = [];
    public $profileFieldDefs = [];
    public $positionFieldDefs = [];
    public $payrollFieldDefs = [];

    public ?array $recordIds = null; // Ids of all the records of row on a page of datateble
    public ?int $currentIndex = null;
    public bool $inline = false;
    public ?array $returnParams = [];
    public string $activeTab = 'overview';

    protected ?FieldFactory $fieldFactory = null;


public function refreshEmployee(): void
{
    $this->loadData();
    $this->loadFieldDefinitions();
}




    public function mount(
        string $configKey,
        int $recordId,
        ?array $recordIds = null,
        ?int $currentIndex = null,
        bool $inline = false,
        array $returnParams = []
    ): void {
        $this->configKey = $configKey;
        $this->recordId = $recordId;
        $this->recordIds = $recordIds;
        $this->currentIndex = $currentIndex;
        $this->inline = $inline;
        $this->returnParams = $returnParams;

        // Load data and field definitions
        $this->loadData();
        $this->loadFieldDefinitions();

        // If no recordIds were passed (i.e., we're in page mode) and we have returnParams,
        // try to load the IDs from the filtered list
        if ($this->recordIds === null && !empty($this->returnParams)) {
            $this->loadPageIds();
        }
    }



    

    /*protected function loadData(): void
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModel();
        $this->modelName = $resolver->getModelName();
        $this->moduleName = $resolver->getModuleName();

        $this->employee = $modelClass::with([
            'department',
            'employeeProfile',
            'employeePosition.jobTitle',
            'employeePosition.department',
            'employeePosition.manager',
            'employeePosition.reportsTo',
            'employeePosition.location',
            'employeePosition.shift',
            'employeePosition.attendancePolicy',
            'employeeWorkPatterns.workPattern',
        ])->findOrFail($this->recordId);

        $this->profile = $this->employee->employeeProfile;
        $this->position = $this->employee->employeePosition;
        $this->workPatterns = $this->employee->employeeWorkPatterns;

        // Payroll profile – separate query because no relation is defined
        $payrollModel = 'App\Modules\Hr\Models\EmployeePayrollProfile';
        $this->payrollProfile = $payrollModel::where('employee_id', $this->recordId)->first();
    }*/




        

protected function loadData(): void
{
    $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
    $modelClass = $resolver->getModel();
    
    // Core data only (always needed)
    $this->employee = $modelClass::with(['department'])->findOrFail($this->recordId);
    $this->modelName = $resolver->getModelName();
    $this->moduleName = $resolver->getModuleName();

    // Load data for the active tab (if any)
    if ($this->activeTab !== '') {
        $this->loadTabData($this->activeTab);
    }
}


public function updatedActiveTab($newTab, $oldTab)
{
    // Only load if we haven't loaded this tab's data yet (optional)
    if ($newTab === 'personal' && $this->profile === null) {
        $this->loadTabData($newTab);
    } elseif ($newTab === 'employment' && $this->position === null) {
        $this->loadTabData($newTab);
    } elseif ($newTab === 'payroll' && $this->payrollProfile === null) {
        $this->loadTabData($newTab);
        //$this->confirmPayrollAccess();
    } elseif ($newTab === 'workpatterns' && $this->workPatterns === null) {
        $this->loadTabData($newTab);
    }
}



public function confirmPayrollAccess()
{
    $this->dispatch('showAlert', [
        'type' => 'confirm',
        'title' => 'Confirm Access',
        'message' => 'Viewing payroll information requires your password. Continue?',
        'confirmEvent' => 'loadPayroll',
        'cancelEvent' => 'resetActiveTab'
    ]);
}

public function loadPayroll()
{
    $this->loadTabData('payroll');
}

public function resetActiveTab()
{
    $this->activeTab = 'personal'; // fallback
}


protected function loadTabData(string $tab): void
{
    switch ($tab) {
        case 'personal':
            $this->employee->load('employeeProfile');
            $this->profile = $this->employee->employeeProfile;
            break;
        case 'employment':
            $this->employee->load([
                'employeePosition.jobTitle',
                'employeePosition.department',
                'employeePosition.manager',
                'employeePosition.reportsTo',
                'employeePosition.location',
                'employeePosition.shift',
                'employeePosition.attendancePolicy'
            ]);
            $this->position = $this->employee->employeePosition;
            break;
        case 'payroll':
            $payrollModel = 'App\Modules\Hr\Models\EmployeePayrollProfile';
            $this->payrollProfile = $payrollModel::where('employee_id', $this->employee->employeePayrollProfile?->employee_id)->first();
            
            break;
        case 'workpatterns':
            $this->employee->load('employeeWorkPatterns.workPattern');
            $this->workPatterns = $this->employee->employeeWorkPatterns;
            break;
    }
}



    protected function loadFieldDefinitions(): void
    {
        // Main employee fields
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $this->fieldDefinitions = $resolver->getFieldDefinitions();

        // Profile fields – adjust configKey to match your actual file structure
        $profileResolver = app(ConfigResolver::class, ['configKey' => 'hr.employee_profile']); // or 'hr.employee_profile'
        $this->profileFieldDefs = $profileResolver->getFieldDefinitions();

        // Position fields
        $positionResolver = app(ConfigResolver::class, ['configKey' => 'hr.employee_position']); // or 'hr.employee_position'
        $this->positionFieldDefs = $positionResolver->getFieldDefinitions();

        // Payroll fields
        $payrollResolver = app(ConfigResolver::class, ['configKey' => 'hr.employee_payroll_profile']); // or 'hr.employee_payroll_profile'
        $this->payrollFieldDefs = $payrollResolver->getFieldDefinitions();
    }

    protected function getFieldFactory(): FieldFactory
    {
        if (!$this->fieldFactory) {
            $this->fieldFactory = app(FieldFactory::class);
        }
        return $this->fieldFactory;
    }

    protected function renderField(string $modelKey, string $fieldName, $value): string
    {
        $defs = match ($modelKey) {
            'employee' => $this->fieldDefinitions,
            'profile' => $this->profileFieldDefs,
            'position' => $this->positionFieldDefs,
            'payroll' => $this->payrollFieldDefs,
            default => [],
        };

        if (!isset($defs[$fieldName])) {
            return e($value ?? '—');
        }

        $field = $this->getFieldFactory()->make($fieldName, $defs[$fieldName]);
        return $field->renderDetail($value);
    }

    // Computed properties (access via $this->fullName, etc.)
    public function getFullNameProperty(): string
    {
        return trim(($this->employee->first_name ?? '') . ' ' . ($this->employee->last_name ?? ''));
    }

    public function getJobTitleProperty(): string
    {
        return $this->position?->jobTitle?->title ?? '';
    }

    public function getDepartmentNameProperty(): string
    {
        return $this->position?->department?->name ?? $this->employee->department?->name ?? '';
    }

    public function getStatusProperty(): string
    {
        return $this->position?->employment_status ?? $this->employee->status ?? 'Active';
    }

    public function getPhotoUrlProperty(): ?string
    {
        if ($this->profile && $this->profile->photo) {
            return Storage::url($this->profile->photo);
        }
        return null;
    }

    public function getHireDateProperty(): ?string
    {
        return $this->employee->hire_date ? $this->employee->hire_date->format('M d, Y') : null;
    }

    // Navigation methods
    protected function loadPageIds(): void
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModel();
        $query = $modelClass::query();

        // Apply search (if present)
        $search = $this->returnParams['search'] ?? null;
        if ($search) {
            // Determine searchable fields from config (or use a default set)
            $searchableFields = $this->getSearchableFields();
            if (!empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $search) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }
        }

        // Apply filters (if any)
        $filters = $this->returnParams['activeFilters'] ?? null;
        if ($filters && is_string($filters)) {
            $filters = json_decode($filters, true);
            $this->applyActiveFilters($query, $filters);
        }

        // Apply sort (if present)
        $sort = $this->returnParams['sort'] ?? null;
        if ($sort && is_string($sort)) {
            $sort = json_decode($sort, true);
            if (isset($sort['field']) && isset($sort['direction'])) {
                $query->orderBy($sort['field'], $sort['direction']);
            }
        }

        // Apply pagination
        $perPage = $this->returnParams['perPage'] ?? 15;
        $page = $this->returnParams['page'] ?? 1;

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $this->recordIds = $paginator->pluck('id')->toArray();
        $this->currentIndex = array_search($this->recordId, $this->recordIds);
    }

    protected function getSearchableFields(): array
    {
        // This should replicate the logic from DataTable's searchable fields.
        // For simplicity, we'll use the same approach: fields that are not hidden on table and have no relationship.
        $hiddenOnTable = $this->fieldDefinitions['hiddenFields']['onTable'] ?? [];
        $searchable = [];
        foreach ($this->fieldDefinitions as $field => $def) {
            if (!in_array($field, $hiddenOnTable) && !isset($def['relationship']) && ($def['searchable'] ?? true) !== false) {
                $searchable[] = $field;
            }
        }
        return $searchable;
    }

    protected function applyActiveFilters($query, array $filters): void
    {
        // Simplified version – you may want to copy the full logic from DataTable
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? null;
            $value = $filter['value'] ?? null;
            if (!$field || !$operator) continue;

            switch ($filter['type'] ?? 'string') {
                case 'string':
                    $this->applyStringFilter($query, $field, $operator, $value);
                    break;
                case 'number':
                    $this->applyNumberFilter($query, $field, $operator, $value);
                    break;
                case 'date':
                    $this->applyDateFilter($query, $field, $operator, $value);
                    break;
                case 'boolean':
                    $this->applyBooleanFilter($query, $field, $operator, $value);
                    break;
                default:
                    $query->where($field, $operator, $value);
            }
        }
    }

    // These helper methods are simplified; you can copy them from DataTable for full functionality
    protected function applyStringFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals': $query->where($field, $value); break;
            case 'contains': $query->where($field, 'like', "%{$value}%"); break;
            case 'starts_with': $query->where($field, 'like', "{$value}%"); break;
            case 'ends_with': $query->where($field, 'like', "%{$value}"); break;
            default: $query->where($field, $value);
        }
    }

    protected function applyNumberFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals': $query->where($field, $value); break;
            case 'not_equals': $query->where($field, '!=', $value); break;
            case 'greater_than': $query->where($field, '>', $value); break;
            case 'less_than': $query->where($field, '<', $value); break;
            case 'greater_than_or_equals': $query->where($field, '>=', $value); break;
            case 'less_than_or_equals': $query->where($field, '<=', $value); break;
            case 'between':
                if (!empty($value['min'])) $query->where($field, '>=', $value['min']);
                if (!empty($value['max'])) $query->where($field, '<=', $value['max']);
                break;
        }
    }

    protected function applyDateFilter($query, $field, $operator, $value)
    {
        // Simplified – you can expand with presets like 'today', 'this_week' etc.
        if ($operator === 'between' && is_array($value)) {
            if (!empty($value['start'])) $query->whereDate($field, '>=', $value['start']);
            if (!empty($value['end'])) $query->whereDate($field, '<=', $value['end']);
        } else {
            $query->whereDate($field, $operator, $value);
        }
    }

    protected function applyBooleanFilter($query, $field, $operator, $value)
    {
        if ($value !== '') {
            $query->where($field, $value);
        }
    }

public function previous(): void
{
    if ($this->recordIds === null && !empty($this->returnParams)) {
        $this->loadPageIds();
    }
    if ($this->currentIndex > 0 && !empty($this->recordIds)) {
        $newIndex = $this->currentIndex - 1;
        if (!$this->inline) {
            $this->redirectToEmployee($this->recordIds[$newIndex]);
        } else {
            $this->currentIndex = $newIndex;
            $this->recordId = $this->recordIds[$newIndex];
            $this->loadData();
            $this->loadFieldDefinitions();
        }
    }
}

public function next(): void
{
    if ($this->recordIds === null && !empty($this->returnParams)) {
        $this->loadPageIds();
    }
    if ($this->currentIndex < count($this->recordIds) - 1 && !empty($this->recordIds)) {
        $newIndex = $this->currentIndex + 1;
        if (!$this->inline) {
            $this->redirectToEmployee($this->recordIds[$newIndex]);
        } else {
            $this->currentIndex = $newIndex;
            $this->recordId = $this->recordIds[$newIndex];
            $this->loadData();
            $this->loadFieldDefinitions();
        }
    }
}



protected function getDaysUntilAnniversary(): int
{
    if (!$this->employee->hire_date) {
        return 0;
    }
    $nextAnniversary = $this->employee->hire_date->copy()->addYears(now()->diffInYears($this->employee->hire_date) + 1);
    return now()->diffInDays($nextAnniversary);
}










protected function redirectToEmployee($newId): void
{
    $routePrefix = Str::plural(Str::kebab($this->modelName));
    $url = url("/{$this->moduleName}/{$routePrefix}/{$newId}");
    $queryParams = $this->returnParams;
    unset($queryParams['id']);
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    $this->redirect($url);
}

    public function render()
    {
        return view('qf::livewire.custom.employee-detail', [
            'employee' => $this->employee,
            'profile' => $this->profile,
            'position' => $this->position,
            'payrollProfile' => $this->payrollProfile,
            'workPatterns' => $this->workPatterns,
            'fullName' => $this->fullName,
            'jobTitle' => $this->jobTitle,
            'departmentName' => $this->departmentName,
            'status' => $this->status,
            'photoUrl' => $this->photoUrl,
            'hireDate' => $this->hireDate,
            'recordIds' => $this->recordIds,
            'currentIndex' => $this->currentIndex,
            'activeTab' => $this->activeTab,
        ]);
    }
}