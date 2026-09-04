{{--
    Security Overview page for the Security context group.
    
    This view resolves the /admin/dashboard-security-overview catch-all route.
    Serves as the landing page when clicking the Security context group header.
--}}
<x-qf::navigation-layout 
    configKey="admin.dashboards.dashboard_security_overview" 
    context="Security" 
    moduleName="admin" 
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    <livewire:qf.dashboard config-key="admin.dashboards.dashboard_security_overview" />
</x-qf::navigation-layout>