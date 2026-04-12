<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class CheckboxField implements FieldType
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
        $multiple = $this->definition['multiSelect'] ?? false;

        if ($multiple) {
            // Render a group of checkboxes (options from relationship or static array)
            $options = $this->getOptions();
            return $this->renderBlade('qf::components.fields.checkbox-group', [
                'field' => $this,
                'value' => $value ?? [],
                'name' => $this->name,
                'label' => $this->definition['label'] ?? ucfirst($this->name),
                'options' => $options,
                'customAttributes' => $this->definition['attributes'] ?? [],
            ]);
        } else {
            // Single boolean checkbox
            return $this->renderBlade('qf::components.fields.checkbox', [
                'field' => $this,
                'value' => $value,
                'name' => $this->name,
                'label' => $this->definition['label'] ?? ucfirst($this->name),
                'customAttributes' => $this->definition['attributes'] ?? [],
            ]);
        }
    }

public function renderTable($value, $record): string
{
    $multiple = $this->definition['multiSelect'] ?? false;

    // If it's a relationship and the record has it loaded, use that.
    if ($this->isRelationship() && $record && $record->relationLoaded($this->name)) {
        $related = $record->{$this->name};
        $displayField = $this->definition['relationship']['display_field'] ?? 'name';

        if ($multiple) {
            // For belongsToMany/hasMany, $related is a collection
            return $related->pluck($displayField)->implode(', ');
        } else {
            // For belongsTo, $related is a single model
            return $related->$displayField ?? '';
        }
    }

    // Fallback to using options
    if ($multiple) {
        $options = $this->getOptions();
        $selectedKeys = is_string($value) ? explode(',', $value) : (array) $value;
        $labels = array_intersect_key($options, array_flip($selectedKeys));
        return implode(', ', $labels);
    }


    // Green badge for truthy values, Red for falsy
    $class = $value ? 'bg-success' : 'bg-danger';
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
        // Similar to SelectField
        if (isset($this->definition['options']) && is_array($this->definition['options'])) {
            if (array_is_list($this->definition['options'])) {
                return array_combine($this->definition['options'], $this->definition['options']);
            }
            return $this->definition['options'];
        }
        if (isset($this->definition['relationship'])) {
            $rel = $this->definition['relationship'];
            if (isset($rel['model']) && isset($rel['display_field'])) {
                $model = $rel['model'];
                $displayField = $rel['display_field'];
                if (class_exists($model)) {
                    return $model::pluck($displayField, 'id')->toArray();
                }
            }
        }
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
