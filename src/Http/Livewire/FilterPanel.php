<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Models\SavedFilter;

class FilterPanel extends Component
{
    public string $configKey;
    public array $filtersConfig = [];
    public array $fieldDefinitions = [];
    public array $activeFilters = [];
    public array $savedFilters = [];

    public $filterName = '';
    public $filterIsGlobal = false;

    // NEW: Edit mode properties
    public ?int $editingFilterId = null;
    public string $modalTitle = 'Save Filter Set';
    public string $saveButtonLabel = 'Save';

    // Accept initial filters from the parent (DataTable)
    public array $initialFilters = [];

    protected ?ConfigResolver $configResolver = null;


    public array $searches = [];      // Search queries per filter index
    public array $searchResults = []; // Results per filter index
    public array $selectedLabels = []; // Labels for selected IDs per filter index


        public bool $saveFilterModalOpen = false;



    protected $listeners = [
        'confirmClearFilters' => 'confirmClearFilters',
        'confirmDeleteSavedFilter' => 'deleteSavedFilter'
    ];



    public function mount(string $configKey, array $initialFilters = []): void
    {
        $this->configKey = $configKey;
        $this->initialFilters = $initialFilters;
        $this->loadConfig();
        $this->normalizeFilterConfig();

        // Always use initialFilters (from DataTable) – no session restore
        $this->activeFilters = [];
        foreach ($this->filtersConfig as $index => $config) {
            $found = false;
            foreach ($initialFilters as $filter) {
                if ($filter['field'] === $config['field']) {
                    $this->activeFilters[$index] = [
                        'operator' => $filter['operator'],
                        'value' => $filter['value'],
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $defaultOperator = $config['defaultOperator'];
                $this->activeFilters[$index] = [
                    'operator' => $defaultOperator,
                    'value' => $this->getDefaultValueForType($config['type'], $defaultOperator, $config['multi'] ?? false),
                ];
            }
        }

        $this->loadSavedFilters();
        $this->loadFilterSelectedLabels();
    }


    public function updateFilters($filters)
    {
        $this->activeFilters = $this->sanitizeActiveFilters($filters);
        $this->saveFiltersToSession();
        $this->resetPage();
    }



    protected function initializeFilters(): void
    {
        if (!empty($this->initialFilters)) {
            foreach ($this->filtersConfig as $index => $config) {
                $found = false;
                foreach ($this->initialFilters as $filter) {
                    if ($filter['field'] === $config['field']) {
                        $this->activeFilters[$index] = [
                            'operator' => $filter['operator'],
                            'value' => $filter['value'],
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $defaultOperator = $config['defaultOperator'];
                    $this->activeFilters[$index] = [
                        'operator' => $defaultOperator,
                        'value' => $this->getDefaultValueForType($config['type'], $defaultOperator, $config['multi'] ?? false),
                    ];
                }
            }
        } else {
            foreach ($this->filtersConfig as $index => $filter) {
                $defaultOperator = $filter['defaultOperator'];
                $this->activeFilters[$index] = [
                    'operator' => $defaultOperator,
                    'value' => $this->getDefaultValueForType($filter['type'], $defaultOperator, $filter['multi'] ?? false),
                ];
            }
        }
    }

    // ========== SAVED FILTERS – EDIT MODE ==========
public function showSaveFilterModal(?int $filterId = null): void
{
    if ($filterId) {
        $filter = SavedFilter::where('id', $filterId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $this->filterName = $filter->name;
        $this->filterIsGlobal = $filter->is_global;
        $this->editingFilterId = $filterId;
        $this->modalTitle = 'Rename Filter';
        $this->saveButtonLabel = 'Update';
    } else {
        $this->filterName = '';
        $this->filterIsGlobal = false;
        $this->editingFilterId = null;
        $this->modalTitle = 'Save Filter Set';
        $this->saveButtonLabel = 'Save';
    }
    $this->saveFilterModalOpen = true; // open modal
}



public function openSaveFilterModal(): void
{
    $this->saveFilterModalOpen = true;
}

public function closeSaveFilterModal(): void
{
    $this->saveFilterModalOpen = false;
    $this->reset(['filterName', 'filterIsGlobal', 'editingFilterId']);
}




    public function saveFilter(): void
    {
        $this->validate(['filterName' => 'required|string|max:255']);

        if ($this->editingFilterId) {
            $filter = SavedFilter::where('id', $this->editingFilterId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $filter->update([
                'name' => $this->filterName,
                'is_global' => $this->filterIsGlobal,
                // Optionally overwrite filters – uncomment next line if you want to update filter conditions too
                // 'filters' => $this->activeFilters,
            ]);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Filter renamed']);
        } else {
            $this->saveCurrentFilters($this->filterName, $this->filterIsGlobal);
        }

    $this->closeSaveFilterModal(); // close modal
        $this->reset(['filterName', 'filterIsGlobal', 'editingFilterId']);
        $this->loadSavedFilters();
    }

    // ========== DELETE WITH CONFIRMATION ==========
    public function confirmDeleteSavedFilter(int $id): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Delete Filter',
            'message' => 'Are you sure you want to delete this saved filter?',
            'confirmEvent' => 'confirmDeleteSavedFilter',
            'confirmParams' => ['id' => $id],
        ]);
    }

    public function deleteSavedFilter(array $params): void
    {
        $id = $params['id'] ?? null;
        if ($id) {
            SavedFilter::where('id', $id)->where('user_id', Auth::id())->delete();
            $this->loadSavedFilters();
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Filter set deleted']);
        }
    }

    // ========== EXISTING METHODS (unchanged except where noted) ==========
    protected function queryString()
    {
        return [
            'activeFilters' => ['as' => 'panel-filters-' . $this->configKey, 'except' => ''],
        ];
    }

    protected function getConfigResolver(): ConfigResolver
    {
        if (!$this->configResolver) {
            $this->configResolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        }
        return $this->configResolver;
    }

    protected function loadConfig(): void
    {
        $resolver = $this->getConfigResolver();
        $controls = $resolver->getControls();
        $this->filtersConfig = $controls['filters'] ?? [];
        $this->fieldDefinitions = $resolver->getFieldDefinitions();

        if (empty($this->filtersConfig) && ($controls['autoFilters'] ?? true)) {
            $this->filtersConfig = $this->generateFiltersFromFields();
        }
    }

    protected function generateFiltersFromFields(): array
    {
        $filters = [];
        $autoConfig = $this->getConfigResolver()->getConfig()['controls']['autoFilters'] ?? true;
        $fieldsToInclude = is_array($autoConfig) ? $autoConfig : array_keys($this->fieldDefinitions);

        foreach ($this->fieldDefinitions as $fieldName => $definition) {
            if (is_array($fieldsToInclude) && !in_array($fieldName, $fieldsToInclude))
                continue;
            if (($definition['filterable'] ?? true) === false)
                continue;

            // Detect many‑to‑many relations (including morphToMany)
            $isManyToMany = false;
            if (isset($definition['relationship']['type']) && in_array($definition['relationship']['type'], ['belongsToMany', 'morphToMany'])) {
                $isManyToMany = true;
            }

            $fieldType = $definition['field_type'] ?? 'string';
            $filterType = $this->mapFieldTypeToFilterType($fieldType);

            // Override for many‑to‑many: treat as multi‑select
            if ($isManyToMany) {
                $filterType = 'select';
            }

            $operators = $this->getOperatorsForType($filterType);
            $defaultOperator = array_key_first($operators);

            $filter = [
                'field' => $fieldName,
                'label' => $definition['label'] ?? ucfirst($fieldName),
                'type' => $filterType,
                'operators' => $operators,
                'defaultOperator' => $defaultOperator,
                'multi' => $definition['multiSelect'] ?? false,
            ];

            // If many‑to‑many, force multi = true and replace operators with 'in'
            if ($isManyToMany) {
                $filter['multi'] = true;
                $filter['operators'] = ['in' => 'Has any of'];
                $filter['defaultOperator'] = 'in';
            }

            if ($filterType === 'select') {
                $filter['options'] = $this->getOptionsForField($fieldName, $definition);
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    protected function mapFieldTypeToFilterType(string $fieldType): string
    {
        return match ($fieldType) {
            'string', 'textarea', 'text' => 'string',
            'number', 'integer', 'float' => 'number',
            'datepicker', 'datetimepicker' => 'date',
            'timepicker', 'time' => 'time',
            'checkbox', 'boolcheckbox', 'radio' => 'boolean',
            'select' => 'select',
            default => 'string',
        };
    }

    protected function getOperatorsForType(string $type): array
    {
        return match ($type) {
            'string' => [
                'equals' => 'Equals',
                'contains' => 'Contains',
                'starts_with' => 'Starts with',
                'ends_with' => 'Ends with',
            ],
            'number' => [
                'equals' => 'Equals',
                'not_equals' => 'Not equals',
                'greater_than' => 'Greater than',
                'less_than' => 'Less than',
                'greater_than_or_equals' => '≥',
                'less_than_or_equals' => '≤',
                'between' => 'Between',
            ],
            'date' => [
                'equals' => 'Equals',
                'not_equals' => 'Not equals',
                'greater_than' => 'After',
                'less_than' => 'Before',
                'between' => 'Between',
                'today' => 'Today',
                'this_week' => 'This week',
                'this_month' => 'This month',
                'this_year' => 'This year',
                'last_week' => 'Last week',
                'last_month' => 'Last month',
                'last_year' => 'Last year',
                'last_7_days' => 'Last 7 days',
                'next_30_days' => 'Next 30 days',
                'this_quarter' => 'This quarter',
                'last_quarter' => 'Last quarter',
            ],
            'boolean' => [
                'equals' => 'Is',
            ],
            'select' => [
                'equals' => 'Is',
                'in' => 'Is one of',
            ],
            default => [
                'equals' => 'Equals',
                'contains' => 'Contains',
            ],
        };
    }

    protected function getOptionsForField(string $fieldName, array $definition): array
    {
        // 1. Explicit options from config
        if (isset($definition['options']['model'])) {
            $modelClass = $definition['options']['model'];
            $valueColumn = $definition['options']['column'] ?? 'name';
            $keyColumn = $definition['options']['key'] ?? 'id';
            if (class_exists($modelClass)) {
                return $modelClass::pluck($valueColumn, $keyColumn)->toArray();
            }
        } elseif (isset($definition['options']) && is_array($definition['options'])) {
            return $definition['options'];
        }

        // 2. Relationship (belongsTo, belongsToMany, morphToMany)
        if (isset($definition['relationship']['model'])) {
            $modelClass = $definition['relationship']['model'];
            $displayField = $definition['relationship']['display_field'] ?? 'name';
            if (class_exists($modelClass)) {
                return $modelClass::pluck($displayField, 'id')->toArray();
            }
        }

        return [];
    }

    protected function normalizeFilterConfig(): void
    {
        foreach ($this->filtersConfig as $index => &$filter) {
            if (!isset($filter['type'])) {
                $fieldDef = $this->fieldDefinitions[$filter['field']] ?? [];
                $filter['type'] = $this->mapFieldTypeToFilterType($fieldDef['field_type'] ?? 'string');
            }

            if ($filter['type'] === 'date_range') {
                $filter['type'] = 'date';
                $filter['operators'] = ['between' => 'Between'];
                $filter['defaultOperator'] = 'between';
            }

            if (!isset($filter['operators'])) {
                $filter['operators'] = $this->getOperatorsForType($filter['type']);
            }

            if (!isset($filter['defaultOperator'])) {
                $filter['defaultOperator'] = array_key_first($filter['operators']);
            }

            if ($filter['type'] === 'select' && !isset($filter['options'])) {
                $fieldDef = $this->fieldDefinitions[$filter['field']] ?? [];
                $filter['options'] = $this->getOptionsForField($filter['field'], $fieldDef);
            }

            $fieldDef = $this->fieldDefinitions[$filter['field']] ?? [];
            $filter['multi'] = $filter['multi'] ?? ($fieldDef['multiSelect'] ?? false);
        }
    }

    protected function getDefaultValueForType(string $type, string $operator, bool $multi = false)
    {
        if ($multi && $type === 'select') {
            return [];
        }
        if ($operator === 'between' && in_array($type, ['date', 'number'])) {
            return $type === 'date' ? ['start' => '', 'end' => ''] : ['min' => '', 'max' => ''];
        }
        return '';
    }

    public function updatedActiveFilters(): void
    {
        $this->emitFilters();
    }

    protected function emitFilters(): void
    {
        $filters = [];
        foreach ($this->filtersConfig as $index => $config) {
            $active = $this->activeFilters[$index] ?? null;
            if (!$active || $this->isFilterEmpty($active, $config)) {
                continue;
            }
            $filters[] = [
                'field' => $config['field'],
                'type' => $config['type'],
                'operator' => $active['operator'],
                'value' => $active['value'],
                'multi' => $config['multi'] ?? false,
            ];
        }
        $this->dispatch('filtersUpdated', filters: $filters);
    }

    protected function isFilterEmpty(array $active, array $filterConfig): bool
    {
        $type = $filterConfig['type'];
        $operator = $active['operator'];
        $value = $active['value'];
        $multi = $filterConfig['multi'] ?? false;

        if (
            $type === 'date' && in_array($operator, [
                'today',
                'this_week',
                'this_month',
                'this_year',
                'last_week',
                'last_month',
                'last_year',
                'last_7_days',
                'next_30_days',
                'this_quarter',
                'last_quarter'
            ])
        ) {
            return false;
        }

        if ($multi && $type === 'select') {
            return empty($value);
        }
        if (is_array($value)) {
            return empty(array_filter($value));
        }
        return $value === '' || $value === null;
    }

    public function clearFilters(): void
    {
        $this->dispatch('showAlert', [
            'type' => 'confirm',
            'title' => 'Clear All Filters',
            'message' => 'Are you sure you want to clear all active filters?',
            'confirmEvent' => 'confirmClearFilters',
        ]);
    }

    public function confirmClearFilters(): void
    {
        foreach ($this->filtersConfig as $index => $config) {
            $defaultOperator = $config['defaultOperator'];
            $this->activeFilters[$index] = [
                'operator' => $defaultOperator,
                'value' => $this->getDefaultValueForType($config['type'], $defaultOperator, $config['multi'] ?? false),
            ];
        }
        $this->emitFilters();
    }

    // ---------- Saved Filters (non‑edit) ----------
    protected function loadSavedFilters(): void
    {
        $this->savedFilters = SavedFilter::where('user_id', Auth::id())
            ->where('config_key', $this->configKey)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function saveCurrentFilters(string $name, bool $global = false): void
    {
        SavedFilter::create([
            'user_id' => Auth::id(),
            'config_key' => $this->configKey,
            'name' => $name,
            'filters' => $this->activeFilters,
            'is_global' => $global,
        ]);
        $this->loadSavedFilters();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Filter set saved']);
    }

    public function loadSavedFilter(int $id): void
    {
        $saved = SavedFilter::where('id', $id)
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                    ->orWhere('is_global', true);
            })
            ->firstOrFail();

        $this->activeFilters = $saved->filters;
        $this->emitFilters();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => "Filter '{$saved->name}' applied"]);
    }









    /**
     * When search input changes, fetch matching options.
     */
    public function updatedSearches($value, $index)
    {
        $filter = $this->filtersConfig[$index] ?? null;
        if (!$filter || ($filter['type'] ?? '') !== 'select') {
            return;
        }

        $options = $filter['options'] ?? [];
        if (empty($options)) {
            $this->searchResults[$index] = [];
            return;
        }

        $results = [];
        $search = strtolower(trim($value));
        if (strlen($search) >= 2) {
            foreach ($options as $id => $label) {
                if (stripos($label, $search) !== false) {
                    $results[$id] = $label;
                }
            }
        }
        $this->searchResults[$index] = $results;
    }

    /**
     * Select an option from search results.
     */
    public function selectOption($index, $id, $label)
    {
        $filter = $this->filtersConfig[$index] ?? null;
        if (!$filter)
            return;

        // Get current selected values
        $current = $this->activeFilters[$index]['value'] ?? [];
        if (!is_array($current)) {
            $current = [];
        }

        if (!in_array($id, $current)) {
            $current[] = $id;
            $this->activeFilters[$index]['value'] = $current;
            $this->selectedLabels[$index][$id] = $label;
        }

        // Clear search
        $this->searches[$index] = '';
        $this->searchResults[$index] = [];

        $this->emitFilters();
    }

    /**
     * Remove a selected option.
     */
    public function removeSelected($index, $id)
    {
        $filter = $this->filtersConfig[$index] ?? null;
        if (!$filter)
            return;

        $current = $this->activeFilters[$index]['value'] ?? [];
        if (is_array($current)) {
            $current = array_values(array_diff($current, [$id]));
            $this->activeFilters[$index]['value'] = $current;
            unset($this->selectedLabels[$index][$id]);
        }

        $this->emitFilters();
    }

    /**
     * Load initial selected labels when filter panel loads.
     */
    protected function loadFilterSelectedLabels(): void
    {
        foreach ($this->filtersConfig as $index => $filter) {
            if (($filter['type'] ?? '') !== 'select' || !($filter['multi'] ?? false)) {
                continue;
            }
            $selectedIds = $this->activeFilters[$index]['value'] ?? [];
            if (empty($selectedIds)) {
                $this->selectedLabels[$index] = [];
                continue;
            }
            $options = $filter['options'] ?? [];
            $labels = [];
            foreach ((array) $selectedIds as $id) {
                if (isset($options[$id])) {
                    $labels[$id] = $options[$id];
                }
            }
            $this->selectedLabels[$index] = $labels;
        }
    }









    public function render()
    {
        return view('qf::livewire.filter-panel', [
            'filtersConfig' => $this->filtersConfig,
            'activeFilters' => $this->activeFilters,
            'savedFilters' => $this->savedFilters,
        ]);
    }
}