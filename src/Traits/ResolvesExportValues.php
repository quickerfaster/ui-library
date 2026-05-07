<?php

namespace QuickerFaster\UILibrary\Traits;

use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

trait ResolvesExportValues
{
    protected function getFieldValueForExport($record, string $field, array $fieldDefinitions)
    {
        $definition = $fieldDefinitions[$field] ?? [];

        // Handle relationship fields
        if (isset($definition['relationship'])) {
            $relationMethod = $definition['relationship']['dynamic_property'] ?? null;
            if (!$relationMethod) {
                $relationMethod = \Illuminate\Support\Str::camel(str_replace('_id', '', $field));
            }
            $related = $record->$relationMethod;
            if ($related) {
                $displayField = $definition['relationship']['display_field'] ?? 'name';
                return data_get($related, $displayField);
            }
            return '';
        }

        // Direct attribute
        $value = data_get($record, $field);

        // Apply options mapping if present (non-relationship select/checkbox fields)
        if (!empty($definition['options']) && is_array($definition['options'])) {
            $isMulti = $definition['multiSelect'] ?? false;
            // Multi‑select stored as comma‑separated string
            if ($isMulti && is_string($value) && str_contains($value, ',')) {
                $keys = explode(',', $value);
                $labels = array_map(fn($k) => $definition['options'][trim($k)] ?? trim($k), $keys);
                return implode(', ', $labels);
            }
            // Already an array
            if (is_array($value)) {
                return implode(', ', array_map(fn($v) => $definition['options'][$v] ?? $v, $value));
            }
            // Single value
            return $definition['options'][$value] ?? $value;
        }

        return $value;
    }
}