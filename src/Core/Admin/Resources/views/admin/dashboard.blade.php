<x-qf::navigation-layout
    configKey="admin.dashboards.dashboard"
    context="dashboard"
    moduleName="admin"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]"
>
    <livewire:qf.dashboard config-key="admin.dashboards.dashboard" />
</x-qf::navigation-layout>
