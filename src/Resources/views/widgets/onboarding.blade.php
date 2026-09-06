@props(['data'])
@if($data['inProgress'] || $data['showCompleted'])
<div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-gradient-{{ $data['color'] }} text-white d-flex justify-content-between align-items-center border-0">
        <div>
            @if($data['icon'])
                <i class="{{ $data['icon'] }} me-2"></i>
            @endif
            <span>{{ $data['title'] }}</span>
        </div>
        <button class="btn btn-sm btn-light" onclick="this.closest('.card').remove()">Hide</button>
        {{-- For persistence, you'd need to store user preference --}}
    </div>
    <div class="card-body">
        @if($data['description'])
            <p class="card-text text-muted mb-3">{{ $data['description'] }}</p>
        @endif

        <div class="progress mb-3">
            <div class="progress-bar" style="width: {{ $data['percentage'] }}%">
                {{ $data['percentage'] }}%
            </div>
        </div>

        <div class="list-group">
            @foreach($data['steps'] as $step)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        @if($step['complete'])
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <s>{{ $step['title'] }}</s>
                        @else
                            <i class="fas fa-circle-notch text-muted me-2"></i>
                            {{ $step['title'] }}
                        @endif
                    </span>
                    <a href="{{ $step['link'] }}" class="btn btn-sm btn-primary">
                        {{ $step['cta'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif