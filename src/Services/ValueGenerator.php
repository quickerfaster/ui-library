<?php

namespace QuickerFaster\UILibrary\Services;

class ValueGenerator
{
    public function generate(string $modelClass, string $fieldName, array $fieldDef, $recordId = null)
    {
        $generatorDef = $fieldDef['generator'] ?? [];
        $pattern = $generatorDef['pattern'] ?? $this->defaultPattern($modelClass, $fieldName);

        // Base replacements
        $replacements = [
            '{year}' => now()->format('Y'),
            '{month}' => now()->format('m'),
            '{day}' => now()->format('d'),
            '{id}' => $recordId ?? 'NEW',
        ];

        // Handle {sequence} and {sequence:pad}
        preg_match('/\{sequence(?::(\d+))?\}/', $pattern, $matches);
        if (!empty($matches[0])) {
            $padLength = $matches[1] ?? 5; // default padding
            $sequence = $this->getNextSequence($modelClass, $fieldName, $generatorDef);
            $replacements[$matches[0]] = str_pad($sequence, $padLength, '0', STR_PAD_LEFT);
        }

        // Apply all replacements
        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    protected function defaultPattern($modelClass, $fieldName)
    {
        return strtoupper(class_basename($modelClass)) . '-{year}-{sequence:5}';
    }

    protected function getNextSequence($modelClass, $fieldName, $generatorDef)
    {
        $sequenceModel = $generatorDef['sequenceModel'] ?? $modelClass;
        $sequenceField = $generatorDef['sequenceField'] ?? $fieldName;

        $max = $sequenceModel::max($sequenceField);
        if ($max) {
            preg_match('/(\d+)$/', $max, $matches);
            return isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        }
        return 1;
    }
}