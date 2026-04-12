<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;


use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasAutoGenerate;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;


class TextField implements FieldType
{
    use HasBladeRendering, HasAutoGenerate;

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

public function renderForm($value = null): string
{
    $view = ($this->definition['autoGenerate'] ?? false) 
        ? 'qf::components.fields.text-with-generate' 
        : 'qf::components.fields.text';

    return $this->renderBlade($view, [
        'field' => $this,
        'value' => $value,
        'name' => $this->name,
        'label' => $this->definition['label'] ?? ucfirst($this->name),
        'customAttributes' => $this->definition['attributes'] ?? [],
    ]);
}



    public function renderTable($value, $record): string
    {
        // Simple string output (maybe escape)
        return e($value);
    }

    public function renderDetail($value): string
    {
        return e($value);
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
        return [];
    }

    public function isRelationship(): bool
    {
        return isset($this->definition['relationship']);
    }

    public function getRelationshipConfig(): ?array
    {
        return $this->definition['relationship'] ?? null;
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
