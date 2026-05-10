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
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'multiple' => $this->definition['multiSelect'] ?? false,
            'placeholder' => $this->definition['placeholder'] ?? 'Search...',
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


    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        return $this->renderComplexFallback($record, $extra, 'Select');
    }




    /**
 * Render the field for a table cell.
 */
public function renderTable($value, $record): string
{
    $relName = $this->definition["relationship"]["dynamic_property"] ?? '';
    
    // If it's a many-to-many relationship, show badges
    if ($this->isManyToMany() && $record && $record->relationLoaded($relName)) {
        $related = $record->{$relName}; // use property, not method
        
        if ($related && $related->count() > 0) {
            $badges = [];
            foreach ($related as $item) {
                $color = $item->color ?? 'secondary';
                $name = $item->{$this->getDisplayField()};
                $badges[] = "<span style = \" padding:0.3em 0.4em \" class=\"badge  badge-{$color} bg-gradient-{$color}\">{$name}</span>";
            }
            return implode(' ', $badges);
        }
        return '';
    }

    // Fallback to the existing label lookup
    return $this->getLabelForValue($value);
}

/**
 * Render the field for a detail view.
 */
public function renderDetail($value, $record): string
{
    // Same logic as table, but maybe with different styling? Reuse.
    return $this->renderTable($value, $record);
}



/**
 * Check if the field represents a many-to-many relationship.
 */
protected function isManyToMany(): bool
{
    $rel = $this->definition['relationship'] ?? null;
    if (!$rel) {
        return false;
    }
    $type = $rel['type'] ?? '';
    return in_array($type, ['belongsToMany', 'morphToMany']);
}

/**
 * Get the display field from relationship config.
 */
protected function getDisplayField(): string
{
    return $this->definition['relationship']['display_field'] ?? 'name';
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