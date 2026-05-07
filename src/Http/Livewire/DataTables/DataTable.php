<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use Livewire\WithPagination;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Traits\DataTables\HasColumnPreferences;
use App\Modules\Admin\Services\ActivityLogger;
use QuickerFaster\UILibrary\Services\Search\SearchEngine;






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

    public bool $enforceFilters = false;


    public bool $editable = false;              // master switch
    public array $editMode = [];                // ['rowId' => ['field' => true]]
    public array $editedData = [];              // temporary changes before save

    public array $fieldErrors = []; // format: [rowKey => [field => 'error message']]

    public array $searchableRelations = [];
    public array $selectedSearchColumns = [];   // columns to search in
    public bool $exactMatch = false;

    protected $listeners = [
        'performDelete' => 'performDelete',
        'refreshDataTable' => '$refresh',
        'executeBulkAction' => 'executeBulkAction',
        'filtersUpdated' => 'updateFilters',
        'executeRowAction' => 'executeRowAction',
        'searchApplied' => 'applySearchPanel',


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




    /**
     * Start editing a specific cell
     */
    public function startEditingCell($rowId, $field = null)
    {
        $rowKey = 'row_' . $rowId;

        // Always fetch fresh values from database (ignore any previous editedData)
        $this->editedData[$rowKey] = $this->getRowOriginalValues($rowId);

        // Reset edit mode for this row
        $this->editMode[$rowKey] = [];

        // Clear any previous errors for this row
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

        // Custom columns support (unchanged)
        if (!empty($this->customColumns)) {
            $this->columns = [];
            $this->searchableFields = [];
            $this->searchableRelations = [];

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
        foreach ($this->activeFilters as $filter) {
            if (empty($filter['field']) || !isset($filter['value'])) {
                continue;
            }


            $field = $filter['field'];
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'];

            // Skip empty values (but allow 0 and '0')
            if ($value === '' || $value === null || (is_array($value) && empty($value))) {
                continue;
            }

            // Get field definition to determine type
            $fieldDef = $this->columns[$field] ?? [];
            $fieldType = $fieldDef['field_type'] ?? 'string';

            // Route to type-specific handler
            $type = $this->mapFieldTypeToFilterType($fieldType);

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
                    $query->where($field, $operator, $value);
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
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Unauthorized access.']);
            return;
        }

        $query = ($this->getConfigResolver()->getModel())::query();

        // Only hit the DB once by chaining withTrashed conditionally
        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        $record = $query->find($recordId);

        if (!$record) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Record not found.']);
            return;
        }

        if (!$this->checkConditions($action, $record)) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Action unavailable for this record state.']);
            return;
        }

        $params = ["actionIndex" => $actionIndex, "recordId" => $recordId];
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

    protected function userCan(array $action): bool
    {
        return true; // Implement your permission logic
    }

    protected function checkConditions(array $action, $record): bool
    {


        // For now, return true for testing purpose
        return true;









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
            'selectedSearchColumns' => json_encode($this->selectedSearchColumns),
            'exactMatch' => $this->exactMatch ? '1' : '0',
            'sort' => $this->sort['field'],
            'direction' => $this->sort['direction'],
            'activeFilters' => json_encode($this->activeFilters),
            'trashedFilter' => $this->trashedFilter,
            'columns' => json_encode($this->visibleColumns),
            'perPage' => $this->perPage, // NEW
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