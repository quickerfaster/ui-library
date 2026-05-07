<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Imports;

use QuickerFaster\UILibrary\Models\Import;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;


class ImportController extends Controller
{
    public function downloadErrors(Import $import)
    {
        if (!$import->error_file || !Storage::disk('local')->exists($import->error_file)) {
            abort(404, 'Error file not found.');
        }

        return Storage::disk('local')->download($import->error_file, 'import_errors.csv');
    }
}

