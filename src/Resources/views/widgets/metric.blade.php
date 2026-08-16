@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-start">
        <div class="min-width-0">
            <h6 class="text-secondary text-uppercase fw-semibold mb-2" style="font-size: .75rem;">{{ $data['title'] }}</h6>
            <h3 class="fw-bolder mb-0">{{ $data['value'] }}</h3>

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
        @if(!empty($data['icon']))
            <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $data['color'] }} text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                <i class="fa-solid {{ $data['icon'] }}"></i>
            </div>
        @endif
    </div>
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', ['data' => $data, 'body' => $body])
