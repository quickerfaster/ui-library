<div>
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Progress</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if (!$exportId)
                        <p>Initializing export...</p>
                    @else
                        @if ($status === 'pending' || $status === 'processing')
                            <p>Your export is being prepared. Please wait...</p>

                            @if ($totalChunks > 0)
                                <div class="progress mt-2" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary py-3"
                                        style="width: {{ ($completedChunks / $totalChunks) * 100 }}%">
                                        {{ round(($completedChunks / $totalChunks) * 100) }}%
                                    </div>
                                </div>
                                <p class="mt-2 small text-muted">
                                    {{ $completedChunks }} of {{ $totalChunks }} parts completed
                                </p>
                            @else
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated py-3"
                                        style="width: 100%"></div>
                                </div>
                                <p class="mt-2 small text-muted">Preparing chunks...</p>
                            @endif

                            <div class="d-flex justify-content-between mt-3">
                                <button wire:click="cancelExport" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i> Cancel Export
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
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
                        @elseif ($status === 'cancelled')
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Export cancelled by user.
                            </div>
                            <div class="text-end">
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
                </div>
            </div>
        </div>
    </div>

    @script
        <script>
            let pollingInterval = null;

            function startPolling(exportId) {
                if (pollingInterval) clearInterval(pollingInterval);
                pollingInterval = setInterval(async () => {
                    const response = await fetch(`/export/status/${exportId}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    $wire.set('status', data.status);
                    $wire.set('fileSize', data.file_size);
                    $wire.set('completedChunks', data.completed_chunks || 0);
                    $wire.set('totalChunks', data.total_chunks || 0);

                    if (data.status === 'completed') {
                        clearInterval(pollingInterval);
                        $wire.set('downloadUrl', data.file_url);
                        // Force reopen modal if hidden
                        const modalEl = document.getElementById('{{ $modalId }}');
                        if (modalEl && !modalEl.classList.contains('show')) {
                            const modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    } else if (data.status === 'failed') {
                        clearInterval(pollingInterval);
                        $wire.set('error', data.error);
                        const modalEl = document.getElementById('{{ $modalId }}');
                        if (modalEl && !modalEl.classList.contains('show')) {
                            const modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    }
                }, 2000);
            }

            $wire.on('startExport', async (params) => {
                try {
                    const response = await fetch('{{ route('export.queue') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(params[0])
                    });
                    const result = await response.json();
                    if (result.export_id) {
                        $wire.set('exportId', result.export_id);
                        $wire.set('status', 'pending');
                        startPolling(result.export_id);
                    }
                } catch (error) {
                    console.error(error);
                    $wire.set('status', 'failed');
                    $wire.set('error', error.message);
                }
            });

            $wire.on('cancelExport', async (exportId) => {
                try {
                    await fetch(`/export/cancel/${exportId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    clearInterval(pollingInterval);
                    $wire.set('status', 'cancelled');
                    $wire.set('error', 'Export cancelled by user.');
                } catch (error) {
                    console.error('Cancel failed', error);
                }
            });

            document.addEventListener('livewire:navigating', () => {
                if (pollingInterval) clearInterval(pollingInterval);
            });
        </script>
    @endscript
</div>
