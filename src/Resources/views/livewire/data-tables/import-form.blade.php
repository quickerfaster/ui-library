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
        <div wire:poll.5s="checkImportStatus"></div>

        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span>
            </div>
            <p>Import in progress... You can close this modal and you'll be notified when complete.</p>
        </div>
        {{-- 
        <div class="mb-3 form-check">
            <input type="checkbox" id="runInBackground" wire:model="runInBackground" class="form-check-input">
            <label for="runInBackground" class="form-check-label">Run in background (close modal immediately)</label>
            <small class="text-muted d-block">Recommended for large files. For small imports, leave unchecked to
                wait.</small>
        </div>
        --}}
        <div class="text-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
</div>
