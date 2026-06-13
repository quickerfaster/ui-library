<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportChunk extends Model
{
    protected $table = 'export_chunks';

    protected $fillable = [
        'export_id',
        'chunk_index',
        'file_path',
    ];

    public function export(): BelongsTo
    {
        return $this->belongsTo(Export::class);
    }
}