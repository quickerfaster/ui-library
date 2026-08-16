@props(['data'])

@php
    $trendId = "trend-".uniqid();
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center">
            @if(!empty($data['icon']))
                <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $data['color'] }} text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 48px; height: 48px;">
                    <i class="fa-solid {{ $data['icon'] }}"></i>
                </div>
            @endif
            <h6 class="fw-bolder mb-0 text-body">{{ $data['title'] }}</h6>
        </div>
        @if($data['change'] !== null)
            <div class="text-end">
                <span class="badge bg-{{ $data['trendDirection'] === 'up' ? 'success' : ($data['trendDirection'] === 'down' ? 'danger' : 'secondary') }}">
                    @if($data['trendDirection'] === 'up')
                        <i class="fas fa-arrow-up"></i> +{{ $data['change'] }}%
                    @elseif($data['trendDirection'] === 'down')
                        <i class="fas fa-arrow-down"></i> {{ $data['change'] }}%
                    @else
                        <i class="fas fa-minus"></i> 0%
                    @endif
                </span>
            </div>
        @endif
    </div>

    @if(!empty($data['values']))
        <canvas id="trend-{{ $trendId }}" width="400" height="150" style="max-height: 150px;"></canvas>
    @else
        <div class="text-center text-muted py-3">
            No data available
        </div>
    @endif
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', ['data' => $data, 'body' => $body])

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('livewire:initialized', function () {
        const canvasId = 'trend-{{ $trendId }}';
        const ctx = document.getElementById(canvasId);
        if (ctx) {
            new Chart(ctx, {
                type: '{{ $data['chart_type'] }}',
                data: {
                    labels: @json($data['labels']),
                    datasets: [{
                        label: '{{ $data['title'] }}',
                        data: @json($data['values']),
                        borderColor: '#4dc9f6',
                        backgroundColor: 'rgba(77, 201, 246, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: { ticks: { maxRotation: 45, minRotation: 45 } }
                    }
                }
            });
        }
    });
</script>
@endpush
