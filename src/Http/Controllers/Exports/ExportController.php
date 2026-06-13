<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Exports;

use Illuminate\Http\Request;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Services\Exports\DataTableExport;
use QuickerFaster\UILibrary\Jobs\GenerateExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use QuickerFaster\UILibrary\Models\Export;
use QuickerFaster\UILibrary\Services\Exports\TemplateExport;
use QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory;
use QuickerFaster\UILibrary\Traits\ResolvesExportValues;
use QuickerFaster\UILibrary\Models\ExportChunk;

class ExportController extends Controller
{
    use ResolvesExportValues;

    public function export(Request $request)
    {
        $request->validate([
            'configKey' => 'required|string',
            'ids' => 'required|string',
            'format' => 'required|in:csv,xls,pdf',
            'columns' => 'nullable|string',
        ]);

        $configKey = $request->configKey;
        $ids = explode(',', $request->ids);
        $format = $request->format;
        $columns = $request->filled('columns') ? explode(',', $request->columns) : [];

        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $modelClass = $resolver->getModel();
        $records = $modelClass::whereIn('id', $ids)->get();

        if ($format === 'pdf') {
            return $this->generatePdf($configKey, $records, $columns);
        }

        $export = new DataTableExport($configKey, $records, $columns);
        $fileName = 'export_' . now()->format('Ymd_His') . '.' . $format;
        return Excel::download($export, $fileName);
    }

    public function exportAll(Request $request)
    {
        $request->validate([
            'configKey' => 'required|string',
            'format' => 'required|in:csv,xls,pdf',
            'columns' => 'nullable|string',
            'search' => 'nullable|string',
            'sort' => 'nullable|string',
            'direction' => 'nullable|string|in:asc,desc',
            'filters' => 'nullable|json',
        ]);

        $configKey = $request->configKey;
        $format = $request->format;
        $columns = $request->filled('columns') ? explode(',', $request->columns) : [];


        // Inside exportAll()
        if ($format === 'pdf') {
            $recordCount = (clone $query)->count();
            $maxPdfRows = config('qf.max_pdf_rows', 500);
            if ($recordCount > $maxPdfRows) {
                return response()->json(['error' => "PDF export limited to {$maxPdfRows} rows. Use Excel or CSV for larger datasets."], 422);
            }
        }



        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $modelClass = $resolver->getModel();
        $query = $modelClass::query();

        // Eager load relationships from config
        $relations = array_keys($resolver->getRelations());
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply search (if any)
        if ($request->filled('search')) {
            $search = $request->search;
            $searchableFields = collect($resolver->getFieldDefinitions())
                ->filter(fn($def) => ($def['searchable'] ?? false) === true)
                ->keys()
                ->toArray();
            if (!empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $search) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', '%' . $search . '%');
                    }
                });
            }
        }

        // Apply filters (reuse the same logic as DataTable)
        if ($request->filled('filters')) {
            $filters = json_decode($request->filters, true);
            $this->applyFilters($query, $filters, $resolver);
        }

        // Apply sorting
        if ($request->filled('sort')) {
            $query->orderBy($request->sort, $request->direction ?? 'asc');
        }

        $records = $query->get();

        if ($format === 'pdf') {
            return $this->generatePdf($configKey, $records, $columns);
        }

        $records = $query->get();
        $export = new DataTableExport($configKey, $records, $columns);
        return Excel::download($export, 'export_all_' . now()->format('Ymd_His') . '.' . $format);
    }

    protected function generatePdf(string $configKey, $records, array $columns)
    {

        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $title = $resolver->getConfig()['pageTitle'] ?? null;
        $subtitle = $resolver->getConfig()['subtitle'] ?? 'Data Export';

        if (!$title) {
            // Fallback: model name formatted
            $modelName = class_basename($resolver->getModel());
            $title = ucwords(str_replace('_', ' ', \Str::snake($modelName)));
        }

        $definitions = $resolver->getFieldDefinitions();

        // Determine which columns to use
        if (empty($columns)) {
            $columns = array_keys($definitions);
        }

        // Prepare headings with labels
        $headings = [];
        foreach ($columns as $field) {
            $headings[$field] = $definitions[$field]['label'] ?? ucfirst($field);
        }

        // Transform records to have resolved values for each column
        $transformedRecords = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $field) {
                $row[$field] = $this->getFieldValueForExport($record, $field, $definitions);
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

        // apply orientation, paper size, etc.
        if (isset($options['orientation'])) {
            $pdf->setPaper('a4', $options['orientation']);
        }
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper'], $options['orientation'] ?? 'portrait');
        }

        return $pdf->download('export_' . now()->format('Ymd_His') . '.pdf');
    }

    public function queueExport(Request $request)
    {
        $request->validate([
            'configKey' => 'required|string',
            'format' => 'required|in:csv,xls,pdf',
            'columns' => 'nullable|string',
            'filters' => 'nullable|json',
            'options' => 'nullable|json',
        ]);

        $export = Export::create([
            'user_id' => auth()->id(),
            'config_key' => $request->configKey,
            'filters' => json_decode($request->filters ?? '[]', true),
            'columns' => $request->has('columns') ? array_values(array_filter(explode(',', $request->columns), fn($c) => trim($c) !== '')) : [],
            'format' => $request->format,
            'options' => $request->options ? json_decode($request->options, true) : [],
            'status' => 'pending',
        ]);

        GenerateExport::dispatch($export->id);

        return response()->json([
            'export_id' => $export->id,
            'message' => 'Export queued successfully.',
        ]);
    }


    public function exportStatus($id)
    {
        $export = Export::findOrFail($id);
        $fileUrl = $export->status === 'completed' && $export->download_token
            ? route('export.download', ['token' => $export->download_token])
            : null;

        // Calculate chunk progress
        $completedChunks = 0;
        $totalChunks = $export->total_chunks ?? 0;
        if ($totalChunks > 0 && in_array($export->status, ['processing', 'pending', 'completed'])) {
            $completedChunks = ExportChunk::where('export_id', $export->id)->count();
        }

        return response()->json([
            'status' => $export->status,
            'file_url' => $fileUrl,
            'file_size' => $export->file_size,
            'error' => $export->error_message,
            'completed_at' => $export->completed_at,
            'completed_chunks' => $completedChunks,
            'total_chunks' => $totalChunks,
        ]);
    }


    public function download($token)
    {
        $export = Export::where('download_token', $token)->first();

        if (!$export) {
            abort(404, 'Export not found.');
        }

        if (!$export->isValid()) {
            // Optional: delete expired record and file
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
            }
            $export->delete();
            abort(410, 'This download link has expired.');
        }

        if ($export->status !== 'completed' || !$export->file_path) {
            abort(404, 'File not ready or missing.');
        }

        $disk = Storage::disk('local');
        $path = $export->file_path;

        if (!$disk->exists($path)) {
            abort(404, 'File not found on server.');
        }

        // Optional: one‑time use – delete the token after download
        // $export->update(['download_token' => null, 'expires_at' => null]);

        // ✅ Use the actual file extension from the stored file
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'export.' . $extension;

        return $disk->download($path, $filename);

    }




    // Filter methods (will be replaced by AppliesFilters trait later)
    protected function applyFilters($query, array $filters, ConfigResolver $resolver)
    {
        $fieldDefinitions = $resolver->getFieldDefinitions();

        foreach ($filters as $filter) {
            if (!isset($fieldDefinitions[$filter['field']])) {
                continue;
            }

            $field = $filter['field'];
            $type = $filter['type'] ?? 'string';
            $operator = $filter['operator'] ?? 'equals';
            $value = $filter['value'];

            switch ($type) {
                case 'string':
                    $this->applyStringFilter($query, $field, $operator, $value);
                    break;
                case 'number':
                    $this->applyNumberFilter($query, $field, $operator, $value);
                    break;
                case 'date':
                    $this->applyDateFilter($query, $field, $operator, $value);
                    break;
                case 'boolean':
                    $this->applyBooleanFilter($query, $field, $operator, $value);
                    break;
                case 'select':
                    $this->applySelectFilter($query, $field, $operator, $value);
                    break;
                default:
                    $query->where($field, $value);
            }
        }
    }

    protected function applyStringFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'contains':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value . '%');
                break;
            case 'ends_with':
                $query->where($field, 'like', '%' . $value);
                break;
            default:
                $query->where($field, $value);
        }
    }

    protected function applyNumberFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
            case 'less_than':
                $query->where($field, '<', $value);
                break;
            case 'greater_than_or_equals':
                $query->where($field, '>=', $value);
                break;
            case 'less_than_or_equals':
                $query->where($field, '<=', $value);
                break;
            case 'between':
                if (!empty($value['min']))
                    $query->where($field, '>=', $value['min']);
                if (!empty($value['max']))
                    $query->where($field, '<=', $value['max']);
                break;
        }
    }

    protected function applyDateFilter($query, $field, $operator, $value)
    {
        $now = now();
        switch ($operator) {
            case 'equals':
                $query->whereDate($field, $value);
                break;
            case 'not_equals':
                $query->whereDate($field, '!=', $value);
                break;
            case 'greater_than':
                $query->whereDate($field, '>', $value);
                break;
            case 'less_than':
                $query->whereDate($field, '<', $value);
                break;
            case 'between':
                if (!empty($value['start']))
                    $query->whereDate($field, '>=', $value['start']);
                if (!empty($value['end']))
                    $query->whereDate($field, '<=', $value['end']);
                break;
            case 'today':
                $query->whereDate($field, $now->toDateString());
                break;
            case 'this_week':
                $query->whereBetween($field, [
                    $now->copy()->startOfWeek()->toDateString(),
                    $now->copy()->endOfWeek()->toDateString()
                ]);
                break;
            case 'this_month':
                $query->whereMonth($field, $now->month)->whereYear($field, $now->year);
                break;
            case 'this_year':
                $query->whereYear($field, $now->year);
                break;
            case 'last_week':
                $lastWeek = $now->copy()->subWeek();
                $query->whereBetween($field, [
                    $lastWeek->copy()->startOfWeek()->toDateString(),
                    $lastWeek->copy()->endOfWeek()->toDateString()
                ]);
                break;
            case 'last_month':
                $lastMonth = $now->copy()->subMonth();
                $query->whereMonth($field, $lastMonth->month)->whereYear($field, $lastMonth->year);
                break;
            case 'last_year':
                $lastYear = $now->copy()->subYear();
                $query->whereYear($field, $lastYear->year);
                break;
        }
    }

    protected function applyBooleanFilter($query, $field, $operator, $value)
    {
        if ($value !== '') {
            $query->where($field, $value);
        }
    }

    protected function applySelectFilter($query, $field, $operator, $value)
    {
        if ($value !== '') {
            if ($operator === 'in') {
                $query->whereIn($field, (array) $value);
            } else {
                $query->where($field, $value);
            }
        }
    }





public function cancelExport($id)
{
    $export = Export::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

    if (!in_array($export->status, ['pending', 'processing'])) {
        return response()->json(['message' => 'Cannot cancel export in current status'], 400);
    }

    $export->update([
        'status' => 'cancelled',
        'error_message' => 'Cancelled by user',
        'completed_at' => now(),
    ]);

    // Delete all chunk files and the parent directory
    $exportDir = "exports/{$export->id}";
    if (Storage::disk('local')->exists($exportDir)) {
        Storage::disk('local')->deleteDirectory($exportDir);
    }

    // Also delete any ExportChunk records (optional, but keeps DB clean)
    ExportChunk::where('export_id', $export->id)->delete();

    return response()->json(['message' => 'Export cancelled']);
}





    public function exportTemplate($configKey)
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
        $fieldDefinitions = $resolver->getFieldDefinitions();

        $hiddenOnTable = $resolver->getHiddenFields()['onTable'] ?? [];
        $columns = array_diff_key($fieldDefinitions, array_flip($hiddenOnTable));
        $fieldNames = array_keys($columns);

        // Build example row with human‑readable values
        $exampleRow = [];
        $relationSheets = [];

        foreach ($columns as $field => $def) {
            // For relationship fields, add lookup sheet data
            if (isset($def['relationship'])) {
                $relationModel = $def['relationship']['model'] ?? null;
                $displayField = $def['relationship']['display_field'] ?? 'name';
                if ($relationModel && class_exists($relationModel)) {
                    // Fetch all records for lookup sheet
                    $records = $relationModel::orderBy($displayField)->get();
                    $lookupData = [];
                    foreach ($records as $record) {
                        $lookupData[] = [$record->id, $record->$displayField];
                    }
                    if (!empty($lookupData)) {
                        // Use display field name as sheet identifier
                        $sheetName = $def['label'] ?? $field;
                        $relationSheets[$sheetName] = $lookupData;
                    }
                    // Example row: use the first record's display value
                    $firstRecord = $records->first();
                    $exampleRow[] = $firstRecord ? $firstRecord->$displayField : 'Example ' . $def['label'];
                } else {
                    $exampleRow[] = 'Example ' . $def['label'];
                }
            } else {
                // Non‑relationship fields: use type‑based placeholder
                $exampleRow[] = $this->getExampleValue($def);
            }
        }

        // Collect fields with inline options (no relationship)
        $optionsReference = [];
        foreach ($columns as $field => $def) {
            if (!isset($def['relationship']) && isset($def['options']) && is_array($def['options']) && !isset($def['options']['model'])) {
                $optionsReference[$field] = $def['options'];
            }
        }

        // Then pass it to the export
        $export = new TemplateExport($fieldNames, $exampleRow, $relationSheets, $optionsReference);
        $fileName = 'template_' . $configKey . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    protected function getExampleValue(array $definition): string
    {
        $type = $definition['field_type'] ?? 'string';
        $multiSelect = $definition['multiSelect'] ?? false;

        // 1. Handle inline options (no relationship)
        if (!empty($definition['options']) && is_array($definition['options']) && !isset($definition['options']['model'])) {
            $options = $definition['options'];
            if ($multiSelect) {
                // Multi‑select: return first 3 labels as comma‑separated string
                $firstFew = array_slice($options, 0, 3);
                return implode(', ', $firstFew);
            } else {
                // Single select: return first label
                return reset($options);
            }
        }

        // 2. Handle relationship fields (with lookup)
        if (isset($definition['relationship'])) {
            return $this->getRelationshipExample($definition);
        }

        // 3. Fallback: type‑based placeholders
        return match ($type) {
            'string', 'textarea', 'text' => 'Example ' . ($definition['label'] ?? 'text'),
            'number', 'integer', 'float' => '123',
            'datepicker', 'datetimepicker' => date('Y-m-d'),
            'timepicker' => '09:00',
            'checkbox', 'boolcheckbox', 'boolradio' => '1',
            'select', 'livewire-searchable-select' => 'Example',
            'file', 'image', 'photo', 'picture' => 'example.jpg',
            default => 'example',
        };
    }

    protected function getRelationshipExample(array $definition): string
    {
        $relatedModel = $definition['relationship']['model'] ?? null;
        $displayField = $definition['relationship']['display_field'] ?? 'name';

        if ($relatedModel && class_exists($relatedModel)) {
            // Fetch the first record (or any one) to get a real example
            $exampleRecord = $relatedModel::query()->first();
            if ($exampleRecord) {
                return $exampleRecord->$displayField . ' (auto‑matched)';
            }
        }
        return 'Example ' . ($definition['label'] ?? 'related record');
    }

    protected function getFirstOptionKey(array $definition): string
    {
        $options = $definition['options'] ?? [];
        if (empty($options))
            return 'example';
        // If options is a model reference, we cannot get a static key easily; use 'example'
        if (isset($options['model']))
            return 'example';
        // Get first key of associative array
        return array_key_first($options);
    }




}