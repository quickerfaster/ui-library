<?php
// QuickerFaster/UILibrary/Jobs/GenerateExport.php

namespace QuickerFaster\UILibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Services\Exports\DataTableExport;
use QuickerFaster\UILibrary\Traits\AppliesFilters;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use QuickerFaster\UILibrary\Models\Export;
use QuickerFaster\UILibrary\Traits\ResolvesExportValues; // <-- Add this


class GenerateExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AppliesFilters, ResolvesExportValues;

    protected int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }


    public function handle()
    {
        $export = Export::find($this->exportId);
        if (!$export)
            return;

        // Check if cancelled before doing any work
        if ($export->status === 'cancelled') {
            \Log::info("Export {$export->id} was cancelled before processing.");
            return;
        }

        $export->update(['status' => 'processing']);

        try {
            $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
            $modelClass = $resolver->getModel();
            $query = $modelClass::query();

            // Optional: eager load relations if needed
            $relations = array_keys($resolver->getRelations());
            if (!empty($relations)) {
                $query->with($relations);
            }

            // Apply filters (if any)
            if (!empty($export->filters)) {
                $this->applyActiveFilters($query, $export->filters, $resolver);
            }

            $records = $query->get();

            if ($records->isEmpty()) {
                throw new \Exception('No records found to export.');
            }

            $columns = $export->columns ?? [];

            // Generate file and get relative path
            if ($export->format === 'pdf') {
                $relativePath = $this->generatePdf($export, $records, $columns, $export->format);
            } else {
                $relativePath = $this->generateExcel($export, $records, $columns, $export->format);
            }

            // Double-check file existence and size
            $fullPath = Storage::disk('local')->path($relativePath);
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found after generation: {$fullPath}");
            }
            $size = filesize($fullPath);
            if ($size === 0) {
                throw new \Exception("Generated file is empty: {$fullPath}");
            }

            $fileSize = Storage::disk('local')->size($relativePath);
            $export->update([
                'status' => 'completed',
                'file_path' => $relativePath,
                'file_size' => $fileSize,
                'download_token' => \Str::random(64),
                'expires_at' => now()->addHour(),
                'completed_at' => now(),
            ]);


            if ($export->fresh()->status === 'cancelled') {
                // Clean up and exit
                Storage::disk('local')->delete($relativePath);
                return;
            }


        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            \Log::error('Export generation failed', [
                'export_id' => $export->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }



    protected function generateExcel(Export $export, $records, array $columns, string $format): string
    {
        $excelExport = new DataTableExport($export->config_key, $records, $columns);
        $relativePath = 'exports/' . uniqid() . '.' . $format;

        // Ensure directory exists
        Storage::disk('local')->makeDirectory('exports');

        // Store the file
        Excel::store($excelExport, $relativePath, 'local');

        // Verify file was created
        if (!Storage::disk('local')->exists($relativePath)) {
            throw new \Exception("Excel file not written: {$relativePath}");
        }

        return $relativePath;
    }


    protected function generatePdf(Export $export, $records, array $columns, string $format): string
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
        $fieldDefinitions = $resolver->getFieldDefinitions();

        // Title
        $title = $resolver->getConfig()['pageTitle'] ?? null;
        if (!$title) {
            $modelName = class_basename($resolver->getModel());
            $title = ucwords(str_replace('_', ' ', \Str::snake($modelName)));
        }
        $subtitle = $resolver->getConfig()['subtitle'] ?? 'Data Export';

        // Which columns to display
        if (empty($columns)) {
            $columns = array_keys($fieldDefinitions);
        }

        // Headings (labels)
        $headings = [];
        foreach ($columns as $field) {
            $headings[$field] = $fieldDefinitions[$field]['label'] ?? ucfirst($field);
        }

        // Transform records to resolved values
        $transformedRecords = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $field) {
                $row[$field] = $this->getFieldValueForExport($record, $field, $fieldDefinitions);
            }
            $transformedRecords[] = $row;
        }

        // PDF view
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

}