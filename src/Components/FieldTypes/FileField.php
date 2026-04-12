<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class FileField implements FieldType
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
    $isImage = $this->definition['preview'] ?? false; // optional: treat as image for preview
    return $this->renderBlade('qf::components.fields.file', [
        'field' => $this,
        'value' => $value,
        'name' => $this->name,
        'label' => $this->definition['label'] ?? ucfirst($this->name),
        'accept' => $this->definition['accept'] ?? '*',
        'multiple' => $this->definition['multiple'] ?? false,
        'customAttributes' => $this->definition['attributes'] ?? [],
        'isImage' => $isImage,
    ]);
}

    public function renderTable($value, $record): string
    {
        if ($value) {
            $url = asset('storage/' . $value);
            return '<a href="' . $url . '" target="_blank">View</a>';
        }
        return '';
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
