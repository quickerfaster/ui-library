<div class="p-4">
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> 
        Text file preview is available after downloading.
    </div>
    <pre class="bg-light p-3 rounded" style="max-height: 500px; overflow: auto;">{{ $textContent ?? 'Loading...' }}</pre>
</div>