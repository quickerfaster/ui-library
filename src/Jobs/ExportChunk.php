<?php
// app/Jobs/ExportChunk.php

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
use QuickerFaster\UILibrary\Models\ExportChunk as ExportChunkModel;
use QuickerFaster\UILibrary\Models\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use QuickerFaster\UILibrary\Traits\ResolvesExportValues; 

class ExportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AppliesFilters, ResolvesExportValues;

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
        $export = \QuickerFaster\UILibrary\Models\Export::find($this->exportId);
        if (!$export || $export->status === 'cancelled') {
            return;
        }

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

        $records = $query->offset($this->offset)->limit($this->limit)->get();

        if ($records->isEmpty()) {
            return; // nothing to do
        }

        $columns = $export->columns ?? [];
        $format = $export->format;

        // Generate partial file
        $relativePath = $this->generatePartialFile($export, $records, $columns, $format, $this->chunkIndex);

        // Store the file path in a temporary tracking table (see step 3)
        ExportChunkModel::create([
            'export_id' => $this->exportId,
            'chunk_index' => $this->chunkIndex,
            'file_path' => $relativePath,
        ]);

        // After all chunks are done (checked by a separate job or after each chunk), zip them
        $export->refresh();   // force reload from database
        $this->checkAndFinalizeExport($export);
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

    // Title & subtitle (same as before)
    $title = $resolver->getConfig()['pageTitle'] ?? class_basename($resolver->getModel());
    $subtitle = $resolver->getConfig()['subtitle'] ?? 'Data Export';

    if (empty($columns)) {
        $columns = array_keys($fieldDefinitions);
    }

    // Headings
    $headings = [];
    foreach ($columns as $field) {
        $headings[$field] = $fieldDefinitions[$field]['label'] ?? ucfirst($field);
    }

    // Transform records to resolved values (same as in your original generatePdf)
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

    // Apply orientation/paper from export options
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







protected function checkAndFinalizeExport($export)
{
    $export->refresh();

    // If chunk info is missing, try to derive from existing chunk files
    if (empty($export->chunk_size) || empty($export->total_rows)) {
        $chunks = ExportChunkModel::where('export_id', $export->id)->get();
        if ($chunks->isEmpty()) {
            return;
        }
        // assume chunk_size = 100 (or guess from first chunk's filename?)
        $chunkSize = 100;
        $totalChunks = $chunks->count();
        $totalRows = $totalChunks * $chunkSize; // approximate
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
        // fallback to count completed chunks as totalChunks
        $totalChunks = ExportChunkModel::where('export_id', $export->id)->count();
        if ($totalChunks === 0) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'No chunk files found.',
            ]);
            return;
        }
        // treat all chunks as done
        $completedChunks = $totalChunks;
        $export->update([
            'total_chunks' => $totalChunks,
            'total_rows' => $totalChunks * 100, // rough estimate
        ]);
    } else {
        $totalChunks = (int) ceil($totalRows / $chunkSize);
    }

    $completedChunks = ExportChunkModel::where('export_id', $export->id)->count();

    if ($completedChunks >= $totalChunks) {
        FinalizeExportZip::dispatch($export->id);
    }
}

}