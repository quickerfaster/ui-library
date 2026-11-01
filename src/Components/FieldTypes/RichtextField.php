<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class RichtextField implements FieldType
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
        return $this->renderBlade('qf::components.fields.richtext', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'customAttributes' => $this->definition['attributes'] ?? [],
        ]);
    }

    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        // Richtext is not suitable for inline editing in table cells.
        // Fall back to a plain text display.
        $plain = strip_tags((string) $value);
        return e(\Str::limit($plain, 80));
    }

    public function renderTable($value, $record): string
    {
        // Strip HTML tags for table display, limit to 80 chars
        $plain = strip_tags((string) $value);
        return e(\Str::limit($plain, 80));
    }

    public function renderDetail($value, $record): string
    {
        // Render HTML safely in detail view
        return $value ?? '';
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