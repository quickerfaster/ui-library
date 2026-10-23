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
use QuickerFaster\UILibrary\Components\FieldTypes\CurrencyField;
use QuickerFaster\UILibrary\Components\FieldTypes\LivewireSearchableSelectField;
use QuickerFaster\UILibrary\Components\FieldTypes\MorphToSelectField;
use QuickerFaster\UILibrary\Components\FieldTypes\PasswordField;
use QuickerFaster\UILibrary\Components\FieldTypes\PolicyCalculationBuilderField;
use QuickerFaster\UILibrary\Components\FieldTypes\RichtextField;
use QuickerFaster\UILibrary\Components\FieldTypes\TimepickerField;
use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;


class FieldFactory
{
    /**
     * Built-in field type → class mappings.
     */
    protected array $map = [
        'string'       => TextField::class,
        'text'         => TextareaField::class,
        'select'       => SelectField::class,
        'date'         => DatepickerField::class,
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
        'richtext'     => RichtextField::class,
        'livewire-searchable-select' => LivewireSearchableSelectField::class,
        'morph_to_select' => MorphToSelectField::class,
        'password'     => PasswordField::class,
        'policy_calculation_builder' => PolicyCalculationBuilderField::class,
        'currency'                   => CurrencyField::class,
    ];

    /**
     * Custom field types registered by consuming app modules.
     * @var array<string, class-string<FieldType>>
     */
    protected static array $customTypes = [];

    /**
     * Register a custom field type from a consuming app module.
     *
     * Call this in your module's ServiceProvider::boot():
     *   FieldFactory::registerType('recurrence_rule_builder', RecurrenceRuleBuilderField::class);
     *
     * @param string $name  The field_type value used in DataTable configs.
     * @param string $class A FieldType implementation class name.
     */
    public static function registerType(string $name, string $class): void
    {
        static::$customTypes[$name] = $class;
    }

    public function make(string $name, array $definition): FieldType
    {
        $type = $definition['field_type'] ?? 'string';
        $class = $this->map[$type]
            ?? static::$customTypes[$type]
            ?? TextField::class;

        return new $class($name, $definition);
    }
}








