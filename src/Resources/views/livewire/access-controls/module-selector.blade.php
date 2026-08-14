<div class="d-flex flex-wrap align-items-center gap-2">
    <div class="d-flex align-items-center gap-2">
        <label for="access-control-scope" class="form-label mb-0 text-xs fw-bold text-uppercase text-secondary">
            Role
        </label>
        <select
            id="access-control-scope"
            class="form-select form-select-sm"
            wire:model.live="selectedScopeId"
        >
            <option value="">-- Select Role --</option>
            @foreach($scopeNames as $scopeId => $scopeName)
                <option value="{{ $scopeId }}">{{ $scopeName }}</option>
            @endforeach
        </select>
    </div>

    <div class="d-flex align-items-center gap-2">
        <label for="access-control-module" class="form-label mb-0 text-xs fw-bold text-uppercase text-secondary">
            Module
        </label>
        <select
            id="access-control-module"
            class="form-select form-select-sm"
            wire:model.live="selectedModule"
        >
            <option value="">-- Select Module --</option>
            @foreach($moduleNames as $moduleName)
                <option value="{{ $moduleName }}">{{ ucfirst($moduleName) }}</option>
            @endforeach
        </select>
    </div>

    <button
        type="button"
        class="btn btn-sm btn-primary rounded-pill px-3 text-uppercase fw-bold"
        wire:click="manageAccessControl"
        wire:loading.attr="disabled"
    >
        OK
    </button>
</div>
