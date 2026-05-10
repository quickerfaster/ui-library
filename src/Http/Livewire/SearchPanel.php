<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

class SearchPanel extends Component
{
    public string $configKey;
    public string $searchTerm = '';
    public array $selectedColumns = [];
    public array $allColumns = [];
    public bool $exactMatch = false;

    protected ?ConfigResolver $configResolver = null;

    public function mount(string $configKey, string $initialSearch = '', array $initialColumns = [], bool $initialExactMatch = false): void
    {
        $this->configKey = $configKey;
        $this->loadColumns();

        $this->searchTerm = $initialSearch;
        $this->exactMatch = $initialExactMatch;

        // Restore from session if available (overrides passed values)
        $savedColumns = session()->get("search_columns.{$this->configKey}");
        $savedTerm = session()->get("search_term.{$this->configKey}");
        $savedExact = session()->get("search_exactmatch.{$this->configKey}");

        if (!empty($savedColumns)) {
            $this->selectedColumns = array_intersect($savedColumns, array_keys($this->allColumns));
        } elseif (!empty($initialColumns)) {
            $this->selectedColumns = array_intersect($initialColumns, array_keys($this->allColumns));
        } else {
            $this->selectedColumns = array_slice(array_keys($this->allColumns), 0, 2);  // 2 is also the datatble
        }

        if ($savedTerm !== null) {
            $this->searchTerm = $savedTerm;
        }
        if ($savedExact !== null) {
            $this->exactMatch = $savedExact;
        }
    }

    protected function loadColumns(): void
    {
        $resolver = $this->getConfigResolver();
        $hiddenOnTable = $resolver->getHiddenFields()['onTable'] ?? [];
        $fieldDefs = $resolver->getFieldDefinitions();

        foreach ($fieldDefs as $field => $def) {
            if (in_array($field, $hiddenOnTable)) {
                continue;
            }
            if (isset($def['relationship'])) {
                continue;
            }
            // Respect searchable flag (suggestion #1)
            if (($def['searchable'] ?? true) === false) {
                continue;
            }
            $this->allColumns[$field] = $def['label'] ?? ucfirst($field);
        }
    }

    // Live update handlers
    public function updatedSearchTerm(): void
    {
        $this->emitSearch();
    }

    public function updatedSelectedColumns(): void
    {
        // Enforce max columns (suggestion #4)
        if (count($this->selectedColumns) > 5) {
            $this->selectedColumns = array_slice($this->selectedColumns, 0, 5);
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Maximum 5 columns allowed for search.']);
        }
        $this->emitSearch();
    }

    public function updatedExactMatch(): void
    {
        $this->emitSearch();
    }

    protected function emitSearch(): void
    {
        // Save to session
        session()->put("search_columns.{$this->configKey}", $this->selectedColumns);
        session()->put("search_term.{$this->configKey}", $this->searchTerm);
        session()->put("search_exactmatch.{$this->configKey}", $this->exactMatch);

        // Dispatch to DataTable
        $this->dispatch('searchApplied', 
            search: $this->searchTerm, 
            columns: $this->selectedColumns,
            exactMatch: $this->exactMatch
        );
    }

    public function resetSearch(): void
    {
        $this->searchTerm = '';
        $this->selectedColumns = array_slice(array_keys($this->allColumns), 0, 2); // 2 is also the datatble selection
        $this->exactMatch = false;

        // Clear session
        session()->forget("search_columns.{$this->configKey}");
        session()->forget("search_term.{$this->configKey}");
        session()->forget("search_exactmatch.{$this->configKey}");

        $this->emitSearch();
        $this->dispatch('closeDrawer');
    }

    protected function getConfigResolver(): ConfigResolver
    {
        if (!$this->configResolver) {
            $this->configResolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        }
        return $this->configResolver;
    }

    public function render()
    {
        return view('qf::livewire.search-panel', [
            'allColumns' => $this->allColumns,
            'selectedColumns' => $this->selectedColumns,
            'searchTerm' => $this->searchTerm,
            'exactMatch' => $this->exactMatch,
        ]);
    }
}