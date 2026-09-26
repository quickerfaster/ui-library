<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\HasCurrencySymbol;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

/**
 * Renders monetary values with a currency symbol prefix.
 *
 * Resolution order for currency code:
 *  1. $definition['currency_code']  — static, set in data config
 *  2. $record->currency_code        — dynamic, from the model being displayed
 *  3. 'USD'                         — fallback
 *
 * Config example:
 *   'gross_pay' => [
 *       'field_type'    => 'currency',
 *       'label'         => 'Gross Pay',
 *       'currency_code' => 'NGN',   // optional static override
 *   ],
 */
class CurrencyField implements FieldType
{
    use HasBladeRendering, HasCurrencySymbol;

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function renderForm($value = null): string
    {
        return $this->renderBlade('qf::components.fields.text', [
            'field'  => $this,
            'value'  => $value,
            'name'   => $this->name,
            'label'  => $this->definition['label'] ?? ucfirst($this->name),
            'type'   => 'number',
            'step'   => $this->definition['step'] ?? '0.01',
            'customAttributes' => array_merge(
                $this->definition['attributes'] ?? [],
                ['type' => 'number', 'step' => $this->definition['step'] ?? '0.01']
            ),
        ]);
    }

    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        $wireModel = $extra['wire:model'] ?? 'editedData.' . $extra['rowId'] . '.' . $this->name;

        return $this->renderBlade('qf::components.fields.inline-editor.text', [
            'name'             => $this->name,
            'wireModel'        => $wireModel,
            'value'            => $value,
            'customAttributes' => array_merge(
                $this->definition['attributes'] ?? [],
                ['type' => 'number', 'step' => $this->definition['step'] ?? '0.01']
            ),
            'rowId'     => $extra['rowId'] ?? null,
            'fieldName' => $this->name,
        ]);
    }

    public function renderTable($value, $record): string
    {
        return $this->formatCurrency($value, $record);
    }

    public function renderDetail($value, $record): string
    {
        return $this->formatCurrency($value, $record);
    }

    /**
     * Format a numeric value with the resolved currency symbol.
     */
    protected function formatCurrency($value, $record): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-muted fst-italic">-</span>';
        }

        // Resolve currency code:
        // 1. Static from field definition
        // 2. Model's company's currency_code (most authoritative — company-level setting)
        // 3. Model's own currency_code (stored at creation time, may be stale)
        // 4. Fallback
        $currencyCode = $this->definition['currency_code'] ?? null;

        if (!$currencyCode && is_object($record) && method_exists($record, 'getAttribute')) {
            // Company currency takes priority over stored value
            if (method_exists($record, 'company')) {
                $company = $record->company;
                if ($company && method_exists($company, 'getAttribute')) {
                    $currencyCode = $company->currency_code ?? null;
                }
            }

            // Fall back to model's own currency_code
            if (!$currencyCode) {
                $currencyCode = $record->currency_code ?? null;
            }
        }

        $currencyCode = $currencyCode ?: 'USD';

        $symbol = $this->getCurrencySymbol($currencyCode);

        return e($symbol . number_format((float) $value, 2));
    }

    public function getValidationRules(): array
    {
        if (isset($this->definition['validation'])) {
            return [$this->name => $this->definition['validation']];
        }

        return [$this->name => 'nullable|numeric|min:0'];
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