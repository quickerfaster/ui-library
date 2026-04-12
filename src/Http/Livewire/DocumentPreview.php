<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;

class DocumentPreview extends Component
{
    public string $fileUrl;
    public string $fileType = 'unknown';
    public ?string $error = null;

    public function mount(string $fileUrl): void
    {
        $this->fileUrl = $fileUrl;
        $this->detectFileType();
    }

protected function detectFileType(): void
{
    // 1. Check for data URL (client‑side preview)
    if (str_starts_with($this->fileUrl, 'data:')) {
        // Determine type from MIME
        if (preg_match('/^data:image\/(\w+);base64,/', $this->fileUrl)) {
            $this->fileType = 'image';
        } elseif (str_contains($this->fileUrl, 'application/pdf')) {
            $this->fileType = 'pdf';
        } elseif (str_contains($this->fileUrl, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
            $this->fileType = 'office'; // docx
        } elseif (str_contains($this->fileUrl, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
            $this->fileType = 'office'; // xlsx
        } elseif (str_contains($this->fileUrl, 'application/vnd.ms-excel')) {
            $this->fileType = 'office'; // xls (older)
        } elseif (str_contains($this->fileUrl, 'application/msword')) {
            $this->fileType = 'office'; // doc (older)
        } elseif (str_contains($this->fileUrl, 'text/plain')) {
            $this->fileType = 'text';
        } else {
            $this->fileType = 'unsupported';
        }
        return;
    }

    // 2. Normal file URL – detect by extension
    $extension = strtolower(pathinfo($this->fileUrl, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    $pdfExtensions = ['pdf'];
    $textExtensions = ['txt', 'md', 'csv', 'json', 'xml', 'log'];
    $officeExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    if (in_array($extension, $imageExtensions)) {
        $this->fileType = 'image';
    } elseif (in_array($extension, $pdfExtensions)) {
        $this->fileType = 'pdf';
    } elseif (in_array($extension, $textExtensions)) {
        $this->fileType = 'text';
    } elseif (in_array($extension, $officeExtensions)) {
        $this->fileType = 'office';
    } else {
        $this->fileType = 'unsupported';
    }
}

    public function render()
    {
        return view('qf::livewire.documents.document-preview', [
            'fileType' => $this->fileType,
            'fileUrl' => $this->fileUrl,
        ]);
    }
}