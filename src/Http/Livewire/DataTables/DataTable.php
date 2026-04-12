<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use Livewire\WithPagination;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Traits\DataTables\HasColumnPreferences;
use App\Modules\Admin\Services\ActivityLogger;






class DataTable extends Component
{
    use WithPagination, HasColumnPreferences;

    // Public properties (persisted in query string)
    public string $configKey;
    public string $search = '';
    public array $sort = ['field' => 'id', 'direction' => 'asc'];
    public $perPage = 5;

    protected $paginationTheme = 'bootstrap';

    public $bulkSelection = [
        'all' => false,
        'ids' => [],
    ];

    public string $viewMode = 'table';
    public array $hiddenFields = [];
    public array $queryFilters = [];
    public array $pageQueryFilters = [];

    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;
    public array $searchableFields = [];
    public array $columns = [];

    public array $moreActions = [];
    public array $bulkActions = [];
    public array $filesActions = [];
    public array $activeFilters = [];

    public array $visibleColumns = [];
    public array $allColumns = [];
    public array $quickFilterValues = [];
    public array $customColumns = [];
    public array $initialActiveFilters = [];
    public string $trashedFilter = 'without'; // without, with, only

    public bool $columnDropdownOpen = false;

    protected $listeners = [
        'performDelete' => 'performDelete',
        'refreshDataTable' => '$refresh',
        'executeBulkAction' => 'executeBulkAction',
        'filtersUpdated' => 'updateFilters',
        'executeRowAction' => 'executeRowAction',
    ];

    public function mount(
        string $configKey,
        array $hiddenFields = [],
        array $queryFilters = [],
        array $pageQueryFilters = [],
        array $customColumns = []  // <-- NEW
    ) {
        
        $this->configKey = $configKey;
        $this->hiddenFields = $hiddenFields;
        $this->queryFilters = $queryFilters;
        $this->pageQueryFilters = $pageQueryFilters;
        $this->customColumns = $customColumns; // <-- NEW

        $this->initializeFromConfig();
        $this->initializeComponent();

        $this->allColumns = array_keys($this->columns);
        $this->activeFilters = $this->sanitizeActiveFilters($this->activeFilters);


        // Merge initialActiveFilters with any existing activeFilters (from query string)
        $this->activeFilters = $this->sanitizeActiveFilters(
            array_merge($this->initialActiveFilters, $this->activeFilters)
        );

        $this->validateSortField();

        if ($this->showHideColumnsEnabled()) {
            $this->visibleColumns = $this->loadVisibleColumns($this->configKey, $this->allColumns);
        } else {
            $this->visibleColumns = $this->allColumns;
        }

        $this->perPage = (int) request()->query('perPage', 5);
        // Altenatively, use the saved settings from the SettingsManager
        // $settings = app(SettingsManager::class);
        // $this->perPage = $settings->get('pagination.per_page', 15);
        // $this->sort = $settings->get('default_sort', ['field' => 'id', 'direction' => 'asc']);

    }







    /**
     * Apply a quick filter from column header.
     */
    public function applyQuickFilter(string $field, $value): void
    {
        if ($value === '' || $value === null) {
            $this->clearQuickFilter($field);
            return;
        }

        // Determine field type and build filter structure
        $fieldDef = $this->columns[$field] ?? [];
        $filterType = $this->mapFieldTypeToFilterType($fieldDef['field_type'] ?? 'string');

        // Remove existing filter for this field if any
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== $field));

        // Add new filter
        $operator = 'equals';
        $filterValue = $value;

        if ($filterType === 'select' && isset($fieldDef['options']) && count($fieldDef['options']) > 1) {
            // For multi-value selects? Keep simple for now: single equals
            $operator = 'equals';
        } elseif ($filterType === 'date') {
            $operator = 'equals';
        } elseif ($filterType === 'string') {
            $operator = 'contains'; // more intuitive for quick filter
        }

        $this->activeFilters[] = [
            'field' => $field,
            'type' => $filterType,
            'operator' => $operator,
            'value' => $filterValue,
        ];

        $this->quickFilterValues[$field] = $value;
        $this->resetPage();
        $this->dispatch('filtersUpdated', filters: $this->activeFilters);
    }

    /**
     * Clear a quick filter for a specific field.
     */
    public function clearQuickFilter(string $field): void
    {
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== $field));
        unset($this->quickFilterValues[$field]);
        $this->resetPage();
        $this->dispatch('filtersUpdated', filters: $this->activeFilters);
    }


    public function clearAllQuickFilters(): void
    {
        $this->quickFilterValues = [];
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => !in_array($f['field'], array_keys($this->columns))));
        $this->resetPage();
        $this->dispatch('filtersUpdated', filters: $this->activeFilters);
    }









    protected function initializeComponent(): void
    {
        $this->initializeFromConfig();
        $this->allColumns = array_keys($this->columns);
        $this->validateSortField();

        if ($this->showHideColumnsEnabled()) {
            $this->visibleColumns = $this->loadVisibleColumns($this->configKey, $this->allColumns);
        } else {
            $this->visibleColumns = $this->allColumns;
        }
    }

    public function updatedConfigKey($value)
    {
        $this->resetPage();
        $this->search = '';
        $this->sort = ['field' => 'id', 'direction' => 'asc'];
        $this->perPage = 15;
        $this->viewMode = 'table';
        $this->activeFilters = [];
        $this->bulkSelection = ['all' => false, 'ids' => []];
        $this->queryFilters = [];
        $this->pageQueryFilters = [];

        $this->initializeComponent();
        $this->activeFilters = $this->sanitizeActiveFilters($this->activeFilters);
    }

    public function toggleColumnDropdown(): void
    {
        $this->columnDropdownOpen = !$this->columnDropdownOpen;
    }

    public function closeColumnDropdown(): void
    {
        $this->columnDropdownOpen = false;
    }

    public function queryString()
    {
        return [
            'search' => ['except' => ''],
            'sort' => ['except' => ['field' => 'id', 'direction' => 'asc']],
            'perPage' => ['except' => 15],
            'viewMode' => ['except' => 'table'],
            'activeFilters' => ['as' => 'filters-' . $this->configKey, 'except' => ''],
            'trashedFilter' => ['except' => 'without'],

        ];
    }

    protected function validateSortField(): void
    {
        if (!array_key_exists($this->sort['field'], $this->columns)) {
            $this->sort = ['field' => 'id', 'direction' => 'asc'];
        }
    }

    protected function sanitizeActiveFilters(array $filters): array
    {
        $fieldDefinitions = $this->getConfigResolver()->getFieldDefinitions();
        return array_filter($filters, function ($filter) use ($fieldDefinitions) {
            return isset($fieldDefinitions[$filter['field']]);
        });
    }

    protected function showHideColumnsEnabled(): bool
    {
        $controls = $this->getConfigResolver()->getControls();
        return $controls['showHideColumns'] ?? false;
    }

    public function toggleColumn(string $column): void
    {
        if (!in_array($column, $this->allColumns)) {
            return;
        }

        if (in_array($column, $this->visibleColumns)) {
            $this->visibleColumns = array_values(array_diff($this->visibleColumns, [$column]));
        } else {
            $this->visibleColumns = array_values(array_intersect(
                $this->allColumns,
                array_merge($this->visibleColumns, [$column])
            ));
        }

        if ($this->showHideColumnsEnabled()) {
            $this->saveVisibleColumns($this->configKey, $this->visibleColumns);
        }
        $this->resetPage();
    }

    public function resetColumns(): void
    {
        $this->visibleColumns = $this->allColumns;
        if ($this->showHideColumnsEnabled()) {
            $this->saveVisibleColumns($this->configKey, $this->visibleColumns);
        }
        $this->resetPage();
    }



    protected function initializeFromConfig(): void
    {
        $resolver = $this->getConfigResolver();

        // NEW: If custom columns are provided, use them instead of config field definitions
        if (!empty($this->customColumns)) {
            // Build a temporary field definitions array from customColumns
            // customColumns format: ['field_name' => ['label' => '...', 'field_type' => '...', ...]]
            // We need to ensure each definition has at least the minimal required keys.
            $this->columns = [];
            $this->searchableFields = [];

            foreach ($this->customColumns as $field => $definition) {
                // Ensure a field_type (default to 'string' if missing)
                if (!isset($definition['field_type'])) {
                    $definition['field_type'] = 'string';
                }
                // Ensure label
                if (!isset($definition['label'])) {
                    $definition['label'] = ucfirst(str_replace('_', ' ', $field));
                }
                $this->columns[$field] = $definition;

                // If searchable is not explicitly false, add to searchable fields
                if (($definition['searchable'] ?? true) !== false) {
                    $this->searchableFields[] = $field;
                }
            }

            // For custom columns, we cannot rely on config hidden fields – ignore them.
            // Also, we should not apply perPage override from config.
            return;
        }

        // --- Existing logic (when no custom columns) ---
        $configHidden = $resolver->getHiddenFields();
        foreach ($this->hiddenFields as $key => $fields) {
            if (isset($configHidden[$key])) {
                $configHidden[$key] = array_merge($configHidden[$key], $fields);
            } else {
                $configHidden[$key] = $fields;
            }
        }

        $hiddenOnTable = $configHidden['onTable'] ?? [];
        $this->searchableFields = collect($resolver->getFieldDefinitions())
            ->reject(fn($def, $field) => in_array($field, $hiddenOnTable))
            ->filter(fn($def) => ($def['searchable'] ?? true) !== false)
            ->reject(fn($def) => isset($def['relationship']))
            ->keys()
            ->toArray();

        $this->columns = array_diff_key(
            $resolver->getFieldDefinitions(),
            array_flip($hiddenOnTable)
        );

        $perPageOptions = $resolver->getControls()['perPage'] ?? null;
        if ($perPageOptions && !empty($perPageOptions)) {
            $this->perPage = $perPageOptions[0];
        }

        $defaultView = $resolver->getSwitchViews()['default'] ?? 'table';
        $this->viewMode = session("view_preference.{$this->configKey}", $defaultView);
        $this->moreActions = $resolver->getMoreActions();

        $controls = $resolver->getControls();
        $this->bulkActions = $this->parseBulkActions($controls['bulkActions'] ?? []);
        $this->filesActions = $controls['files'] ?? [];
    }



    public function updateFilters($filters)
    {
        $this->activeFilters = $this->sanitizeActiveFilters($filters);
        $this->resetPage();
    }

    protected function parseBulkActions(array $bulkActionsConfig): array
    {
        $actions = [];
        foreach ($bulkActionsConfig as $key => $value) {
            if ($key === 'export' && is_array($value)) {
                foreach ($value as $format) {
                    $formatKey = 'export_' . $format;
                    $actions[$formatKey] = [
                        'type' => 'export',
                        'label' => 'Export as ' . strtoupper($format),
                        'icon' => $this->getExportIcon($format),
                        'format' => $format,
                        'confirm' => null,
                    ];
                }
            } elseif ($key === 'delete' && $value === true) {
                $actions['delete'] = [
                    'type' => 'delete',
                    'label' => 'Delete',
                    'icon' => 'fas fa-trash',
                    'confirm' => 'Delete selected items?',
                ];
            } elseif ($key === 'updateModelFields' && is_array($value)) {
                foreach ($value as $field => $fieldConfig) {
                    $actions['update_field_' . $field] = [
                        'type' => 'updateField',
                        'label' => $fieldConfig['label'] ?? 'Update ' . $field,
                        'icon' => $fieldConfig['icon'] ?? 'fas fa-edit',
                        'field' => $field,
                        'value' => $fieldConfig['value'] ?? null,
                        'confirm' => $fieldConfig['confirm'] ?? null,
                    ];
                }
            }


            if ($key === 'restore' && $value === true) {
                $actions['restore'] = [
                    'type' => 'restore',
                    'label' => 'Restore Selected',
                    'icon' => 'fas fa-trash-restore',
                    'confirm' => 'Restore selected items?',
                ];
            }
            if ($key === 'forceDelete' && $value === true) {
                $actions['forceDelete'] = [
                    'type' => 'forceDelete',
                    'label' => 'Permanently Delete',
                    'icon' => 'fas fa-skull-crossbones',
                    'confirm' => 'This action cannot be undone. Permanently delete selected items?',
                ];
            }





        }
        return $actions;
    }

    protected function getExportIcon(string $format): string
    {
        return match ($format) {
            'pdf' => 'fas fa-file-pdf',
            'csv' => 'fas fa-file-csv',
            'xls', 'xlsx' => 'fas fa-file-excel',
            default => 'fas fa-download',
        };
    }

    protected function getConfigResolver(): ConfigResolver
    {
        if (!$this->configResolver) {
            $this->configResolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        }
        return $this->configResolver;
    }

    protected function getFieldFactory(): FieldFactory
    {
        if (!$this->fieldFactory) {
            $this->fieldFactory = app(FieldFactory::class);
        }
        return $this->fieldFactory;
    }

    public function getField(string $name, array $definition)
    {
        return $this->getFieldFactory()->make($name, $definition);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $fieldDefinitions = $this->getConfigResolver()->getFieldDefinitions();
        if (isset($fieldDefinitions[$field]['relationship'])) {
            return;
        }

        if ($this->sort['field'] === $field) {
            $this->sort['direction'] = $this->sort['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = ['field' => $field, 'direction' => 'asc'];
        }
        $this->resetPage();
    }

    protected function getValueFromRecord($record, string $path)
    {
        return data_get($record, $path);
    }

    public function toggleViewMode(): void
    {
        $modes = ['table', 'list', 'card'];
        $currentIndex = array_search($this->viewMode, $modes);
        $nextIndex = ($currentIndex + 1) % count($modes);
        $this->viewMode = $modes[$nextIndex];
        session(["view_preference.{$this->configKey}" => $this->viewMode]);
        $this->resetPage();
    }

    public function getRecordsProperty()
    {
        $resolver = $this->getConfigResolver();
        $modelClass = $resolver->getModel();
        $query = $modelClass::query();

        $relations = array_keys($resolver->getRelations());
        if (!empty($relations)) {
            $query->with($relations);
        }

        if ($this->search !== '' && !empty($this->searchableFields)) {
            $query->where(function ($q) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', '%' . $this->search . '%');
                }
            });
        }

        $this->applyFilters($query, $this->queryFilters);
        $this->applyFilters($query, $this->pageQueryFilters, true);
        $this->applyActiveFilters($query);
        // Apply trashed filter only if model uses SoftDeletes
        if ($this->usesSoftDeletes()) {
            if ($this->trashedFilter === 'only') {
                $query->onlyTrashed();
            } elseif ($this->trashedFilter === 'with') {
                $query->withTrashed();
            } else {
                $query->withoutTrashed();
            }
        }

        $query->orderBy($this->sort['field'], $this->sort['direction']);
        return $query->paginate($this->perPage)->withQueryString();
    }


protected function usesSoftDeletes(): bool
{
    $modelClass = $this->getConfigResolver()->getModel();
    return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($modelClass));
}


    // Single record restore
    public function restore($id)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::withTrashed()->find($id);
        if ($record && $record->trashed()) {
            $record->restore();
            ActivityLogger::log($this->configKey, 'restored', $record, [], [], 'Record restored');
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Record restored.']);
            $this->dispatch('refreshDataTable');
        }
    }

    // Single record force delete
    public function forceDelete($id)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::withTrashed()->find($id);
        if ($record) {
            $old = $record->toArray();
            $record->forceDelete();
            ActivityLogger::deleted($this->configKey, $record, $old, true);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Record permanently deleted.']);
            $this->dispatch('refreshDataTable');
        }
    }












    protected function applyActiveFilters($query): void
    {
        if (!isset($this->activeFilters))
            return;

        $fieldDefinitions = $this->getConfigResolver()->getFieldDefinitions();

        foreach ($this->activeFilters as $filter) {
            if (!isset($fieldDefinitions[$filter['field']])) {
                continue;
            }
            if (isset($fieldDefinitions[$filter['field']]['relationship'])) {
                continue;
            }

            $fieldDef = $fieldDefinitions[$filter['field']];
            $expectedType = $this->mapFieldTypeToFilterType($fieldDef['field_type'] ?? 'string');
            if ($filter['type'] !== $expectedType) {
                continue;
            }

            $field = $filter['field'];
            $type = $filter['type'];
            $operator = $filter['operator'];
            $value = $filter['value'];

            switch ($type) {
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
                case 'select':
                    $this->applySelectFilter($query, $field, $operator, $value);
                    break;
                default:
                    $query->where($field, $value);
            }
        }

    }

    // To Address the browser forward/backward error   
    public function fill($values)
    {
        parent::fill($values);
        if (!isset($this->activeFilters)) {
            $this->activeFilters = [];
        }
    }

    protected function mapFieldTypeToFilterType(string $fieldType): string
    {
        return match ($fieldType) {
            'string', 'textarea', 'text' => 'string',
            'number', 'integer', 'float' => 'number',
            'datepicker', 'datetimepicker' => 'date',
            'checkbox', 'boolcheckbox', 'radio' => 'boolean',
            'select' => 'select',
            default => 'string',
        };
    }

    protected function applyStringFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'contains':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value . '%');
                break;
            case 'ends_with':
                $query->where($field, 'like', '%' . $value);
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
                if (!empty($value['min'])) {
                    $query->where($field, '>=', $value['min']);
                }
                if (!empty($value['max'])) {
                    $query->where($field, '<=', $value['max']);
                }
                break;
        }
    }

    protected function applyDateFilter($query, $field, $operator, $value)
    {
        $now = now();
        switch ($operator) {
            case 'equals':
                $query->whereDate($field, $value);
                break;
            case 'not_equals':
                $query->whereDate($field, '!=', $value);
                break;
            case 'greater_than':
                $query->whereDate($field, '>', $value);
                break;
            case 'less_than':
                $query->whereDate($field, '<', $value);
                break;
            case 'between':
                if (!empty($value['start'])) {
                    $query->whereDate($field, '>=', $value['start']);
                }
                if (!empty($value['end'])) {
                    $query->whereDate($field, '<=', $value['end']);
                }
                break;
            case 'today':
                $query->whereDate($field, $now->toDateString());
                break;
            case 'this_week':
                $query->whereBetween($field, [
                    $now->copy()->startOfWeek()->toDateString(),
                    $now->copy()->endOfWeek()->toDateString()
                ]);
                break;
            case 'this_month':
                $query->whereMonth($field, $now->month)->whereYear($field, $now->year);
                break;
            case 'this_year':
                $query->whereYear($field, $now->year);
                break;
            case 'last_week':
                $lastWeek = $now->copy()->subWeek();
                $query->whereBetween($field, [
                    $lastWeek->copy()->startOfWeek()->toDateString(),
                    $lastWeek->copy()->endOfWeek()->toDateString()
                ]);
                break;
            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                $query->whereMonth($field, $lastMonth->month)->whereYear($field, $lastMonth->year);
                break;
            case 'last_year':
                $lastYear = $now->copy()->subYear();
                $query->whereYear($field, $lastYear->year);
                break;
            case 'last_7_days':
                $query->whereDate($field, '>=', $now->subDays(7));
                break;
            case 'next_30_days':
                $query->whereBetween($field, [$now, $now->addDays(30)]);
                break;
            case 'this_quarter':
                $query->whereBetween($field, [$now->startOfQuarter(), $now->endOfQuarter()]);
                break;
            case 'last_quarter':
                $lastQuarterStart = $now->subQuarter()->startOfQuarter();
                $query->whereBetween($field, [$lastQuarterStart, $lastQuarterStart->copy()->endOfQuarter()]);
                break;
        }
    }

    protected function applyBooleanFilter($query, $field, $operator, $value)
    {
        if ($value !== '') {
            $query->where($field, $value);
        }
    }

    protected function applySelectFilter($query, $field, $operator, $value)
    {
        if ($value === '' || $value === null) {
            return;
        }
        if ($operator === 'in' || is_array($value)) {
            $query->whereIn($field, (array) $value);
        } else {
            $query->where($field, $value);
        }
    }

    protected function applyFilters($query, array $filters, bool $mandatory = false): void
    {
        $fieldDefinitions = $this->getConfigResolver()->getFieldDefinitions();

        foreach ($filters as $filter) {
            if (!is_array($filter) || count($filter) !== 3) {
                continue;
            }
            [$field, $operator, $value] = $filter;
            if (!array_key_exists($field, $fieldDefinitions)) {
                continue;
            }
            $query->where($field, $operator, $value);
        }
    }

    public function removeFilter(string $field): void
    {
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== $field));
        $this->dispatch('filtersUpdated', filters: $this->activeFilters);
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->activeFilters = [];
        $this->dispatch('filtersUpdated', filters: []);
        $this->resetPage();
    }

    // ==================== ROW ACTIONS ====================

    public function handleRowAction(int $actionIndex, int $recordId): void
    {
        $action = $this->moreActions[$actionIndex] ?? null;
        if (!$action)
            return;

        if (!$this->userCan($action)) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'You do not have permission to perform this action.']);
            return;
        }

        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::withTrashed()->find($recordId);

        if (!$record) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found.']);
            return;
        }

        if (!$this->checkConditions($action, $record)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'This action cannot be performed due to the current record state.']);
            return;
        }

        if (!empty($action['confirm'])) {
            $this->dispatch('showAlert', [
                'type' => 'confirm',
                'title' => 'Confirm Action',
                'message' => $action['confirm'],
                'confirmEvent' => 'executeRowAction',
                'confirmParams' => ["actionIndex" => $actionIndex, "recordId" => $recordId],
            ]);
            return;
        }




        //$this->executeRowAction($actionIndex, $recordId);
    }

    public function executeRowAction($params): void
    {
        if (empty($params) || !is_array($params) )
            return;

        if (!isset($params["actionIndex"]) || !isset($params["actionIndex"]) )
            return;


        $actionIndex = $params["actionIndex"];
        $recordId = $params["recordId"];

        $action = $this->moreActions[$actionIndex] ?? null;
        if (!$action)
            return;


        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::withTrashed()->find($recordId);
        if (!$record) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found.']);
            return;
        }

        // Soft deleted [Restore]  or permanent [forceDelete] 
        $permission = $action["requiredPermission"]?? '';
        $condition = $action["condition"]?? '';
        $act = $action["action"]?? '';

        // Restore the soft deleted
        if ($act && $act == "restore") {
            $record->restore();
        }
        // Force Delete
        if ($act && $act == "forceDelete") {
            $record->forceDelete();
        }





        if (!empty($action['updateModelField'])) {
            $field = $action['fieldName'];
            $value = $action['fieldValue'];
            $oldValues = $record->toArray();
            $record->$field = $value;
            $record->save();

            ActivityLogger::log(
                $this->configKey,
                $action['log_action'] ?? 'custom_action',
                $record,
                $oldValues,
                [$field => $value],
                $action['successMessage'] ?? 'Custom action executed'
            );

            $this->dispatch('showAlert', ['type' => 'success', 'message' => $action['successMessage'] ?? 'Record updated successfully.', 'autoClose' => true]);
            $this->dispatch('$refresh');
            return;
        }

        if (!empty($action['dispatchStandardEvent'])) {
            $eventClass = $action['eventClass'];
            $params = $this->buildParams($action['params'] ?? [], $record);
            event(new $eventClass(...$params));
            $this->dispatch('showAlert', ['type' => 'success', 'message' => $action['successMessage'] ?? 'Event dispatched.', 'autoClose' => true]);
            return;
        }

        if (!empty($action['dispatchLivewireEvent'])) {
            $eventName = $action['eventName'];
            $params = $this->buildParams($action['params'] ?? [], $record);
            $this->dispatch($eventName, ...$params);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => $action['successMessage'] ?? 'Action triggered.', 'autoClose' => true]);
            return;
        }

        if (!empty($action['url']) || !empty($action['route'])) {
            $url = $this->generateActionUrl($action, $record);
            if ($url) {
                $this->dispatch('open-url-new-tab', $url);
            }
            return;
        }
    }

    protected function userCan(array $action): bool
    {
        return true; // Implement your permission logic
    }

    protected function checkConditions(array $action, $record): bool
    {
        if (empty($action['condition']) || !is_array($action['condition']))
            return true;

        
        foreach ($action['condition'] as $field => $expected) {
            if ($record->$field != $expected)
                return false;
        }
        return true;
    }

    protected function replacePlaceholders($value, $record)
    {
        if (!is_string($value))
            return $value;
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($record) {
            return data_get($record, $matches[1], '');
        }, $value);
    }

    protected function buildParams(array $params, $record): array
    {
        $result = [];
        foreach ($params as $key => $value) {
            $result[$key] = $this->replacePlaceholders($value, $record);
        }
        return $result;
    }

    protected function generateActionUrl(array $action, $record): ?string
    {
        if (!empty($action['route'])) {
            $params = $this->buildParams($action['params'] ?? [], $record);
            return route($action['route'], $params);
        }
        if (!empty($action['url'])) {
            return $this->replacePlaceholders($action['url'], $record);
        }
        return null;
    }

    // ==================== BULK ACTIONS ====================

    public function handleBulkAction(string $actionKey): void
    {
        $action = $this->bulkActions[$actionKey] ?? null;
        if (!$action)
            return;

        $selectedIds = $this->bulkSelection['ids'] ?? [];
        if (empty($selectedIds)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'No items selected.']);
            return;
        }

        if (!empty($action['confirm'])) {
            $this->dispatch('showAlert', [
                'type' => 'confirm',
                'title' => 'Confirm Bulk Action',
                'message' => $action['confirm'],
                'confirmEvent' => 'executeBulkAction',
                'confirmParams' => ["actionKey" => $actionKey],
            ]);
            return;
        }

        $this->executeBulkAction(["actionKey" => $actionKey]);
    }

    public function executeBulkAction(array $params): void
    {
        $actionKey = $params["actionKey"] ?? null;
        $action = $this->bulkActions[$actionKey] ?? null;
        if (!$action)
            return;

        $selectedIds = $this->bulkSelection['ids'] ?? [];
        if (empty($selectedIds))
            return;

        switch ($action['type']) {
            case 'delete':
                $this->performBulkDelete($selectedIds);
                break;
            case 'export':
                $this->performBulkExport($selectedIds, $action['format']);
                break;
            case 'updateField':
                $this->performBulkUpdateField($selectedIds, $action['field'], $action['value']);
                break;
            case 'restore':
                $this->performBulkRestore($selectedIds);
                break;
            case 'forceDelete':
                $this->performBulkForceDelete($selectedIds);
                break;
            default:
                return;
        }

        $this->bulkSelection = ['all' => false, 'ids' => []];
        $this->dispatch('$refresh');
    }


protected function performBulkRestore(array $ids): void
{
    $modelClass = $this->getConfigResolver()->getModel();
    $count = $modelClass::onlyTrashed()->whereIn('id', $ids)->restore();
    ActivityLogger::log($this->configKey, 'bulk_restored', null, [], ['ids' => $ids], $count . ' records restored');
    $this->dispatch('showAlert', ['type' => 'success', 'message' => $count . ' records restored.', 'autoClose' => true]);
}

protected function performBulkForceDelete(array $ids): void
{
    $modelClass = $this->getConfigResolver()->getModel();
    $records = $modelClass::withTrashed()->whereIn('id', $ids)->get();
    $count = $records->count();
    foreach ($records as $record) {
        ActivityLogger::deleted($this->configKey, $record, $record->toArray(), true);
        $record->forceDelete();
    }
    $this->dispatch('showAlert', ['type' => 'success', 'message' => $count . ' records permanently deleted.', 'autoClose' => true]);
}





protected function performBulkDelete(array $ids): void
{
    $modelClass = $this->getConfigResolver()->getModel();
    $controls = $this->getConfigResolver()->getControls();
    $softDelete = $controls['softDelete'] ?? false;
    
    if ($softDelete) {
        $modelClass::whereIn('id', $ids)->delete(); // soft delete
        ActivityLogger::log($this->configKey, 'bulk_soft_deleted', null, [], ['ids' => $ids], count($ids) . ' records moved to trash');
        $this->dispatch('showAlert', ['type' => 'success', 'message' => count($ids) . ' records moved to trash.', 'autoClose' => true]);
    } else {
        $modelClass::whereIn('id', $ids)->delete(); // hard delete
        $this->dispatch('showAlert', ['type' => 'success', 'message' => count($ids) . ' records deleted.', 'autoClose' => true]);
    }
}

public function isTrashed($record): bool
{
    return method_exists($record, 'trashed') && $record->trashed();
}






    protected function performBulkExport(array $ids, string $format): void
    {
        $threshold = 1000;
        $columns = $this->showHideColumnsEnabled() ? $this->visibleColumns : [];

        if (count($ids) <= $threshold) {
            $url = route('export.data', [
                'configKey' => $this->configKey,
                'ids' => implode(',', $ids),
                'format' => $format,
                'columns' => implode(',', $columns),
            ]);
            $this->dispatch('open-url-new-tab', $url);
        } else {
            $params = [
                'configKey' => $this->configKey,
                'format' => $format,
                'columns' => implode(',', $columns),
                'filters' => json_encode(array_merge(
                    $this->activeFilters,
                    [['field' => 'id', 'type' => 'number', 'operator' => 'in', 'value' => $ids]]
                )),
                'options' => json_encode($this->getExportOptions($format)),
            ];
            $this->dispatch('startExport', $params);
        }
    }

    protected function getExportOptions(string $format): array
    {
        $controls = $this->getConfigResolver()->getControls();
        return $controls['files']['export_options'][$format] ?? [];
    }

    protected function performBulkUpdateField(array $ids, string $field, $value): void
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $modelClass::whereIn('id', $ids)->update([$field => $value]);
        $this->dispatch('showAlert', ['type' => 'success', 'message' => count($ids) . ' records updated.', 'autoClose' => true]);
    }

    // ==================== SINGLE DELETE ====================

    public function confirmDelete($id)
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Delete?',
            'message' => 'Are you sure?',
            'icon' => 'fas fa-trash-alt text-danger',
            'size' => 'sm',
            'confirmEvent' => 'performDelete',
            'confirmParams' => ["recordId" => $id],
        ]);
    }

    public function performDelete($params)
    {
        if (isset($params["recordId"])) {
            $modelClass = $this->getConfigResolver()->getModel();
            $record = $modelClass::find($params["recordId"]);
            if ($record) {
                ActivityLogger::deleted($this->configKey, $record, $record->toArray());
                $record->delete();
            }
        }
    }

    // ==================== EVENT EMITTERS ====================

    // Replace the existing add() method
    public function add($prefilledData = []): void
    {
        $this->dispatch('openAddModal', $this->configKey, $prefilledData);
    }

    public function edit($id)
    {
        $this->dispatch('openEditModal', $this->configKey, $id);
    }

    public function show($id)
    {
        $currentPageIds = $this->getCurrentPageIds();
        $index = array_search($id, $currentPageIds);
        $this->dispatch('openDetailModal', $this->configKey, $id, $currentPageIds, $index);
    }

    protected function getCurrentPageIds(): array
    {
        $resolver = $this->getConfigResolver();
        $modelClass = $resolver->getModel();
        $query = $modelClass::query();

        $relations = array_keys($resolver->getRelations());
        if (!empty($relations)) {
            $query->with($relations);
        }

        if ($this->search !== '' && !empty($this->searchableFields)) {
            $query->where(function ($q) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', '%' . $this->search . '%');
                }
            });
        }

        $this->applyFilters($query, $this->queryFilters);
        $this->applyFilters($query, $this->pageQueryFilters, true);
        $this->applyActiveFilters($query);
        $query->orderBy($this->sort['field'], $this->sort['direction']);

        $paginator = $query->paginate($this->perPage);
        return $paginator->pluck('id')->toArray();
    }

    // ==================== BULK SELECTION ====================

    public function updatedBulkSelectionAll($value)
    {
        if ($value) {
            $this->bulkSelection['ids'] = $this->records->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->bulkSelection['ids'] = [];
        }
    }

    public function updated($name, $value)
    {
        if (str_contains($name, 'bulkSelection.ids')) {
            $countOnPage = $this->records->count();
            $this->bulkSelection['all'] = (count($this->bulkSelection["ids"]) === $countOnPage && $countOnPage > 0);
        }
    }

    // ==================== FILES ====================

    public function exportAll(string $format): void
    {
        $columns = $this->showHideColumnsEnabled() ? $this->visibleColumns : [];
        $params = [
            'configKey' => $this->configKey,
            'format' => $format,
            'columns' => implode(',', $columns),
            'filters' => json_encode($this->activeFilters),
            'options' => json_encode($this->getExportOptions($format)),
        ];
        $this->dispatch('openExportModal', ['configKey' => $this->configKey, 'params' => $params]);
    }

    public function import(): void
    {
        $this->dispatch('openImportModal', $this->configKey);
    }

    public function print(): void
    {
        $url = route('print.data', [
            'configKey' => $this->configKey,
            'search' => $this->search,
            'sort' => $this->sort['field'],
            'direction' => $this->sort['direction'],
            'filters' => json_encode($this->queryFilters),
        ]);
        $this->dispatch('open-url-new-tab', $url);
    }

    // ==================== RENDER ====================

    public function render()
    {
        $resolver = $this->getConfigResolver();
        $switchViews = $resolver->getSwitchViews();

        $viewConfig = [];
        if ($this->viewMode === 'list' || $this->viewMode === 'card') {
            $viewConfig = $switchViews[$this->viewMode] ?? [];
        }

        $controls = $resolver->getControls();
        $simpleActions = $resolver->getConfig()['simpleActions'] ?? [];
        $viewType = $resolver->getConfig()['viewType'] ?? false;

        return view('qf::livewire.data-tables.data-table', [
            'records' => $this->records,
            'columns' => $this->columns,
            'allColumns' => $this->allColumns,
            'viewConfig' => $viewConfig,
            'switchViews' => $switchViews,
            'viewMode' => $this->viewMode,
            'viewType' => $viewType,
            'controls' => $controls,
            'simpleActions' => $simpleActions,
            'bulkActions' => $this->bulkActions,
            'filesActions' => $this->filesActions,
            'modelName' => $resolver->getModelName(),
        ]);
    }
}