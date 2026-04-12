<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class LivewireSearchableSelectField implements FieldType
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

    return $this->renderBlade('qf::components.fields.livewire-searchable-select', [
        'field'      => $this,
        'value'      => $value,
        'name'       => $this->name,
        'label'      => $this->definition['label'] ?? ucfirst($this->name),
        'multiple'   => $this->definition['multiSelect'] ?? false,
        'placeholder'=> $this->definition['placeholder'] ?? 'Search...',
    ]);
}

public function getInitialOptions($value): array
{
    if (empty($value)) {
        return [];
    }

    if ($this->isRelationship()) {
        $rel = $this->definition['relationship'];
        $model = $rel['model'];
        $displayField = $rel['display_field'] ?? 'name';
        $multiple = $this->definition['multiSelect'] ?? false;

        if ($multiple && is_array($value)) {
            return $model::whereIn('id', $value)->pluck($displayField, 'id')->toArray();
        } else {
            $item = $model::find($value);
            return $item ? [$value => $item->$displayField] : [];
        }
    }

    if (isset($this->definition['options'])) {
        $options = $this->definition['options'];
        if (is_array($value)) {
            return array_intersect_key($options, array_flip($value));
        } else {
            return isset($options[$value]) ? [$value => $options[$value]] : [];
        }
    }

    return [];
}


    

public function renderTable($value, $record): string
{
    // If the relation is already loaded, use it.
    if ($this->isRelationship() && $record && $record->relationLoaded($this->name)) {
        $related = $record->{$this->name};
        $displayField = $this->definition['relationship']['display_field'] ?? 'name';
        return $related->$displayField ?? e($value);
    }

    // Fallback: try to get the label from the relationship or static options.
    return $this->getLabelForValue($value);
}

public function renderDetail($value): string
{
    // For detail view, we may not have the record, so we always fetch the label.
    return $this->getLabelForValue($value);
}

/**
 * Get the human‑readable label for a given value (ID or array of IDs).
 */
protected function getLabelForValue($value): string
{
    if (empty($value)) {
        return '';
    }

    // Relationship lookup
    if ($this->isRelationship()) {
        $rel = $this->definition['relationship'];
        $model = $rel['model'];
        $displayField = $rel['display_field'] ?? 'name';

        $multiple = $this->definition['multiSelect'] ?? false;

        if ($multiple && is_array($value)) {
            $items = $model::whereIn('id', $value)->pluck($displayField, 'id')->toArray();
            return implode(', ', $items);
        } else {
            $item = $model::find($value);
            return $item ? $item->$displayField : e($value);
        }
    }

    // Static options
    if (isset($this->definition['options'])) {
        $options = $this->definition['options'];
        if (is_array($value)) {
            $labels = array_intersect_key($options, array_flip($value));
            return implode(', ', $labels);
        } else {
            return $options[$value] ?? e($value);
        }
    }

    return e($value);
}







    public function getValidationRules(): array
    {
        return isset($this->definition['validation'])
            ? [$this->name => $this->definition['validation']]
            : [];
    }

    public function getOptions(): array
    {
        // Return all options? Not needed for this field type.
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