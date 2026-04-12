<div>
    @if (!$exportId)
        <p>Initializing export...</p>
    @else
        @if ($status === 'pending' || $status === 'processing')
            <p>Your export is being prepared. Please wait...</p>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
            <p class="mt-2 small text-muted">This may take a moment depending on the data size.</p>
        @elseif ($status === 'completed')
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Export completed successfully!
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ $downloadUrl }}" class="btn btn-success" download>Download</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        @elseif ($status === 'failed')
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Export failed: {{ $error }}
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        @endif
    @endif

    @script
    <script>
        $wire.on('queueExport', async (data) => {
            const params = data[0];
            try {
                const response = await fetch('{{ route("export.queue") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(params)
                });

                if (!response.ok) {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Server error');
                    } else {
                        const text = await response.text();
                        console.error('Non-JSON response:', text.substring(0, 200));
                        throw new Error('Server returned HTML');
                    }
                }

                const result = await response.json();
                if (result.export_id) {
                    $wire.set('exportId', result.export_id);
                    $wire.set('status', 'pending'); // optional
                    // Start polling
                    const interval = setInterval(async () => {
                        const statusResponse = await fetch(`/export/status/${result.export_id}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const statusData = await statusResponse.json();
                        $wire.set('status', statusData.status);
                        if (statusData.status === 'completed' || statusData.status === 'failed') {
                            clearInterval(interval);
                            if (statusData.status === 'completed') {
                                $wire.set('downloadUrl', statusData.file_url);
                            } else {
                                $wire.set('error', statusData.error);
                            }
                            // Optionally auto-close modal after a delay if you want
                            // setTimeout(() => $wire.dispatch('closeExportModal'), 2000);
                        }
                    }, 2000);
                }
            } catch (error) {
                console.error('Export queue error:', error);
                // Show error in modal instead of alert
                $wire.set('status', 'failed');
                $wire.set('error', 'Failed to queue export: ' + error.message);
            }
        });
    </script>
    @endscript
</div>