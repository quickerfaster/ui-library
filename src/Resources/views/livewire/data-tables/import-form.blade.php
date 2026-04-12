<div>
    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if(!$importId)
        {{-- Step 1: Upload file --}}
        <div class="mb-3">
            <label for="file" class="form-label">Choose File (CSV, Excel)</label>
            <input type="file" id="file" wire:model.live="file" class="form-control" accept=".csv,.xlsx,.xls">
            @error('file') <span class="text-danger">{{ $message }}</span> @enderror
            <div wire:loading wire:target="file" class="text-muted small mt-1">Uploading...</div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" id="hasHeaderRow" wire:model.live="hasHeaderRow" class="form-check-input">
            <label for="hasHeaderRow" class="form-check-label">First row contains column headers</label>
        </div>

        @if($previewHeaders || $previewRows)
            <hr>
            <h6>Preview</h6>
            <div class="table-responsive" style="max-height: 200px;">
                <table class="table table-sm table-bordered">
                    @if($previewHeaders)
                        <thead>
                            <tr>
                                @foreach($previewHeaders as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    @endif
                    <tbody>
                        @foreach($previewRows as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($previewHeaders)
                <hr>
                <h6>Column Mapping</h6>
                <p class="text-muted small">Map file columns to database fields. Leave empty to skip a column.</p>
                @foreach($columnMapping as $field => $columnIndex)
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <strong>{{ $field }}</strong>
                        </div>
                        <div class="col-md-8">
                            <select wire:model.live="columnMapping.{{ $field }}" class="form-select">
                                <option value="">-- Skip --</option>
                                @foreach($previewHeaders as $idx => $header)
                                    <option value="{{ $idx }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif
    @else

        @if($importId)
            <div wire:poll.5s="checkImportStatus" wire:loading.remove>
                <!-- Optional: show a small indicator that import is running -->
                <div class="text-muted small mt-2">
                    <i class="fas fa-spinner fa-spin"></i> Processing import...
                </div>
            </div>
        @endif



        {{-- Step 2: Import in progress --}}
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Import in progress... You can close this modal and you'll be notified when complete.</p>
        </div>
    @endif

    @if($file && !$importId && empty($error))
        <div class="mt-3">
            <button type="button" class="btn btn-primary" wire:click="startImport" wire:loading.attr="disabled">
                <span wire:loading.remove>Start Import</span>
                <span wire:loading>Processing...</span>
            </button>
        </div>
    @endif
</div>