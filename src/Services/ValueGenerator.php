<?php

namespace QuickerFaster\UILibrary\Services;

class ValueGenerator
{
    public function generate(string $modelClass, string $fieldName, array $fieldDef, $recordId = null)
    {
        $generatorDef = $fieldDef['generator'] ?? [];
        $pattern = $generatorDef['pattern'] ?? $this->defaultPattern($modelClass, $fieldName);

        $now = now();

        // Base replacements
        $replacements = [
            '{year}' => $now->format('Y'),
            '{year:2}' => $now->format('y'),
            '{month}' => $now->format('m'),
            '{month:short}' => $now->format('M'),
            '{day}' => $now->format('d'),
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
        // Derive a pattern-specific sequence name so each unique pattern
        // (e.g. per-company employee number formats) gets its own
        // independent counter starting from 1.
        $pattern = $generatorDef['pattern'] ?? $this->defaultPattern($modelClass, $fieldName);
        $sequenceName = $fieldName . '_' . md5($pattern);

        // Use the atomic sequence table if it exists (guarantees no collisions).
        // The UPDATE acquires a row-level lock, preventing concurrent reads
        // from getting the same value.
        if (\Schema::hasTable('employee_number_sequence')) {
            // Auto-create the row for new patterns on first use
            if (\DB::table('employee_number_sequence')->where('name', $sequenceName)->doesntExist()) {
                \DB::table('employee_number_sequence')->insert([
                    'name'          => $sequenceName,
                    'current_value' => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            \DB::update(
                'UPDATE employee_number_sequence SET current_value = current_value + 1, updated_at = ? WHERE name = ?',
                [now(), $sequenceName]
            );

            return (int) \DB::table('employee_number_sequence')
                ->where('name', $sequenceName)
                ->value('current_value');
        }

        // Fallback: legacy MAX-based approach (kept for backward compatibility
        // if the sequence table hasn't been migrated yet).
        $sequenceModel = $generatorDef['sequenceModel'] ?? $modelClass;
        $sequenceField = $generatorDef['sequenceField'] ?? $fieldName;

        $max = $sequenceModel::withoutCompanyScope()->max($sequenceField);
        if ($max) {
            preg_match('/(\d+)$/', $max, $matches);
            return isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        }
        return 1;
    }
}