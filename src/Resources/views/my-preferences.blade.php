<x-qf::navigation-layout configKey="admin.user" context="Users" moduleName="admin" :overrides="[
    'breadcrumb' => ['enabled' => false],
    'title' => ['enabled' => false],
    'titleRow' => ['enabled' => false],
    'context_menu' => ['enabled' => false],
    'top_bar' => ['enabled' => false],
]">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button onclick="window.close()||history.back()" class="btn btn-sm btn-outline-secondary">&larr; Back</button>
    </div>
    <livewire:qf.settings-panel mode="user" />
</x-qf::navigation-layout>