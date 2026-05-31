<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use Livewire\WithPagination;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Traits\DataTables\HasColumnPreferences;
use App\Modules\Admin\Services\ActivityLogger;
use QuickerFaster\UILibrary\Services\Search\SearchEngine;
use App\Modules\Admin\Services\AuthorizationService;
use QuickerFaster\UILibrary\Traits\Filters\AppliesFilters;




class DataTable extends Component
{
    use WithPagination, HasColumnPreferences, AppliesFilters;

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

    public bool $enforceFilters = false;


    public bool $editable = false;              // master switch
    public array $editMode = [];                // ['rowId' => ['field' => true]]
    public array $editedData = [];              // temporary changes before save

    public array $fieldErrors = []; // format: [rowKey => [field => 'error message']]

    public array $searchableRelations = [];
    public array $selectedSearchColumns = [];   // columns to search in
    public bool $exactMatch = false;


    public $filterModalOpen = false;
    public $columnFilterField = null;
    public $columnFilterValue = null;
    public array $allFieldDefinitions = [];

    // Add these properties
    public string $density = 'comfortable';      // 'comfortable' or 'compact'
    public bool $showInlineEditing = false;     // mirror of $editable, used in View menu

    protected AuthorizationService $authService;





    protected $listeners = [
        'performDelete' => 'performDelete',
        'refreshDataTable' => '$refresh',
        'executeBulkAction' => 'executeBulkAction',
        'filtersUpdated' => 'updateFilters',
        'executeRowAction' => 'executeRowAction',
        'searchApplied' => 'applySearchPanel',
        'columnsUpdated' => 'handleColumnsUpdated',



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
        $this->loadSortFromSession();




        $user = auth()->user();
        $modelName = $this->getModelNameForPermissions();
        $viewName = \Str::kebab($modelName);


        // Global view permission (no specific record)
        if (!$this->authService->canAccessView($user, $viewName)) {
            abort(403, 'You do not have permission to view this data.');
        }



        // Add to mount() after loading session filters
        $this->density = session()->get("density_{$this->configKey}", 'comfortable');
        $this->showInlineEditing = $this->editable;


        // Load saved filters from session
        $sessionFilters = $this->loadFiltersFromSession();
        $this->activeFilters = $sessionFilters;
        // Extract trashed filter from activeFilters if present
        foreach ($this->activeFilters as $filter) {
            if ($filter['field'] === '_trashed') {
                $this->trashedFilter = $filter['value'];
                break;
            }
        }

        $this->activeFilters = $this->enrichFiltersWithDisplayValues($this->activeFilters);
        $this->rebuildQuickFilterValues();

        $this->activeFilters = $this->sanitizeActiveFilters($this->activeFilters);


        $this->allColumns = array_keys($this->columns);
        $this->activeFilters = $this->sanitizeActiveFilters($this->activeFilters);


        // Merge initialActiveFilters with any existing activeFilters (from query string)
        $this->activeFilters = $this->sanitizeActiveFilters(
            array_merge($this->initialActiveFilters, $this->activeFilters)
        );

        $this->activeFilters = $this->sanitizeActiveFilters(array_merge($this->initialActiveFilters, $this->activeFilters));
        $this->activeFilters = $this->enrichFiltersWithDisplayValues($this->activeFilters);


        $this->rebuildQuickFilterValues();


        $this->validateSortField();

        // Determine default visible columns from config (or fallback)
        $defaultColumns = $this->getDefaultVisibleColumns();

        if ($this->showHideColumnsEnabled()) {
            // Load from session; if none, use $defaultColumns
            $this->visibleColumns = $this->loadVisibleColumns($this->configKey, $defaultColumns);
        } else {
            $this->visibleColumns = $this->allColumns;
        }
        // Final safety: ensure only valid columns are kept
        $this->visibleColumns = array_values(array_intersect($this->visibleColumns, array_keys($this->columns)));

        $this->perPage = (int) request()->query('perPage', 5);
        // Altenatively, use the saved settings from the SettingsManager
        // $settings = app(SettingsManager::class);
        // $this->perPage = $settings->get('pagination.per_page', 15);
        // $this->sort = $settings->get('default_sort', ['field' => 'id', 'direction' => 'asc']);

        if ($this->trashedFilter != 'without') // Initilise only applied  trashed filters
            $this->syncTrashedFilterToActiveFilters();


    }


    public function boot(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }







    public function handleColumnsUpdated($visibleColumns)
    {
        $this->visibleColumns = $visibleColumns;
        // Also update session in DataTable for consistency (though ColumnManager already does)
        if ($this->showHideColumnsEnabled()) {
            $this->saveVisibleColumns($this->configKey, $this->visibleColumns);
        }
        $this->resetPage();
        $this->dispatch('refreshDataTable');
    }



    public function openColumnManager()
    {
        $this->dispatch('openDrawer', 'qf.column-manager', ['configKey' => $this->configKey], 'Manage Columns');
    }





    /**
     * Set density (compact / comfortable) and persist in session.
     */
    public function setDensity(string $density): void
    {
        if (!in_array($density, ['comfortable', 'compact'])) {
            return;
        }
        $this->density = $density;
        session()->put("density_{$this->configKey}", $density);
        $this->dispatch('refreshDataTable');
    }

    /**
     * Set rows per page from the View menu dropdown.
     */
    public function setPerPageFromView(int $value): void
    {
        $this->perPage = $value;
        $this->resetPage();
    }



    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['table', 'list', 'card']) && isset($this->getConfigResolver()->getSwitchViews()[$mode])) {
            $this->viewMode = $mode;
            session(["view_preference.{$this->configKey}" => $mode]);
            $this->resetPage();
        }
    }



    public function toggleInlineEditing(): void
    {
        $this->editable = !$this->editable;
        $this->showInlineEditing = $this->editable;
        $this->editMode = [];
        $this->editedData = [];
        $this->dispatch('refreshDataTable');
    }

    public function openBackgroundJobsDrawer(): void
    {
        $this->dispatch('openDrawer', 'qf.background-jobs-panel', [], 'Background Jobs');
    }











    public function applySearchPanel($search, $columns, $exactMatch): void
    {
        $this->search = $search;
        $this->selectedSearchColumns = $columns;
        $this->exactMatch = $exactMatch;
        $this->resetPage();
        $this->dispatch('refreshDataTable');
    }

    public function openSearchDrawer(): void
    {
        $this->dispatch('openDrawer', 'qf.search-panel', [
            'configKey' => $this->configKey,
            'initialSearch' => $this->search,
            'initialColumns' => $this->selectedSearchColumns,
            'initialExactMatch' => $this->exactMatch,
        ], 'Search');
    }



    protected function saveSortToSession(): void
    {
        session()->put("sort_{$this->configKey}", $this->sort);
    }

    protected function loadSortFromSession(): void
    {
        $saved = session()->get("sort_{$this->configKey}");
        if ($saved && is_array($saved)) {
            $this->sort = $saved;
        }
    }


    /**
     * Start editing a specific cell
     */
    public function startEditingCell($rowId, $field = null)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($rowId);

        if (!$record) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Record not found.',
                'autoClose' => true
            ]);
            return;
        }

        if (!$this->authService->canUpdate(auth()->user(), $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to edit this record.',
                'autoClose' => true
            ]);
            return;
        }

        // Original logic continues...
        $rowKey = 'row_' . $rowId;
        $this->editedData[$rowKey] = $this->getRowOriginalValues($rowId);
        $this->editMode[$rowKey] = [];
        unset($this->fieldErrors[$rowKey]);
        if ($field) {
            $this->editMode[$rowKey][$field] = true;
        }
        $this->dispatch('$refresh');
    }

    /**
     * Get original values for all editable fields of a record
     */
    protected function getRowOriginalValues($rowId): array
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($rowId);
        if (!$record)
            return [];

        $values = [];
        foreach ($this->columns as $field => $def) {
            if (($def['editable'] ?? false)) {
                $values[$field] = $record->$field;
            }
        }
        return $values;
    }

    /**
     * Save a single cell value
     */

    public function saveCell($rowId, $field)
    {

        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($rowId);

        if (!$record) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Record not found.',
                'autoClose' => true
            ]);
            return;
        }

        if (!$this->authService->canUpdate(auth()->user(), $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to edit this record.',
                'autoClose' => true
            ]);
            return;
        }


        $rowKey = 'row_' . $rowId;
        $value = $this->editedData[$rowKey][$field] ?? null;

        // Validation (same as before)
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($rowId);
        if (!$record)
            return;

        $def = $this->getConfigResolver()->getFieldDefinitions()[$field];
        $fieldObj = $this->getField($field, $def);
        $rules = $fieldObj->getValidationRules();
        $validator = \Validator::make([$field => $value], $rules);

        if ($validator->fails()) {
            $this->fieldErrors[$rowKey][$field] = $validator->errors()->first($field);
            return;
        }

        // Save to database
        $oldValue = $record->$field;
        $record->$field = $value;
        $record->save();

        // Log activity
        ActivityLogger::updated($this->configKey, $record, [$field => $oldValue], [$field => $value]);

        // Clear all temporary data for this row
        unset($this->editMode[$rowKey]);
        unset($this->editedData[$rowKey]);
        unset($this->fieldErrors[$rowKey]);

        $this->dispatch('refreshDataTable');
    }


    /**
     * Download a blank import template with database field names as headers.
     */
    public function exportTemplate(): void
    {
        if (!$this->canImport()) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to download the import template.',
                'autoClose' => true
            ]);
            return;
        }
        $url = route('export.template', ['configKey' => $this->configKey]);
        $this->dispatch('open-url-new-tab', $url);
    }

    /**
     * Cancel editing a specific cell
     */
    public function cancelEditingCell($rowId, $field)
    {
        $rowKey = 'row_' . $rowId;
        // Just exit edit mode without saving
        unset($this->editMode[$rowKey][$field]);
        // Optionally keep editedData for other fields still being edited
        // But you may also want to clear the entire row if you prefer a clean state
    }


    /**
     * Helper to get original value from record (cached)
     */
    protected function getOriginalValue($rowId, $field)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        return $modelClass::find($rowId)->$field ?? null;
    }

    /**
     * Enable/disable editing globally
     */
    public function toggleEditing()
    {
        $this->editable = !$this->editable;
        $this->editMode = [];
        $this->editedData = [];

        //dd($this->editable);
    }


    public function clearSearch(): void
    {
        $this->search = '';
        $this->selectedSearchColumns = [];
        $this->exactMatch = false;

        session()->forget("search_columns.{$this->configKey}");
        session()->forget("search_term.{$this->configKey}");
        session()->forget("search_exactmatch.{$this->configKey}");

        $this->resetPage();
        $this->dispatch('refreshDataTable');
    }



    public function getSearchColumnsLabelsProperty(): array
    {
        $labels = [];
        foreach ($this->selectedSearchColumns as $field) {
            $labels[$field] = $this->columns[$field]['label'] ?? ucfirst($field);
        }
        return $labels;
    }



    public function openFilterDrawer(): void
    {
        $this->dispatch(
            'openDrawer',

            'qf.filter-panel',
            [
                'configKey' => $this->configKey,
                'initialFilters' => $this->activeFilters,
            ],
            'Filter Options'

        );
    }







    public function getAvatarUrl($record): ?string
    {
        // 1. Explicit config (highest priority)
        $avatarField = $this->viewConfig['avatarField'] ?? null;
        if ($avatarField) {
            $value = data_get($record, $avatarField);
            if ($value) {
                return $this->normalizeUrl($value);
            }
        }

        // 2. Intelligent fallback: try common relation paths (supports dot notation)
        $relationPaths = ['employeeProfile', 'profile', 'avatar', 'employee.employeeProfile'];
        $fields = ['photo', 'avatar_url', 'image', 'picture', 'profile_photo', 'avatar'];

        foreach ($relationPaths as $path) {
            $related = data_get($record, $path);
            if ($related && is_object($related)) {
                foreach ($fields as $field) {
                    $value = $related->$field ?? null;
                    if ($value) {
                        return $this->normalizeUrl($value);
                    }
                }
            }
        }

        // 3. Direct attribute on the main record
        foreach ($fields as $field) {
            if (isset($record->$field) && $record->$field) {
                return $this->normalizeUrl($record->$field);
            }
        }

        return null;
    }

    protected function normalizeUrl($value): ?string
    {
        if (!$value)
            return null;

        // Already a full URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Assume it's a storage path
        return \Storage::url($value);
    }


    public function getShowUrl($recordId): string
    {
        $module = strtolower($this->getConfigResolver()->getModuleName());
        $modelPlural = \Str::plural(\Str::kebab($this->getConfigResolver()->getModelName()));

        $queryParams = [
            'page' => $this->getPage(),
            'perPage' => $this->perPage,
            'search' => $this->search,
            'sort' => json_encode($this->sort),
            'activeFilters' => json_encode($this->activeFilters),
            'trashedFilter' => $this->trashedFilter,
        ];

        // Remove empty values to keep URL clean
        $queryParams = array_filter($queryParams, fn($v) => $v !== '' && $v !== null && $v !== []);

        // return url("/{$module}/{$modelPlural}/{$recordId}") . (empty($queryParams) ? '' : '?' . http_build_query($queryParams));
        return url("/{$modelPlural}/{$recordId}") . (empty($queryParams) ? '' : '?' . http_build_query($queryParams));
    }


    public function openDocumentPreview($url, $filename)
    {
        $this->dispatch('openDocumentPreview', ['fileUrl' => $url, 'fileName' => $filename]);
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
        $this->saveFiltersToSession();

        $this->resetPage();
        $this->dispatch('filtersUpdated', filters: $this->activeFilters);
        $this->dispatch('refreshColumnFilters');
    }

    /**
     * Clear a quick filter for a specific field.
     */
    public function clearQuickFilter(string $field): void
    {
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== $field));
        unset($this->quickFilterValues[$field]);
        $this->saveFiltersToSession();
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








    public function openColumnFilter($field)
    {
        $this->columnFilterField = $field;
        $this->columnFilterValue = $this->quickFilterValues[$field] ?? null;
        $this->filterModalOpen = true;
    }

    public function closeColumnFilter()
    {
        $this->filterModalOpen = false;
        $this->columnFilterField = null;
        $this->columnFilterValue = null;
    }

    public function applyColumnFilter()
    {
        if ($this->columnFilterField) {
            $this->applyQuickFilter($this->columnFilterField, $this->columnFilterValue);
        }
        $this->closeColumnFilter();
    }

    public function clearColumnFilter()
    {
        if ($this->columnFilterField) {
            $this->clearQuickFilter($this->columnFilterField);
        }
        $this->closeColumnFilter();
    }



    protected function enrichFiltersWithDisplayValues(array $filters): array
    {
        $result = [];
        foreach ($filters as $filter) {
            $field = $filter['field'];
            $value = $filter['value'];
            $def = $this->allFieldDefinitions[$field] ?? [];

            $displayValue = $value;


            // Handle many‑to‑many / select fields
            if (isset($def['relationship']) && in_array($def['relationship']['type'] ?? '', ['belongsToMany', 'morphToMany'])) {
                // Many‑to‑many: value is an array of IDs
                $displayValue = $this->getManyToManyDisplayValue($field, $value);
            } elseif (isset($def['relationship']) && $def['relationship']['type'] === 'belongsTo') {

                // Belongs to (foreign key) – single value
                $relatedModel = $def['relationship']['model'];
                $displayField = $def['relationship']['display_field'] ?? 'name';

                if ($value && class_exists($relatedModel)) {
                    $record = $relatedModel::find($value);
                    $displayValue = $record ? $record->$displayField : $value;
                }
            } elseif (($def['field_type'] ?? '') === 'select' || isset($def['options'])) {
                // Regular select (single or multi)
                $options = $def['options'] ?? [];
                if (is_array($value)) {
                    $displayValue = array_values(array_intersect_key($options, array_flip($value)));
                } else {
                    $displayValue = $options[$value] ?? $value;
                }
            }

            $result[] = array_merge($filter, [
                'displayValue' => $displayValue,
                'label' => $def['label'] ?? ucfirst($field),
            ]);
        }
        return $result;
    }

    protected function getManyToManyDisplayValue(string $field, $value): string
    {
        if (empty($value)) {
            return '';
        }
        $def = $this->columns[$field] ?? [];
        $rel = $def['relationship'] ?? [];
        $modelClass = $rel['model'] ?? null;
        $displayField = $rel['display_field'] ?? 'name';
        if (!$modelClass) {
            return is_array($value) ? implode(', ', $value) : (string) $value;
        }
        $ids = (array) $value;
        $records = $modelClass::whereIn('id', $ids)->pluck($displayField, 'id')->toArray();
        return implode(', ', $records);
    }





    protected function initializeComponent(): void
    {
        $this->initializeFromConfig();
        $this->allColumns = array_keys($this->columns);
        $this->validateSortField();





        // Determine default visible columns from config (or fallback)
        $defaultColumns = $this->getDefaultVisibleColumns();

        if ($this->showHideColumnsEnabled()) {
            // Load from session; if none, use $defaultColumns
            $this->visibleColumns = $this->loadVisibleColumns($this->configKey, $defaultColumns);
        } else {
            $this->visibleColumns = $this->allColumns;
        }
        // Final safety: ensure only valid columns are kept
        $this->visibleColumns = array_values(array_intersect($this->visibleColumns, array_keys($this->columns)));




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

    /**
     * Reset visible columns to the default set defined by config (or fallback).
     */
    public function resetColumns(): void
    {
        // Get the default visible columns from config (or fallback first 6)
        $defaultColumns = $this->getDefaultVisibleColumns();

        // Apply the default set
        $this->visibleColumns = $defaultColumns;

        // If column preferences are stored, save the reset state
        if ($this->showHideColumnsEnabled()) {
            $this->saveVisibleColumns($this->configKey, $this->visibleColumns);
        }

        // Reset pagination to avoid inconsistencies
        $this->resetPage();
    }




    protected function initializeFromConfig(): void
    {
        $resolver = $this->getConfigResolver();
        $fieldDefs = $resolver->getFieldDefinitions();
        $this->allFieldDefinitions = $fieldDefs;

        // Custom columns support (unchanged)
        if (!empty($this->customColumns)) {
            $this->columns = [];
            $this->searchableFields = [];
            $this->searchableRelations = [];
            $this->allFieldDefinitions = [];

            foreach ($this->customColumns as $field => $definition) {
                if (!isset($definition['field_type'])) {
                    $definition['field_type'] = 'string';
                }
                if (!isset($definition['label'])) {
                    $definition['label'] = ucfirst(str_replace('_', ' ', $field));
                }
                $this->columns[$field] = $definition;

                if (($definition['searchable'] ?? true) !== false) {
                    $this->searchableFields[] = $field;
                }

                $this->allFieldDefinitions[$field] = $definition;

            }
            return;
        }

        // --- Existing logic for building columns & searchable fields ---
        $configHidden = $resolver->getHiddenFields();
        foreach ($this->hiddenFields as $key => $fields) {
            if (isset($configHidden[$key])) {
                $configHidden[$key] = array_merge($configHidden[$key], $fields);
            } else {
                $configHidden[$key] = $fields;
            }
        }

        $hiddenOnTable = $configHidden['onTable'] ?? [];
        $fieldDefs = $resolver->getFieldDefinitions();
        $this->searchableFields = [];
        $this->searchableRelations = [];

        foreach ($fieldDefs as $field => $def) {
            if (in_array($field, $hiddenOnTable)) {
                continue;
            }
            if (!($def['searchable'] ?? false)) {
                continue;
            }

            if (isset($def['relationship'])) {
                $relationMethod = $this->getRelationMethodFromField($field, $def);
                if ($relationMethod) {
                    $displayColumn = $this->getRelationDisplayColumn($def);
                    if ($displayColumn) {
                        $this->searchableRelations[] = [
                            'field' => $field,
                            'relation' => $relationMethod,
                            'column' => $displayColumn,
                        ];
                    }
                }
            } else {
                $this->searchableFields[] = $field;
            }
        }

        // Columns = all non-hidden fields (existing behavior)
        $this->columns = array_diff_key($fieldDefs, array_flip($hiddenOnTable));


        // In initializeFromConfig() or mount(), after columns are defined
        if (empty($this->selectedSearchColumns)) {
            $savedColumns = session()->get("search_columns.{$this->configKey}");
            if (!empty($savedColumns)) {
                // Ensure saved columns still exist in current columns
                $this->selectedSearchColumns = array_values(array_intersect(
                    $savedColumns,
                    array_keys($this->columns)
                ));
            }

            // Also restore search term if any
            $savedSearch = session()->get("search_term.{$this->configKey}");
            if ($savedSearch && empty($this->search)) {
                $this->search = $savedSearch;
            }
        }



        // Existing code for perPage, viewMode, actions, etc. (keep as is)
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



    /**
     * For a select field with options, find matching keys based on search term.
     * Returns an array of keys where display label contains the search term (case-insensitive),
     * or the key itself contains the search term. If no matches, returns empty array.
     *
     * @param string $field
     * @param string $searchTerm
     * @return array
     */
    protected function getSearchKeysForSelectField(string $field, string $searchTerm): array
    {
        $fieldDef = $this->columns[$field] ?? [];
        $options = $fieldDef['options'] ?? [];

        if (empty($options) || !is_array($options)) {
            return [];
        }

        $lowerTerm = strtolower($searchTerm);
        $matchedKeys = [];

        foreach ($options as $key => $label) {
            $lowerKey = strtolower($key);
            $lowerLabel = strtolower($label);

            if (str_contains($lowerKey, $lowerTerm) || str_contains($lowerLabel, $lowerTerm)) {
                $matchedKeys[] = $key;
            }
        }

        return array_unique($matchedKeys);
    }





    /**
     * Returns the default visible columns based on config or fallback.
     * Does not consider session – pure config default.
     *
     * @return array
     */
    protected function getDefaultVisibleColumns(): array
    {
        $resolver = $this->getConfigResolver();
        $allColumns = array_keys($this->columns);

        // 1. Check for tableDefaultFields in config
        $tableDefaultFields = $resolver->getConfig()['tableDefaultFields'] ?? [];

        if (!empty($tableDefaultFields)) {
            // Intersect with actually existing columns (safety)
            $default = array_values(array_intersect($tableDefaultFields, $allColumns));
            if (!empty($default)) {
                return $default;
            }
        }

        // 2. Fallback: first 6 columns (performance-safe)
        return array_slice($allColumns, 0, 6);
    }



    /**
     * Determines if the "Reset Columns" button should be shown.
     * Returns true only when column management is enabled and the current visible
     * columns differ from the default set (config or fallback).
     */
    public function isResetVisible(): bool
    {
        if (!$this->showHideColumnsEnabled()) {
            return false;
        }

        $defaultColumns = $this->getDefaultVisibleColumns();
        return $this->visibleColumns != $defaultColumns;
    }







    protected function getRelationMethodFromField(string $field, array $def): ?string
    {
        if (!empty($def['relationship']['dynamic_property'])) {
            return $def['relationship']['dynamic_property'];
        }
        if (str_ends_with($field, '_id')) {
            $base = substr($field, 0, -3);
            // Optional: verify that the relation exists on the model
            $modelClass = $this->getConfigResolver()->getModel();
            if (method_exists($modelClass, $base)) {
                return $base;
            }
            return $base;
        }
        return null;
    }

    protected function getRelationDisplayColumn(array $def): ?string
    {
        if (!empty($def['relationship']['display_field'])) {
            return $def['relationship']['display_field'];
        }
        if (!empty($def['options']['column'])) {
            return $def['options']['column'];
        }
        return 'name';
    }








    public function updateFilters($filters)
    {

        $regularFilters = [];
        $trashedValue = 'without';

        foreach ($filters as $filter) {
            if ($filter['field'] === '_trashed') {
                $trashedValue = $filter['value'];
            } else {
                $regularFilters[] = $filter;
            }
        }

        $this->trashedFilter = $trashedValue;
        $this->activeFilters = $regularFilters;

        $this->syncTrashedFilterToActiveFilters(); // adds _trashed back

        $this->saveFiltersToSession();
        $this->rebuildQuickFilterValues();
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

        $this->saveSortToSession();
        $this->resetPage();
    }


    protected function rebuildQuickFilterValues(): void
    {
        $this->quickFilterValues = [];
        foreach ($this->activeFilters as $filter) {
            $this->quickFilterValues[$filter['field']] = $filter['value'];
        }
    }

    protected function getValueFromRecord($record, string $path)
    {
        return data_get($record, $path);
    }

    public function toggleViewMode(): void
    {
        $allowedModes = ['table', 'list', 'card'];
        $modes = array_keys($this->getConfigResolver()->getConfig()["switchViews"]);

        // Filter and then RESET the keys (0, 1, 2...)
        $modes = array_values(array_intersect($modes, $allowedModes));

        // Safety check: if no valid modes exist, stop to avoid division by zero
        if (empty($modes))
            return;

        $currentIndex = array_search($this->viewMode, $modes);

        // If current mode isn't in the new list, start at the first one
        if ($currentIndex === false) {
            $nextIndex = 0;
        } else {
            $nextIndex = ($currentIndex + 1) % count($modes);
        }

        $this->viewMode = $modes[$nextIndex];
        session(["view_preference.{$this->configKey}" => $this->viewMode]);
        $this->resetPage();
    }



    // Inside your Livewire component
    public function getNextViewModeProperty()
    {
        $allowedModes = ['table', 'list', 'card'];
        $modes = array_values(array_intersect(
            array_keys($this->getConfigResolver()->getConfig()["switchViews"]),
            $allowedModes
        ));

        if (empty($modes))
            return null;

        $currentIndex = array_search($this->viewMode, $modes);
        $nextIndex = ($currentIndex === false) ? 0 : ($currentIndex + 1) % count($modes);

        return $modes[$nextIndex];
    }







    public function getRecordsProperty()
    {
        $resolver = $this->getConfigResolver();
        $modelClass = $resolver->getModel();

        $query = $modelClass::query();

        // ✅ 1. SELECT ONLY VISIBLE COLUMNS
        $columnsToSelect = $this->visibleColumns ?? array_keys($this->columns);

        if (!in_array('id', $columnsToSelect)) {
            $columnsToSelect[] = 'id';
        }


        if ($this->usesSoftDeletes() && !in_array('deleted_at', $columnsToSelect)) {
            $columnsToSelect[] = 'deleted_at';
        }

        $query->select($columnsToSelect);

        // ✅ 2. CONDITIONAL RELATION LOADING
        $allowedRelations = [];

        foreach ($this->columns as $field => $def) {
            if (
                isset($def['relationship']) &&
                in_array($field, $this->visibleColumns)
            ) {
                $allowedRelations[] = $this->getRelationMethodFromField($field, $def);
            }
        }

        if (!empty($allowedRelations)) {
            $query->with(array_unique($allowedRelations));
        }

        // ✅ 3. SAFE SEARCH
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $columns = !empty($this->selectedSearchColumns)
                    ? $this->selectedSearchColumns
                    : array_slice($this->searchableFields, 0, 2);

                foreach ($columns as $field) {
                    $fieldDef = $this->columns[$field] ?? [];

                    // Handle select fields with options (fuzzy match on label/key)
                    if (isset($fieldDef['options']) && is_array($fieldDef['options'])) {
                        $keys = $this->getSearchKeysForSelectField($field, $this->search);
                        if (!empty($keys)) {
                            $q->orWhereIn($field, $keys);
                        }
                        continue; // Exact match toggle does not apply to select fields
                    }

                    // Regular field
                    if ($this->exactMatch) {
                        $q->orWhere($field, '=', $this->search);
                    } else {
                        $q->orWhere($field, 'like', $this->search . '%');
                    }
                }
            });
        }

        // ✅ 4. APPLY FILTERS
        $this->applyFilters($query, $this->queryFilters);
        $this->applyFilters($query, $this->pageQueryFilters, true);
        $this->applyActiveFilters($query);

        // ✅ 5. SOFT DELETE
        if ($this->usesSoftDeletes()) {
            match ($this->trashedFilter) {
                'only' => $query->onlyTrashed(),
                'with' => $query->withTrashed(),
                default => $query->withoutTrashed(),
            };
        }

        // ✅ 6. SORT
        if (!array_key_exists($this->sort['field'], $this->columns)) {
            $this->sort = ['field' => 'id', 'direction' => 'asc'];
        }

        $query->orderBy($this->sort['field'], $this->sort['direction']);

        // ✅ 7. FILTER CONTROL (FINAL POSITION)
        $hasFilters = !empty(array_filter($this->activeFilters ?? []));
        $hasSearch = !empty($this->search);

        if (!$hasFilters && !$hasSearch && $this->enforceFilters) {
            return $modelClass::query()
                ->whereRaw('1 = 0')
                //->cursorPaginate($this->perPage);
                ->paginate($this->perPage);
        }

        // ✅ 8. ALWAYS RETURN RESULT
        // return $query->cursorPaginate($this->perPage);
        return $query->paginate($this->perPage);
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

        if (!$record || !$record->trashed()) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found or not deleted.', 'autoClose' => true]);
            return;
        }

        if (!$this->authService->canRestore(auth()->user(), $modelClass)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to restore records.', 'autoClose' => true]);
            return;
        }

        $record->restore();
        ActivityLogger::log($this->configKey, 'restored', $record, [], [], 'Record restored');
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Record restored.', 'autoClose' => true]);
        $this->dispatch('refreshDataTable');
    }

    // Single record force delete
    public function forceDelete($id)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::withTrashed()->find($id);

        if (!$record) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found.', 'autoClose' => true]);
            return;
        }

        $modelName = $this->getModelNameForPermissions();
        if (!$this->authService->canForceDelete(auth()->user(), $modelClass)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to permanently delete records.', 'autoClose' => true]);
            return;
        }

        $old = $record->toArray();
        $record->forceDelete();
        ActivityLogger::deleted($this->configKey, $record, $old, true);
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Record permanently deleted.', 'autoClose' => true]);
        $this->dispatch('refreshDataTable');
    }










    // To Address the browser forward/backward error   
    public function fill($values)
    {
        parent::fill($values);
        if (!isset($this->activeFilters)) {
            $this->activeFilters = [];
        }
    }



    public function removeFilter(string $field): void
    {

        if ($field === '_trashed') {
            $this->trashedFilter = 'without';
            // Remove _trashed from activeFilters
            $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== '_trashed'));
            $this->saveFiltersToSession();
            $this->resetPage();
            return;
        }

        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== $field));
        $this->activeFilters = $this->enrichFiltersWithDisplayValues($this->activeFilters);
        unset($this->quickFilterValues[$field]);
        $this->saveFiltersToSession();
        $this->resetPage();
    }




    protected function saveFiltersToSession(): void
    {
        session()->put("active_filters.{$this->configKey}", $this->activeFilters);
    }

    protected function loadFiltersFromSession(): array
    {
        return session()->get("active_filters.{$this->configKey}", []);
    }

    protected function syncTrashedFilterToActiveFilters(): void
    {
        // Remove any existing _trashed filter
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== '_trashed'));

        if ($this->trashedFilter != "without") {

            // Add current trashedFilter as a filter
            $this->activeFilters[] = [
                'field' => '_trashed',
                'type' => 'select',
                'operator' => 'equals',
                'value' => $this->trashedFilter,
                'multi' => false,
            ];
        }
        // Re-enrich to get display values
        $this->activeFilters = $this->enrichFiltersWithDisplayValues($this->activeFilters);
    }



    public function clearAllFilters(): void
    {



        $this->activeFilters = [];
        $this->trashedFilter = 'without';
        $this->syncTrashedFilterToActiveFilters(); // adds _trashed with 'without'
        $this->activeFilters = array_values(array_filter($this->activeFilters, fn($f) => $f['field'] !== '_trashed'));

        $this->quickFilterValues = [];
        $this->saveFiltersToSession();
        // $this->dispatch('filtersUpdated', filters: $this->activeFilters);
        $this->resetPage();
    }

    // ==================== ROW ACTIONS ====================

    public function handleRowAction(int $actionIndex, int $recordId): void
    {
        $action = $this->moreActions[$actionIndex] ?? null;
        if (!$action) {
            return;
        }

        // Load record first (needed for both permission and conditions)
        $query = ($this->getConfigResolver()->getModel())::query();
        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }
        $record = $query->find($recordId);

        if (!$record) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found.']);
            return;
        }


        // Check permission with record
        if (!$this->authService->canPerformAction(auth()->user(), $action, $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to perform this action.',
                'autoClose' => true
            ]);
            return;
        }

        // Check business conditions (if any)
        if (!$this->checkConditions($action, $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'Action unavailable for this record state.',
                'autoClose' => true
            ]);
            return;
        }

        $params = ["actionIndex" => $actionIndex, "recordId" => $recordId];

        // If action requires confirmation
        if (!empty($action['confirm'])) {
            $this->dispatch('showAlert', [
                'type' => 'confirm',
                'title' => 'Confirm Action',
                'message' => $action['confirm'],
                'confirmEvent' => 'executeRowAction',
                'confirmParams' => $params,
            ]);
            return;
        }

        $this->executeRowAction($params);
    }


    public function executeRowAction($params): void
    {

        if (empty($params) || !is_array($params))
            return;

        if (!isset($params["actionIndex"]) || !isset($params["actionIndex"]))
            return;


        $actionIndex = $params["actionIndex"];
        $recordId = $params["recordId"];

        $action = $this->moreActions[$actionIndex] ?? null;
        if (!$action)
            return;


        $query = ($this->getConfigResolver()->getModel())::query();
        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        $record = $query->find($recordId);
        if (!$record) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found.']);
            return;
        }

        // Soft deleted [Restore]  or permanent [forceDelete] 
        $permission = $action["requiredPermission"] ?? '';
        $condition = $action["condition"] ?? '';
        $act = $action["action"] ?? '';

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



    protected function checkConditions(array $action, $record): bool
    {


        if (empty($action['condition']) || !is_array($action['condition']))
            return true;


        foreach ($action['condition'] as $field => $expected) {
            if (in_array($record->$field, $expected))
                return true;
        }
        return false;
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




    protected function canExport(): bool
    {
        return $this->authService->canExport(auth()->user(), $this->getConfigResolver()->getModel());
    }

    protected function canImport(): bool
    {
        return $this->authService->canImport(auth()->user(), $this->getConfigResolver()->getModel());
    }

    protected function canPrint(): bool
    {
        return $this->authService->canPrint(auth()->user(), $this->getConfigResolver()->getModel());
    }



    protected function getModelNameForPermissions(): string
    {
        return \Str::snake($this->getConfigResolver()->getModelName());
    }


    public function executeBulkAction(array $params): void
    {
        $actionKey = $params["actionKey"] ?? null;
        $action = $this->bulkActions[$actionKey] ?? null;
        if (!$action)
            return;

        $selectedIds = $this->bulkSelection['ids'] ?? [];
        if (empty($selectedIds)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'No items selected.', 'autoClose' => true]);
            return;
        }

        $user = auth()->user();
        $modelClass = $this->getConfigResolver()->getModel();

        // Permission checks based on action type
        switch ($action['type']) {
            case 'delete':
                if (!$this->authService->canBulkDelete($user, $modelClass)) {
                    $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to delete records.', 'autoClose' => true]);
                    return;
                }
                $this->performBulkDelete($selectedIds);
                break;

            case 'restore':
                if (!$this->authService->canBulkRestore($user, $modelClass)) {
                    $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to restore records.', 'autoClose' => true]);
                    return;
                }
                $this->performBulkRestore($selectedIds);
                break;

            case 'forceDelete':
                if (!$this->authService->canBulkForceDelete($user, $modelClass)) {
                    $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to permanently delete records.', 'autoClose' => true]);
                    return;
                }
                $this->performBulkForceDelete($selectedIds);
                break;

            case 'export':
                if (!$this->authService->canBulkExport($user, $modelClass)) {
                    $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to export records.', 'autoClose' => true]);
                    return;
                }
                $this->performBulkExport($selectedIds, $action['format']);
                break;

            case 'updateField':
                if (!$this->authService->canBulkUpdate($user, $modelClass)) {
                    $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'You do not have permission to update records.', 'autoClose' => true]);
                    return;
                }
                $this->performBulkUpdateField($selectedIds, $action['field'], $action['value']);
                break;

            default:
                return;
        }

        // Reset bulk selection after successful action
        $this->bulkSelection = ['all' => false, 'ids' => []];
        $this->dispatch('refreshDataTable');
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
        if (!isset($params["recordId"])) {
            return;
        }

        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($params["recordId"]);

        if (!$record) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Record not found.',
                'autoClose' => true
            ]);
            return;
        }

        if (!$this->authService->canDelete(auth()->user(), $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to delete this record.',
                'autoClose' => true
            ]);
            return;
        }

        ActivityLogger::deleted($this->configKey, $record, $record->toArray());
        $record->delete();

        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Record deleted successfully.',
            'autoClose' => true
        ]);
        $this->dispatch('refreshDataTable');
    }

    // ==================== EVENT EMITTERS ====================

    // Replace the existing add() method
    public function add($prefilledData = []): void
    {
        $modelClass = $this->getConfigResolver()->getModel();

        if (!$this->authService->canCreate(auth()->user(), $modelClass)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to create new records.',
                'autoClose' => true
            ]);
            return;
        }

        $this->dispatch('openAddModal', $this->configKey, $prefilledData);
    }



    public function edit($id)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($id);

        if (!$record) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Record not found.',
                'autoClose' => true
            ]);
            return;
        }

        if (!$this->authService->canUpdate(auth()->user(), $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to edit this record.',
                'autoClose' => true
            ]);
            return;
        }

        $this->dispatch('openEditModal', $this->configKey, $id);
    }



    public function show($id)
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $record = $modelClass::find($id);

        if (!$record) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Record not found.',
                'autoClose' => true
            ]);
            return;
        }

        if (!$this->authService->canView(auth()->user(), $record)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to view this record.',
                'autoClose' => true
            ]);
            return;
        }

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
            $query = SearchEngine::apply(
                $query,
                $this->search,
                array_slice($this->searchableFields, 0, 2)
            );
        }

        $this->applyFilters($query, $this->queryFilters);
        $this->applyFilters($query, $this->pageQueryFilters, true);
        $this->applyActiveFilters($query);
        $query->orderBy($this->sort['field'], $this->sort['direction']);

        // $paginator = $query->paginate($this->perPage);
        // return $paginator->pluck('id')->toArray();
        return $this->records->pluck('id')->toArray();
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

        if (!$this->canExport()) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to export records.',
                'autoClose' => true
            ]);
            return;
        }



        // PDF limit check
        if ($format === 'pdf') {
            $totalRows = $this->getTotalRowsCount(); // implement helper
            if ($totalRows > 500) {
                $this->dispatch('showAlert', [
                    'type' => 'warning',
                    'message' => 'PDF export is limited to 500 rows. Use XLS or CSV for larger datasets.',
                ]);
                return;
            }
        }

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
        if (!$this->canImport()) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to import records.',
                'autoClose' => true
            ]);
            return;
        }
        $this->dispatch('openImportModal', $this->configKey);
    }

    public function print(): void
    {
        if (!$this->canPrint()) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'You do not have permission to print.',
                'autoClose' => true
            ]);
            return;
        }
        $url = route('print.data', [
            'configKey' => $this->configKey,
            'search' => $this->search,
            'selectedSearchColumns' => json_encode($this->selectedSearchColumns),
            'exactMatch' => $this->exactMatch ? '1' : '0',
            'sort' => $this->sort['field'],
            'direction' => $this->sort['direction'],
            'activeFilters' => json_encode($this->activeFilters),
            'trashedFilter' => $this->trashedFilter,
            'columns' => json_encode($this->visibleColumns),
            'perPage' => $this->perPage,
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
        $crudType = $resolver->getConfig()['crudType'] ?? false;

        return view('qf::livewire.data-tables.data-table', [
            'records' => $this->records,
            'columns' => $this->columns,
            'allColumns' => $this->allColumns,
            'viewConfig' => $viewConfig,
            'switchViews' => $switchViews,
            'viewMode' => $this->viewMode,
            'crudType' => $crudType,
            'controls' => $controls,
            'simpleActions' => $simpleActions,
            'bulkActions' => $this->bulkActions,
            'filesActions' => $this->filesActions,
            'modelName' => $resolver->getModelName(),
        ]);
    }
}