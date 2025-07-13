<div>
    <!-- Header Section -->
    <div class="header-section">
        <h1><i class="fas fa-car"></i> VALET Smart Parking</h1>
        <p class="lead">Real-time Parking Space Monitoring System</p>
        <p class="text-light">
            <i class="fas fa-clock"></i> Last updated: <span>{{ $lastUpdate }}</span>
        </p>
    </div>

    <!-- Error Message -->
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Floor Filter -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="status-card">
                <h6><i class="fas fa-filter"></i> Filter by Floor</h6>
                <select wire:model.live="floorFilter" class="form-select">
                    <option value="all">All Floors</option>
                    @foreach($availableFloors as $floor)
                        <option value="{{ $floor }}">{{ $floor }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="status-card">
                <h6><i class="fas fa-chart-bar"></i> Floor Statistics</h6>
                <div>
                    @if(count($floorStats) > 0)
                        @foreach($floorStats as $floorStat)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ $floorStat['floor_level'] }}:</strong>
                                <div>
                                    <span class="text-success">{{ $floorStat['available'] }}</span> / 
                                    <span class="text-danger">{{ $floorStat['occupied'] }}</span> / 
                                    <span class="text-primary">{{ $floorStat['total'] }}</span>
                                    <small class="text-muted">({{ $floorStat['occupancy_rate'] }}%)</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">Loading floor statistics...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number available">{{ $availableSpaces }}</div>
                <h6 class="text-muted mb-0">
                    <i class="fas fa-check-circle"></i> Available Spaces
                </h6>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number occupied">{{ $occupiedSpaces }}</div>
                <h6 class="text-muted mb-0">
                    <i class="fas fa-car"></i> Occupied Spaces
                </h6>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number total">{{ $totalSpaces }}</div>
                <h6 class="text-muted mb-0">
                    <i class="fas fa-th-large"></i> Total Spaces
                </h6>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div wire:loading class="text-center mb-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted mt-2">Updating parking data...</p>
    </div>

    <!-- Parking Spaces Grid -->
    <div class="row" wire:loading.class="loading">
        @if(count($spaces) > 0)
            @foreach($spaces as $space)
                <div class="col-lg-6 col-xl-4">
                    <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-microchip"></i> Sensor {{ $space->sensor_id }}
                                </h5>
                                <small class="text-muted">
                                    <i class="fas fa-building"></i> {{ $space->floor_level ?? '4th Floor' }}
                                </small>
                            </div>
                            <span class="status-badge {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                                <i class="{{ $space->is_occupied ? 'fas fa-car' : 'fas fa-check-circle' }}"></i>
                                {{ $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE' }}
                            </span>
                        </div>
                        
                        <div class="space-details">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Distance:</span>
                                <strong style="color: {{ $this->getDistanceColor($space->distance_cm) }}">
                                    {{ $space->distance_cm }} cm
                                </strong>
                            </div>
                            
                            <div class="distance-bar">
                                <div class="distance-fill" 
                                     style="width: {{ $this->getDistancePercentage($space->distance_cm) }}%; 
                                            background-color: {{ $this->getDistanceColor($space->distance_cm) }};"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <span class="text-muted">Last Updated:</span>
                                <small class="text-muted">{{ $space->updated_at->diffForHumans() }}</small>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted">Status:</span>
                                <span class="fw-bold {{ $space->is_occupied ? 'text-danger' : 'text-success' }}">
                                    {{ $space->is_occupied ? '🚗 Vehicle Present' : '✅ Space Free' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="col-12">
                <div class="status-card no-data">
                    <i class="fas fa-info-circle"></i>
                    <h5>
                        @if($floorFilter === 'all')
                            No Parking Sensors Detected
                        @else
                            No sensors found on {{ $floorFilter }}
                        @endif
                    </h5>
                    <p>Waiting for ESP32 sensors to send data...</p>
                    <small class="text-muted">Make sure your ESP32 is connected and sending data to the API</small>
                </div>
            </div>
        @endif
    </div>

    <!-- Manual Refresh Button -->
    <div class="text-center mt-4">
        <button wire:click="loadParkingData" class="btn btn-outline-light">
            <i class="fas fa-sync-alt" wire:loading.class="fa-spin"></i>
            <span wire:loading.remove>Refresh Now</span>
            <span wire:loading>Refreshing...</span>
        </button>
    </div>
</div>