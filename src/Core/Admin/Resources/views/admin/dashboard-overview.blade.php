{{--
    Dashboard Overview page for the Dashboard context group.
    
    This view resolves the /admin/dashboard-overview catch-all route.
    Serves as the landing page when clicking the Dashboard context group header.
--}}
<x-qf::navigation-layout
    configKey="admin.dashboards.dashboard_overview"
    context="dashboard"
    moduleName="admin" 
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    <livewire:qf.dashboard config-key="admin.dashboards.dashboard_overview" />
</x-qf::navigation-layout>