<x-qf::navigation-layout 
    configKey="organization.dashboards.dashboard_locations_overview" 
    context="locations" 
    moduleName="organization" 
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    <livewire:qf.dashboard config-key="organization.dashboards.dashboard_locations_overview" />
</x-qf::navigation-layout>