<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Prints;

use Illuminate\Http\Request;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Services\Exports\DataTableExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Routing\Controller;
class PrintController extends Controller
{




public function print(Request $request)
{
    $configKey = $request->configKey;
    $resolver = app(ConfigResolver::class, ['configKey' => $configKey]);
    $modelClass = $resolver->getModel();

    // Build query with filters (same as DataTable)
    $query = $modelClass::query();
    if ($request->search) {
        $searchableFields = collect($resolver->getFieldDefinitions())
            ->filter(fn($def) => ($def['searchable'] ?? false) === true)
            ->keys()
            ->toArray();
        $query->where(function ($q) use ($request, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', '%' . $request->search . '%');
            }
        });
    }
    if ($request->sort && $request->direction) {
        $query->orderBy($request->sort, $request->direction);
    }
    // Apply filters from JSON if any
    if ($request->filters) {
        $filters = json_decode($request->filters, true);
        foreach ($filters as $filter) {
            if (is_array($filter) && count($filter) === 3) {
                [$field, $operator, $value] = $filter;
                $query->where($field, $operator, $value);
            }
        }
    }

    $records = $query->get();

    // Get columns to display (exclude hidden on table)
    $hiddenOnTable = $resolver->getHiddenFields()['onTable'] ?? [];
    $columns = array_filter(
        $resolver->getFieldDefinitions(),
        fn($field) => !in_array($field, $hiddenOnTable),
        ARRAY_FILTER_USE_KEY
    );

    
    return view('qf::print.data-table', [
        'configKey' => $configKey,
        'records'   => $records,
        'columns'   => $columns,
        'title'     => $resolver->getConfig()['pageTitle'] ?? 'Data Export',
    ]);

    
}


}