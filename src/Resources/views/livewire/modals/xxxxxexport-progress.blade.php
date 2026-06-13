<div>
    @if (!$exportId)
        <p>Initializing export...</p>
    @else
        @if ($status === 'pending' || $status === 'processing')
            <p>Your export is being prepared. Please wait...</p>

            @if ($totalChunks > 0)
                <div class="progress mt-2" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary py-3"  role="progressbar"
                        style="width: {{ ($completedChunks / $totalChunks) * 100 }}%"
                        aria-valuenow="{{ $completedChunks }}" aria-valuemin="0" aria-valuemax="{{ $totalChunks }}">
                        {{ round(($completedChunks / $totalChunks) * 100) }}%
                    </div>
                </div>
                <p class="mt-2 small text-muted">
                    {{ $completedChunks }} of {{ $totalChunks }} parts completed
                </p>
            @else
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated py-3" style="width: 100%"></div>
                </div>
                <p class="mt-2 small text-muted">Preparing chunks...</p>
            @endif

            <div class="d-flex justify-content-between mt-3">
                <div class="text-end">
                    <button wire:click="cancelExport" class="btn btn-sm btn-danger">
                        <i class="fas fa-times"></i> Cancel Export
                    </button>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        @elseif ($status === 'completed')
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Export completed successfully!
                @if ($fileSize)
                    ({{ number_format($fileSize / 1024, 2) }} KB)
                @endif
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ $downloadUrl }}" class="btn btn-success" download>
                    <i class="fas fa-file-archive"></i> Download ZIP
                </a>
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
                    const response = await fetch('{{ route('export.queue') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(params)
                    });

                    if (!response.ok) throw new Error('Server error');
                    const result = await response.json();
                    if (result.export_id) {
                        $wire.set('exportId', result.export_id);
                        $wire.set('status', 'pending');

                        // Start polling every 2 seconds
                        const interval = setInterval(async () => {
                            const statusResponse = await fetch(`/export/status/${result.export_id}`, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            const statusData = await statusResponse.json();

                            $wire.set('status', statusData.status);
                            $wire.set('fileSize', statusData.file_size);
                            $wire.set('completedChunks', statusData.completed_chunks || 0);
                            $wire.set('totalChunks', statusData.total_chunks || 0);

                            if (statusData.status === 'completed') {
                                clearInterval(interval);
                                $wire.set('downloadUrl', statusData.file_url);

                                // Show a toast notification (even if modal is closed)
                                /*$wire.dispatch('showAlert', {
                                    type: 'success',
                                    message: `Export completed! <a href="${statusData.file_url}" class="alert-link">Download</a>`,
                                    autoClose: 8000,
                                    html: true
                                });*/
                            } else if (statusData.status === 'failed') {
                                clearInterval(interval);
                                $wire.set('error', statusData.error);
                                $wire.dispatch('showAlert', {
                                    type: 'error',
                                    message: `Export failed: ${statusData.error}`,
                                    autoClose: 5000
                                });
                            }
                        }, 2000);
                    }
                } catch (error) {
                    console.error(error);
                    $wire.set('status', 'failed');
                    $wire.set('error', error.message);
                }
            });





            $wire.on('startPollingForExport', (exportId) => {
    const interval = setInterval(async () => {
        const response = await fetch(`/export/status/${exportId}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        $wire.set('status', data.status);
        $wire.set('fileSize', data.file_size);
        $wire.set('completedChunks', data.completed_chunks || 0);
        $wire.set('totalChunks', data.total_chunks || 0);
        if (data.status === 'completed') {
            clearInterval(interval);
            $wire.set('downloadUrl', data.file_url);
            $wire.dispatch('exportCompleted', { exportId, configKey: '{{ $configKey }}' });
        } else if (data.status === 'failed') {
            clearInterval(interval);
            $wire.set('error', data.error);
        }
    }, 2000);
});



        </script>
    @endscript
</div>
