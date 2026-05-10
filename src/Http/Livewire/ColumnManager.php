<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

class ColumnManager extends Component
{
    public string $configKey;
    public array $visibleColumns = [];
    public array $allColumns = [];
    public array $columns = [];

    protected $listeners = [
        'refreshColumnManager' => 'loadData',
    ];

    public function mount(string $configKey)
    {
        $this->configKey = $configKey;
        $this->loadData();
    }

    public function loadData()
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $fieldDefs = $resolver->getFieldDefinitions();
        $hiddenOnTable = $resolver->getHiddenFields()['onTable'] ?? [];
        $this->columns = array_diff_key($fieldDefs, array_flip($hiddenOnTable));
        $this->allColumns = array_keys($this->columns);
        // Load visible columns from session (same logic as DataTable)
        $this->visibleColumns = $this->loadVisibleColumnsFromSession();
    }

    protected function loadVisibleColumnsFromSession(): array
    {
        $sessionKey = "visible_columns_{$this->configKey}";
        $saved = session()->get($sessionKey, []);
        if (!empty($saved)) {
            // Ensure saved columns exist
            return array_values(array_intersect($saved, $this->allColumns));
        }
        // Default: first 6 columns
        return $this->getDefaultVisibleColumns();
    }

    protected function saveVisibleColumnsToSession(array $columns): void
    {
        session()->put("visible_columns_{$this->configKey}", $columns);
    }

    public function toggleColumn($column)
    {
        if (!in_array($column, $this->allColumns)) {
            return;
        }
        if (in_array($column, $this->visibleColumns)) {
            $this->visibleColumns = array_values(array_diff($this->visibleColumns, [$column]));
        } else {
            $this->visibleColumns[] = $column;
            // Keep order consistent with allColumns
            $this->visibleColumns = array_values(array_intersect($this->allColumns, $this->visibleColumns));
        }
        $this->saveVisibleColumnsToSession($this->visibleColumns);
        // Emit event to DataTable to refresh its columns
        $this->dispatch('columnsUpdated', visibleColumns: $this->visibleColumns);
    }

    public function resetColumns()
    {
        $defaultColumns = $this->getDefaultVisibleColumns();
        $this->visibleColumns = $defaultColumns;
        $this->saveVisibleColumnsToSession($this->visibleColumns);

        $this->dispatch('columnsUpdated', visibleColumns: $this->visibleColumns);
    }

    public function isResetVisible(): bool
    {
        $defaultColumns = $this->getDefaultVisibleColumns();
        return $this->visibleColumns != $defaultColumns;
    }




    /**
     * Returns the default visible columns based on config or fallback.
     * Does not consider session – pure config default.
     *
     * @return array
     */
    protected function getDefaultVisibleColumns(): array
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);

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




    public function render()
    {
        return view('qf::livewire.column-manager');
    }
}