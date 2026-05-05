<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Custom;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use  QuickerFaster\UILibrary\Services\Search\SearchEngine;

class SearchableEmployeeDropdown extends Component
{
    public string $configKey;
    public ?int $selectedId = null;
    public array $returnParams = [];
    public string $search = '';
    public array $results = [];

    public function mount(string $configKey, ?int $selectedId = null, array $returnParams = []): void
    {
        $this->configKey = $configKey;
        $this->selectedId = $selectedId;
        $this->returnParams = $returnParams;
    }

    public function updatedSearch(): void
    {
        if (strlen($this->search) < 2) {
            $this->results = [];
            return;
        }

        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $modelClass = $resolver->getModel();
        
        // Get searchable fields from config (same as DataTable)
        $searchableFields = $this->getSearchableFields($resolver);
        
        if (empty($searchableFields)) {
            $this->results = [];
            return;
        }
        

$records = SearchEngine::get(
    $modelClass,
    $this->search,
    array_slice($searchableFields, 0, 2),
    20
);
        
        $this->results = [];
        foreach ($records as $record) {
            $this->results[] = [
                'id' => $record->id,
                'label' => $this->getEmployeeLabel($record, $resolver),
            ];
        }
    }

    public function selectEmployee(int $id): void
    {
        $this->selectedId = $id;
        $this->search = '';
        $this->results = [];
        $this->dispatch('employeeSelected', id: $id);
    }

    protected function getSearchableFields(ConfigResolver $resolver): array
    {
        $hiddenOnTable = $resolver->getHiddenFields()['onTable'] ?? [];
        $searchable = [];
        foreach ($resolver->getFieldDefinitions() as $field => $def) {
            if (!in_array($field, $hiddenOnTable) && !isset($def['relationship']) && ($def['searchable'] ?? true) !== false) {
                $searchable[] = $field;
            }
        }
        // Prioritize name fields
        usort($searchable, function ($a, $b) {
            $aPriority = preg_match('/name|first|last/i', $a) ? 0 : 1;
            $bPriority = preg_match('/name|first|last/i', $b) ? 0 : 1;
            return $aPriority <=> $bPriority;
        });
        return $searchable;
    }

    protected function getEmployeeLabel($record, ConfigResolver $resolver): string
    {
        // Try to build a readable name
        $defs = $resolver->getFieldDefinitions();
        $firstName = $record->first_name ?? $record->name ?? null;
        $lastName = $record->last_name ?? null;
        $employeeNumber = $record->employee_number ?? null;
        
        if ($firstName && $lastName) {
            $label = $firstName . ' ' . $lastName;
        } elseif ($firstName) {
            $label = $firstName;
        } else {
            $label = $record->id;
        }
        
        if ($employeeNumber) {
            $label .= " (#{$employeeNumber})";
        }
        
        return $label;
    }

    public function render()
    {
        return view('qf::livewire.custom.searchable-employee-dropdown');
    }
}