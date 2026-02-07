<div>
    <div id="alert-container"></div>

    <div class="container mt4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Guest Access</h2>
                <p class="text-muted mb-0">Manage temporary guest parking passes</p>
            </div>
            <button class="btn btn-valet-charcoal" wire:click="openModal">
                <i class="fas fa-plus me-2"></i>New Guest Pass
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-primary bg-opacity-10">
                    <div class="card-body text-center py-3">
                        <h3 class="fw-bold text-primary mb-0">{{ $stats['active'] }}</h3>
                        <small class="text-muted">Active Passes</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-success bg-opacity-10">
                    <div class="card-body text-center py-3">
                        <h3 class="fw-bold text-success mb-0">{{ $stats['today'] }}</h3>
                        <small class="text-muted">Created Today</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-danger bg-opacity-10">
                    <div class="card-body text-center py-3">
                        <h3 class="fw-bold text-danger mb-0">{{ $stats['expired'] }}</h3>
                        <small class="text-muted">Expired</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-secondary bg-opacity-10">
                    <div class="card-body text-center py-3">
                        <h3 class="fw-bold text-secondary mb-0">{{ $stats['total'] }}</h3>
                        <small class="text-muted">Total Records</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by name, plate, or pass ID...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="statusFilter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="used">Used</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guest List -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Guest Passes ({{ $guests->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($guests->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No guest passes found</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pass ID</th>
                                    <th>Guest Name</th>
                                    <th>Vehicle Plate</th>
                                    <th>Valid Until</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($guests as $guest)
                                    @php
                                        [$badgeClass, $statusText] = $this->getStatusBadge($guest);
                                    @endphp
                                    <tr class="{{ $statusText === 'Expired' || $statusText === 'Cancelled' ? 'table-secondary' : '' }}">
                                        <td>
                                            <code class="fw-bold">{{ $guest->guest_id }}</code>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $guest->name }}</div>
                                            @if($guest->phone)
                                                <small class="text-muted">{{ $guest->phone }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-dark fs-6">{{ $guest->vehicle_plate }}</span>
                                        </td>
                                        <td>
                                            <div>{{ \Carbon\Carbon::parse($guest->valid_until)->format('M j, Y g:i A') }}</div>
                                            <small class="{{ $statusText === 'Active' ? 'text-success' : 'text-danger' }}">
                                                {{ $this->getTimeRemaining($guest->valid_until) }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $guest->creator->name ?? 'System' }}</small>
                                        </td>
                                        <td class="text-end">
                                            @if($statusText === 'Active')
                                                <div class="btn-group btn-group-sm">
                                                    <button wire:click="extendAccess({{ $guest->id }}, 24)" class="btn btn-outline-success" title="Extend 24h">
                                                        <i class="fas fa-clock"></i>
                                                    </button>
                                                    <button wire:click="openModal({{ $guest->id }})" class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button wire:click="cancelAccess({{ $guest->id }})" wire:confirm="Cancel this guest pass?" class="btn btn-outline-danger" title="Cancel">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <button wire:click="delete({{ $guest->id }})" wire:confirm="Delete this record permanently?" class="btn btn-sm btn-outline-danger" title="Delete">
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

    <!-- Create/Edit Modal -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-clock me-2"></i>
                        {{ $editingId ? 'Edit Guest Pass' : 'New Guest Pass' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Guest Name <span class="text-danger">*</span></label>
                                <input type="text" wire:model="name" class="form-control" placeholder="Enter guest name">
                                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle Plate <span class="text-danger">*</span></label>
                                <input type="text" wire:model="vehicle_plate" class="form-control text-uppercase" placeholder="ABC 1234">
                                @error('vehicle_plate') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" wire:model="phone" class="form-control" placeholder="Optional">
                                @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Purpose of Visit</label>
                                <input type="text" wire:model="purpose" class="form-control" placeholder="e.g., Meeting, Delivery, Visitor">
                                @error('purpose') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Valid Duration <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" wire:model="valid_hours" class="form-control" min="1" max="168">
                                    <span class="input-group-text">hours</span>
                                </div>
                                <small class="text-muted">Max: 168 hours (1 week)</small>
                                @error('valid_hours') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Notes</label>
                                <textarea wire:model="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                                @error('notes') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="submit" class="btn btn-valet-charcoal">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-save me-2"></i>{{ $editingId ? 'Update' : 'Create Pass' }}
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin me-2"></i>Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-alert', (event) => {
                const alertContainer = document.getElementById('alert-container');
                const alertId = 'alert-' + Date.now();

                const alertHtml = `
                    <div class="container-fluid mt-3">
                        <div id="${alertId}" class="alert alert-${event.type} alert-dismissible fade show" role="alert">
                            <i class="fas fa-${event.type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                            ${event.message}
                            <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
                        </div>
                    </div>
                `;

                alertContainer.innerHTML = alertHtml;

                setTimeout(() => {
                    const alert = document.getElementById(alertId);
                    if (alert) alert.remove();
                }, 5000);
            });
        });
    </script>
</div>
