@props(['data'])
<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            @if($data['photo_url'])
                <img src="{{ $data['photo_url'] }}" class="rounded-circle" width="100" height="100" alt="Photo">
            @else
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:100px;height:100px;">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
            @endif
            <h4 class="mt-3 mb-1">{{ $data['full_name'] }}</h4>
            <span class="mt-3 mb-1">{{ $data['record_number'] }}</span>
            <p class="text-muted small mb-2">{{ $data['title'] ?? '' }}</p>
        </div>
        <hr>
        <div class="mt-3">
            @foreach($data['fields'] as $field)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ $field['label'] }}:</span>
                    <span class="fw-medium">{{ $field['value'] }}</span>
                </div>
            @endforeach
        </div>
        @if(!empty($data['actions']))
            <div class="mt-3 d-flex justify-content-around">
                @foreach($data['actions'] as $action)
                    <button wire:click="{{ $action['event'] }}({{ json_encode($action['params'] ?? []) }})" class="btn btn-sm btn-outline-primary">
                        <i class="{{ $action['icon'] ?? 'fas fa-edit' }} me-1"></i> {{ $action['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>