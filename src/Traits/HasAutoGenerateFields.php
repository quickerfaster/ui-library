<?php

namespace QuickerFaster\UILibrary\Traits;


use QuickerFaster\UILibrary\Services\ValueGenerator;

trait HasAutoGenerateFields
{
    public function generateField(string $fieldName)
    {
        $definition = $this->fieldDefinitions[$fieldName] ?? null;
        if (!$definition || !($definition['autoGenerate'] ?? false)) {
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
