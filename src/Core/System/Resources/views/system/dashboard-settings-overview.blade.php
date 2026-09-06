<x-qf::navigation-layout 
    configKey="system.dashboards.dashboard_settings_overview" 
    context="settings" 
    moduleName="system" 
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    <livewire:qf.dashboard config-key="system.dashboards.dashboard_settings_overview" />
</x-qf::navigation-layout>