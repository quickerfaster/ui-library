@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <canvas id="{{ $data['chart_id'] }}" width="400" height="200"></canvas>
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', [
    'data' => $data,
    'body' => $body,
    'header' => [
        'title' => $data['title'],
        'icon' => $data['icon'] ?? null,
        'color' => $data['color'],
    ],
])

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('livewire:initialized', function () {
        const ctx = document.getElementById('{{ $data['chart_id'] }}').getContext('2d');
        const currencySymbol = @json($data['currency_symbol'] ?? null);

        const options = {
            responsive: true,
            maintainAspectRatio: true,
        };

        if (currencySymbol) {
            options.scales = {
                y: {
                    ticks: {
                        callback: function(value) {
                            return currencySymbol + value.toLocaleString();
                        }
                    }
                }
            };
            options.plugins = {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + currencySymbol + context.parsed.y.toLocaleString();
                        }
                    }
                }
            };
        }

        new Chart(ctx, {
            type: '{{ $data['chart_type'] }}',
            data: @json($data['chart_data']),
            options: options,
        });
    });
</script>
@endpush
