@props(['data'])
<div class="card h-100">
    <div class="card-body">
        @if($data['icon'] ?? null)
            <i class="{{ $data['icon'] }} fa-2x mb-3 text-primary"></i>
        @endif
        <h5 class="card-title">{{ $data['title'] }}</h5>
        <p class="card-text text-muted">{{ $data['description'] }}</p>
    </div>
    @if(!empty($data['actions']))
        <div class="card-footer bg-transparent border-top-0">
            @foreach($data['actions'] as $action)
                @php
                    $buttonClass = $action['style'] ?? 'primary';
                @endphp
                <button
                    wire:click="{{ $action['event'] }}({{ json_encode($action['params'] ?? []) }})"
                    class="btn btn-{{ $buttonClass }} btn-sm"
                >
                    {{ $action['label'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>