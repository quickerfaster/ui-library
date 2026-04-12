    <x-qf::navigation-layout configKey="admin.role" context="Users & Permissions" moduleName="admin" :overrides=[]></x-qf::navigation-layout>
        
    
    @hasanyrole('admin|super_admin')
        <x-slot name="mainTitle"> <strong class="text-info text-gradient">{{ $selectedScope?->name}}</strong> Permissions</x-slot>
            <x-slot name="subtitle"> {{ $selectedModuleName? ucfirst($selectedModuleName. " Module"): ''}}</x-slot>
            <x-slot name="controls">
                {{-- - --@include("admin::access-controls.module-selector")
            </x-slot>

            @if($showResourceControlButtonGroup)
                <div class="row g-5">
                    @foreach ($this->resourceNames as $key => $resourceName)
                        @php
                            $preparedResourceName = str_replace('_', ' ',Str::snake($resourceName));
                            $title = ucwords($preparedResourceName) . " Management";
                            $subtitle = "<div class='text-xs mt-2'> What <strong class='text-dark'>".$selectedScope?->name."</strong> can do on <strong class='text-dark'>". ucfirst($preparedResourceName) . " records?</strong></div>";
                        @endphp


                        {{-- -- -<div class="col-12 col-sm-6">
                            <livewire:qf::widgets.buttons.toggle-button-group
                                :title="$title"
                                :subtitle="$subtitle"
                                :componentId="$resourceName.'-'.$key"
                                :buttons="$resourceControlButtonGroup[$resourceName]?? []"
                                :groupId="$resourceName"
                                stateSyncMethod="method"
                                :data="[
                                    'selectedScope' => $this->selectedScope,
                                    'selectedScopeId' => $this->selectedScopeId,
                                    'resourceName' => $resourceName,
                                    'controlsCSSClasses' => $controlsCSSClasses,
                                ]"
                            > --}}
                        </div>
                    @endforeach
                </div>
            @else
                <h4>Need Help?</h4>
                <p>Select <strong class="text-primary">[Role],</strong>  then select <strong class="text-primary">[Module]</strong> and click <strong class="text-primary">[OK]</strong>    to set the permission of  <strong class="text-primary"> user that has that role can/cannot do.</strong> </p>
            @endif
         @endhasanyrole




 
</x-qf::navigation-layout>
