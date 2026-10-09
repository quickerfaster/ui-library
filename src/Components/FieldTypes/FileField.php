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
        \Log::info('FileField.renderTable called', [
            'value' => $value,
            'record_class' => get_class($record),
            'record_id' => $record->id ?? 'none',
        ]);

        if (!$value) {
            return '<span class="text-muted small italic">None</span>';
        }

        // Detect if this record has a related owner via common relationship names
        $isDocument = (method_exists($record, 'subject') && $record->subject)
            || (method_exists($record, 'owner') && $record->owner)
            || (method_exists($record, 'user') && $record->user);

        if ($isDocument && $record->id) {
            $url = route('documents.download', $record->id);
            $filename = $record->name ?? basename($value);
        } else {
            // Fallback for public files (profile images, etc.)
            $url = asset('storage/' . $value);
            $filename = basename($value);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Truncate long filenames for display: "Very long document name...pdf"
        $displayName = $filename;
        $maxLen = 30;
        if (mb_strlen($displayName) > $maxLen) {
            $namePart = pathinfo($filename, PATHINFO_FILENAME);
            if (mb_strlen($namePart) > 20) {
                $displayName = mb_substr($namePart, 0, 17) . '...' . ($extension ? '.' . $extension : '');
            }
        }

        $icon = match ($extension) {
            'pdf' => 'fa-file-pdf',
            'xls', 'xlsx' => 'fa-file-excel',
            'doc', 'docx' => 'fa-file-word',
            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image',
            default => 'fa-file-alt',
        };

        // Use the preview modal (same pattern as ImageField) instead of
        // target="_blank" download links. The DocumentPreviewModal is
        // globally available in navigation-layout.blade.php.
        // Use e() for HTML attribute safety. The document-level
        // click delegation in navigation-layout reads these
        // attributes and dispatches the preview event.
        return '<a href="#" data-file-preview="' . e($url) . '" data-file-name="' . e($filename) . '"
                    class="d-inline-flex align-items-center gap-1 text-decoration-none stop-propagation"
                    style="cursor: pointer;">
                    <i class="fas ' . $icon . '"></i>
                    <span title="' . e($filename) . '">' . e($displayName) . '</span>
                </a>';
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
