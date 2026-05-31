<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class PasswordField implements FieldType
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
        return $this->renderBlade('qf::components.fields.password', [
            'field' => $this,
            'name' => $this->name,
            'label' => $this->getLabel(),
            'customAttributes' => $this->definition['attributes'] ?? [],
        ]);
    }

    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        $wireModel = $extra['wire:model'] ?? 'editedData.' . $extra['rowId'] . '.' . $this->name;
        return $this->renderBlade('qf::components.fields.inline-editor.password', [
            'name' => $this->name,
            'wireModel' => $wireModel,
            'customAttributes' => $this->definition['attributes'] ?? [],
            'rowId' => $extra['rowId'] ?? null,
            'fieldName' => $this->name,
        ]);
    }

    public function renderTable($value, $record): string
    {
        // Never show actual password in tables
        return '••••••';
    }

    public function renderDetail($value, $record): string
    {
        // Never show actual password in detail view
        return '••••••';
    }

    public function getValidationRules(): array
    {
        $rules = [];
        if (isset($this->definition['validation'])) {
            $rules[$this->name] = $this->definition['validation'];
        }
        
        return $rules;
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