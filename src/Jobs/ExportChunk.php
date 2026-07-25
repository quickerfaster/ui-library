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
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Services\Exports\DataTableExport;
use QuickerFaster\UILibrary\Traits\AppliesFilters;
use Maatwebsite\Excel\Facades\Excel;
use QuickerFaster\UILibrary\Models\ExportChunk as ExportChunkModel;
use QuickerFaster\UILibrary\Models\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use QuickerFaster\UILibrary\Traits\ResolvesExportValues;

/**
 * Process a single chunk of an export.
 *
 * Generates a partial file (Excel or PDF) for the assigned row range,
 * then checks whether all chunks are complete and dispatches the
 * finalizer if so. Uses lockForUpdate to prevent race conditions
 * when multiple chunks complete near-simultaneously.
 */
class ExportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AppliesFilters, ResolvesExportValues;

    /**
     * Timeout for chunk processing. PDF chunks can be heavier.
     */
    public $timeout = 300;

    /**
     * No retries — if a chunk fails, the export is marked as failed.
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

    protected int $exportId;
    protected int $chunkIndex;
    protected int $offset;
    protected int $limit;

    public function __construct(int $exportId, int $chunkIndex, int $offset, int $limit)
    {
        $this->exportId = $exportId;
        $this->chunkIndex = $chunkIndex;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function handle()
    {
        $export = Export::find($this->exportId);

        if (!$export || $export->status === 'cancelled') {
            Log::info("ExportChunk: Export #{$this->exportId} not found or cancelled. Skipping chunk #{$this->chunkIndex}.");
            return;
        }

        // Restore company context from the export record so that
        // CompanyScope filters correctly in the queue job (no HTTP session).
        $this->restoreCompanyContext($export);

        $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
        $modelClass = $resolver->getModel();

        // Build the same query as before, but with offset & limit
        $query = $modelClass::query();
        $relations = array_keys($resolver->getRelations());
        if (!empty($relations)) {
            $query->with($relations);
        }
        if (!empty($export->filters)) {
            $this->applyActiveFilters($query, $export->filters, $resolver);
        }

        try {
            $records = $query->offset($this->offset)->limit($this->limit)->get();

            if ($records->isEmpty()) {
                Log::info("ExportChunk: No records for chunk #{$this->chunkIndex} of export #{$this->exportId}.");
                return;
            }

            $columns = $export->columns ?? [];
            $format = $export->format;

            // Generate partial file
            $relativePath = $this->generatePartialFile($export, $records, $columns, $format, $this->chunkIndex);

            // Store the file path in the tracking table
            ExportChunkModel::create([
                'export_id' => $this->exportId,
                'chunk_index' => $this->chunkIndex,
                'file_path' => $relativePath,
            ]);

            Log::info("ExportChunk: Chunk #{$this->chunkIndex} of export #{$this->exportId} completed.", [
                'record_count' => $records->count(),
                'file_path' => $relativePath,
            ]);

        } catch (\Exception $e) {
            Log::error("ExportChunk: Chunk #{$this->chunkIndex} of export #{$this->exportId} failed.", [
                'error' => $e->getMessage(),
            ]);

            // Mark the export as failed if any chunk fails
            Export::where('id', $this->exportId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->update([
                    'status' => 'failed',
                    'error_message' => "Chunk #{$this->chunkIndex} failed: " . substr($e->getMessage(), 0, 500),
                ]);

            throw $e;
        }

        // After this chunk, atomically check if all chunks are done.
        // Uses lockForUpdate to prevent the race condition where two
        // chunks complete simultaneously and both dispatch FinalizeExportZip.
        $this->checkAndFinalizeExport();
    }

    protected function generatePartialFile($export, $records, array $columns, string $format, int $chunkIndex): string
    {
        if ($format === 'pdf') {
            return $this->generatePartialPdf($export, $records, $columns, $chunkIndex);
        }
        return $this->generatePartialExcel($export, $records, $columns, $format, $chunkIndex);
    }

    protected function generatePartialExcel($export, $records, array $columns, string $format, int $chunkIndex): string
    {
        $excelExport = new DataTableExport($export->config_key, $records, $columns);
        $relativePath = "exports/{$export->id}/part_{$chunkIndex}.{$format}";
        Storage::disk('local')->makeDirectory("exports/{$export->id}");
        Excel::store($excelExport, $relativePath, 'local');
        return $relativePath;
    }

    protected function generatePartialPdf(Export $export, $records, array $columns, int $chunkIndex): string
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
        $fieldDefinitions = $resolver->getFieldDefinitions();

        $title = $resolver->getConfig()['pageTitle'] ?? class_basename($resolver->getModel());
        $subtitle = $resolver->getConfig()['subtitle'] ?? 'Data Export';

        if (empty($columns)) {
            $columns = array_keys($fieldDefinitions);
        }

        $headings = [];
        foreach ($columns as $field) {
            $headings[$field] = $fieldDefinitions[$field]['label'] ?? ucfirst($field);
        }

        $transformedRecords = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $field) {
                $row[$field] = $this->getFieldValueForExport($record, $field, $fieldDefinitions);
            }
            $transformedRecords[] = $row;
        }

        $pdfView = $resolver->getControls()['files']['export_pdf_view'] ?? 'qf::exports.default-pdf';
        $pdf = Pdf::loadView($pdfView, [
            'records' => $transformedRecords,
            'columns' => $columns,
            'headings' => $headings,
            'title' => $title,
            'subtitle' => $subtitle,
        ]);

        $options = $export->options ?? [];
        if (isset($options['orientation'])) {
            $pdf->setPaper('a4', $options['orientation']);
        }
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper'], $options['orientation'] ?? 'portrait');
        }

        $relativePath = "exports/{$export->id}/part_{$chunkIndex}.pdf";
        Storage::disk('local')->put($relativePath, $pdf->output());
        return $relativePath;
    }

    /**
     * Atomically check if all chunks are complete and dispatch the finalizer.
     *
     * Uses lockForUpdate on the Export record to prevent the race condition
     * where two chunks complete near-simultaneously and both dispatch
     * FinalizeExportZip, causing ZIP corruption or duplicate finalization.
     */
    protected function checkAndFinalizeExport(): void
    {
        DB::transaction(function () {
            $export = Export::where('id', $this->exportId)
                ->lockForUpdate()
                ->first();

            if (!$export || in_array($export->status, ['completed', 'failed', 'cancelled'], true)) {
                return; // Already finalized or cancelled
            }

            // Determine total chunks needed
            if (empty($export->chunk_size) || empty($export->total_rows)) {
                $chunks = ExportChunkModel::where('export_id', $export->id)->get();
                if ($chunks->isEmpty()) {
                    return;
                }
                $chunkSize = 100;
                $totalChunks = $chunks->count();
                $totalRows = $totalChunks * $chunkSize;

                $export->update([
                    'chunk_size' => $chunkSize,
                    'total_rows' => $totalRows,
                    'total_chunks' => $totalChunks,
                ]);
                $export->refresh();
            }

            $totalRows = (int) $export->total_rows;
            $chunkSize = (int) $export->chunk_size;

            if ($totalRows <= 0 || $chunkSize <= 0) {
                $totalChunks = ExportChunkModel::where('export_id', $export->id)->count();
                if ($totalChunks === 0) {
                    $export->update([
                        'status' => 'failed',
                        'error_message' => 'No chunk files found.',
                    ]);
                    return;
                }
            } else {
                $totalChunks = (int) ceil($totalRows / $chunkSize);
            }

            $completedChunks = ExportChunkModel::where('export_id', $export->id)->count();

            if ($completedChunks >= $totalChunks) {
                // Transition to finalizing so another chunk doesn't also dispatch
                $export->update(['status' => 'finalizing']);

                Log::info("ExportChunk: All chunks complete for export #{$this->exportId}. Dispatching FinalizeExportZip.", [
                    'completed_chunks' => $completedChunks,
                    'total_chunks' => $totalChunks,
                ]);

                FinalizeExportZip::dispatch($export->id);
            }
        });
    }

    /**
     * Restore the company context from the export record into the session.
     *
     * Queue jobs run in CLI context with no HTTP session, so CompanyScope's
     * Session::get('current_company_id') returns null and applies no filter.
     * By restoring the company_id stored at export creation time, we ensure
     * the export only contains data from the intended company scope.
     *
     * - company_id = 0 or null → "All Companies" mode, no filter
     * - company_id = N          → filter to company N only
     *
     * @param Export $export
     */
    protected function restoreCompanyContext(Export $export): void
    {
        $companyId = $export->company_id ?? null;

        // Only set session if the export has a specific company context.
        // 0 and null both mean "no filter" in CompanyScope's logic.
        if ($companyId && $companyId !== 0) {
            session()->put('current_company_id', $companyId);
        }
        // If 0 or null, leave session empty so CompanyScope applies no filter.
    }
}