<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Reports</h3>
        <div class="d-flex gap-2">
            {{-- Dropdown for creating a new report --}}
<div class="dropdown">
    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        + Create New Report
    </button>
    <ul class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
        @forelse($availableSources as $source)
            <li>
                <a class="dropdown-item" href="{{ route('report.builder', ['configKey' => $source['key']]) }}">
                    <strong>{{ ucfirst($source['module']) }}</strong> / {{ ucfirst($source['context']) }} – {{ $source['label'] }}
                </a>
            </li>
        @empty
            <li><span class="dropdown-item text-muted">No data sources available</span></li>
        @endforelse
    </ul>
</div>

            @if ($module)
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">All Modules</a>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Search reports...">
                </div>
                <div class="col-md-4">
                    <div class="btn-group" role="group">
                        <button wire:click="$set('reportTypeFilter', 'all')"
                                class="btn btn-sm {{ $reportTypeFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            All
                        </button>
                        <button wire:click="$set('reportTypeFilter', 'system')"
                                class="btn btn-sm {{ $reportTypeFilter === 'system' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            System Reports
                        </button>
                        <button wire:click="$set('reportTypeFilter', 'user')"
                                class="btn btn-sm {{ $reportTypeFilter === 'user' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            My Reports
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Module</th>
                    <th>Context</th>
                    <th>Last Run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td>
                        <strong>{{ $report['name'] }}</strong><br>
                        <small class="text-muted">{{ $report['description'] }}</small>
                    </td>
                    <td>
                        <span class="badge {{ $report['type'] === 'system' ? 'bg-secondary' : 'bg-info' }}">
                            {{ ucfirst($report['type']) }}
                        </span>
                    </td>
                    <td>{{ $report['module'] ?? '' }}</td>
                    <td>{{ $report['context'] ?? '' }}</td>
                    <td>{{ $report['last_run'] ?? 'Never' }}</td>
                    <td>
                        <button wire:click="runReport('{{ $report['id'] }}')" class="btn btn-sm btn-primary">
                            <i class="fas fa-play"></i> Run
                        </button>
                        @if ($report['type'] === 'user')
                            <a href="{{ route('report.builder', ['configKey' => $report['report_key'], 'reportId' => $report['report_id']]) }}"
                               class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button wire:click="deleteSavedReport('{{ $report['id'] }}')"
                                    class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No reports found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>