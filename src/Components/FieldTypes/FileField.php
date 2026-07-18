<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;

class FileField implements FieldType
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
        $isImage = $this->definition['preview'] ?? false; // optional: treat as image for preview
        return $this->renderBlade('qf::components.fields.file', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'accept' => $this->definition['accept'] ?? '*',
            'multiple' => $this->definition['multiple'] ?? false,
            'customAttributes' => $this->definition['attributes'] ?? [],
            'isImage' => $isImage,
        ]);
    }



    public function renderTable($value, $record): string
{
    if (!$value) {
        return '<span class="text-muted small italic">None</span>';
    }

    // Detect if this is a Document model (has an 'employee' relationship)
    $isDocument = method_exists($record, 'employee') && $record->employee;
    
    if ($isDocument && $record->id) {
        $url = route('documents.download', $record->id);
        $filename = $record->name ?? basename($value);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $icon = match($extension) {
            'pdf' => 'fa-file-pdf',
            'xls', 'xlsx' => 'fa-file-excel',
            'doc', 'docx' => 'fa-file-word',
            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image',
            default => 'fa-file-alt',
        };
        
        return '<a href="' . $url . '" target="_blank" class="d-inline-flex align-items-center gap-1 text-decoration-none">
                    <i class="fas ' . $icon . '"></i>
                    <span>' . e($filename) . '</span>
                    <i class="fas fa-download ms-1 small"></i>
                </a>';
    }
    
    // Fallback for public files (profile images, etc.)
    $url = asset('storage/' . $value);
    $filename = basename($value);
    return '<a href="' . $url . '" target="_blank">' . e($filename) . '</a>';
}




    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        return $this->renderComplexFallback($record, $extra, 'Upload file');
    }



    public function renderDetail($value, $record): string
    {
        return $this->renderTable($value, $record);
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
