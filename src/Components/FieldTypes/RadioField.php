<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class RadioField implements FieldType
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
        // Options for radio can come from definition or be default Yes/No
        $options = $this->getOptions();
        if (empty($options)) {
            $options = [1 => 'Yes', 0 => 'No'];
        }

        return $this->renderBlade('qf::components.fields.radio', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'options' => $options,
            'customAttributes' => $this->definition['attributes'] ?? [],
        ]);
    }


    public function renderTable($value, $record): string
    {
        $options = $this->getOptions();

        if (isset($options[$value])) {
            return $options[$value];
        }

        // Green badge for truthy values, Red for falsy
        $class = $value ? 'bg-gradient-success' : 'bg-gradient-danger';
        $label = $value ? 'Yes' : 'No';

        return "<span class=\"badge {$class}\">{$label}</span>";
    }


    public function renderDetail($value): string
    {
        return $this->renderTable($value, null);
    }

    public function getValidationRules(): array
    {
        if (isset($this->definition['validation'])) {
            return [$this->name => $this->definition['validation']];
        }
        return [];
    }

    public function getOptions(): array
    {
        if (isset($this->definition['options']) && is_array($this->definition['options'])) {
            return $this->definition['options'];
        }
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
