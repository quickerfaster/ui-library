@props(['data'])
<div class="card h-100">
    <div class="card-body">
        @if($data['icon'] ?? null)
            <i class="{{ $data['icon'] }} fa-2x mb-3 text-primary"></i>
        @endif
        <h5 class="card-title">{{ $data['title'] }}</h5>
        @if($data['description'])
            <p class="card-text text-muted">{{ $data['description'] }}</p>
        @endif

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        @foreach($data['columns'] as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['items'] as $item)
                        <tr>
                            @foreach($data['columns'] as $column)
                                <td>{{ $item[$column['label']] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($data['columns']) }}" class="text-center text-muted">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($data['showViewAll'] && $data['viewAllLink'])
            <div class="mt-2 text-end">
                <a href="{{ $data['viewAllLink'] }}" class="btn btn-sm btn-link">View All</a>
            </div>
        @endif
    </div>
</div>