<?php

namespace QuickerFaster\UILibrary\Services\Documents;

use QuickerFaster\UILibrary\Contracts\Documents\Documentable;
use QuickerFaster\UILibrary\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class DocumentEngine
{
    protected string $defaultDisk;

    public function __construct()
    {
        $this->defaultDisk = config('ui-library.documents.disk', 'public');
    }

    /**
     * Upload a file and attach it to a documentable entity.
     */
    public function upload(Documentable $entity, UploadedFile $file, ?string $name = null): Document
    {
        $path = $file->store($entity->getDocumentStoragePath(), $this->defaultDisk);

        return Document::create([
            'documentable_type' => get_class($entity),
            'documentable_id' => $entity->getDocumentableId(),
            'name' => $name ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'document_type' => $entity->getDocumentType(),
            'disk' => $this->defaultDisk,
        ]);
    }

    /**
     * Generate a PDF document from a Blade template and attach it.
     */
    public function generatePdf(Documentable $entity, string $template, string $fileName, array $data = []): Document
    {
        $mergedData = array_merge($entity->getDocumentTemplateData(), $data);
        $pdf = Pdf::loadView($template, $mergedData);
        
        $folder = $entity->getDocumentStoragePath();
        $filePath = $folder . '/' . $fileName;
        
        Storage::disk($this->defaultDisk)->put($filePath, $pdf->output());

        return Document::create([
            'documentable_type' => get_class($entity),
            'documentable_id' => $entity->getDocumentableId(),
            'name' => $fileName,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => 'application/pdf',
            'size' => Storage::disk($this->defaultDisk)->size($filePath),
            'document_type' => $entity->getDocumentType(),
            'disk' => $this->defaultDisk,
        ]);
    }

    /**
     * Generate an Excel document and attach it.
     */
    public function generateExcel(Documentable $entity, $export, string $fileName): Document
    {
        $folder = $entity->getDocumentStoragePath();
        $filePath = $folder . '/' . $fileName;
        
        Excel::store($export, $filePath, $this->defaultDisk);

        return Document::create([
            'documentable_type' => get_class($entity),
            'documentable_id' => $entity->getDocumentableId(),
            'name' => $fileName,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size' => Storage::disk($this->defaultDisk)->size($filePath),
            'document_type' => $entity->getDocumentType(),
            'disk' => $this->defaultDisk,
        ]);
    }

    /**
     * Get all documents for a documentable entity.
     */
    public function getDocuments(Documentable $entity)
    {
        return Document::where('documentable_type', get_class($entity))
            ->where('documentable_id', $entity->getDocumentableId())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete a document and its file.
     */
    public function delete(Document $document): void
    {
        $document->deleteFile();
        $document->delete();
    }

    /**
     * Get the configured storage disk.
     */
    public function getDisk(): string
    {
        return $this->defaultDisk;
    }
}