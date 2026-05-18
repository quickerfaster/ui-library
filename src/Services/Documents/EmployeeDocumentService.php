<?php

namespace QuickerFaster\UILibrary\Services\Documents;

use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;



class EmployeeDocumentService
{
    protected int $maxDocuments;

    public function __construct(int $maxDocuments = 10)
    {
        $this->maxDocuments = $maxDocuments;
    }

public function canUpload(Employee $employee, ?int $excludeDocumentId = null): bool
{
    return $this->remainingSlots($employee, $excludeDocumentId) > 0;
}

public function remainingSlots(Employee $employee, ?int $excludeDocumentId = null): int
{
    // Clone prevents modifying the original relationship instance
    $query = clone $employee->documents();

    if ($excludeDocumentId) {
        $query->where('id', '!=', $excludeDocumentId);
    }

    $currentCount = $query->withTrashed()->count();

    return max(0, $this->maxDocuments - $currentCount);
}


    public function getStorageFolder(Employee $employee): string
    {
        return 'employee_documents/' . $employee->employee_number;
    }

    public function storeFile(Employee $employee, UploadedFile $file): string
    {
        $folder = $this->getStorageFolder($employee);
        $path = $file->store($folder, 'documents');
        if (!$path) {
            throw new \Exception('Failed to store document file.');
        }
        return $path;
    }

    public function deleteFile(Document $document): void
    {
        if ($document->document && Storage::disk('documents')->exists($document->document)) {
            Storage::disk('documents')->delete($document->document);
        }
    }
}