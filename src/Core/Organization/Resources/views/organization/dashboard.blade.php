<x-qf::navigation-layout
    configKey="organization.dashboards.dashboard"
    context="dashboard"
    moduleName="organization"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]"
>
    <livewire:qf.dashboard config-key="organization.dashboards.dashboard" />
</x-qf::navigation-layout>