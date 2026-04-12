<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class TimepickerField implements FieldType
{
    use HasBladeRendering;

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function renderForm($value = null): string
    {
        return $this->renderBlade('qf::components.fields.timepicker', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->getLabel(),
            'customAttributes' => $this->definition['attributes'] ?? [],
        ]);
    }

    public function renderTable($value, $record): string
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('H:i');
        }
        return e($value);
    }

    public function renderDetail($value): string
    {
        return $this->renderTable($value, null);
    }

    public function getValidationRules(): array
    {
        return isset($this->definition['validation']) ? [$this->name => $this->definition['validation']] : [];
    }

    public function getOptions(): array
    {
        return [];
    }

    public function isRelationship(): bool
    {
        return false;
    }

    public function getRelationshipConfig(): ?array
    {
        return null;
    }

    public function getLabel(): string
    {
        return $this->definition['label'] ?? ucfirst($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
