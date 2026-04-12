<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class MorphToSelectField implements FieldType
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
        $morphMap = $this->definition['morph_map'] ?? [];
        $displayField = $this->definition['display_field'] ?? 'name';

        return $this->renderBlade('qf::components.fields.morph-to-select', [
            'fieldName' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'morphMap' => $morphMap,
            'displayField' => $displayField,
        ]);
    }

    public function renderTable($value, $record): string
    {
        // For table view, we try to get the related model from the record if the relation is loaded.
        // The relation name is stored in 'morph_relation' in the definition.
        $relationName = $this->definition['morph_relation'] ?? null;
        if ($relationName && $record && $record->relationLoaded($relationName)) {
            $related = $record->$relationName;
            if ($related) {
                $displayField = $this->definition['display_field'] ?? 'name';
                return $related->$displayField ?? '';
            }
        }

        // Fallback: try to read from the compound value if stored as array
        if (is_array($value) && isset($value['type'], $value['id'])) {
            $morphMap = $this->definition['morph_map'] ?? [];
            $type = $value['type'];
            $id = $value['id'];
            if (isset($morphMap[$type])) {
                $modelClass = $morphMap[$type];
                $entity = $modelClass::find($id);
                $displayField = $this->definition['display_field'] ?? 'name';
                return $entity ? $entity->$displayField : $id;
            }
        }
        return '';
    }

    public function renderDetail($value): string
    {
        return $this->renderTable($value, null);
    }

    public function getValidationRules(): array
    {
        $rules = [];
        if (isset($this->definition['validation'])) {

            $rules[$this->name] = $this->definition['validation'];

        } else {
            // Default: must be an array with type and id
            $rules[$this->name] = 'required|array';
            $rules[$this->name . '.type'] = 'required|string|in:' . implode(',', array_keys($this->definition['morph_map'] ?? []));
            $rules[$this->name . '.id'] = 'required|integer|min:1';
        }
        return $rules;
    }



    public function getValidationMessages(): array
    {
        $fieldLabel = $this->definition['label'] ?? ucfirst($this->name);
        return [
            $this->name . '.type.required' => 'Please select a type (e.g., Company, Location).',
            $this->name . '.type.in' => 'The selected type is invalid.',
            $this->name . '.id.required' => "Please select a specific {$fieldLabel}.",
            $this->name . '.id.integer' => "The selected {$fieldLabel} must be a valid ID.",
            $this->name . '.id.min' => "Please select a {$fieldLabel}.",
        ];
    }




    public function getOptions(): array
    {
        return [];
    }

    public function isRelationship(): bool
    {
        return true; // It's a polymorphic relationship
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

    /**
     * Called during form save to transform the field value into actual foreign key fields.
     * This writes to assignable_type and assignable_id.
     */
    public function prepareForSave($value, array &$data): void
    {
        if (!is_array($value) || empty($value['type']) || empty($value['id'])) {
            $data['assignable_type'] = null;
            $data['assignable_id'] = null;
            return;
        }

        $typeKey = $value['type'];
        $morphMap = $this->definition['morph_map'] ?? [];

        // Convert type key to full model class
        $data['assignable_type'] = $morphMap[$typeKey] ?? $typeKey;
        $data['assignable_id'] = $value['id'];
    }
}