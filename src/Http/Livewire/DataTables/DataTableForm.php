<?php

namespace QuickerFaster\UILibrary\Http\Livewire\DataTables;

use QuickerFaster\UILibrary\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use Livewire\Attributes\On;


use QuickerFaster\UILibrary\Services\Validation\DataTableFormValidationService;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasHintField;
use QuickerFaster\UILibrary\Traits\HasAutoGenerateFields;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;
use QuickerFaster\UILibrary\Concerns\ResolvesModels;

class DataTableForm extends Component
{
    use WithFileUploads;
    use HasAutoGenerateFields;
    use HasHintField;
    use ResolvesModels;

    // Public properties (config‑driven)
    public string $configKey;
    public ?int $recordId = null;
    public bool $inline = false;          // If true, no modal footer
    public ?string $modalId = null;       // For closing the modal

    // Internal state
    public array $fields = [];
    public array $fileUploads = [];  // Top-level file upload storage — Livewire's WithFileUploads
    // properly persists TemporaryUploadedFile objects here,
    // unlike nested $fields array where they are lost across requests.
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
    protected int $sessionCompanyId = 0;


    // Lazy-loaded services
    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;

    protected $listeners = [
        'openAddModal' => 'handleOpenAddModal',
        'openEditModal' => 'handleOpenEditModal',
        'refreshFields' => 'refreshFields',
        'resetForm' => 'resetFields',
        'calculationLogicUpdated' => 'updateCalculationLogic',

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
        $this->sessionCompanyId = session('current_company_id', 0);
    \Log::info('DataTableForm mount - sessionCompanyId set to ' . $this->sessionCompanyId);


        if ($this->recordId) {
            // Resolve the model once and pass it to authorization to avoid a second findOrFail
            $record = $this->resolveModelOrFail($this->modelClass, $this->recordId);
            app(AuthorizationService::class)->authorizeUpdate(auth()->user(), $record, $this->modelClass);

            $this->isEditMode = true;
            $this->loadRecord($record);
        } else {
            app(AuthorizationService::class)->authorizeCreate(auth()->user(), $this->modelClass);
            // Apply prefilled data only for new records
            $this->applyPrefilledData();
        }

    }



    public function updateCalculationLogic($json)
    {
        $this->fields['calculation_logic'] = $json;
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

        // When 'All Companies' mode, show company_id on forms so super_admin can assign it
        if ($this->isAllCompaniesMode()) {
            foreach (['onNewForm', 'onEditForm', 'onTable'] as $context) {
                if (isset($this->hiddenFields[$context])) {
                    $this->hiddenFields[$context] = array_values(array_diff($this->hiddenFields[$context], ['company_id']));
                }
            }
        }

        $this->relations = $resolver->getRelations();
        $this->columns = array_keys($this->fieldDefinitions);

    }

    protected function initializeFields(): void
    {
        $this->fields = [];


        foreach ($this->fieldDefinitions as $field => $definition) {
            $default = $definition['default'] ?? null;

            if (isset($definition['multiSelect']) && $definition['multiSelect']) {
                $this->fields[$field] = [];
            } elseif (isset($definition['field_type']) && $definition['field_type'] === 'boolcheckbox') {
                $this->fields[$field] = $default ?? false;
            } elseif (isset($definition['field_type']) && $definition['field_type'] === 'boolradio') {
                $this->fields[$field] = $default ?? null;
            } else {
                $default = $definition['default'] ?? null;

                // ✅ For date fields, ensure default is a string
                if (isset($definition['field_type']) && in_array($definition['field_type'], ['date', 'datepicker'])) {
                    $default = $default instanceof \Carbon\Carbon ? $default->format('Y-m-d') : $default;
                }

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
        if (!isset($this->fields[$fieldName]) || !is_array($this->fields[$fieldName])) {
            $this->fields[$fieldName] = ['type' => null, 'id' => null];
        }
        $this->fields[$fieldName]['type'] = $type;
        $this->fields[$fieldName]['id'] = null;
    }





public function updatedMorphSelectedType($value, $fieldName): void
{
    $this->morphSelectedId[$fieldName] = null;
    $this->loadMorphEntityOptions($fieldName);
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
        $query = $modelClass::query();

        // Restrict Company type when in single‑company mode
        if ($selectedType === 'company' && $this->sessionCompanyId !== 0) {
            $query->where('id', $this->sessionCompanyId);
        }

        $this->morphEntityOptions[$fieldName] = $query->pluck($displayField, 'id')->toArray();
    }
}

public function hydrate()
{
    $this->sessionCompanyId = session('current_company_id', 0);
}

/**
 * Determine whether the current session is in "All Companies" mode.
 *
 * The company switcher stores the selected company ID in the session.
 * "All Companies" is represented by 0, null, or an empty value, while a
 * specific company is a positive integer.
 */
protected function isAllCompaniesMode(): bool
{
    $companyId = \Illuminate\Support\Facades\Session::get('current_company_id');

    return empty($companyId) || (int) $companyId === 0;
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

        $rawType = $this->fields['assignable_type'] ?? null;
        $rawId = $this->fields['assignable_id'] ?? null;
        $morphMap = $def['morph_map'] ?? [];
        $typeKey = null;

        if ($rawType && $rawId) {
            $typeKey = array_search($rawType, $morphMap);
            if ($typeKey !== false) {
                // Compound field for save/validation
                $this->fields[$field] = ['type' => $typeKey, 'id' => (int) $rawId];
                // Reactive properties for Livewire bindings
                $this->morphSelectedType[$field] = $typeKey;
                $this->morphSelectedId[$field] = (int) $rawId;
                // Load options via the unified method (which now applies the filter)
                $this->loadMorphEntityOptions($field);
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
    }
}






    // DataTableForm.php

    public function updatedSearches($value, $field)
    {
        $definition = $this->fieldDefinitions[$field] ?? null;
        if (!$definition) {
            return;
        }

        $results = [];

        if (isset($definition['relationship'])) {
            $rel = $definition['relationship'];
            $model = $rel['model'];
            $displayField = $rel['display_field'] ?? 'name';
            $hintField = $rel['hint_field'] ?? ($definition['options']['hintField'] ?? null);

            // Parse hint fields using the trait
            $hintFields = $this->parseHintFields($hintField);

            $searchableFields = $rel['searchable_fields'] ?? null;

            $query = $model::query();

            if (is_array($searchableFields) && !empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $value) {
                    foreach ($searchableFields as $sf) {
                        $q->orWhere($sf, 'LIKE', "%{$value}%");
                    }
                });
            } else {
                $query->where(function ($q) use ($displayField, $hintFields, $value) {
                    $q->where($displayField, 'LIKE', "%{$value}%");
                    foreach ($hintFields as $hf) {
                        $q->orWhere($hf, 'LIKE', "%{$value}%");
                    }
                });
            }

            $items = $query->limit(50)->get();

            foreach ($items as $item) {
                $label = $item->$displayField;
                $hintParts = [];
                foreach ($hintFields as $hf) {
                    if (!empty($item->$hf)) {
                        $hintParts[] = $item->$hf;
                    }
                }
                if (!empty($hintParts)) {
                    $label .= ' (' . implode(' ', $hintParts) . ')';
                }
                $results[$item->id] = $label;
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

        // Convert to integer
        $id = (int) $id;

        if ($multiple) {
            $current = $this->fields[$field] ?? [];
            // Ensure all existing IDs are integers
            $current = array_map('intval', $current);
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



    /**
     * Create a new tag/option on the fly and select it immediately.
     * Works for any relationship model that has fillable 'name' and 'slug' fields.
     *
     * @param string $field
     * @param string $newValue
     * @return void
     */


    // DataTableForm.php

    public function createAndSelectOption($field, $newValue)
    {
        $definition = $this->fieldDefinitions[$field] ?? null;
        if (!$definition || !isset($definition['relationship'])) {
            return;
        }

        // Check if inline creation is allowed
        $inlineAdd = $definition['relationship']['inlineAdd'] ?? false;
        if (!$inlineAdd) {
            // Optionally dispatch a message or just silently ignore
            return;
        }

        $rel = $definition['relationship'];
        $modelClass = $rel['model'];
        $displayField = $rel['display_field'] ?? 'name';

        // Create new record – ensure fillable fields are allowed
        // You might want to customize which fields are set. For simplicity:
        $fillable = (new $modelClass)->getFillable();
        $data = [];
        if (in_array($displayField, $fillable)) {
            $data[$displayField] = $newValue;
        }
        // Optionally set slug, color, is_active if they exist in fillable
        if (in_array('slug', $fillable)) {
            $data['slug'] = \Illuminate\Support\Str::slug($newValue);
        }
        if (in_array('color', $fillable)) {
            $data['color'] = 'primary';
        }
        if (in_array('is_active', $fillable)) {
            $data['is_active'] = true;
        }

        $newRecord = $modelClass::create($data);

        $multiple = $definition['multiSelect'] ?? false;
        $hintField = $rel['hint_field'] ?? ($definition['options']['hintField'] ?? null);
        $label = $newRecord->$displayField;
        if ($hintField && !empty($newRecord->$hintField)) {
            $label .= " ({$newRecord->$hintField})";
        }

        if ($multiple) {
            $currentIds = $this->fields[$field] ?? [];
            $currentIds = array_map('intval', $currentIds);
            if (!in_array($newRecord->id, $currentIds)) {
                $currentIds[] = $newRecord->id;
                $this->fields[$field] = $currentIds;
                $this->selectedLabels[$field][$newRecord->id] = $label;
            }
        } else {
            $this->fields[$field] = $newRecord->id;
            $this->selectedLabels[$field] = [$newRecord->id => $label];
        }

        // Clear search
        $this->searches[$field] = '';
        $this->searchResults[$field] = [];
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




    protected function loadRecord(?Model $resolvedRecord = null): void
    {
        $record = $resolvedRecord ?? $this->resolveModelOrFail($this->modelClass, $this->recordId);

        if (!empty($this->relations)) {
            // Only eager-load relations that actually exist on the model.
            // This prevents crashes when a config defines a relation (e.g. a
            // module-specific relation like HR's 'profile') that the underlying
            // model class doesn't implement.
            $validRelations = array_filter($this->relations, function ($relationConfig, $relationName) use ($record) {
                return method_exists($record, $relationName);
            }, ARRAY_FILTER_USE_BOTH);
            if (!empty($validRelations)) {
                $record->load(array_keys($validRelations));
            }
        }

        foreach ($this->fieldDefinitions as $field => $definition) {
            if (isset($definition['relationship'])) {
                $rel = $definition['relationship'];

                $dynamicProp = $rel['dynamic_property'] ?? $field;
                if ($record->$dynamicProp) {
                    $relationResult = $record->$dynamicProp;
                    if (method_exists($relationResult, 'pluck')) {
                        // It's a collection (many‑to‑many, hasMany, morphMany, morphToMany)
                        $this->fields[$field] = $relationResult->pluck('id')->toArray();
                    } else {
                        // It's a single model (belongsTo, hasOne)
                        $this->fields[$field] = $relationResult->id ?? null;
                    }
                } elseif (($rel['type'] ?? '') === 'belongsTo' && isset($rel['foreign_key'])) {
                    // Fallback: when the related model can't be loaded (e.g. due to
                    // global scopes in multi-tenant mode), read the foreign key
                    // value directly from the record so the select dropdown still
                    // shows the correct pre-selected value.
                    $this->fields[$field] = $record->{$rel['foreign_key']} ?? null;
                } else {
                    $this->fields[$field] = null;
                }
            } else {

                $value = $record->$field;
                // ✅ Convert date fields to string in Y-m-d format
                if (isset($definition['field_type']) && in_array($definition['field_type'], ['date', 'datepicker'])) {
                    $this->fields[$field] = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : $value;
                } else {
                    if ($definition['field_type'] != 'password')
                        $this->fields[$field] = $value;
                    else
                        $this->fields[$field] = ""; // For password load empty value to the form

                }

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
        return $this->getFieldFactory()->make(
            $name,
            $this->getConfigResolver()->getSettingsOverrideFieldDefinition($name)
        );
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


        // Pre-save document limit check
        if (!$this->checkDocumentLimit()) {
            return;
        }


        DB::transaction(function () {
            $record = $this->isEditMode
                ? $this->resolveModelOrFail($this->modelClass, $this->recordId)
                : new $this->modelClass();

            // Filter fillable fields based on hidden config
            $formType = $this->isEditMode ? 'onEditForm' : 'onNewForm';
            $hiddenForForm = $this->hiddenFields[$formType] ?? [];

            // When 'All Companies' mode (0), show company_id on forms so super_admin can assign it
            if ($this->isAllCompaniesMode()) {
                $hiddenForForm = array_diff($hiddenForForm, ['company_id']);
            }

            $allowedFields = array_diff(
                $this->columns,
                $hiddenForForm,
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

                // Skip multiselect
                if ($definition["multiSelect"] ?? false)
                    continue;

                if (isset($definition['field_type'])) {
                    // Prepare boolen values
                    if ($definition['field_type'] === 'checkbox') {
                        // If the key exists in $data, cast it; if it's missing (unchecked), set to false
                        if (array_key_exists($fieldName, $data)) {
                            $data[$fieldName] = (bool) $data[$fieldName];
                        } else {
                            // Only add as false if it's an "allowedField" but missing from the request
                            if (in_array($fieldName, $allowedFields)) {
                                $data[$fieldName] = false;
                            }
                        }

                        // Prepare password values
                    } else if ($definition['field_type'] === 'password') {
                        if (empty($data[$fieldName]) && $this->isEditMode)
                            unset($data[$fieldName]); // Remove the none updated password fields
                        else if (isset($data[$fieldName]))
                            $data[$fieldName] = \Illuminate\Support\Facades\Hash::make($data[$fieldName]);
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

            // Inject company_id from session for multi-tenancy (create mode only)
            // Skip when 'All Companies' (0) is selected — records get NULL company_id
            if (!$this->isEditMode) {
                $companyId = \Illuminate\Support\Facades\Session::get('current_company_id');
                if ($companyId && $companyId !== 0 && \Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'company_id')) {
                    $data['company_id'] = $companyId;
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
            $logName = $this->configKey; // e.g., 'module.resource'

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

            // Show success feedback to user
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $this->isEditMode ? 'Record updated successfully.' : 'Record created successfully.',
            ]);

            // Refresh record globally
            $refreshEventName = "refresh" . \Str::plural($this->getConfigResolver()->getModelName());
            $this->dispatch($refreshEventName);


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






    /**
     * Check document limit for Document model before saving.
     *
     * @return bool True if limit is not exceeded (or not applicable), false otherwise.
     */
    private function checkDocumentLimit(): bool
    {
        // Only applies to Document model
        if ($this->modelClass !== \QuickerFaster\UILibrary\Models\Document::class) {
            return true;
        }

        $documentableId = $this->fields['documentable_id'] ?? null;
        if (!$documentableId) {
            // No related record selected yet – validation will catch required rule later
            return true;
        }

        $documentable = $this->resolveRelatedModel('documentable', $documentableId);
        if (!$documentable) {
            return true;
        }

        // Get max limit from config or default to 3
        $max = $this->fieldDefinitions['document']['maxDocumentsPerRecord'] ?? 3;

        $isCreating = !$this->isEditMode;
        $isChangingDocumentable = false;

        if ($this->isEditMode && $this->recordId) {
            $existingDocument = $this->modelClass::find($this->recordId);
            if ($existingDocument && $existingDocument->documentable_id != $documentableId) {
                $isChangingDocumentable = true;
            }
        }

        $shouldCheck = $isCreating || $isChangingDocumentable;

        if ($shouldCheck) {
            $excludeDocumentId = ($this->isEditMode && $isChangingDocumentable) ? $this->recordId : null;
            $currentCount = $this->modelClass::where('documentable_id', $documentableId);
            if ($excludeDocumentId) {
                $currentCount->where('id', '!=', $excludeDocumentId);
            }
            if ($currentCount->count() >= $max) {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "This record already has the maximum allowed documents ({$max}). Cannot add another document.",
                    'autoClose' => true,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a related model instance dynamically.
     *
     * This replaces hardcoded App\Modules references (e.g., \App\Models\Invoice)
     * with config-driven model resolution. The consuming application should configure
     * the related model mapping in their ui-library config.
     *
     * @param string $relation The relation name (e.g., 'documentable')
     * @param mixed $id The model ID to find
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function resolveRelatedModel(string $relation, $id): ?\Illuminate\Database\Eloquent\Model
    {
        // Try to resolve from the config resolver's relations first
        $relations = $this->getConfigResolver()->getRelations() ?? [];
        if (isset($relations[$relation])) {
            $modelClass = $relations[$relation]['model'] ?? null;
            if ($modelClass && class_exists($modelClass)) {
                return $modelClass::find($id);
            }
        }

        // Fallback: try the model class from the config resolver
        $modelClass = $this->getConfigResolver()->getModel();
        if ($modelClass && class_exists($modelClass)) {
            return $modelClass::find($id);
        }

        return null;
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
        // Merge top-level file uploads into $fields so the validator can see them.
        // Livewire's WithFileUploads properly persists TemporaryUploadedFile objects
        // in top-level properties but loses them inside nested arrays like $fields.
        foreach ($this->fileUploads as $fieldName => $file) {
            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $this->fields[$fieldName] = $file;
            }
        }

        // When 'All Companies' mode, make company_id required on the form
        $fieldDefs = $this->fieldDefinitions;
        if (
            $this->isAllCompaniesMode()
            && isset($fieldDefs['company_id'])
            && !$this->isEditMode
        ) {
            $fieldDefs['company_id']['validation'] = 'required|integer|exists:companies,id';
        }

        // 🔍 DIAGNOSTIC: Log state before validation
        \Log::channel('single')->warning('validateFields() START', [
            'configKey' => $this->configKey,
            'modelClass' => $this->modelClass,
            'isEditMode' => $this->isEditMode,
            'fieldDefs_keys' => array_keys($fieldDefs),
            'fieldDefs_has_document' => array_key_exists('document', $fieldDefs),
            'document_def' => $fieldDefs['document'] ?? 'NOT_IN_DEFS',
            'fields_keys' => array_keys($this->fields),
            'fields_has_document' => array_key_exists('document', $this->fields),
            'fields_document_value' => $this->fields['document'] ?? 'KEY_NOT_PRESENT',
            'hiddenFields' => $this->hiddenFields,
            'allowedGroups' => $this->allowedGroups,
        ]);

        $formValidator = app(DataTableFormValidationService::class);
        [$rules, $messages] = $formValidator->getDynamicValidationRules(
            $this->fields,
            $fieldDefs,
            $this->getFieldFactory(),
            $this->isEditMode,
            null,
            $this->recordId,
            $this->hiddenFields,
        );

        // 🔍 DIAGNOSTIC: Log generated rules
        \Log::channel('single')->warning('validateFields() RULES', [
            'configKey' => $this->configKey,
            'rules_keys' => array_keys($rules),
            'rules_has_document' => array_key_exists('document', $rules),
            'document_rule' => $rules['document'] ?? 'NOT_IN_RULES',
        ]);

        // For file fields: ensure file uploads are properly recognized before validation.
        // The merge above (fileUploads → fields) handles most cases, but we add
        // belt-and-suspenders logic here for both create and edit modes.
        foreach ($this->fieldDefinitions as $fieldName => $definition) {
            if (($definition['field_type'] ?? '') !== 'file') {
                continue;
            }

            // CREATE MODE: Ensure file from fileUploads is copied to fields
            // (belt-and-suspenders — the merge above should already handle this,
            // but if wire:model binding was delayed, this catches it)
            if (!$this->isEditMode) {
                if (
                    isset($this->fileUploads[$fieldName])
                    && $this->fileUploads[$fieldName] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                ) {
                    $this->fields[$fieldName] = $this->fileUploads[$fieldName];

                    \Log::channel('single')->info('validateFields(): file field in create mode — copied from fileUploads to fields', [
                        'field' => $fieldName,
                    ]);
                }
                continue;
            }

            // EDIT MODE: If no new file is being uploaded, replace validation rules
            // with 'nullable' since the existing file is kept.
            // (Livewire does not preserve old file paths across requests, so the field
            // value will be null — 'file' and 'required' would both fail on null.)
            $hasNewFile = isset($this->fields[$fieldName])
                && $this->fields[$fieldName] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

            if (!$hasNewFile && isset($rules[$fieldName])) {
                $rules[$fieldName] = 'nullable';

                \Log::channel('single')->info('validateFields(): file field in edit mode, no new file — set to nullable', [
                    'field' => $fieldName,
                ]);
            }
        }

        // If allowed groups are specified, only validate fields belonging to those groups
        if (!empty($this->allowedGroups)) {
            $groupFields = [];
            foreach ($this->allowedGroups as $groupKey) {
                if (isset($this->fieldGroups[$groupKey]['fields'])) {
                    $groupFields = array_merge($groupFields, $this->fieldGroups[$groupKey]['fields']);
                }
            }
            $rules = array_intersect_key($rules, array_flip($groupFields));

            // 🔍 DIAGNOSTIC: Log after group filtering
            \Log::channel('single')->warning('validateFields() AFTER GROUP FILTER', [
                'configKey' => $this->configKey,
                'rules_keys' => array_keys($rules),
                'rules_has_document' => array_key_exists('document', $rules),
            ]);
        }

        // Custom validation for policy calculation logic
        // Triggered when the model config has a 'calculation_logic' field
        if (isset($this->fieldDefinitions['calculation_logic'])) {
            $this->validatePolicyCalculationLogic();
        }



        $validator = Validator::make($this->fields, $rules, $messages);
        if ($validator->fails()) {
            // 🔍 DIAGNOSTIC: Log validation failures
            \Log::channel('single')->warning('validateFields() FAILED', [
                'configKey' => $this->configKey,
                'modelClass' => $this->modelClass,
                'errors' => $validator->errors()->toArray(),
                'fields_snapshot' => array_map(function ($v) {
                    return is_object($v) ? get_class($v) : (is_array($v) ? '(array)' : $v);
                }, $this->fields),
            ]);

            $this->resetErrorBag();
            foreach ($validator->errors()->messages() as $key => $errors) {
                foreach ($errors as $error) {
                    $this->addError($key, $error);
                }
            }
            return;
        }
    }

    protected function validatePolicyCalculationLogic(): void
    {
        $type = $this->fields['type'] ?? null;
        $calcLogic = $this->fields['calculation_logic'] ?? null;
        if (!$type || !$calcLogic)
            return;

        $data = json_decode($calcLogic, true);
        if (!is_array($data)) {
            $this->addError('calculation_logic', 'Invalid calculation logic.');
            return;
        }

        if ($type === 'tax') {
            $bands = $data['bands'] ?? [];
            $hasValid = false;
            foreach ($bands as $band) {
                $limit = $band[0] ?? ($band['limit'] ?? 0);
                $rate = $band[1] ?? ($band['rate'] ?? 0);
                if ($limit > 0 || $rate > 0) {
                    $hasValid = true;
                    break;
                }
            }
            if (!$hasValid) {
                $this->addError('calculation_logic', 'At least one tax bracket with a positive limit or rate is required.');
            }
        } else {
            $individualValue = $data['individual_value'] ?? 0;
            $organizationValue = $data['organization_value'] ?? 0;
            if ($individualValue <= 0 && $organizationValue <= 0) {
                $this->addError('calculation_logic', 'At least one of Individual or Organization contribution must be positive.');
            }
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
            $imageData = explode(',', $dataUrl)[1];
            $imageData = base64_decode($imageData);
            $tempFile = tempnam(sys_get_temp_dir(), 'crop_') . '.jpg';
            file_put_contents($tempFile, $imageData);
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempFile,
                'cropped_image.jpg',
                'image/jpeg',
                null,
                true
            );
            $folder = 'uploads/' . $this->configKey;
            $storedPath = $uploadedFile->store($folder, 'public');
            if ($storedPath) {
                $data[$field] = $storedPath;
                if ($this->isEditMode && !empty($record->$field)) {
                    \Storage::disk('public')->delete($record->$field);
                }
            }
            unset($this->croppedImages[$field]);
        }

        // 2. Process normal file uploads — read from $fileUploads (top-level property)
        //    where Livewire's WithFileUploads properly persists TemporaryUploadedFile objects.
        foreach ($this->fieldDefinitions as $field => $def) {
            $fieldType = $def['field_type'] ?? null;
            if (!in_array($fieldType, ['file', 'image'])) {
                continue;
            }

            // Check $fileUploads first (new upload), fall back to $fields (existing path)
            $uploadedFile = $this->fileUploads[$field] ?? null;

            if ($uploadedFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $tempFile = $uploadedFile;

                $disk = 'public';
                $customFolder = null;

                // Override for Document model's 'document' field
                if ($this->modelClass === \QuickerFaster\UILibrary\Models\Document::class && $field === 'document') {
                    $documentableId = $data['documentable_id'] ?? $this->fields['documentable_id'] ?? null;
                    if ($documentableId) {
                        $documentable = $this->resolveRelatedModel('documentable', $documentableId);
                        if ($documentable) {
                            $customFolder = 'documents/record_' . $documentable->id;
                            $disk = 'documents';
                        }
                    }
                }

                $folder = $customFolder ?? ('uploads/' . $this->configKey);
                $storedPath = $tempFile->store($folder, $disk);

                if ($storedPath) {
                    $data[$field] = $storedPath;
                    if ($this->isEditMode && !empty($record->$field)) {
                        $oldDisk = ($this->modelClass === \QuickerFaster\UILibrary\Models\Document::class && $field === 'document') ? 'documents' : 'public';
                        \Storage::disk($oldDisk)->delete($record->$field);
                    }
                }
                // Clear both the upload store and the fields entry
                unset($this->fileUploads[$field]);
                $this->fields[$field] = null;
            } elseif (isset($data[$field]) && is_string($data[$field]) && !empty($data[$field])) {
                // Keep existing path (edit mode, no new file selected)
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

            // Include morphToMany alongside other many-to-many types
            if (in_array($type, ['belongsToMany', 'hasMany', 'morphMany', 'morphToMany'])) {
                $ids = $this->fields[$field] ?? [];
                if (!empty($ids)) {
                    $record->$dynamicProp()->sync($ids);
                } else {
                    $record->$dynamicProp()->sync([]);
                }
            }
            // For belongsTo, foreign key is already in $data
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
        $this->fileUploads = [];        // clear any pending file uploads
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
        $this->fileUploads = [];        // clear any pending file uploads
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

