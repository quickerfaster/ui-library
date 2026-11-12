@php
    $pdfUrl = $fileUrl;
    if (!str_starts_with($fileUrl, 'blob:') && !str_starts_with($fileUrl, 'data:')) {
        $pdfUrl = $fileUrl . (str_contains($fileUrl, '?') ? '&' : '?') . 'v=' . uniqid();
    }
@endphp
<embed src="{{ $pdfUrl }}" type="application/pdf" width="100%" height="600px" />