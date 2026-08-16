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
use Barryvdh\DomPDF\Facade\Pdf;
use QuickerFaster\UILibrary\Models\Export;
use QuickerFaster\UILibrary\Traits\ResolvesExportValues;

/**
 * Orchestrate a chunked export by counting rows and dispatching
 * ExportChunk jobs for each chunk.
 *
 * Uses lockForUpdate to atomically check and transition the export
 * status, preventing double-dispatch.
 */
class GenerateExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AppliesFilters, ResolvesExportValues;

    /**
     * This job only dispatches chunk jobs — it doesn't generate files.
     * A short timeout is sufficient.
     */
    public $timeout = 120;

    /**
     * No retries — if dispatch fails, the export is marked as failed.
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

    protected int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle()
    {
        // -----------------------------------------------------------------
        // Atomic status check with lockForUpdate
        // -----------------------------------------------------------------
        $export = DB::transaction(function () {
            $export = Export::where('id', $this->exportId)
                ->lockForUpdate()
                ->first();

            if (!$export) {
                Log::warning("GenerateExport: Export #{$this->exportId} not found.");
                return null;
            }

            if ($export->status === 'cancelled') {
                Log::info("GenerateExport: Export #{$this->exportId} was cancelled before processing.");
                return null;
            }

            // Guard: only process pending exports
            if ($export->status !== 'pending') {
                Log::info("GenerateExport: Export #{$this->exportId} already processing (status: {$export->status}). Skipping.");
                return null;
            }

            $export->update(['status' => 'processing']);
            return $export;
        });

        if (!$export) {
            return;
        }

        // Restore company context from the export record so that
        // CompanyScope filters correctly in the queue job (no HTTP session).
        $this->restoreCompanyContext($export);

        try {
            $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
            $modelClass = $resolver->getModel();
            $query = $modelClass::query();

            if (!empty($export->filters)) {
                $this->applyActiveFilters($query, $export->filters, $resolver);
            }

            $totalRows = $query->count();
            if ($totalRows === 0) {
                $export->update([
                    'status' => 'failed',
                    'error_message' => 'No records to export',
                ]);
                Log::warning("GenerateExport: No records for export #{$this->exportId}.");
                return;
            }

            // Set chunking parameters
            $chunkSize = $export->chunk_size ?? 100;
            $totalChunks = (int) ceil($totalRows / $chunkSize);

            $export->update([
                'total_rows' => $totalRows,
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
            ]);

            Log::info("GenerateExport: Dispatching {$totalChunks} chunks for export #{$this->exportId}.", [
                'total_rows' => $totalRows,
                'chunk_size' => $chunkSize,
            ]);

            // Dispatch chunk jobs
            for ($i = 0; $i < $totalChunks; $i++) {
                $offset = $i * $chunkSize;
                ExportChunk::dispatch($export->id, $i, $offset, $chunkSize);
            }

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'Export dispatch failed: ' . substr($e->getMessage(), 0, 500),
            ]);

            Log::error("GenerateExport: Export #{$this->exportId} failed.", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a single-file Excel export (non-chunked path, kept for reference).
     */
    protected function generateExcel(Export $export, $records, array $columns, string $format): string
    {
        $excelExport = new DataTableExport($export->config_key, $records, $columns);
        $relativePath = 'exports/' . uniqid() . '.' . $format;

        Storage::disk('local')->makeDirectory('exports');
        Excel::store($excelExport, $relativePath, 'local');

        if (!Storage::disk('local')->exists($relativePath)) {
            throw new \Exception("Excel file not written: {$relativePath}");
        }

        return $relativePath;
    }

    /**
     * Generate a single-file PDF export (non-chunked path, kept for reference).
     */
    protected function generatePdf(Export $export, $records, array $columns, string $format): string
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
        $fieldDefinitions = $resolver->getFieldDefinitions();

        $title = $resolver->getConfig()['pageTitle'] ?? null;
        if (!$title) {
            $modelName = class_basename($resolver->getModel());
            $title = ucwords(str_replace('_', ' ', \Str::snake($modelName)));
        }
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

        $controls = $resolver->getControls();
        $pdfView = $controls['files']['export_pdf_view'] ?? 'qf::exports.default-pdf';
        $options = $export->options ?? [];

        $pdf = Pdf::loadView($pdfView, [
            'records' => $transformedRecords,
            'columns' => $columns,
            'headings' => $headings,
            'title' => $title,
            'subtitle' => $subtitle,
        ]);

        if (isset($options['orientation'])) {
            $pdf->setPaper('a4', $options['orientation']);
        }
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper'], $options['orientation'] ?? 'portrait');
        }

        $relativePath = 'exports/' . uniqid() . '.pdf';
        Storage::disk('local')->put($relativePath, $pdf->output());

        if (!Storage::disk('local')->exists($relativePath)) {
            throw new \Exception("PDF file not written: {$relativePath}");
        }

        return $relativePath;
    }

    /**
     * Restore the company context from the export record into the session.
     *
     * Queue jobs run in CLI context with no HTTP session, so CompanyScope's
     * Session::get('current_company_id') returns null and applies no filter.
     *
     * @param Export $export
     */
    protected function restoreCompanyContext(Export $export): void
    {
        $companyId = $export->company_id ?? null;

        if ($companyId && $companyId !== 0) {
            session()->put('current_company_id', $companyId);
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