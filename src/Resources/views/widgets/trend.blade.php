@props(['data'])
@php
    $trendId = "trend-".uniqid();
@endphp
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                @if($data['icon'] ?? null)
                    <i class="{{ $data['icon'] }} fa-2x text-primary mb-2 d-block"></i>
                @endif
                <h5 class="card-title">{{ $data['title'] }}</h5>
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
</div>

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