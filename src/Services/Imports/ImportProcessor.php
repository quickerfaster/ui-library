<?php

namespace QuickerFaster\UILibrary\Services\Imports;

use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ImportProcessor
{
    protected ConfigResolver $configResolver;
    protected FieldFactory $fieldFactory;

    public function __construct(ConfigResolver $configResolver, FieldFactory $fieldFactory)
    {
        $this->configResolver = $configResolver;
        $this->fieldFactory = $fieldFactory;
    }

    public function process(string $filePath, array $columnMapping, bool $hasHeaderRow, ?callable $checkCallback = null): array
    {
        $modelClass = $this->configResolver->getModel();
        $fieldDefinitions = $this->configResolver->getFieldDefinitions();

        $rows = Excel::toArray([], $filePath)[0];

        if ($hasHeaderRow) {
            array_shift($rows); // remove header row
        }

        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            // Call the callback if provided (for cancellation)
            if ($checkCallback && $rowIndex % 100 === 0) {
                $checkCallback($rowIndex);
            }


            $processed++;
            $data = [];

            // Build data array using column mapping
            $rowErrors = [];
            $data = [];
            foreach ($columnMapping as $field => $columnIndex) {
                $rawValue = $row[$columnIndex];
                $def = $fieldDefinitions[$field] ?? [];
                $data[$field] = $this->resolveFieldValue($field, $rawValue, $def, $rowErrors);
            }
            if (!empty($rowErrors)) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex + ($hasHeaderRow ? 2 : 1),
                    'errors' => $rowErrors,
                ];
                continue;
            }

            // Build validation rules (flattened)
            $rules = [];
            foreach ($fieldDefinitions as $field => $def) {
                if (($def['fillable'] ?? false) && isset($data[$field])) {
                    $fieldObj = $this->fieldFactory->make($field, $def);
                    $rules[$field] = $fieldObj->getValidationRules();
                }
            }

            $flatRules = array_map(function ($rule) {
                return is_array($rule) ? implode('|', $rule) : $rule;
            }, $rules);

            $validator = Validator::make($data, $flatRules);

            if ($validator->fails()) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex + ($hasHeaderRow ? 2 : 1),
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            try {
                $modelClass::create($data);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex + ($hasHeaderRow ? 2 : 1),
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        return [
            'processed' => $processed,
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Convert a human‑readable value from the import file to the database format.
     *
     * @param string $field
     * @param mixed $value
     * @param array $definition
     * @return mixed
     */
    protected function resolveFieldValue(string $field, $value, array $definition, array &$rowErrors)
    {
        // Skip empty values
        if ($value === null || $value === '') {
            return null;
        }

        // 1. Relationship (belongsTo) – display name → ID
        if (isset($definition['relationship'])) {
            $relatedModel = $definition['relationship']['model'] ?? null;
            $displayField = $definition['relationship']['display_field'] ?? 'name';
            if ($relatedModel && class_exists($relatedModel)) {
                $related = $relatedModel::where($displayField, $value)->first();
                if ($related) {
                    return $related->id;
                } else {
                    $rowErrors[] = "Field '$field': No {$displayField} found with value '$value'";
                    return null;
                }
            }
        }

        // 2. Inline options (single select) – label → key
        if (!empty($definition['options']) && is_array($definition['options']) && !isset($definition['options']['model'])) {
            $options = $definition['options'];
            $isMulti = $definition['multiSelect'] ?? false;

            if ($isMulti) {
                // Multi‑select: value is comma‑separated labels → comma‑separated keys
                $labels = array_map('trim', explode(',', $value));
                $keys = [];
                foreach ($labels as $label) {
                    $key = array_search($label, $options);
                    if ($key !== false) {
                        $keys[] = $key;
                    }
                }
                return implode(',', $keys);
            } else {
                // Single select
                $key = array_search($value, $options);
                return $key !== false ? $key : $value;
            }
        }

        // 3. Everything else (plain string, number, etc.) – keep as is
        return $value;
    }
}