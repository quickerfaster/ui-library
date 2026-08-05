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
use QuickerFaster\UILibrary\Models\Export;
use QuickerFaster\UILibrary\Models\ExportChunk;
use ZipArchive;

/**
 * Finalize a chunked export by zipping all partial files.
 *
 * Uses lockForUpdate to atomically check and transition the export
 * status, preventing duplicate finalization when multiple chunks
 * complete near-simultaneously.
 */
class FinalizeExportZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for ZIP creation. Large exports with many chunks
     * may take longer to compress.
     */
    public $timeout = 300;

    /**
     * No retries — if ZIP fails, the export is marked as failed.
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
     * same queue/connection as payroll jobs, allowing a single worker.sh
     * to serve both.
     */
    public $queue = 'default';
    public $connection = 'database';

    protected int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle()
    {
        // -----------------------------------------------------------------
        // Atomic status check with lockForUpdate
        // Prevents duplicate finalization if dispatched twice.
        // -----------------------------------------------------------------
        $export = DB::transaction(function () {
            $export = Export::where('id', $this->exportId)
                ->lockForUpdate()
                ->first();

            if (!$export) {
                Log::warning("FinalizeExportZip: Export #{$this->exportId} not found.");
                return null;
            }

            // Only process exports in 'finalizing' status (set by ExportChunk)
            // or 'processing' (legacy path from non-chunked exports)
            if (!in_array($export->status, ['finalizing', 'processing'], true)) {
                Log::info("FinalizeExportZip: Export #{$this->exportId} already finalized (status: {$export->status}). Skipping.");
                return null;
            }

            return $export;
        });

        if (!$export) {
            return;
        }

        try {
            // Get all chunks for this export, ordered by index
            $chunks = ExportChunk::where('export_id', $export->id)
                ->orderBy('chunk_index')
                ->get();

            if ($chunks->isEmpty()) {
                $export->update([
                    'status' => 'failed',
                    'error_message' => 'No chunk files found for finalisation.',
                ]);
                Log::error("FinalizeExportZip: No chunks found for export #{$export->id}.");
                return;
            }

            $zipDir = "exports/{$export->id}";
            $zipPath = "{$zipDir}/export.zip";
            $fullZipPath = Storage::disk('local')->path($zipPath);

            // Ensure directory exists
            Storage::disk('local')->makeDirectory($zipDir);

            $zip = new ZipArchive();
            if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $export->update([
                    'status' => 'failed',
                    'error_message' => 'Unable to create ZIP archive.',
                ]);
                Log::error("FinalizeExportZip: Cannot create ZIP for export #{$export->id}.");
                return;
            }

            foreach ($chunks as $chunk) {
                $fullPath = Storage::disk('local')->path($chunk->file_path);
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, basename($chunk->file_path));
                }
            }
            $zip->close();

            // Verify ZIP was created and has size > 0
            $fileSize = Storage::disk('local')->size($zipPath);
            if ($fileSize === 0) {
                $export->update([
                    'status' => 'failed',
                    'error_message' => 'Generated ZIP file is empty.',
                ]);
                Storage::disk('local')->delete($zipPath);
                Log::error("FinalizeExportZip: Empty ZIP for export #{$export->id}.");
                return;
            }

            // Update export record
            $export->update([
                'status' => 'completed',
                'file_path' => $zipPath,
                'file_size' => $fileSize,
                'download_token' => \Str::random(64),
                'expires_at' => now()->addHour(),
                'completed_at' => now(),
            ]);

            Log::info("FinalizeExportZip: Export #{$export->id} completed.", [
                'file_size' => $fileSize,
                'chunk_count' => $chunks->count(),
            ]);

            // Delete individual chunk files and records to free space
            foreach ($chunks as $chunk) {
                Storage::disk('local')->delete($chunk->file_path);
                $chunk->delete();
            }

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'Finalization failed: ' . substr($e->getMessage(), 0, 500),
            ]);

            Log::error("FinalizeExportZip: Export #{$export->id} failed.", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure — mark the export as failed so the user
     * sees the error rather than an indefinitely "processing" status.
     */
    public function failed(\Throwable $exception = null): void
    {
        if ($this->exportId) {
            Export::where('id', $this->exportId)
                ->update(['status' => 'failed']);
        }
    }
}