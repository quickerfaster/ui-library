<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'name',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'document_type',
        'disk',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function getDownloadUrl(): string
    {
        return route('documents.download', ['document' => $this->id]);
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk($this->disk)->exists($this->file_path)) {
            Storage::disk($this->disk)->delete($this->file_path);
        }
    }

    protected static function booted(): void
    {
        static::forceDeleting(function ($document) {
            $document->deleteFile();
        });
    }
}