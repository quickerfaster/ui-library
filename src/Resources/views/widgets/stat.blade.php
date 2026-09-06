@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <div class="d-flex align-items-center">
        @if(!empty($data['icon']))
            <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $data['color'] }} text-white me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; flex-shrink: 0;">
                <i class="fa-solid {{ $data['icon'] }}"></i>
            </div>
        @endif
        <div class="min-width-0">
            <p class="text-sm text-secondary mb-0 text-uppercase fw-semibold">{{ $data['title'] }}</p>
            <h5 class="fw-bolder mb-0">{{ $data['value'] }}</h5>
        </div>
    </div>
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', ['data' => $data, 'body' => $body])
