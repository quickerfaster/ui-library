<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class PolicyCalculationBuilderField implements FieldType
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
        // We need the current policy type from the parent form.
        // The parent Livewire component (DataTableForm) has a public $fields array.
        // We can pass the type via a temporary Livewire event listener.
        // But a simpler way: inject the type by reading it from the parent component's fields property.
        // However, we can't access the parent directly from here. So we'll use a static approach:
        // The DataTableForm must have a public property 'policyType' that we can reference? Not ideal.
        // Instead, we will rely on the fact that when this field is rendered, the parent already has the 'type' field.
        // We'll use a livewire `@this` trick to get the value. In the blade, we'll write:
        // `@php $type = @$this->fields['type'] ?? 'benefit'; @endphp`
        // Then pass it to the component. This is safe because the field is rendered inside the parent's context.

        return $this->renderBlade('qf::components.fields.policy-calculation-builder', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? 'Calculation Logic',
        ]);
    }

    public function renderTable($value, $record): string
    {
        // Show a summary: e.g., "Tax: 3 brackets" or "Benefit: 5%"
        if (empty($value)) return '—';
        $data = json_decode($value, true);
        if (!is_array($data)) return 'Invalid JSON';
        if (isset($data['bands'])) {
            $count = count($data['bands']);
            return "Tax: {$count} bracket(s)";
        }
        if (isset($data['type'])) {
            $type = $data['type'] === 'fixed' ? 'Fixed' : 'Percentage';
            $val = $data['value'] ?? 0;
            return "{$type}: {$val}" . ($data['type'] === 'percentage' ? '%' : '');
        }
        return '—';
    }

    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        return $this->renderComplexFallback($record, $extra, 'Edit Policy Logic');
    }

    public function renderDetail($value, $record): string
    {
        return $this->renderTable($value, $record);
    }

    public function getValidationRules(): array
    {
        // Validation will be handled by the custom component's output JSON structure.
        // We still need a basic rule to ensure it's valid JSON.
        return [$this->name => 'nullable|json'];

        /*return [
        $this->name => ['nullable', 'json', function ($attribute, $value, $fail) {
            $data = json_decode($value, true);
            if (!is_array($data)) {
                $fail('The calculation logic must be valid JSON.');
                return;
            }
            // Basic structural checks
            if (isset($data['bands'])) {
                if (!is_array($data['bands'])) {
                    $fail('The bands must be an array.');
                }
            } elseif (isset($data['type'])) {
                if (!in_array($data['type'], ['fixed', 'percentage'])) {
                    $fail('Calculation type must be "fixed" or "percentage".');
                }
                if (!isset($data['value']) || !is_numeric($data['value'])) {
                    $fail('A numeric value is required.');
                }
            } else {
                $fail('Calculation logic must contain either "bands" or "type"/"value".');
            }
        }],
    ];*/




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