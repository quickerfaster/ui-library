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
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Services\Imports\ImportProcessor;
use Illuminate\Support\Facades\Storage;

class ProcessImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $import = Import::find($this->importId);
        $chunk = ImportChunk::find($this->chunkId);

        if (!$import || !$chunk || $import->status === 'cancelled') {
            if ($chunk)
                $chunk->update(['status' => 'cancelled']);
            return;
        }

        $chunk->update(['status' => 'processing']);

        // Read only the required slice of rows from the file
        $rows = Excel::toArray([], $import->file_path)[0];

        // If header row exists, remove it before calculating offset
        if ($this->hasHeaderRow) {
            $header = array_shift($rows);
        }

        // Get the slice for this chunk
        $slice = array_slice($rows, $chunk->offset, $chunk->limit);

        $processor = new ImportProcessor(
            new ConfigResolver($import->config_key),
            new FieldFactory()
        );

        // Process the slice (the processor's process method currently handles whole file;
        // we'll modify it to accept a rows array instead of file path – see Step 5)
        $result = $processor->processRows(
            $slice,
            $this->columnMapping,
            $this->hasHeaderRow,
            function ($processedRows) use ($import, $chunk) {
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
            'processed_rows' => $result['processed'],
            'successful_rows' => $result['successful'],
            'failed_rows' => $result['failed'],
            'errors' => $result['errors'],
        ]);

        // After this chunk, check if all chunks are done
        $this->finalizeIfComplete($import);
    }

    protected function finalizeIfComplete(Import $import)
    {
        $totalChunks = $import->total_chunks;
        $completedChunks = ImportChunk::where('import_id', $import->id)
            ->where('status', 'completed')
            ->count();

        if ($completedChunks >= $totalChunks) {
            // Aggregate all errors from chunks
            $chunks = ImportChunk::where('import_id', $import->id)->get();
            $allErrors = [];
            $totalProcessed = 0;
            $totalSuccessful = 0;
            $totalFailed = 0;

            foreach ($chunks as $chunk) {
                $totalProcessed += $chunk->processed_rows;
                $totalSuccessful += $chunk->successful_rows;
                $totalFailed += $chunk->failed_rows;
                if ($chunk->errors) {
                    $allErrors = array_merge($allErrors, $chunk->errors);
                }
            }

            // Generate error report CSV if there are errors
            $errorFilePath = null;
            if ($totalFailed > 0 && !empty($allErrors)) {
                $errorFilePath = $this->generateErrorReport($import, $allErrors);
            }

            $import->update([
                'status' => 'completed',
                'processed_rows' => $totalProcessed,
                'successful_rows' => $totalSuccessful,
                'failed_rows' => $totalFailed,
                'errors' => json_encode($allErrors),
                'error_file' => $errorFilePath,
            ]);
        }
    }

    protected function generateErrorReport(Import $import, array $errors): string
    {
        $csv = fopen('php://temp', 'w');
        fputcsv($csv, ['Row Number', 'Error Message']);

        foreach ($errors as $error) {
            fputcsv($csv, [$error['row'] ?? 'Unknown', implode('; ', $error['errors'])]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $path = "imports/{$import->id}/errors.csv";
        Storage::disk('local')->put($path, $content);
        return $path;
    }
}