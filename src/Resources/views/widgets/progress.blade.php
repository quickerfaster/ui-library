@props(['data'])
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="text-muted mb-1">{{ $data['title'] }}</h6>
                <h4 class="mb-0">{{ $data['current_value'] }} / {{ $data['target_value'] }}</h4>
            </div>
            @if($data['icon'] ?? null)
                <i class="{{ $data['icon'] }} fa-2x text-primary"></i>
            @endif
        </div>
        <div class="progress mb-2">
            <div class="progress-bar" role="progressbar" style="width: {{ $data['percentage'] }}%"
                 aria-valuenow="{{ $data['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                {{ $data['percentage'] }}%
            </div>
        </div>
        <small class="text-muted">{{ $data['percentage'] }}% completed</small>
    </div>
</div>