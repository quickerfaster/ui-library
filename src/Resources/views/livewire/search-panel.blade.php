<div>
    <div class="card border-0 shadow-none">
        <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Advanced Search</h5>
            @if(!empty($searchTerm) || count($selectedColumns) > 0)
                <span class="badge bg-primary">Active</span>
            @endif
        </div>
        <div class="card-body">
            {{-- Search input with debounce --}}
            <div class="mb-4">
                <label class="form-label fw-semibold">Search term</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="searchTerm" 
                       class="form-control"
                       placeholder="Enter keywords...">
                <small class="text-muted">Search is case‑insensitive and uses "starts with" (or exact if toggled).</small>
            </div>

            {{-- Performance warning --}}
            @if(count($allColumns) > 3)
                <div class="alert alert-warning alert-sm py-2 mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <small>Selecting many columns may slow down search. For best performance, choose 1-3 columns.</small>
                </div>
            @endif

            {{-- Column selection --}}
            <div class="mb-4">
                <label class="form-label fw-semibold">Search in columns</label>
                <div class="row">
                    @foreach($allColumns as $field => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       wire:model.live="selectedColumns" value="{{ $field }}"
                                       id="col_{{ $field }}">
                                <label class="form-check-label" for="col_{{ $field }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(count($allColumns) === 0)
                    <p class="text-muted">No searchable columns available.</p>
                @endif
            </div>

            {{-- Exact match toggle --}}
            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" wire:model.live="exactMatch" id="exactMatch">
                    <label class="form-check-label" for="exactMatch">
                        <i class="fas fa-equals me-1"></i> Exact match
                        <small class="text-muted d-block">(Search for exact value instead of "starts with")</small>
                    </label>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top d-flex justify-content-end gap-2">
            <button wire:click="resetSearch" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-undo-alt"></i> Reset all
            </button>
        </div>
    </div>
</div>