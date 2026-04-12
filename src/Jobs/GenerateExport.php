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

class GenerateExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AppliesFilters;

    protected int $exportId;

    public function __construct(int $exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle()
    {
        $export = Export::find($this->exportId);
        if (!$export) {
            return;
        }

        $export->update(['status' => 'processing']);

        try {
            $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
            $modelClass = $resolver->getModel();
            $query = $modelClass::query();

            // Eager load relationships
            $relations = array_keys($resolver->getRelations());
            if (!empty($relations)) {
                $query->with($relations);
            }

            // Apply filters (stored as JSON in export record)
            if (!empty($export->filters)) {
                $this->applyActiveFilters($query, $export->filters, $resolver);
            }

            // Apply sorting (if needed) – could be stored in export record as well
            // For simplicity, we'll leave sorting out or add later

            $records = $query->get();
            $columns = $export->columns ?? [];

            $filePath = 'exports/' . uniqid() . '.' . $export->format;
            $fullPath = Storage::disk('local')->path($filePath);

            if ($export->format === 'pdf') {
                $this->generatePdf($export, $records, $columns, $fullPath);
            } else {
                $this->generateExcel($export, $records, $columns, $fullPath, $export->format);
            }

            $export->update([
                'status'       => 'completed',
                'file_path'    => $filePath,
                'completed_at' => now(),
            ]);

            // Optional: notify user via Livewire event or database notification
            // We'll handle polling from frontend.

        } catch (\Exception $e) {
            $export->update([
                'status'         => 'failed',
                'error_message'  => $e->getMessage(),
                'completed_at'   => now(),
            ]);
        }
    }

    protected function generateExcel(Export $export, $records, array $columns, string $fullPath, string $format)
    {
        $excelExport = new DataTableExport($export->config_key, $records, $columns);
        Excel::store($excelExport, $fullPath, 'local');
    }

    protected function generatePdf(Export $export, $records, array $columns, string $fullPath)
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $export->config_key]);
        $definitions = $resolver->getFieldDefinitions();

        if (empty($columns)) {
            $columns = array_keys($definitions);
        }

        $headings = [];
        foreach ($columns as $field) {
            $headings[$field] = $definitions[$field]['label'] ?? ucfirst($field);
        }

        // Determine PDF view and options
        $controls = $resolver->getControls();
        $pdfView = $controls['files']['export_pdf_view'] ?? 'qf::exports.default-pdf';

        $options = $export->options ?? [];

        $pdf = Pdf::loadView($pdfView, [
            'records'   => $records,
            'columns'   => $columns,
            'headings'  => $headings,
            'configKey' => $export->config_key,
        ]);

        // Apply PDF options
        if (isset($options['orientation'])) {
            $pdf->setPaper('a4', $options['orientation']);
        }
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper'], $options['orientation'] ?? 'portrait');
        }

        file_put_contents($fullPath, $pdf->output());
    }
}