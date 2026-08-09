<div class="module-selector">
    <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">
        {{ __('qf::nav.modules') }}
    </h6>
    <ul class="list-group list-group-flush">
        @foreach($modules as $key => $module)
            <li class="list-group-item border-0 px-0">
                <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                    <label class="form-check-label mb-0 ms-2" for="module-{{ $key }}">
                        <i class="fas {{ $module['icon'] ?? 'fa-cube' }} me-2"></i>
                        {{ $module['label'] ?? ucfirst($key) }}
                    </label>
                    <input
                        class="form-check-input ms-auto"
                        type="checkbox"
                        id="module-{{ $key }}"
                        wire:model.live="moduleStates.{{ $key }}"
                    >
                </div>
            </li>
        @endforeach
    </ul>
</div>
