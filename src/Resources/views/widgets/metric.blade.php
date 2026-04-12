@props(['data'])
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="text-muted mb-2">{{ $data['title'] }}</h6>
                <h3 class="mb-0">{{ $data['value'] }}</h3>

                @if($data['previous_value'] !== null && $data['change_percentage'] !== null)
                    <div class="mt-2">
                        @if($data['trend'] === 'up')
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> {{ $data['change_percentage'] }}%
                            </span>
                        @elseif($data['trend'] === 'down')
                            <span class="text-danger">
                                <i class="fas fa-arrow-down"></i> {{ $data['change_percentage'] }}%
                            </span>
                        @else
                            <span class="text-secondary">
                                <i class="fas fa-minus"></i> 0%
                            </span>
                        @endif
                        <span class="text-muted small"> vs previous period</span>
                    </div>
                @endif

                @if($data['description'])
                    <p class="text-muted small mt-2 mb-0">{{ $data['description'] }}</p>
                @endif
            </div>
            @if($data['icon'] ?? null)
                <i class="{{ $data['icon'] }} fa-2x text-primary"></i>
            @endif
        </div>
    </div>
</div>