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
use QuickerFaster\UILibrary\Models\Export; // Ensure this model exists in your main app

class ExportController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'configKey' => 'required|string',
            'ids'       => 'required|string',
            'format'    => 'required|in:csv,xls,pdf',
            'columns'   => 'nullable|string',
        ]);

        $configKey = $request->configKey;
        $ids = explode(',', $request->ids);
        $format = $request->format;
        $columns = $request->has('columns') ? explode(',', $request->columns) : [];

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
            'format'    => 'required|in:csv,xls,pdf',
            'columns'   => 'nullable|string',
            'search'    => 'nullable|string',
            'sort'      => 'nullable|string',
            'direction' => 'nullable|string|in:asc,desc',
            'filters'   => 'nullable|json',
        ]);

        $configKey = $request->configKey;
        $format = $request->format;
        $columns = $request->has('columns') ? explode(',', $request->columns) : [];

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

        $export = new DataTableExport($configKey, $records, $columns);
        $fileName = 'export_all_' . now()->format('Ymd_His') . '.' . $format;
        return Excel::download($export, $fileName);
    }

    protected function generatePdf(string $configKey, $records, array $columns)
    {
        $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
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

        // Get the PDF view (custom or default)
        $controls = $resolver->getControls();
        $pdfView = $controls['files']['export_pdf_view'] ?? 'qf::exports.default-pdf';

        $pdf = Pdf::loadView($pdfView, [
            'records'   => $records,
            'columns'   => $columns,
            'headings'  => $headings,
            'configKey' => $configKey,
        ]);

        return $pdf->download('export_' . now()->format('Ymd_His') . '.pdf');
    }

    // New methods for queued exports
    public function queueExport(Request $request)
    {
        $request->validate([
            'configKey' => 'required|string',
            'format'    => 'required|in:csv,xls,pdf',
            'columns'   => 'nullable|string',
            'filters'   => 'nullable|json',
            'options'   => 'nullable|json',
        ]);

        $export = Export::create([
            'user_id'     => auth()->id(),
            'config_key'  => $request->configKey,
            'filters'     => json_decode($request->filters ?? '[]', true),
            'columns'     => $request->has('columns') ? explode(',', $request->columns) : [],
            'format'      => $request->format,
            'options'     => $request->options ? json_decode($request->options, true) : [],
            'status'      => 'pending',
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
    return response()->json([
        'id'         => $export->id,
        'status'     => $export->status,
        'file_url'   => $export->status === 'completed' 
            ? secure_url(route('export.download', $export->id, false))  // route('export.download', $export->id) none securs https://
            : null,
        'error'      => $export->error_message,
        'completed_at' => $export->completed_at,
    ]);
}

    public function download($id)
    {
        $export = Export::findOrFail($id);
        if ($export->status !== 'completed' || !$export->file_path) {
            abort(404);
        }

        return Storage::disk('local')->download($export->file_path, 'export.' . $export->format);
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
            case 'equals': $query->where($field, $value); break;
            case 'contains': $query->where($field, 'like', '%' . $value . '%'); break;
            case 'starts_with': $query->where($field, 'like', $value . '%'); break;
            case 'ends_with': $query->where($field, 'like', '%' . $value); break;
            default: $query->where($field, $value);
        }
    }

    protected function applyNumberFilter($query, $field, $operator, $value)
    {
        switch ($operator) {
            case 'equals': $query->where($field, $value); break;
            case 'not_equals': $query->where($field, '!=', $value); break;
            case 'greater_than': $query->where($field, '>', $value); break;
            case 'less_than': $query->where($field, '<', $value); break;
            case 'greater_than_or_equals': $query->where($field, '>=', $value); break;
            case 'less_than_or_equals': $query->where($field, '<=', $value); break;
            case 'between':
                if (!empty($value['min'])) $query->where($field, '>=', $value['min']);
                if (!empty($value['max'])) $query->where($field, '<=', $value['max']);
                break;
        }
    }

    protected function applyDateFilter($query, $field, $operator, $value)
    {
        $now = now();
        switch ($operator) {
            case 'equals': $query->whereDate($field, $value); break;
            case 'not_equals': $query->whereDate($field, '!=', $value); break;
            case 'greater_than': $query->whereDate($field, '>', $value); break;
            case 'less_than': $query->whereDate($field, '<', $value); break;
            case 'between':
                if (!empty($value['start'])) $query->whereDate($field, '>=', $value['start']);
                if (!empty($value['end'])) $query->whereDate($field, '<=', $value['end']);
                break;
            case 'today': $query->whereDate($field, $now->toDateString()); break;
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
}