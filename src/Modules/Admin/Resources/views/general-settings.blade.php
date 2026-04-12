<div>
<x-qf::navigation-layout
    configKey="admin.dashboards.dashboard_company_profile_overview"
    context="General Settings"
    moduleName="admin"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]"
>

<br /><br/>
    <livewire:qf.settings-panel mode="user" />

</x-qf::navigation-layout>
</div>

