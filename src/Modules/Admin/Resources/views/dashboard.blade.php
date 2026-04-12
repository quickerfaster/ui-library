<x-qf::navigation-layout
    configKey="admin.dashboard" 
    context="dashboard" 
    moduleName="admin" 
    :overrides="[
        'top_bar' => ['enabled' => true],
        'context_menu' => ['enabled' => false],
    ]"
>
    <livewire:qf.dashboard config-key="xxxxxx" />
</x-qf::navigation-layout>
