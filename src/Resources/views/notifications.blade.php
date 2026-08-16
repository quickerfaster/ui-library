{{--
    User-facing "View All Notifications" page.
    Served at /notifications — linked from the bell drawer footer.
--}}
<x-qf::navigation-layout moduleName="common" :overrides="[
    'breadcrumb' => ['enabled' => false],
    'title' => ['enabled' => false],
    'titleRow' => ['enabled' => false],
    'context_menu' => ['enabled' => false],
]">
    <div class="container-fluid py-4">
        <livewire:qf.notifications-index />
    </div>
</x-qf::navigation-layout>