<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use QuickerFaster\UILibrary\Models\Import;
use QuickerFaster\UILibrary\Models\ImportChunk;

/**
 * Orchestrate a chunked import by reading the file, counting rows,
 * and dispatching ProcessImportChunk jobs for each chunk.
 *
 * Uses lockForUpdate to atomically check and transition the import
 * status, preventing double-dispatch.
 */
class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * This job only dispatches chunk jobs — a short timeout is sufficient.
     */
    public $timeout = 120;

    /**
     * No retries — if dispatch fails, the import is marked as failed.
     */
    public $tries = 1;

    /**
     * Mark as failed on timeout.
     */
    public $failOnTimeout = true;

    /**
     * Maximum unhandled exceptions.
     */
    public $maxExceptions = 1;

    /**
     * Explicit queue and connection so these jobs always land on the
     * same queue/connection as background jobs, allowing a single worker.sh
     * to serve both.
     */
    public $queue = 'default';
    public $connection = 'database';

    protected int $importId;
    protected array $columnMapping;
    protected bool $hasHeaderRow;

    public function __construct(int $importId, array $columnMapping, bool $hasHeaderRow)
    {
        $this->importId = $importId;
        $this->columnMapping = $columnMapping;
        $this->hasHeaderRow = $hasHeaderRow;
    }

    public function handle()
    {
        // -----------------------------------------------------------------
        // Atomic status check with lockForUpdate
        // -----------------------------------------------------------------
        $import = DB::transaction(function () {
            $import = Import::where('id', $this->importId)
                ->lockForUpdate()
                ->first();

            if (!$import) {
                Log::warning("ProcessImport: Import #{$this->importId} not found.");
                return null;
            }

            if ($import->status === 'cancelled') {
                Log::info("ProcessImport: Import #{$this->importId} was cancelled before processing.");
                return null;
            }

            // Guard: only process pending imports
            if ($import->status !== 'pending') {
                Log::info("ProcessImport: Import #{$this->importId} already processing (status: {$import->status}). Skipping.");
                return null;
            }

            $import->update(['status' => 'processing']);
            return $import;
        });

        if (!$import) {
            return;
        }

        try {
            // Count total rows (excluding header if present)
            $rows = Excel::toArray([], $import->file_path)[0];
            $totalRows = count($rows);
            if ($this->hasHeaderRow && $totalRows > 0) {
                $totalRows--;
            }

            if ($totalRows === 0) {
                $import->update([
                    'status' => 'failed',
                    'errors' => json_encode(['No data rows found in file.']),
                ]);
                Log::warning("ProcessImport: No data rows for import #{$this->importId}.");
                return;
            }

            // Determine chunk size
            $chunkSize = $import->chunk_size ?? 100;
            $totalChunks = (int) ceil($totalRows / $chunkSize);

            $import->update([
                'total_rows' => $totalRows,
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
            ]);

            Log::info("ProcessImport: Dispatching {$totalChunks} chunks for import #{$this->importId}.", [
                'total_rows' => $totalRows,
                'chunk_size' => $chunkSize,
            ]);

            // Create chunk records and dispatch jobs
            for ($i = 0; $i < $totalChunks; $i++) {
                $offset = $i * $chunkSize;

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

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => json_encode(['Import dispatch failed: ' . $e->getMessage()]),
            ]);

            Log::error("ProcessImport: Import #{$this->importId} failed.", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure — mark the import as failed so the user
     * sees the error rather than an indefinitely "processing" status.
     */
    public function failed(\Throwable $exception = null): void
    {
        if ($this->importId) {
            Import::where('id', $this->importId)
                ->update(['status' => 'failed']);
        }
    }
}