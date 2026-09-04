<?php

namespace QuickerFaster\UILibrary\Traits\Documents;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use QuickerFaster\UILibrary\Models\Document;
use QuickerFaster\UILibrary\Services\Documents\DocumentEngine;

trait HasDocuments
{
    /**
     * Get all documents attached to this model.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Upload a document and attach it to this model.
     */
    public function uploadDocument(UploadedFile $file, ?string $name = null): Document
    {
        return app(DocumentEngine::class)->upload($this, $file, $name);
    }

    /**
     * Get all documents for this model via DocumentEngine.
     */
    public function getDocuments()
    {
        return app(DocumentEngine::class)->getDocuments($this);
    }

    /**
     * Delete a document via DocumentEngine.
     */
    public function deleteDocument(Document $document): void
    {
        app(DocumentEngine::class)->delete($document);
    }
}