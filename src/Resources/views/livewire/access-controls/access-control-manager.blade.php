    <x-qf::dashboards.default-dashboard>

        @hasanyrole(\QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::ADMIN_ROLES)
        <x-slot name="mainTitle"> <strong class="text-info text-gradient">{{ $selectedScope?->name}}</strong> Permissions</x-slot>
            <x-slot name="subtitle"> {{ $selectedModuleName? ucfirst($selectedModuleName. " Module"): ''}}</x-slot>
            <x-slot name="controls">
                @include("qf::livewire.access-controls.module-selector")
            </x-slot>

            @if($showResourceControlButtonGroup)
                <div class="row mb-3">
                    <div class="col-12 col-md-6">
                        <input
                            type="text"
                            wire:model.live="modelSearch"
                            class="form-control"
                            placeholder="Search models..."
                            aria-label="Search models"
                        >
                    </div>
                </div>

                <div class="bulk-actions mb-3">
                    <span class="fw-bold me-2">Bulk:</span>
                    @foreach ($this->controlList as $control)
                        <button
                            type="button"
                            wire:click="bulkToggle('{{ $control }}', true)"
                            class="btn btn-sm btn-outline-primary me-1 mb-1"
                        >All {{ ucfirst($control) }} On</button>
                        <button
                            type="button"
                            wire:click="bulkToggle('{{ $control }}', false)"
                            class="btn btn-sm btn-outline-secondary me-1 mb-1"
                        >All {{ ucfirst($control) }} Off</button>
                    @endforeach
                </div>

                @if(count($this->filteredResourceNames) === 0)
                    <div class="alert alert-warning">
                        {{ $modelSearch !== '' ? 'No models match your search.' : 'No models found in this module.' }}
                    </div>
                @else
                    <div class="row g-5">
                        @foreach ($this->filteredResourceNames as $key => $resourceName)
                            @php
                                $preparedResourceName = str_replace('_', ' ',Str::snake($resourceName));
                                $title = ucwords($preparedResourceName) . " Management";
                                $subtitle = "<div class='text-xs mt-2'> What <strong class='text-dark'>".$selectedScope?->name."</strong> can do on <strong class='text-dark'>". ucfirst($preparedResourceName) . " records?</strong></div>";
                            @endphp


                            <div class="col-12 col-sm-6" wire:key="resource-{{ $resourceName }}-{{ $key }}">
                                <livewire:qf.toggle-button-group
                                    wire:key="toggle-group-{{ $controlButtonGroupVersion }}-{{ $resourceName }}"
                                    :title="$title"
                                    :subtitle="$subtitle"
                                    :componentId="$resourceName.'-'.$key"
                                    :buttons="$resourceControlButtonGroup[$resourceName]?? []"
                                    :groupId="$resourceName"
                                    :version="$controlButtonGroupVersion"
                                    stateSyncMethod="method"
                                    :data="[
                                        'selectedScope' => $this->selectedScope,
                                        'selectedScopeId' => $this->selectedScopeId,
                                        'resourceName' => $resourceName,
                                        'controlsCSSClasses' => $controlsCSSClasses,
                                    ]"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <h4>Need Help?</h4>
                <p>Select <strong class="text-primary">[Role],</strong>  then select <strong class="text-primary">[Module]</strong> and click <strong class="text-primary">[OK]</strong>    to set the permission of  <strong class="text-primary"> user that has that role can/cannot do.</strong> </p>
            @endif
         @endhasanyrole
    </x-qf::dashboards.default-dashboard>
