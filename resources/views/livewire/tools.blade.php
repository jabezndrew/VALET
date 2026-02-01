<div>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Admin Tools</h2>
            <p class="text-muted mb-0">Quick access to all system management features</p>
        </div>

        <!-- Tools Grid -->
        <div class="row g-4">
            <!-- User Management -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.users') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">User Management</h5>
                            <p class="card-text text-muted small mb-2">Manage system users and access</p>
                            <span class="badge bg-primary">Management</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Pending Accounts -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.pending-accounts') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Pending Accounts</h5>
                            <p class="card-text text-muted small mb-2">Review and approve new accounts</p>
                            <span class="badge bg-warning text-dark">Approval</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- RFID Management -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.rfid') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
                                <i class="fas fa-id-card fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">RFID Management</h5>
                            <p class="card-text text-muted small mb-2">Manage RFID tags and assignments</p>
                            <span class="badge bg-success">Hardware</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Sensor Management -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('admin.sensors') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                                <i class="fas fa-microchip fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Sensor Management</h5>
                            <p class="card-text text-muted small mb-2">Configure parking sensors</p>
                            <span class="badge bg-info">Hardware</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Parking Log -->
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

            <!-- Vehicle Management -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('cars.index') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-danger bg-opacity-10 text-danger mx-auto mb-3">
                                <i class="fas fa-car fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Vehicle Management</h5>
                            <p class="card-text text-muted small mb-2">Manage registered vehicles</p>
                            <span class="badge bg-danger">Management</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Feedback -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('feedback.index') }}" class="text-decoration-none" wire:navigate>
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-purple bg-opacity-10 mx-auto mb-3" style="color: #6f42c1;">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Feedback</h5>
                            <p class="card-text text-muted small mb-2">View user feedback & reports</p>
                            <span class="badge" style="background-color: #6f42c1;">Support</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Clear Cache -->
            <div class="col-md-4 col-lg-3">
                <a href="{{ route('public.clear-cache', ['secret' => 'valet2025secret']) }}" class="text-decoration-none">
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-dark bg-opacity-10 text-dark mx-auto mb-3">
                                <i class="fas fa-broom fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Clear Cache</h5>
                            <p class="card-text text-muted small mb-2">Clear all system caches</p>
                            <span class="badge bg-dark">System</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Parking Display -->
            <div class="col-md-4 col-lg-3">
                <a href="/parking-display" class="text-decoration-none" target="_blank">
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
                                <i class="fas fa-tv fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Public Display</h5>
                            <p class="card-text text-muted small mb-2">Open public parking display</p>
                            <span class="badge bg-success">Display</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Guard Display -->
            <div class="col-md-4 col-lg-3">
                <a href="/guard" class="text-decoration-none" target="_blank">
                    <div class="card tool-card h-100">
                        <div class="card-body text-center py-4">
                            <div class="tool-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                            <h5 class="card-title mb-2">Guard Display</h5>
                            <p class="card-text text-muted small mb-2">Open guard monitoring screen</p>
                            <span class="badge bg-warning text-dark">Display</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

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
