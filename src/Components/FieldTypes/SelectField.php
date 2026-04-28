<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;
use Illuminate\Support\Facades\Log;

class SelectField implements FieldType
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

        $options = $this->getOptions();

        return $this->renderBlade('qf::components.fields.select', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'options' => $options,
            'multiple' => $this->definition['multiSelect'] ?? false,
            'placeholder' => $this->definition['placeholder'] ?? '-- Select --',
            'customAttributes' => $this->definition['attributes'] ?? [],
        ]);
    }



    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        $multiple = $this->definition['multiSelect'] ?? false;
        if ($multiple) {
            // Multi‑select is too complex for inline – fallback to drawer
            return $this->renderComplexFallback($record, $extra, 'Select values');
        }

        $wireModel = $extra['wire:model'] ?? 'editedData.' . $extra['rowId'] . '.' . $this->name;
        return $this->renderBlade('qf::components.fields.inline-editor.select', [
            'name' => $this->name,
            'wireModel' => $wireModel,
            'value' => $value,
            'options' => $this->getOptions(),
            'placeholder' => $this->definition['placeholder'] ?? null,
        ]);
    }



    public function renderTable($value, $record): string
    {
        // For a select, we usually want to show the label, not the raw value.
        $options = $this->getOptions();
        return $options[$value] ?? e($value);
    }

    public function renderDetail($value, $record): string
    {
        $options = $this->getOptions();
        return $options[$value] ?? e($value);
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


        // If relationship is defined, load options from related model.
        /*if (isset($this->definition['relationship'])) {
            $rel = $this->definition['relationship'];
            if (isset($rel['model']) && isset($rel['display_field'])) {
                $model = $rel['model'];
                $displayField = $rel['display_field'];

                if (class_exists($model)) {
                    // Support dot notation like 'jobTitle.title'
                    if (str_contains($displayField, '.')) {
                        [$relation, $column] = explode('.', $displayField, 2);
                        return $model::with($relation)
                            ->get()
                            ->pluck($relation . '.' . $column, 'id')
                            ->toArray();
                    }
                    return $model::pluck($displayField, 'id')->toArray();
                }
            }
        }*/


        if (isset($this->definition['options']['model'])) {
            $opt = $this->definition['options'];
            $model = $opt['model'];
            $column = $opt['column'] ?? 'name';
            $hintField = $opt['hintField'] ?? null;

            if (class_exists($model)) {
                // Fetch only necessary columns for performance
                $query = $model::select('id', $column);
                if ($hintField) {
                    $query->addSelect($hintField);
                }

                return $query->get()->mapWithKeys(function ($item) use ($column, $hintField) {
                    $label = $item->{$column};

                    // If hintField exists, format as "email (Name)" or "Name (email)"
                    if ($hintField && $item->{$hintField}) {
                        $label .= " ({$item->{$hintField} })";
                    }

                    return [$item->id => $label];
                })->toArray();
            }
        }










        // If options are already provided in definition, use them.
        if (isset($this->definition['options']) && is_array($this->definition['options'])) {
            // Check if it's a simple key-value array
            if (array_is_list($this->definition['options'])) {
                // It's a list, we need to create key-value where key = value
                return array_combine($this->definition['options'], $this->definition['options']);
            }
            return $this->definition['options'];
        }


        // If no options found, return empty array.
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
