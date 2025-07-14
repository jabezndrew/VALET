<div wire:poll.3s="loadFloorData">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Floor Header -->
    <div class="floor-header-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-2">{{ $floorLevel }}</h2>
                <p class="text-muted mb-0">Last Updated: {{ $lastUpdate ?? 'Never' }}</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="live-badge">LIVE</span>
            </div>
        </div>
    </div>

    <!-- Floor Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h3 class="stat-number total-color">{{ $floorStats['total'] }}</h3>
                <h6 class="text-muted">Total Spots</h6>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3 class="stat-number available-color">{{ $floorStats['available'] }}</h3>
                <h6 class="text-muted">Available</h6>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3 class="stat-number occupied-color">{{ $floorStats['occupied'] }}</h3>
                <h6 class="text-muted">Occupied</h6>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3 class="stat-number" style="color: #6f42c1;">{{ $floorStats['occupancy_rate'] }}%</h3>
                <h6 class="text-muted">Occupancy</h6>
            </div>
        </div>
    </div>

    <!-- Parking Spaces -->
    @if(count($spaces) > 0)
    <div class="parking-spaces-section">
        <h4 class="mb-4 fw-bold">Parking Spaces</h4>
        <div class="row">
            @foreach($spaces as $space)
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="parking-space-card {{ $space->is_occupied ? 'occupied' : 'available' }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="{{ $this->getSpaceIcon((array)$space) }}"></i>
                            #{{ $space->sensor_id }}
                        </h5>
                        <span class="status-badge-small {{ $space->is_occupied ? 'badge-occupied' : 'badge-available' }}">
                            {{ $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE' }}
                        </span>
                    </div>
                    
                    <div class="space-details">
                        <div class="mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="fw-bold">{{ $this->getStatusText((array)$space) }}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Distance:</span>
                            <span class="fw-bold">{{ $space->distance_cm ?? 'N/A' }}cm</span>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">Updated: {{ $space->updated_at->format('H:i:s') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="no-data-section text-center">
        <i class="fas fa-tools fa-4x mb-4 text-muted"></i>
        <h3>No Sensors Installed</h3>
        <p class="text-muted">{{ $floorLevel }} sensors are not yet installed. Please check back later.</p>
    </div>
    @endif
</div>