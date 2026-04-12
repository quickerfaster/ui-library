@php
    
    // $reportConfig = config($configKey);
    $repository = app(\QuickerFaster\UILibrary\Services\Config\ModelConfigRepository::class);
    $reportConfig = $repository->get($configKey);


    if (!$reportConfig) {
        abort(404, 'Report configuration not found.');
    }
    $mainConfigKey = $reportConfig['configKey'] ?? null;
    if (!$mainConfigKey) {
        abort(400, 'Report config missing configKey.');
    }
    $moduleName = $reportConfig['module'] ?? explode('_', $configKey)[0] ?? 'system';
    $context = $reportConfig['context'] ?? 'reports';
@endphp

<x-qf::navigation-layout 
    configKey="{{ $mainConfigKey }}" 
    context="{{ $context }}"
    moduleName="{{ $moduleName }}" 
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]">
    <div class="container py-4 mt-3">
        <div class="row justify-content-center">
            <div class="col-12">
                <livewire:qf.report-builder 
                    :mainConfigKey="$mainConfigKey" 
                    :reportConfigKey="$configKey" 
                    :reportId="$reportId ?? null" />
            </div>
        </div>
    </div>
</x-qf::navigation-layout>