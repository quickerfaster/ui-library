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
    public $currentPosition;        // now directly from employee->employeePosition (hasOne)
    public $jobHistory;             // renamed from $positionHistory – collection of EmployeeJobHistory
    public $payrollProfile;
    public $payrollPayslip;
    
    public $workPatterns;
    public string $modelName;
    public string $moduleName;

    public $fieldDefinitions = [];
    public $profileFieldDefs = [];
    public $employeePositionFieldDefs = [];
    public $payrollFieldDefs = [];

    public ?array $recordIds = null;
    public ?int $currentIndex = null;
    public bool $inline = false;
    public ?array $returnParams = [];
    public string $activeTab = 'overview';

    protected ?FieldFactory $fieldFactory = null;
    public $selfServiceConfig = [];
    public $isSelfServiceMode = false;

    protected $listeners = [
        'employeeSelected' => 'jumpToEmployee',
        'refreshEmployeeWorkPatterns' => 'loadWorkPatterns'
    ];

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

        $this->loadSelfServiceConfig();
        $this->loadData();
        $this->loadFieldDefinitions();

        if ($this->recordIds === null && !empty($this->returnParams)) {
            $this->loadPageIds();
        }
    }

    protected function loadSelfServiceConfig(): void
    {
        $this->selfServiceConfig = [
            'enabled' => true,
            'allowedTabs' => ['overview', 'personal', 'employment', 'history', 'payroll','payslips', 'workpatterns', 'attendance', 'timeoff', 'documents', 'clockevents'],
            'hideEditButtons' => true,
        ];

        $isConfigEnabled = $this->selfServiceConfig['enabled'] ?? false;
        $isProfilePage = request()->is('hr/my-profile');
        $this->isSelfServiceMode = $isConfigEnabled && $isProfilePage;
    }

    public function canEdit(): bool
    {
        return !$this->isSelfServiceMode;
    }

    public function getAllowedTabs(): array
    {
        if ($this->isSelfServiceMode) {
            $allowed = $this->selfServiceConfig['allowedTabs'] ?? [];
            return array_intersect($this->getAllPossibleTabs(), $allowed);
        }
        return $this->getAllPossibleTabs();
    }

    protected function getAllPossibleTabs(): array
    {
        return ['overview', 'personal', 'contact', 'employment', 'history', 'payroll', 'payslips', 'workpatterns', 'attendance', 'timeoff', 'documents', 'clockevents'];
    }

    public function jumpToEmployee(int $id): void
    {
        $this->changeEmployee($id);
    }

    public function loadWorkPatterns(): void
    {
        $this->employee->load('employeeWorkPatterns.workPattern');
        $this->workPatterns = $this->employee->employeeWorkPatterns;
    }

    public function refreshEmployee(): void
    {
        $this->loadData();
        $this->loadFieldDefinitions();
    }

    protected function loadData(): void
    {
        if ($this->isSelfServiceMode) {
            $userEmployeeId = \App\Modules\Hr\Models\Employee::where('user_id', auth()->id())->value('id');
            if ($this->recordId != $userEmployeeId) {
                abort(403, 'You can only view your own profile.');
            }
        }

        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModel();

        // Load employee with the correct relations:
        // - position (hasOne) for current job data
        // - jobHistory (hasMany) for audit trail
        // - profile, workPatterns, etc.
        $this->employee = $modelClass::with([
            'employeeProfile',
            'employeePosition.jobTitle',           // current position
            'employeePosition.department',
            'employeePosition.manager',
            'employeePosition.reportsTo',
            'employeePosition.location',
            'employeePosition.shift',
            'employeePosition.attendancePolicy',
            'jobHistory',                  // history records (sorted by effective_date desc)
            'employeeWorkPatterns.workPattern',
        ])->findOrFail($this->recordId);

        $this->modelName = $resolver->getModelName();
        $this->moduleName = $resolver->getModuleName();

        $this->profile = $this->employee->employeeProfile;
        $this->currentPosition = $this->employee->employeePosition;          // simple hasOne
        $this->jobHistory = $this->employee->jobHistory;             // collection of EmployeeJobHistory
        $this->workPatterns = $this->employee->employeeWorkPatterns;

        // Payroll profile (separate query)
        $payrollModel = 'App\Modules\Hr\Models\EmployeePayrollProfile';
        $this->payrollProfile = $payrollModel::where('employee_id', $this->recordId)->first();

        if ($this->activeTab !== '') {
            $this->loadTabData($this->activeTab);
        }
    }

    /**
     * No longer needed – current position is simply $this->employee->employeePosition.
     * Kept for compatibility with existing view code.
     */
    protected function getCurrentPosition()
    {
        return $this->employee->employeePosition;
    }

    public function updatedActiveTab($newTab, $oldTab)
    {
        if ($newTab === 'personal' && $this->profile === null) {
            $this->loadTabData($newTab);
        } elseif ($newTab === 'employment' && $this->currentPosition === null) {
            $this->loadTabData($newTab);
        } elseif ($newTab === 'history') {
            $this->loadTabData($newTab);
        } elseif ($newTab === 'payroll' && $this->payrollProfile === null) {
            $this->loadTabData($newTab);
        } elseif ($newTab === 'payslips' && $this->payrollPayslip === null) {
            $this->loadTabData($newTab);
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
        $this->activeTab = 'personal';
    }

    protected function loadTabData(string $tab): void
    {
        switch ($tab) {
            case 'personal':
                $this->employee->load('employeeProfile');
                $this->profile = $this->employee->employeeProfile;
                break;
            case 'employment':
                // Refresh current position with all its relations
                $this->employee->load([
                    'employeePosition.jobTitle',
                    'employeePosition.department',
                    'employeePosition.manager',
                    'employeePosition.reportsTo',
                    'employeePosition.location',
                    'employeePosition.shift',
                    'employeePosition.attendancePolicy'
                ]);
                $this->currentPosition = $this->employee->employeePosition;
                break;
            case 'history':
                // Load the audit trail (EmployeeJobHistory) – already loaded in loadData, but refresh if needed
                $this->employee->load('jobHistory');
                $this->jobHistory = $this->employee->jobHistory;
                break;
            case 'payroll':
                $payrollModel = 'App\Modules\Hr\Models\EmployeePayrollProfile';
                $this->payrollProfile = $payrollModel::where('employee_id', $this->employee->id)->first();
                break;
            case 'payslips':
                $payrollModel = 'App\Modules\Hr\Models\PayrollPayslip';
                $this->payrollPayslip = $payrollModel::where('employee_id', $this->employee->id)->first();
                break;
            case 'workpatterns':
                $this->employee->load('employeeWorkPatterns.workPattern');
                $this->workPatterns = $this->employee->employeeWorkPatterns;
                break;
            case 'contact':
                $this->employee->load('employeeProfile');
                $this->profile = $this->employee->employeeProfile;
                break;
        }
    }
























    protected function loadFieldDefinitions(): void
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $this->fieldDefinitions = $resolver->getFieldDefinitions();

        $profileResolver = app(ConfigResolver::class, ['configKey' => 'hr.employee_profile']);
        $this->profileFieldDefs = $profileResolver->getFieldDefinitions();

        $positionResolver = app(ConfigResolver::class, ['configKey' => 'hr.employee_position']);
        $this->employeePositionFieldDefs = $positionResolver->getFieldDefinitions();

        $payrollResolver = app(ConfigResolver::class, ['configKey' => 'hr.employee_payroll_profile']);
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
            'position' => $this->employeePositionFieldDefs,
            'payroll' => $this->payrollFieldDefs,
            default => [],
        };

        if (!isset($defs[$fieldName])) {
            return e($value ?? '—');
        }

        $field = $this->getFieldFactory()->make($fieldName, $defs[$fieldName]);
        return $field->renderDetail($value, null);
    }

    // Computed properties
    public function getFullNameProperty(): string
    {
        return trim(($this->employee->first_name ?? '') . ' ' . ($this->employee->last_name ?? ''));
    }

    public function getJobTitleProperty(): string
    {
        return $this->currentPosition?->jobTitle?->title ?? '';
    }

    public function getDepartmentNameProperty(): string
    {
        return $this->currentPosition?->department?->name ?? '';
    }

    public function getStatusProperty(): string
    {
        return $this->currentPosition?->employment_status ?? 'Active';
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

        $search = $this->returnParams['search'] ?? null;
        if ($search) {
            $searchableFields = $this->getSearchableFields();
            if (!empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $search) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }
        }

        $filters = $this->returnParams['activeFilters'] ?? null;
        if ($filters && is_string($filters)) {
            $filters = json_decode($filters, true);
            $this->applyActiveFilters($query, $filters);
        }

        $sort = $this->returnParams['sort'] ?? null;
        if ($sort && is_string($sort)) {
            $sort = json_decode($sort, true);
            if (isset($sort['field']) && isset($sort['direction'])) {
                $query->orderBy($sort['field'], $sort['direction']);
            }
        }

        $perPage = $this->returnParams['perPage'] ?? 15;
        $page = $this->returnParams['page'] ?? 1;

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $this->recordIds = $paginator->pluck('id')->toArray();
        $this->currentIndex = array_search($this->recordId, $this->recordIds);
    }

    protected function getSearchableFields(): array
    {
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
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? null;
            $value = $filter['value'] ?? null;
            if (!$field || !$operator)
                continue;

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

    protected function applyStringFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'contains':
                $query->where($field, 'like', "%{$value}%");
                break;
            case 'starts_with':
                $query->where($field, 'like', "{$value}%");
                break;
            case 'ends_with':
                $query->where($field, 'like', "%{$value}");
                break;
            default:
                $query->where($field, $value);
        }
    }

    protected function applyNumberFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
            case 'less_than':
                $query->where($field, '<', $value);
                break;
            case 'greater_than_or_equals':
                $query->where($field, '>=', $value);
                break;
            case 'less_than_or_equals':
                $query->where($field, '<=', $value);
                break;
            case 'between':
                if (!empty($value['min']))
                    $query->where($field, '>=', $value['min']);
                if (!empty($value['max']))
                    $query->where($field, '<=', $value['max']);
                break;
        }
    }

    protected function applyDateFilter($query, $field, $operator, $value)
    {
        if ($operator === 'between' && is_array($value)) {
            if (!empty($value['start']))
                $query->whereDate($field, '>=', $value['start']);
            if (!empty($value['end']))
                $query->whereDate($field, '<=', $value['end']);
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
        if ($this->currentIndex > 0 && !empty($this->recordIds)) {
            $newId = $this->recordIds[$this->currentIndex - 1];
            $this->changeEmployee($newId);
        }
    }

    public function next(): void
    {
        if ($this->currentIndex < count($this->recordIds) - 1 && !empty($this->recordIds)) {
            $newId = $this->recordIds[$this->currentIndex + 1];
            $this->changeEmployee($newId);
        }
    }

    public function changeEmployee(int $newId): void
    {
        if ($newId === $this->recordId)
            return;

        $this->recordId = $newId;
        $this->loadData();
        $this->loadFieldDefinitions();

        if (!empty($this->recordIds)) {
            $this->currentIndex = array_search($newId, $this->recordIds);
            if ($this->currentIndex === false) {
                $this->loadPageIds();
            }
        }

        $this->dispatch('updateUrl', url: $this->getCurrentUrl($newId));
    }

    protected function getCurrentUrl(int $employeeId): string
    {
        $module = strtolower($this->moduleName);
        $modelPlural = \Str::plural(\Str::kebab($this->modelName));
        $url = url("/{$module}/{$modelPlural}/{$employeeId}");

        if (!empty($this->returnParams)) {
            $url .= '?' . http_build_query($this->returnParams);
        }
        return $url;
    }

    protected function getDaysUntilAnniversary(): int
    {
        if (!$this->employee->hire_date)
            return 0;
        $nextAnniversary = $this->employee->hire_date->copy()->addYears(now()->diffInYears($this->employee->hire_date) + 1);
        return now()->diffInDays($nextAnniversary);
    }

    public function render()
    {
        $widgetParams = [
            'full_name' => $this->fullName,
            'photo_url' => $this->photoUrl,
            'title' => $this->jobTitle,
            'fields' => [
                ['label' => 'Department', 'value' => $this->departmentName],
                ['label' => 'Status', 'value' => $this->status],
                ['label' => 'Hire Date', 'value' => $this->hireDate ?? '—'],
                ['label' => 'Manager', 'value' => $this->currentPosition?->manager?->name ?? '—'],
                ['label' => 'Work Email', 'value' => $this->employee->email ?? '—'],
            ],
            'actions' => [],
        ];

        return view('qf::livewire.custom.employee-detail', [
            'employee' => $this->employee,
            'profile' => $this->profile,
            'currentPosition' => $this->currentPosition,
            'jobHistory' => $this->jobHistory,      // renamed from $positionHistory
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
            'widgetParams' => $widgetParams,
        ]);
    }
}