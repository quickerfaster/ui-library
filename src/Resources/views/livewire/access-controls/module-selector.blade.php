<div class="module-selector">
    <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">
        {{ __('qf::nav.modules') }}
    </h6>
    <ul class="list-group list-group-flush">
        @forelse($moduleNames as $key => $moduleName)
            <li class="list-group-item border-0 px-0">
                <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                    <label class="form-check-label mb-0 ms-2" for="module-{{ $key }}">
                        <i class="fas fa-cube me-2"></i>
                        {{ is_array($moduleName) ? ($moduleName['label'] ?? ucfirst($key)) : ucfirst($moduleName) }}
                    </label>
                    <input
                        class="form-check-input ms-auto"
                        type="checkbox"
                        id="module-{{ $key }}"
                        wire:model.live="moduleStates.{{ $key }}"
                    >
                </div>
            </li>
        @empty
            <li class="list-group-item border-0 px-0">
                <span class="text-muted">No modules found</span>
            </li>
        @endforelse
    </ul>
</div>
