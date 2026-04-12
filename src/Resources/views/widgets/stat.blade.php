@props(['data'])
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="text-muted mb-2">{{ $data['title'] }}</h6>
                <h3 class="mb-0">{{ $data['value'] }}</h3>
            </div>
            @if($data['icon'] ?? null)
                <i class="{{ $data['icon'] }} fa-2x text-primary"></i>
            @endif
        </div>
    </div>
</div>