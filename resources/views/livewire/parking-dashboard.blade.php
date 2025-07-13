<div wire:poll.3s="loadParkingData">
    <!-- Header Section -->
    <div class="header-section">
        <h1><i class="fas fa-car"></i> VALET Smart Parking</h1>
        <p class="lead">University of San Jose-Recoletos - Quadricentennial Campus</p>
        <div class="d-flex justify-content-center align-items-center mt-3">
            <span class="me-3">Last Updated: {{ $lastUpdate ?? 'Never' }}</span>
            <button wire:click="refreshNow" class="btn btn-outline-light btn-sm me-2">
                <i class="fas fa-sync-alt"></i> Refresh Now
            </button>
            <button wire:click="toggleAutoRefresh" class="btn {{ $isAutoRefreshEnabled ? 'btn-success' : 'btn-secondary' }} btn-sm">
                <i class="fas fa-{{ $isAutoRefreshEnabled ? 'pause' : 'play' }}"></i>
                Auto-Refresh {{ $isAutoRefreshEnabled ? 'ON' : 'OFF' }}
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="status-card stat-card">
                <div class="stat-number total">{{ $totalSpaces }}</div>
                <h5><i class="fas fa-parking"></i> Total Spaces</h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card stat-card">
                <div class="stat-number available">{{ $availableSpaces }}</div>
                <h5><i class="fas fa-check-circle"></i> Available</h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card stat-card">
                <div class="stat-number occupied">{{ $occupiedSpaces }}</div>
                <h5><i class="fas fa-car"></i> Occupied</h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card stat-card">
                <div class="stat-number" style="color: #6f42c1;">{{ $occupancyRate }}%</div>
                <h5><i class="fas fa-chart-pie"></i> Occupancy Rate</h5>
            </div>
        </div>
    </div>

    <!-- Floor Filter -->
    @if(count($availableFloors) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="status-card">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filter by Floor</h5>
                    <select wire:model.live="floorFilter" class="form-select" style="max-width: 200px;">
                        <option value="all">All Floors</option>
                        @foreach($availableFloors as $floor)
                            <option value="{{ $floor }}">{{ $floor }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Floor Statistics -->
    @if(count($floorStats) > 1)
    <div class="row mb-4">
        <div class="col-12">
            <div class="status-card">
                <h5><i class="fas fa-building"></i> Floor Overview</h5>
                <div class="row">
                    @foreach($floorStats as $floorStat)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="{{ $this->getFloorIcon($floorStat['floor_level']) }} fa-2x mb-2"></i>
                                <h6>{{ $floorStat['floor_level'] }}</h6>
                                <div class="row">
                                    <div class="col-4">
                                        <small class="text-muted">Total</small>
                                        <div class="fw-bold">{{ $floorStat['total'] }}</div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-success">Available</small>
                                        <div class="fw-bold text-success">{{ $floorStat['available'] }}</div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-danger">Occupied</small>
                                        <div class="fw-bold text-danger">{{ $floorStat['occupied'] }}</div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small>{{ $floorStat['occupancy_rate'] }}% Occupied</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Parking Spaces Grid -->
    @if(count($spaces) > 0)
    <div class="row">
        @foreach($spaces as $space)
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">
                            <i class="{{ $this->getSpaceIcon((array)$space) }}"></i>
                            Sensor #{{ $space->sensor_id }}
                        </h5>
                        <small class="text-muted">{{ $space->floor_level }}</small>
                    </div>
                    <span class="status-badge {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                        {{ $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE' }}
                    </span>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ $this->getStatusText((array)$space) }}</span>
                        <span class="fw-bold">{{ $space->distance_cm ?? 'N/A' }}cm</span>
                    </div>
                    
                    @if($space->distance_cm)
                    <div class="distance-bar">
                        <div class="distance-fill" 
                             style="width: {{ $this->getDistancePercentage($space->distance_cm) }}%; 
                                    background-color: {{ $this->getDistanceColor($space->distance_cm) }};">
                        </div>
                    </div>
                    <small class="text-muted">Distance: {{ $this->getDistanceStatus($space->distance_cm) }}</small>
                    @endif
                </div>
                
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted">Last Updated</small>
                        <div class="fw-bold">{{ $space->updated_at->format('H:i:s') }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Created</small>
                        <div class="fw-bold">{{ $space->created_at->format('M d') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="no-data">
        <i class="fas fa-parking"></i>
        <h3>No Parking Data Available</h3>
        <p>No parking spaces have been configured yet. ESP32 sensors will automatically create spaces when they start sending data.</p>
        <div class="mt-4">
            <h5>Expected API Endpoint:</h5>
            <code>POST {{ url('/api/parking') }}</code>
            <br><br>
            <h6>Expected JSON Format:</h6>
            <pre class="text-start bg-light p-3 rounded">
{
    "sensor_id": 1,
    "is_occupied": false,
    "distance_cm": 45,
    "floor_level": "4th Floor"
}
            </pre>
        </div>
    </div>
    @endif

    <!-- Loading Indicator -->
    <div wire:loading class="position-fixed top-50 start-50 translate-middle">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // Handle auto-refresh toggle
        Livewire.on('enable-auto-refresh', () => {
            document.querySelector('#refreshStatus').className = 'refresh-status refresh-active';
            document.querySelector('#refreshStatus').innerHTML = '<i class="fas fa-sync-alt"></i> Live Updates ON';
        });
        
        Livewire.on('disable-auto-refresh', () => {
            document.querySelector('#refreshStatus').className = 'refresh-status refresh-error';
            document.querySelector('#refreshStatus').innerHTML = '<i class="fas fa-pause"></i> Live Updates OFF';
        });
    });
</script>