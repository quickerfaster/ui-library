<?php

namespace QuickerFaster\UILibrary\Http\Controllers\Documents;



use App\Modules\Hr\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Routing\Controller;
use App\Modules\Admin\Services\AuthorizationService;

class DocumentController extends Controller
{
    public function download(Document $document): StreamedResponse
    {
        if (!app(AuthorizationService::class)->canPerformAction(auth()->user(), ["view_document"], $document))
            abort(403, 'You do not have permission to download this file.');


        if (!Storage::disk('documents')->exists($document->document)) {
            abort(404, 'File not found.');
        }
        
        return Storage::disk('documents')->download($document->document, $document->name);
    }
}