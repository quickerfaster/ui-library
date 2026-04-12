<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class ImageField implements FieldType
{
    use HasBladeRendering;

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    /**
     * Render form input – uses the same file input as FileField,
     * but could be enhanced with a preview.
     */
public function renderForm($value = null): string
{
    return $this->renderBlade('qf::components.fields.image', [
        'field' => $this,
        'value' => $value,
        'name' => $this->name,
        'label' => $this->definition['label'] ?? ucfirst($this->name),
        'accept' => $this->definition['accept'] ?? 'image/*',
        'multiple' => $this->definition['multiple'] ?? false,
        'customAttributes' => $this->definition['attributes'] ?? [],
    ]);
}

    /**
     * Render table cell – show a thumbnail image.
     */
public function renderTable($value, $record): string
{
    if (!$value) {
        return '';
    }

    $width = $this->definition['thumbnail_width'] ?? 50;
    $height = $this->definition['thumbnail_height'] ?? 50;
    $url = asset('storage/' . $value);
    $fileName = basename($value);

    // FIX: Wrapped parameters inside the 'payload' key
    $imgTag = sprintf(
        '<img src="%s" width="%d" height="%d" style="object-fit: cover; cursor: pointer;" class="img-thumbnail" 
              onclick="Livewire.dispatch(\'openDocumentPreview\', { payload: { fileUrl: \'%s\', fileName: \'%s\' } })">',
        e($url),
        $width,
        $height,
        e($url),
        e($fileName)
    );

    return $imgTag;
}


    /**
     * Render detail view – show a larger image (optional).
     */
public function renderDetail($value): string
{
    if (!$value) {
        return '';
    }

    $width = $this->definition['detail_width'] ?? 200;
    $height = $this->definition['detail_height'] ?? 200;
    $url = asset('storage/' . $value);
    $fileName = basename($value);

    // FIX: Wrapped parameters inside a 'payload' key to match the Modal's requirement
    $imgTag = sprintf(
        '<img src="%s" width="%d" height="%d" style="object-fit: contain; cursor: pointer;" class="img-fluid" 
        onclick="Livewire.dispatch(\'openDocumentPreview\', { payload: { fileUrl: \'%s\', fileName: \'%s\' } })">',
        e($url),
        $width,
        $height,
        e($url),
        e($fileName)
    );

    return $imgTag;
}


    public function getValidationRules(): array
    {
        if (isset($this->definition['validation'])) {
            return [$this->name => $this->definition['validation']];
        }
        // Default validation for images
        return [$this->name => 'nullable|image|max:2048'];
    }

    public function getOptions(): array
    {
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