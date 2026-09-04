<x-qf::navigation-layout
    configKey="admin.dashboards.dashboard_users_overview"
    context="Users"
    moduleName="admin"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    <livewire:qf.dashboard config-key="admin.dashboards.dashboard_users_overview" />
</x-qf::navigation-layout>