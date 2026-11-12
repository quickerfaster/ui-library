<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;

class DocumentPreview extends Component
{
    public string $fileUrl;
    public ?string $fileName = null;
    public ?string $fileMime = null;
    public string $fileType = 'unknown';
    public ?string $error = null;

    public function mount(string $fileUrl, ?string $fileName = null, ?string $fileMime = null): void
    {
        $this->fileUrl = $fileUrl;
        $this->fileName = $fileName;
        $this->fileMime = $fileMime;
        $this->detectFileType();
    }

    protected function detectFileType(): void
    {
        // 1. Client-side base64 data URL — detect from MIME inside the URL.
        if (str_starts_with($this->fileUrl, 'data:')) {
            $this->detectDataUrlType();
            return;
        }

        // 2. Blob URLs have no path or extension, so fall back to the
        //    original file name / MIME supplied by the upload field.
        if (str_starts_with($this->fileUrl, 'blob:')) {
            $this->assignTypeFromExtension($this->resolveBlobExtension());
            return;
        }

        // 3. Normal file URL — detect by extension.
        $extension = strtolower(pathinfo($this->fileUrl, PATHINFO_EXTENSION));
        $this->assignTypeFromExtension($extension);
    }

    protected function detectDataUrlType(): void
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $this->fileUrl)) {
            $this->fileType = 'image';
        } elseif (str_contains($this->fileUrl, 'application/pdf')) {
            $this->fileType = 'pdf';
        } elseif (str_contains($this->fileUrl, 'wordprocessingml') || str_contains($this->fileUrl, 'application/msword')) {
            $this->fileType = 'office';
        } elseif (str_contains($this->fileUrl, 'spreadsheetml') || str_contains($this->fileUrl, 'ms-excel')) {
            $this->fileType = 'office';
        } elseif (str_contains($this->fileUrl, 'text/plain')) {
            $this->fileType = 'text';
        } else {
            $this->fileType = 'unsupported';
        }
    }

    protected function resolveBlobExtension(): string
    {
        $mime = strtolower((string) $this->fileMime);

        if (str_starts_with($mime, 'image/')) {
            return 'jpg';
        }
        if ($mime === 'application/pdf') {
            return 'pdf';
        }
        if (str_contains($mime, 'wordprocessingml') || $mime === 'application/msword') {
            return 'docx';
        }
        if (str_contains($mime, 'spreadsheetml') || $mime === 'application/vnd.ms-excel') {
            return 'xlsx';
        }
        if (str_starts_with($mime, 'text/')) {
            return 'txt';
        }

        return strtolower(pathinfo((string) $this->fileName, PATHINFO_EXTENSION));
    }

    protected function assignTypeFromExtension(string $extension): void
    {
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
