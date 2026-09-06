<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;
use QuickerFaster\UILibrary\Concerns\ResolvesModels;

class DataTableDetail extends Component
{
    use ResolvesModels;

    public string $configKey;
    public int $recordId;

    protected $record;
    protected array $fieldDefinitions = [];
    protected array $fieldGroups = [];
    protected array $hiddenFields = [];
    public array $returnParams = [];
    public bool $inline = false;          // If true, no modal footer
    public ?string $crudType = null;

    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;

    public function mount(string $configKey, int $recordId, $inline = false, array $returnParams = [], ?string $crudType = null)
    {
        $this->configKey = $configKey;
        $this->recordId = $recordId;
        $this->returnParams = $returnParams;
        $this->inline = $inline;
        $this->crudType = $crudType ?? ($this->getConfigResolver()->getConfig()['crudType'] ?? 'modal');

        $this->loadConfiguration();
        $this->loadRecord();

        // Pass the already-resolved model to avoid a second findOrFail in AuthorizationService
        app(AuthorizationService::class)->authorizeView(auth()->user(), $this->record, $this->getConfigResolver()->getModel());

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
        $relationConfigs = $this->getConfigResolver()->getRelations();

        $this->record = $this->resolveModelOrFail($modelClass, $this->recordId);

        if (!empty($relationConfigs)) {
            // Only eager-load relations that actually exist on the model.
            // This prevents crashes when a config defines a relation (e.g. a
            // module-specific relation like HR's 'profile') that the underlying
            // model class doesn't implement.
            $validRelations = array_filter($relationConfigs, function ($config, $name) {
                return method_exists($this->record, $name);
            }, ARRAY_FILTER_USE_BOTH);
            if (!empty($validRelations)) {
                $this->record->load(array_keys($validRelations));
            }
        }
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