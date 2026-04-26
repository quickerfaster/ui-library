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
        if (!$value)
            return '<span class="text-muted small italic">None</span>';

        $url = asset('storage/' . $value);
        $filename = basename($value);
        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        $fileStyle = match ($extension) {
            'pdf' => ['icon' => 'fa-file-pdf', 'color' => '#e74c3c'],
            'xls', 'xlsx' => ['icon' => 'fa-file-excel', 'color' => '#27ae60'],
            'doc', 'docx' => ['icon' => 'fa-file-word', 'color' => '#2980b9'],
            default => ['icon' => 'fa-file-alt', 'color' => '#7f8c8d'],
        };

        // Main Wrapper
        $html = '<div class="d-inline-flex align-items-center bg-light border rounded px-2 py-1 shadow-sm" style="max-width: 200px; cursor: pointer;" ';
        // Dispatching directly on the container click for better UX
        $html .= 'onclick="Livewire.dispatch(\'openDocumentPreview\', [{ fileUrl: \'' . $url . '\', fileName: \'' . $filename . '\' }])">';

        // Visual Indicator (Thumbnail or Icon)
        if ($isImage) {
            $html .= '<img src="' . $url . '" style="width: 20px; height: 20px; object-fit: cover;" class="rounded-sm me-2">';
        } else {
            $html .= '<i class="fas ' . $fileStyle['icon'] . ' me-2" style="color: ' . $fileStyle['color'] . ';"></i>';
        }

        // Filename
        $html .= '<span class="text-truncate small fw-medium text-dark" style="max-width: 120px;" title="' . $filename . '">' . $filename . '</span>';

        // Small Eye Icon to hint at preview
        $html .= '<i class="fas fa-eye ms-2 text-muted small" style="font-size: 0.75rem;"></i>';

        $html .= '</div>';

        return $html;
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
