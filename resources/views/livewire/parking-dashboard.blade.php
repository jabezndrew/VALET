<div wire:poll.3s="loadParkingData">
    <!-- Header Section -->
    <div class="header-section">
        <div class="d-flex justify-content-center align-items-center mb-3">
            <h1>VALET</h1>
        </div>
        <div class="d-flex justify-content-center align-items-center mt-3">
            <span class="me-3">Last Updated: {{ $lastUpdate ?? 'Never' }}</span>
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
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number total">{{ $totalSpaces }}</div>
                <h5><i class="fas fa-parking"></i> Total Spaces</h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number available">{{ $availableSpaces }}</div>
                <h5><i class="fas fa-check-circle"></i> Available</h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="status-card stat-card">
                <div class="stat-number occupied">{{ $occupiedSpaces }}</div>
                <h5><i class="fas fa-car"></i> Occupied</h5>
            </div>
        </div>
    </div>

    <!-- Floor Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="status-card">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Filter by Floor</h5>
                    <select wire:model.live="floorFilter" class="form-select" style="max-width: 200px;">
                        <option value="all">All Floors</option>
                        <option value="1st Floor">1st Floor</option>
                        <option value="2nd Floor">2nd Floor</option>
                        <option value="3rd Floor">3rd Floor</option>
                        @foreach($availableFloors as $floor)
                            @if(!in_array($floor, ['1st Floor', '2nd Floor', '3rd Floor']))
                                <option value="{{ $floor }}">{{ $floor }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

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
                </div>
                
                <div class="text-center">
                    <small class="text-muted">Last Updated</small>
                    <div class="fw-bold">{{ $space->updated_at->format('H:i:s') }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @elseif(in_array($floorFilter, ['1st Floor', '2nd Floor', '3rd Floor']))
    <div class="no-data">
        <i class="fas fa-tools"></i>
        <h3>No Sensors Installed Yet</h3>
        <p>{{ $floorFilter }} sensors are not yet installed. Please check back later.</p>
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
    @endif
</div>