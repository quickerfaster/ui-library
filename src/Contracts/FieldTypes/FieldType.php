<?php

namespace QuickerFaster\UILibrary\Contracts\FieldTypes;

interface FieldType
{
    public function __construct(string $name, array $definition);

    /**
     * Render the field for a form (input, select, etc.)
     */
    public function renderForm($value = null): string;

    /**
     * Render the field value for a table cell
     */
    public function renderTable($value, $record): string;

    /**
     * Render the field value for a detail view
     */
    public function renderDetail($value, $record): string;

    /**
     * Get validation rules for this field
     */
    public function getValidationRules(): array;

    // public function getValidationMessages(): array;

    /**
     * Get options for select, radio, etc.
     */
    public function getOptions(): array;

    /**
     * Whether this field represents a relationship
     */
    public function isRelationship(): bool;

    /**
     * If relationship, return its configuration
     */
    public function getRelationshipConfig(): ?array;

    /**
     * Get the field's label
     */
    public function getLabel(): string;

    /**
     * Get the field's name
     */
    public function getName(): string;

        /**
     * Render an inline editor for use inside a table cell.
     * Should output only the input/select/etc. element, no label, no form-group div.
     *
     * @param mixed $value Current value
     * @param mixed $record Record object (useful for relationships)
     * @param array $extra Extra parameters (e.g., 'wire:model.live' binding)
     * @return string
     */
    public function renderInlineEditor($value, $record, array $extra = []): string;
}
