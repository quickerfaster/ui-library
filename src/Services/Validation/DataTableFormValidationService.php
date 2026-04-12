<?php

namespace QuickerFaster\UILibrary\Services\Validation;

use Illuminate\Validation\Rule;

class DataTableFormValidationService
{
    public function getDynamicValidationRules($fields, $fieldDefinitions, $fieldFactory, $isEditMode = false, $model = null, $recordId = null, $hiddenFields = [])
    {
        $rules = [];
        $allMessages = [];

        foreach ($fieldDefinitions as $field => $definition) {
            if (!$this->shouldValidateField($fields, $fieldDefinitions, $field, $isEditMode, $model, $recordId, $hiddenFields)) {
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
        if (isset($fieldDefinitions[$field]['type']) && $fieldDefinitions[$field]['type'] === 'file') {
            return true;
        }

        // If password fiied is changed on edit form validate
        if ($field === 'password' || $field === 'password_confirmation') {
            // $modelClass eg. App\Modules\Admin\Models\User
            return $isEditMode && isset($fields['password']);

        }



        $formType = $isEditMode ? 'onEditForm' : 'onNewForm';
        return !in_array($field, $hiddenFields[$formType] ?? []);
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
