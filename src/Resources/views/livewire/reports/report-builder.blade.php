<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>{{ $reportId ? 'Edit' : 'Create' }} Custom Report</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary">← Back to Reports</a>
    </div>

    <div class="row">
        {{-- LEFT PANEL: Available Fields --}}
        <div class="col-md-3 border-end">
            <h5>Available Fields</h5>
            <div class="overflow-auto" style="max-height: 70vh;">
                @foreach ($allFields as $field => $def)
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectedFields"
                            value="{{ $field }}" id="field_{{ $field }}">
                        <label class="form-check-label" for="field_{{ $field }}">
                            {{ $def['label'] ?? ucfirst(str_replace('_', ' ', $field)) }}
                            @if (isset($def['field_type']))
                                <small class="text-muted">({{ $def['field_type'] }})</small>
                            @endif
                        </label>
                    </div>
                @endforeach
            </div>
            @if (count($allFields) === 0)
                <div class="alert alert-warning">No fields found for this module.</div>
            @endif
        </div>

        {{-- RIGHT PANEL: Filters & Controls --}}
        <div class="col-md-3 border-end">
            <h5>Filters</h5>
            @if (count($allFields) > 0)
                @livewire(
                    'qf.filter-panel',
                    [
                        'configKey' => $mainConfigKey,
                        'initialActiveFilters' => $activeFilters,
                    ],
                    key('builder-filters-' . $mainConfigKey)
                )
            @else
                <div class="alert alert-secondary">No filters available.</div>
            @endif

            <hr class="my-3">

            <div class="mb-3">
                <label class="form-label">Report Name</label>
                <input type="text" wire:model="reportName" class="form-control"
                    placeholder="e.g., Active Employees List">
                @error('reportName')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" wire:model="isGlobal" class="form-check-input" id="globalCheck">
                <label class="form-check-label" for="globalCheck">
                    Make available to all users
                </label>
            </div>

            <button wire:click="saveReport" class="btn btn-primary w-100" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $reportId ? 'Update' : 'Save' }} Report</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>

        {{-- CENTER PANEL: Live Preview --}}
        <div class="col-md-6">
            <h5>Live Preview</h5>
            @if (!empty($previewColumns))
                @livewire(
                    'qf.data-table',
                    [
                        'configKey' => $mainConfigKey,
                        'customColumns' => $previewColumns,
                        'initialActiveFilters' => $activeFilters,
                    ],
                    key($tableKey)
                )
            @else
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Select at least one field to preview the report.
                </div>
            @endif
        </div>
    </div>
</div>
