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


    /**
     * Override getInitialOptions to return combined labels.
     */
    public function getInitialOptions($value): array
    {
        if (empty($value)) {
            return [];
        }

        if ($this->isRelationship()) {
            $rel = $this->definition['relationship'];
            $modelClass = $rel['model'];
            $displayField = $this->getDisplayField();
            $hintField = $this->getHintField();
            $multiple = $this->definition['multiSelect'] ?? false;

            $query = $modelClass::query();
            if ($multiple && is_array($value)) {
                $records = $query->whereIn('id', $value)->get();
                $result = [];
                foreach ($records as $record) {
                    $result[$record->id] = $this->getCombinedLabel($record);
                }
                return $result;
            } else {
                $record = $query->find($value);
                return $record ? [$record->id => $this->getCombinedLabel($record)] : [];
            }
        }

        // Static options – hint field not applicable
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
     * Get the hint field name (if any) from the config.
     */
    protected function getHintField(): ?string
    {
        // First check inside 'relationship' (preferred), then fallback to 'options'
        if ($this->isRelationship()) {
            return $this->definition['relationship']['hint_field'] ?? null;
        }
        return $this->definition['options']['hintField'] ?? null;
    }

    /**
     * Get the display field name.
     */
    protected function getDisplayField(): string
    {
        if ($this->isRelationship()) {
            return $this->definition['relationship']['display_field'] ?? 'name';
        }
        return $this->definition['options']['column'] ?? 'name';
    }

    /**
     * Build a combined label: "display (hint)" or just "display" if no hint.
     */
    protected function getCombinedLabel($model, $displayValue = null, $hintValue = null): string
    {
        if ($model && $this->isRelationship()) {
            $displayField = $this->getDisplayField();
            $displayValue = $model->$displayField ?? '';
            $hintField = $this->getHintField();
            $hintValue = $hintField ? ($model->$hintField ?? '') : null;
        }
        if (!empty($hintValue)) {
            return "{$displayValue} ({$hintValue})";
        }
        return $displayValue;
    }




    /**
     * Whether inline creation of new options is allowed.
     */
    public function canInlineAdd(): bool
    {
        if ($this->isRelationship()) {
            return $this->definition['relationship']['inlineAdd'] ?? false;
        }
        return false;
    }

    /**
     * Get the searchable fields array (if defined), otherwise null.
     */
    public function getSearchableFields(): ?array
    {
        if ($this->isRelationship()) {
            return $this->definition['relationship']['searchable_fields'] ?? null;
        }
        return null;
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
     * Override getLabelForValue (used in table/detail views).
     */
    protected function getLabelForValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        if ($this->isRelationship()) {
            $rel = $this->definition['relationship'];
            $modelClass = $rel['model'];
            $displayField = $this->getDisplayField();
            $hintField = $this->getHintField();
            $multiple = $this->definition['multiSelect'] ?? false;

            if ($multiple && is_array($value)) {
                $records = $modelClass::whereIn('id', $value)->get();
                $labels = [];
                foreach ($records as $record) {
                    $labels[] = $this->getCombinedLabel($record);
                }
                return implode(', ', $labels);
            } else {
                $record = $modelClass::find($value);
                return $record ? $this->getCombinedLabel($record) : e($value);
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