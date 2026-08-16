@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="min-width-0">
            <h6 class="text-secondary text-uppercase fw-semibold mb-1" style="font-size: .75rem;">{{ $data['title'] }}</h6>
            <h4 class="fw-bolder mb-0">{{ $data['current_value'] }} / {{ $data['target_value'] }}</h4>
        </div>
        @if(!empty($data['icon']))
            <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $data['color'] }} text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="fa-solid {{ $data['icon'] }}"></i>
            </div>
        @endif
    </div>
    <div class="progress mb-2" style="height: 6px;">
        <div class="progress-bar bg-{{ $data['color'] }}" role="progressbar" style="width: {{ $data['percentage'] }}%"
             aria-valuenow="{{ $data['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <small class="text-muted">{{ $data['percentage'] }}% completed</small>
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', ['data' => $data, 'body' => $body])
