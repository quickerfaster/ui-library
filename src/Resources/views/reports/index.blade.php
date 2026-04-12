<x-qf::navigation-layout 
    configKey="hr.employee"
    context="reports"
    moduleName="system"
    :overrides="[
        'top_bar' => ['enabled' => true], 
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]"
>
    <div class="container py-4 mt-3">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10"> {{-- Restricts width on large screens, full width on mobile --}}
                
                <livewire:qf.report-index />
                
            </div>
        </div>
    </div>
</x-qf::navigation-layout>
