<div>
    <div class="container-fluid mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="mb-4">
            <h2 class="fw-bold mb-1">Tools</h2>
            <p class="text-muted mb-0">Quick access to features</p>
        </div>

        @php
            $role     = auth()->user()->role;
            $isAdmin  = $role === 'admin';
            $isSsd    = $role === 'ssd';
            $isSecurity = $role === 'security';
            $isUser   = $role === 'user';
            $isStaff  = $isSecurity || $isSsd || $isAdmin;
        @endphp

        {{-- ── General ─────────────────────────────────────────────── --}}
        <h6 class="category-label">General</h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('parking-display') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="fas fa-map fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Parking Map</h5>
                            <p class="card-text text-muted small">View parking availability</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('feedback.index') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon mx-auto mb-3" style="background:rgba(111,66,193,.1);color:#6f42c1;">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Feedback</h5>
                            <p class="card-text text-muted small">View &amp; submit feedback</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- ── Logs ─────────────────────────────────────────────────── --}}
        @if($isStaff)
        <h6 class="category-label">Logs</h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('parking-log') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-3">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Parking Log</h5>
                            <p class="card-text text-muted small">View entry/exit history</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('rfid-log') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-wave-square fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">RFID Logs</h5>
                            <p class="card-text text-muted small">All RFID scan events</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('incidents') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Incident Log</h5>
                            <p class="card-text text-muted small">View &amp; manage incident reports</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        @endif

        {{-- ── Analytics ────────────────────────────────────────────── --}}
        @if($isStaff)
        <h6 class="category-label">Analytics</h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('parking-analytics') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="fas fa-chart-bar fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Parking Analytics</h5>
                            <p class="card-text text-muted small">Charts &amp; trends over time</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        @endif

        {{-- ── Management ───────────────────────────────────────────── --}}
        @if($isStaff || $isSsd || $isAdmin)
        <h6 class="category-label">Management</h6>
        <div class="row g-3 mb-4">
            @if($isStaff)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('cars.index') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Vehicles</h5>
                            <p class="card-text text-muted small">Manage registered vehicles</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('guest-access') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Guest Access</h5>
                            <p class="card-text text-muted small">Manage guest parking passes</p>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            @if($isSsd || $isAdmin)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('admin.users') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">User Management</h5>
                            <p class="card-text text-muted small">Manage system users</p>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            @if($isAdmin)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('admin.pending-accounts') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Pending Accounts</h5>
                            <p class="card-text text-muted small">Review new accounts</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('admin.rfid') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
                                <i class="fas fa-id-card fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">RFID Management</h5>
                            <p class="card-text text-muted small">Manage RFID tags</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('admin.sensors') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                                <i class="fas fa-microchip fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-1">Sensors</h5>
                            <p class="card-text text-muted small">Configure parking sensors</p>
                        </div>
                    </div>
                </a>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Danger Zone ──────────────────────────────────────────── --}}
        @if($isAdmin)
        <h6 class="category-label text-danger">Danger Zone</h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card tool-card tool-card-danger h-100" wire:click="openClearLogsModal" style="cursor:pointer;">
                    <div class="card-body text-center py-4">
                        <div class="tool-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                            <i class="fas fa-trash fa-2x"></i>
                        </div>
                        <h5 class="card-title mb-1">Clear Logs</h5>
                        <p class="card-text text-muted small">Delete all parking entries &amp; scan logs</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Clear Logs Modal --}}
    @if($showClearLogsModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Clear Parking Logs</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeClearLogsModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <strong>Warning:</strong> This will permanently delete <strong>all parking entries</strong> and <strong>all RFID scan logs</strong>. This action cannot be undone.
                    </div>
                    <p class="text-muted mb-3">Enter the admin password to confirm:</p>
                    <div>
                        <input
                            type="password"
                            class="form-control @if($clearLogsError) is-invalid @endif"
                            wire:model="clearLogsPassword"
                            placeholder="Enter password"
                            wire:keydown.enter="clearParkingLogs"
                            autofocus
                        >
                        @if($clearLogsError)
                            <div class="invalid-feedback">{{ $clearLogsError }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeClearLogsModal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="clearParkingLogs">
                        <i class="fas fa-trash me-1"></i>Yes, Delete Everything
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
        .category-label {
            text-transform: uppercase;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: #6c757d;
            margin-bottom: .6rem;
            padding-bottom: .3rem;
            border-bottom: 1px solid #e9ecef;
        }

        .tool-card {
            transition: all 0.2s ease;
            border: 1px solid #e0e0e0;
        }

        .tool-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-color: #adb5bd;
        }

        .tool-card-danger:hover {
            border-color: #dc3545;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.2);
        }

        .tool-icon {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tool-card .card-title {
            color: #333;
            font-weight: 600;
            font-size: .95rem;
        }

        .tool-card:hover .card-title {
            color: #000;
        }
    </style>
</div>
