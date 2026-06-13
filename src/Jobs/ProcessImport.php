<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use QuickerFaster\UILibrary\Models\Import;
use QuickerFaster\UILibrary\Models\ImportChunk;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importId;
    public $columnMapping;
    public $hasHeaderRow;

    public function __construct(int $importId, array $columnMapping, bool $hasHeaderRow)
    {
        $this->importId = $importId;
        $this->columnMapping = $columnMapping;
        $this->hasHeaderRow = $hasHeaderRow;
    }

    public function handle()
    {
        $import = Import::find($this->importId);
        if (!$import || $import->status === 'cancelled') {
            return;
        }

        $import->update(['status' => 'processing']);

        // Count total rows (excluding header if present)
        $rows = Excel::toArray([], $import->file_path)[0];
        $totalRows = count($rows);
        if ($this->hasHeaderRow && $totalRows > 0) {
            $totalRows--; // subtract header row
        }

        if ($totalRows === 0) {
            $import->update([
                'status' => 'failed',
                'errors' => json_encode(['No data rows found in file.']),
            ]);
            return;
        }

        // Determine chunk size (e.g., 100 rows per chunk)
        $chunkSize = $import->chunk_size ?? 100;
        $totalChunks = (int) ceil($totalRows / $chunkSize);

        $import->update([
            'total_rows' => $totalRows,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
        ]);

        // Create chunk records and dispatch jobs
        for ($i = 0; $i < $totalChunks; $i++) {
            $offset = $i * $chunkSize;
            // For first chunk, if header exists, offset must account for it (we'll handle inside chunk job)
            $chunk = ImportChunk::create([
                'import_id' => $import->id,
                'chunk_index' => $i,
                'offset' => $offset,
                'limit' => $chunkSize,
                'status' => 'pending',
            ]);

            ProcessImportChunk::dispatch(
                $import->id,
                $chunk->id,
                $this->columnMapping,
                $this->hasHeaderRow,
                $chunkSize
            );
        }
    }
}