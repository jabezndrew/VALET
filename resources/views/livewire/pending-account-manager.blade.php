<div>
    <div id="alert-container"></div>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Pending Account Approvals</h2>
                <p class="text-muted mb-0">Review and approve accounts created by SSD personnel</p>
            </div>
            <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary" wire:navigate>
                <i class="fas fa-users me-2"></i>Back to Users
            </a>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-total">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total'] }}</h3>
                        <p class="mb-0">Total Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-types">
                    <div class="card-body text-center">
                        <h3>{{ $stats['pending'] }}</h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-active">
                    <div class="card-body text-center">
                        <h3>{{ $stats['approved'] }}</h3>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-inactive">
                    <div class="card-body text-center">
                        <h3>{{ $stats['rejected'] }}</h3>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Filter by Status</label>
                        <select wire:model.live="statusFilter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="pending">Pending Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Accounts List -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Requested User</th>
                                <th>Role</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Created By</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingAccounts as $account)
                                @php
                                    $requestDate = \Carbon\Carbon::parse($account->created_at);
                                    $rowClass = match($account->status) {
                                        'approved' => 'table-success',
                                        'rejected' => 'table-danger',
                                        default => ''
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>
                                        <div>
                                            <strong>{{ $account->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $account->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ match($account->role) {
                                            'admin' => 'bg-danger',
                                            'ssd' => 'bg-valet-charcoal text-white',
                                            'security' => 'bg-warning',
                                            default => 'bg-valet-gray'
                                        } }}">
                                            {{ $this->getRoleDisplayName($account->role) }}
                                        </span>
                                    </td>
                                    <td class="font-monospace">{{ $account->employee_id ?: '-' }}</td>
                                    <td>{{ $account->department ?: '-' }}</td>
                                    <td>
                                        {{ $account->created_by_name }}
                                        <br>
                                        <span class="badge bg-valet-charcoal text-white">
                                            {{ $this->getRoleDisplayName($account->created_by_role) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $this->getStatusBadgeClass($account->status) }}">
                                            <i class="{{ $this->getStatusIcon($account->status) }} me-1"></i>
                                            {{ ucfirst($account->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $requestDate->format('M j, Y') }}
                                            <br>
                                            <span class="text-muted">{{ $requestDate->diffForHumans() }}</span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="viewAccount({{ $account->id }})" 
                                                    class="btn btn-outline-secondary"
                                                    title="View details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($account->status === 'pending')
                                            <button wire:click="approveAccount({{ $account->id }})" 
                                                    class="btn btn-outline-success btn-sm"
                                                    title="Quick approve"
                                                    wire:loading.attr="disabled"
                                                    wire:target="approveAccount({{ $account->id }})">
                                                <span wire:loading.remove wire:target="approveAccount({{ $account->id }})">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <span wire:loading wire:target="approveAccount({{ $account->id }})">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </span>
                                            </button>
                                            <button wire:click="rejectAccount({{ $account->id }})"
                                                    class="btn btn-outline-danger btn-sm"
                                                    title="Quick reject"
                                                    wire:loading.attr="disabled"
                                                    wire:target="rejectAccount({{ $account->id }})">
                                                <span wire:loading.remove wire:target="rejectAccount({{ $account->id }})">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                                <span wire:loading wire:target="rejectAccount({{ $account->id }})">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </span>
                                            </button>
                                            @endif
                                            <button wire:click="deleteAccount({{ $account->id }})" 
                                                    wire:confirm="Are you sure you want to delete this request? This action cannot be undone."
                                                    class="btn btn-outline-danger"
                                                    title="Delete request">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-user-clock text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <h5 class="text-muted">
                                            @if($statusFilter !== 'all')
                                                No {{ $statusFilter }} accounts found
                                            @else
                                                No account requests
                                            @endif
                                        </h5>
                                        <p class="text-muted">
                                            @if($statusFilter !== 'all')
                                                Try changing the filter to see other requests
                                            @else
                                                SSD personnel haven't submitted any account requests yet
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Details Modal -->
    @if($showModal && $selectedAccount)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="{{ $this->getStatusIcon($selectedAccount->status) }} me-2"></i>
                        Account Request Details
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-user me-1"></i>User Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="fw-bold">Name:</td>
                                            <td>{{ $selectedAccount->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Email:</td>
                                            <td>{{ $selectedAccount->email }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Role:</td>
                                            <td>
                                                <span class="badge {{ match($selectedAccount->role) {
                                                    'admin' => 'bg-danger',
                                                    'ssd' => 'bg-valet-charcoal text-white',
                                                    'security' => 'bg-warning',
                                                    default => 'bg-valet-gray'
                                                } }}">
                                                    {{ $this->getRoleDisplayName($selectedAccount->role) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Employee ID:</td>
                                            <td class="font-monospace">{{ $selectedAccount->employee_id ?: 'Not provided' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Department:</td>
                                            <td>{{ $selectedAccount->department ?: 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Status:</td>
                                            <td>
                                                <span class="{{ $this->getStatusBadgeClass($selectedAccount->status) }}">
                                                    <i class="{{ $this->getStatusIcon($selectedAccount->status) }} me-1"></i>
                                                    {{ ucfirst($selectedAccount->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-1"></i>Request Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="fw-bold">Created By:</td>
                                            <td>{{ $selectedAccount->created_by_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Creator Role:</td>
                                            <td>
                                                <span class="badge bg-valet-charcoal text-white">
                                                    {{ $this->getRoleDisplayName($selectedAccount->created_by_role) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Requested:</td>
                                            <td>{{ \Carbon\Carbon::parse($selectedAccount->created_at)->format('M j, Y g:i A') }}</td>
                                        </tr>
                                        @if($selectedAccount->reviewed_at)
                                        <tr>
                                            <td class="fw-bold">Reviewed By:</td>
                                            <td>{{ $selectedAccount->reviewed_by_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Reviewed:</td>
                                            <td>{{ \Carbon\Carbon::parse($selectedAccount->reviewed_at)->format('M j, Y g:i A') }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($selectedAccount->status === 'pending')
                    <div class="mt-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sticky-note me-1"></i>Admin Notes <small class="text-muted">(Optional)</small>
                        </label>
                        <textarea wire:model="adminNotes" class="form-control" rows="3" 
                                  placeholder="Add notes about your decision (optional)..."
                                  maxlength="1000"></textarea>
                        <small class="text-muted">{{ strlen($adminNotes) }}/1000 characters</small>
                        @error('adminNotes') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    @elseif($selectedAccount->admin_notes)
                    <div class="mt-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sticky-note me-1"></i>Admin Notes
                        </label>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-quote-left me-2"></i>
                            {{ $selectedAccount->admin_notes }}
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($selectedAccount->status === 'pending')
                        <button type="button" class="btn btn-danger" wire:click="rejectAccount" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="rejectAccount">
                                <i class="fas fa-times me-1"></i> Reject Account
                            </span>
                            <span wire:loading wire:target="rejectAccount">
                                <i class="fas fa-spinner fa-spin me-1"></i> Rejecting...
                            </span>
                        </button>
                        <button type="button" class="btn btn-success" wire:click="approveAccount" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="approveAccount">
                                <i class="fas fa-check me-1"></i> Approve Account
                            </span>
                            <span wire:loading wire:target="approveAccount">
                                <i class="fas fa-spinner fa-spin me-1"></i> Approving...
                            </span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-alert', (event) => {
                const alertContainer = document.getElementById('alert-container');
                const alertId = 'alert-' + Date.now();
                
                const alertHtml = `
                    <div class="container mt-3">
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