<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Wizards;

use App\Modules\Admin\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\HasAutoGenerateFields;

class WizardForm extends Component
{
    use WithFileUploads;
    use HasAutoGenerateFields;

    public string $configKey;
    public array $presetData = [];
    public int $stepIndex;
    public ?int $recordId = null;
    public bool $isEditMode = false;
    public array $stepGroups = [];

    // Internal state
    public array $fields = [];
    public array $fieldDefinitions = [];
    public array $fieldGroups = [];
    public array $hiddenFields = [];
    public array $relations = [];
    public string $modelClass;

    // For searchable selects
    public array $searches = [];
    public array $searchResults = [];
    public array $selectedLabels = [];

    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;

    public $listeners = [
        'saveStepForm' => 'save',
    ];

public function mount(string $configKey, array $presetData = [], int $stepIndex = 0, ?int $recordId = null): void
{
    $this->configKey = $configKey;
    $this->presetData = $presetData;
    $this->stepIndex = $stepIndex;
    $this->recordId = $recordId;

    // Single listener for all step save events
    $this->listeners['saveStepForm'] = 'handleSaveStepForm';

    $this->loadConfiguration();
    $this->initializeFields();
    $this->applyPresetData();

    if ($this->recordId) {
        $this->isEditMode = true;
        $this->loadRecord();
    }
}

/**
 * Handle the saveStepForm event – only proceed if the step index matches.
 */
public function handleSaveStepForm($stepIndex): void
{
    if ($stepIndex == $this->stepIndex) {
        $this->save();
    }
}



    protected function loadRecord(): void
    {
        $record = $this->modelClass::with(array_keys($this->relations))->find($this->recordId);

        if (!$record) {
            abort(404, 'Record not found');
        }

        foreach ($this->fieldDefinitions as $field => $definition) {
            if (isset($definition['relationship'])) {
                $rel = $definition['relationship'];
                $dynamicProp = $rel['dynamic_property'] ?? $field;
                if ($record->$dynamicProp) {
                    if (in_array($rel['type'], ['belongsToMany', 'hasMany', 'morphMany'])) {
                        $this->fields[$field] = $record->$dynamicProp->pluck('id')->toArray();
                    } else {
                        $this->fields[$field] = $record->$dynamicProp->id ?? null;
                    }
                } else {
                    $this->fields[$field] = null;
                }
            } else {
                $this->fields[$field] = $record->$field;
            }
        }

        // Convert comma-separated strings to arrays for multi-select fields
        foreach ($this->fieldDefinitions as $field => $definition) {
            if (($definition['multiSelect'] ?? false) && !isset($definition['relationship']) && isset($this->fields[$field]) && is_string($this->fields[$field])) {
                $this->fields[$field] = array_filter(explode(',', $this->fields[$field]));
            }
        }

        // Update selectedLabels for searchable selects
        foreach ($this->fieldDefinitions as $field => $definition) {
            if (($definition['field_type'] ?? '') === 'livewire-searchable-select') {
                $fieldObj = $this->getField($field);
                $this->selectedLabels[$field] = $fieldObj->getInitialOptions($this->fields[$field] ?? null);
            }
        }
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
        $this->modelClass = $resolver->getModel();
        $this->fieldDefinitions = $resolver->getFieldDefinitions();
        $this->fieldGroups = $resolver->getFieldGroups();
        $this->hiddenFields = $resolver->getHiddenFields();
        $this->relations = $resolver->getRelations();
    }

    protected function initializeFields(): void
    {
        foreach ($this->fieldDefinitions as $field => $definition) {
            $default = $definition['default'] ?? null;
            if (isset($definition['multiSelect']) && $definition['multiSelect']) {
                $this->fields[$field] = [];
            } elseif (isset($definition['field_type']) && $definition['field_type'] === 'boolcheckbox') {
                $this->fields[$field] = $default ?? false;
            } elseif (isset($definition['field_type']) && $definition['field_type'] === 'boolradio') {
                $this->fields[$field] = $default ?? null;
            } else {
                $this->fields[$field] = $default;
            }
        }

        // Initialise selectedLabels for searchable selects
        foreach ($this->fieldDefinitions as $field => $definition) {
            if (($definition['field_type'] ?? '') === 'livewire-searchable-select') {
                $fieldObj = $this->getField($field);
                $this->selectedLabels[$field] = $fieldObj->getInitialOptions($this->fields[$field] ?? null);
            }
        }
    }

    protected function applyPresetData(): void
    {
        foreach ($this->presetData as $field => $value) {
            if (array_key_exists($field, $this->fields)) {
                $this->fields[$field] = $value;

                // Update selectedLabels for searchable selects
                if (($this->fieldDefinitions[$field]['field_type'] ?? '') === 'livewire-searchable-select') {
                    $fieldObj = $this->getField($field);
                    $this->selectedLabels[$field] = $fieldObj->getInitialOptions($value);
                }
            }
        }
    }

    // ---------- Searchable Select Logic ----------
    public function updatedSearches($value, $field)
    {
        $definition = $this->fieldDefinitions[$field] ?? null;
        if (!$definition) return;

        $results = [];

        if (isset($definition['relationship'])) {
            $rel = $definition['relationship'];
            $model = $rel['model'];
            $displayField = $rel['display_field'] ?? 'name';
            $searchFields = $rel['search_fields'] ?? [$displayField];

            $query = $model::query();
            $query->where(function ($q) use ($searchFields, $value) {
                foreach ($searchFields as $sf) {
                    $q->orWhere($sf, 'LIKE', '%' . $value . '%');
                }
            });
            $items = $query->limit(50)->get();
            foreach ($items as $item) {
                $results[$item->id] = $item->$displayField;
            }
        } elseif (isset($definition['options'])) {
            $options = $definition['options'];
            foreach ($options as $id => $label) {
                if (stripos($label, $value) !== false) {
                    $results[$id] = $label;
                }
            }
        }

        $this->searchResults[$field] = $results;
    }

    public function selectOption($field, $id, $label)
    {
        $multiple = $this->fieldDefinitions[$field]['multiSelect'] ?? false;

        if ($multiple) {
            $current = $this->fields[$field] ?? [];
            if (!in_array($id, $current)) {
                $current[] = $id;
                $this->fields[$field] = $current;
                $this->selectedLabels[$field][$id] = $label;
            }
        } else {
            $this->fields[$field] = $id;
            $this->selectedLabels[$field] = [$id => $label];
            $this->searches[$field] = '';
            $this->searchResults[$field] = [];
        }
    }

    public function removeSelected($field, $id)
    {
        $multiple = $this->fieldDefinitions[$field]['multiSelect'] ?? false;

        if ($multiple) {
            $current = $this->fields[$field] ?? [];
            $this->fields[$field] = array_values(array_diff($current, [$id]));
            unset($this->selectedLabels[$field][$id]);
        } else {
            $this->fields[$field] = null;
            $this->selectedLabels[$field] = [];
        }
    }

    // ---------- Field Helpers ----------
    public function getField(string $name): FieldType
    {
        return $this->getFieldFactory()->make($name, $this->fieldDefinitions[$name]);
    }

    public function isFieldHidden(string $field, string $context): bool
    {
        return in_array($field, $this->hiddenFields[$context] ?? []);
    }

    // ---------- Save ----------
    public function save(): void
    {
        $this->validateFields();

        if ($this->getErrorBag()->any()) {
            return;
        }

        DB::transaction(function () {
            if ($this->isEditMode) {
                $record = $this->modelClass::findOrFail($this->recordId);
            } else {
                $record = new $this->modelClass();
            }

            $formType = $this->isEditMode ? 'onEditForm' : 'onNewForm';
            $allowedFields = array_diff(
                array_keys($this->fieldDefinitions),
                $this->hiddenFields[$formType] ?? [],
                $this->hiddenFields['onQuery'] ?? []
            );
            $data = array_intersect_key($this->fields, array_flip($allowedFields));

            $data = $this->handleFileUploads($record, $data);

            foreach ($this->fieldDefinitions as $field => $def) {
                if (isset($def['multiSelect']) && !isset($def['relationship']) && isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }

            if ($this->isEditMode) {
                $record->update($data);
            } else {
                $record = $record->create($data);
                $this->recordId = $record->id;
                $this->isEditMode = true;
            }



            // Add audit trait to ActivityLogger
            if ($this->isEditMode) {
                // Capture old values before update
                $original = $record->getOriginal();
                $record->update($data);
                $changed = $record->getChanges();
                $old = array_intersect_key($original, $changed);
                $new = array_intersect_key($data, $changed);
                ActivityLogger::updated($this->configKey, $record, $old, $new);
            } else {
                $record = $record->create($data);
                $this->recordId = $record->id;
                $this->isEditMode = true;
                ActivityLogger::created($this->configKey, $record, $data);
            }




            $this->syncRelationships($record);

            $this->dispatch('stepFormSaved', $record->id, $this->stepIndex);
        });
    }

protected function validateFields(): void
{
    $rules = [];

    foreach ($this->fieldDefinitions as $field => $def) {
        if (isset($def['validation']) && !$this->isFieldHidden($field, $this->isEditMode ? 'onEditForm' : 'onNewForm')) {
            $rule = $def['validation'];

            // If we are editing and the rule contains 'unique', append the record ID to ignore
            if ($this->isEditMode && $this->recordId && str_contains($rule, 'unique')) {
                // Split the rule string (could be pipe-separated)
                $parts = explode('|', $rule);
                foreach ($parts as &$part) {
                    if (str_starts_with($part, 'unique:')) {
                        // unique:table,column,except,id
                        // If the rule doesn't already have an except clause, add it
                        if (!str_contains($part, ',' . $this->recordId)) {
                            $part .= ',' . $this->recordId . ',id';
                        }
                    }
                }
                $rule = implode('|', $parts);
            }

            $rules[$field] = $rule;
        }
    }

    $validator = Validator::make($this->fields, $rules);
    if ($validator->fails()) {
        $this->resetErrorBag();
        foreach ($validator->errors()->messages() as $key => $errors) {
            foreach ($errors as $error) {
                $this->addError($key, $error);
            }
        }
    }
}

    protected function handleFileUploads($record, array $data): array
    {
        foreach ($this->fieldDefinitions as $field => $def) {
            if (isset($def['field_type']) && in_array($def['field_type'], ['file', 'image'])) {
                if (isset($this->fields[$field]) && is_object($this->fields[$field])) {
                    $path = $this->fields[$field]->store('uploads/' . $this->configKey, 'public');
                    $data[$field] = $path;
                }
            }
        }
        return $data;
    }


    protected function syncRelationships($record): void
{
    foreach ($this->fieldDefinitions as $field => $def) {
        if (!isset($def['relationship'])) {
            continue;
        }
        $rel = $def['relationship'];
        $type = $rel['type'] ?? 'belongsTo';
        $dynamicProp = $rel['dynamic_property'] ?? $field;

        // belongsTo is handled via foreign key in main data
        if ($type === 'belongsTo') {
            continue;
        }

        $ids = $this->fields[$field] ?? [];
        // Clean up: remove nulls/empties and duplicates
        $ids = array_unique(array_filter($ids));

        if ($type === 'belongsToMany') {
            $record->$dynamicProp()->sync($ids);
        } elseif (in_array($type, ['hasMany', 'morphMany'])) {
            // Must have foreign_key defined in config
            if (!isset($rel['foreign_key'])) {
                continue;
            }

            $relatedClass = $rel['model'];
            $foreignKey = $rel['foreign_key'];

            // Get the primary key name of the related model (usually 'id')
            $relatedInstance = new $relatedClass;
            $localKey = $relatedInstance->getKeyName();

            if (empty($ids)) {
                // No selected: disassociate all currently linked
                $relatedClass::where($foreignKey, $record->id)
                    ->update([$foreignKey => null]);
            } else {
                // Disassociate those not in the selected list
                $relatedClass::where($foreignKey, $record->id)
                    ->whereNotIn($localKey, $ids)
                    ->update([$foreignKey => null]);

                // Associate the selected ones
                $relatedClass::whereIn($localKey, $ids)
                    ->update([$foreignKey => $record->id]);
            }
        }
    }
}

    // ---------- Render ----------
public function render()
{
    // Filter groups based on step configuration
    $displayGroups = [];
    if (!empty($this->stepGroups)) {
        foreach ($this->stepGroups as $groupKey) {
            if (isset($this->fieldGroups[$groupKey])) {
                $displayGroups[$groupKey] = $this->fieldGroups[$groupKey];
            }
        }
    } else {
        // Fallback: show all groups
        $displayGroups = $this->fieldGroups;
    }

    return view('qf::livewire.wizards.wizard-form', [
        'displayGroups' => $displayGroups,
        'fieldDefinitions' => $this->fieldDefinitions,
        'hiddenFields' => $this->hiddenFields,
    ]);
}

}