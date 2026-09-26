<?php

namespace QuickerFaster\UILibrary\Traits;


use QuickerFaster\UILibrary\Services\ValueGenerator;

trait HasAutoGenerateFields
{
    /**
     * Generate a value for an auto-generate field.
     *
     * If the field already has a non-empty value, it is NOT overwritten.
     * This prevents the sequence from being consumed again on Livewire
     * re-renders (e.g. after a validation failure) when the generated
     * value is still valid and unused.
     *
     * The "Generate" button in the UI still calls this method, but the
     * guard prevents accidental double-generation.
     */
    public function generateField(string $fieldName)
    {
        $definition = $this->getConfigResolver()->getSettingsOverrideFieldDefinition($fieldName);
        if (!$definition || !($definition['autoGenerate'] ?? false)) {
            return;
        }

        // Do not regenerate if the field already has a value.
        // This prevents burning sequence numbers on Livewire re-renders
        // and ensures the user's manually entered value is not overwritten.
        if (!empty($this->fields[$fieldName])) {
            return;
        }

        $generator = app(ValueGenerator::class);
        $newValue = $generator->generate(
            $this->getConfigResolver()->getModel(),
            $fieldName,
            $definition,
            $this->recordId // if editing, we have the record; if adding, it's null
        );

        if ($newValue !== null) {
            $this->fields[$fieldName] = $newValue;
        }
    }

}
