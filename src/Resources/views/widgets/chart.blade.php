@props(['data'])
<div class="card h-100">
    <div class="card-body">
        <h6 class="text-muted mb-3">{{ $data['title'] }}</h6>
        <canvas id="{{ $data['chart_id'] }}" width="400" height="200"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('livewire:initialized', function () {
        const ctx = document.getElementById('{{ $data['chart_id'] }}').getContext('2d');
        new Chart(ctx, {
            type: '{{ $data['chart_type'] }}',
            data: @json($data['chart_data']),
            options: {
                responsive: true,
                maintainAspectRatio: true,
            }
        });
    });
</script>
@endpush