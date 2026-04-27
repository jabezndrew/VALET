<div>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('parking-log') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Parking Log
                </a>
                <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Analytics</h2>
            </div>
        </div>

        {{-- Date Range Filter --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">
                        <i class="fas fa-chart-bar me-1"></i> Entries — Last 7 Days
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">
                        <i class="fas fa-clock me-1"></i> Peak Hours
                        <small class="text-muted ms-1">(selected range)</small>
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card h-100">
                    <div class="card-header fw-semibold">
                        <i class="fas fa-chart-pie me-1"></i> Entry Type
                        <small class="text-muted ms-1">(selected range)</small>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <canvas id="typeChart" style="max-width:220px;"></canvas>
                        <div class="mt-3 text-center" style="font-size:.85rem;">
                            <span class="badge me-1" style="background:#3b82f6;">RFID</span>
                            <span class="badge" style="background:#6b7280;">Guest</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let dailyChart  = null;
    let hourlyChart = null;
    let typeChart   = null;

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
    };

    function initCharts(data) {
        if (dailyChart)  dailyChart.destroy();
        if (hourlyChart) hourlyChart.destroy();
        if (typeChart)   typeChart.destroy();

        const dailyCtx = document.getElementById('dailyChart');
        if (dailyCtx) {
            dailyChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: data.daily.labels,
                    datasets: [{
                        label: 'Entries',
                        data: data.daily.data,
                        backgroundColor: 'rgba(59,130,246,0.7)',
                        borderColor: 'rgba(59,130,246,1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }

        const hourlyCtx = document.getElementById('hourlyChart');
        if (hourlyCtx) {
            hourlyChart = new Chart(hourlyCtx, {
                type: 'bar',
                data: {
                    labels: data.hourly.labels,
                    datasets: [{
                        label: 'Entries',
                        data: data.hourly.data,
                        backgroundColor: 'rgba(16,185,129,0.7)',
                        borderColor: 'rgba(16,185,129,1)',
                        borderWidth: 1,
                        borderRadius: 3,
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { ticks: { font: { size: 10 } } }
                    }
                }
            });
        }

        const typeCtx = document.getElementById('typeChart');
        if (typeCtx) {
            typeChart = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['RFID', 'Guest'],
                    datasets: [{
                        data: [data.type.rfid, data.type.guest],
                        backgroundColor: ['rgba(59,130,246,0.8)', 'rgba(107,114,128,0.8)'],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.parsed}`
                            }
                        }
                    }
                }
            });
        }
    }

    // Initial render from server-side data
    const initialData = @json($chartData);
    document.addEventListener('DOMContentLoaded', () => initCharts(initialData));

    // Re-render when date filters change
    window.addEventListener('refreshCharts', event => initCharts(event.detail.chartData));
</script>
@endpush
