<div class="card h-100 widget-activity-log">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            @if($data['icon'] ?? false)
                <i class="{{ $data['icon'] }} me-2"></i>
            @endif
            {{ $data['title'] }}
        </h5>
        @if(($data['show_view_all'] ?? false) && ($data['view_all_link'] ?? false))
            <a href="{{ $data['view_all_link'] }}" class="btn btn-sm btn-link">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        @endif
    </div>
    <div class="card-body p-0">
        @if(count($data['items']))
            <div class="list-group list-group-flush">
                @foreach($data['items'] as $item)
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <small class="text-muted">{{ $item['timestamp'] }}</small>
                            <span class="badge 
                                @switch($item['action'])
                                    @case('created') bg-success @break
                                    @case('updated') bg-info @break
                                    @case('deleted') bg-danger @break
                                    @default bg-secondary
                                @endswitch">
                                {{ $item['action_label'] }}
                            </span>
                        </div>
                        <div class="mt-1">
                            <strong>{{ $item['causer_name'] }}</strong>
                            <span class="text-muted mx-1">→</span>
                            {{ $item['description'] }}
                        </div>
                        @if($item['changes'])
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-code-branch me-1"></i> {{ $item['changes'] }}
                            </small>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center p-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                No recent activity
            </div>
        @endif
    </div>
</div>