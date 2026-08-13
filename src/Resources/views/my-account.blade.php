<x-qf::navigation-layout configKey="admin.user" context="Users & Permissions" moduleName="admin" :overrides="[
    'top_bar' => ['enabled' => true],
    'breadcrumb' => ['enabled' => false],
    'title' => ['enabled' => false],
    'titleRow' => ['enabled' => false],
    'context_menu' => ['enabled' => false],
]">
    @livewire("qf.data-table-form", ['inline' => false, 'recordId' => auth()->user()->id, 'configKey' => 'admin.user', 'returnParams' => []])
</x-qf::navigation-layout>