<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Imports;

use QuickerFaster\UILibrary\Models\Import;
use QuickerFaster\UILibrary\Models\ImportChunk;
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



    public function status($id)
    {
        $import = Import::findOrFail($id);
        $completedChunks = ImportChunk::where('import_id', $import->id)->whereIn('status', ['completed', 'failed'])->count();
        $totalChunks = $import->total_chunks ?? 0;
        $errorFileUrl = $import->error_file ? route('import.download-errors', $import) : null;

        return response()->json([
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->failed_rows,
            'completed_chunks' => $completedChunks,
            'total_chunks' => $totalChunks,
            'error_file_url' => $errorFileUrl,
        ]);
    }



}

