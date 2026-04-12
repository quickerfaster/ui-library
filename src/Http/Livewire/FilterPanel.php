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


    protected ?ConfigResolver $configResolver = null;

    public function mount(string $configKey): void
    {
        $this->configKey = $configKey;
        $this->loadConfig();
        $this->normalizeFilterConfig();
        $this->initializeFilters();
        $this->loadSavedFilters();

        // Remove any filter indices that no longer exist in the config
        $this->activeFilters = array_intersect_key($this->activeFilters, $this->filtersConfig);

        // Re‑initialize each remaining filter to ensure operator and value are valid
        foreach ($this->activeFilters as $index => $filter) {
            $config = $this->filtersConfig[$index] ?? null;
            if (!$config) continue;

            if (!isset($filter['operator']) || !in_array($filter['operator'], array_keys($config['operators']))) {
                $filter['operator'] = $config['defaultOperator'];
            }
            if (!isset($filter['value'])) {
                $filter['value'] = $this->getDefaultValueForType($config['type'], $filter['operator'], $config['multi'] ?? false);
            }
            $this->activeFilters[$index] = $filter;
        }
    }





public function showSaveFilterModal()
{
    $this->filterName = '';
    $this->filterIsGlobal = false;
    $this->dispatch('openSaveFilterModal');
}

public function saveFilter()
{
    $this->validate(['filterName' => 'required|string|max:255']);
    $this->saveCurrentFilters($this->filterName, $this->filterIsGlobal);
    $this->dispatch('closeSaveFilterModal');
    $this->filterName = '';
    $this->filterIsGlobal = false;
}


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
            if (is_array($fieldsToInclude) && !in_array($fieldName, $fieldsToInclude)) continue;
            if (($definition['filterable'] ?? true) === false) continue;

            $fieldType = $definition['field_type'] ?? 'string';
            $filterType = $this->mapFieldTypeToFilterType($fieldType);
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
            'number', 'integer', 'float'  => 'number',
            'datepicker', 'datetimepicker' => 'date',
            'checkbox', 'boolcheckbox', 'radio' => 'boolean',
            'select' => 'select',
            default => 'string',
        };
    }

    protected function getOperatorsForType(string $type): array
    {
        return match ($type) {
            'string' => [
                'equals'       => 'Equals',
                'contains'     => 'Contains',
                'starts_with'  => 'Starts with',
                'ends_with'    => 'Ends with',
            ],
            'number' => [
                'equals'                  => 'Equals',
                'not_equals'              => 'Not equals',
                'greater_than'            => 'Greater than',
                'less_than'               => 'Less than',
                'greater_than_or_equals'  => '≥',
                'less_than_or_equals'     => '≤',
                'between'                 => 'Between',
            ],
            'date' => [
                'equals'        => 'Equals',
                'not_equals'    => 'Not equals',
                'greater_than'  => 'After',
                'less_than'     => 'Before',
                'between'       => 'Between',
                'today'         => 'Today',
                'this_week'     => 'This week',
                'this_month'    => 'This month',
                'this_year'     => 'This year',
                'last_week'     => 'Last week',
                'last_month'    => 'Last month',
                'last_year'     => 'Last year',
                'last_7_days'   => 'Last 7 days',
                'next_30_days'  => 'Next 30 days',
                'this_quarter'  => 'This quarter',
                'last_quarter'  => 'Last quarter',
            ],
            'boolean' => [
                'equals' => 'Is',
            ],
            'select' => [
                'equals' => 'Is',
                'in'     => 'Is one of',
            ],
            default => [
                'equals'   => 'Equals',
                'contains' => 'Contains',
            ],
        };
    }

    protected function getOptionsForField(string $fieldName, array $definition): array
    {
        if (isset($definition['options']['model'])) {
            $modelClass = $definition['options']['model'];
            $valueColumn = $definition['options']['column'] ?? 'name';
            $keyColumn = $definition['options']['key'] ?? 'id';
            if (class_exists($modelClass)) {
                return $modelClass::pluck($valueColumn, $keyColumn)->toArray();
            }
        } elseif (isset($definition['options']) && is_array($definition['options'])) {
            return $definition['options'];
        } elseif (isset($definition['relationship']['model'])) {
            $modelClass = $definition['relationship']['model'];
            $displayField = $definition['relationship']['display_field'] ?? 'name';
            return $modelClass::pluck($displayField, 'id')->toArray();
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

            // Multi-select support
            $fieldDef = $this->fieldDefinitions[$filter['field']] ?? [];
            $filter['multi'] = $filter['multi'] ?? ($fieldDef['multiSelect'] ?? false);
        }
    }

    protected function initializeFilters(): void
    {
        foreach ($this->filtersConfig as $index => $filter) {
            $defaultOperator = $filter['defaultOperator'];
            $this->activeFilters[$index] = [
                'operator' => $defaultOperator,
                'value'    => $this->getDefaultValueForType($filter['type'], $defaultOperator, $filter['multi'] ?? false),
            ];
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
                'field'    => $config['field'],
                'type'     => $config['type'],
                'operator' => $active['operator'],
                'value'    => $active['value'],
                'multi'    => $config['multi'] ?? false,
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

        if ($type === 'date' && in_array($operator, [
            'today','this_week','this_month','this_year','last_week','last_month','last_year',
            'last_7_days','next_30_days','this_quarter','last_quarter'
        ])) {
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
        $this->initializeFilters();
        $this->emitFilters();
    }

    // ---------- Saved Filters ----------
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
            'user_id'    => Auth::id(),
            'config_key' => $this->configKey,
            'name'       => $name,
            'filters'    => $this->activeFilters,
            'is_global'  => $global,
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

    public function deleteSavedFilter(int $id): void
    {
        SavedFilter::where('id', $id)->where('user_id', Auth::id())->delete();
        $this->loadSavedFilters();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Filter set deleted']);
    }

    public function render()
    {
        return view('qf::livewire.filter-panel', [
            'filtersConfig' => $this->filtersConfig,
            'activeFilters' => $this->activeFilters,
            'savedFilters'  => $this->savedFilters,
        ]);
    }
}