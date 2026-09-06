@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-4">
    <div class="d-flex align-items-center mb-3">
        @if(!empty($data['icon']))
            <div class="icon-shape icon-lg rounded-3 bg-gradient-{{ $data['color'] }} text-white d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px; flex-shrink: 0;">
                <i class="fa-solid {{ $data['icon'] }} fs-5"></i>
            </div>
        @endif
        <h6 class="fw-bolder mb-0 text-body">{{ $data['title'] }}</h6>
    </div>
    <p class="text-sm text-secondary mb-0">{{ $data['description'] }}</p>
</div>

@if(!empty($data['actions']))
    <div class="card-footer bg-transparent border-top-0 px-4 pb-4 pt-0">
        @foreach($data['actions'] as $action)
            @php
                $buttonClass = $action['style'] ?? 'primary';
            @endphp
            <button
                wire:click="$dispatch('{{ $action['event'] }}', {{ json_encode($action['params'] ?? []) }})"
                class="btn btn-{{ $buttonClass }} btn-sm"
            >
                {{ $action['label'] }}
            </button>
        @endforeach
    </div>
@endif

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', ['data' => $data, 'body' => $body, 'hover' => true])
