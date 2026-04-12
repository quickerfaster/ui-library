<?php

namespace QuickerFaster\UILibrary\Traits\FieldTypes;

trait HasAutoGenerate
{
    protected function renderAutoGenerateButton(): string
    {
        if (!($this->definition['autoGenerate'] ?? false)) {
            return '';
        }

        return view('qf::components.fields.generate-button', [
            'fieldName' => $this->name,
            'label' => 'Generate',
        ])->render();
    }
}