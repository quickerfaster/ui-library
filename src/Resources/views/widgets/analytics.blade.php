@props(['data'])

<div class="card mb-4">
    <div class="card-header pb-0">
        <h6>Analytics — Last {{ $data['period_days'] }} Days</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body p-3">
                        <p class="text-sm mb-0 opacity-8">Active Users</p>
                        <h4 class="mb-0">{{ number_format($data['active_users']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-info text-white">
                    <div class="card-body p-3">
                        <p class="text-sm mb-0 opacity-8">Page Views</p>
                        <h4 class="mb-0">{{ number_format($data['page_views']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-gradient-dark text-white">
                    <div class="card-body p-3">
                        <p class="text-sm mb-0 opacity-8">Top Actions</p>
                        @foreach(array_slice($data['actions'], 0, 5) as $action)
                            <span class="badge bg-white text-dark me-1 mb-1">
                                {{ $action['action'] }} ({{ $action['total'] }})
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <p class="text-sm font-weight-bold mb-2">Daily Page Views</p>
                <div class="chart-container" style="height: 150px;">
                    <canvas id="analytics-page-views-chart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <p class="text-sm font-weight-bold mb-2">Daily Active Users</p>
                <div class="chart-container" style="height: 150px;">
                    <canvas id="analytics-logins-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <p class="text-sm font-weight-bold mb-2">Top Pages</p>
                <div class="table-responsive">
                    <table class="table table-sm align-items-center mb-0">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th class="text-end">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['top_pages'] as $page)
                                <tr>
                                    <td class="text-xs">{{ \Illuminate\Support\Str::limit($page['route'], 60) }}</td>
                                    <td class="text-end text-xs font-weight-bold">{{ $page['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        var dailyViews = @json($data['daily_page_views']);
        var dailyLogins = @json($data['daily_logins']);

        if (dailyViews.length && document.getElementById('analytics-page-views-chart')) {
            new Chart(document.getElementById('analytics-page-views-chart'), {
                type: 'line',
                data: {
                    labels: dailyViews.map(function(d) { return d.date; }),
                    datasets: [{
                        label: 'Page Views',
                        data: dailyViews.map(function(d) { return d.total; }),
                        borderColor: '#5e72e4',
                        backgroundColor: 'rgba(94,114,228,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: { beginAtZero: true },
                    }
                }
            });
        }

        if (dailyLogins.length && document.getElementById('analytics-logins-chart')) {
            new Chart(document.getElementById('analytics-logins-chart'), {
                type: 'line',
                data: {
                    labels: dailyLogins.map(function(d) { return d.date; }),
                    datasets: [{
                        label: 'Active Users',
                        data: dailyLogins.map(function(d) { return d.total; }),
                        borderColor: '#2dce89',
                        backgroundColor: 'rgba(45,206,137,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: { beginAtZero: true },
                    }
                }
            });
        }
    })();
</script>
@endpush