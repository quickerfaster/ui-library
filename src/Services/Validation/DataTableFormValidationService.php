<?php

namespace QuickerFaster\UILibrary\Services\Validation;

use Illuminate\Validation\Rule;

class DataTableFormValidationService
{
    public function getDynamicValidationRules($fields, $fieldDefinitions, $fieldFactory, $isEditMode = false, $model = null, $recordId = null, $hiddenFields = [])
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
            $shouldValidate = $this->shouldValidateField($fields, $fieldDefinitions, $field, $isEditMode, $model, $recordId, $hiddenFields);

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
                        $rules[$key] = $this->adjustUniqueRule($rule, $isEditMode, $recordId);
                    }
                }

            } elseif (isset($definition['validation'])) {
                // Fallback to config string
                $rules[$field] = $this->adjustUniqueRule($definition['validation'], $isEditMode, $recordId);
            } elseif (isset($definition['field_type']) && $definition['field_type'] === 'file') {
                $rules[$field] = $this->getDefaultFileValidationRules($definition);
            } else {
                $rules[$field] = $this->adjustUniqueRule('sometimes', $isEditMode, $recordId);
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

    protected function adjustUniqueRule($validation, $isEditMode, $recordId)
    {
        if ($isEditMode && $recordId && str_contains($validation, 'unique')) {
            return preg_replace(
                '/unique:([^,]+),([^,]+)/',
                "unique:$1,$2,{$recordId}",
                $validation
            );
        }

        return $validation;
    }
}
