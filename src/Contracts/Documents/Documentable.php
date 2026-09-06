<?php

namespace QuickerFaster\UILibrary\Contracts\Documents;

interface Documentable
{
    /**
     * Get the unique identifier for this documentable entity.
     */
    public function getDocumentableId(): int|string;

    /**
     * Get the document type key (e.g., 'contract', 'invoice').
     */
    public function getDocumentType(): string;

    /**
     * Get the storage folder path relative to the configured disk.
     */
    public function getDocumentStoragePath(): string;

    /**
     * Get template data for document generation.
     */
    public function getDocumentTemplateData(): array;
}