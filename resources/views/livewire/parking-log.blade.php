<div>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Parking Log</h2>
            <button class="btn btn-success" wire:click="export">
                <i class="fas fa-download me-1"></i> Export CSV
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
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
                <div class="card bg-success text-white">
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
                                        <span class="badge bg-{{ $entry->status === 'parked' ? 'warning' : 'success' }}">
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
</div>
