<div>
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-clipboard-list me-2 text-warning"></i>Incident Log</h2>
        </div>

        {{-- Stats --}}
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold">{{ $stats->total }}</div>
                        <div class="text-muted small">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-warning">{{ $stats->open }}</div>
                        <div class="text-muted small">Open</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-info">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-info">{{ $stats->in_progress }}</div>
                        <div class="text-muted small">In Progress</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-success">{{ $stats->resolved }}</div>
                        <div class="text-muted small">Resolved</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select class="form-select form-select-sm" wire:model.live="statusFilter">
                            <option value="all">All Statuses</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" wire:model.live="categoryFilter">
                            <option value="all">All Categories</option>
                            <option value="debris">Debris / Obstruction</option>
                            <option value="damaged">Damaged Spot</option>
                            <option value="blocked">Blocked Area</option>
                            <option value="light_issue">Light Issue</option>
                            <option value="sensor_issue">Sensor Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="date" class="form-control form-control-sm" wire:model.live="dateFilter">
                    </div>
                    @if($statusFilter !== 'all' || $categoryFilter !== 'all' || $dateFilter)
                        <div class="col-auto">
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="$set('statusFilter','all'); $set('categoryFilter','all'); $set('dateFilter','')">
                                Clear
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-body p-0">
                @if($incidents->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-clipboard-check" style="font-size:2.5rem;opacity:0.3;"></i>
                        <p class="mt-3">No incidents found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>When</th>
                                    <th>Where</th>
                                    <th>Category</th>
                                    <th>Involved</th>
                                    <th>Notes</th>
                                    <th>Action Taken</th>
                                    <th>Reported By</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incidents as $incident)
                                    @php
                                        $statusColors = [
                                            'open'        => 'warning',
                                            'in_progress' => 'info',
                                            'resolved'    => 'success',
                                        ];
                                        $incidentTime = $incident->incident_at ?? $incident->created_at;
                                    @endphp
                                    <tr>
                                        <td class="text-muted small">{{ $incident->id }}</td>
                                        <td style="white-space:nowrap;font-size:0.85rem;">
                                            {{ $incidentTime->format('M d, Y') }}<br>
                                            <span class="text-muted">{{ $incidentTime->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $incident->space_code ?? 'N/A' }}</strong><br>
                                            <span class="text-muted small">{{ $incident->floor_level }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas {{ \App\Models\GuardIncident::getCategoryIcon($incident->category) }} me-1"></i>
                                                {{ \App\Models\GuardIncident::getCategoryLabel($incident->category) }}
                                            </span>
                                        </td>
                                        <td class="small">{{ $incident->involved_party ?? '—' }}</td>
                                        <td class="small" style="max-width:180px;">{{ $incident->notes ?? '—' }}</td>
                                        <td class="small" style="max-width:160px;">{{ $incident->action_taken ?? '—' }}</td>
                                        <td class="small">{{ $incident->reported_by ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $statusColors[$incident->status] ?? 'secondary' }}">
                                                {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                                            </span>
                                            @if($incident->resolved_by)
                                                <div class="text-muted" style="font-size:0.72rem;">
                                                    by {{ $incident->resolved_by }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="white-space:nowrap;">
                                            @if($incident->status !== 'resolved')
                                                <button class="btn btn-sm btn-outline-success"
                                                        wire:click="resolve({{ $incident->id }})"
                                                        title="Mark resolved">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            @if($this->canManage())
                                                <button class="btn btn-sm btn-outline-danger ms-1"
                                                        wire:click="confirmDelete({{ $incident->id }})"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    @if($showDeleteModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Incident</h5>
                        <button type="button" class="btn-close" wire:click="cancelDelete"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete incident #{{ $deletingIncidentId }}? This cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" wire:click="cancelDelete">Cancel</button>
                        <button class="btn btn-danger btn-sm" wire:click="deleteIncident">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
