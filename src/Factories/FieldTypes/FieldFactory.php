<?php

namespace QuickerFaster\UILibrary\Factories\FieldTypes;

use QuickerFaster\UILibrary\Components\FieldTypes\ImageField;
use QuickerFaster\UILibrary\Components\FieldTypes\TextField;
use QuickerFaster\UILibrary\Components\FieldTypes\SelectField;
use QuickerFaster\UILibrary\Components\FieldTypes\DatepickerField;
use QuickerFaster\UILibrary\Components\FieldTypes\CheckboxField;
use QuickerFaster\UILibrary\Components\FieldTypes\ConditionalScopeField;
use QuickerFaster\UILibrary\Components\FieldTypes\DatetimepickerField;
use QuickerFaster\UILibrary\Components\FieldTypes\RadioField;
use QuickerFaster\UILibrary\Components\FieldTypes\FileField;
use QuickerFaster\UILibrary\Components\FieldTypes\TextareaField;
use QuickerFaster\UILibrary\Components\FieldTypes\LivewireSearchableSelectField;
use QuickerFaster\UILibrary\Components\FieldTypes\MorphToSelectField;
use QuickerFaster\UILibrary\Components\FieldTypes\TimepickerField;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;


class FieldFactory
{
    protected array $map = [
        'string'       => TextField::class,
        'text'         => TextareaField::class,   // maybe textarea
        'select'       => SelectField::class,
        'datepicker'   => DatepickerField::class,
        'timepicker'   => TimepickerField::class,
        'datetimepicker'   => DatetimepickerField::class,
        'checkbox'     => CheckboxField::class,
        'boolcheckbox' => CheckboxField::class,
        'boolradio'    => RadioField::class,
        'file'         => FileField::class,
        'image'        => ImageField::class,
        'photo'        => ImageField::class,
        'picture'      => ImageField::class,
        'textarea'     => TextareaField::class,
        'livewire-searchable-select' => LivewireSearchableSelectField::class,
        'morph_to_select' => MorphToSelectField::class,


    ];

    public function make(string $name, array $definition): FieldType
    {
        $type = $definition['field_type'] ?? 'string';
        $class = $this->map[$type] ?? TextField::class;

        return new $class($name, $definition);
    }
}








