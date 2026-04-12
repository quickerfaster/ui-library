
<div class="row g-2">
    @hasanyrole('admin|super_admin')

        <!-- Scope Selection Dropdown -->
        <div class="input-group col-12 w-100 col-sm-auto w-sm-auto">
            <select id="scopeSelect" wire:model.live.500ms="selectedScopeId"  class="form-select rounded-pill p-1 ps-3 pe-sm-5 px-sm-4 m-0 small-control"  >
                <option value="">Select {{strtolower($selectedScopeName)}}...</option>
                @foreach($scopeNames as $id => $scopeName)
                    <option value="{{ $id }}">{{ $scopeName }}</option>
                @endforeach
            </select>
        </div>

        <!-- Module Selection Dropdown (Conditional) -->
        @if (!$isUrlAccess)
            <div  class="input-group col-12 w-100 col-sm-auto w-sm-auto" >
                <select id="moduleSelect" wire:model.live.500ms="selectedModule" class="form-select rounded-pill p-1 ps-3 pe-sm-5 px-sm-4 m-0 small-control">
                    <option value="">Select module...</option>
                    @foreach($moduleNames as $moduleName)
                        <option value="{{ strtolower($moduleName) }}">{{ $moduleName }}</option>
                    @endforeach
                </select>
            </div>
        @endif


        <!-- Navigation Button -->
        <div x-data class="col-12 w-100 col-sm-auto w-sm-auto" style="height: 2em" >
            <button
                style="height: 2.5em"
                wire:click="manageAccessControl"
                :class="$wire.selectedScopeId && $wire.selectedModule? 'btn-primary': 'btn-secondary'"
                class="btn rounded-pill py-0 small-control"
                :disabled="!$wire.selectedScopeId || !$wire.selectedModule">
                OK
            </button>
        </div>

    @endhasanyrole
</div>



