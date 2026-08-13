<x-qf::navigation-layout
    configKey="system.dashboards.dashboard"
    context="dashboard"
    moduleName="system"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]"
>
    <livewire:qf.dashboard config-key="system.dashboards.dashboard" />
</x-qf::navigation-layout>
