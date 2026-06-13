<div>
    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    {{-- Initial upload form (no import started, no result) --}}
    @if (!$importId && !$importResult)
        <div class="mb-3">
            <label for="file" class="form-label">Choose File (CSV, Excel)</label>
            <input type="file" id="file" wire:model.live="file" class="form-control" accept=".csv,.xlsx,.xls">
            @error('file')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <div wire:loading wire:target="file" class="text-muted small mt-1">Uploading...</div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" id="hasHeaderRow" wire:model.live="hasHeaderRow" class="form-check-input">
            <label for="hasHeaderRow" class="form-check-label">First row contains column headers</label>
        </div>

        @if ($previewHeaders || $previewRows)
            <hr>
            <h6>Preview</h6>
            <div class="table-responsive" style="max-height: 200px;">
                <table class="table table-sm table-bordered">
                    @if ($previewHeaders)
                        <thead>
                            <tr>
                                @foreach ($previewHeaders as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    @endif
                    <tbody>
                        @foreach ($previewRows as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($previewHeaders)
                <hr>
                <h6>Column Mapping</h6>
                <p class="text-muted small">Map file columns to database fields. Leave empty to skip a column.</p>
                @foreach ($columnMapping as $field => $columnIndex)
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>{{ $field }}</strong></div>
                        <div class="col-md-8">
                            <select wire:model.live="columnMapping.{{ $field }}" class="form-select">
                                <option value="">-- Skip --</option>
                                @foreach ($previewHeaders as $idx => $header)
                                    <option value="{{ $idx }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif

        @if ($file && empty($error))
            <div class="mt-3">
                <button type="button" class="btn btn-primary" wire:click="startImport" wire:loading.attr="disabled">
                    <span wire:loading.remove>Start Import</span>
                    <span wire:loading>Processing...</span>
                </button>
            </div>
        @endif

        {{-- Processing state --}}
    @elseif($importId && !$importResult)
        <div wire:poll.2s="checkImportStatus">
            @if ($importStatus === 'processing' || $importStatus === 'pending')
                <p>Your import is being processed. Please wait...</p>

                @if ($totalChunks > 0)
                    <div class="progress mt-2" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary py-3"
                            style="width: {{ ($completedChunks / $totalChunks) * 100 }}%">
                            {{ round(($completedChunks / $totalChunks) * 100) }}%
                        </div>
                    </div>
                    <p class="mt-2 small text-muted">
                        {{ $completedChunks }} of {{ $totalChunks }} chunks completed
                    </p>
                    <p class="mt-1 small text-muted">
                        Rows processed: {{ $successfulRows + $failedRows }} / {{ $totalRows }}
                        ({{ $successfulRows }} succeeded, {{ $failedRows }} failed)
                    </p>
                @else
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated py-3" style="width: 100%">
                        </div>
                    </div>
                    <p class="mt-2 small text-muted">Preparing chunks...</p>
                @endif

                <div class="d-flex justify-content-between mt-3">
                    <button wire:click="cancelImport" class="btn btn-sm btn-danger">
                        <i class="fas fa-times"></i> Cancel Import
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @elseif($importStatus === 'completed')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Import completed!<br>
                    Processed: {{ $totalRows }} rows<br>
                    Success: {{ $successfulRows }}, Failed: {{ $failedRows }}
                    @if ($errorFileUrl)
                        <div class="mt-2">
                            <a href="{{ $errorFileUrl }}" class="btn btn-sm btn-outline-danger" target="_blank">
                                <i class="fas fa-download"></i> Download Error Report
                            </a>
                        </div>
                    @endif
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @elseif($importStatus === 'failed')
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Import failed.
                    @if ($error)
                        <br>{{ $error }}
                    @endif
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @endif
        </div>




        {{-- Result state --}}
    @elseif($importResult)
        <div class="mt-3">
            @if (isset($importResult['successful']))
                <div class="alert {{ $importResult['failed'] > 0 ? 'alert-warning' : 'alert-success' }}">
                    <i
                        class="fas {{ $importResult['failed'] > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' }}"></i>
                    Import completed: {{ $importResult['successful'] }} records imported,
                    {{ $importResult['failed'] }} failed.
                    @if (isset($importResult['errorFileUrl']))
                        <div class="mt-2">
                            <a href="{{ $importResult['errorFileUrl'] }}" class="btn btn-sm btn-outline-danger"
                                target="_blank">
                                <i class="fas fa-download"></i> Download Error Report (CSV)
                            </a>
                        </div>
                    @endif
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @elseif(isset($importResult['error']))
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Import failed: {{ $importResult['error'] }}
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @endif
        </div>
    @endif





    @script
        <script>
            let pollingInterval = null;

            function startImportPolling(importId) {
                if (pollingInterval) clearInterval(pollingInterval);
                pollingInterval = setInterval(async () => {
                    const response = await fetch(`/import/status/${importId}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();

                    // Use $wire to update Livewire properties
                    $wire.set('importStatus', data.status);
                    $wire.set('completedChunks', data.completed_chunks || 0);
                    $wire.set('totalChunks', data.total_chunks || 0);
                    $wire.set('totalRows', data.total_rows || 0);
                    $wire.set('successfulRows', data.successful_rows || 0);
                    $wire.set('failedRows', data.failed_rows || 0);
                    $wire.set('errorFileUrl', data.error_file_url || null);

                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(pollingInterval);
                        // Reopen modal if hidden
                        const modalEl = document.getElementById('{{ $modalId }}');
                        if (modalEl && !modalEl.classList.contains('show')) {
                            const modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    }
                }, 2000);
            }

            // Listen for the event emitted from Livewire when a new import starts
            $wire.on('startImportPolling', (importId) => {
                startImportPolling(importId);
            });
        </script>
    @endscript
</div>
