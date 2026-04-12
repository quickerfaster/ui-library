<div>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>{{ $reportName }}</h3>
        <a href="{{ route('reports.index', ['module' => $moduleName]) }}" class="btn btn-sm btn-secondary">
            ← Back to Reports
        </a>
    </div>

    @livewire('qf.dashboard', [
        'configKey' => $configKey,
        'customWidgets' => $customWidgets,
    ])
</div>