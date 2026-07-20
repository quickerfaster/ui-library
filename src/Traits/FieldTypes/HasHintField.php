<?php

namespace QuickerFaster\UILibrary\Traits\FieldTypes;

trait HasHintField
{
    /**
     * Parse a hint field definition into an array of column names.
     * Supports: string "first_name,last_name" or array ['first_name','last_name'].
     */
    protected function parseHintFields($hintField): array
    {
        if (empty($hintField)) {
            return [];
        }
        if (is_array($hintField)) {
            return $hintField;
        }
        // Comma-separated string
        return array_map('trim', explode(',', $hintField));
    }

    
}