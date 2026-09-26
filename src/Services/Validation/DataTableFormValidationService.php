<?php

namespace QuickerFaster\UILibrary\Services\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataTableFormValidationService
{
    /**
     * Build dynamic validation rules from field definitions.
     *
     * @param array       $fields           Current field values
     * @param array       $fieldDefinitions Field configuration from the data config
     * @param mixed       $fieldFactory     FieldFactory instance
     * @param bool        $isEditMode       Whether we're editing an existing record
     * @param string|null $modelClass       The fully-qualified model class name
     * @param int|null    $recordId         The record ID when editing
     * @param array       $hiddenFields     Hidden field configuration
     * @return array [rules, messages]
     */
    public function getDynamicValidationRules($fields, $fieldDefinitions, $fieldFactory, $isEditMode = false, $modelClass = null, $recordId = null, $hiddenFields = [])
    {
        $rules = [];
        $allMessages = [];

        // 🔍 DIAGNOSTIC: Log incoming field definitions
        \Log::channel('single')->warning('getDynamicValidationRules() called', [
            'fieldDefs_keys' => array_keys($fieldDefinitions),
            'fieldDefs_has_document' => array_key_exists('document', $fieldDefinitions),
            'fields_keys' => array_keys($fields),
            'fields_has_document' => array_key_exists('document', $fields),
            'hiddenFields' => $hiddenFields,
            'isEditMode' => $isEditMode,
        ]);

        foreach ($fieldDefinitions as $field => $definition) {
            $shouldValidate = $this->shouldValidateField($fields, $fieldDefinitions, $field, $isEditMode, $modelClass, $recordId, $hiddenFields);

            // 🔍 DIAGNOSTIC: Log every field's validation decision
            \Log::channel('single')->info('getDynamicValidationRules() field decision', [
                'field' => $field,
                'shouldValidate' => $shouldValidate,
                'has_validation_key' => isset($definition['validation']),
                'validation_value' => $definition['validation'] ?? 'NONE',
                'field_type' => $definition['field_type'] ?? 'NONE',
            ]);

            if (!$shouldValidate) {
                continue;
            }

            // Try to get validation rules from the field type
            $fieldObj = $fieldFactory->make($field, $definition);
            // Get the validation rules
            $fieldRules = $fieldObj->getValidationRules();

            
            // Get the validation messages
            if (method_exists($fieldObj, 'getValidationMessages')) {
                $allMessages = array_merge($allMessages, $fieldObj->getValidationMessages());
            }


            if (!empty($fieldRules)) {
                // Field type provides its own rules (may be nested)
                foreach ($fieldRules as $key => $rule) {

                    if (str_contains($key, '.')) {
                        // Nested rule like 'assignable_id.type' – keep as is
                        $rules[$key] = $rule;
                    } else {
                        // Main field rule – adjust for unique if needed
                        $rules[$key] = $this->adjustUniqueRule($rule, $isEditMode, $recordId, $modelClass);
                    }
                }

            } elseif (isset($definition['validation'])) {
                // Fallback to config string
                $rules[$field] = $this->adjustUniqueRule($definition['validation'], $isEditMode, $recordId, $modelClass);
            } elseif (isset($definition['field_type']) && $definition['field_type'] === 'file') {
                $rules[$field] = $this->getDefaultFileValidationRules($definition);
            } else {
                $rules[$field] = $this->adjustUniqueRule('sometimes', $isEditMode, $recordId, $modelClass);
            }
        }

        // 🔍 DIAGNOSTIC: Log final rules
        \Log::channel('single')->warning('getDynamicValidationRules() RESULT', [
            'rules_keys' => array_keys($rules),
            'rules_has_document' => array_key_exists('document', $rules),
            'document_rule' => $rules['document'] ?? 'NOT_IN_RULES',
        ]);

        return [$rules, $allMessages];
    }


    protected function getDefaultFileValidationRules($definition)
    {
        $fileTypes = $definition['fileTypes'] ?? ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];//, 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'gif', 'svg'];
        $maxSizeMB = $definition['maxSizeMB'] ?? 1; // Default to 1MB
        $maxSizeKB = $maxSizeMB * 1024; // Convert MB to KB for Laravel validation
        return "file|mimes:" . implode(',', $fileTypes) . "|max:{$maxSizeKB}";
    }



    protected function shouldValidateField($fields, $fieldDefinitions, $field, $isEditMode, $modelClass = null, $recordId = null, $hiddenFields = [])
    {

        // Always validate file fields if they exist in request
        if (isset($fieldDefinitions[$field]['field_type']) && $fieldDefinitions[$field]['field_type'] === 'file') {
            \Log::channel('single')->info('shouldValidateField: file field forced', ['field' => $field]);
            return true;
        }

        // If password fiied is changed on edit form validate
        if ($field === 'password' || $field === 'password_confirmation') {
            // $modelClass eg. App\Modules\Admin\Models\User
            $result = !$isEditMode || (isset($fields['password']) && !empty($fields['password']));
            \Log::channel('single')->info('shouldValidateField: password field', ['field' => $field, 'result' => $result]);
            return $result;

        }



        $formType = $isEditMode ? 'onEditForm' : 'onNewForm';
        $isHidden = in_array($field, $hiddenFields[$formType] ?? []);
        $result = !$isHidden;

        // 🔍 DIAGNOSTIC: Log hidden field decisions for key fields
        if ($field === 'document' || $isHidden) {
            \Log::channel('single')->info('shouldValidateField: hidden check', [
                'field' => $field,
                'formType' => $formType,
                'isHidden' => $isHidden,
                'result' => $result,
                'hiddenFields_for_formType' => $hiddenFields[$formType] ?? [],
            ]);
        }

        return $result;
    }

    /**
     * Adjust a unique validation rule for edit mode and soft-delete awareness.
     *
     * When the model uses SoftDeletes, the unique rule must explicitly exclude
     * soft-deleted records because Laravel's table-based unique rule (e.g.
     * "unique:employees,employee_number") does NOT automatically add the
     * "WHERE deleted_at IS NULL" clause — only model-based rules do.
     *
     * This method appends ",NULL,id,deleted_at,NULL" to the unique rule when
     * the model class is provided and uses the SoftDeletes trait.
     *
     * @param string      $validation The raw validation rule string
     * @param bool        $isEditMode Whether editing an existing record
     * @param int|null    $recordId   The record ID (for edit mode exclusion)
     * @param string|null $modelClass The fully-qualified model class name
     * @return string Adjusted validation rule string
     */
    protected function adjustUniqueRule($validation, $isEditMode, $recordId, $modelClass = null)
    {
        if (!str_contains($validation, 'unique')) {
            return $validation;
        }

        // Determine if the model uses SoftDeletes so we can exclude
        // soft-deleted records from the unique check.
        $usesSoftDeletes = $modelClass
            && class_exists($modelClass)
            && in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);

        if ($isEditMode && $recordId) {
            // Edit mode: exclude the current record from the unique check.
            // Also add soft-delete exclusion if applicable.
            if ($usesSoftDeletes) {
                return preg_replace(
                    '/unique:([^,]+),([^,]+)/',
                    "unique:$1,$2,{$recordId},id,deleted_at,NULL",
                    $validation
                );
            }
            return preg_replace(
                '/unique:([^,]+),([^,]+)/',
                "unique:$1,$2,{$recordId}",
                $validation
            );
        }

        // Create mode: add soft-delete exclusion if the model uses SoftDeletes.
        if ($usesSoftDeletes) {
            return preg_replace(
                '/unique:([^,]+),([^,]+)/',
                "unique:$1,$2,NULL,id,deleted_at,NULL",
                $validation
            );
        }

        return $validation;
    }
}
