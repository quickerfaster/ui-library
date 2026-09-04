<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Wizards;

use QuickerFaster\UILibrary\Services\ActivityLogger;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\HasAutoGenerateFields;
use QuickerFaster\UILibrary\Concerns\ResolvesModels;

class WizardForm extends Component
{
    use WithFileUploads;
    use HasAutoGenerateFields;
    use ResolvesModels;

    public string $configKey;
    public array $presetData = [];
    public array $presetFields = [];
    public int $stepIndex;
    public ?int $recordId = null;
    public bool $isEditMode = false;
    public array $stepGroups = [];
    public array $customValidation = [];
    public array $dynamicFields = [];

    // Internal state
    public array $fields = [];
    public array $fieldDefinitions = [];
    public array $fieldGroups = [];
    public array $hiddenFields = [];
    public array $relations = [];
    public string $modelClass;

    // Real-time conflict warnings (non-blocking, shown in UI)
    public array $conflictWarnings = [];

    // Configurable draft success message (override in wizard step config)
    public string $draftSuccessMessage = 'Record saved as draft.';

    // For searchable selects
    public array $searches = [];
    public array $searchResults = [];
    public array $selectedLabels = [];

    protected ?ConfigResolver $configResolver = null;
    protected ?FieldFactory $fieldFactory = null;

    public $listeners = [
        'saveStepForm' => 'save',
        'saveDraftForm' => 'saveDraft',
    ];

    public function mount(string $configKey, array $presetData = [], int $stepIndex = 0, ?int $recordId = null, array $customValidation = [], array $dynamicFields = [], ?string $draftSuccessMessage = null): void
    {
        $this->configKey = $configKey;
        $this->presetData = $presetData;
        $this->stepIndex = $stepIndex;
        $this->recordId = $recordId;
        $this->customValidation = $customValidation;
        $this->dynamicFields = $dynamicFields;

        if ($draftSuccessMessage !== null) {
            $this->draftSuccessMessage = $draftSuccessMessage;
        }

        // Single listener for all step save events
        $this->listeners['saveStepForm'] = 'handleSaveStepForm';
        $this->listeners['saveDraftForm'] = 'handleSaveDraftForm';

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

    /**
     * Handle the saveDraftForm event – only proceed if the step index matches.
     */
    public function handleSaveDraftForm($stepIndex): void
    {
        if ($stepIndex == $this->stepIndex) {
            $this->saveDraft();
        }
    }



    protected function loadRecord(): void
    {
        $record = $this->resolveModel($this->modelClass, $this->recordId);

        if (!$record) {
            $this->flashAndRedirect(
                'error',
                'The record you are trying to edit no longer exists or is not accessible.',
                'dashboard'
            );
            return;
        }

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
                } else {
                    $this->fields[$field] = null;
                }
            } else {
                $value = $record->$field;
                // ✅ Convert date fields to string in Y-m-d format
                if (isset($definition['field_type']) && in_array($definition['field_type'], ['date', 'datepicker'])) {
                    $this->fields[$field] = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : $value;
                } else {
                    $this->fields[$field] = $value;
                }
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

        // When 'All Companies' mode, show company_id on forms so super_admin can assign it
        if (\Illuminate\Support\Facades\Session::get('current_company_id') === 0) {
            foreach (['onNewForm', 'onEditForm', 'onTable'] as $context) {
                if (isset($this->hiddenFields[$context])) {
                    $this->hiddenFields[$context] = array_values(array_diff($this->hiddenFields[$context], ['company_id']));
                }
            }
        }

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
                if (isset($definition['field_type']) && in_array($definition['field_type'], ['date', 'datepicker'])) {
                    $default = $default instanceof \Carbon\Carbon ? $default->format('Y-m-d') : $default;
                }
                $this->fields[$field] = $default;
            }
        }

        // Process dynamic field options (loadOptionsFrom)
        $this->loadDynamicOptions();

        // Initialise selectedLabels for searchable selects
        foreach ($this->fieldDefinitions as $field => $definition) {
            if (($definition['field_type'] ?? '') === 'livewire-searchable-select') {
                $fieldObj = $this->getField($field);
                $this->selectedLabels[$field] = $fieldObj->getInitialOptions($this->fields[$field] ?? null);
            }
        }
    }

    /**
     * Process dynamicFields config: for each field with 'loadOptionsFrom',
     * call the named method on $this and inject the returned options into
     * the field definition so SelectField::getOptions() picks them up.
     *
     * Also removes any 'relationship' key from the field definition so that
     * the dynamic options take precedence over relationship-based loading.
     */
    protected function loadDynamicOptions(): void
    {
        foreach ($this->dynamicFields as $fieldName => $dynamicConfig) {
            if (!isset($dynamicConfig['loadOptionsFrom'])) {
                continue;
            }

            $method = $dynamicConfig['loadOptionsFrom'];

            if (method_exists($this, $method)) {
                $options = $this->{$method}($fieldName);
                if (is_array($options) && isset($this->fieldDefinitions[$fieldName])) {
                    $this->fieldDefinitions[$fieldName]['options'] = $options;
                    // Remove relationship so dynamic options take precedence
                    unset($this->fieldDefinitions[$fieldName]['relationship']);
                }
            }
        }
    }

    protected function applyPresetData(): void
    {
        foreach ($this->presetData as $field => $value) {
            if (array_key_exists($field, $this->fields)) {
                $this->fields[$field] = $value;
                $this->presetFields[$field] = true;

                // Update selectedLabels for searchable selects
                if (($this->fieldDefinitions[$field]['field_type'] ?? '') === 'livewire-searchable-select') {
                    $fieldObj = $this->getField($field);
                    $this->selectedLabels[$field] = $fieldObj->getInitialOptions($value);
                }
            }
        }
    }

    /**
     * Check if a field was set via preset data (should be rendered as read-only).
     */
    public function isPresetField(string $fieldName): bool
    {
        return !empty($this->presetFields[$fieldName]);
    }

    // ---------- Searchable Select Logic ----------
    public function updatedSearches($value, $field)
    {
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

        $wasEdit = $this->isEditMode;

        DB::transaction(function () use ($wasEdit) {
            if ($this->isEditMode) {
                $record = $this->resolveModelOrFail($this->modelClass, $this->recordId);
            } else {
                $record = new $this->modelClass();
            }

            $formType = $this->isEditMode ? 'onEditForm' : 'onNewForm';
            $hiddenForForm = $this->hiddenFields[$formType] ?? [];

            // When 'All Companies' mode (0), show company_id on forms so super_admin can assign it
            if (\Illuminate\Support\Facades\Session::get('current_company_id') === 0) {
                $hiddenForForm = array_diff($hiddenForForm, ['company_id']);
            }

            $allowedFields = array_diff(
                array_keys($this->fieldDefinitions),
                $hiddenForForm,
                $this->hiddenFields['onQuery'] ?? []
            );
            $data = array_intersect_key($this->fields, array_flip($allowedFields));

            $data = $this->handleFileUploads($record, $data);

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

            // When submitting a draft, transition status from Draft to Pending
            if ($this->isEditMode && ($record->status ?? '') === 'Draft') {
                $data['status'] = 'Pending';
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

            // Auto-start workflow for records that implement Workflowable
            // Skip if status is Draft (drafts are saved without workflow)
            if ($record instanceof \QuickerFaster\UILibrary\Contracts\Workflow\Workflowable) {
                $status = $record->status ?? ($data['status'] ?? null);
                if ($status !== 'Draft') {
                    try {
                        $definitionKey = $record->getWorkflowDefinitionKey();
                        $engine = app(\QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine::class);
                        $definition = $engine->getDefinition($definitionKey);

                        if ($definition !== null) {
                            $engine->start($record, $record->getWorkflowContext());
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('WizardForm: workflow auto-start failed', [
                            'model' => get_class($record),
                            'id' => $record->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $this->dispatch('stepFormSaved', $record->id, $this->stepIndex);

            // Show success feedback to user
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $wasEdit ? 'Record updated successfully.' : 'Record created successfully.',
            ]);
        });
    }

    /**
     * Save the current step as a draft without triggering workflow.
     * Uses minimal validation — only required fields are checked.
     */
    public function saveDraft(): void
    {
        // Minimal validation: only check required fields
        $rules = [];
        foreach ($this->fieldDefinitions as $field => $def) {
            if (isset($def['validation']) && !$this->isFieldHidden($field, $this->isEditMode ? 'onEditForm' : 'onNewForm')) {
                $rule = $def['validation'];
                // For draft, make all fields nullable except those explicitly required
                // Strip 'required' from rules to allow partial saves
                $parts = explode('|', $rule);
                $parts = array_filter($parts, fn($p) => !str_starts_with($p, 'required'));
                if (!empty($parts)) {
                    $rules[$field] = implode('|', $parts);
                }
            }
        }

        if (!empty($rules)) {
            $validator = Validator::make($this->fields, $rules);
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

        DB::transaction(function () {
            if ($this->isEditMode) {
                $record = $this->resolveModelOrFail($this->modelClass, $this->recordId);
            } else {
                $record = new $this->modelClass();
            }

            $formType = $this->isEditMode ? 'onEditForm' : 'onNewForm';
            $hiddenForForm = $this->hiddenFields[$formType] ?? [];

            if (\Illuminate\Support\Facades\Session::get('current_company_id') === 0) {
                $hiddenForForm = array_diff($hiddenForForm, ['company_id']);
            }

            $allowedFields = array_diff(
                array_keys($this->fieldDefinitions),
                $hiddenForForm,
                $this->hiddenFields['onQuery'] ?? []
            );
            $data = array_intersect_key($this->fields, array_flip($allowedFields));

            $data = $this->handleFileUploads($record, $data);

            foreach ($this->fieldDefinitions as $field => $def) {
                if (isset($def['multiSelect']) && !isset($def['relationship']) && isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = implode(',', $data[$field]);
                }
            }

            // Force status to Draft
            $data['status'] = 'Draft';

            if (!$this->isEditMode) {
                $companyId = \Illuminate\Support\Facades\Session::get('current_company_id');
                if ($companyId && $companyId !== 0 && \Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'company_id')) {
                    $data['company_id'] = $companyId;
                }
            }

            if ($this->isEditMode) {
                $record->update($data);
                ActivityLogger::updated($this->configKey, $record, $record->getOriginal(), $data);
            } else {
                $record = $record->create($data);
                $this->recordId = $record->id;
                $this->isEditMode = true;
                ActivityLogger::created($this->configKey, $record, $data);
            }

            $this->syncRelationships($record);

            // Dispatch stepFormSaved so the wizard tracks the record ID
            $this->dispatch('stepFormSaved', $record->id, $this->stepIndex);

            // Show draft-specific success message
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $this->draftSuccessMessage,
            ]);
        });
    }

    protected function validateFields(): void
    {
        // When 'All Companies' mode, make company_id required on the form
        if (\Illuminate\Support\Facades\Session::get('current_company_id') === 0
            && isset($this->fieldDefinitions['company_id'])
            && !$this->isEditMode
        ) {
            $this->fieldDefinitions['company_id']['validation'] = 'required|integer|exists:companies,id';
        }

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

        // Run custom validation methods defined in the wizard step config
        if (empty($this->getErrorBag()->toArray())) {
            foreach ($this->customValidation as $method) {
                if (method_exists($this, $method)) {
                    $this->{$method}();
                }
            }
        }
    }

    /**
     * Custom validation: check that the employee has sufficient leave balance.
     *
     * Override in consuming app subclass with domain-specific logic.
     */
    protected function checkLeaveBalance(): void
    {
        // Stub — override in consuming app subclass
    }

    /**
     * Custom validation: check for overlapping approved leave requests.
     *
     * Override in consuming app subclass with domain-specific logic.
     */
    protected function checkDateConflicts(): void
    {
        // Stub — override in consuming app subclass
    }

    /**
     * Livewire hook: fires when any field in the `fields` array is updated.
     *
     * When start_date or end_date changes, run real-time conflict detection
     * and store warnings in $conflictWarnings for display in the blade.
     */
    public function updatedFields($value, $nested): void
    {
        if (in_array($nested, ['start_date', 'end_date'], true)) {
            $this->detectDateConflicts();
        }
    }

    /**
     * Real-time conflict detection for the UI (non-blocking warning).
     *
     * Override in consuming app subclass with domain-specific logic.
     */
    protected function detectDateConflicts(): void
    {
        // Stub — override in consuming app subclass
    }

    /**
     * Calculate the number of working days (Mon-Fri) between two dates.
     */
    protected function calculateWorkingDays(string $startDate, string $endDate): float
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $days = 0;

        while ($start->lte($end)) {
            if (!$start->isWeekend()) {
                $days++;
            }
            $start->addDay();
        }

        // P2: Half-day support — if is_half_day is true, return 0.5 per working day
        if (!empty($this->fields['is_half_day'])) {
            return $days * 0.5;
        }

        return (float) $days;
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

    /**
     * Get available leave types with balance info for the dropdown.
     *
     * Override in consuming app subclass with domain-specific logic.
     */
    public function getAvailableLeaveTypes(string $fieldName): array
    {
        // Stub — override in consuming app subclass
        return [];
    }

    /**
     * P1-2: Get working days count between start_date and end_date.
     *
     * Returns null if either date is missing, otherwise the count of Mon-Fri days.
     */
    public function getWorkingDaysCount(): ?float
    {
        $startDate = $this->fields['start_date'] ?? null;
        $endDate = $this->fields['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            return null;
        }

        return $this->calculateWorkingDays($startDate, $endDate);
    }

    /**
     * Get leave type info for the currently selected leave_type_id.
     *
     * Override in consuming app subclass with domain-specific logic.
     */
    public function getLeaveTypeInfo(): ?array
    {
        // Stub — override in consuming app subclass
        return null;
    }

    /**
     * Get field info for the hints system (showInfo hint).
     *
     * Override in consuming app subclass.
     */
    public function getFieldInfo(string $fieldName): ?array
    {
        return null;
    }

    /**
     * Get field duration for the hints system (showDuration hint).
     *
     * Override in consuming app subclass.
     */
    public function getFieldDuration(string $fieldName): ?float
    {
        return null;
    }

    /**
     * Get field conflicts for the hints system (showConflicts hint).
     *
     * Override in consuming app subclass.
     */
    public function getFieldConflicts(string $fieldName): array
    {
        return [];
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