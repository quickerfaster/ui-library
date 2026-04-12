<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;

class DataTableDetail extends Component
{
    public string $configKey;
    public int $recordId;

    protected $record;
    protected array $fieldDefinitions = [];
    protected array $fieldGroups = [];
    protected array $hiddenFields = [];
public array $returnParams = [];
    public bool $inline = false;          // If true, no modal footer

    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;

    public function mount(string $configKey, int $recordId, $inline = false, array $returnParams = [])
    {
        $this->configKey = $configKey;
        $this->recordId = $recordId;
        $this->returnParams = $returnParams; 
        $this->inline = $inline;

        $this->loadConfiguration();
        $this->loadRecord();
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

    protected function loadConfiguration(): void
    {
        $resolver = $this->getConfigResolver();
        $this->fieldDefinitions = $resolver->getFieldDefinitions();
        $this->fieldGroups = $resolver->getFieldGroups();
        $this->hiddenFields = $resolver->getHiddenFields();
    }

    protected function loadRecord(): void
    {
        $modelClass = $this->getConfigResolver()->getModel();
        $relations = array_keys($this->getConfigResolver()->getRelations());
        $this->record = $modelClass::with($relations)->findOrFail($this->recordId);
    }

    public function getField(string $name)
    {
        return $this->getFieldFactory()->make($name, $this->fieldDefinitions[$name]);
    }

    public function render()
    {
        return view('qf::livewire.data-tables.data-table-detail', [
            'record' => $this->record,
            'fieldGroups' => $this->fieldGroups,
            'fieldDefinitions' => $this->fieldDefinitions,
            'hiddenFields' => $this->hiddenFields,
        ]);
    }
}