<div>
    <div class="container-fluid mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <!-- Header -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Tools</h2>
            <p class="text-muted mb-0">Quick access to features</p>
        </div>

        <!-- Tools Grid -->
        <div class="row g-4">
            @php
                $role = auth()->user()->role;
                $isAdmin = $role === 'admin';
                $isSsd = $role === 'ssd';
                $isSecurity = $role === 'security';
                $isUser = $role === 'user';
            @endphp

            <!-- Parking Map - Everyone -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('parking-display') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="fas fa-map fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Parking Map</h5>
                            <p class="card-text text-muted small mb-2">View parking availability</p>
                            <span class="badge bg-primary">Parking</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Feedback - Everyone -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('feedback.index') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-purple bg-opacity-10 mx-auto mb-3" style="color: #6f42c1;">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Feedback</h5>
                            <p class="card-text text-muted small mb-2">View & submit feedback</p>
                            <span class="badge" style="background-color: #6f42c1;">Support</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Incident Log - Security, SSD, Admin -->
            @if($isSecurity || $isSsd || $isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('incidents') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Incident Log</h5>
                            <p class="card-text text-muted small mb-2">View & manage incident reports</p>
                            <span class="badge bg-warning text-dark">Security</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- Vehicle Management - Security, SSD, Admin -->
            @if($isSecurity || $isSsd || $isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('cars.index') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Vehicles</h5>
                            <p class="card-text text-muted small mb-2">Manage registered vehicles</p>
                            <span class="badge bg-danger">Management</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- Parking Log - Security, SSD, Admin -->
            @if($isSecurity || $isSsd || $isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('parking-log') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-3">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Parking Log</h5>
                            <p class="card-text text-muted small mb-2">View entry/exit history</p>
                            <span class="badge bg-secondary">Reports</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif
            <!-- RFID Log - Security, SSD, Admin -->
            @if($isSecurity || $isSsd || $isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('rfid-log') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-wave-square fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">RFID Logs</h5>
                            <p class="card-text text-muted small mb-2">All RFID scan events</p>
                            <span class="badge bg-warning text-dark">Logs</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif
            <!-- Guest Access - Security, SSD, Admin -->
            @if($isSecurity || $isSsd || $isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('guest-access') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Guest Access</h5>
                            <p class="card-text text-muted small mb-2">Manage guest parking passes</p>
                            <span class="badge bg-info">Access</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- User Management - SSD, Admin -->
            @if($isSsd || $isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.users') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">User Management</h5>
                            <p class="card-text text-muted small mb-2">Manage system users</p>
                            <span class="badge bg-primary">Management</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- Pending Accounts - Admin only -->
            @if($isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.pending-accounts') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Pending Accounts</h5>
                            <p class="card-text text-muted small mb-2">Review new accounts</p>
                            <span class="badge bg-warning text-dark">Approval</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- RFID Management - Admin only -->
            @if($isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.rfid') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
                                <i class="fas fa-id-card fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">RFID Management</h5>
                            <p class="card-text text-muted small mb-2">Manage RFID tags</p>
                            <span class="badge bg-success">Hardware</span>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- Sensor Management - Admin only -->
            @if($isAdmin)
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.sensors') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                                <i class="fas fa-microchip fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Sensors</h5>
                            <p class="card-text text-muted small mb-2">Configure parking sensors</p>
                            <span class="badge bg-info">Hardware</span>
                        </div>
                    </div>
                </a>
            </div>
            
            @endif
            
            <!-- Clear Parking & RFID Logs - Admin only -->
            @if($isAdmin)
            <div class="col-md-4 col-lg-3">
                <div class="card tool-card h-100" wire:click="openClearLogsModal" style="cursor:pointer;">
                    <div class="card-body text-center py-4">
                        <div class="tool-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                            <i class="fas fa-trash fa-2x"></i>
                        </div>
                        <h5 class="card-title mb-2">Clear Logs</h5>
                        <p class="card-text text-muted small mb-2">Delete all parking entries &amp; scan logs</p>
                        <span class="badge bg-danger">Danger</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

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
        .tool-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            cursor: pointer;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #333;
        }

        .tool-icon {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tool-card .card-title {
            color: #333;
            font-weight: 600;
        }

        .tool-card:hover .card-title {
            color: #000;
        }
    </style>
</div>
