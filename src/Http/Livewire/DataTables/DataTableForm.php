<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use App\Modules\Admin\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use Livewire\Attributes\On;


use QuickerFaster\UILibrary\Services\Validation\DataTableFormValidationService;
use QuickerFaster\UILibrary\Traits\HasAutoGenerateFields;

class DataTableForm extends Component
{
    use WithFileUploads;
    use HasAutoGenerateFields;

    // Public properties (config‑driven)
    public string $configKey;
    public ?int $recordId = null;
    public bool $inline = false;          // If true, no modal footer
    public ?string $modalId = null;       // For closing the modal

    // Internal state
    public array $fields = [];
    public bool $isEditMode = false;

    // Configuration data (loaded once)
    public array $fieldDefinitions = [];
    public array $fieldGroups = [];
    public array $hiddenFields = [];
    public array $columns = [];
    public array $relations = [];
    public string $modelClass;

    // Add these properties
    public array $searches = [];          // Holds search queries per field
    public array $searchResults = [];     // Holds search results per field
    public array $selectedLabels = [];    // Holds labels of selected options for display
    public array $returnParams = [];

    public array $allowedGroups = []; // The group of form field on the DatTable form

    public array $prefilledData = [];
    public array $croppedImages = [];


    public array $morphSelectedType = [];   // keyed by field name
    public array $morphSelectedId = [];     // keyed by field name
    public array $morphEntityOptions = [];  // keyed by field name


    // Lazy-loaded services
    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;

    protected $listeners = [
        'openAddModal' => 'handleOpenAddModal',
        'openEditModal' => 'handleOpenEditModal',
        'refreshFields' => 'refreshFields',
        'resetForm' => 'resetFields',

    ];

    public function mount(
        string $configKey,
        ?int $recordId = null,
        bool $inline = false,
        ?string $modalId = null,
        array $returnParams = [],
        array $allowedGroups = [],
        array $prefilledData = []   // new parameter
    ): void {
        $this->configKey = $configKey;
        $this->recordId = $recordId;
        $this->inline = $inline;
        $this->modalId = $modalId;
        $this->returnParams = $returnParams;
        $this->allowedGroups = $allowedGroups;
        $this->prefilledData = $prefilledData;

        $this->loadConfiguration();
        $this->initializeFields();

        if ($this->recordId) {
            $this->isEditMode = true;
            $this->loadRecord();
        } else {
            // Apply prefilled data only for new records
            $this->applyPrefilledData();
        }
    }













    // ---------- Initialization Helpers ----------
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
        $this->columns = array_keys($this->fieldDefinitions);

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


            if (($definition['field_type'] ?? '') === 'morph_to_select') {
                $this->fields[$field] = ['type' => null, 'id' => null];
                $this->morphSelectedType[$field] = null;
                $this->morphSelectedId[$field] = null;
                $this->morphEntityOptions[$field] = [];
                continue;
            }


            // Initialise selectedLabels for searchable selects (for new records)
            /*foreach ($this->fieldDefinitions as $field => $definition) {
                if (($definition['field_type'] ?? '') === 'livewire-searchable-select') {
                    $fieldObj = $this->getField($field);
                    $this->selectedLabels[$field] = $fieldObj->getInitialOptions($this->fields[$field] ?? null);
                }
            }*/

        }

    }



    /**
     * Set the morph type and clear the selected ID when type changes.
     */
    public function setMorphType(string $fieldName, string $type): void
    {
        \Log::info('setMorphType called', ['field' => $fieldName, 'type' => $type, 'before' => $this->fields[$fieldName] ?? null]);
        if (!isset($this->fields[$fieldName]) || !is_array($this->fields[$fieldName])) {
            $this->fields[$fieldName] = ['type' => null, 'id' => null];
        }
        $this->fields[$fieldName]['type'] = $type;
        $this->fields[$fieldName]['id'] = null;
        \Log::info('after update', ['after' => $this->fields[$fieldName]]);
    }





    public function updatedMorphSelectedType($value, $fieldName): void
    {
        // When type changes, reset the ID and reload entity options
        $this->morphSelectedId[$fieldName] = null;
        $this->loadMorphEntityOptions($fieldName);

        // Update the compound field value
        $this->fields[$fieldName] = [
            'type' => $value,
            'id' => null,
        ];
    }

    public function updatedMorphSelectedId($value, $fieldName)
    {
        // Update the compound field value
        $this->fields[$fieldName] = [
            'type' => $this->morphSelectedType[$fieldName] ?? null,
            'id' => $value,
        ];
    }

    protected function loadMorphEntityOptions(string $fieldName): void
    {
        $def = $this->fieldDefinitions[$fieldName] ?? [];
        $morphMap = $def['morph_map'] ?? [];
        $displayField = $def['display_field'] ?? 'name';
        $selectedType = $this->morphSelectedType[$fieldName] ?? null;

        $this->morphEntityOptions[$fieldName] = [];
        if ($selectedType && isset($morphMap[$selectedType])) {
            $modelClass = $morphMap[$selectedType];
            $this->morphEntityOptions[$fieldName] = $modelClass::pluck($displayField, 'id')->toArray();
        }



    }
















    /**
     * Merge prefilled data into the form fields and set up any extra state
     * (e.g., selected labels for livewire-searchable-select).
     */
    protected function applyPrefilledData(): void
    {
        if (empty($this->prefilledData)) {
            return;
        }

        // Merge prefilled data over the default field values
        foreach ($this->prefilledData as $field => $value) {
            if (!array_key_exists($field, $this->fieldDefinitions)) {
                continue;
            }

            $def = $this->fieldDefinitions[$field];
            $fieldType = $def['field_type'] ?? 'string';

            // Handle multi-select values (ensure they are arrays)
            if (($def['multiSelect'] ?? false) && !is_array($value)) {
                $value = $value === null ? [] : explode(',', (string) $value);
            }

            // For checkbox fields, convert to boolean
            if ($fieldType === 'checkbox' && !($def['multiSelect'] ?? false)) {
                $value = (bool) $value;
            }

            $this->fields[$field] = $value;

            // Special handling for livewire-searchable-select: pre-populate selected labels
            if ($fieldType === 'livewire-searchable-select') {
                $fieldObj = $this->getField($field);
                if (method_exists($fieldObj, 'getInitialOptions')) {
                    $this->selectedLabels[$field] = $fieldObj->getInitialOptions($value);
                }
            }
        }
    }






    protected function hydrateMorphToSelectFields(): void
    {
        foreach ($this->fieldDefinitions as $field => $def) {
            if (($def['field_type'] ?? '') !== 'morph_to_select') {
                continue;
            }

            // Raw values are already in $this->fields from loadRecord()
            $rawType = $this->fields['assignable_type'] ?? null;
            $rawId = $this->fields['assignable_id'] ?? null;

            $morphMap = $def['morph_map'] ?? [];
            $displayField = $def['display_field'] ?? 'name';
            $typeKey = null;

            if ($rawType && $rawId) {
                $typeKey = array_search($rawType, $morphMap);
                if ($typeKey !== false) {
                    // Compound field for save and validation
                    $this->fields[$field] = ['type' => $typeKey, 'id' => (int) $rawId];
                    // Reactive properties for Livewire bindings (radio + dropdown)
                    $this->morphSelectedType[$field] = $typeKey;
                    $this->morphSelectedId[$field] = (int) $rawId;
                    // Load entity options for dropdown
                    $modelClass = $morphMap[$typeKey];
                    $this->morphEntityOptions[$field] = $modelClass::pluck($displayField, 'id')->toArray();
                } else {
                    $this->fields[$field] = null;
                    $this->morphSelectedType[$field] = null;
                    $this->morphSelectedId[$field] = null;
                    $this->morphEntityOptions[$field] = [];
                }
            } else {
                $this->fields[$field] = null;
                $this->morphSelectedType[$field] = null;
                $this->morphSelectedId[$field] = null;
                $this->morphEntityOptions[$field] = [];
            }

            // Remove raw fields to avoid duplication (they are not in fieldGroups)
            // unset($this->fields['assignable_type'], $this->fields['assignable_id']);
        }
    }





    public function updatedSearches($value, $field)
    {
        // When search query changes, fetch matching options
        $definition = $this->fieldDefinitions[$field] ?? null;
        if (!$definition)
            return;

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
            // Clear search after single selection
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


        $this->hydrateMorphToSelectFields();



        // Convert comma-separated strings to arrays for multi-select fields
        foreach ($this->fieldDefinitions as $field => $definition) {
            if (($definition['multiSelect'] ?? false) && !isset($definition['relationship']) && isset($this->fields[$field]) && is_string($this->fields[$field])) {
                $this->fields[$field] = array_filter(explode(',', $this->fields[$field])); // remove empty values
            }
        }


        // After fields are populated, refresh selectedLabels for searchable selects
        foreach ($this->fieldDefinitions as $field => $definition) {
            if (($definition['field_type'] ?? '') === 'livewire-searchable-select') {
                $fieldObj = $this->getField($field);
                $this->selectedLabels[$field] = $fieldObj->getInitialOptions($this->fields[$field] ?? null);
            }
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

    // ---------- Save Logic ----------

    public function save(): void
    {


        $this->validateFields();
        if ($this->getErrorBag()->any()) {
            return;
        }

        DB::transaction(function () {
            $record = $this->isEditMode
                ? $this->modelClass::findOrFail($this->recordId)
                : new $this->modelClass();

            // Filter fillable fields based on hidden config
            $formType = $this->isEditMode ? 'onEditForm' : 'onNewForm';
            $allowedFields = array_diff(
                $this->columns,
                $this->hiddenFields[$formType] ?? [],
                $this->hiddenFields['onQuery'] ?? []
            );

            // Exclude polymorphic columns from mass assignment – they will be set by prepareMorphToSelectFields
            $allowedFields = array_diff($allowedFields, ['assignable_type', 'assignable_id']);


            // If allowed groups are specified, further restrict to fields in those groups
            if (!empty($this->allowedGroups)) {
                $groupFields = [];
                foreach ($this->allowedGroups as $groupKey) {
                    if (isset($this->fieldGroups[$groupKey]['fields'])) {
                        $groupFields = array_merge($groupFields, $this->fieldGroups[$groupKey]['fields']);
                    }
                }
                $allowedFields = array_intersect($allowedFields, $groupFields);
            }

            $data = array_intersect_key($this->fields, array_flip($allowedFields));

            // Handle conditional scope
            $this->prepareMorphToSelectFields($data);



            // Automatically cast all checkbox/boolean fields to true/false
            foreach ($this->fieldDefinitions as $fieldName => $definition) {
                if (isset($definition['field_type']) && $definition['field_type'] === 'checkbox') {
                    // If the key exists in $data, cast it; if it's missing (unchecked), set to false
                    if (array_key_exists($fieldName, $data)) {
                        $data[$fieldName] = (bool) $data[$fieldName];
                    } else {
                        // Only add as false if it's an "allowedField" but missing from the request
                        if (in_array($fieldName, $allowedFields)) {
                            $data[$fieldName] = false;
                        }
                    }
                }
            }






            // Handle file uploads
            $data = $this->handleFileUploads($record, $data);

            // Handle multi‑select fields that are not relationships (comma‑separated)
            foreach ($this->fieldDefinitions as $field => $def) {
                if (isset($def['multiSelect']) && !isset($def['relationship']) && isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }

            // Save the record
            if ($this->isEditMode) {
                $record->update($data);
            } else {
                $record = $record->create($data);
                $this->recordId = $record->id;
            }


            //Add the Audit trail to ActivityLogger
            $logName = $this->configKey; // e.g., 'hr.attendance'

            if ($this->isEditMode) {
                $original = $record->getOriginal();
                $changed = $record->getChanges();
                $old = array_intersect_key($original, $changed);
                $new = array_intersect_key($data, $changed);
                ActivityLogger::updated($logName, $record, $old, $new);
            } else {
                ActivityLogger::created($logName, $record, $data);
            }


            // Sync relationships
            $this->syncRelationships($record);

            // Emit success event
            $this->dispatch('formSaved', $this->recordId, $this->isEditMode);
            $this->dispatch('refreshDataTable');

            if (!$this->inline) {
                $this->dispatch('closeModal', $this->modalId);
            } else {
                $module = strtolower($this->getConfigResolver()->getModuleName());
                $modelPlural = \Str::plural(\Str::kebab($this->getConfigResolver()->getModelName()));
                return redirect()->to(url("/{$module}/{$modelPlural}?" . http_build_query($this->returnParams)));
            }

            // Reset form for new records
            if (!$this->isEditMode) {
                $this->resetFields();
            }
        });

        // Clear cropped images after successful save
        $this->croppedImages = [];
    }



    protected function prepareMorphToSelectFields(array &$data): void
    {
        foreach ($this->fieldDefinitions as $field => $def) {
            if (($def['field_type'] ?? '') === 'morph_to_select') {
                $value = $this->fields[$field] ?? null;

                if (is_array($value) && isset($value['type'], $value['id'])) {
                    $morphMap = $def['morph_map'] ?? [];
                    $typeKey = $value['type'];
                    $data['assignable_type'] = $morphMap[$typeKey] ?? $typeKey;
                    $data['assignable_id'] = (int) $value['id'];

                } else {
                    $data['assignable_type'] = null;
                    $data['assignable_id'] = null;
                }
            }
        }
    }



    protected function validateFields(): void
    {
        $formValidator = app(DataTableFormValidationService::class);
        [$rules, $messages] = $formValidator->getDynamicValidationRules(
            $this->fields,
            $this->fieldDefinitions,
            $this->getFieldFactory(),
            $this->isEditMode,
            null,
            $this->recordId,
            $this->hiddenFields,
        );

        // If allowed groups are specified, only validate fields belonging to those groups
        if (!empty($this->allowedGroups)) {
            $groupFields = [];
            foreach ($this->allowedGroups as $groupKey) {
                if (isset($this->fieldGroups[$groupKey]['fields'])) {
                    $groupFields = array_merge($groupFields, $this->fieldGroups[$groupKey]['fields']);
                }
            }
            $rules = array_intersect_key($rules, array_flip($groupFields));
        }

        $validator = Validator::make($this->fields, $rules, $messages);
        if ($validator->fails()) {
            $this->resetErrorBag();
            foreach ($validator->errors()->messages() as $key => $errors) {
                foreach ($errors as $error) {
                    $this->addError($key, $error);
                }
            }
            return;
        }
    }


    /**
     * For morph_to_select fields, temporarily add the raw type and id fields
     * to $this->fields so validation rules can see them.
     */
    protected function prepareRawFieldsForValidation(): void
    {
        foreach ($this->fieldDefinitions as $field => $def) {
            if (($def['field_type'] ?? '') === 'morph_to_select') {
                $compound = $this->fields[$field] ?? null;
                if (!is_array($compound)) {
                    continue;
                }
                $relationName = $def['morph_relation'] ?? null;
                if ($relationName && isset($this->relations[$relationName])) {
                    $relation = $this->relations[$relationName];
                    $typeField = $relation['typeField'] ?? 'assignable_type';
                    $idField = $relation['idField'] ?? 'assignable_id';
                    $this->fields[$typeField] = $compound['type'] ?? null;
                    $this->fields[$idField] = $compound['id'] ?? null;
                }
            }
        }
    }







    protected function handleFileUploads($record, array $data): array
    {
        // 1. Process cropped images (data URLs) first
        foreach ($this->croppedImages as $field => $dataUrl) {
            // Convert data URL to a temporary file
            $imageData = explode(',', $dataUrl)[1];
            $imageData = base64_decode($imageData);

            $tempFile = tempnam(sys_get_temp_dir(), 'crop_') . '.jpg';
            file_put_contents($tempFile, $imageData);

            // Create a proper UploadedFile instance
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempFile,
                'cropped_image.jpg',
                'image/jpeg',
                null,
                true
            );

            // Store the file
            $folder = 'uploads/' . $this->configKey;
            $storedPath = $uploadedFile->store($folder, 'public');

            if ($storedPath) {
                $data[$field] = $storedPath;

                // Delete old file if editing
                if ($this->isEditMode && !empty($record->$field)) {
                    \Storage::disk('public')->delete($record->$field);
                }
            }

            // Remove from cropped images array
            unset($this->croppedImages[$field]);
        }

        // 2. Process normal file uploads (TemporaryUploadedFile objects)
        foreach ($this->fieldDefinitions as $field => $def) {
            $fieldType = $def['field_type'] ?? null;
            if (!in_array($fieldType, ['file', 'image'])) {
                continue;
            }

            // Skip if already handled as cropped image
            if (isset($data[$field]) && !($this->fields[$field] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)) {
                continue;
            }

            if (isset($this->fields[$field]) && $this->fields[$field] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $tempFile = $this->fields[$field];
                $folder = 'uploads/' . $this->configKey;
                $storedPath = $tempFile->store($folder, 'public');

                if ($storedPath) {
                    $data[$field] = $storedPath;

                    if ($this->isEditMode && !empty($record->$field)) {
                        \Storage::disk('public')->delete($record->$field);
                    }
                }

                // Clear the temporary field
                $this->fields[$field] = null;
            } elseif (isset($data[$field]) && is_string($data[$field]) && !empty($data[$field])) {
                // Keep existing path
            } else {
                $data[$field] = null;
            }
        }

        return $data;
    }






    #[On('cropCompleted')]
    public function handleCroppedImage(array $payload): void
    {
        $croppedImageData = $payload['croppedImageData'];
        $fieldName = $payload['fieldName'];

        // 1. Store the data URL for processing later
        $this->croppedImages[$fieldName] = $croppedImageData;

        // 2. Update the field value (internal state)
        $this->fields[$fieldName] = $croppedImageData;

        // 3. Dispatch to Browser (Note the array structure)
        $this->dispatch(
            'cropped-image-updated',
            fieldName: $fieldName,
            imageDataUrl: $croppedImageData
        );

        // 4. Optional: refresh components
        $this->dispatch('refreshFields');
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

            if (in_array($type, ['belongsToMany', 'hasMany', 'morphMany'])) {
                $ids = $this->fields[$field] ?? [];
                $record->$dynamicProp()->sync($ids);
            }
            // For belongsTo, the foreign key is already in $data
        }
    }

    // ---------- Event Handlers ----------
    public function handleOpenAddModal(string $configKey): void
    {
        if ($configKey !== $this->configKey)
            return;
        $this->resetFields();
        $this->isEditMode = false;
        $this->dispatch('openModal', $this->modalId);
    }

    public function handleOpenEditModal(string $configKey, int $recordId): void
    {
        if ($configKey !== $this->configKey)
            return;
        $this->recordId = $recordId;
        $this->loadRecord();
        $this->isEditMode = true;
        $this->dispatch('openModal', $this->modalId);
    }


    public function resetFields(): void
    {
        $this->fields = [];
        $this->initializeFields();
        $this->recordId = null;
        $this->isEditMode = false;
        $this->prefilledData = [];      // reset prefilled data
        $this->selectedLabels = [];     // clear any selected labels
        $this->resetErrorBag();
    }





    #[On('resetAndPrefill')]
    public function resetAndPrefill(array $prefilledData): void
    {
        // Reset all form state
        $this->fields = [];
        $this->recordId = null;
        $this->isEditMode = false;
        $this->selectedLabels = [];
        $this->searches = [];
        $this->searchResults = [];
        $this->resetErrorBag();

        // Re-initialize with default values from config
        $this->initializeFields();

        // Apply the new prefilled data
        $this->prefilledData = $prefilledData;
        $this->applyPrefilledData();
    }






    public function refreshFields(): void
    {
        // Reload options for relationship selects if needed
        foreach ($this->fieldDefinitions as $field => $def) {
            if (isset($def['relationship']['model'])) {
                $model = $def['relationship']['model'];
                $displayField = $def['relationship']['display_field'] ?? 'name';
                if (class_exists($model)) {
                    $this->fieldDefinitions[$field]['options'] = $model::pluck($displayField, 'id')->toArray();
                }
            }
        }
    }

    // ---------- Render ----------
    public function render()
    {
        // Filter field groups to only those allowed (if any groups specified)
        $displayGroups = empty($this->allowedGroups)
            ? $this->fieldGroups
            : array_intersect_key($this->fieldGroups, array_flip($this->allowedGroups));

        return view('qf::livewire.data-tables.data-table-form', [
            'displayGroups' => $displayGroups,
            'fieldDefinitions' => $this->fieldDefinitions,
            'hiddenFields' => $this->hiddenFields,
            'isEditMode' => $this->isEditMode,
            'inline' => $this->inline,
            'modalId' => $this->modalId,
            'modelName' => $this->getConfigResolver()->getModelName(),
            'moduleName' => $this->getConfigResolver()->getModuleName(),
        ]);
    }
}
