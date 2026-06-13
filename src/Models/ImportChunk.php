<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class ImportChunk extends Model
{
    protected $table = 'import_chunks';

    protected $fillable = [
        'import_id',
        'chunk_index',
        'offset',
        'limit',
        'status',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
    ];

    protected $casts = [
        'errors' => 'array',
    ];

    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}