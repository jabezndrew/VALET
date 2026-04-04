<div wire:poll.10s>
    <div class="container mt-4">
        <h2 class="fw-bold mb-4">RFID Logs</h2>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search UID, name, plate..."
                            wire:model.live.debounce.300ms="search">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="all">All Statuses</option>
                            <option value="valid">Valid</option>
                            <option value="invalid">Invalid</option>
                            <option value="expired">Expired</option>
                            <option value="suspended">Suspended</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" wire:model.live="typeFilter">
                            <option value="all">All Types</option>
                            <option value="entry">Entry</option>
                            <option value="exit">Exit</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-secondary w-100" wire:click="clearFilters">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Time</th>
                            <th>UID</th>
                            <th>User</th>
                            <th>Plate</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="text-muted small">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                            <td><code>{{ $log->uid }}</code></td>
                            <td>{{ $log->user_name ?? '—' }}</td>
                            <td>{{ $log->vehicle_plate ?? '—' }}</td>
                            <td>
                                @if($log->scan_type === 'entry')
                                    <span class="badge bg-primary">Entry</span>
                                @else
                                    <span class="badge bg-secondary">Exit</span>
                                @endif
                            </td>
                            <td>
                                @if($log->status === 'valid')
                                    <span class="badge bg-success">Valid</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($log->status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $log->message }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No RFID scan logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>