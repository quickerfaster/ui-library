<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use QuickerFaster\UILibrary\Models\Import;
use QuickerFaster\UILibrary\Models\ImportChunk;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Services\Imports\ImportProcessor;

/**
 * Process a single chunk of an import.
 *
 * Uses lockForUpdate on chunk and import status transitions to prevent
 * race conditions when multiple chunks complete near-simultaneously
 * and try to finalize the import.
 */
class ProcessImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for processing a chunk. Large files may require
     * more time for Excel parsing.
     */
    public $timeout = 300;

    /**
     * No retries — if a chunk fails, errors are recorded per-chunk
     * and the import can be reviewed.
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
    protected int $chunkId;
    protected array $columnMapping;
    protected bool $hasHeaderRow;
    protected int $chunkSize;

    public function __construct(int $importId, int $chunkId, array $columnMapping, bool $hasHeaderRow, int $chunkSize)
    {
        $this->importId = $importId;
        $this->chunkId = $chunkId;
        $this->columnMapping = $columnMapping;
        $this->hasHeaderRow = $hasHeaderRow;
        $this->chunkSize = $chunkSize;
    }

    public function handle()
    {
        // -----------------------------------------------------------------
        // Atomic chunk status check with lockForUpdate
        // -----------------------------------------------------------------
        $result = DB::transaction(function () {
            $import = Import::where('id', $this->importId)
                ->lockForUpdate()
                ->first();

            $chunk = ImportChunk::where('id', $this->chunkId)
                ->lockForUpdate()
                ->first();

            if (!$import || !$chunk) {
                if ($chunk) {
                    $chunk->update(['status' => 'cancelled']);
                }
                Log::warning("ProcessImportChunk: Import #{$this->importId} or chunk #{$this->chunkId} not found.");
                return null;
            }

            if ($import->status === 'cancelled') {
                $chunk->update(['status' => 'cancelled']);
                Log::info("ProcessImportChunk: Import #{$this->importId} was cancelled.");
                return null;
            }

            // Guard: skip if already processed
            if (in_array($chunk->status, ['completed', 'failed'], true)) {
                Log::info("ProcessImportChunk: Chunk #{$this->chunkId} already processed (status: {$chunk->status}).");
                return null;
            }

            $chunk->update(['status' => 'processing']);

            return ['import' => $import, 'chunk' => $chunk];
        });

        if (!$result) {
            return;
        }

        $import = $result['import'];
        $chunk = $result['chunk'];

        // Restore company context from the import record so that
        // CompanyScope and WizardForm/DataTableForm inject the correct
        // company_id when creating records during import processing.
        $this->restoreCompanyContext($import);

        try {
            // Check file size before loading to prevent memory exhaustion
            $fileSize = filesize($import->file_path);
            if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
                Log::warning("ProcessImportChunk #{$this->chunkId}: File too large ({$fileSize} bytes), may exhaust memory");
            }

            // Read only the required slice of rows from the file
            $rows = Excel::toArray([], $import->file_path)[0];

            // Remove header row if present
            if ($this->hasHeaderRow) {
                array_shift($rows);
            }

            // Get the slice for this chunk
            $slice = array_slice($rows, $chunk->offset, $chunk->limit);

            $processor = new ImportProcessor(
                new ConfigResolver($import->config_key),
                new FieldFactory()
            );

            $processResult = $processor->processRows(
                $slice,
                $this->columnMapping,
                $this->hasHeaderRow,
                function ($processedRows) use ($import) {
                    // Check for cancellation every 100 rows
                    if ($processedRows % 100 === 0) {
                        $import->refresh();
                        if ($import->status === 'cancelled') {
                            throw new \Exception('Import cancelled by user.');
                        }
                    }
                }
            );

            $chunk->update([
                'status' => 'completed',
                'processed_rows' => $processResult['processed'],
                'successful_rows' => $processResult['successful'],
                'failed_rows' => $processResult['failed'],
                'errors' => $processResult['errors'],
            ]);

            Log::info("ProcessImportChunk: Chunk #{$this->chunkId} completed.", [
                'import_id' => $import->id,
                'processed' => $processResult['processed'],
                'successful' => $processResult['successful'],
                'failed' => $processResult['failed'],
            ]);

        } catch (\Exception $e) {
            $chunk->update([
                'status' => 'failed',
                'errors' => [['row' => 0, 'errors' => [$e->getMessage()]]],
            ]);

            Log::error("ProcessImportChunk: Chunk #{$this->chunkId} failed.", [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            // Don't re-throw — let other chunks continue
            // The import is finalized with partial success
        }

        // After this chunk, atomically check if all chunks are done
        $this->finalizeIfComplete();
    }

    /**
     * Atomically check if all chunks are complete and finalize the import.
     *
     * Uses lockForUpdate to prevent the race condition where two chunks
     * complete near-simultaneously and both try to finalize the import,
     * causing duplicate aggregation or corrupted totals.
     */
    protected function finalizeIfComplete(): void
    {
        DB::transaction(function () {
            $import = Import::where('id', $this->importId)
                ->lockForUpdate()
                ->first();

            if (!$import) {
                return;
            }

            // Already finalized
            if (in_array($import->status, ['completed', 'failed', 'cancelled'], true)) {
                return;
            }

            $totalChunks = (int) ($import->total_chunks ?? 0);
            if ($totalChunks <= 0) {
                return;
            }

            $completedChunks = ImportChunk::where('import_id', $import->id)
                ->where('status', 'completed')
                ->count();

            $failedChunks = ImportChunk::where('import_id', $import->id)
                ->where('status', 'failed')
                ->count();

            $processedChunks = $completedChunks + $failedChunks;

            if ($processedChunks < $totalChunks) {
                return; // Not all chunks are done yet
            }

            // All chunks processed — aggregate results
            $chunks = ImportChunk::where('import_id', $import->id)->get();
            $allErrors = [];
            $totalProcessed = 0;
            $totalSuccessful = 0;
            $totalFailed = 0;

            foreach ($chunks as $c) {
                $totalProcessed += (int) ($c->processed_rows ?? 0);
                $totalSuccessful += (int) ($c->successful_rows ?? 0);
                $totalFailed += (int) ($c->failed_rows ?? 0);
                if ($c->errors) {
                    $chunkErrors = is_string($c->errors) ? json_decode($c->errors, true) : $c->errors;
                    if (is_array($chunkErrors)) {
                        $allErrors = array_merge($allErrors, $chunkErrors);
                    }
                }
            }

            // Generate error report CSV if there are errors
            $errorFilePath = null;
            if ($totalFailed > 0 && !empty($allErrors)) {
                $errorFilePath = $this->generateErrorReport($import, $allErrors);
            }

            $newStatus = $totalFailed > 0 ? 'completed_with_errors' : 'completed';

            $import->update([
                'status' => $newStatus,
                'processed_rows' => $totalProcessed,
                'successful_rows' => $totalSuccessful,
                'failed_rows' => $totalFailed,
                'errors' => json_encode($allErrors),
                'error_file' => $errorFilePath,
            ]);

            Log::info("ProcessImportChunk: Import #{$import->id} finalized.", [
                'status' => $newStatus,
                'total_processed' => $totalProcessed,
                'total_successful' => $totalSuccessful,
                'total_failed' => $totalFailed,
            ]);
        });
    }

    /**
     * Generate a CSV error report for failed rows.
     */
    protected function generateErrorReport(Import $import, array $errors): string
    {
        $csv = fopen('php://temp', 'w');
        fputcsv($csv, ['Row Number', 'Error Message']);

        foreach ($errors as $error) {
            $rowNum = $error['row'] ?? 'Unknown';
            $errorMsg = is_array($error['errors'] ?? null)
                ? implode('; ', $error['errors'])
                : ($error['errors'] ?? 'Unknown error');
            fputcsv($csv, [$rowNum, $errorMsg]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $path = "imports/{$import->id}/errors.csv";
        Storage::disk('local')->put($path, $content);
        return $path;
    }

    /**
     * Restore the company context from the import record into the session.
     *
     * Queue jobs run in CLI context with no HTTP session. The ImportProcessor
     * and downstream form handlers use Session::get('current_company_id') to
     * inject company_id into created records. By restoring it here, imported
     * records get the correct company assignment.
     *
     * @param Import $import
     */
    protected function restoreCompanyContext(Import $import): void
    {
        $companyColumn = config('ui-library.tenancy.column', 'company_id');
        $companyId = $import->{$companyColumn} ?? null;

        if ($companyId && $companyId !== 0) {
            session()->put(config('ui-library.tenancy.session_key', 'current_company_id'), $companyId);
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