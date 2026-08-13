<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Documents;


use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Routing\Controller;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;

/**
 * Generic document download controller.
 *
 * The $document parameter should be an Eloquent model instance with:
 * - $document->document (string file path)
 * - $document->name (string display name)
 */
class DocumentController extends Controller
{
    public function download($document): StreamedResponse
    {
        if (!app(AuthorizationService::class)->canPerformAction(auth()->user(), ["view_document"], $document))
            abort(403, 'You do not have permission to download this file.');


        if (!Storage::disk('documents')->exists($document->document)) {
            abort(404, 'File not found.');
        }
        
        return Storage::disk('documents')->download($document->document, $document->name);
    }
}
