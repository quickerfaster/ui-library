<?php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use QuickerFaster\UILibrary\Models\Export;
use QuickerFaster\UILibrary\Models\ExportChunk;
use ZipArchive;

class FinalizeExportZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle()
    {
        $export = Export::find($this->exportId);

        if (!$export || $export->status !== 'processing') {
            return;
        }

        // Get all chunks for this export, ordered by index
        $chunks = ExportChunk::where('export_id', $export->id)
            ->orderBy('chunk_index')
            ->get();

        if ($chunks->isEmpty()) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'No chunk files found for finalisation.',
            ]);
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
            return;
        }

        foreach ($chunks as $chunk) {
            $fullPath = Storage::disk('local')->path($chunk->file_path);
            if (file_exists($fullPath)) {
                // Add file with a name like "part_0.xls"
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
            return;
        }

        // Update export record
        $export->update([
            'status'        => 'completed',
            'file_path'     => $zipPath,
            'file_size'     => $fileSize,
            'download_token' => \Str::random(64),
            'expires_at'    => now()->addHour(),
            'completed_at'  => now(),
        ]);

        // Optional: delete individual chunk files and records to free space
        foreach ($chunks as $chunk) {
            Storage::disk('local')->delete($chunk->file_path);
            $chunk->delete();
        }
    }
}