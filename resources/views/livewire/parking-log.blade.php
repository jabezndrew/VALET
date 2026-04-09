<div wire:poll.5s>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Parking Log</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" wire:click="openAnalytics">
                    <i class="fas fa-chart-bar me-1"></i> Analytics
                </button>
                <button class="btn btn-success" wire:click="export">
                    <i class="fas fa-download me-1"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Today's Entries</h6>
                        <h3>{{ $stats['total_today'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6 class="card-title">Currently Parked</h6>
                        <h3>{{ $stats['currently_parked'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Exited Today</h6>
                        <h3>{{ $stats['exited_today'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Avg Duration (min)</h6>
                        <h3>{{ number_format($stats['avg_duration'], 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Name or plate...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="all">All</option>
                            <option value="parked">Parked</option>
                            <option value="exited">Exited</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" wire:model.live="entryTypeFilter">
                            <option value="all">All</option>
                            <option value="rfid">RFID</option>
                            <option value="guest">Guest</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-secondary w-100" wire:click="clearFilters">Clear</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>User</th>
                                <th>Vehicle Plate</th>
                                <th>Entry Time</th>
                                <th>Exit Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $entry->entry_type === 'rfid' ? 'primary' : 'secondary' }}">
                                            {{ strtoupper($entry->entry_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $entry->user->name ?? 'Guest' }}</td>
                                    <td><code>{{ $entry->vehicle_plate ?? 'N/A' }}</code></td>
                                    <td>{{ $entry->entry_time->format('M d, Y h:i A') }}</td>
                                    <td>
                                        @if($entry->exit_time)
                                            {{ $entry->exit_time->format('M d, Y h:i A') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($entry->duration_minutes)
                                            {{ $entry->duration_minutes }} min
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $entry->status === 'parked' ? 'warning' : ($entry->status === 'exited' ? 'danger' : 'secondary') }}">
                                            {{ ucfirst($entry->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No parking entries found for the selected filters
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $entries->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Modal --}}
    @if($showAnalytics)
    <div class="modal fade show" style="display:block;z-index:1055;" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chart-bar me-2"></i>Analytics</h5>
                    <button type="button" class="btn-close" wire:click="closeAnalytics"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card h-100">
                                <div class="card-header fw-semibold">
                                    <i class="fas fa-chart-bar me-1"></i> Entries — Last 7 Days
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
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
                        <div class="col-md-2">
                            <div class="card h-100">
                                <div class="card-header fw-semibold">
                                    <i class="fas fa-chart-pie me-1"></i> Entry Type
                                    <small class="text-muted ms-1">(selected range)</small>
                                </div>
                                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                    <canvas id="typeChart"></canvas>
                                    <div class="mt-2 text-center" style="font-size:.8rem;">
                                        <span class="badge" style="background:#3b82f6;">RFID</span>
                                        <span class="ms-1 badge" style="background:#6b7280;">Guest</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeAnalytics">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" style="z-index:1050;"></div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let dailyChart = null;
    let hourlyChart = null;
    let typeChart = null;

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
    };

    function initCharts(data) {
        if (dailyChart) dailyChart.destroy();
        if (hourlyChart) hourlyChart.destroy();
        if (typeChart) typeChart.destroy();

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
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
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

    // Render charts when analytics modal opens
    window.addEventListener('updateCharts', event => {
        // Wait a tick for the modal DOM to render
        setTimeout(() => initCharts(event.detail.chartData), 50);
    });
</script>
@endpush
